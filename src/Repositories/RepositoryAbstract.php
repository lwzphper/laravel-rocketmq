<?php
/**
 * @Author: laoweizhen <1149243551@qq.com>,
 * @Date: 2021/10/27 18:31,
 * @LastEditTime: 2021/10/27 18:31
 */

namespace Lwz\LaravelExtend\MQ\Repositories;

class RepositoryAbstract
{
    /**
     * 通过ID查找一条记录
     * @param int $id
     * @param array $fields
     * @return mixed
     */
    public static function getOneById(int $id, array $fields = ['*'])
    {
        return app(static::$model)->select($fields)->find($id);
    }

    /**
     * 通过where查找一条记录
     * @param $where
     * @param array $fields
     * @return mixed
     */
    public static function getOneByWhere($where, array $fields = ['*'])
    {
        return app(static::$model)->select($fields)->where($where)->first();
    }

    /**
     * 新增一条数据
     * @param array $data
     * @return mixed
     */
    public static function add(array $data)
    {
        return app(static::$model)->create($data);
    }

    /**
     * 批量插入
     * @param array $data
     * @return mixed
     * @author lwz
     */
    public static function insert(array $data)
    {
        return app(static::$model)->insert($data);
    }

    /**
     * 根据ID更新一条记录
     * @param array $update
     * @param int $id
     * @return mixed
     */
    public static function updateById(int $id, array $update)
    {
        return app(static::$model)->whereId($id)->update($update);
    }

    /**
     * 根据where条件更新一条或多条记录
     * @param $where
     * @param array $update
     * @return mixed
     */
    public static function updateByWhere($where, array $update)
    {
        return app(static::$model)->where($where)->update($update);
    }

    /**
     * 根据ID删除一条记录
     * @param $id
     * @return mixed
     */
    public static function deleteById($id)
    {
        $info = app(static::$model)->find($id);
        $info && $info->delete();
        return $info;
    }

    /**
     * 根据where 删除多条记录
     * @param array $where
     * @return mixed
     */
    public static function deleteByWhere(array $where)
    {
        return app(static::$model)->where($where)->delete();
    }

    /**
     * 根据 where column自增num
     * @param mixed $where 查询条件
     * @param string $column 字段名称
     * @param int $num
     * @return mixed
     */
    public static function increment($where, string $column, int $num = 1)
    {
        return app(static::$model)->where($where)->increment($column, $num);
    }

    /**
     * 更新或添加数据
     * @param array $where 查询条件
     * @param array $data 更新的数据
     * @return mixed
     * @author lwz
     */
    public static function updateOrInsert(array $where, array $data)
    {
        return static::$model::query()
            ->updateOrInsert($where, $data);
    }

    /**
     * 根据 where column自减 num
     * @param $where
     * @param string $column
     * @param int $num
     * @return mixed
     */
    public static function decrement($where, string $column, int $num = 1)
    {
        return app(static::$model)->where($where)->decrement($column, $num);
    }

    /**
     * 通过scope where方式查询数据
     * @param array $where where查询条件
     * @param array|string[] $fields 查询的字段
     * @param string|null $orderField 排序字段
     * @param string|null $orderType 排序类型。ASC、DESC
     * @return
     * @author lwz
     */
    public static function getOneByScopeWhere(array $where, array $fields = ['*'])
    {
        return app(static::$model)->select($fields)
            ->setWhereQuery($where)
            ->first();
    }

    /**
     * 存在则更新，否则新建
     * @param array $attributes 搜索条件
     * @param array $values 附加更新的数据
     * @return mixed
     */
    public static function updateOrCreate(array $attributes, array $values)
    {
        return app(static::$model)->updateOrCreate($attributes, $values);
    }
}