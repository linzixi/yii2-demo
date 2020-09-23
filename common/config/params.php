<?php
$params = [
    'adminEmail' => 'noreply@qbdsj.cn',
    'supportEmail' => 'support@example.com',
    'user.passwordResetTokenExpire' => 3600,
    'schemeTypes' => ['人物', '机构', '产品', '品牌', '事件', '其他'],
    'emailConfig' => [//默认的邮箱服务器配置
        'host' => 'xxxxx',  //每种邮箱的host配置不一样
        'mail_account' => 'xxxxx',
        'mail_password' => 'xxxxx',
        'port' => '465',//需服务器开启25端口权限
        'encryption' => 'ssl',//ssl 或者 tls
    ],
    "esToken" => "",
    'mediaLevel' => [
        "政府" => [//政府
            "中央政府",
            "省级政府",
            "市&以下政府",
        ],
        "媒体" => [//媒体
            "中央媒体",
            "商业媒体",
            "省级媒体",
            "市&以下媒体",
            "境外媒体",
        ],
        "其他" => [//其他
            "央企",
            "名企",
            "高校",
            "智库",
            "宗教",
        ],
        "个人" => [//个人
            "明星",
            "KOL",
            "其他",
        ],
    ],
    "mainMedia" => ['中央政府', '中央媒体', '省级媒体'],//主要媒体配置
    'wxVerified' => [
        "1" => "认证",
        "0" => "未认证"
    ],
    'weiboVerified' => [
        "-1" => "普通个人",
        "0" => "认证个人",
        "1" => "认证政府",
        "2" => "认证企业",
        "3" => "认证媒体",
        "6" => "认证校园",
        "7" => "认证社团",
        "10" => "微女郎",
        "200" => "达人",
    ],
   // 'ossDomain' => 'http://qbhadoop.oss-cn-hangzhou-internal.aliyuncs.com/',
    'ossDomain' => 'http://qbhadoop.oss-cn-hangzhou.aliyuncs.com/',
    'sentiments' => [
        '正面', '中性', '负面'
    ],
    'moods' => [//情感属性词
        "喜悦" => "乐",
        "赞扬" => "好",
        "愤怒" => "怒",
        "悲伤" => "哀",
        "恐惧" => "惧",
        "厌恶" => "恶",
        "惊奇" => "惊"
    ],
];
return $params;
