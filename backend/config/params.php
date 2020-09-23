<?php
return [
    'adminEmail' => 'admin@example.com',

    "menus" => [
        [
            "menu_name" => "企业列表",
            "index" =>"company_site_index",
            "url"   => "/company/site/index"
        ],
        [
            "menu_name" => "网站列表",
            "index" =>"company_web_index",
            "url"   => "/company/web/index"
        ],
        [
            "menu_name" => "操作日志",
            "index" =>"company_user_index",
//            "url"   => "/company/user/index",
            "url"   => "/company/site/log"
        ],
    ]
];
