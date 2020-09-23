<?php
/**
 * Created by PhpStorm.
 * User: qingbo
 * Date: 2015/7/27
 * Time: 13:11
 */
namespace qingbojj\editorgridview;

use yii\web\AssetBundle;

class EditorGridViewAsset extends AssetBundle
{
    public $sourcePath = '@qingbojj/editorgridview/assets';

    public $css = [
//        'bootstrap3-editable/css/bootstrap-editable.css',
        'admin.css'
    ];
    public $js = [
//        'bootstrap3-editable/js/bootstrap-editable.js',
        'admin.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',
//        'yii\bootstrap\BootstrapAsset',
//        'yii\bootstrap\BootstrapPluginAsset',
        'yii\grid\GridViewAsset'
    ];
}