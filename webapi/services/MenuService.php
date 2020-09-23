<?php
/**
 * 菜单服务
 * @author xiao
 * @date 2020年6月10日14:06:19
 */
namespace webapi\services;

use webapi\models\SysMenu;
use Yii;

class MenuService
{
    /**
     * 获取菜单
     * @param int $type //1默认获取角色下菜单 2获取系统菜单 3获取所有
     * @param bool $is_menu
     * @return array|mixed
     */
    public function getMenuList($type = 1, $is_menu = false)
    {
        switch ($type) {
            case 1:
                //获取角色允许进入的菜单ids
                $role_info = Yii::$app->service->RoleService->getRoleInfo();
                $menu_ids_str = isset($role_info['menu_ids']) ? $role_info['menu_ids'] : '';
                //角色菜单配置id为空则获取系统菜单配置id
                if ($menu_ids_str) {
                    if (CompanyService::$company_info['menu_ids']) {//做交集
                        $menu_ids = array_intersect(explode(',', $menu_ids_str), explode(',', CompanyService::$company_info['menu_ids']));
                        if (!$menu_ids) {
                            return jsonErrorReturn('fail', '', '角色菜单发生错误，请重新编辑角色');
                        }
                    }else {
                        $menu_ids = explode(',', $menu_ids_str);
                    }
                }else {
                    $menu_ids = CompanyService::$company_info['menu_ids'] ? explode(',', CompanyService::$company_info['menu_ids']) : [];
                }
                break;
            case 2:
                //获取系统菜单
                $menu_ids_str = CompanyService::$company_info['menu_ids'];
                $menu_ids = $menu_ids_str ? explode(',', $menu_ids_str) : [];
                break;
            case 3:
                $menu_ids = [];
                break;
        }
        $menu_list = Yii::$app -> cache -> get(RedisKeyService::getKey(1001));
        $menu_list = json_decode($menu_list, true);
        $menu_list = '';
        if (!$menu_list) {
            $menu_list = SysMenu::find()->select('id,pid,name,url,is_menu,icon')->asArray()->all();
            $menu_list = array_column($menu_list, null, 'id');
            //缓存
            Yii::$app -> cache -> set(RedisKeyService::getKey(1001), json_encode($menu_list, JSON_UNESCAPED_UNICODE), (30 * 24 * 3600));
        }
        $res = [];
        if (!$menu_ids) {
            if ($is_menu) {
                $res = array_filter($menu_list, function($t) {return $t['is_menu'] == 1;});
                $res = array_column($res, null, 'id');
            }else {
                $res = $menu_list;
            }
        }else {
            foreach ($menu_ids as $v) {
                if (!isset($menu_list[$v])) {
                    continue;
                }
                if ($is_menu && $menu_list[$v]['is_menu'] != 1) {
                    continue;
                }
                $res[$v] = $menu_list[$v];
            }
        }
        ksort($res);

        return $res;
    }

    /**
     * 根据url获取二级菜单的id
     */
    public function getMenuId()
    {
        $now_url = trim(Yii::$app->requestedRoute, '/');

        //获取当前菜单的二级菜单id
        $all_menu_list = $this->getMenuList(3);
        $menu_url_list = array_column($all_menu_list, 'id', 'url');
        if (!isset($menu_url_list[$now_url])) {
            $id = 0;
        }else {
            $id = $menu_url_list[$now_url];
            if ($all_menu_list[$id]['is_menu'] != 1) {
                $id = $all_menu_list[$id]['pid'];
                if (!isset($all_menu_list[$id])) {
                    $id = 0;
                }
            }
        }

        return $id;
    }

    /**
     * 根据菜单id获取菜单信息
     * @param $menu_ids array|int
     */
    public function getMenuByIds($menu_ids)
    {
        //获取当前角色可访问的菜单
        $menu_list = $this->getMenuList(2);
        if (is_int($menu_ids)) {
            if (isset($menu_list[$menu_list])) {
                $menu_list = [
                    $menu_ids => $menu_list[$menu_list]
                ];
            }else {
                $menu_list = [];
            }
        }else {
            $menu_ids = array_flip($menu_ids); //反转数组
            $menu_list = array_intersect_key($menu_list, $menu_ids);
        }

        return $menu_list;
    }
}