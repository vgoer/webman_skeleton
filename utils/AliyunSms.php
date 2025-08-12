<?php

namespace app\library\Sms;


use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use support\Log;

class AliyunSms
{
    private $accessKeyId;

    private $accessKeySecret;

    private $signName;

    private $templateCode;

    // 单例实例
    private static $instance;

    // 私有构造函数，防止外部实例化
    private function __construct($accessKeyId, $accessKeySecret, $signName)
    {
        $this->accessKeyId = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
        $this->signName = $signName;
    }

    // 获取单例实例
    public static function getInstance()
    {
        if (!self::$instance) {
            $accessKeyId = getenv("ALIYUN_KEY");
            $accessKeySecret = getenv("ALIYUN_SECRET");
            $signName = getenv("ALIYUN_SIGN_NAME");

            self::$instance = new self($accessKeyId, $accessKeySecret, $signName);
        }
        return self::$instance;
    }

    /**
     * 发送验证码
     * @param $phone 手机号
     * @param $templateParam 验证码等参数
     * @return bool 成功是否
     */
    public function sendSms($phone, $templateParam,$templateCode)
    {
        try {
            AlibabaCloud::accessKeyClient($this->accessKeyId, $this->accessKeySecret)
                ->regionId('cn-hangzhou')
                ->asDefaultClient();

            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->options([
                    'query' => [
                        'PhoneNumbers' => $phone,
                        'SignName' => $this->signName,
                        'TemplateCode' => $templateCode,
                        'TemplateParam' => json_encode($templateParam),
                    ],
                ])
                ->request();

            $response = $result->toArray();

            if ($response['Code'] == 'OK') {
                return true;
            } else {
                return false;
            }
        } catch (ClientException $e) {
            Log::info("sendSms1:" . $e->getErrorMessage());
            return false;
        } catch (ServerException $e) {
            Log::info("sendSms2:" . $e->getErrorMessage());
            return false;
        }
    }

}