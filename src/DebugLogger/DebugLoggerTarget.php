<?php
/**
 * User: fanyawei
 * Date: 2024/3/1 16:42
 */

namespace Fanyawei\Yii2Logger\DebugLogger;

use Yii;
use yii\db\Exception;
use yii\helpers\VarDumper;
use yii\log\DbTarget;
use yii\log\LogRuntimeException;

/**
 * 程序运行中debug日志记录d
 */
class DebugLoggerTarget extends DbTarget
{
    /**
     * @var string 应用标识，当为空时默认获取Yii::$app->id
     */
    public $appID = '';

    /**
     * @var int 请求用户ID，为空时默认获取Yii::$app->user->id
     */
    public $userID = 0;

    /**
     * @var string 用户名，为空时默认获取应用登录user的username属性
     */
    public $username = '';

    /**
     * Stores log messages to DB.
     * Starting from version 2.0.14, this method throws LogRuntimeException in case the log can not be exported.
     * @throws Exception
     * @throws LogRuntimeException
     */
    public function export()
    {
        if ($this->db->getTransaction()) {
            // create new database connection, if there is an open transaction
            // to ensure insert statement is not affected by a rollback
            $this->db = clone $this->db;
        }
        if (empty($this->appID)) {
            $this->appID = Yii::$app->id;
        }
        if (empty($this->userID) && !empty(Yii::$app->user->id)) {
            $this->userID = intval(Yii::$app->user->id);
        }
        if (empty($this->username) && !empty(Yii::$app->user->identity->username)) {
            $this->username = Yii::$app->user->identity->username;
        }

        $tableName = $this->db->quoteTableName($this->logTable);
        $sql = "INSERT INTO $tableName ([[app_id]], [[level]], [[category]], [[log_time]], [[prefix]], [[message]], [[user_id]], [[username]])
                VALUES (:app_id, :level, :category, :log_time, :prefix, :message, :user_id, :username)";
        $command = $this->db->createCommand($sql);
        foreach ($this->messages as $message) {
            list($text, $level, $category, $timestamp) = $message;
            if (!is_string($text)) {
                // exceptions may not be serializable if in the call stack somewhere is a Closure
                if ($text instanceof \Exception || $text instanceof \Throwable) {
                    $text = (string) $text;
                } else {
                    $text = VarDumper::export($text);
                }
            }
            if ($command->bindValues([
                    ':app_id' => $this->appID,
                    ':level' => $level,
                    ':category' => $category,
                    ':log_time' => $timestamp,
                    ':prefix' => $this->getMessagePrefix($message),
                    ':message' => $text,
                    ':user_id' => $this->userID,
                    ':username' => $this->username,
                ])->execute() > 0) {
                continue;
            }
            throw new LogRuntimeException('Unable to export log through database!');
        }
    }
}
