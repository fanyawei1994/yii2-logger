<?php
/**
 * User: fanyawei
 * Date: 2024/3/20 11:30
 */

namespace Fanyawei\Yii2Logger;

use Yii;
use yii\base\InvalidConfigException;
use yii\debug\FlattenException;
use yii\debug\Module;
use yii\debug\panels\DbPanel;
use yii\log\DbTarget;

/**
 * 将请求响应异常的debug日志记录到数据库
 */
class ErrorLogger extends DbTarget
{
    /**
     * 当错误日志达到一定数量后自动进行表数据删除，设置为0时不自动删除数据
     * @var int
     */
    public $maxLogNumber = 0;

    /**
     * @var callable 检查当前请求是否进行日志记录的函数
     * function () {
     *    if (Yii::$app->response->statusCode == 500) {
     *        return true;
     *    }
     *    return false;
     * }
     */
    public $checkIsSaveLog;

    /**
     * @var string 当前应用标识
     */
    public $appID;

    /**
     * @var int 当前请求用户ID
     */
    public $userID;

    /**
     * @var string 当前请求用户名
     */
    public $username;

    /**
     * @var string 当前请求用户ip
     */
    public $userIP;


    public function export()
    {
        if (!is_callable($this->checkIsSaveLog)) {
            throw new InvalidConfigException('请配置一个有效的checkIsSaveLog回调函数');
        }
        //判断当前请求是否进行日志记录
        if (!call_user_func($this->checkIsSaveLog)) {
            return;
        }
        if (empty($this->appID)) {
            $this->appID = Yii::$app->id;
        }
        if (empty($this->userID)) {
            $this->userID = intval(Yii::$app->user->id);
        }
        if (empty($this->username)) {
            $this->username = strval(Yii::$app->user->identity->username ?? '');
        }
        if (empty($this->userIP)) {
            $this->userIP = Yii::$app->request->userIP;
        }

        $tableName = $this->db->quoteTableName($this->logTable);
        //自动进行日志数据清除
        $this->clearTableData($tableName);

        $debugTag = self::getDebugTag();

        $existSql = "SELECT EXISTS(SELECT * FROM {$tableName} WHERE `category`='{$debugTag}')";
        if ($this->db->createCommand($existSql)->queryScalar()) {
            return;
        }

        $insertSql = "INSERT INTO {$tableName} ([[app_id]], [[category]], [[message]], [[log_time]], [[user_id]], [[username]], [[ip]])
                VALUES (:app_id, :category, :message, :log_time, :user_id, :username, :ip)";
        $command = $this->db->createCommand($insertSql);
        $oldEnableLogging = Yii::$app->db->enableLogging;
        $enableProfiling = Yii::$app->db->enableProfiling;
        Yii::$app->db->enableLogging = false;
        Yii::$app->db->enableProfiling = false;
        $command->bindValues([
            ':app_id' => $this->appID,
            ':category' => $debugTag,
            ':message' => $this->getDebugMessage(),
            ':log_time' => time(),
            ':user_id' => $this->userID,
            ':username' => $this->username,
            ':ip' => Yii::$app->request->userIP,
        ])->execute();
        Yii::$app->db->enableLogging = $oldEnableLogging;
        Yii::$app->db->enableProfiling = $enableProfiling;
    }

    /**
     * @return string
     */
    public function getDebugMessage()
    {
        $data = [];
        $exceptions = [];
        $oldTarget = self::getDebugModule()->logTarget;
        self::getDebugModule()->logTarget = $this;
        $summary = $this->collectSummary();
        foreach (self::getDebugModule()->panels as $id => $panel) {
            try {
                $panelData = $panel->save();
                if ($id === 'profiling') {
                    $summary['peakMemory'] = $panelData['memory'];
                    $summary['processingTime'] = $panelData['time'];
                }
                $data[$id] = serialize($panelData);
            } catch (\Exception $exception) {
                $exceptions[$id] = new FlattenException($exception);
            }
        }
        $data['summary'] = $summary;
        $data['exceptions'] = $exceptions;
        self::getDebugModule()->logTarget = $oldTarget;
        return serialize($data);
    }

    /**
     * 自动清除表格数据
     * @param string $tableName
     */
    public function clearTableData($tableName)
    {
        if ($this->maxLogNumber <= 0) {
            return;
        }
        $sql = "SELECT COUNT(*) FROM {$tableName}";
        $count = $this->db->createCommand($sql)->queryScalar();
        if($count > $this->maxLogNumber){
            $sql = "TRUNCATE TABLE {$tableName}";//删除所有数据，保留表结构
            $this->db->createCommand($sql)->execute();
        }
    }

    private static $_debugTag;
    public static function getDebugTag()
    {
        if(empty(self::$_debugTag)){
            self::$_debugTag = uniqid();
        }
        return self::$_debugTag;
    }


    /**
     * @return Module
     */
    public static function getDebugModule(){
        if(!Yii::$app->getModule('debug')){
            Yii::$app->setModule('debug',[
                'class' => 'yii\debug\Module',
            ]);
        }
        /** @var Module $debugModule */
        $debugModule = Yii::$app->getModule('debug');

        return $debugModule;
    }

    /**
     * Processes the given log messages.
     * This method will filter the given messages with [[levels]] and [[categories]].
     * And if requested, it will also export the filtering result to specific medium (e.g. email).
     * @param array $messages log messages to be processed. See [[\yii\log\Logger::messages]] for the structure
     * of each message.
     * @param bool $final whether this method is called at the end of the current application
     * @throws \yii\base\Exception
     */
    public function collect($messages, $final)
    {
        $this->messages = array_merge($this->messages, $messages);
        if ($final) {
            $this->export();
        }
    }

    /**
     * Collects summary data of current request.
     * @return array
     */
    protected function collectSummary()
    {
        if (Yii::$app === null) {
            return [];
        }

        $request = Yii::$app->getRequest();
        $response = Yii::$app->getResponse();
        $summary = [
            'tag' => self::getDebugTag(),
            'url' => $request instanceof yii\console\Request ? "php yii " . implode(' ', $request->getParams()): $request->getAbsoluteUrl(),
            'ajax' => $request instanceof yii\console\Request ? 0 : (int) $request->getIsAjax(),
            'method' => $request instanceof yii\console\Request ? 'COMMAND' : $request->getMethod(),
            'ip' => $request instanceof yii\console\Request ? exec('whoami') : $request->getUserIP(),
            'time' => $_SERVER['REQUEST_TIME_FLOAT'],
            'statusCode' => $response instanceof yii\console\Response ? $response->exitStatus : $response->statusCode,
            'sqlCount' => $this->getSqlTotalCount(),
            'excessiveCallersCount' => $this->getExcessiveDbCallersCount(),
        ];

        if (isset($this->module->panels['mail'])) {
            $mailFiles = self::getDebugModule()->panels['mail']->getMessagesFileName();
            $summary['mailCount'] = count($mailFiles);
            $summary['mailFiles'] = $mailFiles;
        }

        return $summary;
    }

    /**
     * Get the number of excessive Database caller(s).
     *
     * @return int
     * @since 2.1.23
     */
    protected function getExcessiveDbCallersCount()
    {
        if (!isset(self::getDebugModule()->panels['db'])) {
            return 0;
        }
        /** @var DbPanel $dbPanel */
        $dbPanel = self::getDebugModule()->panels['db'];

        return $dbPanel->getExcessiveCallersCount();
    }

    /**
     * Returns total sql count executed in current request. If database panel is not configured
     * returns 0.
     * @return int
     */
    protected function getSqlTotalCount()
    {
        if (!isset(self::getDebugModule()->panels['db'])) {
            return 0;
        }
        $profileLogs = self::getDebugModule()->panels['db']->getProfileLogs();

        # / 2 because messages are in couple (begin/end)

        return count($profileLogs) / 2;
    }
}
