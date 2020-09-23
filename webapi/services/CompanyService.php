<?php
namespace webapi\services;

use Kafka\Exception;
use webapi\models\CompanyAuthority;
use webapi\models\SysMenu;
use webapi\models\SysPageAuth;
use Yii;
use webapi\models\Company;

class CompanyService
{
    public static $company_id; //登录后公司id
    public static $company_info; //登录后公司信息

    //根据域名获取企业数据
//    protected function initCompanyInfo()
//    {
//        $domain = getHost();
//        if ( YII_ENV == "local" ) { //如果是本地环境 可配置域名进行测试
//            $domain = "shudi.gsdata.cn";
//        }
//        $info = Company::find()->where(['domain' => $domain])->one();
//        return $info;
//    }

    //获取当前访问企业配置
    public function getCompanyConfig()
    {
        $info = self::$company_info;
        //处理一下首页图片
        $style_images = json_decode($info->style_images, true);
        $images = [];
        if (isset($style_images["style_" . $info->index_style])) {
            $images = $style_images["style_" . $info->index_style];
            //处理图片的默认值问题
            if ($info->index_style == 1) {
                $images['banner'] = $images['banner'] ? $images['banner'] : "http://" . $info->domain . "/images/banner.png";
            } else {
                $images['banner'] = $images['banner'] ? $images['banner'] : "http://" . $info->domain . "/static/images/banner2.jpg";
                if (empty($images['small_1']) && empty($images['small_2']) && empty($images['small_3']) && empty($images['small_4'])) {
                    $images['small'] = [];
                } else {
                    $images['small'][] = $images['small_1'] ? $images['small_1'] : "";
                    $images['small'][] = $images['small_2'] ? $images['small_2'] : "";
                    $images['small'][] = $images['small_3'] ? $images['small_3'] : "";
                    $images['small'][] = $images['small_4'] ? $images['small_4'] : "";
                }
                unset($images['small_1']);
                unset($images['small_2']);
                unset($images['small_3']);
                unset($images['small_4']);
            }
        }
        $returnInfo = [
            "companyName" => $info->company_name,
            "companyUser" => $info->company_user,
            "companyMobile" => $info->company_mobile,
            "companyTel" => $info->company_tel,
            "companyEmail" => $info->company_email,
            "systemTitle" => $info->system_title,
            "systemTheme" => $info->system_theme,
            "indexStyle" => $info->index_style,
            "styleImages" => $images,
            "openOnline" => $info->open_online,//是否开启
            "onlineInfo" => [
                "onlineQq" => $info->online_qq,//在线qq
                "onlineWx" => $info->online_wx,//在线微信
                "onlineTel" => $info->online_tel,//在线电话
                "nickName" => $info->wx_nickname,//昵称
            ],
            "wxNickname" => $info->wx_nickname,//微信联系人
            "systemIcon" => $info->system_icon,
            "systemIndexLogo" => $info->system_index_logo,
            "systemOtherLogo" => $info->system_other_logo,
            "qrCode" => $info->qr_code,
            "footerInfo" => json_decode($info->footer_info, true),
           // 'openAddWeContact' => $info->open_add_we_contact == 1 ? 1 : 0,//是否开启添加微信联系人功能
           // 'openAddEmailContact' => $info->open_add_email_contact == 1 ? 1 : 0,//是否开启添加邮箱联系人功能
            'openTextCensor' => $info->open_text_censor == 1 ? 1 : 0,//是否开启内容检查
            'openTextCompliance' => $info->open_text_compliance == 1 ? 1 : 0,//是否开启合规检测
        ];
        return $returnInfo;
    }

    /**
     * 根据域名获取企业数据
     * @return array|mixed|yii\db\ActiveRecord|null
     */
    public function getCompanyInfo()
    {
        if (self::$company_info) {
            return self::$company_info;
        }
        $is_up_cache = false;
        if (self::$company_id) {
            $company_info = Yii::$app -> cache -> get(RedisKeyService::getKey(1002, self::$company_id));
            $company_info = json_decode($company_info, true);
            if ($company_info) {
                $info = $company_info;
            }else {
                $info = Company::find()->alias('c')->select('c.*,a.account_num,a.resource_num,a.search_days,a.start_time,a.end_time,a.scheme_num,a.monitoring_words_num,a.free_sms_num,a.single_export_data_num')->where(['c.id' => self::$company_id])->leftJoin(CompanyAuthority::tableName() . ' a', 'c.id=a.company_id')->asArray()->one();
                $is_up_cache = true;
            }
        }else {
            self::$company_id = Company::find()->select('id')->where(['domain' => getHost()])->asArray()->scalar();
            if (empty(self::$company_id)) {
                return jsonErrorReturn('fail', '系统不存在');
            }
            $info = $this->getCompanyInfo();
        }
        if ($info) {
            self::$company_info = $info;
            //更新缓存
            if ($is_up_cache) {
                Yii::$app->cache->set(RedisKeyService::getKey(1002, $info['id']), json_encode($info, JSON_UNESCAPED_UNICODE), (30 * 24 * 3600));
            }
        }

        return self::$company_info;
    }

    /**
     * 根据域名获取企业信息
     */
    public function getCompanyByHosts($domain = '')
    {
        if (!$domain) {
            $domain = getHost();
        }
        return Company::find()->where(['domain' => $domain])->asArray()->one();
    }

    /**
     * 编辑企业基本信息
     * @param array $data
     * @return array
     */
    public function basicInfo($company_id, $info)
    {
        if (isset($info['domain']) && $info['domain']) {
            //判断域名是否重复
            if (Company::find()->where(['domain' => $info['domain']])->andWhere(['<>', 'id', $company_id])->count()) {
                return ['success' => false, 'msg' => '该域名已存在'];
            }

            //开启事物
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $company_param = [
                    'update_time' => date('Y-m-d H:i:s'),
                    'domain' => addslashes($info['domain'])
                ];
                $res_c = Yii::$app->db->createCommand()->update(Company::tableName(), $company_param, "id=$company_id")->execute();
                $auth_param = [
                    'scheme_num' => (int)$info['scheme_num'] ? (int)$info['scheme_num'] : 300,
                    'free_sms_num' => (int)$info['free_sms_num'] ? (int)$info['free_sms_num'] : 5000,
                    'single_export_data_num' => (int)$info['single_export_data_num'] ? (int)$info['single_export_data_num'] : 2000,
                    'u_time' => date('Y-m-d H:i:s'),
                ];
                $res_a = Yii::$app->db->createCommand()->update(CompanyAuthority::tableName(), $auth_param, "company_id=$company_id")->execute();
                if ($res_c || $res_a) {
                    //提交
                    $transaction->commit();
                }
                //删除企业缓存
                Yii::$app->cache->delete(RedisKeyService::getKey(1002, $company_id));
                return ['success' => true, 'msg' => '保存成功'];
            }catch (Exception $e) {
                //回滚
                $transaction->rollBack();
                return ['success' => false, 'msg' => $e->getMessage()];
            }
        }else {
            return ['success' => false, 'msg' => '请填写域名'];
        }
    }

    /**
     * 保存企业权限
     * @param array $info
     * @return array
     */
    public function authInfo($company_id, $info)
    {
        if (isset($info['select_list']) && $info['select_list']) {
            $select_list = explode(',', $info['select_list']);
        }else {
            return ['success' => false, 'msg' => 'select_list参数错误'];
        }
        $auth_list = [];
        foreach ($select_list as $v) {
            $v = explode('_', $v);
            $auth_list[$v[0]][] = $v[1];
        }
        //获取全部菜单
        $menu_list = SysMenu::find()->select('id,pid')->where(['is_menu' => 1])->asArray()->all();
        $menu_list = array_column($menu_list, 'pid', 'id');

        $menu_ids = [];
        $media_ids = [];
        $region_create_num = 0;
        $tag_create_num = 0;
        $media_create_num = 0;
        $web_create_num = 0;
        $open_text_censor = 0; // 内容检查
        $open_text_compliance = 0; // 合规监测
        foreach ($auth_list as $k => $v) {
            switch ($k) {
                case SysPageAuth::TYPE_SYS:
                case SysPageAuth::TYPE_CONTENT:
                    foreach ($v as $v2) {
                        if (isset($menu_list[$v2])) {
                            $menu_ids[$v2] = $v2;
                            $menu_ids[$menu_list[$v2]] = $menu_list[$v2];
                        }
                        if (in_array($v2, [9, 10])) { //内容检查/合规检查 有这两个任何一个就有 词库维护
                            $menu_ids[13] = 13;
                            if ($v2 == 9) $open_text_censor = 1;
                            if ($v2 == 10) $open_text_compliance = 1;
                        }
                    }
                    break;
                case SysPageAuth::TYPE_MEDIA:
                    $media_ids = $v;
                    $menu_ids[1] = 1; //媒体管理
                    $menu_ids[4] = 4; //媒体榜单
                    break;
                case SysPageAuth::TYPE_REGION:
                    if ($v[0] && $v[0] != '无限') {
                        $region_create_num = $v[0];
                    }
//                    $menu_ids[1] = 1; //媒体管理
//                    $menu_ids[5] = 5; //地区管理
                    break;
                case SysPageAuth::TYPE_TAG:
                    if ($v[0] && $v[0] != '无限') {
                        $tag_create_num = $v[0];
                    }
//                    $menu_ids[1] = 1; //媒体管理
//                    $menu_ids[6] = 6; //标签管理
                    break;
                case SysPageAuth::TYPE_MEDIA_NUM:
                    $media_create_num = end($v);
                    break;
                case SysPageAuth::TYPE_WEB_NUM:
                    $web_create_num = end($v);
                    break;
            }
        }
        ksort($menu_ids);
        //更新 company
        $res = Yii::$app->db->createCommand()->update(Company::tableName(), [
            'menu_ids' => implode(',', $menu_ids),
            'media_tag_ids' => implode(',', $media_ids),
            'region_create_num' => $region_create_num,
            'tag_create_num' => $tag_create_num,
            'media_create_num' => $media_create_num,
            'web_create_num' => $web_create_num,
            'update_time' => date('Y-m-d Y:i:s'),
            'open_text_censor' => $open_text_censor,
            'open_text_compliance' => $open_text_compliance,
        ], "id=$company_id")->execute();
        //删除企业缓存
        Yii::$app->cache->delete(RedisKeyService::getKey(1002, $company_id));
        //更新日志
//        (new LogService())->saveSysLog('缓存地址1：' . json_encode(Yii::$app->cache, JSON_UNESCAPED_UNICODE), $company_id, 0, 2);

        return ['success' => true, 'msg' => '保存成功！'];
    }

    /**
     * 编辑企业其他信息
     * @param array $data
     * @return array
     */
    public function otherAuthority($company_id, $data)
    {
        $info = $data['data'];

        //检查手机
        if ($info['phone']) {
            $res = Yii::$app->globalService->CompanyService->checkTel($info['phone']);
            if (!$res['success']) return ['success' => false, 'msg' => $res['msg']];
        }
        //检查企业邮箱
        if ($info['email']) {
            $res = Yii::$app->globalService->CompanyService->checkEmail($info['email']);
            if (!$res['success']) return ['success' => false, 'msg' => $res['msg']];
        }
        $companyModel = Company::find()->where(['id' => $company_id])->one();
        if (!$companyModel) {
            return ['success' => false, 'msg' => '服务器出错了'];
        }
        $footer_info = json_encode([
            'copyright' => $info['copyright'],
            'phone'     => $info['phone'],
            'wxName'    => $info['wx_name'],
            'email'     => $info['email'],
            'qq'        => $info['qq'],
        ]);
        $email_config = json_encode([
            'host'          => $info['host'],
            'port'          => $info['port'],
            'mail_account'  => $info['mail_account'],
            'mail_password' => $info['mail_password'],
            'encryption'    => $info['encryption'] == "ssl" ? "ssl" : "tsl"
        ]);
        $wx_config = json_encode([
            'app_tag'            => isset($info['app_tag']) ? $info['app_tag']: "" ,//改成从清博管家统一处理，此字段已无用
            'app_id'             => $info['app_id'],
            'app_secret'         => $info['app_secret'],
            'warn_template_id'   => $info['warn_template_id'],
            'warn_template'      => $info['warn_template'],
            'report_template_id' => $info['report_template_id'],
            'report_template'    => $info['report_template'],
        ]);
        $companyModel->open_online = $info['open_online'];
        $companyModel->online_qq = $info['online_qq'];
        $companyModel->wx_nickname = $info['wx_nickname'];
        $companyModel->online_tel = $info['online_tel'];
        $companyModel->footer_info = $footer_info;
//        $companyModel->wx_num_location = $info['wx_num_location'];
        $companyModel->email_config = $email_config;
        $companyModel->open_wx_warn = $info['open_wx_warn'];
        $companyModel->open_email = $info['open_email'];
        $companyModel->wx_config = $wx_config;
        $companyModel->open_add_email_contact = $info['open_add_email_contact'];
        $companyModel->open_add_we_contact = $info['open_add_we_contact'];
//        $companyModel->system_icon = $info['system_icon'];
        try {
//            $callback = $this->callbackFunction;
//            if (is_callable($callback)) {
//                $images = $callback();
//                if (!$images) return ['success' => false, 'msg' => '图片上传失败!'];
//                $data = array_merge($data, $images);
//            }
//            $companyModel->system_icon = isset($data['system_icon']) ? $data['system_icon'] : "";
//            $companyModel->online_wx = isset($data['online_wx']) ? $data['online_wx'] : "";
            $res = $companyModel->save(false);
            //删除企业缓存
            Yii::$app->cache->delete(RedisKeyService::getKey(1002, $company_id));

            return ['success' => true, 'msg' => '保存成功!'];
        } catch (Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }
}