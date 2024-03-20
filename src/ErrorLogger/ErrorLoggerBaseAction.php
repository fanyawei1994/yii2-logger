<?php
/**
 * User: fanyawei
 * Date: 2024/3/20 16:13
 */

namespace Fanyawei\Yii2Logger\ErrorLogger;

use yii\base\Action;
use yii\debug\Module;
use yii\helpers\ArrayHelper;
use Yii;
use yii\web\Response;
use yii\web\View;

/**
 * Class ErrorLoggerBaseAction
 * @package Fanyawei\Yii2Logger\ErrorLogger
 */
class ErrorLoggerBaseAction extends Action
{
    public const PAGE_SIZE = 50;
    /**
     * @var Module
     */
    public $debugModule;

    protected $summary;
    
    public function beforeRun()
    {
        if(!empty(Yii::$app->getModule('debug'))){
            // do not display debug toolbar when in debug view mode
            Yii::$app->getView()->off(View::EVENT_END_BODY, [Yii::$app->getModule('debug'), 'renderToolbar']);
            Yii::$app->getResponse()->off(Response::EVENT_AFTER_PREPARE, [Yii::$app->getModule('debug'), 'setDebugHeaders']);
        }
        return parent::beforeRun();
    }

    public function loadData($tag)
    {
        $where = ['category' => $tag];
        $logContent = YiiLogErrorLoggerMeta::find()->where($where)->select('message')->scalar();
        if(empty($logContent)){
            exit('数据已删除');
        }
        $logContent = unserialize($logContent);
        $exceptions = $logContent['exceptions'];
        foreach ($this->debugModule->panels as $id => $panel) {
            if (isset($logContent[$id])) {
                $panel->tag = $tag;
                $panel->load(unserialize($logContent[$id]));
            }
            if (isset($exceptions[$id])) {
                $panel->setError($exceptions[$id]);
            }
        }
        $this->summary = $logContent['summary'];
    }

    public function getManifest($limit = self::PAGE_SIZE)
    {

        $models = YiiLogErrorLoggerMeta::find()->limit($limit)->orderBy('id desc')->select('message')->column();
        $data = [];
        foreach ($models as $model){
            $logContent = unserialize($model);
            $index = ArrayHelper::getValue($logContent, ['summary', 'tag'], '');
            $data[$index] = $logContent['summary'];
        }
        return $data;
    }
}
