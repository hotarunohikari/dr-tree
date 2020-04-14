# dr-tree

   基于TP5+的树形结构工具

   
    /**
     * 设置数据表,不带前缀
     * @param $tableName
     * @return mixed
     */
    function setTable($tableName);

    /**
     * 获取所有记录
     * @param array $where 查询条件
     * @param array|null $join 关联查询(TP语法)
     * @param string|bool $field 要返回的字段
     * @return mixed
     */
    function getAll($where = [], $join = null, $field = true);

    /**
     * 向下获取团队
     * @param int $topId 顶点ID
     * @param bool $withSelf 是否包含自己,默认包含
     * @param int $deep 相对深度,-1为全部
     * @param array $where 查询条件
     * @param array|null $join 关联查询(TP语法)
     * @param string|bool $field 要返回的字段
     * @return mixed
     */
    function getTeam($topId = 1, $withSelf = true, $deep = -1, $where = [], $join = null, $field = true);

    /**
     * 向下获取团队ID集合
     * @param int $topId 顶点ID
     * @param bool $withSelf 是否包含自己,默认包含
     * @param int $deep 相对深度,-1为全部
     * @param array $where 查询条件
     * @param array|null $join 关联查询(TP语法)
     * @return mixed
     */
    function getTeamIds($topId, $withSelf = true, $deep = -1, $where = [], $join = null);

    /**
     * 向下获取团队总人数统计
     * @param $topId
     * @param bool $withSelf 是否包含自己,默认包含
     * @param int $deep 相对深度,-1为全部
     * @param array $where 查询条件
     * @param array|null $join 关联查询(TP语法)
     * @return mixed
     */
    function countTeam($topId, $withSelf = true, $deep = -1, $where = [], $join = null);

    /**
     * 向下获取所有的子代(直推)
     * @param int $topId 顶点ID
     * @param array $where 查询条件
     * @param array|null $join 关联查询(TP语法)
     * @param string|bool $field 要返回的字段
     * @return mixed
     */
    function getChildren($topId, $where = [], $join = null, $field = true);

    /**
     * 向下获取所有的子代(直推)ID集合
     * @param int $topId 顶点ID
     * @param array $where 查询条件
     * @param array|null $join 关联查询(TP语法)
     * @return mixed
     */
    function getChildrenIds($topId, $where = [], $join = null);

    /**
     * 向下获取子代(直推)人数统计;
     * @param int $topId 顶点ID
     * @param array $where 查询条件
     * @param array|null $join 关联查询(TP语法)
     * @return mixed
     */
    function countChildren($topId, $where = [], $join = null);

    /**
     * 向上获取母级节点信息
     * @param int $botId 底部ID
     * @param array $where 查询条件
     * @param array|null $join 关联查询(TP语法)
     * @param string|bool $field 要返回的字段
     * @return mixed
     */
    function getParent($botId, $where = [], $join = null, $field = true);

    /**
     * 向上获取所有的祖先节点信息
     * @param int $botId 底部ID
     * @param int $deep 相对深度,-1表示所有
     * @param array $where 查询条件
     * @param array|null $join 关联查询(TP语法)
     * @param string|bool $field 要返回的字段
     * @return mixed
     */
    function getAncestors($botId, $deep = -1, $where = [], $join = null, $field = true);

    /**
     * 向上获取所有的祖先ID集合
     * @param int $botId 底部ID
     * @param int $deep 相对深度,-1表示所有
     * @param array $where 查询条件
     * @param array|null $join 关联查询(TP语法)
     * @return mixed
     */
    function getAncestorIds($botId, $deep = -1, $where = [], $join = null);

    /**
     * 判断两个节点是否属于同一条线
     * @param $id1
     * @param $id2
     * @return mixed
     */
    function inOneLine($id1, $id2);

    /**
     * 获取给定两个ID之间的所有的节点
     * @param int $mid1 成员ID
     * @param int $mid2 成员ID
     * @param array $where 查询条件
     * @param array|null $join 关联查询(TP语法)
     * @param string|bool $field 要返回的字段
     * @return mixed
     */
    function getRange($mid1, $mid2, $where = [], $join = null, $field = true);

    /**
     * 获取给定两个ID之间的所有的节点ID集合
     * @param int $mid1 成员ID
     * @param int $mid2 成员ID
     * @param array $where 查询条件
     * @param array|null $join 关联查询(TP语法)
     * @param string|bool $field 要返回的字段
     * @return mixed
     */
    function getRangeIds($mid1, $mid2, $where = [], $join = null, $field = true);

    /**
     * 数据转树形结构
     * @param array $arr 数据
     * @param string $midField 成员ID字段
     * @param string $pidField 成员母级ID字段
     * @param string $sonTag 子代标签
     * @return array
     */
    function buildTree($arr, $midField = 'mid', $pidField = 'pid', $sonTag = 'son');

    /**
     * 新增
     * @param int $parentId 母级成员ID
     * @param array $data 新增的数据,必须含有成员ID
     * @param bool $atBottom 默认添加于其所有兄弟成员的尾部
     * @return bool
     */
    function add($parentId, array $data = [], $atBottom = true);

    /**
     * 将一个节点及其全部后代移动到另个一节点下,两者为母子级关系
     * @param $mid
     * @param $parentId
     * @param bool $atBottom 默认将分支在最后加入
     * @return bool
     */
    function moveUnder($mid, $parentId, $atBottom = true);

    /**
     * 删除某个分支,默认删除某个成员及所有后代
     * @param int $mid 成员ID
     * @param bool $withSelf 默认包含自己
     * @return bool
     */
    function remove($mid, $withSelf = true);

    /**
     * 递归重建
     * @param int $mid 成员ID
     * @param int $left 成员左键
     * @return int
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    function rebuild($mid, $left);

    /**
     * 根据指定表构建预排序遍历树
     * @param string $tableName 表名
     * @param string $midField 成员ID字段
     * @param string $pidField 成员母级ID字段
     * @return mixed
     */
    function generate($tableName, $midField = 'id', $pidField = 'pid');