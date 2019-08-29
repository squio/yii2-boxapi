<?php
/**
 * Persist BOX auth token data in related user record
 * based on auth user_id
 */
namespace squio\boxapi\models;

use Yii;
use yii\helpers\Json;

class BoxUser extends \yii\db\ActiveRecord
{
    /**
     * If set, this key will be used to encrypt and decrypt stored data in database
     * @var string
     */
    public $secretKey = null;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%box_user}}';
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'user_id'], 'integer'],
            [['token'], 'string']
        ];
    }

    /**
     * Return token data as array
     * @return array
     */
    public function getTokenData()
    {
        if ($this->secretKey) {
            $json_data = Yii::$app->getSecurity()->decryptByKey($this->token, $this->secretKey);
        } else {
            $json_data = $this->token;
        }
        return Json::decode($json_data);
    }

    /**
     * Set token data from array
     * @param array $tokenData
     * @return void
     */
    public function setTokenData($tokenData)
    {
        $json_data = Json::encode($tokenData);
        if ($this->secretKey) {
            $this->token = Yii::$app->getSecurity()->encryptByKey($json_data, $this->secretKey);
        } else {
            $this->token = $json_data;
        }
    }

}
