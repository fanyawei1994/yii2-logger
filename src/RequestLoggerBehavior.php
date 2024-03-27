<?php
/**
 * User: fanyawei
 * Date: 2024/3/25 18:56
 */

namespace Fanyawei\Yii2Logger;

use Exception;
use Yii;
use yii\base\ActionEvent;
use yii\base\Behavior;
use yii\db\Connection;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\Controller;

/**
 * 应用请求日志记录
 */
class RequestLoggerBehavior extends Behavior
{
    /**
     * @var array 定义当前请求是否添加日志
     * [
     *    'index' => false,
     *    'create' => Yii::$app->request->isPost,
     *    'update' => function (ActionEvent $event) {
     *        return Yii::$app->request->isPost;
     *    },
     *    'delete' => true,
     * ]
     */
    public $actions = [];

    /**
     * @var array 自定义日志存储的get参数
     * [
     *     'update' => function ($actionID) {
     *         return [
     *              ....
     *         ];
     *     },
     *     '__default' => function ($actionID) {
     *        return [];//...
     *     }
     * ]
     */
    public $getParamCallback = [];

    /**
     * @var array 自定义日志存储的post参数
     * [
     *     'update' => function ($actionID) {
     *         return [
     *              ....
     *         ];
     *     },
     *    '__default' => function ($actionID) {
     *        return [];//...
     *     }
     * ]
     */
    public $postParamCallback = [];

    /**
     * @var array 返回dataCode标识，区分请求返回状态
     * [
     *     'update' => function ($actionID) {
     *         return [
     *              ....
     *         ];
     *     },
     *    '__default' => function ($actionID) {
     *        return [];//...
     *     }
     * ]
     */
    public $getDataCode = [];

    /**
     * @var string 记录日志的数据表
     */
    public $logTable = 'yii_log_request';

    /**
     * @var Connection 日志表所在数据库连接
     */
    public $db;

    /**
     * @var string 用户名字段
     */
    public $userNameField = 'username';

    /**
     * 注册事件
     * @return array
     */
    public function events():array
    {
        return array_merge(parent::events(), [
            Controller::EVENT_BEFORE_ACTION => [$this, 'beforeAction'],
        ]);
    }

    /**
     * 当请求需要添加请求日志时，记录一条请求日志
     * @param ActionEvent $event
     * @return void
     */
    public function beforeAction(ActionEvent $event):void
    {
        if (!isset($this->actions[$event->action->id])) {
            return;
        }

        register_shutdown_function(function () use ($event) {
            $this->insertRequestLog($event);
        });
    }

    /**
     * 添加一条请求日志
     * @return void
     */
    public function insertRequestLog(ActionEvent $event)
    {
        try {
            if (isset(Yii::$app->params['requestActionIsAddLog'])) {
                $actionIsAddLog = Yii::$app->params['requestActionIsAddLog'];
            } else {
                $actionIsAddLog = $this->actions[$event->action->id];
                if (is_callable($actionIsAddLog)) {
                    $actionIsAddLog = call_user_func_array($actionIsAddLog, [$event]);
                }
            }
            if (!$actionIsAddLog) {
                return;
            }
            $dbConnection = $this->getDbConnection();
            $tableName = $dbConnection->quoteTableName($this->logTable);
            $insertSql = "INSERT INTO {$tableName} ([[url]], [[method]], [[code]], [[data_code]], [[get_params]],
                   [[post_params]], [[extra_data]],[[param_length]], [[time]], [[memory]], [[created_at]], [[user_id]],
                   [[user_name]], [[request_ip]])
                VALUES (:url, :method, :code, :data_code,:get_params, :post_params, :extra_data, :param_length, :time,
                        :memory, :created_at, :user_id, :user_name, :request_ip)";
            $command = $this->db->createCommand($insertSql);


            $getParams = Json::encode($this->requestGetParams());
            $postParams = Json::encode($this->requestPostParams());
            $extraData = ArrayHelper::getValue(Yii::$app->params, ['requestLoggerExtraData'], '');
            $command->bindValues([
                ':url' => Yii::$app->request->pathInfo,
                ':method' => Yii::$app->request->getMethod(),
                ':code' => intval(Yii::$app->response->statusCode),
                ':data_code' => $this->getDataCode(),
                ':get_params' => $getParams,
                ':post_params' => $postParams,
                ':extra_data' => $extraData,
                ':param_length' => strlen($getParams) + strlen($postParams) + strlen($extraData),
                ':time' => intval(round((microtime(true) - YII_BEGIN_TIME)*1000)),
                ':memory' => memory_get_peak_usage(),
                ':created_at' => time(),
                ':user_id' => intval(Yii::$app->user->id),
                ':user_name' => Yii::$app->user->identity->{$this->userNameField} ?? '',
                ':request_ip' => Yii::$app->request->userIP,
            ])->execute();
        } catch (Exception $exception) {
            Yii::error($exception);
        }
    }

    /**
     * @return Connection
     */
    public function getDbConnection():Connection
    {
        if (!empty($this->db)) {
            return $this->db;
        }
        return $this->db = Yii::$app->db;
    }

    /**
     * 获取请求get参数
     * @return array
     */
    public function requestGetParams():array
    {
        $actionID = Yii::$app->requestedAction->id;
        if (!empty($this->getParamCallback[$actionID])) {
            return call_user_func_array($this->getParamCallback[$actionID], [$actionID]);
        }
        if (!empty($this->getParamCallback['__default'])) {
            //通用参数处理回调
            return call_user_func_array($this->getParamCallback['__default'], [$actionID]);
        }
        return Yii::$app->request->get();
    }

    /**
     * 获取请求post参数
     * @return array
     */
    public function requestPostParams():array
    {
        $actionID = Yii::$app->requestedAction->id;
        if (!empty($this->postParamCallback[$actionID])) {
            return call_user_func_array($this->postParamCallback[$actionID], [$actionID]);
        }
        if (!empty($this->postParamCallback['__default'])) {
            //通用参数处理回调
            return call_user_func_array($this->postParamCallback['__default'], [$actionID]);
        }
        return Yii::$app->request->post();
    }

    /**
     * 获取请求post参数
     * @return array
     */
    public function getDataCode():int
    {
        $actionID = Yii::$app->requestedAction->id;
        if (!empty($this->getDataCode[$actionID])) {
            return call_user_func_array($this->getDataCode[$actionID], [$actionID]);
        }
        if (!empty($this->getDataCode['__default'])) {
            //通用参数处理回调
            return call_user_func_array($this->getDataCode['__default'], [$actionID]);
        }
        return -1;
    }

    /**
     * 日志额外数据记录
     * @param array $data
     */
    public static function setExtraData(array $data)
    {
        Yii::$app->params['requestLoggerExtraData'] = Json::encode($data);
    }

    /**
     * 是否记录日志（优先级最高）
     * @param bool $isAddLog
     */
    public static function setRequestActionIsAddLog(bool $isAddLog)
    {
        Yii::$app->params['requestActionIsAddLog'] = $isAddLog;
    }
}
