<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2019/12/6
 * Time: 10:57
 */

namespace webapi\services;


use webapi\extensions\RpcClient;

class RpcService {



    public function getSimArticleNum($sid,$simHash,$newsIds=[],$data = []){
        $schemeInfo = SchemeService()->getSchemeInfo($sid);
        if (!$schemeInfo) return false;
        if ($newsIds && count($simHash)!=count($simHash)) return false;
        if (!$simHash) return false;
        $rpc = new RpcClient();
        $baseEs = SchemeService()->getNewsListFromEsCondition($data, $schemeInfo,true);
        $list = $rpc->getSimNum($simHash,$baseEs->getCondition())['result'];
        $return = [] ;
        foreach ($simHash as $k => $hash){
            $key  = $newsIds ? $newsIds[$k] : $hash;
            $value = isset($list[$hash]) ? ( $list[$hash] - 1 > 0 ?  $list[$hash] - 1 : 0 ) : 0;
            $return[$key] = $value;
        }
        return $return;
    }

}