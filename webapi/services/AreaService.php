<?php


namespace webapi\services;

//地区管理服务
use webapi\models\Province;

class AreaService {
    //直辖市数据
    protected $provinceLevel = ["北京","天津","上海","重庆"];//直辖市列表
    protected $provinceLevelList = [//直辖市列表
        "北京" => ['北京','北京市'],
        "天津" => ['天津','天津市'],
        "上海" => ['上海', '上海市'],
        "重庆" => ['重庆',"重庆市"]
    ];
    //自治区
    protected $provinceAuto = ['内蒙古',"新疆","广西","宁夏","西藏"];
    //"内蒙古自治区","新疆维吾尔自治区","广西自治区","西藏自治区"
    protected $provinceAutoList = [
        '内蒙古' => ['内蒙古','内蒙古自治区'],
        "新疆"   => ['新疆','新疆维吾尔自治区', '新疆自治区'],
        "广西"   => ['广西','广西自治区'],
        "宁夏"   => ['宁夏','宁夏自治区', '宁夏回族自治区'],
        "西藏"   => ['西藏','西藏自治区']
    ];
    //特别行政区
    protected $provinceSpecial = ['香港',"澳门"];
    protected $provinceSpecialList = [
        '香港' => ['香港','香港特别行政区'],
        "澳门" => ['澳门',"澳门特别行政区"]
    ];
    protected $areaInfo ;

    /**
     * 是否为直辖市
     * @param $province
     * @return bool
     */
    public function isLevelProvince($province){
        if (in_array($province,$this->provinceLevel)) return true;
        return false;
    }

    /**
     * 是否为自治区
     * @param $province
     */
    public function isAutoProvince($province){
        if (in_array($province,$this->provinceAuto)) return true;
        return false;
    }

    /**
     * 是否特别行政区
     * @param $province
     */
    public function isSpecialProvince($province){
        if (in_array($province,$this->provinceSpecial)) return true;
        return false;
    }

    public function getCityByProvince($provinceName){

        $prf = "shudi_sass_province_".$provinceName ;
        $data = \Yii::$app->cache->get($prf);

        if ($data) return $data;
        $one = $this->getAreaInfo($provinceName);
        if ( !$one ) return [];

        if (in_array($provinceName,$this->provinceLevel)){

            $provinceLevel =  Province::find()->where(['pid'=> $one->id ])->one();
            $citys = Province::find()->where(['pid'=> $provinceLevel->id ])->all();
            foreach ($citys as &$city){
                $city->realName = $city->name;
            }
        }else{
            //取出父级的id
            $citys = Province::find()->where(["pid"=>$one->id])->all();
        }

        \Yii::$app->cache->set($prf,$citys);
        return $citys;
    }

    /**
     * @param $provinceName
     * @return array|bool|\yii\db\ActiveRecord|null
     */
    public function getAreaInfo($provinceName){
        if (isset($this->areaInfo[$provinceName])&&$this->areaInfo[$provinceName]) return $this->areaInfo[$provinceName];
        $one = Province::find()->where(["name"=>$provinceName])
            ->orWhere(["realName"=>$provinceName])->one();
        if (!$one) return false;
        return $this->areaInfo[$provinceName] = $one;
    }

    public function  getAllProvince(){
       return  Province::find()->where(["pid"=>0])->all();
    }

    /**
     * @param $ref string 1是提及地区  2是发布地区
     * @param $city string 发布城市
     * @return string
     */
    public function getCityGroupField($ref,$city){

        if (in_array($city,$this->provinceLevel)){
            return $ref == 'ref' ? "news_content_county":"platform_county";
        }
        return $ref == 'ref' ? "news_content_city":"media_city";
    }

    /**
     * @param $keywords
     * @param bool $isSelectProvince 是否是选择省份数据
     * @return array|mixed
     */
    public function getKeywordsList($keywords,$isSelectProvince= true){
        if ($isSelectProvince == false){
            return  $returnKeywords = [$keywords."市",$keywords,$keywords."镇",$keywords."乡",$keywords."县"];
        }
        $isLevel = self::isLevelProvince($keywords) ;
        if ($isLevel){
            $returnKeywords = $this->provinceLevelList[$keywords];
        }else{
            $isAuto = self::isAutoProvince($keywords) ;
            if ($isAuto) {
                $returnKeywords = $this->provinceAutoList[$keywords];
            }else{
                $isSpecial = self::isSpecialProvince($keywords);
                if ($isSpecial){
                    $returnKeywords = $this->provinceSpecialList[$keywords];
                }else{
                    $returnKeywords = [$keywords."省",$keywords];
                }
            }
        }
        return $returnKeywords;
    }

    /**
     * 对传入省市在数据库进行验证,ok的话返回全称及简称
     */
    public function getFullAndShortArea($contentAreas){
        $areas = $provinces = $citys = $countys = [];
        foreach($contentAreas as $contentArea){
            if(!$contentArea) return false;
            if(false !== strpos($contentArea,'-')){ // 地级市或直辖市的区县
                $provinceArea = explode('-',$contentArea)[0];
                $contentArea = explode('-',$contentArea)[1];
                if(self::isLevelProvince($provinceArea)){
                    if(!$area = Province::find()->where(['level' => 3])->andWhere(['name' => $contentArea])->one()){
                        return false;
                    }else{
                        $countys[] = $area->name;
                    }
                }else{
                    if(!$area = Province::find()->where(['level' => 2])->andWhere(['name' => $contentArea])->one()){
                        return false;
                    }else{
                        $citys = array_merge($citys,[$area->name,$area->realName]);
                    }
                }
            }else{
                if(self::isAutoProvince($contentArea)){
                    $provinces = array_merge($provinces,$this->provinceAutoList[$contentArea]);
                }else{
                    if(!$area = Province::find()->where(['level' => 1])->andWhere(['name' => $contentArea])->one()){
                        return false;
                    }else{
                        $provinces = array_merge($provinces,[$area->name,$area->realName]);
                    }
                }
            }
        }
        if($provinces) $areas['province'] = $provinces;
        if($citys) $areas['city'] = $citys;
        if($countys) $areas['county'] = $countys;
        return $areas;
    }
}