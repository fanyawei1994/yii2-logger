<?php
/**
 * User: fanyawei
 * Date: 2024/3/20 15:08
 */

namespace Fanyawei\Yii2Logger\ErrorLogger;

use Fanyawei\Yii2Logger\ErrorLogger;
use yii\base\BaseObject;
use yii\BaseYii;
use yii\data\ActiveDataProvider;
use yii\debug\Module;
use yii\grid\GridView;

/**
 * 日志列表
 */
class ActionLoggerIndex extends ErrorLoggerBaseAction
{
    /**
     * @var Module
     */
    public $debugModule;

    public function run()
    {
        if (empty($this->debugModule)) {
            $this->debugModule = ErrorLogger::getDebugModule();
        }
        $dataProvider = new ActiveDataProvider([
            'query' => YiiLogErrorLoggerMeta::find(),
            'pagination' => [
                'pageSize' => self::PAGE_SIZE,
            ],
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC],
            ]
        ]);

        $lastLogTag = YiiLogErrorLoggerMeta::find()->orderBy('id desc')->select('category')->scalar();
        $this->loadData($lastLogTag);

        $models = $dataProvider->getModels();
        foreach ($models as $i => $model){
            $logContent = unserialize($model['message']);
            $models[$i] = $logContent['summary'];
        }
        if (isset($this->debugModule->panels['user'])) {
            $this->debugModule->panels['user']->init();
        }

        BaseYii::$container->set(GridView::class, ['filterPosition' => -1]);
        $dataProvider->setModels($models);
        return $this->controller->render('@yii/debug/views/default/index', [
            'panels' => $this->debugModule->panels,
            'dataProvider' => $dataProvider,
            'searchModel' => new YiiLogErrorLoggerMeta(),
            'manifest' => $this->getManifest(),
        ]);
    }
}