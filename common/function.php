<?php

use webapi\models\SysLog;
use webapi\services\CompanyService;

function jsonSuccessReturn($data = [], $msg = 'success'){
    $data = [
        'code' => 10000,
        "msg"  => $msg,
        "data" => $data
    ];
    $response = Yii::$app->getResponse();
    $response->format = yii\web\Response::FORMAT_JSON;
    $response->data = $data;
    return $response;
}


function jsonErrorReturn($codeName,$msg = '',$data=[]){
    $codeMsg = codeMsg($codeName, $msg);
    $data = [
        'code' => $codeMsg['code'] ,
        "msg"  => $codeMsg['msg'],
        "data" => $data
    ];
    header('Content-type: application/json');
    echo  json_encode($data);die;
}

/**
 * @return webapi\services\UserService
 */
function UserService(){
    /** @var webapi\services\UserService $service */
    $service =  Yii::$app->service->UserService;
    return $service;
}

/**
 * @return webapi\services\SchemeService
 */
function SchemeService(){
    /** @var webapi\services\SchemeService $service */
    $service =  Yii::$app->service->SchemeService;
    return $service;
}

/**
 * @return webapi\services\RpcService
 */
function RpcService(){
    /** @var webapi\services\RpcService $service */
    $service =  Yii::$app->service->RpcService;
    return $service;
}

/**
 * @return webapi\services\CacheService
 */
function CacheService(){
    /** @var webapi\services\CacheService $service */
    $service =  Yii::$app->service->CacheService;
    return $service;
}


/**
 * @return webapi\services\WarnService
 */
function WarnService(){
    /** @var webapi\services\WarnService $service */
    $service =  Yii::$app->service->WarnService;
    return $service;
}

/**
 * @return webapi\services\WarnService
 */
function UserWarningService(){
    /** @var webapi\services\UserWarningService $service */
    $service =  Yii::$app->service->UserWarningService;
    return $service;
}

/**
 * @return webapi\services\AnalysisService
 */
function AnalysisService(){
    /** @var webapi\services\AnalysisService $service */
    $service =  Yii::$app->service->AnalysisService;
    return $service;
}

/**
 * @return webapi\services\RankService
 */
function RankService(){
    /** @var webapi\services\RankService $service */
    $service =  Yii::$app->service->RankService;
    return $service;
}

/**
 * @return webapi\services\RankListService
 */
function RankListService(){
    /** @var webapi\services\RankListService $service */
    $service =  Yii::$app->service->RankListService;
    return $service;
}

/**
 * @return webapi\services\RegionService
 */
function RegionService(){
    /** @var webapi\services\RegionService $service */
    $service =  Yii::$app->service->RegionService;
    return $service;
}

/**
 * @return webapi\services\TagsService
 */
function TagsService(){
    /** @var webapi\services\TagsService $service */
    $service =  Yii::$app->service->TagsService;
    return $service;
}

/**
 * @return webapi\services\ContactsService
 */
function ContactsService(){
    /** @var webapi\services\ContactsService $service */
    $service =  Yii::$app->service->ContactsService;
    return $service;
}

/**
 * @return webapi\services\ComplianceService
 */
function ComplianceService(){
    /** @var webapi\services\ComplianceService $service */
    $service =  Yii::$app->service->ComplianceService;
    return $service;
}
/**
 * @return webapi\services\ContentService
 */
function ContentService(){
    /** @var webapi\services\ContentService $service */
    $service =  Yii::$app->service->ContentService;
    return $service;
}

function httpRequest($url, $headers = [], $method = 'GET', $params = null , $time_out = 0){

    if (is_array($params)) {
        if($method == 'GET'){
            $requestString = http_build_query($params);
        }else{
            $requestString = json_encode($params);
        }
    } else {
        $requestString = $params ? : '';
    }
    if (empty($headers)) {
        $headers = array('Content-type: text/json');
    } elseif (!is_array($headers)) {
        parse_str($headers,$headers);
    }
    // setting the curl parameters.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HEADER,0);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, $time_out);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    // setting the POST FIELD to curl
    switch ($method){
        case "GET" : curl_setopt($ch, CURLOPT_HTTPGET, 1);break;
        case "POST": curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);break;
        case "PUT" : curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);break;
        case "DELETE":  curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);break;
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    if($errno && $httpCode != '200'){
        if(is_array($headers)) {
            $headersStr = implode(';', $headers);
        } else {
            $headersStr = $headers;
        }
        if($headersStr && (strpos($headersStr, 'Authorization:') !== false)) {
            /*Yii::$app -> service -> UserCenterService -> createEsQueryErrorLog(
                $requestString,
                [
                    'curl_errno' => $errno,
                    'http_code'  => $httpCode
                ]
            );*/
        }
        return false;
    }
    //close the connection
    curl_close($ch);
    return $response;
}

/**
　　* 下划线转驼峰
　　* 思路:
　　* step1.原字符串转小写,原字符串中的分隔符用空格替换,在字符串开头加上分隔符
　　* step2.将字符串中每个单词的首字母转换为大写,再去空格,去字符串首部附加的分隔符.
*/
function toCamelCase($uncamelized_words,$separator='_')
{
    $uncamelized_words = $separator. str_replace($separator, " ", strtolower($uncamelized_words));
    return ltrim(str_replace(" ", "", ucwords($uncamelized_words)), $separator );
}

/**
　　* 驼峰命名转下划线命名
　　* 思路:
　　* 小写和大写紧挨一起的地方,加上分隔符,然后全部转小写
　　*/
function toUnderScore($camelCaps,$separator='_')
{
    return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
}

/**
 * @param $data
 * @return array|string
 * 把驼峰的数据 转换为 下划线的数据
 */
function switchDataToCamelCase($data){
    if (is_string($data)) return toUnderScore($data);
    if (is_array($data)){
        $return = [];
        foreach ($data as $key => $item){
            $return[toUnderScore($key)] = $item;
        }
        return $return;
    }
    return $data;
}

/**
　　* 下划线命名转驼峰命名
　　*$ucfirst 为true 首字母也为大写 false 则首字母不大写
　　*/
function convertUnderline ( $str , $ucfirst = false)
{
    $str = ucwords(str_replace('_', ' ', $str));
    $str = str_replace(' ','',lcfirst($str));
    return $ucfirst ? ucfirst($str) : $str;
}
/**
 * @param $data
 * @return array|string
 * 把下划线的数据 转换为 驼峰的数据
 * 最多支持二维数组
 */
function camelCaseToSwitchData($data){
    if (is_string($data)) return convertUnderline($data);
    if (is_array($data)){
        $return = [];
        foreach ($data as $key => $item){
            if(is_array($item)){
                $returnChild = [];
                foreach ($item as $kk => $vv){
                    $returnChild[convertUnderline($kk)] = $vv;
                }
                $return[convertUnderline($key)] = $returnChild;
            }else{
                $return[convertUnderline($key)] = $item;
            }
        }
        return $return;
    }
    return $data;
}

function dd($data){
    var_dump($data);die;
}

function getFirstError($errors){
    $e = "";
    foreach ($errors as $error){
        $e = $error[0];
        break;
    }
    return $e;
}

/**
 * @param string $codeName code配置名
 * @param string $msg 自定义错误信息
 * @return array
 */
function codeMsg($codeName, $msg = '') {
    if (!isset(Yii::$app->params['code'])){
        return  $codeMsg = [
            'code' => -100,
            'msg'  => 'error'
        ];
    }
    $codeArr = Yii::$app->params['code'];
    $codeMsg = [
        'code' => -100,
        'msg'  => ''
    ];
    if(isset($codeArr[$codeName]) && isset($codeArr[$codeName]['code'])) {
        $codeMsg['code'] = $codeArr[$codeName]['code'];
        $codeMsg['msg'] = $msg?$msg:(isset($codeArr[$codeName]['msg'])?$codeArr[$codeName]['msg']:'');
    }
    return $codeMsg;
}

/**
 * 格式化金钱数(比如清贝、人民币等)
 * @param int|float $amount
 * @return int|string
 */
function formatMoney($amount) {
    return $amount?sprintf('%.2f', $amount):0;
}

/***
 * 生成随机编号
 * @return string
 */
function randomCode() {
    $code = date('Ymd') . rand(10, 99) . time() . rand(10000, 99999);
    return $code;
}
/**
 * 创建唯一的ID
 * @return string
 */
function getUUID()
{
    return md5(microtime(true) . uniqid(mt_rand(), true));
}

/**
 * 补全html标签
 * @param $originalHtml
 * @param bool $onlyBody
 * @return string
 */
function replenishHtmlTag($originalHtml,$onlyBody = true){
    $doc = new DOMDocument();
//    $internalErrors = libxml_use_internal_errors(true);
    $meta = '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head>';
    @$doc->loadHTML($meta.$originalHtml);
//    libxml_use_internal_errors($internalErrors);
    $html = $doc->saveHTML();   // 多出来<!DOCTYPE> <html>  <body>
    if($onlyBody){
        $reg = "/<body>(.+)<\/body>/s";
        if(preg_match($reg,$html,$match)){
            return $match[1];
        }else{
            return $originalHtml;
        }
    }else{
        return $html;
    }
}

/**
 * 获取html正文
 * @param $string
 * @return string
 */
function cutstr_html($string){
    $string = replenishHtmlTag($string,true);   // 补全标签
    $string = preg_replace('#<script[^>]*?>.*?</script>#si','',$string);
    $string = preg_replace('#<style[^>]*?>.*?</style>#si','',$string);
    $string = strip_tags($string);
    return trim($string);
}

/**
 * 高亮文章关键词，并保留标签中的关键字
 * @param $content
 * @param $keywords
 * @param string $charset
 * @return string
 */
function highlight($content, $keywords,$ambiguous = [],$tag = 'i'){
    $newAmbiguous = array_map(function ($value){
            return md5($value);
    },$ambiguous);
    $new_content = str_replace($ambiguous,$newAmbiguous,$content);
    if($tag == 'i'){
        $newKeywords = array_map( function ($value) {
            return "<i class='color-red'>".$value."</i>";
        }, $keywords );
    }else{
        $newKeywords = array_map( function ($value) {
            return '<em>'.$value."</em>";
        }, $keywords );
    }
    $new_content = str_ireplace($keywords,$newKeywords,$new_content);
    $new_content = str_replace($newAmbiguous,$ambiguous,$new_content);
    return $new_content;
}

//把歧义词进行替换处理
function replaceAmbiguousKeywords($content, $keywords, $charset = "utf-8"){
    libxml_use_internal_errors(true);
    $ret = $content;
    $hasBody = false;
    foreach ($keywords as $keyword) {
        if (strpos(trim($content), "<body") === 0) {
            // 类似一点资讯  自带<body>.........
            $content = "<html><head><meta http-equiv='content-type' content='text/html;charset={$charset}'></head>{$content}</html>";
            $hasBody = false;
        }elseif (strpos(trim($content), "<body") !== 0) {
            // 有body  但不是第一位
            $content = "<html><head><meta http-equiv='content-type' content='text/html;charset={$charset}'></head>{$content}</html>";
            $hasBody = false;
        }else{
            // 彻底找不到body   估计是只有正文
            $content = "<html><head><meta http-equiv='content-type' content='text/html;charset={$charset}'></head><body>{$content}</body></html>";
            $hasBody = true;
        }

        $dom = new DomDocument();
        $dom->loadHtml($content);
        $xpath = new DomXpath($dom);

        $elements = $xpath->query('//*[contains(.,"' . $keyword . '")]');

        if (!empty($elements)) {
            foreach ($elements as $element) {
                foreach ($element->childNodes as $child) {
                    if (!$child instanceof DomText) {
                        continue;
                    };
                    $fragment = $dom->createDocumentFragment();
                    $text = $child->textContent;
                    $replace = [];
                    while (($pos = stripos($text, $keyword)) !== false) {
                        $fragment->appendChild(new DomText(substr($text, 0, $pos)));
                        $highlight = $dom->createElement('i');
                        $highlight->appendChild(new DomText($keyword));
                        $highlight->setAttribute('class', 'color-red');
                        $fragment->appendChild($highlight);
                        $text = substr($text, $pos + strlen($keyword));
                    }
                    if (!empty($text)) $fragment->appendChild(new DomText($text));
                    $element->replaceChild($fragment, $child);
                }
            }
            $ret = $dom->saveHTML($dom->getElementsByTagName('body')->item(0));
            if ($hasBody) {
                $ret = substr($ret, 6, -7);
            }
            $content = $ret;
        }

    }

//     	header("Content-type:text/html;charset=utf-8");
//          echo ($ret);
//         exit;

    return $ret;
}

/**
 * @param $str
 * @param string $format
 * @return bool
 */
function is_Date($str,$format='Y-m-d'){

    $unixTime_1=strtotime($str);

    if(!is_numeric($unixTime_1)) return false; //如果不是数字格式，则直接返回

    $checkDate=date($format,$unixTime_1);

    $unixTime_2=strtotime($checkDate);

    if($str==$checkDate){

        return true;

    }else{

        return false;

    }

}
/**
 * 验证手机号
 * @return string
 */
function verifyTel($tel)
{
    if (is_string($tel)) {
        if(!preg_match("/^1[3456789]\d{9}$/", $tel)){
            return ['msg'=>'手机号码'.$tel.'格式错误'];
        }else{
            return true;
        }
    }
    if (is_array($tel)){
        foreach ($tel as $key => $value){
            if (is_string($value)) {
                if (!preg_match("/^1[3456789]\d{9}$/", $value)) {
                    return ['msg'=>'手机号码'.$value.'格式错误'];
                }
            }else{
                return ['msg'=>'手机号码格式错误'];
            }
        }
        return true;
    }
    return ['msg'=>'手机号码格式错误'];
}
/**
 * 验证邮箱
 * @return string
 */
function verifyEmail($email)
{
    if (is_string($email)) {
        if(preg_match("/^1[3456789]\d{9}$/", $email)){
            return $return['msg']='邮箱'.$email.'格式错误';
        }else{
            return true;
        }
    }
    if (is_array($email)){
        foreach ($email as $key => $value){
            if (is_string($value)) {
                if(!filter_var($value, FILTER_VALIDATE_EMAIL)){
                    return ['msg'=>'邮箱'.$value.'格式错误'];
                }
            }else{
                return ['msg'=>'邮箱填写格式错误'];
            }
        }
        return true;
    }
    return ['msg'=>'邮箱填写格式错误'];
}

function replaceSpecialChar($strParam){
    $regex = "/\/|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\.|\/|\;|\'|\`|\-|\=|\\\|\|/";
    return preg_replace($regex,"",$strParam);
}

/**
 * 格式化后台搜索时间段为时间戳
 * @param string $timeStr
 * @return array
 */
function formatSearchTime($timeStr) {
    $arr = explode(' - ', $timeStr);
    if($arr && is_array($arr)) {
        $arr[0] = strtotime("$arr[0] 0:0:0");
        $arr[1] = strtotime("$arr[1] 23:59:59");
        return $arr;
    }
    return [];
}

function downloadcsv($file_name,$csv_header,$csv_body)
{
    // 头部标题
    $header = implode(',', $csv_header) . PHP_EOL;
    $content = '';
    foreach ($csv_body as $k => $v) {
        $content .= implode(',', $v) . PHP_EOL;
    }
    $csvData = $header . $content;
    header('Content-Encoding: gbk');
    header("Content-type:text/csv;");
    header("Content-Disposition:attachment;filename=" . $file_name);
    header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
    header('Expires:0');
    header('Pragma:public');
    echo chr(0xEF).chr(0xBB).chr(0xBF);  // 解决乱码
    echo $csvData;
}

/**
 * 格式化显示数字 大于1万转换为小数显示
 * @param $num
 * @return string
 */
function formatNum($num){
    if ($num>10000){
     return   round($num/10000,1)."万";
    }
     return $num;
}

/**
 * 处理新闻标题方法
 * @param $news_title
 * @return mixed
 */
function dealNewsTitle($news_title){
   return  str_replace([" ","\n"],"",strip_tags($news_title));
}


function randomStr($len){
    // 密码字符集，可任意添加你需要的字符
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ( $i = 0; $i < $len; $i++ ){
        $code .= $chars[ mt_rand(0, strlen($chars) - 1) ];
    }
    return $code;
}

//加密函数
function lock_data($txt,$key='qingbo')
{
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-=+";
    $nh = rand(0,64);
    $ch = $chars[$nh];
    $mdKey = md5($key.$ch);
    $mdKey = substr($mdKey,$nh%8, $nh%8+7);
    $txt = base64_encode($txt);
    $tmp = '';
    $i=0;$j=0;$k = 0;
    for ($i=0; $i<strlen($txt); $i++) {
        $k = $k == strlen($mdKey) ? 0 : $k;
        $j = ($nh+strpos($chars,$txt[$i])+ord($mdKey[$k++]))%64;
        $tmp .= $chars[$j];
    }
    return urlencode($ch.$tmp);
}
//解密函数
function unlock_data($txt,$key='qingbo')
{
    $txt = urldecode($txt);
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-=+";
    $ch = $txt[0];
    $nh = strpos($chars,$ch);
    $mdKey = md5($key.$ch);
    $mdKey = substr($mdKey,$nh%8, $nh%8+7);
    $txt = substr($txt,1);
    $tmp = '';
    $i=0;$j=0; $k = 0;
    for ($i=0; $i<strlen($txt); $i++) {
        $k = $k == strlen($mdKey) ? 0 : $k;
        $j = strpos($chars,$txt[$i])-$nh - ord($mdKey[$k++]);
        while ($j<0) $j+=64;
        $tmp .= $chars[$j];
    }
    return base64_decode($tmp);
}

// 通过ip定位地址
function ip2addr($ip){
    $url = "http://ip.taobao.com/service/getIpInfo.php?ip={$ip}";
    $result = httpRequest($url, [], 'GET', null, 1);
    $result = json_decode($result,true);
    if($result['code'] == 0){
        return $result['data'];
    }else{
        return false;
    }
}

function arrGroupByNum($arr,$nums){
    $data = [];
    if(is_array($nums)){
        $i = 0;
        foreach($nums as $num){
            $tmp = array_slice($arr,$i,$num);
            $data[] = $tmp;
            $i+=$num;
        }
    }else{
        for($i = 0;$i<count($arr);$i+=$nums){
            $tmp = array_slice($arr,$i,$nums);
            $data[] = $tmp;
        }
    }
    return $data;
}

function yqLog($level = 'error',$cate = 'php',$msg = '',$type = ''){
    $log = new \webapi\models\ShudiYiilog();
    $log->level = $level;
    $log->category = $cate;
    $log->message = $msg;
    $log->type = $type;
    $log->save();
}

function checkFile($file,$extensions = null,$size = 0){
    if(is_array($file)){
        if($file['error'] === 0){
            $fileArr = explode(".", $file["name"]);
            $extension = end($fileArr);
            if($extensions && !in_array($extension,$extensions)){
                return ['status' => false,'msg' => '文件类型不正确'];
            }
            if($size && $file['size'] > $size){
                return ['status' => false,'msg' => '文件大小超出限制'];
            }
            return ['status' => true,'msg' => '验证成功'];
        }else{
            return ['status' => false,'msg' => '文件上传失败'];
        }
    }else{

    }
}

function viewCode(){
    for($i = 0;$i <= 9;$i++){
        $code = randomStr(4);
        if(!\webapi\models\ViewCodeUrl::isRepeat($code)){
            return $code;
        }
    }
    return false;
}

function httpPost($url, $headers = [], $params = null , $time_out = 0){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if($headers) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt($ch, CURLOPT_HEADER,0);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, $time_out);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    // setting the POST FIELD to curl
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $errorNo = curl_errno($ch);
    if($errorNo && $httpCode != '200'){
        return false;
    }
    //close the connection
    curl_close($ch);
    return $response;
}

function httpGet($url, $headers = [], $params = [], $time_out = 0){
    $ch = curl_init();
    if($params){
        $queryString = http_build_query($params);
        $url = $url.'?'.$queryString;
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    $headers[] = 'Content-Type:application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HEADER,0);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, $time_out);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch);
    $errorNo = curl_errno($ch);
    if($errorNo && $httpCode != '200'){
        return false;
    }
    //close the connection
    curl_close($ch);
    return $response;
}

/**
 * 一维数组转多维，不适用大多数情况
 * @param $list
 * @param string $id
 * @param string $pid
 * @return array
 */
function tree($list, $id = 'id', $pid = 'pid',$name = 'name',$mate) {
    $data = [];
    $tops = $childrens = [];
    foreach($list as $row) {
        $data[$row[$id]] = $data[$row[$id]] ?? $row;
        $data[$row[$pid]]['name'] = $row[$name];
        $data[$row[$pid]]['children'][$row[$id]] = & $data[$row[$id]];
        $tops[] = $row[$pid];
        $childrens[] = $row[$id];
    }
    $tops = array_unique($tops);
    $childrens = array_unique($childrens);
    $diff = array_diff($tops,$childrens);
    foreach($diff as $k => $v){
        if(strpos($v,'weibo_') === 0){
            $data[$mate]['children']['微博']['children'][$v] = &$data[$v];
            continue;
        }
        if(strpos($v,'wx_') === 0){
            $data[$mate]['children']['微信']['children'][$v] = &$data[$v];
            continue;
        }
        if(strpos($v,'app_') === 0){
            $data[$mate]['children']['客户端']['children'][$v] = &$data[$v];
            continue;
        }
        if(strpos($v,'web_') === 0){
            $data[$mate]['children']['网页']['children'][$v] = &$data[$v];
            continue;
        }
    }
    return $data[$mate];
}

function treeHandle($tree){
    if(!isset($tree['children'])){return $tree;}
    $tree['children'] = array_values($tree['children']);
    foreach($tree['children'] as &$v){
        $v = treeHandle($v);
    }
    return $tree;
}

/**
 * 读取域名
 * @return mixed
 */
function getHost()
{
    $a = explode(":", @$_SERVER['HTTP_HOST']); //$_SERVER['HTTP_HOST']:  当前请求的 Host: 头部的内容
    $domain = array_shift($a);  //删除数组第一个元素并返回被删除的元素
    if (YII_ENV == "local") { //如果是本地环境 可配置域名进行测试
        $domain = "shudi.gsdata.cn";
    }

    return $domain;
}

/**
 * 密码加密
 */
function  encryptPassword($passord,$salt =  ""){
    return md5($passord.$salt);
}

/**
 * 情感属性数据重组
 * @param $data array 数据
 * @return mixed
 */
function sentimentDataChange($data){
    $return = ['正面'=>0,'中性'=>0,'负面'=>0];
    foreach ($return as $k=>$v){
        if(isset($data[$k])){
            $return[$k] = $data[$k];
        }
    }
    return $return;
}

/**
 * @param $platform
 * @return string获取媒体图片
 */
function getMediaIcon($platform){
    //$domain =  "http://bsddata.oss-cn-hangzhou-internal.aliyuncs.com/sass_shudi";
    $domain =  "http://bsddata.oss-cn-hangzhou.aliyuncs.com/sass_shudi";
    $other = Yii::$app->params['otherMiddleEsIndex'];
    if (in_array($platform,$other)) $platform = "other";
    return $domain."/images/".$platform.".jpg";
}

function getTopHost() {
    $host = $_SERVER['HTTP_HOST'];
    //查看是几级域名
    $data = explode('.', $host);
    $n = count($data);
    //判断是否是双后缀
    $preg = '/[\w].+\.(com|net|org|gov|edu)\.cn$/';
    if (($n > 2) && preg_match($preg, $host)) {
        //双后缀取后3位
        $host = $data[$n - 3] . '.' . $data[$n - 2] . '.' . $data[$n - 1];
    } else {
        //非双后缀取后两位
        $host = $data[$n - 2] . '.' . $data[$n - 1];
    }
    //去除端口
    @list($domain,$port) = explode(":",$host);
    return $domain;
}

function changeTimeToWeek($date){

   $week =  date("w",strtotime($date));
   switch ($week){
       case 1:
           return "星期一";
       case 2:
           return "星期二";
       case 3:
           return "星期三";
       case 4:
           return "星期四";
       case 5:
           return "星期五";
       case 6:
           return "星期六";
       default:
           return "星期日";
   }
}

/**
 * 获取图片内容
 * @param $url
 * @return false|string
 */
function remoteImg($url){
    $info = getimagesize($url);
    header('content-type: '.$info['mime']);
    return file_get_contents($url);
}

/**
 * 删除字符串中的空格，换行，制表符
 * @param $str
 * @return mixed
 */
function trimall($str){
    $qian=array(" ","　","\t","\n","\r");
    $hou=array("","","","","");
    return str_replace($qian,$hou,$str);
}

/**
 * 修改内容中的图片
 * @param $html
 * @return mixed
 */
function modifyImgSrc($html) {
    preg_match_all('/<img.*? src=[\"\']([\s\S]+?)[\"\'].*?>/i', $html, $imgArr);
    if(isset($imgArr[1]) && $imgArr[1]) {
        $webapiDomian = Yii::$app -> request -> hostInfo;
        foreach ($imgArr[1] as $imgSrc) {
            $url= urlencode($imgSrc);
            $newImgSrc = "{$webapiDomian}/api/show/intranet-img?url={$url}";
            $html = str_replace($imgSrc, $newImgSrc, $html);
        }
    }
    return $html;
}

/**
 * 参数是否存在
 * @param $data
 * @param $key
 * @param string $default
 * @return string
 */
function checkParams($data,$key,$default= ""){

    return isset($data[$key]) ?  $data[$key] : $default;
}


function unicodeDecode($unicode_str){
    $json = '{"str":"'.$unicode_str.'"}';
    $arr = json_decode($json,true);
    if(empty($arr)) return '';
    return $arr['str'];
}


function foo($d) {
    $r = array_pop($d);
    while($d) {
        $t = array();
        $s = array_pop($d);
        if(! is_array($s)) $s = array($s);
        foreach($s as $x) {
            foreach($r as $y) $t[] = array_merge(array($x), is_array($y) ? $y : array($y));
        }
        $r = $t;
    }
    return $r;
}

/**
 * 两值相加
 * @param $dataOne
 * @param $dataTwo
 * @return int
 */
function addition($dataOne, $dataTwo) {
    $dataOne *= 1;
    $dataTwo *= 1;
    return $dataTwo+$dataOne;
}

/**
 * 获取时间段内的日期数组
 * @param $start_time
 * @param $end_time
 * @param string $format
 * @return mixed
 */
function periodDate($start_time,$end_time,$format = 'Y-m-d'){
    $start_time = strtotime($start_time);
    $end_time = strtotime($end_time);
    $i=0;
    while ($start_time<=$end_time){
        $arr[$i]=date($format,$start_time);
        $start_time = strtotime('+1 day',$start_time);
        $i++;
    }
    return $arr;
}

/**
 * 记录临时日志
 * @param $content
 * @param string $fileName
 */
function tempLog($content, $fileName = 'temp') {
    $handle = fopen($fileName.'.log', 'a');
    fwrite($handle, $content);
    fclose($handle);
}

/**
 * 新增脚本日志
 * @param $content
 * @throws \yii\db\Exception
 */
function addTaskLog($content) {
    $sql = 'INSERT INTO `task_log` SET `create_time` = :create_time,`content` = :content;';
    $params = [
        ':content' => $content,
        ':create_time' => date('Y-m-d H:i:s'),
    ];
    Yii::$app->db->createCommand($sql, $params)->execute();
}

/**
 * 二位数组去重
 * @param: $arr 二位数组
 * @param: $key 去重的索引
 * @return array
 */
function distinctDoubleArr($arr, $key) {
    //建立一个目标数组
    $res = array();
    foreach ($arr as $value) {
        //查看有没有重复项
        if(isset($res[$value[$key]])){
            //有：销毁
            unset($value[$key]);
        } else{
            $res[$value[$key]] = $value;
        }
    }
    return array_values($res);
}

/**
 * 二维数组排序
 * @param $arr
 * @param $keys
 * @param string $type
 * @param int $limit
 * @return array
 */
function arraySort($arr,$keys,$type='asc', $limit = -1){
    $keysValue = $newArray = array();
    foreach ($arr as $k=>$v){
        $keysValue[$k] = $v[$keys];
    }
    if($type == 'asc'){
        asort($keysValue);
    }else{
        arsort($keysValue);
    }
    reset($keysValue);
    foreach ($keysValue as $k=>$v){
        $newArray[] = $arr[$k];
        if($limit != -1 && count($newArray) >= $limit) {
            break;
        }
    }
    unset($v);
    unset($keysValue);
    return $newArray;
}

/**
 * 获取汉字首字母
 * @param $str
 * @return string
 */
function getFirstCharter($str)
{
    if (empty($str)) {
        return '';
    }

    $fchar = ord($str{0});

    if ($fchar >= ord('A') && $fchar <= ord('z'))
        return strtoupper($str{0});

    $s1 = iconv('UTF-8', 'gb2312', $str);
    $s2 = iconv('gb2312', 'UTF-8', $s1);
    $s = $s2 == $str ? $s1 : $str;

    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if ($asc >= -20319 && $asc <= -20284)
        return 'A';
    if ($asc >= -20283 && $asc <= -19776)
        return 'B';
    if ($asc >= -19775 && $asc <= -19219)
        return 'C';
    if ($asc >= -19218 && $asc <= -18711)
        return 'D';
    if ($asc >= -18710 && $asc <= -18527)
        return 'E';
    if ($asc >= -18526 && $asc <= -18240)
        return 'F';
    if ($asc >= -18239 && $asc <= -17923)
        return 'G';
    if ($asc >= -17922 && $asc <= -17418)
        return 'H';
    if ($asc >= -17417 && $asc <= -16475)
        return 'J';
    if ($asc >= -16474 && $asc <= -16213)
        return 'K';
    if ($asc >= -16212 && $asc <= -15641)
        return 'L';
    if ($asc >= -15640 && $asc <= -15166)
        return 'M';
    if ($asc >= -15165 && $asc <= -14923)
        return 'N';
    if ($asc >= -14922 && $asc <= -14915)
        return 'O';
    if ($asc >= -14914 && $asc <= -14631)
        return 'P';
    if ($asc >= -14630 && $asc <= -14150)
        return 'Q';
    if ($asc >= -14149 && $asc <= -14091)
        return 'R';
    if ($asc >= -14090 && $asc <= -13319)
        return 'S';
    if ($asc >= -13318 && $asc <= -12839)
        return 'T';
    if ($asc >= -12838 && $asc <= -12557)
        return 'W';
    if ($asc >= -12556 && $asc <= -11848)
        return 'X';
    if ($asc >= -11847 && $asc <= -11056)
        return 'Y';
    if ($asc >= -11055 && $asc <= -10247)
        return 'Z';
    return '';
}

/**
 * 生成周日期列表
 * @param int $count 生成几周日期数据
 * @param int $time
 * @return array
 */
function getWeek($count = 4, $time = 1483200000)
{
    for($timestr=$time;$timestr<(time()-604800);$timestr+=604800){
        $weekarr[$timestr]=date('Ymd',$timestr).'-'.date('Ymd',$timestr+604799);
    }
    krsort($weekarr);
    $weekarr = array_slice($weekarr, 0, $count);
    return $weekarr;
}

/**
 * 生成月日期列表
 * @param int $count 生成几月日期数据
 * @param int $time
 * @return array
 */
function getMonth($count = 4, $time = 1483200000)
{
    for($timestr=$time;$timestr<strtotime('-1 month');$timestr=(strtotime('+1 month',$timestr))){
        $montharr[$timestr]=date('Ymd',$timestr).'-'.date('Ymd',strtotime('+1 month',$timestr)-1);
    }
    krsort($montharr);
    $montharr = array_slice($montharr, 0, $count);
    return $montharr;
}

/**
 * 格式化数字
 * @param $num
 * @return string
 */
function fort_mat($num){
    if($num < 100000){
        $fort_num = $num;
    }elseif($num < 100000000 && $num >= 100000){
        $fort_num = round($num/10000,1).'W+';
    }elseif ($num >= 100000000){
        $fort_num = round($num/100000000,1).'Y+';
    }else{
        $fort_num = $num;
    }
    return $fort_num;
}