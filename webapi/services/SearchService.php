<?php

namespace webapi\services;

use webapi\extensions\MiddleEsBase;
use webapi\extensions\ShudiEsBase;
use webapi\models\SysPageAuth;
use yii\helpers\ArrayHelper;
use Yii;

class SearchService
{
    public $baseEs;
    public $es_type; //whole 全网(中台es) local 本地(属地es)
    public $es_platform; //MiddleEsPlatform 中台es ShudiEsPlatform 属地es

    public function __construct($type = 'whole')
    {
        $this->es_type = $type;
        $this->es_platform = 'MiddleEsPlatform';
        if ($this->es_type == 'whole') {
            $this->es_platform = 'ShudiEsPlatform';
        }
    }

    /**
     * 获取ES实例
     * @return MiddleEsBase|ShudiEsBase
     */
    public function getEs()
    {
        if($this->es_type == 'local'){
            $es = new ShudiEsBase();
        }else{
            $es = new MiddleEsBase();
        }
        return $es;
    }

    /**
     * 获取可查看的媒体平台
     * @param $platform_arr ['web','wx']
     * @return array //
     */
    public function getPlatform($platform_arr)
    {
        if (in_array('total', $platform_arr)) {
//            $platform_arr = Yii::$app->params[$this->es_platform];
            $platform_arr = Yii::$app->params['ShudiEsPlatform'];

            //获取角色可查看的媒体标签
            $user_platform_arr = (new PageAuthService())->getTagList('normal', SysPageAuth::TYPE_MEDIA);
            $user_platform_arr = array_column($user_platform_arr['media_tag'], 'name');
            //获取交集
            $platform_arr = array_intersect($platform_arr, $user_platform_arr);
        }else {
            //获取交集
//            $platform_arr = array_intersect_key(Yii::$app->params[$this->es_platform], array_flip($platform_arr));
            $platform_arr = array_intersect_key(Yii::$app->params['ShudiEsPlatform'], array_flip($platform_arr));
        }

        return $platform_arr;
    }

    /**
     * 列表
     */
    public function getList($page, $num, $platformTypes, $keywords, $match, $emotion, $startDate, $endDate)
    {
        $es = $this->getEs();
        if ($platformTypes) { //平台
            $es->platformType($platformTypes);
        }
        if ($keywords) {
            $es->keyword([$keywords], ($match == 1 ? 'full' : 'title'), 'match'); //文章发布日期筛选
        }
        if ($emotion) {
            $es->emotion($emotion); //文章情感属性
        }
        $es->postdate($startDate, $endDate); //文章发布日期筛选
        $es->sort('news_posttime'); //排序
        return $es->from((($page - 1) * $num))->size($num)->query();//var_dump($es->params);exit;
    }

    /**
     * 根据news_sim_hash获取相似文章数
     */
    public function getCountSimHash($news_sim_hash_arr)
    {
        $es = $this->getEs();

        $es->simHash($news_sim_hash_arr); //相似文章查询sim_hash
        if (is_array($news_sim_hash_arr)) {
            return $es->group('news_sim_hash')->query();
        }else {
            return $es->count()->query();
        }
    }

    const CONDITION = "user";

    protected $searchType;

    protected $params;

    protected $simHashArr;

    protected $newsListCount;

    protected $newsList;

    protected $newsInfo;

    protected $searchStartTime;

    protected $searchEndTime;

    public function setCondition($params) {

        $this->searchStartTime = isset($params['startDate'])?$params['startDate']:date("Y-m-d",strtotime('-30days'));
        $this->searchEndTime = isset($params['endDate'])?$params['endDate']:date("Y-m-d 23:59:59");
        if (isset($params['media'])) $this->searchType = "media";
        if (isset($params['newsUuid']) && $params['newsUuid']) $this->searchType = "news";
        if (!$this->searchType) return jsonErrorReturn("fail","参数异常，缺失media或者newsUuid");
        $this->params = $params;
        if ($this->searchType == 'media') {
            $this->buildMediaCondition();
        }
        if ($this->searchType == 'news') {
            $res =  $this->buildNewsCondition();
            if (isset($res['msg'])) jsonErrorReturn("fail",$res['msg']);
        }
    }

    //获取可分析的媒体列表
    public function getMediaList() {

        $list = Medias::find()->asArray()->all();
        $return = [];
        foreach ($list as $item) {
            $return[$item['platform']][] = $item['media_name'];
        }
        return $return;
    }

    //全部情况
    public function buildMediaCondition() {
        $media = ArrayHelper::getValue($this->params, "media");
        $start = ArrayHelper::getValue($this->params, "start");
        $end = ArrayHelper::getValue($this->params, "end");
        list($media,$platform) = explode("_",$media);
        $platformList = ['微信'=>"wx","微博"=>"weibo","网站"=>"web","头条"=>"media_toutiao"];
        $platform = ArrayHelper::getValue($platformList,$platform);
        if ($start >= $end || !$start || !$end) {//默认最近一天
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d");
        }
        $this->searchStartTime = $startTime = $start . " 00:00:00";
        $this->searchEndTime = $endTime = date("Y-m-d H:i:s",strtotime($end) -1 );
        //先查询该媒体下 两百条的文章，获取相似hash，生成条件
        $esBase = $this->getEs();
        $list = $esBase->where("media_name", $media)
            ->platformType($platform)
            ->posttime($startTime, $endTime)->size(200)->query();
        $this->newsListCount = $list['numFound'];
        $this->newsList = $list['newsList'];
        $this->simHashArr = ArrayHelper::getColumn($list['newsList'], "news_sim_hash");
        $es = $this->getEs();
        $baseEs = $es->where("news_sim_hash", $this->simHashArr)
            ->posttime($startTime, $endTime)
            ->saveCondition(self::CONDITION);
        $this->baseEs = $baseEs;
    }

    //单个新闻传播
    public function buildNewsCondition() {
        $newsUuid = ArrayHelper::getValue($this->params, "newsUuid");
        $key = "news_info_key_" . $newsUuid . '_' . $this->es_type;
        $newsInfo = \Yii::$app->cache->get($key);
//        echo '<pre>';
//        print_r([$key, $newsInfo]);
//        exit;
        if ($newsInfo) {
//        if (0) {
            $this->newsInfo = $newsInfo;
        } else {
            $es = $this->getEs();
            $es->newsUuid($newsUuid);
            if ($this->es_type == 'whole') {
                $es->posttime(date('Y-m-d H:i:s', strtotime('-4 month')), date('Y-m-d H:i:s'));
            }
            $info = $es->query();
            if (!isset($info['newsList'][0])) {
                return ['msg' => "newsId异常"];
            }
            $newsInfo = $info['newsList'][0];
            $newsInfo = camelCaseToSwitchData($newsInfo);
            $this->newsInfo = $newsInfo;
            \Yii::$app->cache->set($key, $newsInfo);
        }
        $this->simHashArr = $newsInfo['newsSimHash'];
//        $this->searchStartTime = date("Y-m-d H:i:s", strtotime($newsInfo['newsPosttime'] . " -1 days"));
        $this->searchStartTime = $newsInfo['newsPosttime'];
        //查询是否停止追踪
//        $cancelInfo = CancelFollow::find()->where(['newsUuid' => $newsUuid])->one();
//        if ($cancelInfo) {
//            $this->searchEndTime = $cancelInfo->stop_time;
//        } else {
//            $this->searchEndTime = date("Y-m-d H:i:s");
//        }
        $this->newsListCount = 1;
        $this->newsList = [$newsInfo];
        $baseEs = $this->getEs()->where("news_sim_hash", $this->simHashArr)
            ->posttime( $this->searchStartTime,  $this->searchEndTime)
            ->saveCondition(self::CONDITION);
        $this->baseEs = $baseEs;
    }

    //转载情况
    public function reprintPie()
    {
        $hasMediaCount = count(array_unique(array_column($this->newsList,"mediaName")));
        //转载情况饼状图
        $pieData_ = $this->baseEs->group('platform')->query();
        $mediaPlatform = Yii::$app->params[$this->es_platform];
        //转载总数
        $rePostSum = array_sum($pieData_) - $this->newsListCount;
        $rePostSum = $rePostSum <=0 ? 0 : $rePostSum;
        $pieData = [];
        foreach ($pieData_ as $key => $item) {
            if (isset($mediaPlatform[$key])) {
                $mediaName = $mediaPlatform[$key];
                $iData = ['name' => $mediaName, "value" => $item];
                $pieData[] = $iData;
            }
        }
        $otherNum = $rePostSum - array_sum(ArrayHelper::getColumn($pieData, "value"));
        if ($otherNum > 0) {
            $otherData = [['name' => "其他", "value" => $otherNum]];
            $pieData = array_merge($pieData, $otherData);
        }
        //主流媒体转载总数
        $mainMedia = Yii::$app->params['mainMedia'];
        $mainMediaRePostCount = $this->baseEs->useCondition(self::CONDITION)
            ->where(($this->baseEs)::MEDIA_LEVEL, $mainMedia)
            ->count()->query();
        //转载媒体数
        $rePostMediaCount = $this->baseEs->useCondition(self::CONDITION)
            ->groupCount('media_name')->query();

        $rePostMediaCount = $rePostMediaCount - $hasMediaCount;
        //转载主流媒体数
        $rePostMainMediaCount = $this->baseEs->useCondition(self::CONDITION)
            ->where(($this->baseEs)::MEDIA_LEVEL, $mainMedia)
            ->groupCount('media_name')->query();

        return compact("pieData", "rePostSum", "mainMediaRePostCount",
            "rePostMediaCount", "rePostMainMediaCount");
    }

    //传播分析趋势
    public function propagationTrend() {
        //判断时间是否小于一天
        if ( strtotime($this->searchEndTime) - strtotime($this->searchStartTime) > 86400 ){
            $type = "day";
        }else{
            $type = "hour";
        }
        $lineData_ = $this->baseEs->datePlatformGroup("date",$type)->query();
        $mediaPlatform = Yii::$app->params[$this->es_platform];
        $lineData = [];
        $timeData = array_keys($lineData_);
        $day_i = 0;
        foreach ($lineData_ as $day => $item) {
            //输出total
            $lineData["total"][$day_i] = $total = $item['total'];
            $hasTotal = 0;
            foreach ($mediaPlatform as $key => $platform) {
                $num = isset($item['detail'][$key]) ? $item['detail'][$key] : 0;
                $lineData[$key][$day_i] = $num;
                $hasTotal = $hasTotal + $num;
            }
            $lineData["other"][$day_i] = $total - $hasTotal;
            $day_i++;
        }
        return compact("timeData", "lineData");
    }

    //传播路径
    public function transPath()
    {
//        if ( strtotime($this->searchEndTime) - strtotime($this->searchStartTime) > 86400 ){
//            $type = "news_postdate";
//        }else{
//            $type = "news_posthour";
//        }
        $type = "news_posthour";
        $ret   = $this->baseEs->exists("media_name")->group("news_postdate", null, 30, 100)->query();

        $newList = $mediaList = [];
        foreach ( $ret as $day => $newsList ){
            foreach ( $newsList['newsList'] as &$item ){
                $item['news_posthour'] = date("Y-m-d H",strtotime( $item['news_posttime']));
                $mediaList[$item['platform']] = $item['platform_name'];
                $newList[] = $item ;
            }
        }
//        $colorList = ["#16A79D","#F7624A","#80628B","#DD547A","#CF4858","#68B1CB","#F5AD42"];
        $colorList = ["#6fa8dc","#1b76fe","#073763","#6aa84f","#e7000a","#dd7e6b","#ff0000","#20124d"];
        $mediaColorList = [];
        $i = 0;
        foreach ($mediaList as $key => $item){
            $mediaColorList[$key] = isset($colorList[$i]) ? $colorList[$i] : $colorList[0];
            $i++;
        }
        $result = ArrayHelper::index($newList,"media_name" );
        ArrayHelper::multisort($result,"news_posttime");

        $result = ArrayHelper::index($result, null, $type);
        $mediaListKey = array_keys($mediaList);
        $timeLine   = array_keys($result);
//        krsort($timeLine);
        $transList = [];
        $mapList = [];
        $x = 0;
        foreach ($result as  $item) {
            $transList__ = [];
            foreach ($item as $y => $news){
                $transList_ = [];
                $transList_[] = $news['media_name'];//媒体名称
                $transList_[] = 0;//跟踪计数
                $transList_[] = -1;//父级x坐标 -1 表示没有父节点
                $transList_[] = -1;//父级y坐标 -1 表示没有父节点
                $transList_[] = 0;//childCount 当前有几个子级
                $transList_[] = $news['platform'];
                $transList_[] = $news['platform'];
                $transList_[] = $news['platform'];
                $transList_[] = $news['news_url'];//webUrl 新闻地址
                $transList_[] = $news['media_name'];//来源
                $transList_[] = $news[$type];//时间
                $transList__[] = $transList_;
                if (isset($news['news_origin_author_name']) && $news['news_origin_author_name']){
                    $keys = $x."_".$y."_".strtotime($news['news_posttime']);
                    $mapList[$keys] = $news['news_origin_author_name'];
                }
            }
            $x++;
            $transList[] = $transList__;
        }
        $newMapList = array_flip($mapList);

        foreach ($transList as $item){
            foreach ($item as &$news){
                if (isset($newMapList[$news[0]])){
                    /*  var_dump($news[0]);
                      var_dump($newMapList[$news[0]]);*/
                    list($x,$y,$time) = explode("_",$newMapList[$news[0]]);
                    $news[2] = $x;
                    $news[3] = $y;
                    $news[4] = $time;
                }
            }
        }

        $originItem[] = $this->newsInfo['mediaName'];
        $originItem[] = $this->newsInfo['mediaName'];
        $originItem[] = -1;
        $originItem[] = -1;
        $originItem[] =  $this->newsInfo['platformName'];
        $originItem[] =  3;
        $originItem[] =  4;
        $originItem[] = $this->newsInfo['platform'];
        $originItem[] = $this->newsInfo['newsUrl'];
        $originItem[] = $this->newsInfo['newsPosttime'];

        return compact("mediaListKey","timeLine","transList","originItem","mediaList","mediaColorList");
    }

    //所有平台转载媒体排行
    public function rePlatformPostRank()
    {
        $platform = $this->params['platform'];
        //如果是全部
        if (!empty($platform) && $platform != 'total') {
            $this->baseEs->platformType($platform);
        }
        //如果是媒体
        if($this->searchType=='meida'){
            //去除当前自己媒体
            $media = ArrayHelper::getValue($this->params, "media");
            list($thisMediaName,$thisPlatName) = explode("_",$media);
        }
        //如果是新闻
        if($this->searchType=='news'){
//            $thisMediaName = $this->newsInfo['mediaName'];
        }
        if(!empty($thisMediaName)){
//            $this->baseEs->mediaNameExclude([$thisMediaName]);
            $this->baseEs->mediaName([$thisMediaName]);
        }
        $data = $this->baseEs->exists("media_name")->group("media_name")->query();
        $media_name = array_keys($data);
        $news_num = array_values($data);
        return compact("media_name", "news_num");
    }

    /**
     * 获取词云图数据
     * @return array
     */
    public function getHotTheme() {

//        var_dump($this->baseEs->getCondition());die;
        $data = $this->baseEs->group(($this->baseEs)::KEYWORDS_LIST, null, 100)->query();
        $return = [];
        foreach ($data as $key => $item) {
            $return_['name'] = $key;
            $return_['value'] = $item;
            $return[] = $return_;
        }
        return $return;
    }

    /**
     * 发布地区数据
     * @return array
     */
    public function getPubArea() {
        $AreaService = new AreaService();
        if ($this->params['province'] && $this->params['province'] !== '全国') {
            $field = $AreaService->getCityGroupField("pub", $this->params['province']);
            $all = $AreaService->getCityByProvince($this->params['province']);
            $areaInfo = $AreaService->getAreaInfo($this->params['province']);
            $this->baseEs->province($areaInfo->realName);//重写省份的参数
            $isSelectProvince = false;
        } else {
            $this->baseEs->province("");//重置数据
            $all = $AreaService->getAllProvince();
            $field = ($this->baseEs)::PUB_PROVINCE;
            $isSelectProvince = true;
        }
        $list = $this->baseEs->group($field, null, 120)->query();

        $return = [];
        foreach ($all as $province) {
            $return_['name'] = $province->name;
            $realNameNumList = $AreaService->getKeywordsList($province->name, $isSelectProvince);
            $NameNum = 0;
            foreach ($realNameNumList as $item) {
                $NameNum += isset($list[$item]) ? $list[$item] : 0;
            }
            $return_['value'] = $NameNum;
            $return[] = $return_;
        }
        ArrayHelper::multisort($return, "value", SORT_DESC);//排序处理
        return $return;
    }

//    /**
//     * 处理追踪信息
//     * @param $newsUuid
//     * @param $deal
//     */
//    public function handleFollow($newsUuid, $deal) {
//
//        if ($deal == "cancel") {
//            $model = new CancelFollow();
//            $model->newsUuid = $newsUuid;
//            $model->stop_time = date("Y-m-d H:i:s");
//            return $model->save();
//        } else {
//            $info = CancelFollow::find()->where(["newsUuid" => $newsUuid])->one();
//            return $info->delete();
//        }
//
//    }

    public function getNewInfo(){

        return $this->newsInfo;
    }

    //获取弹片文章的相似文章列表
    public function getNewList() {

        $page = ArrayHelper::getValue($this->params, "page");
        $page = $page ? $page : 1;
        $limit = ArrayHelper::getValue($this->params, "limit");
        $limit = $limit ? $limit : 10;
        $offset = ($page - 1) * $limit;
        $return = $this->baseEs->from($offset)->size($limit)->query();
        if (!$return) return [];
        return ['total' => $return['numFound'], "list" => $return['newsList'],"page"=>(int)$page];
    }
}