<?php
namespace squio\boxapi;

use Yii;
use yii\helpers\Url;
use yii\base\InvalidCallException;

use squio\boxapi\models\BoxUser;
use squio\boxapi\traits\BoxContent;

/**
 * boxapi module definition class
 */
class BoxApi extends \yii\base\Module
{
    use BoxContent;

    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'squio\boxapi\controllers';

    public $config = [
        'data_crypt_key' => null,
        'expiration'	 => 60,
    ];

    // These urls are used for Box Content API
    protected $token_url     = 'https://api.box.com/oauth2/token';
    protected $api_url       = 'https://api.box.com/2.0';
    protected $upload_url    = 'https://upload.box.com/api/2.0';
    protected $authorize_url = 'https://app.box.com/api/oauth2/authorize';

    private $auth_header;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        // require logged-in user
        if (Yii::$app->user->isGuest) {
            throw new InvalidCallException('You must be logged in to use ' . __CLASS__);
        }
        $this->config['user_id'] = Yii::$app->user->identity->id;
        $this->config['redirect_uri'] = $this->redirect_url();
    }

    /**
     * Redirect URL for OAuth response
     * @return string
     */
    public function redirect_url()
    {
        // set redirect URI to absolute url for path '/boxapi'
        // which invokes defaultController::actionIndex
        return Url::toRoute('/' . $this->id, true);
    }

    /**
     * Authenticate at the BOX API endpoint
     * @param int $user_id
     * @return void
     */
    public function authenticate()
    {
        $tokenData = $this->readToken();
        if (! $tokenData) {
            $this->redirectAuthCode();
        } elseif ($tokenData['expired']) {
            // access_token is expired, try to refresh
            $tokenData = $this->refreshToken($tokenData['refresh_token']);
            $access_token = $tokenData['access_token'];
        } else {
            $access_token = $tokenData['access_token'];
        }
        $this->auth_header = "Authorization: Bearer " . $access_token;
    }


    /**
     * Initialize OAuth request
     * - redirect to OAuth endpoint for Box API
     * @return void
     */
    protected function redirectAuthCode()
    {
        $url = $this->authorize_url.'?'.http_build_query(array(
            'response_type'  => 'code',
            'client_id'      => $this->config['client_id'],
            'redirect_uri'   => $this->config['redirect_uri'],
        ));
        Url::remember();
        Yii::$app->response->redirect($url)->send();
        Yii::$app->end();
    }

    /**
     * Get access_token based on OAuth callback code
     * @param  string $code OAuth authorization code
     * @return array $tokenData
     */
    public function authorizeToken($code)
    {
        $url = $this->token_url;
        $params = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'redirect_uri'   => $this->config['redirect_uri'],
        ];

        try {
            $strToken = $this->http_post($url, $params, 'application/x-www-form-urlencoded');
        } catch (\yii\web\HttpException $e) {
            if ($e->statusCode !== 200) {
                Yii::warning("Got HTTP error " . $e->statusCode . ":\n" . $e->getMessage());
                // TODO error handling
                // {"error":"invalid_grant","error_description":"The authorization code has expired"}
                // {"error":"invalid_request","error_description":"Invalid grant_type parameter or parameter missing"}
                // https://box-content.readme.io/docs/oauth-20
                return false;
            }
        }
        return $this->writeToken($strToken);
    }

    /**
     * REfresh access_token
     * @param string $refresh_token
     * @return array $tokenData
     */
    protected function refreshToken($refresh_token)
    {
        $url = $this->token_url;
        $params = [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refresh_token,
            'client_id'     => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
        ];
        try {
            $strToken = $this->http_post($url, $params, 'application/x-www-form-urlencoded');
        } catch (\yii\web\HttpException $e) {
            if ($e->statusCode == 400) {
                // [*:message] => '{\"error\":\"invalid_grant\",\"error_description\":\"Refresh token has expired\"}'
                Yii::trace("Got HTTP error 400:\n" . $e->getMessage());
                $response = \json_decode($e->getMessage(), true);
                if ($response['error'] &&
                    $response['error'] == 'invalid_grant') {
                        // reset refresh token and redirect user to OAuth page
                        $this->redirectAuthCode();
                }
            } else {
                Yii::trace("Got HTTP error " . $e->statusCode . ":\n" . $e->getMessage());
                $this->redirectAuthCode();
            }
        }
        if (!isset($strToken)) {
            Yii::trace("Got no token, calling redirectAuthCode()\n");
            $this->redirectAuthCode();    
        }
        return $this->writeToken($strToken);
    }


    /**
     * Read back token data from persisted storage
     * @return array
     * [
     *   'access_token' => 'rNWKpq67z93yWnroxZaFPYlBC1bWVzqb'
     *   'expires_in' => 3729
     *   'restricted_to' => []
     *   'refresh_token' => '9DeWjIEb...mEcUSaD4ol3vSi4RnyLb'
     *   'token_type' => 'bearer'
     *   'timestamp' => 1560891596
     *   'expired' => false
     * ]
     * unix timestamp: bash: date -r 1560891596
     */
    public function readToken()
    {
        $bu = BoxUser::find()
            ->where(['user_id' => $this->config['user_id']])
            ->one();
        if (!$bu) {
            return false;
        }
        $bu->secretKey = $this->config['data_crypt_key'];
        $tokenData = $bu->tokenData;
        $tokenData['expired'] = ($tokenData['expires_in'] + $tokenData['timestamp'] < time());
        return $tokenData;
    }

    /**
     * Persist internal token data to storage
     * @param string|array $tokenData json encoded array of token data
     * @return array|bool tokenData or false
     */
    public function writeToken($tokenData)
    {
        if (!\is_array($tokenData)) {
            $array = json_decode($tokenData, true);
        } else {
            $array = $tokenData;
        }
        if (isset($array['error'])) {
            $this->error = $array['error_description'];
            return false;
        } else {
            $array['timestamp'] = time();
            $bu = BoxUser::find()
                ->where(['user_id' => $this->config['user_id']])
                ->one();
            if (!$bu) {
                $bu = new BoxUser(['user_id' => $this->config['user_id']]);
            }
            $bu->secretKey = $this->config['data_crypt_key'];
            $bu->tokenData = $array;
            $bu->save();
            return $array;
        }
    }
}
