<?php
/**
 * 错误码定义，然后直接调用公共方法codeMsg
 */
$code = [
    'fail'  => [
        'code' => -100,
        'msg'  => '操作失败'
    ],
    'paramsError' => [
        'code' => 10001,
        'msg'  => '参数错误'
    ],
    'systemError' => [
        'code' => 10002,
        'msg'  => '系统错误'
    ],
    'authError'   => [
        'code' => 10003,
        'msg'  => '认证错误，请先登录'
    ],
    '404'   => [
        'code' => 404,
        'msg'  => '接口不存在'
    ],
    'memberExpire' => [
        'code' => 10004,
        'msg'  => '会员过期，请购买会员'
    ],
    'authorityError' => [
        'code' => 10005,
        'msg' => '你没有此权限'
    ],
    'illegalRequest' => [
        'code' => 10006,
        'msg'  => '验签失败，请稍后再试'
    ],
    'companyError' => [
        'code' => 10007,
        'msg'  => '企业信息异常'
    ],
    'notLogin' => [
        'code' => 10008,
        'msg'  => '未登录'
    ],
    'rankAddError' => [
        'code' => 10009,
        'msg'  => '未知错误，请重试或联系管理员'
    ],
    'accountError' => [
        'code' => 10010,
        'msg'  => '榜单账号不存在'
    ],
    'rankGroupError' => [
        'code' => 10011,
        'msg'  => '榜单分组不存在'
    ],
    'accountNumError' => [
        'code' => 10012,
        'msg'  => '榜单账号数量超限制'
    ],
    'webNumError' => [
        'code' => 10013,
        'msg'  => '网站数量超限制'
    ],
];
return ['code' => $code];
