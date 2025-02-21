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

$data = [
    ['sentence' => '我国氢弹之父于敏去世 享年93岁', 'uid' => '123', 'time' => '2019-01-12'],
    ['sentence' => '我国“氢弹之父”于敏去世 享年93岁', 'uid' => '124', 'time' => '2019-01-13'],
    ['sentence' => '享年93岁 我国“氢弹之父”于敏去世 ', 'uid' => '124', 'time' => '2019-01-13'],
    ['sentence' => '特朗普时隔30年还是要走老路', 'uid' => '127', 'time' => '2019-01-16'],
    ['sentence' => '全球航天发射近30年首次破百', 'uid' => '128', 'time' => '2019-01-17']
];
$res = NlpRpcSdk::Sim($data, 0.75);
show($res);

$res = NlpRpcSdk::ksfTag("康师傅", "方便面");
show($res);


$res = NlpRpcSdk::shunyaTag("这款长安汽车一般", "", "1");
show($res);

$res = NlpRpcSdk::locationRecognition("余杭区的房价在什么水平？");
show($res);

$res = NlpRpcSdk::entityRecognition("林武出席清华大学专题讲座");
show($res);

$res = NlpRpcSdk::suoBeiLocation("合肥", "北京");
show($res);

$res = NlpRpcSdk::kzTag("", "孔院过过过晚安周三，晚安小锦鲤们。愿望交给锦鲤们，你只管努力就行，握鳍");
show($res);

$res = NlpRpcSdk::hxTag("", "寒冬之时，环京楼市有了点儿活泛气儿。自去年“最严限购令”让环京楼市迅速沉寂后，眼前的场景实属反常：虽然没有购房资质，却不断有来自北京的购房人奔向环京区域的售楼处。记者连续数日调查发现，反常现象背后埋藏着惊人的“押房赌局”——价格不断下探时，尚无资质的购房人投下");
show($res);

$data = ['query' => '香港最近很混乱',
    'result' => [
        ['news_uuid' => '1', 'title' => '香港暴乱', 'content' => '香港暴乱'],
        ['news_uuid' => '2', 'title' => '香港旅游人数下跌', 'content' => '香港旅游人数下跌']
    ]];

$res = NlpRpcSdk::SimQuery($data['query'], $data['result']);
show($res);

$title = '长安标致雪铁龙DS 5';
$content = '【长安标致雪铁龙DS 5】科幻，应该是对DS 5最恰当的形容词，除了旅行版以外，这是国内在售为数不多的两厢中型车。法国人在设计上从来都是走在全世界的前面，如此大胆造型和细节设计在中型车里独树一帜。内饰同样足够惊艳和科幻，大量应用了不规则的设计，层次感特别丰富';
$res = NlpRpcSdk::RecognizeCartType($title, $content);
echo $res;

$title = '长安标致雪铁龙DS 5';
$content = '【长安标致雪铁龙DS 5】科幻，应该是对DS 5最恰当的形容词，除了旅行版以外，这是国内在售为数不多的两厢中型车。法国人在设计上从来都是走在全世界的前面，如此大胆造型和细节设计在中型车里独树一帜。内饰同样足够惊艳和科幻，大量应用了不规则的设计，层次感特别丰富';

$res = NlpRpcSdk::ContentCategory($title, $content, "short");
echo $res;

$title = "人民锐评 | 反暴救港，必须奋起了";
$content = "25日晚，香港葵青、荃湾等地示威游行再度失控，演变为街头暴力骚乱。\n操铁枝、扔砖块、砸店铺、袭警车、投掷汽油弹……武器层出不穷，手段极其恶劣，暴力已将香港推向极为危险的边缘。反暴救港，退无可退，必须奋起。\n激进示威者口口声声“警察施暴”，但谁是真正的暴徒，一目了然。我保障你游行，你却给我暴行。对警员围追堵截，逼警员朝天鸣枪示警。反对派等了两个多月，终于兴奋喊出“开枪了”。然而事实上，警察行为不仅合理而且必须。面对暴力，香港警察不是下手重了，恰恰是极度克制与忍让。在一些国家，警察哪怕被推一下，就能使用警棍并逮捕袭击者；若造成警察受伤，更会被视为“加重攻击罪”。\n香港乱够了，香港人受够了，香港已经疲惫不堪。结束旷日持久的骚乱，恢复秩序，是人心所向，更刻不容缓。";
$res = NlpRpcSdk::SentimentShort($title, $content);
show($res);

$title = "联想将和蔚来汽车联合开发智能汽车计算平台，给智能汽车时代提速";
$content = "联想将和蔚来汽车联合开发智能汽车计算平台，给智能汽车时代提速";
$res = NlpRpcSdk::SentimentLong($title, $content);
show($res);
RpcService::setServiceHost('nlp-national', ['10.81.178.37:50051',
    '10.81.178.37:50052', '10.81.178.37:50053', '10.81.178.37:50054',
    '10.81.178.37:50055', '10.81.178.37:50056']);
//RpcService::setServiceHost('nlp-national', ['127.0.0.1:50051']);

$content = "哈哈";
$res = NlpRpcSdk::EmTags($content);
show($res);

$content = ['联想将和蔚来汽车联合开发智能汽车计算平台，给智能汽车时代提速', '哈哈'];
$res = NlpRpcSdk::EmTags($content);
show($res);

$content = "新华社早知天下事";
$res = NlpRpcSdk::EmPolicyTag($content);
echo $res;

$content1 = '助力脱贫攻坚决战　财政部提前下达专项扶贫资金1136亿';
$res = NlpRpcSdk::EmPolicyTag([$content, $content1]);
show($res);

//RpcService::setServiceHost('nlp-demo', ['127.0.0.1:50051']);
$title = '黄晓明变身“黄主持”！金鸡百花电影节今晚开幕';
$content = '11月19日，第28届中国金鸡百花电影节将在厦门开幕，青岛籍演员黄晓明将担任本届金鸡百花电影节开幕式主持人。';
$res = NlpRpcSdk::DemoTag($title, $content, 20, 2);
show($res);

//$content = ['新华社早知天下事', '我在看：人民币“破7”留学生朋友你的学费交了么？跟新浪看热点网页链接', '港独分子是“自残式”制裁，应该驱逐出境，', '为什么说《香港人权与民主法案》是“自残式”制裁？'];
//$num = [10, 20, 20, 30];
$content = [
    '08年那是高三为了看奥运会学校为每个教室准备了一台电视，八月八号八点班主任带着我们一起守着电视机比上课还认真北京奥运会11周年那年今日丨北京奥运，世界瞩目！2008年的今天，第29届奥林匹克运动会开幕式在北京鸟巢举行。光环照亮古老的日晷，2008名演员击缶而歌，中国画长卷缓缓打开，李宁点燃主火炬世界为之惊艳！还记得当年的热血沸腾吗？还记得为国争光的奥运健儿们吗？2022年，相约',
    '什么贸易战升级，人民币破7，普通人关心这个真的有用么？有这个时间还是努力工作吧。',
    '对。宁青春进行曲1辰的目的很简单，西林山出一位四品的弟子，与将心打。西林山的先天感受到眼前的年轻人身上易烊千玺长安十二时辰人民币破7维密迎首位变性模特',
    '我在看：人民币“破7”留学生朋友你的学费交了么？跟新浪看热点网页链接',
    '扫黑除恶,严惩黑恶势力,弘扬社会正气,兄弟,办什么业务,我有熟人帮你办,简单省事,大家是否遇到过这样的场景,黄牛,隐匿在办事群众之中专门挑选对业务办理流程不熟悉的朋友进行坑蒙拐骗极大地损害了市民的权益,扫黑除恶专项斗争开展以来,鄂前旗交管大队将对,黄牛,代办车驾管业务等涉黑涉恶违法行为进行专项整治,在这里我们要提醒各位车主,擦亮眼睛,平时多多关注车驾管业务办理流程,有问题及时咨询民警,千万不要上当受骗,群众如果发现这类线索可拨打,及时举报,本期就与您分享在这里,欢迎继续关注交管之声',
    '杜雨薇自杀了,我昨天得知小姑娘去年自杀没抢救过来,已经火化了,狗男女复合,一切仿佛没有发生,一个年长男性和一个,岁女女孩都犯错,大家选择率先杀死那个女孩,你们成功了,记得上次这种众志成城骂一个女性还是一个很牛逼的男性画师出轨事件,叫啥我忘了,反正当时骂小三的比骂那个画师的多好多,要没记错的话最后结尾是小三自杀死了,画师和原配和好接着过日子去了,你们要把这个小姑娘逼死才算结束吗',
    '陈铭超话 #非正式会谈# 超级好看的一个节目####idoltube##非正式会谈#随着挖掘个人隐私成本的降低，大量的私人信息逐渐被赋予了商业价值。好在越来越多的人开始重视网络安全，如果生活在一个没有隐私的世界，那我们与“透明人”可能也没有什么区别了L钓娱video的微博视频',
];
$num = [10, 20, 30, 40, 50, 60, 70];
$res = NlpRpcSdk::EmCluster($content, $num);
show($res);

$res = NlpRpcSdk::EmClassify($content);
show($res);

$res = NlpRpcSdk::segWord("特朗普时隔30年还是要走老路");
show($res);

//RpcService::setServiceHost('nlp-spam', ['127.0.0.1:50051']);
$content = '习近平 李克强';
$res = NlpRpcSdk::SpamPolitic($content);
show($res);

$content = "这样当地形成了一种特殊的景观，凡是“红灯”密集的地方，不仅表明这里是妓院所在，而且证明“娼气”很旺。久而久之“红灯区”的叫法就出现了，但是这里所讲的“红灯区”仅仅指的是某些城市色情场所集中的地区。";
$res = NlpRpcSdk::SpamSuobei($content);
show($res);

$content = '白天最高气温，夜间最低气温，综合今日天气数据分析，在此提醒您，';
$res = NlpRpcSdk::SpamSuobeiWeather($content);
show($res);

$content = file_get_contents(dirname(__FILE__) . '/news.txt');
$res = NlpRpcSdk::Summary($content, 2);
echo $res . "\n";
echo str_repeat('-', 100) . "\n";
$res = NlpRpcSdk::Summary($content, -2);
echo $res . "\n";
echo str_repeat('-', 100);