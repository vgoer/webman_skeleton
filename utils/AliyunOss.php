<?php

namespace utils;

use OSS\Core\OssException;
use OSS\OssClient;

class AliyunOss
{
    private $ossClient;
    private $endpoint;

    public function __construct($accessKeyId, $accessKeySecret, $endpoint)
    {
        try {
            $this->ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint, false);
            $this->endpoint = $endpoint;
            return True;
        } catch (OssException $e) {
            printf(__FUNCTION__ . "creating OssClient instance: FAILED\n");
            printf($e->getMessage() . "\n");
            return False;
        }
    }

    // 上传文件
    public function upLoadFile($bucket, $object, $content, $options = NULL)
    {
        try{
            $this->ossClient->putObject($bucket, $object, $content, $options);
            return 'https://' . $bucket . '.' . $this->endpoint . '/' . $object;
        } catch(OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return False;
        }
    }

    // 读取文件内容
    public function downLoadFile($bucket, $object)
    {
        try{
            return $this->ossClient->getObject($bucket, $object);
        } catch(OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
    }

    // 删除文件
    public function removeFile($bucket, $object)
    {
        $object = str_replace('https://' . $bucket . '.' . $this->endpoint . '/', '', $object);
        try{
            $this->ossClient->deleteObject($bucket, $object);
            return True;
        } catch(OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return False;
        }
    }
}

