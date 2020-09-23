<?php

namespace webapi\services;

use webapi\models\Scheme;
use Yii;
use webapi\extensions\MiddleEsBase;
use webapi\extensions\ShudiEsBase;
use yii\helpers\ArrayHelper;
use terry\nlp\NlpRpcSdk;

/**
 * @Author:    Peimc<2676932973@qq.com>
 * @Date:      2020/6/9
 */
class SchemeEsService
{

    public $sid;
    public $type;
    public $schemeInfo;
    public $baseEs;
    public $params;//其他参数

    public function __construct()
    {
        $sid = Yii::$app->request->isGet ? Yii::$app->request->get("sid") : Yii::$app->request->post("sid");
        if (!$sid) return jsonErrorReturn("fail", "方案id不能为空");
        //获取方案详情 判断该方案是否可以访问
        $schemeInfo = SchemeService()->getSchemeInfo($sid,true);
        if (!$schemeInfo) return jsonErrorReturn("fail", "该方案无权访问");
        $this->sid = $schemeInfo['id'];
        $this->schemeInfo = $schemeInfo;

        $type = Yii::$app->request->get('type','whole');
        $this->type = $type;
        $platformType = Yii::$app->request->get("platformType",'');
        if($type=='local'){
            $baseEs = new ShudiEsBase();
            // todo 获取当前用户角色权限
            $params['platformTypes'] = array_keys(Yii::$app->params['ShudiEsPlatform']);
            $params['defaultPlatformTypes'] = Yii::$app->params['ShudiEsPlatform'];
            if(!empty($platformType)){
                $params['platformType'] = $platformType;
            }
        }else{
            $baseEs = new MiddleEsBase();
            $params['platformTypes'] = array_keys(Yii::$app->params['MiddleEsPlatform']);
            $params['defaultPlatformTypes'] = Yii::$app->params['MiddleEsPlatform'];
            if(!empty($platformType)){
                $params['platformType'] = $platformType;
            }
        }

        $province = Yii::$app->request->get("province",'');
        $city = Yii::$app->request->get("city",'');
        if(!empty($province)){
            $params['province'] = $province;
        }
        if(!empty($city)){
            $params['city'] = $city;
        }
        $params['beginDate'] = $schemeInfo['beginDate']." 00:00:00";
        $params['endDate'] = time()>strtotime($schemeInfo['endDate']." 23:59:59")?$schemeInfo['endDate']." 23:59:59". "":date("Y-m-d H:i:s");
        $level = Yii::$app->request->get("level",'');
        if(!empty($level)) {
            if ($level == '省级') {
                $params['mediaLevel'] = "省级媒体";
            } else {
                $params['mediaLevel'] = "中央媒体";
            }
        }
        $this->params = $params;
        $this->buildEsCondition($baseEs,$type);
    }

    protected function buildEsCondition($baseEs,$type){
        if($type=='local'){
            $index = Yii::$app->params['shudiEsPrefix'].CompanyService::$company_info['company_index'];
            $baseEs->index($index);
            if(!empty($this->params['platformType'])){
                $baseEs->platformType( $this->params['platformTypes'] );
            }
        }else{
            //媒体平台
            if (!empty($this->params['platformType']) && $this->params['platformType'] != 'total' ) {
                //查询其他选项
                if ($this->params['platformType'] == "other" ){
                    $otherIndex = Yii::$app->params['otherMiddleEsIndex'];//配置查询
                    $baseEs->platformType($otherIndex);
                }elseif (in_array($this->params['platformType'],['video_douyin',"video_kuaishou"])){
                    //抖音快手数据单独处理
                    $platformName = $this->params['platformType'] == "video_douyin" ? "抖音" :"快手" ;
                    $baseEs->where("platform_name", [$platformName]);
                    $baseEs->platformType(  "video" );
                }else{
                    $baseEs->platformType( $this->params['platformType'] );
                }
            }
        }
        //发布省份
        $AreaService = new AreaService();
        if (isset($this->params['province']) && $this->params['province'] !== '全国') {
            $areaInfo  = $AreaService->getAreaInfo( $this->params['province'] );
            $baseEs->province($areaInfo->realName);
        }
        //发布城市
        if (isset($this->params['city']) && $this->params['city']) {
            $level = $AreaService->isLevelProvince($this->params['province']);
            if ( $level ){
                $baseEs->where("platform_county",$this->params['city']);
            }else{
                $baseEs->city($this->params['city']);
            }
        }
        $baseEs->keyword($this->schemeInfo['keywordsFinal']);
        $baseEs->keywordExclude($this->schemeInfo['keywordsExclude']);
        //媒体类别
        if (isset($this->params['mediaLevel'])) {
            $baseEs->where('media_level',$this->params['mediaLevel']);
        }
        $baseEs->posttime($this->params['beginDate'], $this->params['endDate']);
        return $this->baseEs = $baseEs;
    }

    /**
     * 专题详情信息
     */
    public function getSchemeInfo(){
        $firstArc = $this->baseEs->sort('news_posttime','asc')->size(1)->query();
        $return = $this->schemeInfo;
        $return['newsEmotion'] = isset($firstArc['newsList'][0]['news_emotion'])?$firstArc['newsList'][0]['news_emotion']:'';
        return $return;
    }

    /**
     * 事件概况
     */
    public function getHotOverView(){
        $count = $this->baseEs->count()->query(); //查询总数
        $platformGroup = $this->baseEs->group("platform")->query(); //媒体分布
        $emotionGroup = $this->baseEs->group("news_emotion")->query(); //情感分布
        //var_dump($emotionGroup);exit;
        $maxPlatform = $totalPlatform = 0;$media='';
        $maxEmotion = $totalEmotion = 0;$emotion='';
        foreach ($platformGroup as $k=>$v){
            $totalPlatform += $v;
            if($v>$maxPlatform && isset($this->params['defaultPlatformTypes'][$k])){
                $maxPlatform = $v;
                $media = $this->params['defaultPlatformTypes'][$k];
            }
        }
        $p1 = !empty($count) ? round($maxPlatform/$count,4)*100 : 0;
        foreach ($emotionGroup as $k=>$v){
            $totalEmotion += $v;
            if($v>$maxEmotion){
                $maxEmotion = $v;
                $emotion = $k;
            }
        }
        $p2 = !empty($count) ? round($maxEmotion/$count,4)*100 : 0;
        $msg = "<p class='mt5'>自{$this->params['beginDate']}至{$this->params['endDate']}，共有事件相关信息<strong class='color-blue'>{$totalPlatform}</strong>条。</p>
<p class='mt5'>在传播媒体中，{$media}相关信息最多，总计<strong class='color-blue'>{$maxPlatform}</strong>条，占比<strong class='color-blue'>{$p1}%</strong></p>
<p class='mt5'>整体来看，{$emotion}信息最多，总计<strong class='color-blue'>{$maxEmotion}</strong>条，占比<strong class='color-blue'>{$p2}%</strong>。</p>";
        return $msg;
    }

    /**
     * 数据汇总
     * @return array
     *
     */
    public function getHotLineChart() {
        //判断起始时间 是否小于24小时
        $type = "day";
        if (strtotime($this->params['endDate']) - strtotime($this->params['beginDate']) <= 86400) $type = "hour";
        $dateCount = $this->baseEs->datePlatformGroup('platform',$type)->query();
        $rangeData = $this->formatData1($dateCount, $type, $this->params['beginDate'], $this->params['endDate']);
        return $rangeData;
    }

    /**
     * @return array
     */
    public function getHotNewsPlatformPie(){
        $data = $this->baseEs->group("platform")->query(); //媒体分布
        $return =[];

        foreach ($data as $k=>$v){
            if(in_array($k,$this->params['platformTypes'])){
                $return[] =[
                    "name"  => $this->params['defaultPlatformTypes'][$k],
                    "value" => $v,
                    "index" => $k
                ];
            }
        }

        return $return;
    }

    /**
     * 央级/省级媒体发文
     */
    public function getNewsList(){
        $this->baseEs->keywordField($this->schemeInfo['keywordsFinal']);
        $list = $this->baseEs->appendFields(['media_CI',"news_sim_hash"])
            ->collapse('media_name')
            ->sort("news_posttime","desc")->size(10)->query();
        return $list;
    }

    /**
     * 活跃媒体渠道 媒体类型
     */
    public function getHotNewsMediaType(){
        $mediaType = $this->params['defaultPlatformTypes'];
        return $mediaType;
    }
    /**
     * 活跃媒体渠道
     * @return array
     */
    public function getActiveMedia() {
        $groupFields = "media_name";
        $result = $this->baseEs->appendFields(['platform_picurl', 'media_name'])
            ->group($groupFields, null, 7, 1)
            ->exists($groupFields)
            ->query();
        $data = [];
        $max = null;
        foreach ($result as $k => $v) {
            $list = $v['newsList'][0];
            $max  = is_null($max) ? $v['numFound'] : $max;
            $tmp  = [];
            $tmp['platformName']   = $list[$groupFields];
            $tmp['newsPosttime']   = $list['news_posttime'];
            $tmp['platform']       = $list['platform'];
            $tmp['newsTotal']      = formatNum($v['numFound']);
            $tmp['newsTitle']      = $list['news_title'];
            $tmp['newsDigest']     = $list['news_digest'];
            $tmp['accountAvatar'] = getMediaIcon($list["platform"]);
            $tmp['percent']        = round($v['numFound'] * 100 / $max, 2) . '%';
            $data[] = $tmp;
        }
        return $data;
    }

    // 活跃账号文章
    public function getActiveMediaArticle()
    {
        $media_name = Yii::$app->request->get('mediaName', '');
        $platform_type = Yii::$app->request->get('platformType', '');
        $page = (int) Yii::$app->request->get('page', 1);
        $limit = (int) Yii::$app->request->get('limit', 10);
        if (empty($media_name)) return jsonErrorReturn("fail", "媒体名称不能为空");
        if (empty($platform_type)) return jsonErrorReturn("fail", "媒体类型不能为空");

        // 先查询总数,再分页
        $from = ($page - 1) * $limit;
//        $count = $this->baseEs->appendFields(['platform_picurl', 'media_name'])
//            ->where('media_name', '新浪网')
//            ->count()
//            ->query();

        $res = $this->baseEs->appendFields(['platform_picurl', 'media_name'])
            ->where('media_name', $media_name)
            ->sort('news_posttime', 'desc')
            ->from($from)
            ->size($limit)
            ->query();

        return $res['newsList'] ?? [];
    }

    /**
     * 情感属性
     * @return array
     */
    public function getEmotion() {
        //判断起始时间 是否小于24小时
        $type = "day";
        if (strtotime($this->params['endDate']) - strtotime($this->params['beginDate']) <= 86400) $type = "hour";
        $emotionGroup = ['positive' => '正面', 'neutral' => '中性', 'negative' => '负面'];
        $line = [];$pie  = [];
        $this->baseEs->dateGroup($type)->saveCondition("emotion");
        foreach ($emotionGroup as $key => $emotion) {
            $data   = $this->baseEs->useCondition("emotion")->emotion($emotion)->query();
            $res    = $this->formatData($data, $type);
            $line[$key]    = $res;
            //饼状图数据处理
            $bin_['name']  = $emotion;
            $bin_['value'] = array_sum($res['data']);
            $pie[]         = $bin_;
        }
        return compact("line", "pie");
    }

    /**
     * 情绪分析
     */
    public function getMoods(){
        //判断起始时间 是否小于48小时
        $type = "day";
        if (strtotime($this->params['endDate']) - strtotime($this->params['beginDate']) <= 86400) $type = "hour";
        $moodGroup = Yii::$app->params['moods'];
        //折线图
        $line = [];
        //饼状图
        $pie  = [];
        //占比折线图
        $columnar = [];
        $line['dataName'] = array_keys($moodGroup);
        //每个时间段总和
        $sum = [];
        //占比趋势图小项集合最后一项索引值
        $lastKey = '';
        //占比趋势图小项集合
        $arr = [];
        $this->baseEs->dateGroup($type)->saveCondition("mood");
        foreach ($moodGroup as $key => $mood) {
            $data   = $this->baseEs->useCondition("mood")
                ->where('news_mood_pri',$mood)->query();
            $res    = $this->formatData($data, $type);
            $time = $res['time'];
            $line[$key]    = $res['data'];
            //饼状图数据处理
            $bin_['name']  = $key;
            $bin_['value'] = array_sum($res['data']);
            $pie[]         = $bin_;
            if($res['data']) {
                $sum = array_map('addition',$res['data'],$sum);
                $arr[$key] = $res['data'];
            }
            $lastKey = $key;
        }
        //占比折线图
        if($sum) {
            $columnar = $this->formatMoodProportionData($arr, $lastKey, $sum);
        }
        $columnar['dataName'] = $line['dataName'];
        $line['datetime'] = $time;
        $columnar['datetime'] = $time;
        return compact("line", "pie", 'columnar');
    }


    /**
     * 发布地区数据
     * @return array
     */
    public function getPubArea() {
        $AreaService = new AreaService();
        if (isset($this->params['province']) && $this->params['province'] !== '全国') {
            $field = $AreaService->getCityGroupField( "pub", $this->params['province'] );
            $all   = $AreaService->getCityByProvince( $this->params['province'] );
            $isSelectProvince = false ;
        } else {
            $all = $AreaService->getAllProvince();
            $field = "media_province";
            $isSelectProvince = true ;
        }
        $list = $this->baseEs->group($field,null,120)->query();

        $return = [];
        foreach ($all as $province) {
            $return_['name']  = $province->name ;
            $realNameNumList = $AreaService->getKeywordsList( $province->name,$isSelectProvince);
            $NameNum  = 0 ;
            foreach ($realNameNumList as $item){
                $NameNum  +=  isset($list[$item]) ? $list[$item] : 0;
            }
            $return_['value'] = $NameNum;
            $return[]         = $return_;
        }
        ArrayHelper::multisort($return, "value", SORT_DESC);//排序处理
        return $return;
    }

    /**
     * 获取词云图数据
     * @return array
     */
    public function getHotTheme() {
        $key_cache = 'scheme:hot:theme:' . $this->sid . ':' . $this->type . ':' . strtotime($this->schemeInfo['update_time']);

        $return_json = Yii::$app->cache->get($key_cache);
        $return = json_decode($return_json, true);
        if (empty($return)) {
            $return = [];
            $data = $this->baseEs->group("news_keywords_list", null, 100)->query();
            foreach ($data as $key => $item) {
                $return[] = [
                    'name' => $key,
                    'value' => $item,
                ];
            }
            Yii::$app->cache->set($key_cache, json_encode($return, JSON_UNESCAPED_UNICODE), 10 * 60);
        }

        return $return;
    }

    /**
     * 热门文章数据获取
     */
    public function getHotArticle() {
        $size = 20;
        $sortFieldArr = [
            '1' => 'news_reposts_count',
            "2" => 'news_comment_count',
            "3" => 'news_like_count',
        ];
        $dataO = $this->baseEs->where('platform',"weibo")->sort('news_reposts_count')->appendFields($sortFieldArr)->size($size)->query();
        $dataO = $dataO['newsList'];
        $data = [];
        foreach ($dataO as $k => $v) {
            $tmp["news_reposts_count"] = $v['news_reposts_count'];
            $tmp["news_comment_count"] = $v['news_comment_count'];
            $tmp["news_like_count"]    = $v['news_like_count'];
            $tmp["newsTitle"]          = $v['news_title'] ? dealNewsTitle($v['news_title']) : dealNewsTitle($v['news_digest']);
            $tmp["newsUrl"] = $v['news_url'];
            $tmp["newsPosttime"] = $v['news_posttime'];
            $tmp["newsId"] = $v['news_uuid'];
            $tmp["news_sim_hash"]       = $v['news_sim_hash'];
            $tmp["app_name"]       = !empty($v['media_name'])?$v['media_name']:$v['platform_name'];
            $data[]        = $tmp;
        }
        $data = $this->uniquArr($data,7);
        return camelCaseToSwitchData($data);
    }


    public function getEntity($params){
        $size = 50;
        $startTime = date("Y-m-d 00:00:00",time()-7*86400);
        $endTime = date("Y-m-d H:i:s");
        $model = $this->baseEs->size($size)->posttime($startTime,$endTime);
        $res = $model->query(false);
        //总数
        $total_num = $res["numFound"];
        $entity_organization = [];
        $entity_person = [];
        foreach($res["newsList"] as $key => $item){
            $content_url = Yii::$app->params['ossDomain'].$item['news_local_url'];
            $content = @file_get_contents($content_url);
            if(!$content) continue;
            $result = NlpRpcSdk::entityRecognition($content);
            if($result["organization"]){
                foreach($result["organization"] as $organ){
                    if(!array_key_exists($organ,$entity_organization)){
                        $entity_organization[$organ] = 1;
                    }else{
                        $entity_organization[$organ] ++;
                    }
                }
            }
            if($result["person"]){
                foreach($result["person"] as $person){
                    if(!array_key_exists($person,$entity_person)){
                        $entity_person[$person] = 1;
                    }else{
                        $entity_person[$person] ++;
                    }
                }
            }
        }
        //计算倍数
        $times = ceil($total_num / ($size * 10));
        foreach($entity_person as $key => $item){
            $entity_person[$key] = $item * $times  ? $item * $times : 1 ;
        }
        foreach($entity_organization as $key => $item){
            $entity_organization[$key] = $item * $times  ? $item * $times : 1 ;
        }
        if(count($entity_person)>5){
            arsort($entity_person);
            $entity_person_final = array_slice($entity_person,0,5);
        }else{
            arsort($entity_person);
            $entity_person_final = $entity_person;
        }
        if(count($entity_organization)>5){
            arsort($entity_organization);
            $entity_organization_final = array_slice($entity_organization,0,5);
        }else{
            arsort($entity_organization);
            $entity_organization_final = $entity_organization;
        }
        Yii::$app->cache->set("entity_scheme_v2_".$params["sid"].'_'.$params['type'],["entity_organization_final"=>$entity_organization_final,"entity_person_final"=>$entity_person_final],3600);
        return compact("entity_organization_final","entity_person_final");
    }



    public function uniquArr($array,$size = 5 ) {
        $result = array();
        foreach ($array as $k => $val) {
            $code = false;
            foreach ($result as $_val) {
                if ($_val['news_sim_hash'] == $val['news_sim_hash']) {
                    $code = true;
                    break;
                }
            }
            if (!$code) {
                $result[] = $val;
            }
        }

        return array_slice($result,0,$size);//截取前$size个
    }

    /**
     * 计算情绪占比
     * @param $columnar 情绪趋势图小项集合
     * @param $lastKey 集合中最后一项索引值
     * @param $sum 情绪趋势图小项，每项对应位置数字总和的集合
     * @return mixed
     */
    public function formatMoodProportionData($columnar, $lastKey, $sum) {
        $division = function($dataOne, $dataTwo) {
            if(!$dataTwo) {
                return 0;
            }
            $dataOne *= 1;
            $dataTwo *= 1;
            $data = $dataOne/$dataTwo*100;
            return $data>0?sprintf('%.1f', $data):0;
        };
        //除最后一项的占比总和
        $proportion = [];
        $subtract = function ($dataOne) {
            if(!$dataOne) {
                return 0;
            }
            $dataOne *= 1;
            $data = 100-$dataOne;
            return $data>0?sprintf('%.1f', $data):0;
        };
        foreach ($columnar as $key=>&$value) {
            if($key != $lastKey) {
                $value = array_map($division, $value, $sum);
                $proportion = array_map('addition',$value,$proportion);
            } else {
                //单独处理，为了保证占比能凑成100%
                $value = array_map($subtract, $proportion);
            }
        }
        unset($value);
        return $columnar;
    }

    /**
     * 折线图数据转换格式
     * @param $fromData
     * @param $startDate
     * @param $endDate
     * @return array y-m-d H:i
     */
    protected function formatData($fromData, $type) {
        $format = $type == "day" ? "m-d" : "H:i";
        $time = $data = [];
        foreach ($fromData as $key => $datum) {
            $key_e = $type == "day" ? $key : $key . ":00:00";
            $time[] = date($format, strtotime($key_e));
            $data[] = $datum;
        }
        return compact("time", 'data', "org");
    }

    protected function formatData1($fromData, $type, $startTime, $endTime) {
        $platforms = $this->params['defaultPlatformTypes'];
        if($type == "day"){
            $format = "m-d";
            $format1 = "Y-m-d";
            $step = 86400;
        }else{
            $format = "H:i";
            $format1 = "Y-m-d H";
            $step = 3600;
        }
        $time = $data = $result = [];
        $startTime = strtotime($startTime);
        $endTime = strtotime($endTime);
        for($i = $startTime;date($format1, $i) <= date($format1, $endTime);$i+=$step){
            $result['datetime'][] = date($format,$i);
            $time[] = date($format1,$i);
        }
        foreach ($fromData as $key => $datum) {
            if(isset($platforms[$key])){
                $result['data'][$platforms[$key]] = array_values($datum['detail']);
            }else{
                foreach($time as $v){
                    if(isset($result['data']['其他平台'][$v])){
                        $result['data']['其他平台'][$v] += $datum['detail'][$v];
                    }else{
                        $result['data']['其他平台'][$v] = $datum['detail'][$v];
                    }
                }
            }
            foreach($time as $v){
                if(isset($result['全部'][$v])){
                    $result['data']['全部'][$v] += $datum['detail'][$v];
                }else{
                    $result['data']['全部'][$v] = $datum['detail'][$v];
                }
            }
        }
        foreach($platforms as $key => $platform){
            if(!isset($result['data'][$platform])){
                $result[$platform] = array_fill(0,count($result['datetime']),0);
            }else{
                $result[$platform] = $result['data'][$platform];
            }
        }
        $result['dataName'] = array_values($platforms);
        array_unshift($result['dataName'],'全部');
        $result['data']['全部'] = array_values($result['data']['全部']);
        if(isset($result['data']['其他平台'])) {
            $result['data']['其他平台'] = array_values($result['data']['其他平台']);
        }
        return $result;
    }

}