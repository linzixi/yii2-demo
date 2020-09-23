<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Nlp;

/**
 */
class NlpClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * 长安汽车文章问题标签
     * {"tag":"产品质量问题","value":"0.85"}
     * @param \Nlp\ShunyaTagRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function ShunyaPubTag(\Nlp\ShunyaTagRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/ShunyaPubTag',
        $argument,
        ['\Nlp\NlpMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 长安汽车文章标签，包含问题标签、情感标签
     * {"feed_tag":"产品质量问题","feed_value":"0.85","feel_tag":"正面","feel_vaue":"0.85"}
     * @param \Nlp\ShunyaTagRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function ShunyaTag(\Nlp\ShunyaTagRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/ShunyaTag',
        $argument,
        ['\Nlp\NlpMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 分词
     * @param \Nlp\SegWordRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function SegWord(\Nlp\SegWordRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/SegWord',
        $argument,
        ['\Nlp\NlpMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 汽车类型识别
     * title 文章标题
     * content 文章正文
     * return string
     * @param \Nlp\TitleContentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function RecognizeCartType(\Nlp\TitleContentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/RecognizeCartType',
        $argument,
        ['\Nlp\NlpStringReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 央视项目相似文章计算
     * [{sentence:"news title or news content","uid":"http://www.baidu.com/","time":"2019-01-09 10:00:00"}]
     * @param \Nlp\SimilarityRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function Sim(\Nlp\SimilarityRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/Sim',
        $argument,
        ['\Nlp\NlpListListMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 康师傅项目标签识别：是否相关、情感属性、品牌类别
     * @param \Nlp\TitleContentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function KsfTag(\Nlp\TitleContentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/KsfTag',
        $argument,
        ['\Nlp\NLpListStringReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 地域识别
     * @param \Nlp\NlpStringRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function LocationRecognition(\Nlp\NlpStringRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/LocationRecognition',
        $argument,
        ['\Nlp\NlpListMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 实体识别
     * @param \Nlp\NlpStringRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function EntityRecognition(\Nlp\NlpStringRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/EntityRecognition',
        $argument,
        ['\Nlp\NlpMapListStringReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 索贝定制地域识别
     * title 文章标题 content 文章正文
     * @param \Nlp\TitleContentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function SuoBeiLocation(\Nlp\TitleContentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/SuoBeiLocation',
        $argument,
        ['\Nlp\NlpListMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 孔子学院相关标签
     * @param \Nlp\TitleContentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function KzTag(\Nlp\TitleContentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/KzTag',
        $argument,
        ['\Nlp\NLpListStringReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 华夏幸福相关标签
     * @param \Nlp\TitleContentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function HxTag(\Nlp\TitleContentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/HxTag',
        $argument,
        ['\Nlp\NLpListStringReply', 'decode'],
        $metadata, $options);
    }

    /**
     * just for test
     * @param \Nlp\TitleContentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function hello(\Nlp\TitleContentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/hello',
        $argument,
        ['\Nlp\NLpListStringReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 通用相似文章度计算
     * @param \Nlp\SimRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function SimQuery(\Nlp\SimRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/SimQuery',
        $argument,
        ['\Nlp\NlpListMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 中台文章分类标签
     * @param \Nlp\MgClassifyRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function ContentCategory(\Nlp\MgClassifyRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/ContentCategory',
        $argument,
        ['\Nlp\NlpStringReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 短文本情感分析
     * @param \Nlp\TitleContentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function SentimentShort(\Nlp\TitleContentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/SentimentShort',
        $argument,
        ['\Nlp\NLpListStringReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 微博情感分析
     * @param \Nlp\TitleContentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function SentimentWeibo(\Nlp\TitleContentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/SentimentWeibo',
        $argument,
        ['\Nlp\NLpListStringReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 长文本情感分析
     * @param \Nlp\TitleContentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function SentimentLong(\Nlp\TitleContentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/SentimentLong',
        $argument,
        ['\Nlp\NLpListStringReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 获取服务列表
     * @param \Nlp\NlpStringRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function Service(\Nlp\NlpStringRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/Service',
        $argument,
        ['\Nlp\NLpListStringReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 应急中心项目相关标签
     * @param \Nlp\EmTagRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function EmTags(\Nlp\EmTagRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/EmTags',
        $argument,
        ['\Nlp\NlpListMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 应急中心项目政策标签
     * @param \Nlp\ListStringRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function EmPolicyTag(\Nlp\ListStringRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/EmPolicyTag',
        $argument,
        ['\Nlp\NLpListStringReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 应急中心项目文本聚类
     * @param \Nlp\EmClusterRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function EmCluster(\Nlp\EmClusterRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/EmCluster',
        $argument,
        ['\Nlp\EmClusterReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 应急中心项目文本分类
     * @param \Nlp\ListStringRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function EmClassify(\Nlp\ListStringRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/EmClassify',
        $argument,
        ['\Nlp\NLpListStringReply', 'decode'],
        $metadata, $options);
    }

    /**
     * NLP DEMO
     * @param \Nlp\DemoRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function DemoTag(\Nlp\DemoRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/DemoTag',
        $argument,
        ['\Nlp\NlpMapListStringReply', 'decode'],
        $metadata, $options);
    }

    /**
     * SPAM detectors
     * 包含色情信息
     * @param \Nlp\SpamRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function SpamPorn(\Nlp\SpamRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/SpamPorn',
        $argument,
        ['\Nlp\NlpMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 包含赌博信息
     * @param \Nlp\SpamRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function SpamBetting(\Nlp\SpamRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/SpamBetting',
        $argument,
        ['\Nlp\NlpMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 包含政治信息
     * @param \Nlp\SpamRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function SpamPolitic(\Nlp\SpamRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/SpamPolitic',
        $argument,
        ['\Nlp\NlpMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 检测是否包含色情、赌博等信息
     * @param \Nlp\SpamRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function SpamDetect(\Nlp\SpamRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/SpamDetect',
        $argument,
        ['\Nlp\NlpListMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 索贝项目垃圾文本检测
     * @param \Nlp\SpamRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function SpamSuobei(\Nlp\SpamRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/SpamSuobei',
        $argument,
        ['\Nlp\NlpMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 索贝项目天气文本检测
     * @param \Nlp\SpamRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function SpamSuobeiWeather(\Nlp\SpamRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/SpamSuobeiWeather',
        $argument,
        ['\Nlp\NlpMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 主体地域识别
     * @param \Nlp\TitleContentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function SubjectLocation(\Nlp\TitleContentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/SubjectLocation',
        $argument,
        ['\Nlp\NlpMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 情绪分类
     * @param \Nlp\TitleContentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function EmotionClassify(\Nlp\TitleContentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/EmotionClassify',
        $argument,
        ['\Nlp\NlpMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 短文本情绪分类
     * @param \Nlp\TitleContentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function EmotionShortClassify(\Nlp\TitleContentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/EmotionShortClassify',
        $argument,
        ['\Nlp\NlpMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 长文本情绪分类
     * @param \Nlp\TitleContentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function EmotionLongClassify(\Nlp\TitleContentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/EmotionLongClassify',
        $argument,
        ['\Nlp\NlpMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 文章摘要
     * @param \Nlp\SummaryRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function Summary(\Nlp\SummaryRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/Summary',
        $argument,
        ['\Nlp\NlpStringReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 海外语种分词
     * @param \Nlp\OverseaSegWordRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function OverseaSegWord(\Nlp\OverseaSegWordRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/OverseaSegWord',
        $argument,
        ['\Nlp\NlpMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 海外短文本情绪分类
     * @param \Nlp\OverseaTitleContentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function OverseaEmotionShortClassify(\Nlp\OverseaTitleContentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/OverseaEmotionShortClassify',
        $argument,
        ['\Nlp\NlpMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 海外长文本情绪分类
     * @param \Nlp\OverseaTitleContentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function OverseaEmotionLongClassify(\Nlp\OverseaTitleContentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/OverseaEmotionLongClassify',
        $argument,
        ['\Nlp\NlpMapReply', 'decode'],
        $metadata, $options);
    }

    /**
     * 汽车细粒度情感分析
     * @param \Nlp\NlpStringRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function AutoAspectSentiment(\Nlp\NlpStringRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/nlp.Nlp/AutoAspectSentiment',
        $argument,
        ['\Nlp\AutoAspectSentimentReply', 'decode'],
        $metadata, $options);
    }

}
