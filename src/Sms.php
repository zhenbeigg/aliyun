<?php
/*
 * @author: 布尔
 * @name: 阿里云sms短信服务
 * @desc: 介绍
 * @LastEditTime: 2023-08-11 17:56:15
 * @FilePath: \aliyun\src\Sms.php
 */

declare(strict_types=1);

namespace Eykj\Aliyun;

use Hyperf\Guzzle\ClientFactory;

class Sms
{
    /**
     * @var \Hyperf\Guzzle\ClientFactory
     */
    private $clientFactory;

    private $url = 'https://dysmsapi.aliyuncs.com/?';

    /**
     * @author: 布尔
     * @name: 构造方法引入guzzle类
     * @param {ClientFactory} $clientFactory
     * @return {*}
     */    
    public function __construct(ClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    /**
     * @author: 布尔
     * @name: 编码处理方法
     * @param {*} $string
     * @return {*}
     */    
    private function percentEncode($string) {
        $string = urlencode((string)$string);
        $string = preg_replace('/\+/','%20',$string);
        $string = preg_replace('/\*/', '%2A',$string);
        $string = preg_replace('/%7E/','~',$string);
        return $string;
    }

    /**
     * @author: 布尔
     * @name: 获取公共参数
     * @return {array} $param
     */    
    protected function getPublicParam():array
    {
        return  $params = array (
            'Version' => '2017-05-25',
            'Timestamp' => gmdate ( 'Y-m-d\TH:i:s\Z' ),
            'SignatureVersion' => '1.0',
            'SignatureNonce' => uniqid (),
            'SignatureMethod' => 'HMAC-SHA1',
            'Format' => 'JSON'
        );
    }

    /**
     * @author: 布尔
     * @name: 签名发送请求
     * @param {array} $array
     * @return {*}
     */    
    public function getSign(array $array=array()):array
    {
        $params = $this->getPublicParam();
        $newArray = array_merge($params,$array);
        unset($newArray['Signature']);
        ksort($newArray);
        $canonicalizedQueryString = '';
        foreach ($newArray as $key => $value) {
            $canonicalizedQueryString .= '&' . $this->percentEncode($key) . '=' . $this->percentEncode($value);
        }
        $stringToSign = 'GET&%2F&' .$this->percentencode(substr($canonicalizedQueryString,1));
        $signature = base64_encode(hash_hmac('sha1',$stringToSign,env('ALIYUN_SMS_ACCESS_KEY_SECRET'). '&',true));
        $newArray ['Signature'] = $signature;
        $client = $this->clientFactory->create($options=[]);
        $url = $this->url.http_build_query($newArray);
        try{
            $result = $client->request('get',$url);
            return json_decode($result->getBody()->getContents(),true);
        } catch (\Throwable $th) {
            $r= $th->getMessage();
            alog($r,2);
            return [];
        }
    }


    /**
     * @author: 布尔
     * @name: 发送单条短信
     * @param {array} $array
     * @return {*}
     */    
    public function sendSms(array $array=array()):array
    {
        $array['Action']='SendSms';
        $array['AccessKeyId']= env('ALIYUN_SMS_ACCESS_KEY_ID');
        $array['SignName']= $array['SignName']??env('ALIYUN_SMS_SIGN_NAME');
        return $this->getSign($array);
    }

    /**
     * @author: 布尔
     * @name: 批量发送
     * @param {array} $array
     * @return {*}
     */    
    public function phoneNumberJson(array $array=array()):array
    {
        $array['Action']='SendBatchSms';
        $array['AccessKeyId']= env('ALIYUN_SMS_ACCESS_KEY_ID');
        $array['SignName']= env('ALIYUN_SMS_SIGN_NAME');
        return $this->getSign($array);
    }

    /**
     * @author: 布尔
     * @name: 查询发送记录
     * @param {array} $array
     * @return {*}
     */    
    public function getSendDetails(array $array=array()):array
    {
        $array['Action']='QuerySendDetails';
        $array['AccessKeyId']= env('ALIYUN_SMS_ACCESS_KEY_ID');
        $array['SignName']= env('ALIYUN_SMS_SIGN_NAME');
        return $this->getSign($array);
    }
}