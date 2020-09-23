<?php
/**
 * 公共方法服务
 * @author xiao
 * @date 2020年6月11日11:23:54
 */
namespace webapi\services;

class CommonHelperService
{
    /**
     * 递归获取树形数据
     * @param $array
     * @param int $pid
     * @return array
     */
    public static function getTree($array, $pid = 0){
        $data = [];
        foreach ($array as $k=>$v){        //PID符合条件的
            if($v['pid'] == $pid){            //寻找子集
                $child = self::getTree($array,$v['id']);            //加入数组
                if ($child) {
                    $v['child'] = $child;
                }
                unset($v['pid'], $v['is_menu']);
                $data[] = $v;//加入数组中
            }
        }
        return $data;
    }

    /**
     * 组织分页数据
     * @param $data
     * @param $page
     * @param $num
     * @param $total
     * @return array
     */
    public static function getPage($data, $page, $num, $total)
    {
        $_page = [];
        $_page['data'] = $data;
        $_page['page'] = $page ? (int)$page : 1;
        $_page['num'] = (int)$num;
        $_page['count'] = (int)$total;
        $_page['pages'] = floor(($total + $num - 1) / $num);

        return $_page;
    }

    /**
     * 检查用户名
     */
    public static function checkUserName($name)
    {
        if(!preg_match('/^[a-z0-9]+$/i', $name)) {
            return false;
        }
        return true;
    }

    /**
     * 二维数组排序
     * @param array $data
     * @param $sort // SORT_ASC SORT_DESC
     * @param $key // 排序的键
     * @return array
     */
    public static function arrayMultiSort($data, $key, $sort = SORT_DESC)
    {
        if (!$data) {
            return [];
        }
        foreach ($data as $item) {
            $sortKey[] = $item[$key];
        }
        array_multisort($sortKey, $sort, $data);
        return $data;
    }
}