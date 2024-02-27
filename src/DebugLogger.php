<?php
/**
 * User: fanyawei
 * Date: 2024/2/23 16:01
 */

namespace Fanyawei\Yii2Logger;

use yii\base\Component;
use yii\log\Logger;
use Yii;

class DebugLogger extends Component
{
    /**
     * @var string 用于注册到yii自有log组件中targets中的标识名
     */
    public $logTargetName = 'debug_logger';

    /**
     * @var string 将日志记录到的表格
     */
    public $logTable = 'yii_log_debug_logger';

    /**
     * @var string
     */
    public $category = '';

    /**
     * 将本组件生成的debug日志记录到数据库中以便后续分析
     * @param string $category
     * @return void
     */
    protected function addLogTarget():void
    {
        if (!isset(Yii::$app->log->targets[$this->logTargetName])) {
            if (Yii::$app->log->traceLevel < 2) {
                Yii::$app->log->traceLevel = 2;
            }
            Yii::$app->log->targets[$this->logTargetName] = Yii::createObject([
                'class' => 'yii\log\DbTarget',
                'logTable' => $this->logTable,
                'categories' => [],
                'prefix' => function ($message) {
                    if (!isset($message[4][1])) {
                        return '#';
                    }
                    $userID = 0;
                    if (!empty(Yii::$app->user->id)) {
                        $userID = Yii::$app->user->id;
                    }
                    $trace = $message[4][1];
                    return implode('#', [
                        $userID,
                        $trace['file'],
                        $trace['line'],
                    ]);
                },
                'logVars' => []
            ]);
        }
        if (empty($this->category) && !empty(Yii::$app->request->pathInfo)) {
            $this->category = Yii::$app->request->pathInfo;
        }

        Yii::$app->log->targets[$this->logTargetName]->categories[$this->category] = $this->category;
    }

    /**
     * @param string|array $message
     * @return void
     */
    public function trace($message)
    {
        $this->addLogTarget();
        Yii::getLogger()->log($message, Logger::LEVEL_TRACE, $this->category);
    }

    /**
     * @param string|array $message
     * @return void
     */
    public function info($message)
    {
        $this->addLogTarget();
        Yii::getLogger()->log($message, Logger::LEVEL_INFO, $this->category);
    }

    /**
     * @param string|array $message
     * @return void
     */
    public function warning($message)
    {
        $this->addLogTarget();
        Yii::getLogger()->log($message, Logger::LEVEL_WARNING, $this->category);
    }

    /**
     * @param string|array $message
     * @return void
     */
    public function error($message)
    {
        $this->addLogTarget();
        Yii::getLogger()->log($message, Logger::LEVEL_ERROR, $this->category);
    }
}