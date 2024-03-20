<?php
/**
 * User: fanyawei
 * Date: 2024/3/20 12:00
 */

namespace Fanyawei\Yii2Logger\ErrorLogger;


use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "yii_log_error_logger".
 *
 * @property int $id
 * @property string $app_id  应用标识id
 * @property string $category
 * @property string $message debug内容
 * @property int $log_time 日志记录时间
 * @property int $user_id  用户ID
 * @property string $username  用户名
 * @property string $ip 请求ip
 */
class YiiLogErrorLoggerMeta extends ActiveRecord
{
    public $criticalCodes = [400, 404, 500];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'yii_log_error_logger';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['message'], 'required'],
            [['message'], 'string'],
            [['log_time', 'user_id'], 'integer'],
            [['app_id'], 'string', 'max' => 50],
            [['category'], 'string', 'max' => 20],
            [['username', 'ip'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'app_id' => ' 应用标识id ',
            'category' => 'Category',
            'message' => 'debug内容',
            'log_time' => '日志记录时间',
            'user_id' => ' 用户ID ',
            'username' => ' 用户名 ',
            'ip' => '请求ip',
        ];
    }

    /**
     * Checks if code is critical.
     *
     * @param int $code
     * @return bool
     */
    public function isCodeCritical($code)
    {
        return in_array($code, $this->criticalCodes, false);
    }
}
