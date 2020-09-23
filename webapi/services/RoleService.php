<?php
/**
 * 角色服务
 * @author xiao
 * @date 2020年6月10日14:06:19
 */
namespace webapi\services;

use webapi\models\Company;
use webapi\models\SysPageAuth;
use webapi\models\SysRole;
use Yii;

class RoleService
{
    /**
     * 获取所有角色列表
     */
    public function getRoleList()
    {
        //读取缓存
        $key = RedisKeyService::getKey(1003, CompanyService::$company_id);
        $role_list = Yii::$app -> cache -> get($key);
        $role_list = json_decode($role_list, true);
        if (!$role_list) { //缓存不存在
            //获取角色信息
            $role_list = SysRole::find()->select('id,name,is_super,menu_ids')->where(['company_id' => CompanyService::$company_id, 'status' => 0])->asArray()->all();
            $role_list = array_column($role_list, null, 'id');
            //更新缓存
            Yii::$app -> cache -> set($key, json_encode($role_list, JSON_UNESCAPED_UNICODE), (7 * 24 * 3600));
        }

        return $role_list;
    }
    
    /**
     * 获取角色关联数据(包含菜单权限数据)
     */
    public function getRoleInfo($rid = null)
    {
        if ($rid === null) {
            $rid = UserService::$rid ? UserService::$rid : 0;
        }
        if (!$rid) {
            return [];
        }
        $role_list = $this->getRoleList();
        return isset($role_list[$rid]) ? $role_list[$rid] : [];
    }

}