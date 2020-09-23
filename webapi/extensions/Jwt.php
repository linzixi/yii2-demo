<?php
namespace webapi\extensions;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use phpDocumentor\Reflection\Types\Self_;
use PHPUnit\Framework\MockObject\RuntimeException;
use Yii;

class Jwt
{
    const Key_ = "xxxx";

    const Expire = 86400;//设置过期时间

    /**
     * 创建Token
     * @param int $uid 需要保存的用户身份标识
     * @return String
     **/
    public static  function createToken($uid = null, $user_base = [])
    {
       $signer = new Sha256();
       $token = (new Builder())->setIssuer('https://www.baidu.com')
           ->setAudience('https://www.baidu.com')
           ->setId('sxs-4f1g23a12aa', true)//自定义标识
           ->setIssuedAt(time())//当前时间
           ->setExpiration(time() + self::Expire)//token有效期时长 1 天 时间
           ->set('uid', $uid)
           ->sign($signer,self::Key_ )
           ->getToken();
       self::setExpireCache($token);
       return (String)$token;

    }

    /**
     * 检测Token是否过期与篡改
     * @param string token
     * @return boolean
     **/
    public static  function validateToken($token = null, $debug = false)
    {
        $domain = getHost();
        try {
            $re = self::hasExpireCache($token);
            if (!$debug && !$re) {
                return false;
            }
            $token = (new Parser())->parse((String)$token);
        } catch (RuntimeException $e) {
            return jsonErrorReturn("fail");
        }

        $signer = new Sha256();
        if (!$token->verify($signer, self::Key_)) {
            return false; //签名不正确
        }
        $validationData = new ValidationData();
        $validationData->setIssuer($domain);
        $validationData->setAudience($domain);
        $validationData->setId('sxs-4f1g23a12aa');//自字义标识
        $uid =  $token->validate($validationData) ? $token->getClaim('uid') : false;
        return $uid;
    }

    public static function deleteToken($token){
        self::deleteExpireCache($token);
    }

    public static function setExpireCache($token) {
        $cacheKey = md5(self::Key_.$token);
        \Yii::$app -> cache -> set($cacheKey, 1, self::Expire);
    }

    public static function hasExpireCache($token){
        $cacheKey = md5(self::Key_.$token);
        return   \Yii::$app -> cache -> exists($cacheKey) &&   \Yii::$app -> cache -> get($cacheKey)?true:false;
    }

    public static function deleteExpireCache($token){
        $cacheKey = md5(self::Key_.$token);
        \Yii::$app -> cache -> delete($cacheKey);
    }
}
