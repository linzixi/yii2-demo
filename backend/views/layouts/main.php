<?php

/* @var $content string */

use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use backend\assets\AppAsset;
use common\widgets\Alert;

AppAsset::register($this);
?>
<!DOCTYPE html>
<head>
    <meta charset="utf-8" />
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= $this->params['title'] ?></title>
    <link rel="stylesheet" type="text/css" href="/static/css/style.css"/>
    <script src="/static/js/vue.js" type="text/javascript" charset="utf-8"></script>
    <script src="/static/js/element-ui.js" type="text/javascript" charset="utf-8"></script>
    <script src="/static/js/modal.js" type="text/javascript" charset="utf-8"></script>
    <script src="/static/js/page.js" type="text/javascript" charset="utf-8"></script>
    <script src="/static/js/jquery-1.7.2.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="/static/js/loading.js" type="text/javascript" charset="utf-8"></script>
    <script src="/static/js/vue-datepicker-local.js" type="text/javascript" charset="utf-8"></script>
    <link rel="stylesheet" type="text/css" href="/static/css/vue-datepicker-local.css"/>
</head>

<body>
<div class="container" id="app" style="display: none">
    <!-- 左边导航 -->
    <div class="container-left">
        <div class="logo">媒体属地后台管理系统</div>
        <ul class="naver">
            <?php $menus = Yii::$app->params['menus'] ;?>
            <?php foreach ($menus as $menu) :?>
                <li class="li <?=$menu['index']== $this->params['js_tpl'] ? "curr" : "" ?>"><a class="a" href="<?=$menu['url']?>"><?=$menu['menu_name']?></a></li>
            <?php endforeach;?>
        </ul>
    </div>
    <!-- 左边导航结束 -->

    <!-- 右边部分 -->
    <div class="container-right">
        <div class="inside">
            <!-- 头部 -->
            <div class="header">
                <div class="fr">
                    <span class="">您好，<?php echo $this->params['username']; ?></span>
                    <span class="signOut" onclick="logout()">退出</span>
                </div>
            </div>
            <!-- 内容 -->
            <?= $content ?>
        </div>
    </div>
</div>
<script src="/js/<?=$this->params['js_tpl']?>.js?<?=time()?>"></script>
<script>
    function logout() {
        if(confirm('确定要退出吗')){
            $.get('/company/index/logout',function (data) {
                if(data.code==10000){
                    location.href="/company/index/login";
                }
            })
        }
    }

    window.onload = function () {
        $("#app").show()
    }

</script>
</body>
</html>

