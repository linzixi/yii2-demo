<?php
/**
 * Author: huanw2010@gmail.com
 * Date: 2019/5/20 14:02
 */

namespace terry\nlp;

use Nlp;

//require_once __DIR__ . '/../../../../vendor/autoload.php';

//spl_autoload_register(function ($class) {
//    $file = dirname(__FILE__) . '/' . str_replace("\\", DIRECTORY_SEPARATOR, $class . ".php");
//    if (file_exists($file)) {
//        require_once $file;
//    }
//});

class NlpRpcSdk
{
    /**
     * 央视项目相似文章计算，相似的文章会被合并到一个数组
     * @param $data
     * $data = [
     * ['sentence' => '我国氢弹之父于敏去世 享年93岁', 'uid' => '123', 'time' => '2019-01-12'],
     * ['sentence' => '我国“氢弹之父”于敏去世 享年93岁', 'uid' => '124', 'time' => '2019-01-13'],
     * ['sentence' => '享年93岁 我国“氢弹之父”于敏去世 ', 'uid' => '124', 'time' => '2019-01-13'],
     * ['sentence' => '特朗普时隔30年还是要走老路', 'uid' => '127', 'time' => '2019-01-16'],
     * ['sentence' => '全球航天发射近30年首次破百', 'uid' => '128', 'time' => '2019-01-17']
     * ];
     * $threshold取值范围在[0,1]，值越大，对相似要求越高，越不会被合并；反之，值越小，对相似要求越低，越容易被合并
     * @param float $threshold
     * @return array
     */
    public static function Sim($data, $threshold = 0.75)
    {
        $data1 = [];
        foreach ($data as $d) {
            $map = new Nlp\MapRequest();
            $map->setItem($d);
            $data1[] = $map;
        }
        $request = new Nlp\SimilarityRequest();
        $request->setContent($data1);
        $request->setThreshold($threshold);

        $reply = RpcService::request(__FUNCTION__, $request);
        if (!$reply) {
            return [];
        }
        $message = $reply->getRes();
        $data1 = [];
        foreach ($message as $k1 => $v1) {
            $data2 = [];
            foreach ($v1->getRes() as $k2 => $v2) {
                $kv = [];
                foreach ($v2->getRes() as $k3 => $v3) {
                    $kv[$k3] = $v3;
                }
                $data2[] = $kv;
            }
            $data1[] = $data2;
        }
        return $data1;
    }

    /**
     * 康是否相关标签计算
     * @param $title
     * @param $content
     * @return array [是否相关,正负面,事业部名称]
     */
    public static function KsfTag($title, $content)
    {
        $request = new Nlp\TitleContentRequest();
        $request->setTitle($title);
        $request->setContent($content);
        $reply = RpcService::request(__FUNCTION__, $request);
        $ret = [];
        if (!$reply) {
            return $ret;
        }
        foreach ($reply->getRes() as $k => $v) {
            $ret[$k] = $v;
        }
        return $ret;
    }

    /**
     * 宣亚项目相关标签识别
     * @param $content
     * @return array
     * {"feel_tag":"中性","feed_tag":"其他","feel_value":"0.9319426","feed_value":"0.38606756925582886"}
     * feel_tag 情感标签
     * feel_value 情感置信度
     * feed_tag 问题标签
     * feed_value 问题标签置信度
     */
    public static function ShunyaTag($title, $content, $flag)
    {
        $request = new Nlp\ShunyaTagRequest();
        $request->setTitle($title);
        $request->setContent($content);
        $request->setFlag($flag);
        $reply = RpcService::request(__FUNCTION__, $request);
        if (!$reply) {
            return [];
        }
        $ret = [];
        foreach ($reply->getRes() as $k => $v) {
            $ret [$k] = $v;
        }

        return $ret;
    }

    /**
     * 分词
     * @param string $content 待分词的文本
     * @param int $top 按照词频倒序排列，返回topN
     * @return array
     */
    public static function SegWord($content, $top = 20)
    {
        $request = new Nlp\SegWordRequest();
        $request->setContent($content);
        $request->setTop($top);
        $reply = RpcService::request(__FUNCTION__, $request);
        if (!$reply) {
            return [];
        }
        $ret = [];
        foreach ($reply->getRes() as $k => $v) {
            $ret [$k] = $v;
        }
        return $ret;
    }

    /**
     * 通用地域识别
     * @param $content
     * @return array
     */
    public static function LocationRecognition($content)
    {
        $req = new Nlp\NlpStringRequest();
        $req->setContent($content);
        $reply = RpcService::request(__FUNCTION__, $req);
        $ret = [];
        if (!$reply) {
            return $ret;
        }
        foreach ($reply->getRes() as $map) {
            $_d = [];
            foreach ($map->getRes() as $k => $v) {
                $_d[$k] = $v;
            }
            $ret[] = $_d;
        }

        return $ret;
    }

    /**
     * 通用命名实体识别
     * @param $content
     * @return array {"organization":["清华大学"],"person":["林武"]}
     */
    public static function EntityRecognition($content)
    {
        $req = new Nlp\NlpStringRequest();
        $req->setContent($content);
        $reply = RpcService::request(__FUNCTION__, $req);
        if (!$reply) {
            return [];
        }

        $ret = [];
        foreach ($reply->getRes() as $k => $list) {
            $ret[$k] = [];
            foreach ($list->getRes() as $v) {
                $ret[$k][] = $v;
            }
        }
        return $ret;
    }

    /**
     * 索贝定制地域识别
     * @param $title
     * @param $content
     * @return array
     */
    public static function SuoBeiLocation($title, $content)
    {
        $request = new Nlp\TitleContentRequest();
        $request->setTitle($title);
        $request->setContent($content);
        $reply = RpcService::request(__FUNCTION__, $request);
        $ret = [];
        if (!$reply) {
            return $ret;
        }
        foreach ($reply->getRes() as $map) {
            $_d = [];
            foreach ($map->getRes() as $k => $v) {
                $_d[$k] = $v;
            }
            $ret[] = $_d;
        }

        return $ret;
    }

    /**
     * 孔子学院相关标签
     * @param $title
     * @param $content
     * @return array
     */
    public static function KzTag($title, $content)
    {
        $request = new Nlp\TitleContentRequest();
        $request->setTitle($title);
        $request->setContent($content);
        $reply = RpcService::request(__FUNCTION__, $request);
        $ret = [];
        if (!$reply) {
            return $ret;
        }
        foreach ($reply->getRes() as $item) {
            $ret[] = $item;
        }
        return $ret;
    }

    /**
     * 华夏幸福相关标签
     * @param $title
     * @param $content
     * @return array
     */
    public static function HxTag($title, $content)
    {
        $request = new Nlp\TitleContentRequest();
        $request->setTitle($title);
        $request->setContent($content);
        $reply = RpcService::request(__FUNCTION__, $request);
        $ret = [];
        if (!$reply) {
            return $ret;
        }
        foreach ($reply->getRes() as $item) {
            $ret[] = $item;
        }
        return $ret;
    }

    /**
     * 通用相似文章计算
     * @param $query string news_title/news_content
     * @param $haystack [{news_uuid:"1",news_title:"",news_content:""},{news_uuid:"2",news_title:"",news_content:""}]
     * @return array
     */
    public static function SimQuery($query, $haystack)
    {
        $request = new \Nlp\SimRequest();
        $request->setQuery($query);
        $lst = [];
        foreach ($haystack as $item) {
            $map = new \Nlp\MapRequest();
            $map->setItem($item);
            $lst[] = $map;
        }
        $lstMap = new \Nlp\NlpListMapRequest();
        $lstMap->setContent($lst);
        $request->setHaystack($lstMap);
        $reply = RpcService::request(__FUNCTION__, $request);
        $ret = [];
        if (!$reply) {
            return $ret;
        }
        foreach ($reply->getRes() as $map) {
            $_d = [];
            foreach ($map->getRes() as $k => $v) {
                $_d[$k] = $v;
            }
            $ret[] = $_d;
        }

        return $ret;
    }

    /**
     * 车型识别
     * @param $title
     * @param $content
     * @return string
     */
    public static function RecognizeCartType($title, $content)
    {
        $req = new \Nlp\TitleContentRequest();
        $req->setTitle($title);
        $req->setContent($content);
        $reply = RpcService::request(__FUNCTION__, $req);
        if (!$reply) {
            return '';
        }
        return $reply->getRes();
    }

    /**
     * 文本分类
     * @param $title
     * @param $content
     * @param $type string short 短文本，包括weibo/forum/video/facebook/twitter，其他位长文本 long
     * @return string
     */
    public static function ContentCategory($title, $content, $type)
    {
        $req = new \Nlp\MgClassifyRequest();
        $req->setTitle($title);
        $req->setContent($content);
        $req->setType($type);

        $reply = RpcService::request(__FUNCTION__, $req);

        if (!$req) {
            return '';
        }
        return $reply->getRes();
    }

    /**
     * @param $reply
     * @return array
     */
    private static function parseStringList($reply)
    {
        $res = $reply->getRes();
        $data = [];
        foreach ($res as $v) {
            $data[] = $v;
        }
        return $data;
    }

    /**
     * 短文本情感识别，weibo/forumn/Facebook/Twitter/news which less than 89 words
     * @param $title
     * @param $content
     * @return array
     */
    public static function SentimentShort($title, $content)
    {
        $req = new \Nlp\TitleContentRequest();
        $req->setTitle($title);
        $req->setContent($content);
        $reply = RpcService::request(__FUNCTION__, $req);

        if (!$req) {
            return ['中性', 0.5];
        }
        return self::parseStringList($reply);
    }

    /**
     * 长文本情感识别
     * @param $title
     * @param $content
     * @return array
     */
    public static function SentimentLong($title, $content)
    {
        $req = new \Nlp\TitleContentRequest();
        $req->setTitle($title);
        $req->setContent($content);
        $reply = RpcService::request(__FUNCTION__, $req);

        if (!$req) {
            return ['中性', 0.5];
        }
        return self::parseStringList($reply);
    }

    /**
     * sentiment model for Weibo
     * @param $title
     * @return array
     */
    public static function SentimentWeibo($title)
    {
        $req = new \Nlp\TitleContentRequest();
        $req->setTitle($title);
        $req->setContent('');
        $reply = RpcService::request(__FUNCTION__, $req);

        if (!$req) {
            return ['中性', 0.5];
        }
        return self::parseStringList($reply);
    }

    /**
     * Auto select long/short/weibo model according to the {$type} and {$title} with {$content} length
     * @param $title
     * @param $content
     * @param $type
     * @return array
     */
    public static function SentimentAdvance($title, $content, $type)
    {
        if ($type == 'weibo') {
            return self::SentimentWeibo($title);
        }

        if (mb_strlen($title . $content, 'UTF-8') <= 200) {
            return self::SentimentShort($title, $content);
        }

        return self::SentimentLong($title, $content);
    }

    /**
     * 应急中心项目相关标签
     * @param $content
     * @param string $model
     * @return array|mixed|null
     */
    public static function EmTags($content, $model = 'svm')
    {
        $is_string = false;
        if (is_string($content)) {
            $content = [$content];
            $is_string = true;
        }
        $req = new \Nlp\EmTagRequest();
        $req->setContent($content);
        $req->setModel($model);
        $reply = RpcService::request(__FUNCTION__, $req);
        if (!$req) {
            return null;
        }
        $ret = [];
        foreach ($reply->getRes() as $res) {
            $data = [];
            foreach ($res->getRes() as $k => $v) {
                $data[$k] = $v;
            }
            $ret[] = $data;
        }
        if ($is_string && !empty($ret)) {
            return $ret[0];
        }
        return $ret;
    }

    /**
     * 应急中心项目政策相关标签
     * @param $content
     * @return array | string
     */
    public static function EmPolicyTag($content)
    {
        $isString = false;
        if (is_string($content)) {
            $content = [$content];
            $isString = true;
        }
        $req = new \Nlp\ListStringRequest();
        $req->setContent($content);
        $reply = RpcService::request(__FUNCTION__, $req);
        $ret = [];
        if ($reply) {
            foreach ($reply->getRes() as $tag) {
                $ret[] = $tag;
            }
        }
        return $isString ? (isset($ret[0]) ? $ret[0] : "") : $ret;
    }

    /**
     * NLP DEMO
     * @param $title
     * @param $content
     * @param int $wordNum
     * @param int $abstractNum
     * @return array
     */
    public static function DemoTag($title, $content, $wordNum = 20, $abstractNum = 2)
    {
        $req = new \Nlp\DemoRequest();
        $req->setTitle($title);
        $req->setContent($content);
        $req->setWordNum($wordNum);
        $req->setAbstractNum($abstractNum);

        $reply = RpcService::request(__FUNCTION__, $req);
        if (!$reply) {
            return [];
        }

        $ret = [];
        foreach ($reply->getRes() as $k => $list) {
            $ret[$k] = [];
            foreach ($list->getRes() as $v) {
                $ret[$k][] = $v;
            }
        }
        return $ret;
    }

    /**
     * 应急中心项目文本聚类
     * @param $content array 文章标题数组
     * @param $num array 文章标题对应的相似文章数数组
     * @return array
     */
    public static function EmCluster($content, $num)
    {

        $req = new \Nlp\EmClusterRequest();
        $req->setContent($content);
        $req->setNum($num);

        $ret = ['', [], [], [], [], []];
        $reply = RpcService::request(__FUNCTION__, $req);
        if (!$reply) {
            return [];
        }
        $ret[0] = $reply->getAbstract();
        foreach ($reply->getContent() as $item) {
            $ret[1][] = $item;
        }
        foreach ($reply->getReserve() as $item) {
            $ret[2][] = $item;
        }
        foreach ($reply->getNum() as $item) {
            $ret[3][] = $item;
        }
        $ret[4] = json_decode($reply->getWords(), true);
        foreach ($reply->getSimlist() as $item) {
            $ret[5][] = $item;
        }
        return $ret;
    }

    /**
     * 涉政文本检测
     * @param $content
     * @return array
     */
    public static function SpamPolitic($content)
    {
        $req = new \Nlp\SpamRequest();
        $req->setTitle($content);
        $req->setContent('');
        $ret = [];
        $reply = RpcService::request(__FUNCTION__, $req);
        if (!$reply) {
            return [];
        }
        foreach ($reply->getRes() as $key => $value) {
            $ret[$key] = $value;
        }

        return $ret;
    }

    /**
     * 索贝项目垃圾文本检测
     * @param $content
     * @return array
     */
    public static function SpamSuobei($content)
    {
        $req = new \Nlp\SpamRequest();
        $req->setTitle($content);
        $req->setContent('');
        $ret = [];
        $reply = RpcService::request(__FUNCTION__, $req);
        if (!$reply) {
            return [];
        }
        foreach ($reply->getRes() as $key => $value) {
            $ret[$key] = $value;
        }

        return $ret;
    }

    /**
     * 应急中心项目文本分类
     * @param $contents
     * @return array
     */
    public static function EmClassify($contents)
    {
        if (is_string($contents)) {
            $contents = [$contents];
        }
        $req = new Nlp\ListStringRequest();
        $req->setContent($contents);
        $ret = [];
        $reply = RpcService::request(__FUNCTION__, $req);
        if (!$reply) {
            return [];
        }

        foreach ($reply->getRes() as $item) {
            $ret[] = $item;
        }

        return $ret;
    }

    /**
     * 索贝项目天气文检测
     * @param $content
     * @return array
     */
    public static function SpamSuobeiWeather($content)
    {
        $req = new \Nlp\SpamRequest();
        $req->setTitle($content);
        $req->setContent('');
        $ret = [];
        $reply = RpcService::request(__FUNCTION__, $req);
        if (!$reply) {
            return [];
        }
        foreach ($reply->getRes() as $key => $value) {
            $ret[$key] = $value;
        }
        return $ret;
    }

    /**
     * 文章摘要
     * $num > 0 表示取几个句子
     * $num < 0 表示取几个段落，算法是根据"\n"来识别段落，返回的段落也是用"\n"分割
     * @param $content
     * @param $num
     * @return string
     */
    public static function Summary($content, $num)
    {
        $req = new Nlp\SummaryRequest();
        $req->setContent($content);
        $req->setNum($num);
        $ret = "";
        $reply = RpcService::request(__FUNCTION__, $req);
        if ($reply) {
            $ret = $reply->getRes();
        }
        return $ret;
    }

    /**
     * 汽车领域细粒度情感分析
     * @param $content
     * @return array
     */
    public static function AutoAspectSentiment($content)
    {
        $req = new Nlp\NlpStringRequest();
        $req->setContent($content);
        $reply = RpcService::request(__FUNCTION__, $req);
        $ret = [];
        if ($reply) {
            $res = $reply->getRes();
            foreach ($res as $k1 => $v1) {
                $ret[] = [
                    'aspect' => $v1->getAspect(),
                    'tag' => $v1->getTag(),
                    'prob' => $v1->getProb(),
                ];
            }
        }
        return $ret;
    }

}