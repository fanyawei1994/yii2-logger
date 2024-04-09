<?php
/**
 * User: fanyawei
 * Date: 2024/3/20 14:54
 */

namespace Fanyawei\Yii2Logger\ErrorLogger;

use Fanyawei\Yii2Logger\ErrorLogger;
use HttpException;
use Yii;
use yii\debug\panels\DbPanel;

/**
 * 日志查看
 */
class ActionLoggerView extends ErrorLoggerBaseAction
{

    public function run()
    {
        if (empty($this->debugModule)) {
            $this->debugModule = ErrorLogger::getDebugModule();
        }
        $tag = Yii::$app->request->get('tag');
        $panel = Yii::$app->request->get('panel');

        if ($tag === null) {
            $tag = YiiLogErrorLoggerMeta::find()->orderBy('id desc')->select('category')->scalar();
        }
        $this->loadData($tag);

        if (isset($this->debugModule->panels[$panel])) {
            $activePanel = $this->debugModule->panels[$panel];
        } else {
            $activePanel = $this->debugModule->panels[$this->debugModule->defaultPanel];
        }
        if (Yii::$app->request->isAjax) {
            return $this->dbExplain();
        }

        if ($activePanel->hasError()) {
            Yii::$app->errorHandler->handleException($activePanel->getError());
        }
        if (isset($this->debugModule->panels['user'])) {
            $this->debugModule->panels['user']->init();
        }

        $this->controller->summary = $this->summary;
        return $this->controller->render('@yii/debug/views/default/view', [
            'tag' => $tag,
            'summary' => $this->summary,
            'manifest' => $this->getManifest($tag),
            'panels' => $this->debugModule->panels,
            'activePanel' => $activePanel,
        ]);
    }


    /**
     * @return string
     */
    public function dbExplain()
    {
        if (empty($this->debugModule->panels['db'])) {
            exit('error');
        }
        $panel = $this->debugModule->panels['db'];
        $timings = $panel->calculateTimings();
        $seq = Yii::$app->request->get('seq');

        if (!isset($timings[$seq])) {
            throw new HttpException(404, 'Log message not found.');
        }

        $query = $timings[$seq]['info'];

        $results = $panel->getDb()->createCommand('EXPLAIN ' . $query)->queryAll();

        $output[] = '<table class="table"><thead><tr>' . implode(array_map(function ($key) {
                return '<th>' . $key . '</th>';
            }, array_keys($results[0]))) . '</tr></thead><tbody>';

        foreach ($results as $result) {
            $output[] = '<tr>' . implode(array_map(function ($value) {
                    return '<td>' . (empty($value) ? 'NULL' : htmlspecialchars($value)) . '</td>';
                }, $result)) . '</tr>';
        }
        $output[] = '</tbody></table>';
        return implode($output);
    }
}