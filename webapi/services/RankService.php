<?php


namespace webapi\services;


use webapi\extensions\Databus;
use webapi\models\AccountAddWait;
use webapi\models\AccountDouyinAll;
use webapi\models\AccountKuaishouAll;
use webapi\models\AccountToutiaoAll;
use webapi\models\AccountWebAll;
use webapi\models\AccountWeiboAll;
use webapi\models\AccountWxAll;
use webapi\models\Company;
use webapi\models\CompanyDouyin;
use webapi\models\CompanyKuaishou;
use webapi\models\CompanyRankGroup;
use webapi\models\CompanyToutiao;
use webapi\models\CompanyWeb;
use webapi\models\CompanyWeibo;
use webapi\models\CompanyWx;
use webapi\models\GsdataSiteDomainNew;
use Yii;

/**
 * 媒体榜单账号新增及查询业务逻辑
 * Class RankService
 * @package webapi\services
 */
class RankService
{
    public $databus;

    public function __construct()
    {
        $this->databus = new Databus();
    }

    //+---------------------账号搜索--------------------------
    /**
     * 微信账号搜索
     * @param $search
     * @return array
     */
    public function getWxSearchList($search)
    {
        $res = $this->databus->weixinSearch($search, 1);

        //$is_add_wx_public = CompanyService::$company_info->is_add_wx_public;

        $list = [];
        if($res['success'] == true){
            $data = isset($res['data']['list']) ? $res['data']['list'] : [];
            if($data){
                foreach ($data as $v) {
                    /*if($is_add_wx_public == 1){
                        if($v['is_verified'] == '认证'){
                            $list[] = [
                                'wx_nickname'=> $v['wx_nickname'],
                                'wx_name' => $v['wx_name'],
                                'wx_biz'=> $v['wx_biz'],
                                'wx_logo' => $v['wx_qrcode']
                            ];
                        }
                    }else{*/
                        $list[] = [
                            'wx_nickname'=> $v['wx_nickname'],
                            'wx_name' => $v['wx_name'],
                            'wx_biz'=> $v['wx_biz'],
                            'logo' => $v['wx_logo']
                        ];
                    //}
                }
            }
        }
        return $list;
    }

    /**
     * 微博账号搜索
     * @param $search
     * @return array
     */
    public function getWeiboSearchList($search)
    {
        $res = $this->databus->weiboSearch($search, 1);

        $list = [];
        if($res['success'] == true){
            $data = isset($res['data']['list']) ? $res['data']['list'] : [];
            if($data){
                foreach ($data as $v) {
                    $list[] = [
                        'weibo_uid'=> $v['weibo_uid'],
                        'weibo_nickname' => $v['nickname'],
                        'logo' => $v['avatar']
                    ];
                }
            }
        }
        return $list;
    }

    /**
     * 头条账号搜索
     * @param $search
     * @return array
     */
    public function getToutiaoSearchList($search)
    {
        $res = $this->databus->toutiaoSearch($search, 1);

        $list = [];
        if($res['success'] == true){
            $data = isset($res['data']['list']) ? $res['data']['list'] : [];
            if($data){
                foreach ($data as $v) {
                    $list[] = [
                        'toutiao_user_id'=> $v['toutiao_user_id'],
                        'toutiao_nickname' => $v['nickname'],
                        'logo' => $v['avatar_url'],
                    ];
                }
            }
        }
        return $list;
    }

    /**
     * 抖音账号搜索
     * @param $search
     * @return array
     */
    public function getDouyinSearchList($search)
    {
        $res = $this->databus->douyinSearch($search, 1);

        $list = [];
        if($res['success'] == true){
            $data = isset($res['data']['list']) ? $res['data']['list'] : [];
            if($data){
                foreach ($data as $v) {
                    $list[] = [
                        'douyin_id'=> $v['douyin_id'],
                        'douyin_name' => $v['douyin_name'],
                        'douyin_code' => $v['douyin_code'],
                        'logo' => $v['douyin_img'],
                    ];
                }
            }
        }
        return $list;
    }

    /**
     * 快手账号搜索
     * @param $search
     * @return array
     */
    public function getKuaishouSearchList($search)
    {
        $res = $this->databus->kuaishouSearch($search, 1);

        $list = [];
        if($res['success'] == true){
            $data = isset($res['data']['list']) ? $res['data']['list'] : [];
            if($data){
                foreach ($data as $v) {
                    $list[] = [
                        'kuaishou_id'=> $v['kuaishou_id'],
                        'kuaishou_name' => $v['kuaishou_name'],
                        'kuaishou_code' => $v['kuaishou_code'],
                        'logo' => $v['kuaishou_img'],
                    ];
                }
            }
        }
        return $list;
    }

    //+----------------------【添加账号到榜单】--------------------------

    /**
     * 添加微信账号到榜单
     * @param $uid
     * @param $company_id
     * @param $type
     * @param $account
     * @return array|bool|void
     */
    public function addWxAccount($uid,$company_id,$type,$account)
    {
        //+ 公司信息
        $info = Company::find()->where(['id' => $company_id])->one();
        if(empty($info)){
            return jsonErrorReturn('companyError');
        }

        //+① 判断该公司是否创建分组，若没有创建则先创建分组
        $groupInfo = CompanyRankGroup::findOne(['company_id'=>$company_id]);
        $group_id = 0;
        if(!empty($groupInfo)){
            $group_id = $groupInfo->wx_group_id;
        }
        //+++ 不存在分组，则创建
        if($group_id == 0){
            //+ 新建微信分组
            $group_name = $info->company_name.'微信分组';
            $res = $this->databus->addWeixinGroup($group_name);
            if($res['success'] == true){
                $group_id = isset($res['data']['group']['id']) ? $res['data']['group']['id'] : 0;
                if($group_id == 0){
                    //+++ 分组创建失败，直接返回错误
                    return jsonErrorReturn('rankAddError');
                }
                if(empty($groupInfo)){
                    $groupInfo = new CompanyRankGroup();
                    $groupInfo->company_id = $company_id;
                    $groupInfo->company_index = $info->company_index;
                }
                $groupInfo->wx_group_id = $group_id;
                $res = $groupInfo->save();
                if($res === false){
                    //+++ 分组创建失败，直接返回错误
                    return jsonErrorReturn('rankAddError');
                }
            }else{
                //+++ 分组创建失败，直接返回错误
                return jsonErrorReturn('rankAddError');
            }
        }

        // 校验账号数量是否达到上限
        $config_num = $info->media_create_num;
        $account_num = CompanyWx::find()->where(['company_id' => $company_id])->count();
        if ($account_num >= $config_num) return jsonErrorReturn('accountNumError');

        try {
            //+② 往微信账号表中添加账号，若账号表中有该账号，则更新company_ids,company_index
            $all = count($account);//需要添加的条数
            $success = 0;//成功条数
            $fail = 0;//失败条数
            $isHas = 0;//失败中当前已有的条数
            $successList = [];
            $failList = [];

            //+++ 由于开放平台接口每次最多添加10个账号，所以按每10个一组进行添加
            $accArr = array_chunk($account, 10);
            foreach ($accArr as $vv) {
                if($type == 1){
                    $wx_biz = implode(',',$vv);
                    $res = $this->databus->addWeixinAcc($group_id, $wx_biz);
                }elseif($type == 2){
                    $wx_url = implode(',',$vv);
                    $res = $this->databus->addWeixinAccUrl($group_id, $wx_url);
                }else{
                    return false;
                }

                if($res['success'] == true){
                    $data = isset($res['data']['success']) ? $res['data']['success'] : [];
                    //$success += count($data);
                    if($data){
                        foreach ($data as $acc) {
                            //+++ 查询微信账号表中是否已有该账号
                            $accHas = AccountWxAll::findOne(['nickname_id'=>$acc['account_id']]);
                            if(empty($accHas)){
                                $transaction = \Yii::$app->db->beginTransaction();
                                $accHas = new AccountWxAll();
                                $accHas->nickname_id = $acc['account_id'];
                                $accHas->wx_biz = $acc['wx_biz'];
                                $accHas->wx_logo = isset($acc['wx_logo']) ? $acc['wx_logo'] : '';
                                $accHas->wx_name = $acc['wx_name'];
                                $accHas->wx_nickname = $acc['wx_nickname'];
                                $accHas->company_ids = strval($company_id);
                                $accHas->comapny_index = $info->company_index;
                                $res = $accHas->save();
                                if($res !== false){
                                    //+③ 往公司微信账号关联表中添加账号
                                    $compAcc = new CompanyWx();
                                    $compAcc->company_id = $company_id;
                                    $compAcc->company_index = $info->company_index;
                                    $compAcc->nickname_id = $acc['account_id'];
                                    $compAcc->account_id = $acc['wx_name'];
                                    $res = $compAcc->save();
                                    if($res === false){
                                        //$failList[] = $type==1 ? $acc['wx_biz'] : $acc['wx_url'];
                                        $transaction->rollBack();
                                    }else{
                                        $transaction->commit();
                                        $success += 1;
                                        $successList[] = $acc['wx_nickname'];
                                    }
                                }
                                $transaction->rollBack();
                            }else{
                                //++++ 判断该账号该公司是否已经添加
                                if(in_array($company_id, explode(',',$accHas->company_ids))){
                                    $isHas += 1;
                                }else{
                                    $transaction = \Yii::$app->db->beginTransaction();
                                    //++++ 账号没有添加，则追加公司信息
                                    $company_ids1 = $accHas->company_ids ? $accHas->company_ids.','.$company_id : $company_id;
                                    $company_index1 = $accHas->comapny_index ? $accHas->comapny_index . ',' . $info->company_index : $info->company_index;
                                    $accHas->company_ids = $company_ids1;
                                    $accHas->comapny_index = $company_index1;
                                    $accHas->staus = 0;
                                    $res = $accHas->save();
                                    if($res !== false){
                                        //+③ 往公司微信账号关联表中添加账号
                                        $compAcc = new CompanyWx();
                                        $compAcc->company_id = $company_id;
                                        $compAcc->company_index = $info->company_index;
                                        $compAcc->nickname_id = $acc['account_id'];
                                        $compAcc->account_id = $acc['wx_name'];
                                        $res = $compAcc->save();
                                        if($res === false) {
                                            //$failList[] = $type==1 ? $acc['wx_biz'] : $acc['wx_url'];
                                            $transaction->rollBack();
                                        }else{
                                            $transaction->commit();
                                            $success += 1;
                                            $successList[] = $acc['wx_nickname'];
                                        }
                                    }
                                    $transaction->rollBack();
                                }
                            }
                        }
                    }
                    //$failList = array_merge($failList, $res['data']['failure']);
                }
                //错误
                if (isset($res['data']['failure']) && $res['data']['failure']) {
                    foreach ($res['data']['failure'] as $k => $v) {
                        $err_msg[$v] = '账号或链接不正确';
                    }
                }
            }
            $fail = $all - $success;

            //+ 系统日志
            if($success>0){
                $wxNames = implode(',',$successList);
                $content = '微信号:'.$wxNames.'已被增到新媒体账号管理系统数据库中。';
                \Yii::$app->service->LogService->saveSysLog($content);
            }

            return [
                'success'=> $success,
                'fail'=> $fail,
                'isHas'=> $isHas,
                'err_msg'=> isset($err_msg) ? $err_msg : [],
            ];
        } catch (\Exception $e) {
            return false;
        }
    }

    //---------------微博账号-------------
    /**
     * 添加微博账号到榜单
     * @param $uid
     * @param $company_id
     * @param $type
     * @param $account
     * @return array|bool|void
     */
    public function addWeiboAccount($uid,$company_id,$type,$account)
    {
        //+ 公司信息
        $info = Company::find()->where(['id' => $company_id])->one();
        if(empty($info)){
            return jsonErrorReturn('companyError');
        }

        //+① 判断该公司是否创建分组，若没有创建则先创建分组
        $groupInfo = CompanyRankGroup::findOne(['company_id'=>$company_id]);
        $group_id = 0;
        if(!empty($groupInfo)){
            $group_id = $groupInfo->weibo_group_id;
        }
        //+++ 不存在分组，则创建
        if($group_id == 0){
            //+ 新建分组
            $group_name = $info->company_name.'微博分组';
            $res = $this->databus->addWeiboGroup($group_name);
            if($res['success'] == true){
                $group_id = isset($res['data']['group']['id']) ? $res['data']['group']['id'] : 0;
                if($group_id == 0){
                    //+++ 分组创建失败，直接返回错误
                    return jsonErrorReturn('rankAddError');
                }
                if(empty($groupInfo)){
                    $groupInfo = new CompanyRankGroup();
                    $groupInfo->company_id = $company_id;
                    $groupInfo->company_index = $info->company_index;
                }
                $groupInfo->weibo_group_id = $group_id;
                $res = $groupInfo->save();
                if($res === false){
                    //+++ 分组创建失败，直接返回错误
                    return jsonErrorReturn('rankAddError');
                }
            }else{
                //+++ 分组创建失败，直接返回错误
                return jsonErrorReturn('rankAddError');
            }
        }

        // 校验账号数量是否达到上限
        $config_num = $info->media_create_num;
        $account_num = CompanyWeibo::find()->where(['company_id' => $company_id])->count();
        if ($account_num >= $config_num) return jsonErrorReturn('accountNumError');

        try {
            //+② 往账号表中添加账号，若账号表中有该账号，则更新company_ids,company_index
            $all = count($account);//需要添加的条数
            $success = 0;//成功条数
            $fail = 0;//失败条数
            $isHas = 0;//失败中当前已有的条数
            $successList = [];
            $failList = [];
            $now_date = date('Y-m-d H:i:s');

            //+++ 由于开放平台接口每次最多添加10个账号，所以按每10个一组进行添加
            $accArr = array_chunk($account, 10);
            foreach ($accArr as $vv) {
                if($type == 1){
                    $weibo_uid = implode(',',$vv);
                    $res = $this->databus->addWeiboAcc($group_id, $weibo_uid);
                    if($res['success'] == true){
                        $data = isset($res['data']['success']) ? $res['data']['success'] : [];
                        if($data){
                            foreach ($data as $acc) {
                                $return = $this->addWeiboAccOne($acc,$company_id,$info->company_index,$success,$isHas,$successList);
                                $success = $return['success'];
                                $isHas = $return['isHas'];
                                $successList = $return['success_name'];
                            }
                        }
                    }
                }elseif($type == 2){
                    //+ 微博根据链接添加，每次只能添加一个链接
                    foreach ($vv as $weibo_url) {
                        $res = $this->databus->addWeiboAccUrl($group_id, $weibo_url);
                        if($res['success'] == true && isset($res['data']['success']) && $res['data']['success']){
                            //查询微博账号信息
                            $wb_id = $res['data']['success']['wb_uid'];
                            $wb_info = $this->databus->getWbInfo($wb_id);
                            if (isset($wb_info['data']['info'])) {
                                $return = $this->addWeiboAccOne([
                                    'weibo_uid' => $wb_id,
                                    'nickname' => $wb_info['data']['info']['nickname'],
                                ],$company_id, $info->company_index, $success, $isHas, $successList);
                                $success = $return['success'];
                                $isHas = $return['isHas'];
                                $successList = $return['success_name'];
                            }else {
                                //采集组还没有数据等待稍后脚本添加
                                $accWait = new AccountAddWait();
                                $accWait->company_id = strval($company_id);
                                $accWait->company_index = $info->company_index;
                                $accWait->rid = strval($wb_id);
                                $accWait->type = 1;
                                $accWait->c_time = $now_date;
                                $accWait->save();
                                $err_msg[$weibo_url] = '添加成功，最迟24小时后显示';
                                $success ++;
                            }
                        }else{
                            $err_msg[$weibo_url] = $res['data']['message'];
                        }
                    }
                }else{
                    return false;
                }
            }
            $fail = $all - $success;

            //+ 系统日志
            if($success>0){
                $wxNames = implode(',',$successList);
                $content = '微博号:'.$wxNames.'已被增到新媒体账号管理系统数据库中。';
                \Yii::$app->service->LogService->saveSysLog($content);
            }

            return [
                'success'=> $success,
                'fail'=> $fail,
                'isHas'=> $isHas,
                'err_msg'=> isset($err_msg) ? $err_msg : [],
            ];
        } catch (\Exception $e) {
//            echo '<pre>';
//            print_r([$e->getMessage(), $e->getFile(), $e->getLine()]);
//            exit;
            return false;
        }
    }

    /**
     * 添加一个微博账号到榜单
     * @param $acc
     * @param $company_id
     * @param $company_index
     * @param $success
     * @param $isHas
     * @param $successList
     * @return array
     */
    public function addWeiboAccOne($acc,$company_id,$company_index,$success = 0,$isHas = 0,$successList = [])
    {
        //+++ 查询账号表中是否已有该账号
        $accHas = AccountWeiboAll::findOne(['weibo_uid' => $acc['weibo_uid']]);
        if(empty($accHas)){
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                $accHas = new AccountWeiboAll();
                $accHas->weibo_uid = $acc['weibo_uid'];
                $accHas->nickname = isset($acc['nickname']) ? $acc['nickname'] : '';
                $accHas->avatar = isset($acc['avatar']) ? $acc['avatar'] : '';
                $accHas->company_ids = strval($company_id);
                $accHas->comapny_index = $company_index;
                $res = $accHas->save();
                if($res === false){
                    $transaction->rollBack();
                    return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                }

                //+③ 往公司账号关联表中添加账号
                $compAcc = new CompanyWeibo();
                $compAcc->company_id = $company_id;
                $compAcc->company_index = $company_index;
                $compAcc->weibo_uid = $acc['weibo_uid'];
                $compAcc->account_id = $acc['weibo_uid'];
                $res = $compAcc->save();
                if ($res === false) {
                    $transaction->rollBack();
                    return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                }
                $transaction->commit();
                $success += 1;
                $successList[] = isset($acc['nickname']) ? $acc['nickname'] : '';
                return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
            } catch (\Exception $e) {
                $transaction->rollBack();
                return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
            }
        }else{
            //++++ 判断该账号该公司是否已经添加
            if(in_array($company_id, explode(',',$accHas->company_ids))){
                $isHas += 1;
                return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
            }else{
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    //++++ 账号没有添加，则追加公司信息
                    $company_ids1 = $accHas->company_ids ? $accHas->company_ids.','.$company_id : $company_id;
                    $company_index1 = $accHas->comapny_index ? $accHas->comapny_index . ',' . $company_index : $company_index;
                    $accHas->company_ids = $company_ids1;
                    $accHas->comapny_index = $company_index1;
                    $accHas->staus = 0;
                    $res = $accHas->save();
                    if($res === false){
                        $transaction->rollBack();
                        return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                    }
                    //+③ 往公司账号关联表中添加账号
                    $compAcc = new CompanyWeibo();
                    $compAcc->company_id = $company_id;
                    $compAcc->company_index = $company_index;
                    $compAcc->weibo_uid = $acc['weibo_uid'];
                    $compAcc->account_id = $acc['weibo_uid'];
                    $res = $compAcc->save();
                    if ($res === false) {
                        $transaction->rollBack();
                        return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                    }
                    $transaction->commit();
                    $success += 1;
                    $successList[] = isset($acc['nickname']) ? $acc['nickname'] : '';
                    return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                }
            }
        }
    }

    //---------------头条账号-------------
    /**
     * 添加头条账号到榜单
     * @param $uid
     * @param $company_id
     * @param $type
     * @param $account
     * @return array|bool|void
     */
    public function addToutiaoAccount($uid,$company_id,$type,$account)
    {
        //+ 公司信息
        $info = Company::find()->where(['id' => $company_id])->one();
        if(empty($info)){
            return jsonErrorReturn('companyError');
        }

        //+① 判断该公司是否创建分组，若没有创建则先创建分组
        $groupInfo = CompanyRankGroup::findOne(['company_id'=>$company_id]);
        $group_id = 0;
        if(!empty($groupInfo)){
            $group_id = $groupInfo->toutiao_group_id;
        }
        //+++ 不存在分组，则创建
        if($group_id == 0){
            //+ 新建分组
            $group_name = $info->company_name.'头条分组';
            $res = $this->databus->addToutiaoGroup($group_name);
            if($res['success'] == true){
                $group_id = isset($res['data']['group']['id']) ? $res['data']['group']['id'] : 0;
                if($group_id == 0){
                    //+++ 分组创建失败，直接返回错误
                    return jsonErrorReturn('rankAddError');
                }
                if(empty($groupInfo)){
                    $groupInfo = new CompanyRankGroup();
                    $groupInfo->company_id = $company_id;
                    $groupInfo->company_index = $info->company_index;
                }
                $groupInfo->toutiao_group_id = $group_id;
                $res = $groupInfo->save();
                if($res === false){
                    //+++ 分组创建失败，直接返回错误
                    return jsonErrorReturn('rankAddError');
                }
            }else{
                //+++ 分组创建失败，直接返回错误
                return jsonErrorReturn('rankAddError');
            }
        }

        // 校验账号数量是否达到上限
        $config_num = $info->media_create_num;
        $account_num = CompanyToutiao::find()->where(['company_id' => $company_id])->count();
        if ($account_num >= $config_num) return jsonErrorReturn('accountNumError');

        try {
            //+② 往账号表中添加账号，若账号表中有该账号，则更新company_ids,company_index
            $all = count($account);//需要添加的条数
            $success = 0;//成功条数
            $fail = 0;//失败条数
            $isHas = 0;//失败中当前已有的条数
            $successList = [];
            $failList = [];
            $now_date = date('Y-m-d H:i:s');

            //+++ 由于开放平台接口每次最多添加10个账号，所以按每10个一组进行添加
            $accArr = array_chunk($account, 10);
            foreach ($accArr as $vv) {
                if($type == 1){
                    $toutiao_user_id = implode(',',$vv);
                    $res = $this->databus->addToutiaoAcc($group_id, $toutiao_user_id);
                    if($res['success'] == true){
                        $data = isset($res['data']['success']) ? $res['data']['success'] : [];
                        //$success += count($data);
                        if($data){
                            foreach ($data as $acc) {
                                $return = $this->addToutiaoAccOne($acc,$company_id,$info->company_index,$success,$isHas,$successList);
                                $success = $return['success'];
                                $isHas = $return['isHas'];
                                $successList = $return['success_name'];
                            }
                        }
                    }
                }elseif($type == 2){
                    //+ 根据链接添加，每次只能添加一个链接
                    foreach ($vv as $toutiao_url) {
                        $res = $this->databus->addToutiaoUrl($group_id, $toutiao_url);
                        if($res['success'] == true && isset($res['data']['success']) && $res['data']['success']){
                            //查询头条账号信息
                            $t_user_id = $res['data']['success']['user_id'];
                            $tt_info = $this->databus->getTtInfo($t_user_id);
                            if (isset($tt_info['data']['info'])) {
                                $return = $this->addToutiaoAccOne([
                                    'toutiao_user_id' => $t_user_id,
                                    'nickname' => $tt_info['data']['info']['nickname'],
                                ],$company_id,$info->company_index,$success,$isHas,$successList);
                                $success = $return['success'];
                                $isHas = $return['isHas'];
                                $successList = $return['success_name'];
                            }else {
                                //采集组还没有数据等待稍后脚本添加
                                $accWait = new AccountAddWait();
                                $accWait->company_id = strval($company_id);
                                $accWait->company_index = $info->company_index;
                                $accWait->rid = strval($t_user_id);
                                $accWait->type = 2;
                                $accWait->c_time = $now_date;
                                $accWait->save();
                                $err_msg[$toutiao_url] = '不在监测库中，24小时后显示在榜单中';
                                $success ++;
                            }
                        }else {
                            $err_msg[$toutiao_url] = $res['data']['message'];
                        }
                    }
                }else{
                    return false;
                }

            }
            $fail = $all - $success;

            //+ 系统日志
            if($success>0){
                $wxNames = implode(',',$successList);
                $content = '头条号:'.$wxNames.'已被增到新媒体账号管理系统数据库中。';
                \Yii::$app->service->LogService->saveSysLog($content);
            }

            return [
                'success'=> $success,
                'fail'=> $fail,
                'isHas'=> $isHas,
                'err_msg'=> isset($err_msg) ? $err_msg : [],
            ];
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 添加一个头条账号到榜单
     * @param $acc
     * @param $company_id
     * @param $company_index
     * @param $success
     * @param $isHas
     * @param $successList
     * @return array
     */
    public function addToutiaoAccOne($acc,$company_id,$company_index,$success = 0,$isHas = 0,$successList = [])
    {
        //+++ 查询账号表中是否已有该账号
        $accHas = AccountToutiaoAll::findOne(['toutiao_user_id'=>$acc['toutiao_user_id']]);
        if(empty($accHas)){
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                $accHas = new AccountToutiaoAll();
                $accHas->toutiao_user_id = $acc['toutiao_user_id'];
                $accHas->nickname = isset($acc['nickname']) ? $acc['nickname'] : '';
                $accHas->company_ids = strval($company_id);
                $accHas->comapny_index = $company_index;
                $res = $accHas->save();
                if($res === false){
                    //$failList[] = $acc['toutiao_user_id'];
                    $transaction->rollBack();
                    return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                }

                //+③ 往公司账号关联表中添加账号
                $compAcc = new CompanyToutiao();
                $compAcc->company_id = $company_id;
                $compAcc->company_index = $company_index;
                $compAcc->toutiao_user_id = $acc['toutiao_user_id'];
                $compAcc->account_id = $acc['toutiao_user_id'];
                $res = $compAcc->save();
                if ($res === false) {
                    //$failList[] = $acc['toutiao_user_id'];
                    $transaction->rollBack();
                    return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                }
                $transaction->commit();
                $success += 1;
                $successList[] = isset($acc['nickname']) ? $acc['nickname'] : '';
                return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
            } catch (\Exception $e) {
                $transaction->rollBack();
                return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
            }
        }else{
            //++++ 判断该账号该公司是否已经添加
            if(in_array($company_id, explode(',',$accHas->company_ids))){
                $isHas += 1;
                return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
            }else {
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    //++++ 账号没有添加，则追加公司信息
                    $company_ids1 = $accHas->company_ids ? $accHas->company_ids.','.$company_id : $company_id;
                    $company_index1 = $accHas->comapny_index ? $accHas->comapny_index . ',' . $company_index : $company_index;
                    $accHas->company_ids = $company_ids1;
                    $accHas->comapny_index = $company_index1;
                    $accHas->staus = 0;
                    $res = $accHas->save();
                    if($res === false){
                        //$failList[] = $acc['toutiao_user_id'];
                        $transaction->rollBack();
                        return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                    }

                    //+③ 往公司账号关联表中添加账号
                    $compAcc = new CompanyToutiao();
                    $compAcc->company_id = $company_id;
                    $compAcc->company_index = $company_index;
                    $compAcc->toutiao_user_id = $acc['toutiao_user_id'];
                    $compAcc->account_id = $acc['toutiao_user_id'];
                    $res = $compAcc->save();
                    if ($res === false) {
                        //$failList[] = $acc['toutiao_user_id'];
                        $transaction->rollBack();
                        return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                    }
                    $transaction->commit();
                    $success += 1;
                    $successList[] = isset($acc['nickname']) ? $acc['nickname'] : '';
                    return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                }
            }
        }
    }

    //---------------音账号-------------
    /**
     * 添加抖音账号到榜单
     * @param $uid
     * @param $company_id
     * @param $type
     * @param $account
     * @return array|bool|void
     */
    public function addDouyinAccount($uid,$company_id,$type,$account)
    {
        //+ 公司信息
        $info = Company::find()->where(['id' => $company_id])->one();
        if(empty($info)){
            return jsonErrorReturn('companyError');
        }

        //+① 判断该公司是否创建分组，若没有创建则先创建分组
        $groupInfo = CompanyRankGroup::findOne(['company_id'=>$company_id]);
        $group_id = 0;
        if(!empty($groupInfo)){
            $group_id = $groupInfo->douyin_group_id;
        }
        //+++ 不存在分组，则创建
        if($group_id == 0){
            //+ 新建分组
            $group_name = $info->company_name.'抖音分组';
            $res = $this->databus->addDouyinGroup($group_name);
            if($res['success'] == true){
                $group_id = isset($res['data']['group']['id']) ? $res['data']['group']['id'] : 0;
                if($group_id == 0){
                    //+++ 分组创建失败，直接返回错误
                    return jsonErrorReturn('rankAddError');
                }
                if(empty($groupInfo)){
                    $groupInfo = new CompanyRankGroup();
                    $groupInfo->company_id = $company_id;
                    $groupInfo->company_index = $info->company_index;
                }
                $groupInfo->douyin_group_id = $group_id;
                $res = $groupInfo->save();
                if($res === false){
                    //+++ 分组创建失败，直接返回错误
                    return jsonErrorReturn('rankAddError');
                }
            }else{
                //+++ 分组创建失败，直接返回错误
                return jsonErrorReturn('rankAddError');
            }
        }

        // 校验账号数量是否达到上限
        $config_num = $info->media_create_num;
        $account_num = CompanyToutiao::find()->where(['company_id' => $company_id])->count();
        if ($account_num >= $config_num) return jsonErrorReturn('accountNumError');

        try {
            //+② 往账号表中添加账号，若账号表中有该账号，则更新company_ids,company_index
            $all = count($account);//需要添加的条数
            $success = 0;//成功条数
            $fail = 0;//失败条数
            $isHas = 0;//失败中当前已有的条数
            $successList = [];
            $failList = [];

            //+++ 由于开放平台接口每次最多添加10个账号，所以按每10个一组进行添加
            $accArr = array_chunk($account, 10);
            foreach ($accArr as $vv) {
                if($type == 1){
                    $douyin_id = implode(',',$vv);
                    $res = $this->databus->addDouyinAcc($group_id, $douyin_id);

                    if($res['success'] == true){
                        $data = isset($res['data']['success']) ? $res['data']['success'] : [];
                        //$success += count($data);
                        if($data){
                            foreach ($data as $acc) {
                                $return = $this->addDouyinAccOne($acc,$company_id,$info->company_index,$success,$isHas,$successList);
                                $success = $return['success'];
                                $isHas = $return['isHas'];
                                $successList = $return['success_name'];
                            }
                        }
                        if (isset($res['data']['failure'])) {
                            $failList = array_merge($failList, $res['data']['failure']);
                        }
                    }
                }elseif($type == 2){
                    //+ 根据链接添加，每次只能添加一个链接
                    foreach ($vv as $douyin_url) {
                        $res = $this->databus->addDouyinUrl($group_id, $douyin_url);

                        if($res['success'] == true && isset($res['data']['success']) && $res['data']['success']){
                            $data = $res['data']['success'];
                            //$success += count($data);
                            if($data){
                                $return = $this->addDouyinAccOne($data,$company_id,$info->company_index,$success,$isHas,$successList);
                                $success = $return['success'];
                                $isHas = $return['isHas'];
                                $successList = $return['success_name'];
                            }
                        }else{
                            $err_msg[$douyin_url] = $res['data']['message'];
                        }
                    }
                }else{
                    return false;
                }

            }
            $fail = $all - $success;

            //+ 系统日志
            if($success>0){
                $wxNames = implode(',',$successList);
                $content = '抖音号:'.$wxNames.'已被增到新媒体账号管理系统数据库中。';
                \Yii::$app->service->LogService->saveSysLog($content);
            }

            return [
                'success'=> $success,
                'fail'=> $fail,
                'isHas'=> $isHas,
                'err_msg'=> isset($err_msg) ? $err_msg : [],
                'failList'=> $failList,
            ];
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 添加一个抖音账号到榜单
     * @param $acc
     * @param $company_id
     * @param $company_index
     * @param $success
     * @param $isHas
     * @param $successList
     * @return array
     */
    private function addDouyinAccOne($acc,$company_id,$company_index,$success,$isHas,$successList)
    {
        //+++ 查询账号表中是否已有该账号
        $accHas = AccountDouyinAll::findOne(['douyin_id'=>$acc['douyin_id']]);
        if(empty($accHas)){
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                $accHas = new AccountDouyinAll();
                $accHas->douyin_id = $acc['douyin_id'];
                $accHas->douyin_name = isset($acc['douyin_name']) ? $acc['douyin_name'] : '';
                $accHas->author_img = isset($acc['douyin_img']) ? $acc['douyin_img'] : '';
                $accHas->douyin_code = isset($acc['douyin_code']) ? $acc['douyin_code'] : '';
                $accHas->company_ids = strval($company_id);
                $accHas->comapny_index = $company_index;
                $res = $accHas->save();
                if($res === false){
                    //$failList[] = $acc['douyin_id'];
                    $transaction->rollBack();
                    return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                }

                //+③ 往公司账号关联表中添加账号
                $compAcc = new CompanyDouyin();
                $compAcc->company_id = $company_id;
                $compAcc->company_index = $company_index;
                $compAcc->douyin_id = $acc['douyin_id'];
                $compAcc->account_id = $acc['douyin_id'];
                $res = $compAcc->save();
                if ($res === false) {
                    //$failList[] = $acc['douyin_id'];
                    $transaction->rollBack();
                    return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                }
                $transaction->commit();
                $success += 1;
                $successList[] = isset($acc['douyin_name']) ? $acc['douyin_name'] : '';
                return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
            } catch (\Exception $e) {
                $transaction->rollBack();
                return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
            }
        }else{
            //++++ 判断该账号该公司是否已经添加
            if(in_array($company_id, explode(',',$accHas->company_ids))){
                $isHas += 1;
                return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
            }else {
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    //++++ 账号没有添加，则追加公司信息
                    $company_ids1 = $accHas->company_ids ? $accHas->company_ids.','.$company_id : $company_id;
                    $company_index1 = $accHas->comapny_index ? $accHas->comapny_index . ',' . $company_index : $company_index;
                    $accHas->company_ids = $company_ids1;
                    $accHas->comapny_index = $company_index1;
                    $accHas->staus = 0;
                    $res = $accHas->save();
                    if($res === false){
                        //$failList[] = $acc['douyin_id'];
                        $transaction->rollBack();
                        return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                    }

                    //+③ 往公司账号关联表中添加账号
                    $compAcc = new CompanyDouyin();
                    $compAcc->company_id = $company_id;
                    $compAcc->company_index = $company_index;
                    $compAcc->douyin_id = $acc['douyin_id'];
                    $compAcc->account_id = $acc['douyin_id'];
                    $res = $compAcc->save();
                    if ($res === false) {
                        //$failList[] = $acc['douyin_id'];
                        $transaction->rollBack();
                        return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                    }
                    $transaction->commit();
                    $success += 1;
                    $successList[] = isset($acc['douyin_name']) ? $acc['douyin_name'] : '';
                    return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                }
            }
        }
    }

    //---------------快手账号-------------
    /**
     * 添加快手账号到榜单
     * @param $uid
     * @param $company_id
     * @param $type
     * @param $account
     * @return array|bool|void
     */
    public function addKuaishouAccount($uid,$company_id,$type,$account)
    {
        //+ 公司信息
        $info = Company::find()->where(['id' => $company_id])->one();
        if(empty($info)){
            return jsonErrorReturn('companyError');
        }

        //+① 判断该公司是否创建分组，若没有创建则先创建分组
        $groupInfo = CompanyRankGroup::findOne(['company_id'=>$company_id]);
        $group_id = 0;
        if(!empty($groupInfo)){
            $group_id = $groupInfo->kuaishou_group_id;
        }
        //+++ 不存在分组，则创建
        if($group_id == 0){
            //+ 新建分组
            $group_name = $info->company_name.'快手分组';
            $res = $this->databus->addKuaishouGroup($group_name);
            if($res['success'] == true){
                $group_id = isset($res['data']['group']['id']) ? $res['data']['group']['id'] : 0;
                if($group_id == 0){
                    //+++ 分组创建失败，直接返回错误
                    return jsonErrorReturn('rankAddError');
                }
                if(empty($groupInfo)){
                    $groupInfo = new CompanyRankGroup();
                    $groupInfo->company_id = $company_id;
                    $groupInfo->company_index = $info->company_index;
                }
                $groupInfo->kuaishou_group_id = $group_id;
                $res = $groupInfo->save();
                if($res === false){
                    //+++ 分组创建失败，直接返回错误
                    return jsonErrorReturn('rankAddError');
                }
            }else{
                //+++ 分组创建失败，直接返回错误
                return jsonErrorReturn('rankAddError');
            }
        }

        // 校验账号数量是否达到上限
        $config_num = $info->media_create_num;
        $account_num = CompanyToutiao::find()->where(['company_id' => $company_id])->count();
        if ($account_num >= $config_num) return jsonErrorReturn('accountNumError');

        try {
            //+② 往账号表中添加账号，若账号表中有该账号，则更新company_ids,company_index
            $all = count($account);//需要添加的条数
            $success = 0;//成功条数
            $fail = 0;//失败条数
            $isHas = 0;//失败中当前已有的条数
            $successList = [];
            $failList = [];

            //+++ 由于开放平台接口每次最多添加10个账号，所以按每10个一组进行添加
            $accArr = array_chunk($account, 10);
            foreach ($accArr as $vv) {
                if($type == 1){
                    $kuaishou_id = implode(',',$vv);
                    $res = $this->databus->addKuaishouAcc($group_id, $kuaishou_id);

                    if($res['success'] == true){
                        $data = isset($res['data']['success']) ? $res['data']['success'] : [];
                        //$success += count($data);
                        if($data){
                            foreach ($data as $acc) {
                                $return = $this->addKuaishouAccOne($acc,$company_id,$info->company_index,$success,$isHas,$successList);
                                $success = $return['success'];
                                $isHas = $return['isHas'];
                                $successList = $return['success_name'];
                            }
                        }
                        //$failList = array_merge($failList, $res['data']['failure']);
                    }
                }elseif($type == 2){
                    //+ 根据链接添加，每次只能添加一个链接
                    foreach ($vv as $kuaishou_url) {
                        $res = $this->databus->addKuaishouUrl($group_id, $kuaishou_url);

                        if($res['success'] == true && isset($res['data']['success']) && $res['data']['success']){
                            $data = $res['data']['success'];
                            //$success += count($data);
                            if($data){
                                $return = $this->addKuaishouAccOne($data,$company_id,$info->company_index,$success,$isHas,$successList);
                                $success = $return['success'];
                                $isHas = $return['isHas'];
                                $successList = $return['success_name'];
                            }
                        }else{
                            $err_msg[$kuaishou_url] = $res['data']['message'];
                        }
                    }
                }else{
                    return false;
                }

            }
            $fail = $all - $success;

            //+ 系统日志
            if($success>0){
                $wxNames = implode(',',$successList);
                $content = '快手号:'.$wxNames.'已被增到新媒体账号管理系统数据库中。';
                \Yii::$app->service->LogService->saveSysLog($content);
            }

            return [
                'success'=> $success,
                'fail'=> $fail,
                'isHas'=> $isHas,
                'err_msg'=> isset($err_msg) ? $err_msg : [],
            ];
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 添加一个快手账号到榜单
     * @param $acc
     * @param $company_id
     * @param $company_index
     * @param $success
     * @param $isHas
     * @param $successList
     * @return array
     */
    private function addKuaishouAccOne($acc,$company_id,$company_index,$success,$isHas,$successList)
    {
        //+++ 查询账号表中是否已有该账号
        $accHas = AccountKuaishouAll::findOne(['kuaishou_id'=>$acc['kuaishou_id']]);
        if(empty($accHas)){
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                $accHas = new AccountKuaishouAll();
                $accHas->kuaishou_id = $acc['kuaishou_id'];
                $accHas->kuaishou_name = isset($acc['kuaishou_name']) ? $acc['kuaishou_name'] : '';
                $accHas->kuaishou_code = isset($acc['kuaishou_code']) ? $acc['kuaishou_code'] : '';
                $accHas->author_img = isset($acc['kuaishou_img']) ? $acc['kuaishou_img'] : '';
                $accHas->company_ids = strval($company_id);
                $accHas->comapny_index = $company_index;
                $res = $accHas->save();
                if($res === false){
                    //$failList[] = $acc['kuaishou_id'];
                    $transaction->rollBack();
                    return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                }

                //+③ 往公司账号关联表中添加账号
                $compAcc = new CompanyKuaishou();
                $compAcc->company_id = $company_id;
                $compAcc->company_index = $company_index;
                $compAcc->kuaishou_id = $acc['kuaishou_id'];
                $compAcc->account_id = $acc['kuaishou_id'];
                $res = $compAcc->save();
                if ($res === false) {
                    //$failList[] = $acc['kuaishou_id'];
                    $transaction->rollBack();
                    return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                }
                $transaction->commit();
                $success += 1;
                $successList[] = isset($acc['kuaishou_name']) ? $acc['kuaishou_name'] : '';
                return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
            } catch (\Exception $e) {
                $transaction->rollBack();
                return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
            }
        }else{
            //++++ 判断该账号该公司是否已经添加
            if(in_array($company_id, explode(',',$accHas->company_ids))){
                $isHas += 1;
                return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
            }else {
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    //++++ 账号没有添加，则追加公司信息
                    $company_ids1 = $accHas->company_ids ? $accHas->company_ids.','.$company_id : $company_id;
                    $company_index1 = $accHas->comapny_index ? $accHas->comapny_index . ',' . $company_index : $company_index;
                    $accHas->company_ids = $company_ids1;
                    $accHas->comapny_index = $company_index1;
                    $accHas->staus = 0;
                    $res = $accHas->save();
                    if($res === false){
                        //$failList[] = $acc['kuaishou_id'];
                        $transaction->rollBack();
                        return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                    }

                    //+③ 往公司账号关联表中添加账号
                    $compAcc = new CompanyKuaishou();
                    $compAcc->company_id = $company_id;
                    $compAcc->company_index = $company_index;
                    $compAcc->kuaishou_id = $acc['kuaishou_id'];
                    $compAcc->account_id = $acc['kuaishou_id'];
                    $res = $compAcc->save();
                    if ($res === false) {
                        //$failList[] = $acc['kuaishou_id'];
                        $transaction->rollBack();
                        return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                    }
                    $transaction->commit();
                    $success += 1;
                    $successList[] = isset($acc['kuaishou_name']) ? $acc['kuaishou_name'] : '';
                    return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                }
            }
        }
    }

    /**
     * 添加web账号
     * @param $uid
     * @param $company_id
     * @param $account
     * @return array|bool|void
     */
    public function addWebAccount($uid,$company_id,$account)
    {
        //+ 公司信息
        $info = Company::find()->where(['id' => $company_id])->one();
        if(empty($info)){
            return jsonErrorReturn('companyError');
        }

        // 校验账号数量是否达到上限
        $config_num = $info->web_create_num;
        $account_num = CompanyWeb::find()->where(['company_id' => $company_id])->count();
        if ($account_num >= $config_num) return jsonErrorReturn('accountNumError');

        try {
            //+② 往账号表中添加账号，若账号表中有该账号，则更新company_ids,company_index
            $all = count($account);//需要添加的条数
            $success = 0;//成功条数
            $isHas = 0;//失败中当前已有的条数
            $successList = [];
            $failList = [];

            //+ 根据链接添加，每次只能添加一个链接
            foreach ($account as $web_url) {
                $domain_info = $this->getDomainArr($web_url);
                $return = $this->addWebAccOne($domain_info, $company_id, $info->company_index, $success, $isHas, $successList);
                $success = $return['success'];
                $isHas = $return['isHas'];
                $successList = $return['success_name'];
            }
            $fail = $all - $success;

            //+ 系统日志
            if($success>0){
                $domainNames = implode(',',$successList);
                $content = '网站域名:'.$domainNames.'已被增到新媒体账号管理系统数据库中。';
                \Yii::$app->service->LogService->saveSysLog($content);
            }

            return [
                'success'=> $success,
                'fail'=> $fail,
                'isHas'=> $isHas,
                'err_msg'=> isset($err_msg) ? $err_msg : [],
            ];
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 添加一个网站域名到数据库
     * @param $acc
     * @param $company_id
     * @param $company_index
     * @param $success
     * @param $isHas
     * @param $successList
     * @return array
     */
    public function addWebAccOne($acc,$company_id,$company_index,$success,$isHas,$successList)
    {
        // 1.校验链接是否已经解析 没有解析的状态默认为待解析 解析过的状态为已解析
        // 2.写入 account_web_all 表 已解析的链接再写入 company_web
        //+++ 查询账号表中是否已有该账号
        $accHas = AccountWebAll::findOne(['domain_sec'=>$acc['domain_sec']]);
        $exist_info = GsdataSiteDomainNew::findOne(['domain_sec' => $acc['domain_sec']]);
        if(empty($accHas)){
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                $accHas = new AccountWebAll();
                $accHas->domain_sec = isset($acc['domain_sec']) ? $acc['domain_sec'] : '';
                $accHas->domain_pri = isset($acc['domain_pri']) ? $acc['domain_pri'] : '';
                $accHas->web_url = isset($acc['web_url']) ? $acc['web_url'] : '';
                $accHas->web_name = !empty($exist_info) ? $exist_info->app_name : '';
                $accHas->company_ids = strval($company_id);
                $accHas->comapny_index = $company_index;
                if (empty($exist_info)) {
                    $accHas->staus = 2;
                    $accHas->comment = '解析中，需5到7个工作日';
                }
                $res = $accHas->save();
                if($res === false){
                    $transaction->rollBack();
                    return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                }

                //+③ 往公司账号关联表中添加账号 域名已解析
                if (!empty($exist_info)) {
                    $compAcc = new CompanyWeb();
                    $compAcc->company_id = $company_id;
                    $compAcc->company_index = $company_index;
                    $compAcc->domain = isset($acc['domain_sec']) ? $acc['domain_sec'] : '';
                    $compAcc->account_id = isset($acc['domain_sec']) ? $acc['domain_sec'] : '';
                    $res = $compAcc->save();
                    if ($res === false) {
                        $transaction->rollBack();
                        return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                    }
                }
                $transaction->commit();
                $success += 1;
                $successList[] = isset($acc['domain_sec']) ? $acc['domain_sec'] : '';
                return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
            } catch (\Exception $e) {
                $transaction->rollBack();
                return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
            }
        }else{
            //++++ 判断该账号该公司是否已经添加
            if(in_array($company_id, explode(',',$accHas->company_ids))){
                $isHas += 1;
                return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
            }else {
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    //++++ 账号没有添加，则追加公司信息
                    $company_ids1 = $accHas->company_ids ? $accHas->company_ids.','.$company_id : $company_id;
                    $company_index1 = $accHas->comapny_index ? $accHas->comapny_index . ',' . $company_index : $company_index;
                    $accHas->company_ids = $company_ids1;
                    $accHas->comapny_index = $company_index1;
                    $accHas->staus = 0;
                    $res = $accHas->save();
                    if($res === false){
                        $transaction->rollBack();
                        return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                    }

                    //+③ 往公司账号关联表中添加账号
                    if (!empty($exist_info)) {
                        $compAcc = new CompanyWeb();
                        $compAcc->company_id = $company_id;
                        $compAcc->company_index = $company_index;
                        $compAcc->domain = $accHas->domain_sec;
                        $compAcc->account_id = $accHas->domain_sec;
                        $res = $compAcc->save();
                        if ($res === false) {
                            $transaction->rollBack();
                            return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                        }
                    }
                    $transaction->commit();
                    $success += 1;
                    $successList[] = isset($acc['domain_sec']) ? $acc['domain_sec'] : '';
                    return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    return ['success'=> $success,'isHas'=> $isHas,'success_name'=>$successList];
                }
            }
        }
    }

    //+-----------------------从榜单删除账号--------------------------

    public function delAccount($uid,$company_id,$type,$account_id)
    {
        //+ 公司信息
        $info = Company::find()->where(['id' => $company_id])->one();
        if(empty($info)){
            return jsonErrorReturn('companyError');
        }

        $services = new PageAuthService();
        $tag = $services->getTagList();
        //+ 判断该用户是否有权限
        $is_auth = 0;
        if(!empty($tag['media_tag'])){
            foreach ($tag['media_tag'] as $m) {
                if($m['e_name'] == $type){
                    $is_auth = 1;
                }
            }
        }
        if($is_auth == 0){
            return jsonErrorReturn('authorityError');
        }

        //+ 获取该公司所在榜单分组ID
        $groupArr = $this->getGroupArr($company_id);
        if(empty($groupArr)){
            return jsonErrorReturn('rankGroupError');
        }

        $accountName = '';

        $group_id = 0;
        $web_flag = false;  // 网站标识 兼容未解析的域名 company_web中没数据
        switch ($type) {
            case 'wx':
                $compAcc = CompanyWx::findOne(['company_id'=>$company_id,'nickname_id'=>$account_id]);
                $acc = AccountWxAll::findOne(['nickname_id'=>$account_id]);
                $group_id = $groupArr->wx_group_id;
                $accountName = $acc->wx_nickname;
                break;
            case 'weibo':
                $compAcc = CompanyWeibo::findOne(['company_id'=>$company_id,'weibo_uid'=>$account_id]);
                $acc = AccountWeiboAll::findOne(['weibo_uid'=>$account_id]);
                $group_id = $groupArr->weibo_group_id;
                $accountName = $acc->nickname;
                break;
            case 'media_toutiao':
                $compAcc = CompanyToutiao::findOne(['company_id'=>$company_id,'toutiao_user_id'=>$account_id]);
                $acc = AccountToutiaoAll::findOne(['toutiao_user_id'=>$account_id]);
                $group_id = $groupArr->toutiao_group_id;
                $accountName = $acc->nickname;
                break;
            case 'douyin':
                $compAcc = CompanyDouyin::findOne(['company_id'=>$company_id,'douyin_id'=>$account_id]);
                $acc = AccountDouyinAll::findOne(['douyin_id'=>$account_id]);
                $group_id = $groupArr->douyin_group_id;
                $accountName = $acc->douyin_name;
                break;
            case 'kuaishou':
                $compAcc = CompanyKuaishou::findOne(['company_id'=>$company_id,'kuaishou_id'=>$account_id]);
                $acc = AccountKuaishouAll::findOne(['kuaishou_id'=>$account_id]);
                $group_id = $groupArr->kuaishou_group_id;
                $accountName = $acc->kuaishou_name;
                break;
            case 'web':
                $compAcc = CompanyWeb::findOne(['company_id'=>$company_id,'domain'=>$account_id]);
                $acc = AccountWebAll::findOne(['domain_sec'=>$account_id]);
                if (!empty($acc->id)) $web_flag = true;
                $accountName = $account_id;
                break;
            default:
                $compAcc = null;
                $acc = null;
                break;
        }

        //+ 判断该公司是否存在该账号
        if(is_null($compAcc) || is_null($acc) || empty($compAcc) || empty($acc)){
            if (empty($web_flag)) return jsonErrorReturn('accountError');
        }

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            //+ ①删除公司关联榜单表中账号
            if (!empty($compAcc)) {
                $res = $compAcc->delete();
                if ($res === false) {
                    $transaction->rollBack();
                    return false;
                }
            }

            //+ ②更新账号总表中公司信息，将账号总表中公司信息删除
            $company_ids = explode(',', $acc->company_ids);
            $key1 = array_search($company_id, $company_ids);
            if($key1 !== false){
                array_splice($company_ids,$key1,1);
            }
            $company_ids = $company_ids ? implode(',', $company_ids) : '';

            $company_indexs = explode(',', $acc->comapny_index);
            $key2 = array_search($info->company_index, $company_indexs);
            if($key2 !== false){
                array_splice($company_indexs,$key2,1);
            }
            $company_indexs = $company_indexs ? implode(',', $company_indexs) : '';

            $acc->company_ids = $company_ids;
            $acc->comapny_index = $company_indexs;
            if(empty($company_ids)){
                $acc->staus = 1;
            }
            $res = $acc->save();
            if ($res === false) {
                $transaction->rollBack();
                return false;
            }

            //+ ③将榜单中账号删除
            if($group_id>0){
                //执行时间长故放入队列
//                Yii::$app->redis->rpush(RedisKeyService::getKey(1005), json_encode(['type' => $type, 'group_id' => $group_id, 'account_id' => $account_id]));

                $typeName = '';
                switch ($type) {
                    case 'wx':
                        $res = $this->databus->delWeixinAcc($group_id, $account_id);
                        $typeName = '微信号';
                        break;
                    case 'weibo':
                        $res = $this->databus->delWeiboAcc($group_id, $account_id);
                        $typeName = '微博号';
                        break;
                    case 'media_toutiao':
                        $res = $this->databus->delToutiaoAcc($group_id, $account_id);
                        $typeName = '头条号';
                        break;
                    case 'douyin':
                        $res = $this->databus->delDouyinAcc($group_id, $account_id);
                        $typeName = '抖音号';
                        break;
                    case 'kuaishou':
                        $res = $this->databus->delKuaishouAcc($group_id, $account_id);
                        $typeName = '快手号';
                        break;
                    default:
                        $res = null;
                        break;
                }

                if(isset($res['data']['result']) && $res['data']['result'] == 'ok'){
                    //+ 系统日志
                    $content = $typeName.':'.$accountName.'已被用户从新媒体账号管理系统数据库中删除。';
                    \Yii::$app->service->LogService->saveSysLog($content);

                    $transaction->commit();
                    return true;
                }else{
                    $transaction->rollBack();
                    return false;
                }
            }else{
                if ($type == 'web') {
                    $transaction->commit();
                    return true;
                }
                $transaction->rollBack();
                return false;
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            return false;
        }
    }

    /**
     * 获取该公司分组id
     * @param $company_id
     * @return array|CompanyRankGroup|null
     */
    private function getGroupArr($company_id)
    {
        $groupArr = CompanyRankGroup::findOne(['company_id'=>$company_id]);
        if(empty($groupArr)){
            $groupArr = [];
        }
        return $groupArr;
    }

    /**
     * @info 根据链接获取一级域名、二级域名
     * @param string $url_str
     * @return array
     */
    private function getDomainArr($url_str = '')
    {
        if (empty($url_str)) return [];
        $url_str = strtolower($url_str);
        $hosts = parse_url($url_str);
        $web_url = $hosts['scheme'] . "://" . $hosts['host'];
        $host = $hosts['host'];
        $data = explode('.', $host);
        if ($data[0] == 'www') array_shift($data);
        $count = count($data);

        $suffix_arr = [ "com", "co", "net", "gov", "org", "edu", "cc", "cn",
            "sh", "hk", "or", "biz", "club", "top", "vip", "wang", "ltd", "group", "info", "mobi", "ac","ah","bj",
            "cq","fj","gd","gs","gx","gz","ha","hb","he","hi","hk","hl","hn","jl","js","my", "ph", "sg", "au", "nz",
            "jx","ln","mo","nm","nx","qh","sc","sd","sh","sn","sx","tj","tw","xj","xz","yn","zj","aa","arts","za",
            "firm","nom","rec","store", "pk", "br", "ke", "gob", "go", "pna", "rep", "fgv", "re"];

        // 是否纯ip地址
        if (preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $host)) return ['domain_pri' => $host, 'domain_sec' => $host, 'web_url' => $web_url];
        if ($count <= 2) return ['domain_pri' => implode('.', $data), 'domain_sec' => implode('.', $data), 'web_url' => $web_url];

        $index = 2;
        $check = $data[$count - 2];
        foreach ($suffix_arr as $v) {
            if ($v == $check) {
                $index = 3;
                break;
            }
        }

        $new_host = [];
        for ($i = $count - $index; $i < $count; $i++) {
            array_push($new_host, $data[$i]);
        }
        $domain_pri = implode('.', $new_host);
        $domain_sec = $count > count($new_host) ? $data[$count - $index -1] . "." . $domain_pri : $domain_pri;

        return compact("domain_pri", "domain_sec", "web_url");
    }
}