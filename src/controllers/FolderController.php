<?php

namespace squio\boxapi\controllers;

use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ArrayDataProvider;
/**
 * File and Folder access controller for the `boxboxApi` module
 * Base path = /boxapi/folder
 */
class FolderController extends Controller
{
    private $boxApi;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
            'access' => [
                'class' => AccessControl::class,
                // 'except' => [ ], // covers all actions
                // 'only' => ['index','week','view', 'authorize'],
                'rules' => [
                    // allow authenticated users
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    // everything else is denied
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        // Obtain a refreence to the module as $boxApi
        $this->boxApi = \Yii::$app->controller->module;
        // Authenticate before any API call is made
        $this->boxApi->authenticate();
        return parent::beforeAction($action);
    }

    /**
     * List folder index
     * @param int $id the folder ID
     * @return string
     */
    public function actionIndex($id = 0)
    {
        $data = $this->boxApi->getFolderInfo($id);
        $dataProvider = new ArrayDataProvider([
            'key' => 'id',
            'totalCount' => $data['item_collection']['total_count'],
            'allModels' => $data['item_collection']['entries'],
            'sort' => [
                'attributes' => ['id','name','type'],
            ],
        ]);
        return $this->render('folder', [
            'data' => $data,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Download a file
     * @param int $id the File ID
     * @param int $versionId, optional
     * @return string
     */
    public function actionDownload($id = 0, $version=null)
    {
        if ($version) {
            return $this->redirect($this->boxApi->downloadFile($id, $version));
        } else {
            return $this->redirect($this->boxApi->downloadFile($id));
        }
    }
}
