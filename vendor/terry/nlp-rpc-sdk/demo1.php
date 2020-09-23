<?php
/**
 * Author: huanw2010@gmail.com
 * Date: 2019/8/28 23:05
 */
require_once __DIR__ . '/../../autoload.php';
use terry\nlp\RpcLog;
use terry\nlp\NlpRpcSdk;
use terry\nlp\RpcService;

RpcLog::config([
    'level' => RpcLog::INFO,
    'logSink' => 'terry\nlp\LogConsoleSink',
]);
function show($data)
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
}

//RpcService::setServiceHost('nlp-national', ['10.81.178.37:50051',
//    '10.81.178.37:50052', '10.81.178.37:50053', '10.81.178.37:50054',
//    '10.81.178.37:50055', '10.81.178.37:50056']);
//RpcService::setServiceHost('nlp-national', ['127.0.0.1:50051']);


//$content = ['新华社早知天下事', '我在看：人民币“破7”留学生朋友你的学费交了么？跟新浪看热点网页链接', '港独分子是“自残式”制裁，应该驱逐出境，', '为什么说《香港人权与民主法案》是“自残式”制裁？'];
//$num = [10, 20, 20, 30];
//$content = [
//    '08年那是高三为了看奥运会学校为每个教室准备了一台电视，八月八号八点班主任带着我们一起守着电视机比上课还认真北京奥运会11周年那年今日丨北京奥运，世界瞩目！2008年的今天，第29届奥林匹克运动会开幕式在北京鸟巢举行。光环照亮古老的日晷，2008名演员击缶而歌，中国画长卷缓缓打开，李宁点燃主火炬世界为之惊艳！还记得当年的热血沸腾吗？还记得为国争光的奥运健儿们吗？2022年，相约',
//    '什么贸易战升级，人民币破7，普通人关心这个真的有用么？有这个时间还是努力工作吧。',
//    '对。宁青春进行曲1辰的目的很简单，西林山出一位四品的弟子，与将心打。西林山的先天感受到眼前的年轻人身上易烊千玺长安十二时辰人民币破7维密迎首位变性模特',
//    '我在看：人民币“破7”留学生朋友你的学费交了么？跟新浪看热点网页链接',
//    '扫黑除恶,严惩黑恶势力,弘扬社会正气,兄弟,办什么业务,我有熟人帮你办,简单省事,大家是否遇到过这样的场景,黄牛,隐匿在办事群众之中专门挑选对业务办理流程不熟悉的朋友进行坑蒙拐骗极大地损害了市民的权益,扫黑除恶专项斗争开展以来,鄂前旗交管大队将对,黄牛,代办车驾管业务等涉黑涉恶违法行为进行专项整治,在这里我们要提醒各位车主,擦亮眼睛,平时多多关注车驾管业务办理流程,有问题及时咨询民警,千万不要上当受骗,群众如果发现这类线索可拨打,及时举报,本期就与您分享在这里,欢迎继续关注交管之声',
//    '杜雨薇自杀了,我昨天得知小姑娘去年自杀没抢救过来,已经火化了,狗男女复合,一切仿佛没有发生,一个年长男性和一个,岁女女孩都犯错,大家选择率先杀死那个女孩,你们成功了,记得上次这种众志成城骂一个女性还是一个很牛逼的男性画师出轨事件,叫啥我忘了,反正当时骂小三的比骂那个画师的多好多,要没记错的话最后结尾是小三自杀死了,画师和原配和好接着过日子去了,你们要把这个小姑娘逼死才算结束吗',
//    '陈铭超话 #非正式会谈# 超级好看的一个节目####idoltube##非正式会谈#随着挖掘个人隐私成本的降低，大量的私人信息逐渐被赋予了商业价值。好在越来越多的人开始重视网络安全，如果生活在一个没有隐私的世界，那我们与“透明人”可能也没有什么区别了L钓娱video的微博视频',
//];
//$num = [10, 20, 30, 40, 50, 60, 70];
//$res = NlpRpcSdk::EmCluster($content, $num);
//print_r($res);
////show($res);
//
//$res = NlpRpcSdk::EmClassify($content);
//show($res);
//$content = '白天最高气温，夜间最低气温，综合今日天气数据分析，在此提醒您，';
//$res = NlpRpcSdk::SpamSuobeiWeather($content);
//show($res);
//$content = '24日夜间到25日白天：东南部中雨，局地大雨，大部分地区气温13-29℃，城口及东南部12-26℃。主城区：雷阵雨转多云，气温18-27℃。';
//$res = NlpRpcSdk::SpamSuobeiWeather($content);
//show($res);
//

//$content = file_get_contents(dirname(__FILE__) . '/news.txt');
//$res = NlpRpcSdk::Summary($content, 2);
//echo $res . "\n";
//echo str_repeat('-', 100) . "\n";
//$res = NlpRpcSdk::Summary($content, -2);
//echo $res . "\n";
//echo str_repeat('-', 100);

//$content = '【 #六安# 城南中学区别对待师生？学生宿舍用水问题一直得不到改善！ 】网友爆料：我是城南中学的在校住宿生，关于我校学生宿舍的用水问题我想申明一下，学校接的都是外面的湖水，水质浑浊，并有肉眼可见的黑色杂志，我们每天都要进行生活洗漱，这样的水让我们学生怎么使用？但教师行政楼那边确用的都是自来水。学生用口罩过滤生活用水，出现好多不明黑色杂质，太恐怖！抄送 @六安市教体局发布 2六安';
//
//$res = NlpRpcSdk::SentimentWeibo($content);
//show($res);
//
//$title = "";
//$res = NlpRpcSdk::SentimentAdvance($title, $content, '');
//show($res);
//
//$title = $content;
//$res = NlpRpcSdk::SentimentAdvance($title, $content, '');
//show($res);
//
//$res = NlpRpcSdk::SentimentAdvance($title, $content, 'weibo');
//show($res);

$content = '[微笑]【纳智捷大7 SUV 2011款 2.2T 智享型 四驱《车主点评》】车主喜欢纳智捷大7大气的外观、齐全的配置、充裕的空间以及强劲的动力但市区油耗偏高、内饰用料被车主所吐槽#试驾评测#  ';

$res = NlpRpcSdk::AutoAspectSentiment($content);
show($res);