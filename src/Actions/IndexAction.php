<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Rest\Actions;

use yii\base\Request;
use yii\helpers\Yii;
use yii\data\DataFilter;
use Yiisoft\ActiveRecord\Data\ActiveDataProvider;
use Yiisoft\Db\ConnectionInterface;
use Yiisoft\Yii\Rest\Action;

/**
 * IndexAction implements the API endpoint for listing multiple models.
 *
 * For more details and usage information on IndexAction, see the [guide article on rest controllers](guide:rest-controllers).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 1.0
 */
class IndexAction extends Action
{
    /**
     * @var callable a PHP callable that will be called to prepare a data provider that
     * should return a collection of the models. If not set, [[prepareDataProvider()]] will be used instead.
     * The signature of the callable should be:
     *
     * ```php
     * function (IndexAction $action) {
     *     // $action is the action object currently running
     * }
     * ```
     *
     * The callable should return an instance of [[ActiveDataProvider]].
     *
     * If [[dataFilter]] is set the result of [[DataFilter::build()]] will be passed to the callable as a second parameter.
     * In this case the signature of the callable should be the following:
     *
     * ```php
     * function (IndexAction $action, mixed $filter) {
     *     // $action is the action object currently running
     *     // $filter the built filter condition
     * }
     * ```
     */
    public $prepareDataProvider;
    /**
     * @var DataFilter|null data filter to be used for the search filter composition.
     * You must setup this field explicitly in order to enable filter processing.
     * For example:
     *
     * ```php
     * [
     *     '__class' => \yii\data\ActiveDataFilter::class,
     *     'searchModel' => function () {
     *         return (new \yii\base\DynamicModel(['id' => null, 'name' => null, 'price' => null]))
     *             ->addRule('id', 'integer')
     *             ->addRule('name', 'trim')
     *             ->addRule('name', 'string')
     *             ->addRule('price', 'number');
     *     },
     * ]
     * ```
     *
     * @see DataFilter
     */
    public $dataFilter;
    /**
     * @var \yii\web\Request
     */
    protected $request;
    /**
     * @var \Yiisoft\Db\ConnectionInterface
     */
    protected $db;

    public function __construct($id, $controller, Request $request, ConnectionInterface $db)
    {
        parent::__construct($id, $controller);
        $this->request = $request;
        $this->db = $db;
    }

    /**
     * @return ActiveDataProvider
     */
    public function run()
    {
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id);
        }

        return $this->prepareDataProvider();
    }

    /**
     * Prepares the data provider that should return the requested collection of the models.
     * @return ActiveDataProvider
     */
    protected function prepareDataProvider()
    {
        $requestParams = $this->request->getParsedBody();
        if (empty($requestParams)) {
            $requestParams = $this->request->getQueryParams();
        }

        $filter = null;
        if ($this->dataFilter !== null) {
            $this->dataFilter = Yii::createObject($this->dataFilter);
            if ($this->dataFilter->load($requestParams)) {
                $filter = $this->dataFilter->build();
                if ($filter === false) {
                    return $this->dataFilter;
                }
            }
        }

        if ($this->prepareDataProvider !== null) {
            return call_user_func($this->prepareDataProvider, $this, $filter);
        }

        /* @var $modelClass \Yiisoft\ActiveRecord\BaseActiveRecord */
        $modelClass = $this->modelClass;

        $query = $modelClass::find();
        if (!empty($filter)) {
            $query->andWhere($filter);
        }


        $dataProvider = new ActiveDataProvider($this->db, $query);
        $dataProvider->setPagination(['params' => $requestParams]);
        $dataProvider->setSort(['params' => $requestParams]);

        return $dataProvider;
    }
}
