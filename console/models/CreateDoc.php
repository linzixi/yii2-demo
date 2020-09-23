<?php
/**
 * @Author:    Zhangshaolong<729562806@qq.com>
 * @Date:      2019/8/6
 * 生成word文档和pdf文档
 * 兼容window和mac上的office/wps
 */

namespace console\models;
use yii\base\Exception;
use yii;
use PhpOffice\PhpWord\PhpWord;

class CreateDoc
{
    const IS_DEBUG = false;
    public static function createHtml($page_url,$echarts_imgs)
    {
        if(self::IS_DEBUG) {
            echo "\n---------creating  email_html----------";
        }
        $file_content='';
        try {
            //去掉页面中多余代码
            $tpl_base = @file_get_contents($page_url);
            $tpl_base=preg_replace('/<meta http-equiv="X-UA-Compatible" content="IE=edge">/','',$tpl_base);
            $tpl_base=preg_replace('/<script>.*<\/script>/si','',$tpl_base);
            $tpl_base=preg_replace('/<script\s*src=".*"><\/script>/si','',$tpl_base);
            if(!empty($echarts_imgs)){
                //文本+图片
                $tpl_base=preg_replace('/class="echarts_img"(.+?)src="(.+?)"(.*?)>/','class="echarts_img" src="${2}"/>',$tpl_base);

                $tpl_base=preg_replace('/(.+?)style="(.+?)"(.+?)/','${1} style="${2}" ${3}',$tpl_base);
                $tpl_base=preg_replace('/content="text\/html;\s*charset=utf-8"/','content="text/html; charset=gb2312"',$tpl_base);
                foreach($echarts_imgs as $img_name=>$img_path){
                    $domain = \Yii::$app->params['webapiDomian'];
                    $tpl_base = str_replace($img_name.".png",'http://'.$domain.'/show/intranet-img?url='.urlencode($img_path),$tpl_base);
                }
                $file_content = $tpl_base;
            }else{
                //纯文本
                $tpl_base=preg_replace('/<img(.+?)class="echarts_img".*?>/','',$tpl_base);
                $tpl_base=preg_replace('/content="text\/html;\s*charset=utf-8"/','content="text/html; charset=gb2312"',$tpl_base);
                $file_content = $tpl_base;
            }
        }catch (Exception $e){}
        return $file_content;
    }

    //生成word文档
    public static function createWORD($page_url,$echarts_box)
    {
        $file_content='';
        try {
            echo "\n---------creating image----------";
            $echarts_imgs = self::createEchartsImg($page_url,$echarts_box);
            echo "\n---------creating  word----------";
            //去掉页面中多余代码
            $tpl_base = @file_get_contents($page_url);
            $tpl_base=preg_replace('/<meta http-equiv="X-UA-Compatible" content="IE=edge">/','',$tpl_base);
            $tpl_base=preg_replace('/<script>.*<\/script>/si','',$tpl_base);
            $tpl_base=preg_replace('/<script\s*src=".*"><\/script>/si','',$tpl_base);
// 			$tpl_base= @iconv("utf-8","gb2312//IGNORE",$tpl_base);

            // word url 转码问题
//            $pattern = '/(?<=[\s]href=\")([\s\S]+?)(?=\")/';
//            $tpl_base = preg_replace_callback($pattern,function ($args){
//                return urlencode(@$args[0]);
//            },$tpl_base);

            $tpl_base = mb_convert_encoding($tpl_base, "gbk");
            if(!empty($echarts_imgs)){
                //文本+图片
                $tpl_base=preg_replace('/class="echarts_img"(.+?)src="(.+?)"(.*?)>/','class="echarts_img" src=3D"${2}"/>',$tpl_base);

                $tpl_base=preg_replace('/(.+?)style="(.+?)"(.+?)/','${1} style=3D"${2}" ${3}',$tpl_base);
                $tpl_base=preg_replace('/content="text\/html;\s*charset=utf-8"/','content=3D"text/html; charset=gb2312"',$tpl_base);
                $boundary=self::getFileBoundary();
                $file_content = self::getFileHeader($boundary)."\n\n".$tpl_base;
                foreach($echarts_imgs as $img_name=>$img_path){
                    $attachment=self::getFileAttachment($img_path,"{$img_name}.png",$boundary);
                    $file_content .= $attachment;
                }
                $file_content .= "\n--{$boundary}--";
            }else{
                //纯文本
                $tpl_base=preg_replace('/<img(.+?)class="echarts_img".*?>/','',$tpl_base);
                $tpl_base=preg_replace('/content="text\/html;\s*charset=utf-8"/','content="text/html; charset=gb2312"',$tpl_base);
                $file_content = $tpl_base;
            }
        }catch (Exception $e){}
        return $file_content;
    }

    public static function createReportWORD($tpl_selected,$briefdoc_name,$word_box_include,$echarts_imgs){
        $dir = './tmp/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $name = md5(microtime(true)) . '.docx';
        $file = $dir . $name;

        $phpWord = new PhpWord();
        $sectionStyle = ['pageSizeW'=>20000,'pageSizeH'=>30000];
        $section = $phpWord->addSection($sectionStyle);
        // 样式
        $bigTitleFontStyle = ['size'=>20,'color'=>'47a0e4','bold'=>true];
        $bigTitleParagraphStyle = ['align'=>'center','spaceBefore'=>200];

        $smallTitleFontStyle = ['size'=>16,'color'=>'47a0e4'];
        $smallTitleParagraphStyle =  ['align'=>'left','spaceBefore'=>200,'spaceAfter'=>100,'spacing'=>100];

        $textFontStyle =  ['size'=>14,'align'=>'left'];
        $textParagraphStyle =  ['align'=>'left','spaceAfter'=>200,'spacing'=>50];

        $normalTableStyle = ['borderColor'=>'006699','borderSize'=>6,'cellMargin'=>50,'valign'=>'center',
            'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
            'layout'=>\PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED
        ];
        $normalTableFirstRowStyle = ['bgColor'=>'66BBFF'];
        $phpWord->addTableStyle('normalTableStyle', $normalTableStyle, $normalTableFirstRowStyle);

        $tableChartsStyle = ['borderColor'=>'ffffff','borderSize'=>0,'cellMargin'=>10,'valign'=>'center',
            'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
            'layout'=>\PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED
        ];
        $tableChartsFirstRowStyle = ['bgColor'=>'edf1f7'];
        $phpWord->addTableStyle('tableChartsStyle', $tableChartsStyle, $tableChartsFirstRowStyle);

        $insideTableChartsStyle = ['borderColor'=>'006699','borderSize'=>1,'cellMargin'=>0,'valign'=>'center',
            'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
            'layout'=>\PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED
        ];
        $insideTableChartsFirstRowStyle = ['bgColor'=>'edf1f7','valign'=>'center'];
        $phpWord->addTableStyle('insideTableChartsStyle', $insideTableChartsStyle, $insideTableChartsFirstRowStyle);

        $firstRowStyle = ['bgColor'=>'edf1f7','valign'=>'center'];

        $cellStyle = ['valign'=>'center'];
        $cellFontStyle = ['size'=>14,'bold'=>true, 'align'=>'center'];
        $cellParagraphStyle = ['align'=>'center'];

        // 红头文件
        if($tpl_selected['open_red_header']){
            $section->addText(self::wordCharTrans($tpl_selected['red_title']),['size'=>30,'color'=>'ed233d','bold'=>true],['align'=>'center','spaceAfter'=>200]);
            $section->addText(self::wordCharTrans($tpl_selected['red_number']),['size'=>16,'color'=>'666666','bold'=>true],['align'=>'center','spaceAfter'=>150]);
            $section->addImage('http://bsddata.oss-cn-hangzhou.aliyuncs.com/yuqing_oem/html/red_line.png',['width'=>860,'height'=>'30','align'=>'center']);

        }

        // 标题
        $section->addText($briefdoc_name,$bigTitleFontStyle,$bigTitleParagraphStyle);
        $section->addTextBreak();
        $ii = 1;
        foreach($word_box_include as $k => $v){
            $word = $v['word'];
            $word['title'] = self::wordCharTrans($word['title']);
            $section->addText("{$ii}、{$word['title']}",$smallTitleFontStyle,$smallTitleParagraphStyle);
            if(!empty($word['desc'])){
                $section->addText(self::wordCharTrans($word['desc']),$textFontStyle,$textParagraphStyle);
            }
            if(isset($word['data_type'])){
                if($word['data_type'] == 'table'){
                    $table = $section->addTable('normalTableStyle');
                    for($i=0;$i<count($word['data']);$i++){
                        $j = 0;
                        if($i==0){
                            $table->addRow(600);
                            foreach($word['data'][$i] as $n){
                                $cell = $table->addCell(self::getCellWidth($word['title'],$j),$firstRowStyle);
                                $cell->addText($n,$cellFontStyle,$cellParagraphStyle);
                                $j++;
                            }
                        }else{
                            $table->addRow(600);
                            foreach($word['data'][$i] as $n){
                                $cell = $table->addCell(self::getCellWidth($word['title'],$j),$cellStyle);
                                if(is_array($n)){
                                    if($n['type'] == 'img'){
                                        $cell->addImage(self::wordCharTrans($n['url']),['width'=>30,'height'=>30]);
                                    }elseif($n['type'] == 'url'){
                                        $cell->addLink(self::wordCharTrans($n['url']),self::wordCharTrans($n['title']),$cellFontStyle);
                                    }
                                }else{
                                    $cell->addText(self::wordCharTrans($n),$cellFontStyle,$cellParagraphStyle);
                                }
                                $j++;
                            }
                        }
                    }
                }elseif($word['data_type'] == 'table-charts'){
                    $table0 = $section->addTable('tableChartsStyle');
                    $table0->addRow();
                    $table = $table0->addCell(2000)->addTable('insideTableChartsStyle');
                    if(array_key_exists("get_briefdoc_{$k}_box",$echarts_imgs)){
                        echo "get_briefdoc_{$k}_box\n";
                        $table0->addCell()->addImage(self::getWordImg($echarts_imgs["get_briefdoc_{$k}_box"]),['width'=>400,'height'=>270]);
                    }
                    for($i=0;$i<count($word['data']);$i++){
                        $j = 0;
                        if($i==0){
                            $table->addRow(610);
                            foreach($word['data'][$i] as $n){
                                $cell = $table->addCell(self::getCellWidth($word['title'],$j),$firstRowStyle);
                                $cell->addText($n,$cellFontStyle,$cellParagraphStyle);
                                $j++;
                            }
                        }else{
                            $table->addRow(590);
                            foreach($word['data'][$i] as $n){
                                $cell = $table->addCell(self::getCellWidth($word['title'],$j),$cellStyle);
                                if(is_array($n)){
                                    if($n['type'] == 'img'){
                                        $cell->addImage(self::wordCharTrans($n['url']),['width'=>30,'height'=>30]);
                                    }elseif($n['type'] == 'url'){
                                        $cell->addLink(self::wordCharTrans($n['url']),self::wordCharTrans($n['title']),$cellFontStyle);
                                    }
                                }else{
                                    $cell->addText(self::wordCharTrans($n),$cellFontStyle,$cellParagraphStyle);
                                }
                                $j++;
                            }
                        }
                    }
                }elseif($word['data_type'] == 'table-other') {
                    //只有下边框的表格
                    if(isset($word['function'])) {
                        $function = $word['function'];
                        self::$function($section, $word, $cellStyle, $cellFontStyle, $cellParagraphStyle);
                    }
                }
            }else{
                if(array_key_exists("get_briefdoc_{$k}_box",$echarts_imgs)){
                    echo "get_briefdoc_{$k}_box\n";
                    $section->addImage(self::getWordImg($echarts_imgs["get_briefdoc_{$k}_box"]),['width'=>500,'height'=>200,'align'=>'center']);
                }
            }
            $ii++;
        }
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($file);
        return $file;
    }

    /**
     * 生成分析词榜单word
     * @param $section
     * @param $data
     * @param $cellStyle
     * @param $cellFontStyle
     * @param $cellParagraphStyle
     */
    private static function createAnalyticalWordsWord($section, $data, $cellStyle, $cellFontStyle, $cellParagraphStyle) {
        $table = $section->addTable('onlyBottomBorderTableStyle');
        for($i=0;$i<count($data['data']);$i++){
            $table->addRow(600);
            $cell = $table->addCell(self::getCellWidth($data['title'],0),array_merge(
                $cellStyle, self::getCellStyle($data['title'])));
            $_cellParagraphStyle = $cellParagraphStyle;
            $_cellParagraphStyle['align'] = 'left';
            $cell->addText(self::wordCharTrans($data['data'][$i]['name']), $cellFontStyle, $_cellParagraphStyle);
            $cell = $table->addCell(self::getCellWidth($data['title'],1), array_merge(
                $cellStyle, self::getCellStyle($data['title'])));
            $_cellParagraphStyle['align'] = 'right';
            $_cellFontStyle = array_merge($cellFontStyle, ['color' => '47a0e4']);
            $cell->addText(self::wordCharTrans($data['data'][$i]['value']), $_cellFontStyle, $_cellParagraphStyle);
        }
    }

    public static function createCompareWORD($compareData,$params,$echartsImgs){
        $dir = './tmp/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $name = md5(microtime(true)) . '.docx';
        $file = $dir . $name;

        $phpWord = new PhpWord();
        $sectionStyle = ['pageSizeW'=>20000,'pageSizeH'=>30000];
        $section = $phpWord->addSection($sectionStyle);
        // 样式
        $titleFontStyle = ['size'=>18,'bold'=>true];
        $titleParagraphStyle = ['align'=>'center'];

        $moduleFontStyle = ['size'=>18,'bold'=>true];
        $moduleParagraphStyle = ['spaceBefore'=>200,'spaceAfter'=>100,'spacing'=>100];

        $contentFontStyle = ['size'=>14];
        $contentParagraphStyle = ['spaceBefore'=>50,'spaceAfter'=>50,'spacing'=>100];

        $totalTableStyle = ['borderSize'=>6,'borderColor'=>'EDF1F7','cellMargin'=>50,'valign'=>'center',
            'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER
        ];
        $phpWord->addTableStyle('totalTableStyle', $totalTableStyle);

        // 标准表格
        $normalTableStyle = ['borderColor'=>'006699','borderSize'=>6,'cellMargin'=>50,'valign'=>'center',
            'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
            'layout'=>\PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED
        ];
        $normalTableFirstRowStyle = ['bgColor'=>'66BBFF'];
        $phpWord->addTableStyle('normalTableStyle', $normalTableStyle, $normalTableFirstRowStyle);
        //无边框表格
        $noBorderTableStyle = ['borderColor'=>'ffffff','borderSize'=>0,'cellMargin'=>50,'valign'=>'center',
            'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
            'layout'=>\PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED
        ];
        $phpWord->addTableStyle('noBorderTableStyle', $noBorderTableStyle);

        $textrun = $section->addTextRun($titleParagraphStyle);
        $count = 2;
        if(isset($compareData['schemeName'])){
            $count = count($compareData['schemeName']);
            if(isset($compareData['schemeName'][0])){
                $textrun->addText(self::wordCharTrans($compareData['schemeName'][0]),['size'=>18,'bold'=>true,'color'=>'2475fb']);
            }
            if(isset($compareData['schemeName'][1])){
                $textrun->addText(' VS ',$titleFontStyle);
                $textrun->addText(self::wordCharTrans($compareData['schemeName'][1]),['size'=>18,'bold'=>true,'color'=>'e52e45']);
            }
            if(isset($compareData['schemeName'][2])){
                $textrun->addText(' VS ',$titleFontStyle);
                $textrun->addText(self::wordCharTrans($compareData['schemeName'][2]),['size'=>18,'bold'=>true,'color'=>'f76a44']);
            }
            $section->addTextBreak();

            $startTime = date('Y年m月d日 H:i:s',strtotime(@$params['startTime']));
            $endTime = date('Y年m月d日 H:i:s',strtotime(@$params['endTime']));
            $section->addText($startTime."--".$endTime,['size'=>14],$titleParagraphStyle);
            $section->addTextBreak(2);
        }
        // 数据汇总
        if(isset($compareData['total'])){
            $table = $section->addTable('totalTableStyle');
            $table->addRow(600);
            $table->addCell(7000,['bgColor'=>'EDF1F7','valign'=>'center'])->addText('方案名称',['size'=>14,'bold'=>true],['align'=>'left']);
            $table->addCell(4000,['bgColor'=>'EDF1F7','valign'=>'center'])->addText('数据总量',['size'=>14,'bold'=>true],['align'=>'center']);
            $table->addCell(7000,['bgColor'=>'EDF1F7','valign'=>'center'])->addText('分析词组',['size'=>14,'bold'=>true],['align'=>'right']);
            foreach($compareData['total'] as $v){
                $table->addRow(600);
                $table->addCell(7000,['valign'=>'center'])->addText($v['name'],['size'=>14,'bold'=>true],['align'=>'left']);
                $table->addCell(4000,['valign'=>'center'])->addText($v['num'],['size'=>14],['align'=>'center']);
                $table->addCell(7000,['valign'=>'center'])->addText(implode(',',$v['keywords']),['size'=>14],['align'=>'right']);
            }
            $section->addTextBreak(2);
        }
        // 趋势对比
        if(isset($compareData['trend'])){
            $section->addText('趋势对比',$moduleFontStyle,$moduleParagraphStyle);
            if(isset($echartsImgs['get_briefdoc_yq_trend_box'])){
                $section->addImage(self::getWordImg($echartsImgs['get_briefdoc_yq_trend_box']),['width'=>800,'height'=>260,'align'=>'center']);
            }
            $section->addText(@$compareData['trend']['html'],$contentFontStyle,$contentParagraphStyle);
        }
        // 平台分布
        if(isset($compareData['platforms'])){
            $section->addText('平台分布',$moduleFontStyle,$moduleParagraphStyle);
            if(isset($echartsImgs['get_briefdoc_platform_box'])){
                $section->addImage(self::getWordImg($echartsImgs['get_briefdoc_platform_box']),['width'=>800,'height'=>260,'align'=>'center']);
            }
            $section->addText(@$compareData['platforms']['html'],$contentFontStyle,$contentParagraphStyle);
        }

        $width = $count == 2 ? 400 : 250;
        $height = $count == 2 ? $width - 160 : $width - 50;
        // 情感分布
        if(isset($compareData['emotions'])){
            $section->addText('情感分布',$moduleFontStyle,$moduleParagraphStyle);
            $table = $section->addTable('noBorderTableStyle');
            $table->addRow();
            foreach($compareData['schemeName'] as $v){
                $table->addCell(6000)->addText("【{$v}】",['size'=>14,'bold'=>true],['align'=>'center']);
            }
            $table->addRow();
            if(isset($echartsImgs['get_briefdoc_media_distribute_box'])){
                $table->addCell(6000)->addImage(self::getWordImg($echartsImgs['get_briefdoc_media_distribute_box']),['width'=>$width,'height'=>$height,'align'=>'center']);
            }
            if(isset($echartsImgs['get_briefdoc_media_distribute_box1'])){
                $table->addCell(6000)->addImage(self::getWordImg($echartsImgs['get_briefdoc_media_distribute_box1']),['width'=>$width,'height'=>$height,'align'=>'center']);
            }
            if(isset($echartsImgs['get_briefdoc_media_distribute_box2'])){
                $table->addCell(6000)->addImage(self::getWordImg($echartsImgs['get_briefdoc_media_distribute_box2']),['width'=>$width,'height'=>$height,'align'=>'center']);
            }
            $section->addText(@$compareData['emotions']['html'],$contentFontStyle,$contentParagraphStyle);
        }
        // 热门主题词
        if(isset($compareData['hotWords'])){
            $section->addText('热门主题词',$moduleFontStyle,$moduleParagraphStyle);
            $table = $section->addTable('noBorderTableStyle');
            $table->addRow();
            foreach($compareData['schemeName'] as $v){
                $table->addCell(6000)->addText("【{$v}】",['size'=>14,'bold'=>true],['align'=>'center']);
            }
            $table->addRow();
            if(isset($echartsImgs['get_briefdoc_hotwords_box'])){
                $table->addCell(6000)->addImage(self::getWordImg($echartsImgs['get_briefdoc_hotwords_box']),['width'=>$width,'height'=>$height,'align'=>'center']);
            }
            if(isset($echartsImgs['get_briefdoc_hotwords_box1'])){
                $table->addCell(6000)->addImage(self::getWordImg($echartsImgs['get_briefdoc_hotwords_box1']),['width'=>$width,'height'=>$height,'align'=>'center']);
            }
            if(isset($echartsImgs['get_briefdoc_hotwords_box2'])){
                $table->addCell(6000)->addImage(self::getWordImg($echartsImgs['get_briefdoc_hotwords_box2']),['width'=>$width,'height'=>$height,'align'=>'center']);
            }
            $section->addText(@$compareData['hotWords']['html'],$contentFontStyle,$contentParagraphStyle);
        }
        // 发布地区
        if(isset($compareData['publishAreas'])){
            $section->addText('发布地区',$moduleFontStyle,$moduleParagraphStyle);
            $table = $section->addTable('noBorderTableStyle');
            $table->addRow();
            foreach($compareData['schemeName'] as $v){
                $table->addCell(6000)->addText("【{$v}】",['size'=>14,'bold'=>true],['align'=>'center']);
            }
            $table->addRow();
            if(isset($echartsImgs['get_briefdoc_hotpubarea_box'])){
                $table->addCell(6000)->addImage(self::getWordImg($echartsImgs['get_briefdoc_hotpubarea_box']),['width'=>$width,'height'=>$height,'align'=>'center']);
            }
            if(isset($echartsImgs['get_briefdoc_hotpubarea_box1'])){
                $table->addCell(6000)->addImage(self::getWordImg($echartsImgs['get_briefdoc_hotpubarea_box1']),['width'=>$width,'height'=>$height,'align'=>'center']);
            }
            if(isset($echartsImgs['get_briefdoc_hotpubarea_box2'])){
                $table->addCell(6000)->addImage(self::getWordImg($echartsImgs['get_briefdoc_hotpubarea_box2']),['width'=>$width,'height'=>$height,'align'=>'center']);
            }
            $table->addRow();
            foreach($compareData['publishAreas']['data'] as $n){
                $top5 = array_slice($n['province'],0,5);
                $top5name = array_column($top5,'name');
                $top5num = array_column($top5,'value');
                $top6_10 = array_slice($n['province'],5,5);
                $top6_10name = array_column($top6_10,'name');
                $top6_10num = array_column($top6_10,'value');

                $table0 = $table->addCell(6000)->addTable('noBorderTableStyle');
                $table0->addRow();
                foreach($top5name as $v){
                    $table0->addCell(1200,['bgColor'=>'EDF1F7','valign'=>'center'])->addText($v,['size'=>14,'bold'=>true],['align'=>'center']);
                }
                $table0->addRow();
                foreach($top5num as $v){
                    $table0->addCell(1200)->addText($v,['size'=>14],['align'=>'center']);
                }
                $table0->addRow();
                foreach($top6_10name as $v){
                    $table0->addCell(1200,['bgColor'=>'EDF1F7','valign'=>'center'])->addText($v,['size'=>14,'bold'=>true],['align'=>'center']);
                }
                $table0->addRow();
                foreach($top6_10num as $v){
                    $table0->addCell(1200)->addText($v,['size'=>14],['align'=>'center']);
                }
            }
            $section->addText(@$compareData['publishAreas']['html'],$contentFontStyle,$contentParagraphStyle);
        }

        $cellWidth = $count == 2 ? 8000 : 6000;
        $maxProcessLen = $count == 2 ? 330 : 230;
        // 活跃账号
        if(isset($compareData['activeAccounts'])){
            $section->addText('活跃账号',$moduleFontStyle,$moduleParagraphStyle);
            $table = $section->addTable('noBorderTableStyle');
            $table->addRow();
            foreach($compareData['schemeName'] as $v){
                $table->addCell($cellWidth)->addText("【{$v}】",['size'=>14,'bold'=>true],['align'=>'center']);
            }
            $table->addRow();
            foreach($compareData['activeAccounts']['data'] as $v){
                $max = isset($v[0]['value'])?$v[0]['value']:1;
                $table0 = $table->addCell($cellWidth)->addTable('noBorderTableStyle');
                foreach($v as $n){
                    $table0->addRow();
                    $table0->addCell($cellWidth)->addText($n['name']);
                    $table0->addCell();
                    $table0->addCell();
                    $table0->addRow();
                    $processLen = floor(($n['value']/$max)*$maxProcessLen);
                    $table0->addCell($cellWidth*0.8,['valign'=>'center'])->addImage(__DIR__.'/../../webapi/web/images/process.png',['width'=>$processLen,'height'=>10]);
                    $table0->addCell($cellWidth*0.17,['valign'=>'center'])->addText($n['value'],['size'=>14,'color'=>'00c2eb'],['align'=>'right']);
                    $table0->addCell($cellWidth*0.03);
                }
            }
            $section->addText(self::wordCharTrans(@$compareData['activeAccounts']['html']),$contentFontStyle,$contentParagraphStyle);
        }

        $limitLen = $count == 2 ? 26 : 17;
        // 热点文章
        if(isset($compareData['hotArticles'])){
            $section->addText('热点文章',$moduleFontStyle,$moduleParagraphStyle);
            $table = $section->addTable('noBorderTableStyle');
            $table->addRow();
            foreach($compareData['schemeName'] as $v){
                $table->addCell($cellWidth)->addText("【".self::wordCharTrans($v)."】",['size'=>14,'bold'=>true],['align'=>'center']);
            }
            $table->addRow();
            foreach($compareData['hotArticles']['data'] as $v){
                $table0 = $table->addCell($cellWidth)->addTable('noBorderTableStyle');
                $table0->addRow(600);
                $table0->addCell($cellWidth*0.8,['valign'=>'center','borderBottomSize'=>1,'borderBottomColor'=>'EDF1F7'])->addText('标题',['size'=>12,'bold'=>true]);
                $table0->addCell($cellWidth*0.17,['valign'=>'center','borderBottomSize'=>1,'borderBottomColor'=>'EDF1F7'])->addText('相似文章数',['size'=>12,'bold'=>true],['align'=>'right']);
                $table0->addCell($cellWidth*0.03);
                foreach($v as $n){
                    $table0->addRow(600);
                    $table0->addCell($cellWidth*0.8,['valign'=>'center','borderBottomSize'=>1,'borderBottomColor'=>'EDF1F7'])->addLink(self::wordCharTrans($n['newsInfo']['news_url']),self::limitText(self::wordCharTrans($n['newsInfo']['news_title']),$limitLen),['size'=>12]);
                    $table0->addCell($cellWidth*0.17,['valign'=>'center','borderBottomSize'=>1,'borderBottomColor'=>'EDF1F7'])->addText($n['numFound'],['size'=>12],['align'=>'right']);
                    $table0->addCell($cellWidth*0.03);
                }
            }
        }
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($file);
        return $file;
    }

    /**
     * 获取自定义cell样式
     * @param $module
     * @return array
     */
    private static function getCellStyle($module) {
        $cellStyles = [
            '分析词榜单' => ['borderBottomSize'=> 6,'borderColor'=>'e3e3e3'],
        ];
        if(isset($cellStyles[$module])){
            return $cellStyles[$module];
        }else{
            return [];
        }
    }

    // 返回每个table模块列的宽度
    public Static function getCellWidth($module = null,$i = null){
        $cellWidths = [
            '活跃账号' => [700,3000,1500],
            '敏感媒体' => [700,3000,1500],
            '影响力账号' => [700,3000,1500],
            '提及地区' => [1000,1000],
            '发布地区' => [1000,1000],
            '负面文章' => [600,1400,2000,8000,3000,800],
            '热门文章' => [600,1400,2000,8000,3000,800],
            '分析词榜单' => [9000, 9000],
        ];
        if(isset($cellWidths[$module][$i])){
            return $cellWidths[$module][$i];
        }else{
            return null;
        }
    }
    // 处理图片在本地无法访问
    public static function getWordImg($url){
        if(YII_ENV == 'local'){
//            return "http://n.sinaimg.cn/mil/8_img/upload/21a18b46/400/w1280h720/20200215/5f0a-ipmxpwa2094262.jpg";
            return "http://yuqing.gsdata.cn/v5/show/intranet-img?url=".urlencode($url); // 用测试站地址会报错
        }else{
            return $url;
        }
    }
    // 处理&字符
    public static function wordCharTrans($value){
        $value = str_replace('&','&amp;',$value);
        $value = str_replace('<','&lt;',$value);
        $value = str_replace('>','&gt;',$value);
        $value = str_replace('\'','&apos;',$value);
        $value = str_replace('"','&quot;',$value);
        return $value;
    }

    public static function limitText($text,$len = 17){
        if(mb_strlen($text) >= 20){
            return mb_substr($text,0,$len)."...";
        }else{
            return $text;
        }
    }

    //生成pdf文件
    public static function createPDF($page_url, $test = FALSE)
    {
        //注意服务器tmp文件是否创建
        $file_content='';
        try {
            echo "\n-----creating PDF----\n";
            $root =  dirname(\Yii::$app->BasePath);
            if(!is_dir("{$root}./tmp/")) {
                mkdir("{$root}./tmp/",0777,true);
                chmod($path, 0777);
            }
            if ($test) {
                $file_save_tmp= $root. "/tmp/".getUUID().".pdf";
                $cmd = 'C:\Users\admin\wkhtmltopdf\bin\wkhtmltopdf --page-size A3 --image-quality 100 --javascript-delay 10000 --minimum-font-size 20 "' . $page_url . '" ' . $file_save_tmp;
            } else {
                $file_save_tmp= $root."/tmp/".getUUID().".pdf";
                $bath = YII_ENV == "pro" ? "wkhtmltopdf" : "/usr/local/src/wkhtmltox/bin/wkhtmltopdf";
                $cmd = $bath.' --page-size A3 --image-quality 100 --javascript-delay 10000 --minimum-font-size 20 "' . $page_url . '" ' . $file_save_tmp;
            }
            @shell_exec($cmd);
            if(file_exists($file_save_tmp)){
                $file_content = @file_get_contents($file_save_tmp);
                @unlink($file_save_tmp);
            }
        }catch (Exception $e){}
        return $file_content;
    }

    /*****************************************工具方法****************************************/

    //生成word文档用到下面的3个方法
    public static function getFileHeader($boundary){
        $file_header="MIME-version: 1.0";
        $file_header.="\nContent-Type: multipart/related;boundary=\"{$boundary}\";";
        $file_header.="\nThis is a multi-part message in MIME format.";
        $file_header.="\n\n--{$boundary}";
        $file_header.="\nContent-Type: text/html;charset=\"gb2312\"";
        $file_header.="\nContent-Transfer-Encoding: quoted-printable";
        return $file_header;
    }
    //文件定界符
    public static function getFileBoundary()
    {
        return '--' . strtoupper(md5(mt_rand())) . '_MULTIPART_MIXED';
    }

    //文件图片附件
    public static function getFileAttachment($img_path,$img_name,$boundary){
        $attachment="\n--{$boundary}";
        $attachment.="\nContent-Type: image/png";
        $attachment.="\nContent-Transfer-Encoding: base64";
        $attachment.="\nContent-Location: {$img_name}";
        $attachment.="\n\n".base64_encode(file_get_contents($img_path));;
        return $attachment;
    }

    //创建图片
    public static function createEchartsImg($page_url,$echarts_box)
    {
        if(empty($echarts_box)){
            $echarts_imgs = [];
        }else{
            $post_data_json = json_encode(array('url' => $page_url, 'funcs' => implode(',', $echarts_box), 'delay' => 100));

            $ch = curl_init("http://10.46.70.31:3100");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS,$post_data_json);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_data_json)));
            $resp = curl_exec($ch);
            curl_close($ch);
            if(!empty($resp)){
                $resp = json_decode($resp,true);
                $echarts_imgs = isset($resp['imgs'])&&$resp['imgs']&&is_array($resp['imgs']) ? $resp['imgs'] : [];
            }else{
                $echarts_imgs = [];
            }
        }
        return $echarts_imgs;
    }
    /**
     * 为了word url 转移
     * 参入参数格式
     * array(2) {
    [0] =>
    string(60) "http://weibo.com/6313072047/Gmequf2Xy?refer_flag=1001030103_"
    [1] =>
    string(60) "http://weibo.com/6313072047/Gmequf2Xy?refer_flag=1001030103_"
    }
     */
    public static function urlEncodes($args){
        return urlencode(@$args[0]);
    }


}