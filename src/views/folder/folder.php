<?php
/**
 * Display Folder listing
 * Each sub folder is linked to this list action (index)
 * Each file is linked to the download action (download)
 */
use yii\helpers\Html;
use yii\grid\GridView;

$pathBase = '/' . Yii::$app->controller->route;

// Display folder hierarchy in breadcrumbs
$this->params['breadcrumbs'][] = ['label' => _('Box')];
if ($data['path_collection'] && $data['path_collection']['entries']) {
    foreach ($data['path_collection']['entries'] as $dir) {
        $this->params['breadcrumbs'][] = ['label' => $dir['name'], 'url' => [$pathBase, 'id' => $dir['id']]];
    }
}
$this->params['breadcrumbs'][] = ['label' => $data['name']];
?>
<div class="boxapi-folder-index">
   <h1><?= Html::encode($data['name']) ?></h1>

   <?= GridView::widget([
      'dataProvider' => $dataProvider,
      'columns' => [
          // ['class' => 'yii\grid\SerialColumn'],
          //'id',
          'type' => [
              'attribute' => 'type',
              'value' => function($model) {
                  return '<span  class="glyphicon glyphicon-' .
                     (($model['type'] == 'folder') ? 'folder-close' : 'file') .
                     '" aria-hidden="true"></span>';
              },
              'format' => 'html',
          ],
          'name' => [
              'attribute' => 'name',
              'value' => function($model) {
                  $url = '/' . Yii::$app->controller->route;
                  $version = null;
                  if ($model['type'] != 'folder') {
                      // turn action into download
                      $url = preg_replace('/(\/|\/index)$/', '/download', $url);
                      if (isset($model['file_version'])) {
                          $version = $model['file_version']['id'];
                      }
                  }
                  return Html::a(Html::encode($model['name']), [$url, 'id' => $model['id'], 'version' => $version]);
              },
              'format' => 'html',
          ],
        ],
  ]); ?>
</div>
