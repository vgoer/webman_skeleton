<?php

namespace app\basic;

use app\enum\CommonEnum;
use app\library\Singleton\Singleton;
use app\model\AdminRole;
use support\exception\BusinessException;
use think\facade\Db;
use utils\Date;

///**
// * 逻辑层基础类
// * @package app\service
// * @method static where($data) think-orm的where方法
// * @method static find($id) think-orm的find方法
// * @method static findOrEmpty($id) think-orm的findOrEmpty方法
// * @method static hidden($data) think-orm的hidden方法
// * @method static order($data) think-orm的order方法
// * @method static save($data) think-orm的save方法
// * @method static create($data) think-orm的create方法
// * @method static saveAll($data) think-orm的saveAll方法
// * @method static update($data, $where, $allow = []) think-orm的update方法
// * @method static destroy($id) think-orm的destroy方法
// * @method static select() think-orm的select方法
// * @method static count($data) think-orm的count方法
// * @method static max($data) think-orm的max方法
// * @method static min($data) think-orm的min方法
// * @method static sum($data) think-orm的sum方法
// * @method static avg($data) think-orm的avg方法
// */
class BaseService
{
    use Singleton;

    /**
     * @var bool 数据边界启用状态
     */
    protected $scope = false;

    /**
     * 排序字段
     * @var string
     */
    protected $orderField = '';

    /**
     * 排序方式
     * @var string
     */
    protected $orderType = 'DESC';



    /**
     * 设置数据边界
     * @param $scope
     * @return void
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * 设置排序字段
     * @param $field
     * @return void
     */
    public function setOrderField($field)
    {
        $this->orderField = $field;
    }

    /**
     * 设置排序方式
     * @param $type
     * @return void
     */
    public function setOrderType($type)
    {
        $this->orderType = $type;
    }

    /**
     * 分页查询数据
     * @return mixed
     */
    public function getList($query)
    {
        $saiType = request()->input('saiType', 'list');
        $page = request()->input('page', 1);
        $limit = request()->input('limit', 10);
        $orderBy = request()->input('orderBy', '');
        $orderType = request()->input('orderType', $this->orderType);

        if ($page < 1 || $limit < 1) {
            throw new BusinessException('非法参数', CommonEnum::RETURN_CODE_FAIL);
        }
        if ($limit > 30){
            throw new BusinessException('前端数据不能超过30条', CommonEnum::RETURN_CODE_FAIL);
        }

        if(empty($orderBy)) {
            $orderBy = $this->orderField !== '' ? $this->orderField : 'id';
        }
        $query->order($orderBy, $orderType);
        if ($saiType === 'all') {
            $data['data'] = $query->select()->toArray();
            return $data;
        }
        return $query->paginate($limit, false, ['page' => $page])->toArray();
    }


    /**
     * 获取全部数据
     * @param $query
     * @return mixed
     */
    public function getAll($query)
    {
        $orderBy = request()->input('orderBy', '');
        $orderType = request()->input('orderType', $this->orderType);

        if(empty($orderBy)) {
            $orderBy = $this->orderField !== '' ? $this->orderField : $this->model->getPk();
        }
        $query->order($orderBy, $orderType);
        return $query->select()->toArray();
    }

    public function editStatus($query)
    {
        $id = request()->post('id', '');
        $status = request()->post('status', '');
        if (!in_array($status, [CommonEnum::STATUS_Y, CommonEnum::STATUS_N])){
            throw new BusinessException('状态值错误', CommonEnum::RETURN_CODE_FAIL);
        }
        if (empty($id)){
            throw new BusinessException('参数错误', CommonEnum::RETURN_CODE_FAIL);
        }
        $data = [
            'status' => $status,
            'update_time' => Date::now()
        ];
        return $query->getQuery()
            ->where('id', $id)
            ->update($data);
    }

}