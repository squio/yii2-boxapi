<?php

namespace squio\boxapi\controllers;

use yii\web\Controller;
use yii\helpers\Url;
use squio\boxapi\traits\BoxContent;
/**
 * Default controller for the `boxapi` module
 */
class DefaultController extends Controller
{
    use BoxContent;

    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        $module = \Yii::$app->controller->module->id;
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
