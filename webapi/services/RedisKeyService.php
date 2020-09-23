<?php
/**
 * Created by PhpStorm.
 * User: xiao
 * Date: 2020年6月10日10:03:27
 */
namespace webapi\services;

class RedisKeyService
{
    private static $redis_key = [
//        1000 => 'pic:code:kye:', //名称/类型/时间/备注
        1000 => 'sd:pic:code:kye:', //验证码/str/60s/pic:code:kye:md5(ip地址)
        1001 => 'sd:menu:list', //全部菜单/str/1月/
        1002 => 'sd:company:info:', //公司信息/str/1月/sd:company:info:(公司id)
        1003 => 'sd:role:list:', //角色信息/str/7天/sd:role:list:(公司id)
        1004 => 'sd:user:base:info:', //用户status、end_time、is_ban/str/1天/sd:user:ban:(公司id):(用户id)
        1005 => 'sd:del:rank:account', //删除榜单中账号
    ];

    /**
     * 获取key
     * @param $code
     * @param string $str
     */
    public static function getKey($code, $str = '')
    {
        if (empty($code)) {
            return '';
        }
        return self::$redis_key[$code] . $str;
    }
}
