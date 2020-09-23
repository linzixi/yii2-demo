<?php
namespace webapi\es;
/**
 * 中台es
 */
use Yii;
class MiddleEsBase
{
    private $index = 'web,aq,weibo,wx,app,bbs,journal,media_ifeng,media_sohu,media_wangyi,media_eastday,media_btime,media_toutiao,media_yidianzixun,media_chejia,media_yiche,media_qq,media_people,video';             // 索引
    protected $fields = [];               // 最终返回的字段
    protected $condition = [];            // 查询条件
    protected $response;                  // 网关返回的结果
    protected $result;                    // 对外输出的结果
    protected $hightLightFields = null;   // 高亮字段
    protected $dataType = 'list';         // 数据类型【list,count,group...】
    protected $groupField;                // 分组的字段别名（用于取数据）
    protected $sumField;                  // 求和的字段别名（用于取数据）
    protected $conditionStore;            // 条件库
    protected $decodeResponse;            // 响应数据
    protected $cache;                     // 缓存
    protected $startTime = null;          // 网关查询毫秒时间戳
    protected $endTime = null;            // 网关查询毫秒时间戳
    public $params = null;                //
    protected $scroll = false;            //游标查询
    protected $scrollId = null;

    const ES_GATEWAY_URL = "http://10.94.183.131:8081/mg/search/es";
    const ES_SCROLL_GATEWAY_URL = "http://10.94.183.131:8081/mg/search/es/scroll";

    const POSTTIME = 'news_posttime';
    const POSTDATE = 'news_postdate';
    const FETCHTIME = 'news_fetch_time';
    const ENTERTIME = 'solr_create_time';
    const EMOTION  = 'news_emotion';
    const PLATFORM = 'platform';
    const SIMHASH  = 'news_sim_hash';
    const READ_COUNT = 'news_read_count';
    const REPORT_COUNT = 'news_reposts_count';
    const COMMENT_COUNT = 'news_comment_count';
    const LIKE_COUNT  = 'news_like_count';
    const KEYWORDS_LIST  = 'news_keywords_list';
    const PLATFORM_PROVINCE = 'platform_province';
    const PLATFORM_CITY = 'platform_city';
    const MEDIA_PROVINCE = 'media_province';
    const MEDIA_CITY     = 'media_city';
    const REF_PROVINCE = "news_content_province";//提及省份
    const PLATFORM_NAME = 'platform_name';
    const MEDIA_NAME = 'media_name';
    const CONTENT_CATE = 'news_content_field';
    const MOOD_PRI = 'news_mood_pri';
    const MEDIA_LEVEL  = 'media_level';//媒体类别
    const PUB_PROVINCE = "media_province";//发布省份

    //情感属性阈值
    const EMOTION_SENSITIVE = 0.25;     //敏感的最大值
    const EMOTION_NEGATIVE  = 0.45;     //负面的最大值
    const EMOTION_POSITIVE  = 0.75;     //正面的最小值

    const START_DATE = '2019-08-01';

    public function __construct($limitField = false,$scroll = false,$collapse = false){   // $limitField  后期改为false
        $this->cache = Yii::$app->cache;
        $this->startTime = self::START_DATE;
        $this->endTime = date('Y-m-d');
        if($limitField){
            $this->fields = self::fields();
            $this->condition['_source'] = $this->fields;
        }
        if($scroll){
            $this->scroll = $scroll;
        }
        if($collapse){
            $this->collapse(self::SIMHASH);
        }
    }

    public function index($index = '*'){
        $this->index = $index;
        return $this;
    }

    public function from($from){
        $this->condition['from'] = (int) $from;
        return $this;
    }

    public function size($size){
        $this->condition['size'] = (int) $size;
        return $this;
    }

    /**
     * 排序规则
     * 默认按照news_posttime倒序desc
     * 传入数组，若数组长度大于等于2，则按照字段相加求和排序
     * @param $sort
     * @param string $order
     * @return $this
     */
    public function sort($sort = 'news_posttime',$order = 'desc'){
        if($sort){
            if(is_array($sort) && count($sort) >= 2){
                $script = implode('+',array_map(function($v){
                    return "doc.{$v}.value";
                },$sort));
                $this->condition['sort'][] = [
                    "_script" => [
                        "script" => [
                            "source" => $script,
                        ],
                        "type" => "number",
                        "order" => $order
                    ]
                ];
            }else{
                $sort = is_array($sort) ? $sort[0] : $sort;
                $this->condition['sort'][$sort]['order'] = $order;
            }
        }
        return $this;
    }

    /**
     * 按照传入的platformUuid的顺序来排序
     * @param $platformUuids
     * @return $this
     */
    public function sortPlatformUuid($platformUuids,$order = 'asc'){
        if($platformUuids){
            $platformUuidSort = [];
            for($i=0;$i<count($platformUuids);$i++){
                $platformUuidSort[$platformUuids[$i]['platform'].'_'.$platformUuids[$i]['news_uuid']] = $i;
            }
            $this->condition['sort'][] = [
                "_script" => [
                    "script" => [
                        "source" => "params.platformUuids[doc['platform'].value +'_'+ doc['news_uuid'].value]",
                        "params" => [
                            "platformUuids" => $platformUuidSort
                        ]
                    ],
                    "type" => "number",
                    "order" => $order
                ]
            ];
        }
        return $this;
    }

    public function scroll($scrollId){
        $this->scrollId = $scrollId;
        return $this;
    }

    /**
     * 分词
     * @param array $participles
     * @param string $analyzer
     * @return array|mixed
     */
    public function fenci($participles = [],$analyzer = 'my_analyzer'){
        $key = 'fenci_'.$analyzer.'_'.md5(json_encode($participles));
        if($data = Yii::$app->cache->get($key)){
            $data = json_decode($data,true);
        }else{
            $headers = [];
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Accept: application/plain';
            $headers[] = 'Authorization:'.$this->getEsToken();
            $headers[] = 'Expect:';

            $data = [];
            $analyze = [
                "analyzer" => $analyzer,
                "text" => $participles
            ];
            $url = "http://10.94.183.131:8081/mg/search/es/analyze?index=web_".date('Ym',time()-86400)."&statement=".urlencode(json_encode($analyze,JSON_UNESCAPED_UNICODE));
            $result = httpRequest($url,$headers);
            if($result){
                $data = json_decode($result,true);
                $data = array_column($data['tokens'],'token');
            }
            Yii::$app->cache->set($key,json_encode($data,JSON_UNESCAPED_UNICODE),86400*90);
        }
        return $data;
    }

    /**
     * 关键词查询
     * @param array $keyword_groups
     * @param string $type => [full,title]
     * @param string $searchType => [match_phrase,match]
     * @return $this
     */

    public function keyword($keywordGroups = [],$type = 'full',$searchType = 'match_phrase'){
        $keywordIncludeQuery = null;
        if(!empty($keywordGroups)){
            $keywordIncludeQuery = [];
            $participles = [];
            foreach($keywordGroups as $keywordGroup){
                $participles = array_merge($participles,explode('+',$keywordGroup));
            }
            $fenciResult = $this->fenci($participles);
            foreach($keywordGroups as $keywordGroup){
                $keywords = explode('+',$keywordGroup);
                $must_arr = ['bool'=>['must'=>[]]];
                foreach($keywords as $keyword){
                    if($type == 'full'){
                        if($searchType == 'match' && mb_strlen($keyword) > 3){
                            $tmp['bool'] = [
                                'should' => [
                                    ["match"=>["news_title"=>["query"=>$keyword,"minimum_should_match"=>"80%"]]],
                                    ["match"=>["news_content"=>["query"=>$keyword,"minimum_should_match"=>"80%"]]],
                                ]
                            ];
                        }else{
                            if(in_array($keyword,$fenciResult)){
                                $tmp['bool'] = [
                                    'should' => [
                                        ["term"=>["news_title"=>$keyword]],
                                        ["term"=>["news_content"=>$keyword]],
                                    ]
                                ];
                            }else{
                                $tmp['bool'] = [
                                    'should' => [
                                        ["match_phrase"=>["news_title"=>["query" => $keyword,"slop" => 0]]],
                                        ["match_phrase"=>["news_content"=>["query" => $keyword,"slop" => 0]]],
                                    ]
                                ];
                            }
                        }
                    }elseif($type == 'title'){
                        if($searchType == 'match' && mb_strlen($keyword) > 3){
                            $tmp = ["match"=>["news_title"=>["query"=>$keyword,"minimum_should_match"=>"80%"]]];
                        }else{
                            if(in_array($keyword,$fenciResult)){
                                $tmp = ["term"=>["news_title"=>$keyword]];
                            }else{
                                $tmp = ["match_phrase"=>["news_title"=>$keyword]];
                            }
                        }
                    }elseif($type == 'content'){
                        if($searchType == 'match' && mb_strlen($keyword) > 3){
                            $tmp = ["match"=>["news_content"=>["query"=>$keyword,"minimum_should_match"=>"80%"]]];
                        }else{
                            if(in_array($keyword,$fenciResult)){
                                $tmp = ["term"=>["news_content"=>$keyword]];
                            }else{
                                $tmp = ["match_phrase"=>["news_content"=>$keyword]];
                            }
                        }
                    }
                    $must_arr['bool']['must'][] = $tmp;
                }
                $keywordIncludeQuery[] = $must_arr;
            }
        }
        $this->condition['query']['bool']['minimum_should_match'] = 1;
        $keywordIncludeQuery != null ? $this->condition['query']['bool']['should'] = $keywordIncludeQuery:'';
        return $this;
    }

    public function keywordField($keywordGroups = [],$field='news_title',$type = 'full',$searchType = 'match_phrase')
    {
        $keywordIncludeQuery = null;
        if(!empty($keywordGroups)){
            $keywordIncludeQuery = [];
            $participles = [];
            foreach($keywordGroups as $keywordGroup){
                $participles = array_merge($participles,explode('+',$keywordGroup));
            }
            $fenciResult = $this->fenci($participles);
            foreach($keywordGroups as $keywordGroup){
                $keywords = explode('+',$keywordGroup);
                $must_arr = ['bool'=>['must'=>[]]];
                foreach($keywords as $keyword){
                    if($type == 'full'){
                        if($searchType == 'match' && mb_strlen($keyword) > 3){
                            $tmp['bool'] = [
                                'should' => [
                                    ["match"=>[$field=>["query"=>$keyword,"minimum_should_match"=>"80%"]]],
                                ]
                            ];
                        }else{
                            if(in_array($keyword,$fenciResult)){
                                $tmp['bool'] = [
                                    'should' => [
                                        ["term"=>[$field=>$keyword]],
                                    ]
                                ];
                            }else{
                                $tmp['bool'] = [
                                    'should' => [
                                        ["match_phrase"=>[$field=>["query" => $keyword,"slop" => 0]]],
                                    ]
                                ];
                            }
                        }
                    }else{
                        if($searchType == 'match' && mb_strlen($keyword) > 3){
                            $tmp = ["match"=>[$field=>["query"=>$keyword,"minimum_should_match"=>"80%"]]];
                        }else{
                            $tmp = ["match_phrase"=>[$field=>$keyword]];
                        }
                    }
                    $must_arr['bool']['must'][] = $tmp;
                }
                $keywordIncludeQuery[] = $must_arr;
            }
        }
        $this->condition['query']['bool']['minimum_should_match'] = 1;
        $keywordIncludeQuery != null ? $this->condition['query']['bool']['should'] = $keywordIncludeQuery:'';
        return $this;
    }

    public function keyword1($keywordGroups = [],$type = 'full',$searchType = 'match_phrase'){
        $keywordIncludeQuery = null;
        if(!empty($keywordGroups)){
            $keywordIncludeQuery = [];
            foreach($keywordGroups as $keywordGroup){
                $keywords = explode('+',$keywordGroup);
                $must_arr = ['bool'=>['must'=>[]]];
                foreach($keywords as $keyword){
                    if($type == 'full'){
                        if($searchType == 'match' && mb_strlen($keyword) > 3){
                            $tmp['bool'] = [
                                'should' => [
                                    ["match"=>["news_title"=>["query"=>$keyword,"minimum_should_match"=>"80%"]]],
                                    ["match"=>["news_content"=>["query"=>$keyword,"minimum_should_match"=>"80%"]]],
                                ]
                            ];
                        }else{
                            $tmp['bool'] = [
                                'should' => [
                                    ["match_phrase"=>["news_title"=>$keyword]],
                                    ["match_phrase"=>["news_content"=>$keyword]],
                                ]
                            ];
                        }
                    }else{
                        if($searchType == 'match' && mb_strlen($keyword) > 3){
                            $tmp = ["match"=>["news_title"=>["query"=>$keyword,"minimum_should_match"=>"80%"]]];
                        }else{
                            $tmp = ["match_phrase"=>["news_title"=>$keyword]];
                        }
                    }
                    $must_arr['bool']['must'][] = $tmp;
                }
                $keywordIncludeQuery[] = $must_arr;
            }
        }
        $this->condition['query']['bool']['minimum_should_match'] = 1;
        $keywordIncludeQuery != null ? $this->condition['query']['bool']['should'] = $keywordIncludeQuery:'';
        return $this;
    }

    /**
     * 排除词过滤
     * @param array $exclude_keywords
     * @return $this
     */
    public function keywordExclude($excludeKeywords = []){
        $keywordExcludeQuery = null;
        if(!empty($excludeKeywords)){
            $keywordExcludeQuery = [];
            $fenciResult = $this->fenci($excludeKeywords);
            foreach($excludeKeywords as $excludeKeyword){
                if(empty($excludeKeyword)) continue;
                if(in_array($excludeKeyword,$fenciResult)) {
                    $tmp = [
                        "bool" => [
                            'should' => [
                                ["term" => ["news_title" => $excludeKeyword]],
                                ["term" => ["news_content" => $excludeKeyword]],
                            ]
                        ]
                    ];
                }else{
                    $tmp = [
                        "multi_match" => [
                            "query" => $excludeKeyword,
                            "fields" => ["news_title","news_content"],
                            "operator" => "OR",
                            "type" => "phrase",
//                            "type" => "phrase_prefix",
//                            "max_expansions" => 150
//                                "minimum_should_match" => "100%"
                        ]
                    ];
                }
                $keywordExcludeQuery[] = $tmp;
            }
        }
        $keywordExcludeQuery != null ? $this->condition['query']['bool']['must_not'] = $keywordExcludeQuery:'';
        return $this;
    }

    /**
     * 敏感词查询
     * @param array $keyword_groups
     * @param string $type => [full,title]
     * @param string $searchType => [should,must]
     * @return $this
     */
    public function keywordSensitive($keywordSensitive= [],$type = 'full',$searchType = 'should'){
        $keywordSensitiveQuery = null;
        if(!in_array($searchType,['should','must'])){
            return $this;
        }
        if(!empty($keywordSensitive)) {
            $keywordSensitiveQuery = ['bool' => []];
            $fenciResult = $this->fenci($keywordSensitive);
            foreach ($keywordSensitive as $keyword) {
                if ($type == 'full') {
                    if(in_array($keyword,$fenciResult)) {
                        $tmp = [
                            ["term" => ["news_title" => $keyword]],
                            ["term" => ["news_content" => $keyword]]
                        ];
                    }else{
                        $tmp = [
                            "multi_match" => [
                                "query" => $keyword,
                                "fields" => ["news_title","news_content"],
                                "operator" => "OR",
                                "type" => "phrase"
                            ]
                        ];
                    }
                    $keywordSensitiveQuery['bool'][$searchType][] = $tmp;
                } else {
                    if(in_array($keyword,$fenciResult)) {
                        $tmp = ["term" => ["news_title" => $keyword]];
                    }else{
                        $tmp = ["match_phrase" => ["news_title" => $keyword]];
                    }
                    $keywordSensitiveQuery['bool'][$searchType][] = $tmp;
                }
            }
        }
        $keywordSensitiveQuery?$this->condition['query']['bool']['must'][] = $keywordSensitiveQuery:'';
        return $this;
    }

    /**
     * 排除文章
     * @return $this
     */
    public function newsExclude($platformUuids){
        $newsExcludeQuery = null;
        if(!empty($platformUuids)){
            foreach($platformUuids as $platformUuid){
                $tmp = [
                    "bool" => [
                        "must" => [
                            [
                                "term" => [
                                    'news_uuid' => $platformUuid['news_uuid'],
                                ]
                            ],
                            [
                                "term" => [
                                    'platform' => $platformUuid['platform'],
                                ]
                            ]
                        ]
                    ]
                ];
                $newsExcludeQuery["bool"]['must_not'][] = $tmp;
            }
        }
        $newsExcludeQuery?$this->condition['query']['bool']['filter'][] = $newsExcludeQuery:'';
        return $this;
    }

    /**
     * wildcard匹配基于分词结果，无法做到mysql的模糊查询
     */
    public function like($field,$like){
        $likeQuery = null;
        if($field && $like){
            $likeQuery = [
                "wildcard" => [
                    $field => "*{$like}*"
                ]
            ];
        }
        $likeQuery?$this->condition['query']['bool']['must'][] = $likeQuery:'';
        return $this;
    }

    /**
     *
     */
    public function phraseLike($fields,$like){
        $likeQuery = null;
        if($fields && $like){
            if (is_string($fields) || (is_array($fields) && count($fields) == 1)) {
                $field = is_string($fields) ? $fields : $fields[0];
                $likeQuery = [
                    "match_phrase_prefix" => [
                        $field => $like
                    ]
                ];
            }else{
                $likeQuery = [
                    "multi_match" => [
                        "query" => $like,
                        "fields" => $fields,
                        "operator" => "OR",
                        "type" => "phrase_prefix",
//                        "minimum_should_match" => "60%"
                    ]
                ];
            }
        }
        $likeQuery?$this->condition['query']['bool']['must'][] = $likeQuery:'';
        return $this;
    }

    public function searchAll($keyword){
        $tmp = [
            'bool' =>[
                'should' => [
                    [
                        "multi_match" => [
                            "query" => $keyword,
                            "fields" => ['news_title','news_content'],
                            "operator" => "OR",
                            "type" => "phrase_prefix",
//                        "minimum_should_match" => "60%"
                        ]
                    ],
                    [
                        'bool' => [
                            'should' => [
                                [
                                    'term' => [
                                        self::MEDIA_NAME => $keyword
                                    ]
                                ],
                                [
                                    'term' => [
                                        self::PLATFORM_NAME => $keyword
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $this->condition['query']['bool']['filter'][] = $tmp;
        return $this;

    }

    public function source($like){
        $sourceQuery = null;
        if($like){
            $sourceQuery = [
                'bool' => [
                    'should' => [
                        [
                            'term' => [
                                self::MEDIA_NAME => $like
                            ]
                        ],
                        [
                            'term' => [
                                self::PLATFORM_NAME => $like
                            ]
                        ]
                    ]
                ]
            ];
        }
        $sourceQuery?$this->condition['query']['bool']['filter'][] = $sourceQuery:'';
        return $this;
    }

    /**
     * 文章发布时间筛选，开始时间和结束时间不需要同时存在
     * @param null $begin_time
     * @param null $end_time
     * @return $this
     */
    public function range( $field, $start = null,$end = null){
        $timeRange = null;
        $timeRange['range'][$field]['gte'] = $start;
        $end && $timeRange['range'][$field]['lte'] = $end;
        $this->condition['query']['bool']['filter'][] = $timeRange;
        return $this;
    }

    /**
     * 文章发布时间筛选，开始时间和结束时间不需要同时存在
     * @param null $begin_time
     * @param null $end_time
     * @return $this
     */
    public function posttime($begin_time = null,$end_time = null){
        $timeRange = null;
//        if($begin_time && $begin_time == date('Y-m-d H:i:s',strtotime($begin_time))){
        if($begin_time && strtotime($begin_time)){
            $this->startTime = $begin_time >= $this->startTime
                ? ($begin_time == date('Y-m-d',strtotime($begin_time)) ? "$begin_time 00:00:00" : $begin_time) : $this->startTime;
            $timeRange['range']['news_posttime']['gte'] = $begin_time == date('Y-m-d',strtotime($begin_time)) ? "$begin_time 00:00:00" : $begin_time;
        }
//        if($end_time && $end_time == date('Y-m-d H:i:s',strtotime($end_time))){
        if($end_time && strtotime($end_time)){
            $this->endTime = $end_time == date('Y-m-d',strtotime($end_time)) ? "$end_time 23:59:59" : $end_time;
            $timeRange['range']['news_posttime']['lte'] = $end_time == date('Y-m-d',strtotime($end_time)) ? "$end_time 23:59:59" : $end_time;
        }
        $timeRange != null ? $this->condition['query']['bool']['filter'][] = $timeRange:'';
        return $this;
    }

    /**
     * 初始化发布时间
     */
    public function initPostTime() {
        if(!isset($this->condition['query']['bool']['filter'])) {
            return;
        }
        $filter = $this->condition['query']['bool']['filter'];
        $newFilter = [];
        if($filter) {
            foreach ($filter as $item) {
                if(isset($item['range']['news_posttime']['lte']) && !$item['range']['news_posttime']['lte']) {
                    continue;
                } else if(isset($item['range']['news_posttime']['gte']) && !$item['range']['news_posttime']['gte']) {
                    continue;
                }
                $newFilter[] = $item;
            }
            unset($item);
        }
        $this->condition['query']['bool']['filter'] = $newFilter;
    }

    /**
     * 获取错误的发布时间
     * @return array
     */
    public function getErrorPostTime() {
        $filter = isset($this->condition['query']['bool']['filter'])?$this->condition['query']['bool']['filter']:[];
        $timeArr = [];
        if($filter) {
            foreach ($filter as $item) {
                if(isset($item['range']['news_posttime']['lte']) && !$item['range']['news_posttime']['lte']) {
                    $timeArr = $item['range']['news_posttime'];
                    break;
                } else if(isset($item['range']['news_posttime']['gte']) && !$item['range']['news_posttime']['gte']) {
                    $timeArr = $item['range']['news_posttime'];
                    break;
                }
            }
            unset($item);
        }
        return $timeArr;
    }

    /**
     * 文章发布日期筛选，开始时间和结束时间不需要同时存在
     * @param null $begin_date
     * @param null $end_date
     * @return $this
     */
    public function postdate($begin_date = null,$end_date = null){
        $dateRange = null;
        if($begin_date && $begin_date == date('Y-m-d',strtotime($begin_date))){
            $this->startTime = $begin_date;
            $dateRange['range']['news_postdate']['gte'] = $begin_date;
        }
        if($end_date && $end_date == date('Y-m-d',strtotime($end_date))){
            $this->endTime = $end_date;
            $dateRange['range']['news_postdate']['lte'] = $end_date;
        }
        $dateRange != null ? $this->condition['query']['bool']['filter'][] = $dateRange:'';
        return $this;
    }

    /**
     * 文章抓取时间筛选，开始时间和结束时间不需要同时存在
     * @param null $begin_time
     * @param null $end_time
     * @return $this
     */
    public function fetchtime($begin_time = null,$end_time = null){
        $timeRange = null;
        if($begin_time && $begin_time == date('Y-m-d H:i:s',strtotime($begin_time))){
            $this->startTime = $begin_time;
            $timeRange['range']['news_fetch_time']['gte'] = $begin_time;
        }
        if($end_time && $end_time == date('Y-m-d H:i:s',strtotime($end_time))){
            $this->endTime = $end_time;
            $timeRange['range']['news_fetch_time']['lte'] = $end_time;
        }
        $timeRange != null ? $this->condition['query']['bool']['filter'][] = $timeRange:'';
        return $this;
    }

    /**
     * 文章入库时间筛选，开始时间和结束时间不需要同时存在
     * @param null $begin_time
     * @param null $end_time
     * @return $this
     */
    public function entertime($begin_time = null,$end_time = null){
        $timeRange = null;
        if($begin_time && $begin_time == date('Y-m-d H:i:s',strtotime($begin_time))){
            $this->startTime = $begin_time;
            $timeRange['range']['solr_create_time']['gte'] = $begin_time;
        }
        if($end_time && $end_time == date('Y-m-d H:i:s',strtotime($end_time))){
            $this->endTime = $end_time;
            $timeRange['range']['solr_create_time']['lte'] = $end_time;
        }
        $timeRange != null ? $this->condition['query']['bool']['filter'][] = $timeRange:'';
        return $this;
    }

    /**
     * 索引时间限制
     * @param null $begin_time
     * @param null $end_time
     * @return $this
     */
    public function indextime($begin_time = null,$end_time = null){
        if($begin_time){
            $this->startTime = $begin_time;
        }
        if($end_time){
            $this->endTime = $end_time;
        }
        return $this;
    }

    /**
     * 文章情感属性 【正面，中性，负面，敏感】
     * 支持传入字符串，数组
     * @param array $emotions
     * @return $this
     */
    public function emotion($emotions = []){
        $emotionRange = null;
        if(!empty($emotions)){
            if(is_string($emotions) || (is_array($emotions) && count($emotions) == 1)){
                $emotion = is_string($emotions)?$emotions:$emotions[0];
                $emotionRange = [
                    'term' => [
                        'news_emotion' => $emotion
                    ]
                ];
            }else{
                if(count(array_values(array_unique($emotions))) != 3){
                    $emotionRange = [
                        'terms' => [
                            'news_emotion' => $emotions
                        ]
                    ];
                }
            }
        }
        $emotionRange != null ? $this->condition['query']['bool']['filter'][] = $emotionRange:'';
        return $this;
    }

//    public function emotion($emotions = []){
//        $emotionRange = null;
//        if(!empty($emotions)){
//            $emotionRange = [
//                "range" => []
//            ];
//            if(is_string($emotions) || (is_array($emotions) && count($emotions) == 1)){
//                $emotion = is_string($emotions)?$emotions:$emotions[0];
//                switch($emotion){
//                    case '正面':
//                        $emotionRange['range']['news_positive'] = [
//                            'gte' => self::EMOTION_POSITIVE
//                        ];
//                        break;
//                    case '中性':
//                        $emotionRange['range']['news_positive'] = [
//                            'gt' => self::EMOTION_NEGATIVE,
//                            'lt' => self::EMOTION_POSITIVE
//                        ];
//                        break;
//                    case '负面':
//                        $emotionRange['range']['news_positive'] = [
//                            'gt' => self::EMOTION_SENSITIVE,
//                            'lte' => self::EMOTION_NEGATIVE
//                        ];
//                        break;
//                    case '敏感':
//                        $emotionRange['range']['news_positive'] = [
//                            'lte' => self::EMOTION_SENSITIVE
//                        ];
//                        break;
//                }
//            }else{
//                $emotionRange = [
//                    "bool" => [
//                        "should" => [
//
//                        ]
//                    ]
//                ];
//                foreach($emotions as $emotion){
//                    switch($emotion){
//                        case '正面':
//                            $emotionRange['bool']['should'][] = [
//                                "range" => [
//                                    "news_positive" => [
//                                        'gte' => self::EMOTION_POSITIVE
//                                    ]
//                                ]
//                            ];
//                            break;
//                        case '中性':
//                            $emotionRange['bool']['should'][] = [
//                                "range" => [
//                                    "news_positive" => [
//                                        'gt' => self::EMOTION_NEGATIVE,
//                                        'lt' => self::EMOTION_POSITIVE
//                                    ]
//                                ]
//                            ];
//                            break;
//                        case '负面':
//                            $emotionRange['bool']['should'][] = [
//                                "range" => [
//                                    "news_positive" => [
//                                        'gt' => self::EMOTION_SENSITIVE,
//                                        'lte' => self::EMOTION_NEGATIVE
//                                    ]
//                                ]
//                            ];
//                            break;
//                        case '敏感':
//                            $emotionRange['bool']['should'][] = [
//                                "range" => [
//                                    "news_positive" => [
//                                        'lte' => self::EMOTION_SENSITIVE
//                                    ]
//                                ]
//                            ];
//                            break;
//                    }
//                }
//            }
//        }
//        $emotionRange != null ? $this->condition['query']['bool']['filter'][] = $emotionRange:'';
//        return $this;
//    }

    /**
     * 媒体平台类型【web,wx,weibo,app,journal,forum】
     * 支持传入字符串，数组
     * @param array $platformTypes
     * @return $this
     */
    public function platformType($platformTypes = []){
        $platformTypeRange = null;
        if(!empty($platformTypes)){
            if(is_string($platformTypes) || (is_array($platformTypes) && count($platformTypes) == 1)) {
                $platformType = is_string($platformTypes) ? $platformTypes : $platformTypes[0];
                $this->index = $platformType;
                $platformTypeRange = [
                    'term' => [
                        'platform' => $platformType
                    ]
                ];
            }else{
                $this->index = implode(',',$platformTypes);
                $platformTypeRange = [
                    'terms' => [
                        'platform' => $platformTypes
                    ]
                ];
            }
        }
        $platformTypeRange != null ? $this->condition['query']['bool']['filter'][] = $platformTypeRange:'';
        return $this;
    }

    /**
     * 文章发布媒体
     * 支持传入字符串，数组
     * @param array $mediaNames
     * @return $this
     */
    public function mediaName($mediaNames = []){
        $mediaNameRange = null;
        if(!empty($mediaNames)){
            if(is_string($mediaNames) || (is_array($mediaNames) && count($mediaNames) == 1)) {
                $mediaName = is_string($mediaNames) ? $mediaNames : $mediaNames[0];
                $mediaNameRange = [
                    'term' => [
                        'media_name' => $mediaName
                    ]
                ];
            }else{
                $mediaNameRange = [
                    'terms' => [
                        'media_name' => $mediaNames
                    ]
                ];
            }
        }
        $mediaNameRange != null ? $this->condition['query']['bool']['filter'][] = $mediaNameRange:'';
        return $this;
    }

    /**
     * 省份
     * @param $provinces
     * @return $this
     */
    public function province($provinces){
        $provinceRange = null;
        if(!empty($provinces)){
            if(is_string($provinces) || (is_array($provinces) && count($provinces) == 1)) {
                $province = is_string($provinces) ? $provinces : $provinces[0];
                $provinceRange = [
                    'term' => [
                        self::MEDIA_PROVINCE => $province
                    ]
                ];
            }else{
                $provinceRange = [
                    'terms' => [
                        self::MEDIA_PROVINCE => $provinces
                    ]
                ];
            }
        }
        $provinceRange != null ? $this->condition['query']['bool']['filter'][] = $provinceRange:'';
        return $this;
    }

    /**
     * 城市
     * @param $citys
     * @return $this
     */
    public function city($citys){
        $cityRange = null;
        if(!empty($citys)){
            if(is_string($citys) || (is_array($citys) && count($citys) == 1)) {
                $city = is_string($citys) ? $citys : $citys[0];
                $cityRange = [
                    'term' => [
                        self::MEDIA_CITY => $city
                    ]
                ];
            }else{
                $cityRange = [
                    'terms' => [
                        self::MEDIA_CITY => $citys
                    ]
                ];
            }
        }
        $cityRange != null ? $this->condition['query']['bool']['filter'][] = $cityRange:'';
        return $this;
    }

    public function contentArea($areas){
        $tmp = $tmpRange = [];
        if(!empty($areas['province'])){
            $tmp['tmpProvinces'] = ['terms' => ["news_content_province" => $areas['province']]];
        }
        if(!empty($areas['city'])){
            $tmp['tmpCitys'] = ['terms' => ["news_content_city" => $areas['city']]];
        }
        if(!empty($areas['county'])){
            $tmp['tmpCountys'] = ['terms' => ["news_content_county" => $areas['county']]];
        }
        if(count($tmp) > 1){
            if(isset($tmp['tmpProvinces'])) $tmpRange['bool']['should'][] = $tmp['tmpProvinces'];
            if(isset($tmp['tmpCitys'])) $tmpRange['bool']['should'][] = $tmp['tmpCitys'];
            if(isset($tmp['tmpCountys'])) $tmpRange['bool']['should'][] = $tmp['tmpCountys'];
        }else{
            if(isset($tmp['tmpProvinces'])) $tmpRange = $tmp['tmpProvinces'];
            if(isset($tmp['tmpCitys'])) $tmpRange = $tmp['tmpCitys'];
            if(isset($tmp['tmpCountys'])) $tmpRange = $tmp['tmpCountys'];
        }
        if($tmpRange) $this->condition['query']['bool']['filter'][] = $tmpRange;
        return $this;
    }

    /**
     * 重点关注信源
     * @param $infoSourses
     * @param null $platform
     * @return $this
     */
    public function followInfoSource($infoSourses,$platform = null){
        $config = Yii::$app->params['infoSource'];
        if(is_string($platform)){
            $platform = [$platform];
        }
        if(is_array($platform)){
            if(in_array('other',$platform)){
                $platform = array_merge(array_diff($platform, ['other']),Yii::$app->params['otherIndex']);
            }
            $infoSourses = array_filter($infoSourses,function($v)use($platform){
                return in_array($v['platform'],$platform);
            });
        }
        if($infoSourses){
            $tmpInfoSource = [];
            foreach($infoSourses as $infoSourse){
                $field = $config[$infoSourse['platform']];
                $tmp = [
                    "bool" => [
                        "must" => array_map(function($v)use($infoSourse){
                            return [
                                "term" => [
                                    $v => $infoSourse[$v]
                                ]
                            ];
                        },$field)
                    ]
                ];
                $tmpInfoSource['bool']['should'][] = $tmp;
            }
        }else{
            //设置类似mysql 1!=1
            $tmpInfoSource['term']['aaaaaaa'] = 'aaaaaaa';
        }
        $tmpInfoSource ? $this->condition['query']['bool']['filter'][] = $tmpInfoSource:'';
        return $this;
    }

    /**
     * 排除信源
     * @param $infoSourses
     * @param null $platform
     * @return $this
     */
    public function excludeInfoSource($infoSourses,$platform = null){
        $config = Yii::$app->params['infoSource'];
        if(is_string($platform)){
            $platform = [$platform];
        }
        if(is_array($platform)){
            if(in_array('other',$platform)){
                $platform = array_merge(array_diff($platform, ['other']),Yii::$app->params['otherIndex']);
            }
            $infoSourses = array_filter($infoSourses,function($v)use($platform){
                return in_array($v['platform'],$platform);
            });
        }
        $tmpInfoSource = [];
        foreach($infoSourses as $infoSourse){
            $field = $config[$infoSourse['platform']];
            $tmp = [
                "bool" => [
                    "must" => array_map(function($v)use($infoSourse){
                        return [
                            "term" => [
                                $v => $infoSourse[$v]
                            ]
                        ];
                    },$field)
                ]
            ];
            $tmpInfoSource['bool']['must_not'][] = $tmp;
        }
        $tmpInfoSource ? $this->condition['query']['bool']['filter'][] = $tmpInfoSource:'';
        return $this;
    }

    /**
     * 文章唯一标识news_uuid
     * 支持传入字符串，数组
     * @param array $newsUuids
     * @return $this
     */
    public function newsUuid($newsUuids = []){
        $newsUuidRange = null;
        if(!empty($newsUuids)) {
            if (is_string($newsUuids) || (is_array($newsUuids) && count($newsUuids) == 1)) {
                $newsUuid = is_string($newsUuids) ? $newsUuids : $newsUuids[0];
                $newsUuidRange = [
                    "term" => [
                        "news_uuid" => $newsUuid
                    ]
                ];
            }else{
                $newsUuidRange = [
                    "terms" => [
                        "news_uuid" => $newsUuids
                    ]
                ];
            }
        }
        $newsUuidRange != null ? $this->condition['query']['bool']['filter'][] = $newsUuidRange:'';
        return $this;
    }

    public function platformUuid($platformUuids){
        if(!empty($platformUuids)){
            foreach($platformUuids as $platformUuid){
                $tmp = [
                    "bool" => [
                        "must" => [
                            [
                                "term" => [
                                    'news_uuid' => $platformUuid['news_uuid'],
                                ]
                            ],
                            [
                                "term" => [
                                    'platform' => $platformUuid['platform'],
                                ]
                            ]
                        ]
                    ]
                ];
                $this->condition['query']['bool']['filter']['bool']['should'][] = $tmp;
            }
        }
        return $this;
    }

    /**
     * 相似文章查询sim_hash
     * 支持传入字符串，数组
     * @param array $simHashs
     * @return $this
     */
    public function simHash($simHashs = []){
        $simHashRange = null;
        if(!empty($simHashs)) {
            if (is_string($simHashs) || (is_array($simHashs) && count($simHashs) == 1)) {
                $simHash = is_string($simHashs) ? $simHashs : $simHashs[0];
                $simHashRange = [
                    "term" => [
                        "news_sim_hash" => $simHash
                    ]
                ];
            }else{
                $simHashRange = [
                    "terms" => [
                        "news_sim_hash" => $simHashs
                    ]
                ];
            }
        }
        $simHashRange != null ? $this->condition['query']['bool']['filter'][] = $simHashRange:'';
        return $this;
    }

    /**
     * 条件查询
     * 支持传入字符串，数组
     * @param array $where
     * @return $this
     */
    public function where($field,$wheres = []){
        $whereRange = null;
        if(!empty($wheres)) {
            if (is_string($wheres) || is_numeric($wheres) || (is_array($wheres) && count($wheres) == 1)) {
                $where = !is_array($wheres) ? $wheres : $wheres[0];
                $whereRange = [
                    "term" => [
                        $field => $where
                    ]
                ];
            }else{
                $whereRange = [
                    "terms" => [
                        $field => $wheres
                    ]
                ];
            }
        }
        $whereRange != null ? $this->condition['query']['bool']['filter'][] = $whereRange:'';
        return $this;
    }

    /**
     * 条件or查询
     * 支持传入字符串，数组
     * @param array $whereOr
     * @return $this
     */
    public function whereOr($conditions = []){
        $whereRange = null;
        if(!empty($conditions)) {
            foreach($conditions as $field => $condition){
                if(is_array($condition)){
                    $whereRangeTmp = [
                        "terms" => [
                            $field => $condition
                        ]
                    ];
                }else{
                    $whereRangeTmp = [
                        "term" => [
                            $field => $condition
                        ]
                    ];
                }
                $whereRange['bool']['should'][] = $whereRangeTmp;
            }
        }
        $whereRange != null ? $this->condition['query']['bool']['filter'][] = $whereRange:'';
        return $this;
    }

    /**
     * 排除条件查询
     * 支持传入字符串，数组
     * @param array $where
     * @return $this
     */
    public function whereNot($fieldOrCondition,$wheres = []){
        $whereRange = null;
        if(is_array($fieldOrCondition)){    // 多条件排除,排除条件之间为and关系,只接受$fieldOrCondition一个参数
            foreach($fieldOrCondition as $key => $v){
                if(is_array($v)){
                    $whereRangeTmp = [
                        "terms" => [
                            $key => $v
                        ]
                    ];
                }else{
                    $whereRangeTmp = [
                        "term" => [
                            $key => $v
                        ]
                    ];
                }
                $whereRange['bool']['must_not']['bool']['must'][] = $whereRangeTmp;
            }
        }else{
            if(!empty($wheres)) {
                if (is_string($wheres) || is_numeric($wheres) || (is_array($wheres) && count($wheres) == 1)) {
                    $where = !is_array($wheres) ? $wheres : $wheres[0];
                    $whereRange = [
                        "bool" => [
                            "must_not" => [
                                "term" => [
                                    $fieldOrCondition => $where
                                ]
                            ]
                        ]
                    ];
                }else{
                    $whereRange = [
                        "bool" => [
                            "must_not" => [
                                "terms" => [
                                    $fieldOrCondition => $wheres
                                ]
                            ]
                        ]
                    ];
                }
            }
        }

        $whereRange != null ? $this->condition['query']['bool']['filter'][] = $whereRange:'';
        return $this;
    }

    /**
     * 求和
     * @param $field
     * @return $this
     */
    public function sum($field){
        $this->dataType = 'sum';
        $this->sumField = $field;
        $tmp = [
            $field => [
                "sum" => [
                    "field" => $field
                ]
            ]
        ];
        $this->condition['aggs'] = $tmp;
        return $this;
    }

    /**
     * 过滤空值
     * @param $field
     * @return $this
     */
    public function exists($field){
        $this->condition['query']['bool']['filter'][] = [
            "bool" => [
                "must_not" => [
                    [
                        "term" => [
                            $field => ""
                        ]
                    ]
                ]
            ]
        ];
        $this->condition['query']['bool']['filter'][] = [
            "exists" => [
                "field" => $field
            ]
        ];
        return $this;
    }


    /**
     * 高亮显示
     * @param array $fields
     * @param string $pre_tags
     * @param string $post_tags
     * @return $this
     */
    public function highLight($fields = [],$pre_tags = '<em>', $post_tags = '</em>'){
        $this->hightLightFields = $fields;
        $newFieldsArr = [];
        foreach($fields as $field){
//            $newFieldsArr[$field] = new \stdClass();
            $newFieldsArr[$field] = ['number_of_fragments' => 0];
        }
        $this->condition['highlight'] = [
            "require_field_match" => false,
            "pre_tags" => [
                $pre_tags
            ],
            "post_tags" => [
                $post_tags
            ],
            "fields" => $newFieldsArr
        ];
        return $this;
    }

    /**
     * 要查询的字段
     * @param $fields
     * @return $this
     */
    public function select($fields){
        $this->fields = $fields;
        $this->condition['_source'] = $this->fields;
        return $this;
    }

    /**
     * 追加字段
     * @param $fields
     * @return $this
     */
    public function appendFields($fields){
        $this->fields = array_values(array_unique(array_merge(self::fields() ,$fields)));
        $this->condition['_source'] = $this->fields;
        return $this;
    }

    /**
     * 单字段分组聚合
     * @param $field
     * @param null $fieldAs
     * @return $this
     */
    public function group($field,$fieldAs = null,$size = null,$topHits = null,$sortBy = "solr_create_time"){
        $this->size(0);
        if($topHits){
            $this->dataType = 'groupTop';
        }else{
            $this->dataType = 'group';
        }
        $fieldAs = $fieldAs??$field;
        $this->groupField = $fieldAs;
        $tmp = [
            "terms" => [
                "field" => $field
            ]
        ];
        $size?$tmp['terms']['size'] = $size:null;
        $topHits?$tmp['aggs']['top_data']['top_hits'] = [
            "size" => $topHits,
            "sort" => [
                $sortBy => ["order" => "desc"]
            ]
        ]:null;
        $topHits && $this->fields?$tmp['aggs']['top_data']['top_hits']["_source"] = $this->fields:null;
        $this->condition['aggs'][$fieldAs] = $tmp;
        return $this;
    }

    /**
     * 分组内去重统计
     * @param $field1
     * @param $field2
     * @return $this
     */
    public function groupUnique($field1,$size = null,$field2){
        $this->size(0);
        $this->dataType = 'groupUnique';
        $tmp = [
            "terms" => [
                "field" => $field1
            ],
            "aggs" => [
                'group2' => [
                    "cardinality" => [
                        "field" => $field2
                    ]
                ]
            ]
        ];
        $size?$tmp['terms']['size'] = $size:null;
        $this->condition['aggs']['group1'] = $tmp;
        return $this;
    }

    public function collapse($field){
        $this->condition['collapse'] = ['field' => $field];
        return $this;
    }

    /**
     * 单字段去重统计（获取分组的个数）
     * @param $field
     * @return $this
     */
    public function groupCount($field){
        $this->size(0);
        $this->dataType = "groupCount";
        $tmp = [
            "group_count" => [
                "cardinality" => [
                    "field" => $field
                ]
            ]
        ];
        $this->condition['aggs'] = $tmp;
        return $this;
    }

    public function groupSum($GroupField,$sumField){
        $this->size(0);
        $this->dataType = "groupSum";
        $tmp = [
            "group" => [
                "terms" => [
                    "field" => $GroupField
                ],
                "aggs" => [
                    "group_sum" => [
                        "sum" => ["field" => $sumField]
                    ]
                ]
            ]
        ];
        $this->condition['aggs'] = $tmp;
        return $this;
    }

    public function group1($field,$field1){
        $this->size(0);
        $tmp = [
            "group_media" => [
                "terms" => [
                    "field" => $field
                ],
                "aggs" => [
                    "max_CI" => [
                        "max" => ["field" => $field1],
                    ],
//                    "avg_CI" => [
//                        "avg" => ["field" => $field1],
//                    ],
//                    "0-500" => [
//                        "bucket_selector" => [
//                            "buckets_path" => [
//                                "vars" => "max_CI"
//                            ],
//                            "script" => "params.vars < 500"
//                        ]
//                    ]
                ]
            ],
//            "aaaaa" => [
//                "bucket_selector" => [
//                    "buckets_path" => [
//                        "my_var1" => "group_media>max_CI.value"
//                    ],
//                    "script" => "params.my_var1 <= 500"
//                ]
//            ],
//            "bbbbb" => [
//                "bucket_selector" => [
//                    "buckets_path" => [
//                        "my_var1" => "group_media>max_CI.value"
//                    ],
//                    "script" => "params.my_var1 > 500"
//                ]
//            ],
//            "aaaaa" => [
//                "range" => [
//                    "bucket_path" => "group_media>max_CI",
//                    "ranges" => [
//                        ["to" => 500],
//                        ["from" => 500,"to" => 1000],
//                        ["from" => 1000,"to" => 1500],
//                        ["from" => 1500],
//                    ]
//                ]
//            ]
        ];
        $this->condition['aggs'] = $tmp;
        return $this;
    }

    public function groupGroup($field1,$field2,$size1 = null,$size2 = null){
        $this->size(0);
        $tmp = [
            $field1 => [
                "terms" => [
                    'field' => $field1,
                ],
                'aggs' => [
                    $field2 => [
                        "terms" => [
                            "field" => $field2
                        ]
                    ]
                ]
            ]
        ];
        $size1?$tmp[$field1]["terms"]["size"] = $size1:null;
        $size2?$tmp[$field1]["aggs"][$field2]["terms"]["size"] = $size2:null;
        $this->condition['aggs'] = $tmp;
        return $this;
    }

    /**
     * 按照时间统计数据，配合posttime和postdate方法使用
     * $type = 【day,hour】
     * @return $this
     */
    public function dateGroup($type = 'day'){
        $this->size(0);
        $this->dataType = 'dateGroup';
        switch($type){
            case 'day':
                $interval = "day";
                $format = "yyyy-MM-dd";
                $min_bound = date('Y-m-d',strtotime($this->startTime));
                $max_bound = date('Y-m-d',strtotime($this->endTime));
                break;
            case 'hour':
                $interval = "hour";
                $format = "yyyy-MM-dd HH";
                $min_bound = date('Y-m-d H',strtotime($this->startTime));
                $max_bound = date('Y-m-d H',strtotime($this->endTime));
                break;
            default:
                $interval = "day";
                $format = "yyyy-MM-dd";
                $min_bound = date('Y-m-d',strtotime($this->startTime));
                $max_bound = date('Y-m-d',strtotime($this->endTime));
                break;
        }
        $this->condition['aggs']['group_by_date'] =
            [
                "date_histogram" => [
                    "field" => "news_posttime", // news_postdate  没有结果，难道不是date类型？
                    "interval"=> $interval,
                    "format"=> $format,
                    "min_doc_count" => 0,
                    "extended_bounds" => [
                        "min" => $min_bound,
                        "max" => $max_bound
                    ]
                ],
            ];
        return $this;
    }

    /**
     * 按照日期和媒体平台来统计数据，配合(posttime或postdate)和platformType方法使用
     * @return $this
     */
    public function datePlatformGroup($type1 = 'date',$type2 = 'day',$size = 30){
        $this->size(0);
        switch($type2){
            case 'day':
                $interval = "day";
                $format = "yyyy-MM-dd";
                $min_bound = date('Y-m-d',strtotime($this->startTime));
                $max_bound = date('Y-m-d',strtotime($this->endTime));
                break;
            case 'hour':
                $interval = "hour";
                $format = "yyyy-MM-dd HH";
                $min_bound = date('Y-m-d H',strtotime($this->startTime));
                $max_bound = date('Y-m-d H',strtotime($this->endTime));
                break;
            default:
                $interval = "day";
                $format = "yyyy-MM-dd";
                $min_bound = date('Y-m-d',strtotime($this->startTime));
                $max_bound = date('Y-m-d',strtotime($this->endTime));
                break;
        }

        if($type1 == 'date'){
            $this->dataType = 'datePlatformGroup';
            $this->condition['aggs']['group_alias'] =
                [
                    "date_histogram" => [
                        "field" => "news_posttime", // news_postdate  没有结果，难道不是date类型？
                        "interval"=> $interval,
                        "format"=> $format,
                        "min_doc_count" => 0,
                        "extended_bounds" => [
                            "min" => $min_bound,
                            "max" => $max_bound
                        ]
                    ],
                    "aggs" => [
                        "group_alias" => [
                            "terms" => [
                                "size"=> $size,
                                "field" => "platform",
                                "min_doc_count" => 0
                            ]
                        ]
                    ]
                ];
        }else{
            $this->dataType = 'platformDateGroup';
            $this->condition['aggs']['group_alias'] =
                [
                    "terms" => [
                        "size"=> $size,
                        "field" => "platform",
                        "min_doc_count" => 0
                    ],
                    "aggs" => [
                        "group_alias" => [
                            "date_histogram" => [
                                "field" => "news_posttime", // news_postdate  没有结果，难道不是date类型？
                                "interval"=> $interval,
                                "format"=> $format,
                                "min_doc_count" => 0,
                                "extended_bounds" => [
                                    "min" => $min_bound,
                                    "max" => $max_bound
                                ]
                            ],
                        ]
                    ]
                ];
        }
//        $this->condition['aggs']['size'] = 30;
        return $this;
    }

    /**
     * 设置condition条件
     * @param $condition
     * @return $this
     */
    public function setCondition($condition){
        if(is_array($condition)){
            $this->condition = $condition;
        }else{
            $this->condition = json_decode($condition,true);
        }
        return $this;
    }



    /**
     * @return $this
     */
    public function count(){
        $this->size(0);
        $this->dataType = 'count';
        return $this;
    }

    /**
     * 执行查询并返回数据
     * 默认使用缓存$useCache = true
     * @return mixed
     */
    public function query($useCache = true,$duration = 120){
        $condition = json_encode($this->condition,JSON_UNESCAPED_UNICODE);
        $cacheKey = md5($this->index.$condition);
        /*    if($useCache && $this->result = $this->cache->get($cacheKey)){
                QueryLog::setAndSave(UserService()->getUid(),$this->index,$condition,QueryLog::FROM_CACHE);
                return $this->result;
            }
            QueryLog::setAndSave(UserService()->getUid(),$this->index,$condition,QueryLog::FROM_ES);
        */
        $this->fetch();
        $this->decodeResponse = $this->response!==false?json_decode($this->response,true):false;
        if($this->scroll){
            $this->scrollId = $this->decodeResponse['_scroll_id'];
        }
        switch($this->dataType){
            case 'list':
                $this->getLists();
                break;
            case 'sum':
                $this->getSum();
                break;
            case 'count':
                $this->getTotal();
                break;
            case 'group':
                $this->getGroup();
                break;
            case 'groupCount':
                $this->getGroupCount();
                break;
            case 'groupSum':
                $this->getGroupSum();
                break;
            case 'groupTop':
                $this->getGroupTop();
                break;
            case 'groupUnique':
                $this->getGroupUnique();
                break;
            case 'emotionGroup':
                $this->getEmotionGroup();
                break;
            case 'dateGroup':
                $this->getDateGroup();
                break;
            case 'datePlatformGroup':
                $this->getDatePlatformGroup();
                break;
            case 'platformDateGroup':
                $this->getDatePlatformGroup('platform');
                break;
            case 'ciRangeGroup':
                $this->getCiRangeGroup();
                break;
            default:
                $fun = "get".$this->dataType;
                $this->$fun();
        }

        $useCache && $this->cache->set($cacheKey,$this->result,$duration);
        return $this->result;
    }


    /**
     * @param int $flag
     * @return $this
     */
    public function saveCondition($flag){
        $this->conditionStore[$flag] =  $this->condition;
        return $this;
    }

    /**
     * @param int $flag
     * @return $this
     */
    public function useCondition($flag){
        $this->condition = $this->conditionStore[$flag] ;
        return $this;
    }

    public function reset()
    {
        $this->index = 'web';
        $this->fields = [];
        $this->condition = [];
        $this->response = null;
        $this->result = null;
        $this->hightLightFields = null;
        $this->dataType = 'list';
        $this->groupField = null;
        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getCondition()
    {
        return $this->condition;
    }

    public function getScrollId()
    {
        return $this->scrollId;
    }

    /**
     * 文章列表及总数
     * @return array
     */
    private function getLists(){
        $this->result = [
            'numFound' => 0,
            'newsList' => []
        ];
        if($this->response === false) return $this->result;
        $data = $this->decodeResponse;
        if(isset($data['hits']['total']) && $data['hits']['total'] > 0){
            $this->result['numFound'] = $data['hits']['total'];
            foreach($data['hits']['hits'] as $hit){
                $this->result['newsList'][] = $this->decorate($hit);
            }
        }
        return $this->result;
    }

    /**
     * 文章总数
     * @return int
     */
    private function getTotal(){
        $this->result = 0;
        if($this->response === false) return $this->result;
        $data = $this->decodeResponse;
        if(isset($data['hits']['total']) && $data['hits']['total'] > 0){
            $this->result = $data['hits']['total'];
        }
        return $this->result;
    }

    private function getSum(){
        $this->result = 0;
        if($this->response === false) return $this->result;
        $data = $this->decodeResponse;
        if(isset($data['aggregations'][$this->sumField])){
            $this->result = $data['aggregations'][$this->sumField]['value'];
        }
        return $this->result;
    }

    /**
     * 分组数据
     * @return array
     */
    private function getGroup(){
        $this->result = [];
        if($this->response === false) return $this->result;
        $data = $this->decodeResponse;
        if(isset($data['aggregations'][$this->groupField])){
            foreach($data['aggregations'][$this->groupField]['buckets'] as $v){
                $this->result[$v['key']] = $v['doc_count'];
            }
        }
        return $this->result;
    }

    /**
     * 获取分组个数
     * @return array
     */
    private function getGroupCount(){
        $this->result = 0;
        if($this->response === false) return $this->result;
        $data = $this->decodeResponse;
        if(isset($data['aggregations']['group_count'])){
            $this->result = $data['aggregations']['group_count']['value'];
        }
        return $this->result;
    }

    /**
     * 获取分组内和
     * @return array
     */
    private function getGroupSum(){
        $this->result = [];
        if($this->response === false) return $this->result;
        $data = $this->decodeResponse;
        if(isset($data['aggregations']['group'])){
            foreach($data['aggregations']['group']['buckets'] as $k => $v){
                $this->result[$v['key']] = [
                    'doc_count' => $v['doc_count'],
                    'sum' => $v['group_sum']['value']
                ];
            }
        }
        return $this->result;
    }

    /**
     * 获取分组统计数据及每个分组中【top n】文章
     * @return array
     */
    private function getGroupTop(){
        $this->result = [];
        if($this->response === false) return $this->result;
        $data = $this->decodeResponse;
        if(isset($data['aggregations'][$this->groupField])){
            foreach($data['aggregations'][$this->groupField]['buckets'] as $v){
                $tmp = [
                    'numFound' => $v['doc_count'],
                    'newsList' => array_map(function($n){
                        return $this->decorate($n);
                    },$v['top_data']['hits']['hits'])
                ];
                $this->result[$v['key']] = $tmp;
            }
        }
        return $this->result;
    }

    /**
     * 分组内去重统计数据
     * @return array
     */
    private function getGroupUnique(){
        $this->result = [];
        if($this->response === false) return $this->result;
        $data = $this->decodeResponse;
        if(isset($data['aggregations']['group1'])){
            foreach($data['aggregations']['group1']['buckets'] as $k => $v){
                $this->result[$v['key']] = $v['group2']['value'];
            }
        }
        return $this->result;
    }

    /**
     * 获取情感属性分组数据
     * @return array
     */
    private function getEmotionGroup(){
        $this->result = [];
        if($this->response === false) return $this->result;
        $data = $this->decodeResponse;
        if(isset($data['aggregations'][$this->groupField])){
            foreach($data['aggregations'][$this->groupField]['buckets'] as $k => $v){
                $this->result[$k] = $v['doc_count'];
            }
        }
        return $this->result;
    }

    /**
     * 获取日期统计数据
     * @return array
     */
    private function getDateGroup(){
        $this->result = [];
        if($this->response === false) return $this->result;
        $data = $this->decodeResponse;
        if(isset($data['aggregations']['group_by_date'])){
            foreach($data['aggregations']['group_by_date']['buckets'] as $k => $v){
                $this->result[$v['key_as_string']] = $v['doc_count'];
            }
        }
        return $this->result;
    }

    /**
     * 获取日期、平台统计数据
     * @return array
     */
    private function getDatePlatformGroup($type = 'date'){
        $this->result = [];
        if($this->response === false) return $this->result;
        $data = $this->decodeResponse;
        $key1 = $type == 'date'?'key_as_string':'key';
        $key2 = $type == 'date'?'key':'key_as_string';
        if(isset($data['aggregations']['group_alias'])){
            foreach($data['aggregations']['group_alias']['buckets'] as $k => $v){
                $tmp = [
                    'total' => $v['doc_count'],
                    'detail' => []
                ];
                if(isset($v['group_alias'])){
                    foreach($v['group_alias']['buckets'] as $n){
                        $tmp['detail'][$n[$key2]] = $n['doc_count'];
                    }
                }
                $this->result[$v[$key1]] = $tmp;
            }
        }
        return $this->result;
    }

    /**
     * 获取ci统计数据
     * @return array
     */
    private function getCiRangeGroup(){
        $this->result = [];
        if($this->response === false) return $this->result;
        $data = $this->decodeResponse;
        if(isset($data['aggregations']['group_range_ci'])){
            foreach($data['aggregations']['group_range_ci']['buckets'] as $k => $v){
                $value = $v['count']['value'];
                $this->result[$k] = $value;
            }
        }
        return $this->result;
    }

    /**
     * 数据装饰
     * @param $hit
     * @param array $fields
     * @return array
     */
    private function decorate($hit)
    {
        $this->fields = $this->fields ? $this->fields : self::fields();
        $data = $hit['_source'];
        $result = array_filter($data, function ($v) {
            return in_array($v, $this->fields);
        }, ARRAY_FILTER_USE_KEY);
        if($this->hightLightFields){
            foreach($this->hightLightFields as $hightLightField){
                $result[$hightLightField] = isset($hit['highlight'][$hightLightField])?$hit['highlight'][$hightLightField][0]:$hit['_source'][$hightLightField];
            }
        }
        in_array('media_CI', $this->fields) ? $result['media_CI'] = round($result['media_CI'],1):"";
        /*--ES中不存在的字段可以在这边定义，在select或appendFields方法中写入该字段即可获取-----------------------------------------------------------------------------------*/
        in_array('news_local_url', $this->fields) ? $result['news_local_url'] = self::getNewsLocalUrl($data['news_uuid'], $data['news_posttime']) : '';

        /*-----------------------------------------------------------------------------------------------------------------------------------------------------------*/
        return $result;
    }

    /**
     * 去网关查询数据，并赋值给$this->response
     */
    private function fetch(){
        $headers = [];
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: application/plain';
        $headers[] = 'Authorization:'.$this->getEsToken();
        $headers[] = 'Expect:';

        $startTime = strtotime($this->startTime);
        $endTime = strtotime($this->endTime);
        $allowStartTime = strtotime(self::START_DATE);
        $allowEndTime = time();

        $startTime = ($startTime<$allowStartTime?$allowStartTime:$startTime) * 1000;
        $endTime = ($endTime>$allowEndTime?$allowEndTime:$endTime) * 1000;
        $url = $this->scroll?self::ES_SCROLL_GATEWAY_URL:self::ES_GATEWAY_URL;

        //由于修改为入库时间，会存在上个月的数据这个月才抓到，故当月初时，将startStamp往前挪一挪
        if(date('d',($startTime / 1000)) <= 10){
            $startTime = $startTime - (86400000*11);     // 往前挪6天
        }
        $params = [
            'index' => $this->index,
            'statement' => json_encode(empty($this->condition)?new \stdClass():$this->condition),
            'startStamp' => $startTime,
            'endStamp' => $endTime
        ];
        $this->scrollId?$params['scrollId'] = $this->scrollId:null;
        $this->params = json_encode($params);
        $this->response = httpRequest($url,$headers,'POST',$params);
    }

    /**
     * 默认返回字段（待补充）
     * @return array
     */
    public static function fields(){
        return [
            'news_uuid',//文章唯一标识
            'news_title',//文章标题
            'news_digest',//文章摘要
            'platform',//文章媒体类型
            'news_posttime',//文章发布时间
            'news_fetch_time',//抓取时间
            'solr_create_time',//入库时间
            'news_url',//文章地址
            'news_postdate',//文章发布时间
            'platform_name',//文章发布媒体
            'media_name',//文章发布媒体
            'news_sim_hash',//文章sim_hash
            'news_emotion_score',//正面值
            'news_emotion',//情感属性文字描述
            'news_mood_pri',//情感属性大类
            'news_local_url'//带html标签的正文存放地址
        ];
    }

    /**
     * 获取（带html标签的正文内容）地址
     * @param $news_uuid
     * @param $news_posttime
     * @return string
     */
    public static function getNewsLocalUrl($news_uuid,$news_posttime)
    {
        //docs/2017/08/07/08/a38de929013468823760e99a7061e204.html
        return "docs/".date('Y/m/d/H',strtotime($news_posttime))."/{$news_uuid}.html";
    }

    //综合指数,清朗指数,负面指数权重
    public static function POIWeight($poi_type) {
        $weight_arr = array(
            'zonghe' => array('wx' => 0.3, 'weibo' => 0.25, 'web' => 0.2, 'app' => 0.25, 'extra' => 0),
            'qinglang' => array('wx' => 0.3, 'weibo' => 0.2, 'web' => 0.15, 'app' => 0.25, 'extra' => 0.1),
            'negative' => array('wx' => 0.3, 'weibo' => 0.2, 'web' => 0.15, 'app' => 0.25, 'extra' => 0.1)
        );
        return $weight_arr[$poi_type];
    }

    public function getEsToken(){
        return Yii::$app->params['esToken'];
    }
}