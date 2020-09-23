<?php
/**
 * 角色关联标签服务
 * @author xiao
 * @date 2020年6月11日10:32:35
 */
namespace webapi\services;

use webapi\models\SysLog;
use webapi\models\SysMediaTag;
use webapi\models\SysPageAuth;
use webapi\models\SysRole;
use webapi\models\TagRegion;
use webapi\models\TagTags;
use Yii;
use yii\db\Exception;

class PageAuthService
{
    /**
     * 获取角色关联的所有标签数据
     * @param string $return_type 值为tree时返回树结构，normal时返回正常结构
     * @param string $type 2媒体标签 3地区标签 4标签
     * @return array
     */
    public function getTagList($return_type = 'tree', $type = 0)
    {
        $res = [
            'media_tag' => [], //媒体标签
            'region_tag' => [], //地区标签
            'tag' => [], //标签
        ];

        $media_tag_ids = [];
        $region_tag_ids = [];
        $tag_ids = [];

        //查询当前菜单id
        $menu_id = Yii::$app->service->MenuService->getMenuId();
        //获取角色数据
        $role_info = Yii::$app->service->RoleService->getRoleInfo();
        //判断是否管理员
        if ($role_info['is_super']) {
            //获取企业拥有的标签
            if (CompanyService::$company_info['media_tag_ids']) {
                $media_tag_ids = explode(',', CompanyService::$company_info['media_tag_ids']);
            }else {
                $media_tag_ids = 'ALL';
            }
            //地区
            $region_tag_ids = 'ALL';
            //标签
            $tag_ids = 'ALL';
        }else {
            //查询关联表获取关联id
            $where = ['rid' => UserService::$rid, 'menu_id' => $menu_id];
            if ($type) {
                $where['type'] = $type;
            }
            $related_data = SysPageAuth::find()->select('type,related_id')->where($where)->asArray()->all();
            foreach ($related_data as $v) {
                switch ($v['type']) {
                    case SysPageAuth::TYPE_MEDIA:
                        $media_tag_ids[] = $v['related_id'];
                        break;
                    case SysPageAuth::TYPE_REGION:
                        $region_tag_ids[] = $v['related_id'];
                        break;
                    case SysPageAuth::TYPE_TAG:
                        $tag_ids[] = $v['related_id'];
                        break;
                }
            }
        }
        //查询数据
        if ($media_tag_ids && (in_array($type, [0, SysPageAuth::TYPE_MEDIA]))) {
            $where = [];
            if ($media_tag_ids == 'ALL') {
            }else{
                $where['id'] = $media_tag_ids;
            }
            $res['media_tag'] = SysMediaTag::find()->select('id,name,e_name')->where($where)->asArray()->all();
        }
        if ($region_tag_ids && (in_array($type, [0, SysPageAuth::TYPE_REGION]))) {
            $where = [
                'company_id' => CompanyService::$company_id,
                'status' => 1,
            ];
            if ($region_tag_ids == 'ALL') {
            }else {
                $where['id'] = $region_tag_ids;
            }
            $region_tag = TagRegion::find()->select('id,pid,tag_name')->where($where)->asArray()->all();
            if($return_type == 'tree'){
                $res['region_tag'] = $region_tag ? CommonHelperService::getTree($region_tag) : [];
            }else{
                $res['region_tag'] = $region_tag;
            }
        }
        if ($tag_ids && (in_array($type, [0, SysPageAuth::TYPE_TAG]))) {
            $where = [
                'company_id' => CompanyService::$company_id,
                'status' => 1,
            ];
            if ($tag_ids != 'ALL') {
                $where['id'] = $tag_ids;
            }
            $tag = TagTags::find()->select('id,pid,tag_name')->where($where)->asArray()->all();
            if($return_type == 'tree') {
                $res['tag'] = $tag ? CommonHelperService::getTree($tag) : [];
            }else{
                $res['tag'] = $tag;
            }
        }

        return $res;
    }

    /**
     * 更新/新增权限id
     */
    public function addOrUpdate($rid, $r_name, $data)
    {
        $sql_ids_arr = [ //需要操作的ids
            'menu_ids' => [], //需要添加菜单
//            SysPageAuth::TYPE_MEDIA => [ //媒体平台管理权限
////                [
////                    'menu_id' => 1,
////                    'related_ids' => []
////                ],
//            ],
//            SysPageAuth::TYPE_REGION => [],
//            SysPageAuth::TYPE_TAG => [],
            'add' => [ //需要添加的数据
//                [
//                    'company_id' => 1,
//                    'rid' => 1,
//                    'menu_id' => 1,
//                    'type' => 1,
//                    'related_id' => 1,
//                    'c_uid' => 1,
//                ],
            ],
            'delete_ids' => [ //sys_page_auth 需要删除的ids集合
//                1,2,3
            ],
        ];
        $uid = UserService::$uid ? UserService::$uid : 0;
        $company_id = CompanyService::$company_id;
        $relate_data_exist = []; //已经存在的关联权限id
        //开启事物
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$rid) {
                $params = [
                    'company_id' => CompanyService::$company_id,
                    'name' => $r_name,
                    'c_uid' => $uid
                ];
                $query = Yii::$app->db;
                $query->createCommand()->insert(SysRole::tableName(), $params)->execute();
                $rid = $query->getLastInsertID();
            } else {
                //更新
                Yii::$app->db->createCommand()->update(SysRole::tableName(), ['name' => $r_name], "id=$rid")->execute();
                //查询所有已关联的权限id
                $relate_data = SysPageAuth::find()->select('id,menu_id,type,related_id')->where(['rid' => $rid])->asArray()->all();
                //整理数据
                foreach ($relate_data as $v) {
                    $relate_data_exist[$v['type']][$v['menu_id']][$v['id']] = $v['related_id'];
                }
            }
            //获取系统菜单集合
            $sys_menu_arr = Yii::$app->service->MenuService->getMenuList(2, 1);
            //对比数据
            foreach ($data as $v) {
                switch ($v['type']) {
                    case SysPageAuth::TYPE_MEDIA: //媒体平台管理权限
                    case SysPageAuth::TYPE_REGION: //地区管理权限
                    case SysPageAuth::TYPE_TAG: //标签管理权限
                        foreach ($v['data'] as $s_v) {
                            if (!$s_v['select_ids']) {
                                continue;
                            }
                            $menu_id = $s_v['id'];
                            $relate_exist_ids_arr = isset($relate_data_exist[$v['type']][$menu_id]) ? $relate_data_exist[$v['type']][$menu_id] : [];
                            $select_ids_arr = explode(',', $s_v['select_ids']);

                            //添加菜单
                            $this->addMenuById($sys_menu_arr, $menu_id, $sql_ids_arr['menu_ids']);
                            //添加权限关联id
                            $add_diff = array_diff($select_ids_arr, $relate_exist_ids_arr);
                            if ($add_diff) {
                                foreach ($add_diff as $r_id) {
                                    $sql_ids_arr['add'][] = [
                                        'company_id' => $company_id,
                                        'rid' => $rid,
                                        'menu_id' => $menu_id,
                                        'type' => $v['type'],
                                        'related_id' => $r_id,
                                        'c_uid' => $uid,
                                    ];
                                }
                            }
                            //删除多余的关联id
                            $delete_diff = array_diff($relate_exist_ids_arr, $select_ids_arr);
                            if ($delete_diff) {
                                $sql_ids_arr['delete_ids'] = array_merge($sql_ids_arr['delete_ids'], array_keys($delete_diff));
                            }
                            unset($relate_data_exist[$v['type']][$menu_id]);
                            if (isset($relate_data_exist[$v['type']]) && empty($relate_data_exist[$v['type']])) {
                                unset($relate_data_exist[$v['type']]);
                            }
                        }
                        break;
                    case SysPageAuth::TYPE_SYS: //系统管理权限
//                    case SysPageAuth::TYPE_CONTENT: //内容分析
                        $select_ids = isset($v['data'][0]['select_ids']) ? $v['data'][0]['select_ids'] : '';
                        if (!$select_ids) {
                            break;
                        }
                        $this->addMenuById($sys_menu_arr, explode(',', $select_ids), $sql_ids_arr['menu_ids']);
                        break;
                }
            }
            //删除原来已存在但未被选中的sys_page_auth表 id
            if ($relate_data_exist) {
                foreach ($relate_data_exist as $v) {
                    foreach ($v as $v2) {
                        $sql_ids_arr['delete_ids'] = array_merge($sql_ids_arr['delete_ids'], array_keys($v2));
                    }
                }
            }
            //执行sql入库
            $this->addPageAuth($rid, $sql_ids_arr);
            //提交
            $transaction->commit();
            //删除缓存
            Yii::$app->cache->delete(RedisKeyService::getKey(1003, $company_id));
            return true;
        }catch (Exception $e) {
            //回滚
            $transaction->rollBack();
            Yii::$app->service->LogService->saveSysLog(json_encode([
                'msg' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], JSON_UNESCAPED_UNICODE), $company_id, $uid, SysLog::TYPE_ERR);
            return false;
        }
    }

    /**
     * @param $sys_menu_arr //系统菜单
     * @param $need_menu_ids array|int //菜单
     * @param $return_menu_ids //需要返回的菜单id
     */
    private function addMenuById($sys_menu_arr, $need_menu_ids, &$return_menu_ids)
    {
        if (is_numeric($need_menu_ids)) {
            if (isset($sys_menu_arr[$need_menu_ids])) {
                $return_menu_ids[$need_menu_ids] = $need_menu_ids;
                if ($sys_menu_arr[$need_menu_ids]['pid']) {
                    $return_menu_ids[$sys_menu_arr[$need_menu_ids]['pid']] = $sys_menu_arr[$need_menu_ids]['pid'];
                }
            }
        }else {
            foreach ($need_menu_ids as $s_id) {
                if (!isset($sys_menu_arr[$s_id])) {
                    continue;
                }
                $return_menu_ids[$s_id] = $s_id;
                if ($sys_menu_arr[$s_id]['pid']) {
                    $return_menu_ids[$sys_menu_arr[$s_id]['pid']] = $sys_menu_arr[$s_id]['pid'];
                }
            }
        }
        //补充词库
        if (isset($return_menu_ids[9]) || isset($return_menu_ids[10])) { //内容检查/合规检查 有这两个任何一个就有 补充词库
            $return_menu_ids[13] = 13;
        }
        ksort($return_menu_ids);
    }

    /**
     * 执行sql入库
     */
    private function addPageAuth($rid, $sql_ids_arr)
    {
        foreach ($sql_ids_arr as $k => $v) {
            if (!$v) {
                continue;
            }
            switch ($k) {
                case 'menu_ids':
                    $v = $v ? implode(',', array_unique($v)) : '';
                    Yii::$app->db->createCommand()->update(SysRole::tableName(), ['menu_ids' => $v], "id=$rid")->execute();
                    break;
                case 'add':
                    Yii::$app->db->createCommand()->batchInsert(SysPageAuth::tableName()
                        , ['company_id', 'rid', 'menu_id', 'type', 'related_id', 'c_uid',]
                        , $v)->execute();
                    break;
                case 'delete_ids':
                    Yii::$app->db->createCommand()->delete(SysPageAuth::tableName(), ['in', 'id', $v])->execute();
                    break;
            }
        }
    }

    /**
     * 展示角色权限数据
     */
    public function getShowAuthData($rid, $role_info)
    {
        //返回数据
        $res = [
            SysPageAuth::TYPE_SYS => [
                'type' => SysPageAuth::TYPE_SYS,
                'name' => '系统管理权限',
                'list' => [
//                        [
//                            'id' => 1,
//                            'name' => '用户管理',
//                            'pid' => 0,
//                        ],
                ],
                'sub_select' => [
//                    [
//                            'id' => 0, //0表示只有没有选项
//                            'name' => '',
//                            'select_ids' => '1,2,3,4', //空表是没有选中任何选项
//                    ]
                ]
            ],
            SysPageAuth::TYPE_MEDIA => [
                'type' => SysPageAuth::TYPE_MEDIA,
                'name' => '媒体平台管理权限',
                'list' => [],
                'sub_select' => []
            ],
            SysPageAuth::TYPE_REGION => [
                'type' => SysPageAuth::TYPE_REGION,
                'name' => '地区管理权限',
                'list' => [],
                'sub_select' => []
            ],
            SysPageAuth::TYPE_TAG => [
                'type' => SysPageAuth::TYPE_TAG,
                'name' => '标签管理权限',
                'list' => [],
                'sub_select' => []
            ],
//            SysPageAuth::TYPE_CONTENT => [
//                'type' => SysPageAuth::TYPE_CONTENT,
//                'name' => '内容分析',
//                'list' => [],
//                'sub_select' => []
//            ],
        ];

        //查询系统媒体标签
        $sys_media_ids = CompanyService::$company_info['media_tag_ids'];
        $sys_media_ids_arr = $sys_media_ids ? explode(',', $sys_media_ids) : [];
        //查询角色关联的菜单
        $role_menu_ids = $rid ? $role_info['menu_ids'] : [];
        $role_menu_ids_arr = $role_menu_ids ? explode(',', $role_menu_ids) : [];
        //查询该角色所有已关联的权限id
        $relate_data_exist = [];
        if ($rid) {
            $relate_data = SysPageAuth::find()->select('id,menu_id,type,related_id')->where(['rid' => $rid])->asArray()->all();
            foreach ($relate_data as $v) { //整理数据
                $relate_data_exist[$v['type']][$v['menu_id']][$v['id']] = $v['related_id'];
            }
        }
        //获取系统的所有菜单
        $sys_menu_list = Yii::$app->service->MenuService->getMenuList(2, 1);
        foreach ($sys_menu_list as &$v) {
            unset($v['url'], $v['is_menu']);
        }
        unset($v);
        //type=1
        unset(SysPageAuth::$manage_menu_ids[5], SysPageAuth::$manage_menu_ids[6]); //隐藏
        $res[SysPageAuth::TYPE_SYS]['list'] = array_values(array_intersect_key($sys_menu_list, SysPageAuth::$manage_menu_ids));
        $res[SysPageAuth::TYPE_SYS]['sub_select'][] = [
            'id' => 0,
            'name' => '',
            'select_ids' => implode(',', array_intersect($role_menu_ids_arr, SysPageAuth::$manage_menu_ids)),
        ];
        //type=5
//        $res[SysPageAuth::TYPE_CONTENT]['list'] = array_values(array_intersect_key($sys_menu_list, array_flip(SysPageAuth::$content_analyze_menu_ids)));
//        $res[SysPageAuth::TYPE_CONTENT]['sub_select'][] = [
//            'id' => 0,
//            'name' => '',
//            'select_ids' => implode(',', array_intersect($role_menu_ids_arr, SysPageAuth::$content_analyze_menu_ids)),
//        ];
        //type=2
        $res[SysPageAuth::TYPE_MEDIA]['list'] = SysMediaTag::find()->select('id,name,pid')->where(($sys_media_ids_arr ? ['in', 'id', $sys_media_ids_arr] : []))->asArray()->all();;
        foreach (SysPageAuth::$media_menu_ids as $menu_id) {
            if (!isset($sys_menu_list[$menu_id])) {
                continue;
            }
            $sub_select = [
                'id' => $sys_menu_list[$menu_id]['id'],
                'name' => $sys_menu_list[$menu_id]['name'],
                'select_ids' => '',
            ];
            if (isset($relate_data_exist[SysPageAuth::TYPE_MEDIA][$menu_id])) {
                $sub_select['select_ids'] = implode(',', $relate_data_exist[SysPageAuth::TYPE_MEDIA][$menu_id]);
            }
            $res[SysPageAuth::TYPE_MEDIA]['sub_select'][] = $sub_select;
        }
        //type=3
        $res[SysPageAuth::TYPE_REGION]['list'] = TagRegion::find()->select('id,tag_name name,pid')->where(['company_id' => CompanyService::$company_id, 'status' => 1])->asArray()->all();
        foreach (SysPageAuth::$region_menu_ids as $menu_id) {
            if (!isset($sys_menu_list[$menu_id])) {
                continue;
            }
            $sub_select = [
                'id' => $sys_menu_list[$menu_id]['id'],
                'name' => $sys_menu_list[$menu_id]['name'],
                'select_ids' => '',
            ];
            if (isset($relate_data_exist[SysPageAuth::TYPE_REGION][$menu_id])) {
                $sub_select['select_ids'] = implode(',', $relate_data_exist[SysPageAuth::TYPE_REGION][$menu_id]);
            }
            $res[3]['sub_select'][] = $sub_select;
        }
        //type=4
        $res[SysPageAuth::TYPE_TAG]['list'] = TagTags::find()->select('id,tag_name name,pid')->where(['company_id' => CompanyService::$company_id, 'status' => 1])->asArray()->all();
        foreach (SysPageAuth::$tag_menu_ids as $menu_id) {
            if (!isset($sys_menu_list[$menu_id])) {
                continue;
            }
            $sub_select = [
                'id' => $sys_menu_list[$menu_id]['id'],
                'name' => $sys_menu_list[$menu_id]['name'],
                'select_ids' => '',
            ];
            if (isset($relate_data_exist[SysPageAuth::TYPE_TAG][$menu_id])) {
                $sub_select['select_ids'] = implode(',', $relate_data_exist[SysPageAuth::TYPE_TAG][$menu_id]);
            }
            $res[SysPageAuth::TYPE_TAG]['sub_select'][] = $sub_select;
        }

        return array_values($res);
    }
}