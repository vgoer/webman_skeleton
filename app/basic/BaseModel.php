<?php

namespace app\basic;

use app\library\Singleton\Singleton;
use think\Model;
use think\model\concern\SoftDelete;

class BaseModel extends Model
{
    use SoftDelete;
    use Singleton;

    // 删除时间
    protected $deleteTime = 'delete_time';

    //添加时间
    protected $createTime = 'create_time';

    //更新时间
    protected $updateTime = 'update_time';

    // 隐藏字段
    protected $hidden = ['delete_time'];

    // 只读字段
    protected $readonly = ['created_by', 'create_time'];


    /**
     * 新增前
     */
    public static function onBeforeInsert($model)
    {
        $info = getCurrentInfo();
        $info && $model->setAttr('created_by', $info['id']);
    }




}