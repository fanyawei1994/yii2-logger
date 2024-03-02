<?php

namespace Fanyawei\Yii2Logger\DebugLogger;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "yii_log_debug_logger".
 *
 * @property int $id
 * @property string $app_id 应用标识id
 * @property int|null $level
 * @property string|null $category
 * @property float|null $log_time
 * @property string|null $prefix
 * @property string|null $message
 * @property int $user_id 用户ID
 * @property string $username 用户名
 * @property string $ip 请求ip
 */
class YiiLogDebugLoggerMeta extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'yii_log_debug_logger';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['level', 'user_id'], 'integer'],
            [['log_time'], 'number'],
            [['prefix', 'message'], 'string'],
            [['app_id'], 'string', 'max' => 50],
            [['category', 'username', 'ip'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'app_id' => '应用标识',
            'level' => '日志等级',
            'category' => '日志分类',
            'log_time' => '日志时间',
            'prefix' => 'Prefix',
            'message' => '日志内容',
            'user_id' => ' 用户ID ',
            'username' => ' 用户名 ',
            'ip' => '请求ip',
        ];
    }
}
