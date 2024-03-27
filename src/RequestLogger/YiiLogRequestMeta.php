<?php

namespace Fanyawei\Yii2Logger\RequestLogger;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "yii_log_request".
 *
 * @property int $id
 * @property string $url 请求路由
 * @property string $method 请求方式GET,POST
 * @property int $code Http响应状态码
 * @property int $data_code 响应数据data中的code值，如果没有则为0
 * @property string $get_params 请求get参数
 * @property string $post_params 请求post参数
 * @property string $extra_data 请求额外拓展记录数据，默认空
 * @property int $param_length 为防止部分请求携带特别长的无效参数，本字段记录get,post,和extra_data三个字段内容的长度和供后续即时分析处理
 * @property int $time 请求耗时（毫秒）
 * @property int $memory 请求内存占用
 * @property int $created_at 请求时间
 * @property int $user_id 请求用户id
 * @property string $user_name 请求用户名
 * @property string $request_ip 请求ip
 */
class YiiLogRequestMeta extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'yii_log_request';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'data_code', 'param_length', 'time', 'memory', 'created_at', 'user_id'], 'integer'],
            [['get_params', 'post_params', 'extra_data'], 'required'],
            [['get_params', 'post_params', 'extra_data'], 'string'],
            [['url', 'method', 'user_name'], 'string', 'max' => 255],
            [['request_ip'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'url' => '请求路由',
            'method' => '请求方式GET,POST',
            'code' => 'Http响应状态码',
            'data_code' => '响应数据data中的code值，如果没有则为0',
            'get_params' => '请求get参数',
            'post_params' => '请求post参数',
            'extra_data' => '请求额外拓展记录数据，默认空',
            'param_length' => '为防止部分请求携带特别长的无效参数，本字段记录get,post,和extra_data三个字段内容的长度和供后续即时分析处理',
            'time' => '请求耗时（毫秒）',
            'memory' => '请求内存占用',
            'created_at' => '请求时间',
            'user_id' => '请求用户id',
            'user_name' => '请求用户名',
            'request_ip' => '请求ip',
        ];
    }
}
