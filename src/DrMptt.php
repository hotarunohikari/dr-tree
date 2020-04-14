<?php

namespace dr\tree;

use dr\tree\contract\DrTree;
use think\Db;
use think\Exception;

/**
 * 预排序遍历树
 * Class DrMptt
 * @package dr\tree
 * hotarunohikari
 */
class DrMptt implements DrTree
{

    /**
     * @var 表名
     */
    private $tableName;

    /**
     * @var 全表名
     */
    private $fullTableName;

    /**
     * @var string 左键
     */
    private $leftKey = "lft";

    /**
     * @var string 右键
     */
    private $rightKey = "rht";

    /**
     * @var string 母亲字段
     */
    private $parentKey = "pid";

    /**
     * @var string 节点深度
     */
    private $floorKey = "flr";

    /**
     * @var string 主键
     */
    private $primaryKey = "id";

    /**
     * @var string 成员ID
     */
    private $memberKey = 'mid';

    /**
     * @var array 节点的缓存
     */
    private static $itemCache = [];

    /**
     * @var array 重建数据
     */
    private $metaData = null;

    /**
     * NestedSets constructor.
     * @param $dbName mixed 数据表名或者模型对象,不含前缀
     * @param null $leftKey
     * @param null $rightKey
     * @param null $parentKey
     * @param null $floorKey
     * @param null $primaryKey
     * @param null $MemberKey
     * @throws Exception
     */
    public function __construct($dbName, $leftKey = null, $rightKey = null, $parentKey = null, $floorKey = null, $primaryKey = null, $MemberKey = null) {
        //如果是表名则处理配置
        if (is_string($dbName)) {
            $this->tableName = $dbName;
        }

        //允许传入模型对象
        if (is_object($dbName)) {
            if (method_exists($dbName, 'getTable')) {
                throw new Exception('不能传入该对象');
            }
            $this->tableName = $dbName->getTable();
        }
        //构造方法中传入的配置会覆盖其他方式的配置
        isset($leftKey) && $this->leftKey = $leftKey;
        isset($rightKey) && $this->rightKey = $rightKey;
        isset($parentKey) && $this->parentKey = $parentKey;
        isset($primaryKey) && $this->primaryKey = $primaryKey;
        isset($floorKey) && $this->floorKey = $floorKey;
        isset($memberKey) && $this->memberKey = $memberKey;
        $this->fullTableName = config('database.prefix') . $this->tableName;
    }


    /**
     * @inheritDoc
     */
    function setTable($tableName) {
        $this->tableName = $tableName;
    }

    /**
     * @inheritDoc
     */
    function getAll($where = [], $join = null, $field = true) {
        return $this
            ->buildQuery($where, $join, $field)
            ->order("$this->memberKey")
            ->select();
    }

    /**
     * @inheritDoc
     */
    function getTeam($topId = 1, $withSelf = true, $deep = -1, $where = [], $join = null, $field = true) {
        $item = $this->getItem($topId);
        if (!$item) {
            throw new Exception('没有该节点');
        }
        if ($withSelf) {
            $lt = '<=';
            $gt = '>=';
        } else {
            $lt = '<';
            $gt = '>';
        }
        $current_lev = $item[$this->floorKey];
        $floor       = $deep > 0 ? [$this->floorKey => ['<=', $current_lev + $deep]] : [];
        return $this
            ->buildQuery($where, $join, $field)
            ->where($floor)
            ->where($this->leftKey, $gt, $item[$this->leftKey])
            ->where($this->rightKey, $lt, $item[$this->rightKey])
            ->order("$this->memberKey")
            ->select();
    }

    /**
     * @inheritDoc
     */
    function getTeamIds($topId, $withSelf = true, $deep = -1, $where = [], $join = null) {
        $item = $this->getItem($topId);
        if (!$item) {
            throw new Exception('没有该节点');
        }
        if ($withSelf) {
            $lt = '<=';
            $gt = '>=';
        } else {
            $lt = '<';
            $gt = '>';
        }
        $current_lev = $item[$this->floorKey];
        $floor       = $deep > 0 ? [$this->floorKey => ['<=', $current_lev + $deep]] : [];
        return $this->buildQuery($where, $join, "{$this->memberKey}")
            ->where($floor)
            ->where($this->leftKey, $gt, $item[$this->leftKey])
            ->where($this->rightKey, $lt, $item[$this->rightKey])
            ->order("{$this->memberKey}")
            ->column("{$this->memberKey}");
    }

    /**
     * @inheritDoc
     */
    function countTeam($topId, $withSelf = true, $deep = -1, $where = [], $join = null) {
        $item = $this->getItem($topId);
        if (!$item) {
            throw new Exception('没有该节点');
        }
        if ($withSelf) {
            $lt = '<=';
            $gt = '>=';
        } else {
            $lt = '<';
            $gt = '>';
        }
        $current_lev = $item[$this->floorKey];
        $floor       = $deep > 0 ? [$this->floorKey => ['<=', $current_lev + $deep]] : [];
        return $this->buildQuery($where, $join, "{$this->memberKey}")
            ->where($floor)
            ->where($this->leftKey, $gt, $item[$this->leftKey])
            ->where($this->rightKey, $lt, $item[$this->rightKey])
            ->order("{$this->memberKey}")
            ->count();
    }

    /**
     * 只统计分支人数
     * @param int $topId 顶点成员ID
     * @param bool $withSelf 是否包含自己
     */
    function countTeamOnly($topId, $withSelf = true) {
        $item = $this->getItem($topId);
        if (!$item) {
            throw new Exception('没有该节点');
        }
        $countTeamWithSelf = ($item[$this->rightKey] + 1 - $item[$this->leftKey]) / 2;
        return $withSelf ? $countTeamWithSelf : $countTeamWithSelf - 1;
    }

    /**
     * @inheritDoc
     */
    function getChildren($topId, $where = [], $join = null, $field = true) {
        return $this->buildQuery($where, $join, $field)
            ->where($this->parentKey, '=', $topId)
            ->order("{$this->memberKey}")
            ->select();
    }

    /**
     * @inheritDoc
     */
    function getChildrenIds($topId, $where = [], $join = null) {
        return $this->buildQuery($where, $join, "{$this->memberKey}")
            ->where($this->parentKey, '=', $topId)
            ->order("{$this->memberKey}")
            ->column("{$this->memberKey}");
    }

    /**
     * @inheritDoc
     */
    function countChildren($topId, $where = [], $join = null) {
        return $this->buildQuery($where, $join, "{$this->memberKey}")
            ->where($this->parentKey, '=', $topId)
            ->order("{$this->memberKey}")
            ->count("{$this->memberKey}");
    }

    /**
     * @inheritDoc
     */
    function getParent($botId, $where = [], $join = null, $field = true) {
        $item = $this->getItem($botId);
        if (!$item) {
            throw new Exception('没有该节点');
        }
        return $this->buildQuery($where, $join, $field)
            ->where($this->primaryKey, '=', $item[$this->parentKey])
            ->order("{$this->memberKey}")
            ->find();
    }

    /**
     * @inheritDoc
     */
    function getAncestors($botId, $deep = -1, $where = [], $join = null, $field = true) {
        $item = $this->getItem($botId);
        if (!$item) {
            throw new Exception('没有该节点');
        }
        $current_lev = $item[$this->floorKey];
        $floor       = $deep > 0 ? [$this->floorKey => ['between', [$current_lev - $deep, $current_lev]]] : [];
        return $this->buildQuery($where, $join, $field)
            ->where($floor)
            ->where($this->leftKey, '<', $item[$this->leftKey])
            ->where($this->rightKey, '>', $item[$this->rightKey])
            ->order("{$this->memberKey}")
            ->select();
    }

    /**
     * @inheritDoc
     */
    function getAncestorIds($botId, $deep = -1, $where = [], $join = null) {
        $item = $this->getItem($botId);
        if (!$item) {
            throw new Exception('没有该节点');
        }
        $current_lev = $item[$this->floorKey];
        $floor       = $deep > 0 ? [$this->floorKey => ['between', [$current_lev - $deep, $current_lev]]] : [];
        return $this->buildQuery($where, $join, "{$this->memberKey}")
            ->where($floor)
            ->where($this->leftKey, '<', $item[$this->leftKey])
            ->where($this->rightKey, '>', $item[$this->rightKey])
            ->order("{$this->memberKey}")
            ->column("{$this->memberKey}");
    }

    /**
     * @inheritDoc
     */
    function inOneLine($id1, $id2) {
        $topId    = $id1 < $id2 ? $id1 : $id2;
        $bottomId = $id1 > $id2 ? $id1 : $id2;
        $top      = $this->getItem($topId);
        $bottom   = $this->getItem($bottomId);
        if ($top && $bottom) {
            $rangeIds = $this->getRangeIds($id1, $id2);
            $ids      = array_values($rangeIds);
            return in_array($topId, $ids) && in_array($bottomId, $ids);
        } else {
            throw new Exception('节点号错误');
        }
    }

    /**
     * @inheritDoc
     */
    function getRange($id1, $id2, $where = [], $join = null, $field = true) {
        $topId    = $id1 < $id2 ? $id1 : $id2;
        $bottomId = $id1 > $id2 ? $id1 : $id2;
        $top      = $this->getItem($topId);
        $bottom   = $this->getItem($bottomId);
        if ($top && $bottom) {
            return $this->buildQuery($where, $join, $field)
                ->where($this->leftKey, 'between', [$top[$this->leftKey], $bottom[$this->leftKey]])
                ->where($this->rightKey, 'between', [$bottom[$this->rightKey], $top[$this->rightKey]])
                ->order("{$this->memberKey}")
                ->select();
        } else {
            throw new Exception('节点号错误');
        }
    }

    /**
     * @inheritDoc
     */
    function getRangeIds($id1, $id2, $where = [], $join = null, $field = true) {
        $topId    = $id1 < $id2 ? $id1 : $id2;
        $bottomId = $id1 > $id2 ? $id1 : $id2;
        $top      = $this->getItem($topId);
        $bottom   = $this->getItem($bottomId);
        if ($top && $bottom) {
            return $this->buildQuery($where, $join, $field)
                ->where($this->leftKey, 'between', [$top[$this->leftKey], $bottom[$this->leftKey]])
                ->where($this->rightKey, 'between', [$bottom[$this->rightKey], $top[$this->rightKey]])
                ->order("{$this->memberKey}")
                ->column("{$this->memberKey}");
        } else {
            throw new Exception('节点号错误');
        }
    }


    /**
     * 根据ID获取某个节点
     * @param $mid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getItem($mid) {
        if (!isset(self::$itemCache[$mid])) {
            self::$itemCache[$mid] =
                db($this->tableName)
                    ->field([$this->leftKey, $this->rightKey, $this->parentKey, $this->floorKey])
                    ->where($this->memberKey, '=', $mid)
                    ->find();
        }
        return self::$itemCache[$mid];
    }

    /**
     * @inheritDoc
     */
    public function buildTree($items, $midField = 'mid', $pidField = 'pid', $sonTag = 'son') {
        $tree   = [];
        $tmpMap = [];
        foreach ($items as $item) {
            $tmpMap[$item[$midField]] = $item;
        }
        foreach ($items as $item) {
            if (isset($tmpMap[$item[$pidField]])) {
                $tmpMap[$item[$pidField]][$sonTag][] = &$tmpMap[$item[$midField]];
            } else {
                $tree[] = &$tmpMap[$item[$midField]];
            }
        }
        unset($tmpMap);
        return $tree;
    }

    /************************** 节点操作 **************************/

    /**
     * @inheritDoc
     */
    public function add($parentId, array $data = [], $atBottom = true) {
        if (!isset($data[$this->memberKey])) {
            throw new Exception('传入数据中必须指定' . $this->memberKey . '的值');
        }
        $parent = $this->getItem($parentId);
        if (!$parent) {
            $parentId = 0;
            $floor    = 1;
            if ($atBottom) {
                $key = 1;
            } else {
                $key = db($this->tableName)
                        ->max("{$this->rightKey}") + 1;
            }
        } else {
            $key   = $atBottom ? $parent[$this->rightKey] : $parent[$this->leftKey] + 1;
            $floor = $parent[$this->floorKey] + 1;
        }
        Db::startTrans();
        //更新其他节点
        $sql = "UPDATE {$this->fullTableName} SET {$this->rightKey} = {$this->rightKey}+2,{$this->leftKey} = IF({$this->leftKey}>={$key},{$this->leftKey}+2,{$this->leftKey}) WHERE {$this->rightKey}>={$key}";
        try {
            db($this->tableName)
                ->query($sql);
            $newNode[$this->parentKey] = $parentId;
            $newNode[$this->leftKey]   = $key;
            $newNode[$this->rightKey]  = $key + 1;
            $newNode[$this->floorKey]  = $floor;
            $tmpData                   = array_merge($newNode, $data);
            db($this->tableName)->insert($tmpData);
            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function moveUnder($mid, $parentId, $atBottom = true) {
        $item = $this->getItem($mid);
        if (!$item) {
            throw new Exception('没有该节点');
        }
        $parent = $this->getItem($parentId);
        if (!$parent) {
            $floor = 1;
            // 在顶部插入
            if (!$atBottom) {
                $nearKey = 0;
            } else {
                // 选择最大的右键作为开始
                $nearKey = db($this->tableName)
                    ->max("{$this->rightKey}");
            }
        } else {
            $floor = $parent[$this->floorKey] + 1;
            if (!$atBottom) {
                $nearKey = $parent[$this->leftKey];
            } else {
                //若在底部插入则起始键为母节点的右键减1
                $nearKey = $parent[$this->rightKey] - 1;
            }
        }

        return $this->move($mid, $parentId, $nearKey, $floor);
    }

    /**
     * @inheritDoc
     */
    public function remove($mid, $withSelf = true) {
        $item = $this->getItem($mid);
        if (!$item) {
            throw new Exception('没有该节点');
        }
        $keyWidth = $item[$this->rightKey] - $item[$this->leftKey] + 1;
        if ($withSelf) {
            $lt = '<=';
            $gt = '>=';
        } else {
            $lt = '<';
            $gt = '>';
        }
        try {
            $del = db($this->tableName)
                ->where($this->leftKey, $gt, $item[$this->leftKey])
                ->where($this->rightKey, $lt, $item[$this->rightKey])
                ->delete();
            $sql = "UPDATE {$this->fullTableName} SET {$this->leftKey} = IF({$this->leftKey}>{$item[$this->leftKey]}, {$this->leftKey}-{$keyWidth}, {$this->leftKey}), {$this->rightKey} = {$this->rightKey}-{$keyWidth} WHERE {$this->rightKey}>{$item[$this->rightKey]}";
            //再移动节点
            db($this->tableName)->query($sql);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }


    /**
     * @param $id
     * @param $nearId
     * @param string $position
     * @return bool
     * @throws Exception
     * 把主键为id的整条线移动到主键为nearId的节点的前或者后,两者隶属同一个母节点，两者互为兄弟节点
     */
    public function moveNear($id, $nearId, $position = 'after') {
        $item = $this->getItem($id);
        if (!$item) {
            throw new Exception("要移动的节点不存在");
        }

        $near = $this->getItem($nearId);
        if (!$near) {
            throw new Exception("附近的节点不存在");
        }

        $floor = $near[$this->floorKey];

        //根据要移动的位置选择键
        if ($position == 'before') {
            $nearKey = $near[$this->leftKey] - 1;
        } else {
            $nearKey = $near[$this->rightKey];
        }

        //移动节点
        return $this->move($id, $near[$this->parentKey], $nearKey, $floor);

    }

    /**
     * @param $id
     * @param $parentId
     * @param $nearKey
     * @param $floor
     * @return bool
     * 移动节点
     */
    private function move($id, $parentId, $nearKey, $floor) {
        $item = $this->getItem($id);

        //检查能否移动该节点若为移动到节点本身下则返回错误
        if ($nearKey >= $item[$this->leftKey] && $nearKey <= $item[$this->rightKey]) {
            return false;
        }

        $keyWidth   = $item[$this->rightKey] - $item[$this->leftKey] + 1;
        $floorWidth = $floor - $item[$this->floorKey];
        if ($item[$this->rightKey] < $nearKey) {

            $treeEdit = $nearKey - $item[$this->leftKey] + 1 - $keyWidth;
            $sql      = "UPDATE {$this->fullTableName} 
                    SET 
                    {$this->leftKey} = IF(
                        {$this->rightKey} <= {$item[$this->rightKey]},
                        {$this->leftKey} + {$treeEdit},
                        IF(
                            {$this->leftKey} > {$item[$this->rightKey]},
                            {$this->leftKey} - {$keyWidth},
                            {$this->leftKey}
                        )
                    ),
                    {$this->floorKey} = IF(
                        {$this->rightKey} <= {$item[$this->rightKey]},
                        {$this->floorKey} + {$floorWidth},
                        {$this->floorKey}
                    ),
                    {$this->rightKey} = IF(
                        {$this->rightKey} <= {$item[$this->rightKey]},
                        {$this->rightKey} + {$treeEdit},
                        IF(
                            {$this->rightKey} <= {$nearKey},
                            {$this->rightKey} - {$keyWidth},
                            {$this->rightKey}
                        )
                    ),
                    {$this->parentKey} = IF(
                        {$this->primaryKey} = {$id},
                        {$parentId},
                        {$this->parentKey}
                    )
                    WHERE 
                    {$this->rightKey} > {$item[$this->leftKey]}
                    AND 
                    {$this->leftKey} <= {$nearKey}";
            db($this->tableName)->query($sql);
        } else {
            $treeEdit = $nearKey - $item[$this->leftKey] + 1;

            $sql = "UPDATE {$this->fullTableName}
                    SET 
                    {$this->rightKey} = IF(
						{$this->leftKey} >= {$item[$this->leftKey]},
						{$this->rightKey} + {$treeEdit},
						IF(
							{$this->rightKey} < {$item[$this->leftKey]},
							{$this->rightKey} + {$keyWidth},
							{$this->rightKey}
						)
					),
					{$this->floorKey} = IF(
						{$this->leftKey} >= {$item[$this->leftKey]},
						{$this->floorKey} + {$floorWidth},
						{$this->floorKey}
					),
					{$this->leftKey} = IF(
						{$this->leftKey} >= {$item[$this->leftKey]},
						{$this->leftKey} + {$treeEdit},
						IF(
							{$this->leftKey} > {$nearKey},
							{$this->leftKey} + {$keyWidth},
							{$this->leftKey}
						)
					),
					{$this->parentKey} = IF(
						{$this->memberKey} = {$id},
						{$parentId},
						{$this->parentKey}
					)
					WHERE
					{$this->rightKey} > {$nearKey}
					AND
					{$this->leftKey} < {$item[$this->rightKey]}";
            db($this->tableName)->query($sql);
        }

        return true;

    }

    /**
     * 展示全部
     * @return mixed
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    public function showAll() {
        $sql = "SELECT 
                    CONCAT( REPEAT( 'x', (COUNT(parent.{$this->memberKey}) - 1) ), node.{$this->memberKey}) AS mid
                FROM 
                    {$this->fullTableName} AS node,
                    {$this->fullTableName} AS parent
                WHERE 
                    node.{$this->leftKey} BETWEEN parent.{$this->leftKey} AND parent.{$this->rightKey}
                GROUP BY 
                    node.{$this->memberKey}
                ORDER BY 
                    node.{$this->leftKey}";
        return db($this->tableName)->query($sql);
    }

    /**
     * @inheritDoc
     */
    function rebuild($mid, $left) {
        $right = $left + 1;

        $result = db($this->tableName)->query("SELECT {$this->memberKey} FROM {$this->fullTableName} WHERE {$this->parentKey} = $mid");
        foreach ($result as $row) {
            $right = $this->rebuild_tree($row[$this->memberKey], $right);
        }
        db($this->tableName)->query("UPDATE {$this->fullTableName} SET {$this->leftKey}= $left, {$this->rightKey}= $right WHERE {$this->memberKey}= $mid");
        return $right + 1;
    }

    /**
     * @inheritDoc
     */
    function generate($tableName, $midField = 'id', $pidField = 'pid') {
        return db($tableName)
            ->field("$midField,$pidField")
            ->chunk(500, function ($items) use ($midField, $pidField) {
                foreach ($items as $item) {
                    $this->add($item[$pidField], [$this->memberKey => $item[$midField]]);
                }
            });
    }

    // 构建查询对象
    private function buildQuery($where = [], $join = null, $field = true) {
        $query = db($this->tableName);
        if ($join) {
            list($joinTable, $condition, $type) = $join;
            $query = $query->join($joinTable, $condition, $type);
        }
        return $query->field($field)->where($where);
    }

}