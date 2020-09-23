> ### 环境依赖

- 安装composer
- 安装grpc扩展
    - Windows上有编译好的PHP7 [grpc.dll](https://pecl.php.net/package/grpc)
    - Linux上自行编译安装
- composer.json 文件增加如下配置


```

{
  "repositories": [
    {
      "type": "composer",
      "url": "http://satis.gstai.com/"
    }
  ],
  "config": {
    "secure-http": false
  },
  "require": {
    "terry/nlp-rpc-sdk": "dev-master"
  }
}


```

- 执行 `composer install` 安装 nlp-rpc-sdk
- 使用NlpRpcSdk::SegWord方法实例

```
require_once dirname(__FILE__) . '/vendor/autoload.php';
$content = "习近平同党外人士共迎新春 代表中共中央，向各民主党派、工商联和无党派人士，向统一战线广大成员，致以诚挚的问候和新春的祝福";
$res = \terry\nlp\NlpRpcSdk::SegWord($content);
print_r($res);
```

> ### 支持的RPC方法

>详见`NlpRpcSdk`类中`public static`修饰的方法

- ContentCategory 内容分类
- EntityRecognition 通用实体识别
- HxTag 华夏幸福相关标签
- KsfTag 康师傅标签相关
- KzTag 孔子学院项目相关标签
- LocationRecognition 通用地域识别
- RecognizeCartType 汽车识别
- SegWord 通用分词
- SentimentLong 长文本情感分析
- SentimentShort 短文本情感分析，weibo/forum/video/facebook/twitter 文本小于89个字符的文章
- ShunyaTag 宣亚汽车行业标签相关
- Sim 央视项目相似计算
- SimQuery 通用相似文章计算
- SuoBeiLocation 索贝定制地域识别
- EmTags 应急中心项目相关标签
- EmPolicyTag 应急中心项目政策相关标签

> ### `NlpRpcSdk` 约定

- 在本地测试需要登录VPN，服务器上直接使用
- 方法正常返回结果数组，如果调用异常，可能返回空数组，需要自己在业务中判断是否数组或者特定的key是否为空，防止出现数据下标不存在的异常
- 调用方法，参考`demo.php`

> ### SDK v2.0.0 版本升级日志


```

升级注意： 此版本日志设置不兼容旧版本，如果之前有做类似RpcLog::$level=RpcLog::INFO 设置， 需要统一替换成RpcLog::config
RpcLog::config 是全局配置，建议统一设置，另外， 多次调用config方法， 相同的配置项会进行合并，比如：
RpcLog::config(['level' => 'INFO'])
日志级别会被设置成 INFO
RpcLog::config(['level'=>'WARN','logSink' => 'LogConsoleSink'])
日志级别是会被设置 WARN ，同时logSink 会被设置成LogConsoleSink

另外，
使用 RpcLog::getSettings() 可以获取RpcLog的配置信息
使用 RpcLog::getLogSink()->getSettings() 可以查看sink配置信息

```

> ###### New Features

- RpcLog::config 日志相关配置


```

RpcLog::config([
    'level' => RpcLog::INFO,
    // 目前支持 logConsoleSink 直接echo、logFileSink 输出到到文件
    // 默认是sink是LogFileSink
    'logSink' => 'LogConsoleSink',
    // 日志文件目录，可以修改到指定的目录
    'logFile' => dirname(__FILE__) . '/rpc.log',
]);

```


- RpcService::setServiceHost 设置服务地址，主要在应急中心本地部署项目中用到

```

// nlp-national 内置参数
// 配置应急中心项目服务地址，其他项目或者通用服务不用配置
RpcService::setServiceHost('nlp-national', ['10.81.178.37:50051',
    '10.81.178.37:50052', '10.81.178.37:50053', '10.81.178.37:50054',
    '10.81.178.37:50055']);

```


- NlpRpcSdk::EmTags 应急中心项目相关标签

```

// 传入单条文本
$content = "哈哈";
$res = NlpRpcSdk::EmTags($content);

```


```

{"risk_tag":"网络暴力","network_security":"非网络安全","energy_tag":"清朗","emotion_event_tag":"中性","general_emotion_tag":"中性","general_emotion_value":"0.5868519086936013","emotion_event_value":"0.5868519086936013"}

```


```

// 传入数组
$content = ['联想将和蔚来汽车联合开发智能汽车计算平台，给智能汽车时代提速', '哈哈'];
$res = NlpRpcSdk::EmTags($content);

```


```

// 返回的数组包含多条结果，注意需要进行null值判断，防止异常
[{"risk_tag":"网络暴力","energy_tag":"清朗","network_security":"非网络安全","general_emotion_tag":"中性","emotion_event_tag":"中性","general_emotion_value":"0.5714344885030224","emotion_event_value":"0.5714344885030224"},{"emotion_event_value":"0.5868519086936013","general_emotion_value":"0.5868519086936013","risk_tag":"网络暴力","network_security":"非网络安全","energy_tag":"清朗","general_emotion_tag":"中性","emotion_event_tag":"中性"}]


```

- NlpRpcSdk::EmPolicyTag 应急中心项目政策相关标签

```

$content = "新华社早知天下事";
// 返回结果字符串
$res = NlpRpcSdk::EmPolicyTag($content);

```

> ###### TIPS

- 具体调用方法，参考demo.php
- download [v2.0.0](http://saas-pubsentiment-img1.oss-cn-hangzhou.aliyuncs.com/tmp/sdk/grpc_php_sdk.v2.0.0.tar.gz)

> ### SDK v2.1.0 版本升级日志

- NlpRpcSdk::EmPolicyTag 支持传递数组，批量获取结果


```

$content = "新华社早知天下事";
// 传递单条数据，返回结果字符串
$res = NlpRpcSdk::EmPolicyTag($content);
echo $res;

$content1 = '助力脱贫攻坚决战　财政部提前下达专项扶贫资金1136亿';
// 传递数组，返回结果数组
$res = NlpRpcSdk::EmPolicyTag([$content, $content1]);
show($res);

```

- NlpRpcSdk::DemoTag 增加NLP DEMO方法

```

$title = '黄晓明变身“黄主持”！金鸡百花电影节今晚开幕';
$content = '11月19日，第28届中国金鸡百花电影节将在厦门开幕，青岛籍演员黄晓明将担任本届金鸡百花电影节开幕式主持人。';
// 最多返回20个分词结果；摘要返回2句话
$res = NlpRpcSdk::DemoTag($title, $content, 20, 2);

```

- download [v2.1.0](http://saas-pubsentiment-img1.oss-cn-hangzhou.aliyuncs.com/tmp/sdk/grpc_php_sdk.v2.1.0.tar.gz)

> ### SDK v2.1.1 版本升级日志
- 增加NlpRpcSdk::EmCluster方法，用于应急中心项目文本聚类

```

$content = ['新华社早知天下事', '我在看：人民币“破7”留学生朋友你的学费交了么？跟新浪看热点网页链接', '港独分子是“自残式”制裁，应该驱逐出境，', '为什么说《香港人权与民主法案》是“自残式”制裁？'];
$num = [10, 20, 20, 30];
// $content 文章标题数组
// $num 文章标题对应的相似文章数数组
$res = NlpRpcSdk::EmCluster($content, $num);

```

- download [v2.1.1](http://saas-pubsentiment-img1.oss-cn-hangzhou.aliyuncs.com/tmp/sdk/grpc_php_sdk.v2.1.1.tar.gz)

> ### SDK v2.1.2 版本升级日志
- NlpRpcSdk::EmCluster 方法返回数组增加了一个维度，包含去掉“其他”分类的所有文章

```

// $res[0] 摘要
// $res[1] 聚类结果
// $res[2] 去掉“其他”分类的所有文章标题
$res = ["为什么说《香港人权与民主法案》是“自残式”制裁？",["为什么说《香港人权与民主法案》是“自残式”制裁？"],["为什么说《香港人权与民主法案》是“自残式”制裁？"]]

```

- download [v2.1.2](http://saas-pubsentiment-img1.oss-cn-hangzhou.aliyuncs.com/tmp/sdk/grpc_php_sdk.v2.1.2.tar.gz)

> ### SDK v2.1.3 版本升级日志
- 增加涉政文本识别 NlpRpcSdk::SpamPolitic

```

$content = '习近平';
$res = NlpRpcSdk::SpamPolitic($content);

```

返回结果：

```

// tag:normal 正常文本；politic 涉政文本
// value: 对应的标签的概率
{"tag":"politic","value":"1.0"}

```

- download [v2.1.3](http://saas-pubsentiment-img1.oss-cn-hangzhou.aliyuncs.com/tmp/sdk/grpc_php_sdk.v2.1.3.tar.gz)

> ### SDK v2.1.4 版本升级日志

- NlpRpcSdk::SpamPolitic 返回结果增加words字段，保存了命中的违规关键词


```

$content = '习近平 李克强';
$res = NlpRpcSdk::SpamPolitic($content);

```


返回结果：


```

// 多个违规词用半角逗号分隔
{"tag":"politic","value":"1.0","words":"李克强,习近平"}

```

- download [v2.1.4](http://saas-pubsentiment-img1.oss-cn-hangzhou.aliyuncs.com/tmp/sdk/grpc_php_sdk.v2.1.4.tar.gz)


> ### SDK v3.0.0 版本升级日志

- 重构代码，支持composer install
- 配置composer.json

```
{
  "repositories": [
    {
      "type": "composer",
      "url": "http://satis.gstai.com/"
    }
  ],
  "config": {
    "secure-http": false
  },
  "require": {
    "terry/nlp-rpc-sdk": "v3.0.0"
  }
}
```

> ### SDK v3.1.0 版本升级日志

- 增加`NlpRpcSdk::SpamSuobei`方法，索贝项目定制的垃圾文本识别
- 使用方法
```
$content = "被检测的内容";
// tag: normal/spam normal 表示正常 spam表示是垃圾文本
// words: 命中的关键词，多个关键词用半角逗号隔开 
$res = NlpRpcSdk::SpamSuobei($content);
```
- 使用v3.1.0
```
{
    "require": {
        "terry/nlp-rpc-sdk": "v3.1.0"
      }
}
```

> ### SDK v3.2.0 版本升级日志
- 删除了自定义的测试方法echo，防止在于PHP的内置方法冲突
- 增加了NetCheck 命令，用于检测客户端与RPC服务模块所在的服务器之间的网络连通性
- 使用方式方法：
    - `php vendor/terry/nlp-rpc-sdk/src/command/NetCheck.php -h` 查看支持的模块
    - `php vendor/terry/nlp-rpc-sdk/src/command/NetCheck.php -s mg`检测客户端到中台服务模块的网络连通性

> ### SDK v3.3.0 版本升级日志

- 修改了 NlpRpcSdk::EmCluster返回值类型，返回的数组$res[3]，保存了聚类文本对应的simHASH条数
- 增加了NlpRpcSdk::EmClassify方法，用于多条文本批量分类
```
$content = [
    '什么贸易战升级，人民币破7，普通人关心这个真的有用么？有这个时间还是努力工作吧。',
    '我在看：人民币“破7”留学生朋友你的学费交了么？跟新浪看热点网页链接',
];
$res = NlpRpcSdk::EmClassify($content);
```

> ### SDK v3.3.1 版本升级日志

- 为应急中心项目的相关方法添加了单元测试

> ### SDK v3.3.2 版本升级日志

- 把分词服务从中台服务中独立出来，修改SDK配置
- 孔子学院、康师傅项目的相关服务已经下线，去掉对应方法的单元测试

> ### SDK v3.3.3 版本升级日志

- 应急中心项目 `NlpRpcSdk::EmCluster` 返回值增加了分词

> ### SDK v3.3.4 版本升级日志

- 应急中心项目 `NlpRpcSdk::EmCluster` 返回值增加了事件汇总对应的相似文章数

> ### SDK v3.3.5 版本升级日志

- 修复在特殊环境下的BUG

```
$req = new \Nlp\NlpStringRequest(['content' => $content]);
// 改为
$req = new \Nlp\NlpStringRequest();
$req->setContent($content);
```

> ### SDK v3.3.6 版本升级日志

- `NlpRpcSdk::Sim` 方法，增加了阈值参数`$threshold`

```
// $threshold 取值范围在[0,1]，值越大，对相似要求越高，越不会被合并；反之，值越小，对相似要求越低，越容易被合并
$res = NlpRpcSdk::Sim($data, 0.75);
```

- 新增`NlpRpcSdk::SpamSuobeiWeather`方法,用于索贝项目检测包含天气的文本，出参数据结构同`NlpRpcSdk::SpamSuobei`

```
$content = '白天最高气温，夜间最低气温，综合今日天气数据分析，在此提醒您，';
$res = NlpRpcSdk::SpamSuobeiWeather($content);
```

> ### SDK v3.3.7 版本升级日志

- 新增`NlpRpcSdk::Summary`方法，用于文章摘要

```
// $num > 0 表示取几个句子
// $num < 0 表示取几个段落，算法是根据"\n"来识别段落，返回的段落也是用"\n"分割
$res = NlpRpcSdk::Summary($content, $num);
```

> ### SDK v3.4.0 升级日志

- 新增 `NlpRpcSdk::SentimentAdvance($title, $content, $type)`方法，用于情感分析，自动适配模型
    - 如果type = 'weibo' 那么直接选择weibo的情感分析模型，否则：
        - 标题加上正文的长度lte 200，选择短文本的模型，否则选择长文本的模型
- 新增`NlpRpcSdk::AutoAspectSentiment($content)`方法，用于汽车领域细粒度情感分析
    - 方法返回多个方面(aspect)，每个aspect对应的一个情感属性标签(tag)，以及一个置信度(prob)

```
$content = '[微笑]【纳智捷大7 SUV 2011款 2.2T 智享型 四驱《车主点评》】车主喜欢纳智捷大7大气的外观、齐全的配置、充裕的空间以及强劲的动力但市区油耗偏高、内饰用料被车主所吐槽#试驾评测#  ';

$res = NlpRpcSdk::AutoAspectSentiment($content);
print_r($res);
```