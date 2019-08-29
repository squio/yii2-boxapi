<?php

namespace squio\boxapi\controllers;

use yii\web\Controller;
use yii\helpers\Url;

/**
 * Default controller for the `boxapi` module
 */
class DefaultController extends Controller
{

    /**
     * Process callback from Box OAuth
     * Redirect to last local url if set
     * @return redirect
     */
    public function actionIndex()
    {
        $res = false;
        if (isset($_GET['code'])) {
            $res = \Yii::$app->controller->module->authorizeToken($_GET['code']);
            // TODO handle error
        }
        $url = Url::previous();
        if ($url && $res) {
            return $this->redirect($url);
        } else {
            return $this->redirect(Url::toRoute('/'));
        }
    }
}
