[toc]

### 一些在yii2.0框架中使用的日志组件

```
composer require fanyawei/yii2-logger
```

#### 1，debug调试日志
在程序运行过程中，可能需要获取程序运行到某个位置时的一些数据，但是非本地环境是不允许去打断点调试的，这时可以使用本组件进行调试日志的记录

- 1.1,在yii2框架中引入组件
  可以借助注释来让编辑器对组件使用友好一点
```php
//config.php
[
    //......
    'components' => [
        //....
        'debugLogger' => 'Fanyawei\Yii2Logger\DebugLogger',
//        'debugLogger2' => [
//            'logTargetName' => '自定义名称',
//            'logTable' => '自定义数据表',
//            'category' => '自定义category',
//        ],
    ],
];
//可以借助注释来让编辑器对组件使用友好一点

```

- 1.2,在需要进行调试的地方调用组件方法，则在程序结束时会使用yii2框架的logger组件将记录的调试数据保存到数据表中供后续查看分析

```php
Yii::$app->debugLogger->trace(111);
Yii::$app->debugLogger->info(222);
Yii::$app->debugLogger->warning(333);
Yii::$app->debugLogger->error(444);
```

#### 2,请求错误日志记录

- main.php
```php
[
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \Fanyawei\Yii2Logger\ErrorLogger::class,
                    'logTable' => 'yii_log_error_logger',
                    'checkIsSaveLog' => function () {
                        //是否进行日志记录
                        $pathInfo = explode('.', Yii::$app->request->pathInfo)[0];
                        $notErrorLogActions = ArrayHelper::getValue(Yii::$app->params, 'notErrorLogActions', []);
                        if (in_array($pathInfo, $notErrorLogActions)) {
                            return false;
                        }
                        $responseStatus = Yii::$app->response->statusCode;
                        $notErrorLogCodes = ArrayHelper::getValue(Yii::$app->params, 'notErrorLogCodes', []);
                        if (in_array($responseStatus, $notErrorLogCodes)) {
                            return false;
                        }
                        return true;
                    }
                ],
            ],
        ],
];
```
- params.php
```php
[
    'notErrorLogCodes' => [200],//不进行错误日志记录的responseCode数组
    'notErrorLogActions' => [

    ],
];
 ```      
        

- 添加控制器

```php
use Fanyawei\Yii2Logger\ErrorLogger;
use Fanyawei\Yii2Logger\ErrorLogger\ActionLoggerIndex;
use Fanyawei\Yii2Logger\ErrorLogger\ActionLoggerView;
use yii\web\Controller;
use Yii;

class ErrorLoggerController extends Controller
{

    public array $summary = ['tag' => ''];

    public function beforeAction($action): bool
    {
        $moduleID = $this->module->id;
        $this->module = ErrorLogger::getDebugModule();
        $this->module->id = $moduleID;
        Yii::$app->setModule($this->module->id, $this->module);
        $this->layout = '@yii/debug/views/layouts/main.php';
        return parent::beforeAction($action);
    }

    public function actions():array
    {
        return array_merge(parent::actions(), [
            'index' => ActionLoggerIndex::class,
            'view' => ActionLoggerView::class,
            'db-explain' => ActionLoggerView::class,
        ]);
    }
}
```


#### 3,关键操作的请求日志记录

- Controller.php控制器中

```php
class xxxController extends Controller
{
    /**
     * @return array
     */
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'logRequest' => [
                'class' => RequestLoggerBehavior::class,
                'actions' => [
                    //默认的请求日志记录逻辑，列表不记录，删除记录，添加修改post请求时记录
                    'index' => false,
                    'create' => Yii::$app->request->isPost,
                    'update' => function (ActionEvent $event) {
                        return Yii::$app->request->isPost;
                    },
                    'delete' => true,
                ],
                'getParamCallback' => [
                    '__default' => function (string $actionID) {
                        $params = Yii::$app->request->getQueryParams();
                        //过滤无效参数
                        unset($params[Yii::$app->request->csrfParam]);
                        unset($params['breadcrumbLinks']);
                        return $params;
                    },
                ],
                'postParamCallback' => [
                    '__default' => function (string $actionID) {
                        $params = Yii::$app->request->post();
                        //过滤无效参数
                        unset($params[Yii::$app->request->csrfParam]);
                        unset($params['times']);
                        return $params;
                    },
                ],
                'getDataCode' => [
                    '__default' => function (string $actionID) {
                        $dataCode = -1;
                        if (isset(Yii::$app->response->data['code'])) {
                            $dataCode = Yii::$app->response->data['code'];
                        } elseif (isset(Yii::$app->response->data['status'])) {
                            $dataCode = Yii::$app->response->data['status'];
                        }
                        return intval($dataCode);
                    },
                ],
            ]
        ]);
    }
}
```
- 在日志中记录额外一些自定义数据
```php
$extraData = ['自定义数据......'];
RequestLoggerBehavior::setExtraData($extraData);
```

- 直接在程序中指定要不要进行日志记录
```php
RequestLoggerBehavior::setRequestActionIsAddLog(true);
RequestLoggerBehavior::setRequestActionIsAddLog(false);
```


