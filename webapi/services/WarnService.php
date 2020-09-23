<?php

namespace webapi\services;

use Matrix\Exception;
use webapi\extensions\ShudiEsBase;
use Yii;
use yii\helpers\ArrayHelper;
use webapi\models\WarningConfig;
use webapi\models\WarningConfigRecode;
use webapi\models\WarningRecode;
use webapi\models\WarningUser;

class WarnService
{


    /**
     * 预警开关
     * @param $warningConfig
     * @return array
     */
    public function warnSwitch($warningConfig)
    {
        $warningConfig->company_id = CompanyService::$company_id;
        if(empty($warningConfig->weeks)){
            $warningConfig->weeks = json_encode([1,2,3,4,5]);
        }
        if(empty($warningConfig->sentiment)){
            $warningConfig->sentiment = json_encode(['负面'],JSON_UNESCAPED_UNICODE);
        }
        if(empty($warningConfig->media)){
            $warningConfig->media = json_encode(['total'],JSON_UNESCAPED_UNICODE);
        }
        if(empty($warningConfig->time)){
            $timeSteps = $this->timeStep(warningConfig::DEFAULT_BEGIN_TIME,warningConfig::DEFAULT_END_TIME,warningConfig::DEFAULT_FREQUENCY);
            $time = [
                'begin_time' => warningConfig::DEFAULT_BEGIN_TIME,
                'end_time' => warningConfig::DEFAULT_END_TIME,
                'frequency_set' => warningConfig::DEFAULT_FREQUENCY,
                'time_step' => $timeSteps
            ];
            $warningConfig->time = json_encode($time,JSON_UNESCAPED_UNICODE);
        }
        if($warningConfig->save(false)){
            $record = $warningConfig->status == warningConfig::SWITCH_ON?'开启成功':'关闭成功';
            warningConfigRecode::addRecord($record);
            return ['status' => true,'msg' => '预警提醒开启成功'];
        }else{
            $record = $warningConfig->status == warningConfig::SWITCH_ON?'开启失败':'关闭失败';
            warningConfigRecode::addRecord($record);
            return ['status' => false,'msg' => '系统错误'];
        }
        $content = '内容预警:预警'.$record;
        \Yii::$app->service->LogService->saveSysLog($content);
    }

    /**
     * 舆情预警开始结束日期设置
     * @param $warningConfig
     */
    public function warnBeginEndDate($warningConfig)
    {
        $warningConfig->user_id = UserService::$uid;
        $warningConfig->company_id = CompanyService::$company_id;
        if(empty($warningConfig->weeks)){
            $warningConfig->weeks = json_encode([1,2,3,4,5]);
        }
        if(empty($warningConfig->sentiment)){
            $warningConfig->sentiment = json_encode(['负面'],JSON_UNESCAPED_UNICODE);
        }
        if(empty($warningConfig->media)){
            $warningConfig->media = json_encode(['total'],JSON_UNESCAPED_UNICODE);
        }
        $timeSteps = $this->timeStep(warningConfig::DEFAULT_BEGIN_TIME,warningConfig::DEFAULT_END_TIME,warningConfig::DEFAULT_FREQUENCY);
        $time = [
            'begin_time' => warningConfig::DEFAULT_BEGIN_TIME,
            'end_time' => warningConfig::DEFAULT_END_TIME,
            'frequency_set' => warningConfig::DEFAULT_FREQUENCY,
            'time_step' => $timeSteps
        ];
        $warningConfig->time = json_encode($time,JSON_UNESCAPED_UNICODE);
        if($warningConfig->save(false)){
            jsonSuccessReturn([],'舆情预警日期设置成功');
        }else{
            jsonErrorReturn('systemError');
        }
    }

    /**
     * 舆情预警主要设置
     * @param $warningConfig
     * @return array
     */
    public function warnInfoSet($warningConfig)
    {
        $warningConfig->company_id = CompanyService::$company_id;
        $warningConfig->keywords = !empty($warningConfig->keywords)?json_encode($warningConfig->keywords,JSON_UNESCAPED_UNICODE):'';
        if(empty($warningConfig->process_id)){
            $warningConfig->process_id = rand(1,Yii::$app->params['warnProcess']);
        }
        if(empty($warningConfig->weeks)){
            $warningConfig->weeks = json_encode([1,2,3,4,5]);
        }else{
            $warningConfig->weeks = json_encode(array_values(array_map(function($v){return intval($v);},$warningConfig->weeks)),JSON_UNESCAPED_UNICODE);
        }
        if(empty($warningConfig->sentiment)){
            $warningConfig->sentiment = json_encode(['负面'],JSON_UNESCAPED_UNICODE);
        }else{
            $warningConfig->sentiment = json_encode(array_values($warningConfig->sentiment),JSON_UNESCAPED_UNICODE);
        }
        if(empty($warningConfig->media)){
            $warningConfig->media = json_encode(['total'],JSON_UNESCAPED_UNICODE);
        }else{
            $warningConfig->media = json_encode(array_values($warningConfig->media),JSON_UNESCAPED_UNICODE);
        }
        if(empty($warningConfig->time)) {
            $timeSteps = $this->timeStep(warningConfig::DEFAULT_BEGIN_TIME, warningConfig::DEFAULT_END_TIME, warningConfig::DEFAULT_FREQUENCY);
            $time = [
                'begin_time' => warningConfig::DEFAULT_BEGIN_TIME,
                'end_time' => warningConfig::DEFAULT_END_TIME,
                'frequency_set' => warningConfig::DEFAULT_FREQUENCY,
                'time_step' => $timeSteps
            ];
            $warningConfig->time = json_encode($time, JSON_UNESCAPED_UNICODE);
        }else{
            $timeSteps = $this->timeStep($warningConfig->begin_time,$warningConfig->end_time,$warningConfig->frequency);
            $time = [
                'begin_time' => $warningConfig->begin_time,
                'end_time' => $warningConfig->end_time,
                'frequency_set' => $warningConfig->frequency,
                'time_step' => $timeSteps
            ];
            $warningConfig->time = json_encode($time, JSON_UNESCAPED_UNICODE);
        }

        if($warningConfig->save(false)){
            $content = '内容预警:预警设置修改';
            \Yii::$app->service->LogService->saveSysLog($content);
            return ['status' => true,'msg' => '预警设置成功'];
        }else{
            return ['status' => false,'msg' => '系统错误'];
        }
    }

    /**
     * 舆情预警联系人设置
     * @param $warningConfig
     */
    public function warnContact($warningConfig,$type)
    {
        $warningConfig->user_id = UserService::$uid;
        $warningConfig->contact = is_array($warningConfig->contact)?implode(',',$warningConfig->contact):$warningConfig->contact;
        if(empty($warningConfig->weeks)){
            $warningConfig->weeks = json_encode([1,2,3,4,5]);
        }
        if(empty($warningConfig->sentiment)){
            $warningConfig->sentiment = json_encode(['负面'],JSON_UNESCAPED_UNICODE);
        }
        if(empty($warningConfig->media)){
            $warningConfig->media = json_encode(['total'],JSON_UNESCAPED_UNICODE);
        }
        $timeSteps = $this->timeStep(warningConfig::DEFAULT_BEGIN_TIME,warningConfig::DEFAULT_END_TIME,warningConfig::DEFAULT_FREQUENCY);
        $time = [
            'begin_time' => warningConfig::DEFAULT_BEGIN_TIME,
            'end_time' => warningConfig::DEFAULT_END_TIME,
            'frequency_set' => warningConfig::DEFAULT_FREQUENCY,
            'time_step' => $timeSteps
        ];
        $warningConfig->time = json_encode($time,JSON_UNESCAPED_UNICODE);
        if($warningConfig->save(false)){
            $contacts = [];
            $result = WarningUser::find()->select(['id','username',$type.' as value'])
                ->where(['in','id',explode(',',$warningConfig->contact)])
                ->andWhere(['!=',$type,''])->asArray()->all();
            foreach($result as $v){
                $contacts[] = [
                    "id" => $v['id'],
                    "type" => $type,
                    "username" => $v['username'],
                    "value" => $v['value']
                ];
            }
            $content = '内容预警:预警联系人设置';
            \Yii::$app->service->LogService->saveSysLog($content);
            jsonSuccessReturn($contacts,'预警联系人设置成功');
        }else{
            jsonErrorReturn('systemError');
        }
    }

    /**
     * @param $beginTime
     * @param $endTime
     * @param $frequencySet
     * @return array
     */
    private function timeStep($beginTime,$endTime,$frequencySet){
        $timeSteps = [$beginTime];
        $step = 0;
        switch ($frequencySet){
            case '0.05':$step=60*5;$level=1;break;
            case '0.1':$step=60*10;break;
            case '0.2':$step=60*20;break;
            case '0.3':$step=60*30;break;
            case '1':$step=60*60;break;
            case '2':$step=60*60*2;break;
            case '4':$step=60*60*4;break;
            case '6':$step=60*60*6;break;
            case '8':$step=60*60*8;break;
            case '12':$step=60*60*12;break;
            case '24':$step=60*60*24;break;
        }
        if($step == 0){
            return false;
        }
        $nowDay = date('Y-m-d');
        $n = $nowDay.' '.$beginTime; // '20170101 06:00:00'
        // 起始时间戳
        $n_unix = strtotime($n);
        while (true){
            $n_unix = $n_unix+$step;
            // 需要判断  $n_unix  是否已经跨天
            $i_day = date('Y-m-d',$n_unix);
            if($i_day==$nowDay){
                $t = date('H:i:s',$n_unix);
                if($t>$endTime){
                    // pass
                }else{
                    $timeSteps[] = $t;
                }
            }else{
                // 当已经跨越今日  则忽略其后时间
                break;
            }
        }
        return $timeSteps;
    }


    /**修改联系人
     * @param $company_id
     * @param $contact
     * @param string $type  add 追加联系人  up 覆盖联系人
     * @return bool
     */
    public function addConact($company_id,$contact,$type='up'){
        if($WarningConfig = WarningConfig::findOne(['company_id' => $company_id])){
            $contact_wx_id = [];
            if($WarningConfig->contact) {
                $contact_wx = WarningUser::find()->select('id')
                    ->where("id in ({$WarningConfig->contact}) and openid!='' and openid is not null")
                    ->asArray()->all();
                foreach ($contact_wx as $cv){
                    $contact_wx_id[] = $cv['id'];
                }
            }
            if($type=='up'){
                $contact = array_merge($contact,$contact_wx_id);
                $WarningConfig->contact = is_array($contact)?implode(',',array_values(array_filter(array_unique($contact)))):$contact;
            }else{
                $contact = $WarningConfig->contact.','.$contact;
                $contact = explode(',',$contact);
                $WarningConfig->contact =implode(',',array_values(array_filter(array_unique($contact))));
            }
        }else{
            $WarningConfig = new WarningConfig();
            $WarningConfig->company_id = $company_id;
            $WarningConfig->status = WarningConfig::SWITCH_OFF;
            $WarningConfig->contact = is_array($contact)?implode(',',array_values(array_filter(array_unique($contact)))):$contact;
        }
        if($WarningConfig->save()){
            return true;
        }
        return false;
    }

    /**
     * 获取预警信息
     * @param $sid
     * @return array|\yii\db\ActiveRecord|null
     */
    public function getSchemeWarnInfo($company_id){
        try {
            $warningConfig = WarningConfig::find()
                ->select('begin_date,end_date,time,media,level,weeks,is_weekend,contact,status,sentiment,keywords')
                ->where(['company_id' => $company_id])->asArray()->one();
            if ($warningConfig) {
                $times = json_decode($warningConfig['time'], true);
                $warningConfig['beginTime'] = isset($times['begin_time']) ? $times['begin_time'] : '';
                $warningConfig['endTime'] = isset($times['end_time']) ? $times['end_time'] : '';
                $warningConfig['frequency'] = isset($times['frequency_set']) ? $times['frequency_set'] : '';
                $mediaPlatform = Yii::$app->params['ShudiEsPlatform'];
                if (empty($warningConfig['media'])) {
                    $warningConfig['media'] = [];
                } else {
                    $warningConfig['media'] = json_decode($warningConfig['media'], true);
                    if (count($warningConfig['media']) == count($mediaPlatform)) {
                        $warningConfig['media'] = ['total'];
                    }
                }
                $warningConfig['weeks'] = !empty($warningConfig['weeks']) ? json_decode($warningConfig['weeks'], true) : [];
                $warningConfig['sentiment'] = !empty($warningConfig['sentiment']) ? json_decode($warningConfig['sentiment'], true) : ['负面'];
                $warningConfig['keywords'] = !empty($warningConfig['keywords']) ? implode(',', json_decode($warningConfig['keywords'], true)) : '';
                $warningConfig['contact'] =!empty($warningConfig['contact'])? array_values(array_unique(array_filter(explode(',', $warningConfig['contact'])))):'';
                $warningConfig['status'] = intval($warningConfig['status']);
                $warningConfig['is_weekend'] = intval($warningConfig['is_weekend']);
            } else {
                $warningConfig = [
                    "media" => [],
                    "weeks" => [1, 2, 3, 4, 5],
                    "status" => 0,
                    "sentiment" => ['负面'],
                    "keywords" => "",
                    "beginTime" => WarningConfig::DEFAULT_BEGIN_TIME,
                    "endTime" => WarningConfig::DEFAULT_END_TIME,
                    "frequency" => WarningConfig::DEFAULT_FREQUENCY,
                    'contact' => [],
                ];
            }
            return $warningConfig;
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }

    // 关闭方案预警
    public function closeWarning($sid){
        warningConfig::updateAll(['status' => warningConfig::SWITCH_OFF],['scheme_id' => $sid]);
    }


    /**
     * @param $uid
     * @param $data
     * @return array|bool
     * 添加联系人
     */
    public function addUserWarning($data) {
        $return = [];
        if (isset($data['wx']) && !empty($data['wx']) && is_array($data['wx'])) {
            $return = array_column(WarningUser::find()
                ->select('id')->where(['company_id' => CompanyService::$company_id])
                ->andWhere(['in', 'openid', $data['wx']])->asArray()->all(), 'id');
        }
        if (isset($data['tel']) && !empty($data['tel']) && is_array($data['tel'])) {
            foreach ($data['tel'] as $value) {
                $model = WarningUser::findOne(['tel' => $value, 'company_id' => CompanyService::$company_id]);
                if (!$model) {
                    $model = new WarningUser();
                    $model->username = $value;
                    $model->tel = $value;
                    $model->user_id = UserService::$uid;
                    $model->company_id = CompanyService::$company_id;
                    $model->status = 1;
                    $model->save();
                }
                $return[] = $model->id;
            }
        }
        if (isset($data['email']) && !empty($data['email']) && is_array($data['email'])) {
            foreach ($data['email'] as $value) {
                $model = WarningUser::findOne(['email' => $value, 'company_id' => CompanyService::$company_id]);
                if (!$model) {
                    $model = new WarningUser();
                    $model->email = $value;
                    $model->username = $value;
                    $model->user_id = UserService::$uid;
                    $model->company_id = CompanyService::$company_id;
                    $model->status = 1;
                    $model->save();
                }
                $return[] = $model->id;
            }
        }
        return $return ? $return : false;
    }

    /**
     * 预警列表
     * @param $sid
     * @param $page
     * @param $limit
     * @param $begin_time
     * @param $end_time
     * @return array|mixed
     */
    public function warnList($page,$limit,$begin_time,$end_time){
        $warnList = [
            'newsList' => [],
            'count' => 0,
            'numFound' => 0
        ];
        $offset = ($page - 1) * $limit;
        $WarningConfig = WarningConfig::findOne(['company_id' => CompanyService::$company_id]);
        $map = [
            'from' => $offset,
            'size' => $limit,
            'startDate' => $begin_time,
            'endDate' => $end_time,
        ];
        $es = $this->getWarnCondition($WarningConfig,$map);
        $warnList = $es->query();
        foreach($warnList['newsList'] as &$v){
            $v['app_name'] = $v['media_name']?:$v['platform_name'];
            $v['app_name'] =  strip_tags($v['app_name']);//过滤标签
            $v['news_title'] = dealNewsTitle($v['news_title']);
            $v['news_postdate'] = date('Y-m-d',strtotime($v['news_posttime']));
            $v['account_avatar'] = getMediaIcon($v['platform']);
        }
        $warnList['newsList'] = camelCaseToSwitchData($warnList['newsList']);
        $warnList['count'] = $warnList['numFound'];
        $warnList['numFound'] = $warnList['numFound'] > 10000 ? 10000 : $warnList['numFound'];
        return $warnList;
    }

    /**
     * 下载预警
     * @param $sid
     * @param $type
     * @param $map
     * @return array|bool
     */
    public function downloadNewsList($type, $map) {
        $list = [
            'newsList' => [],
            'numFound' => 0
        ];
        switch ($type) {
            case 1:
                if (empty($map['uuid'])) return $list;
                $Es = new ShudiEsBase();
                $index = Yii::$app->params['shudiEsPrefix'].CompanyService::$company_info['company_index'];
               // $index="new_sdxt_qingbo_test";
                $list = $Es->index($index)->where('news_uuid',explode(',',$map['uuid']))->query();
                $list['newsList'] = isset($list['newsList'])?camelCaseToSwitchData($list['newsList']):[];
                break;
            case 2://信息汇总全部下载
                $list = $this->warnList($map['page'],$map['perpage'],$map['startDate'],$map['endDate']);
                break;
        }
        return $list;
    }

    /**
     * 信息列表下载条件拼接使用
     */
    public function getWarnCondition($WarningConfig = null,$map = ['startDate' => null,'endDate' => null]){
        if(is_null($WarningConfig)){
            $WarningConfig = WarningConfig::findOne([
                'company_id' => CompanyService::$company_id,
            ]);
        }
        if(empty($WarningConfig->media)){
            $platform = array_keys(Yii::$app->params['ShudiEsPlatform']);
        }else{
            $platform = json_decode($WarningConfig->media,true);
            if(in_array('total',$platform)){
                $platform = array_keys(Yii::$app->params['ShudiEsPlatform']);
            }
        }
        $platforms = $platform;
        if(empty($WarningConfig->sentiment)){
            $sentiment = ['负面'];
        }else{
            $sentiment = json_decode($WarningConfig->sentiment,true);
        }
        if(empty($WarningConfig->keywords)){
            $keywordsFinal = [];
        }else{
            $keywordsFinal = json_decode($WarningConfig->keywords,true);
        }
        $es = new ShudiEsBase();
        $es->sort(ShudiEsBase::POSTTIME,'desc')
            ->select(['es_create_time','news_title','platform','news_postdate','news_posttime','media_name','platform_name','news_emotion','news_url','news_uuid'])
            ->keyword($keywordsFinal)
            ->platformType($platforms)
            ->emotion($sentiment)
            ->postdate($map['startDate'],$map['endDate']);
        if(!empty($map['from'])){
            $es->from($map['from']);
        }
        if(!empty($map['size'])){
            $es->size($map['size']);
        }
        $index = Yii::$app->params['shudiEsPrefix'].CompanyService::$company_info['company_index'];
         //$index = "new_sdxt_qingbo_test";
        $es->index($index);
        return $es;
    }

    public function getPlatforms($media){
        if(empty($media)){
            $platform = array_keys(Yii::$app->params['ShudiEsPlatform']);
        }else{
            $platform = json_decode($media,true);
            if(in_array('total',$platform)){
                $platform = array_keys(Yii::$app->params['ShudiEsPlatform']);
            }
        }
        return $platform;
    }
}