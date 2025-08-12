<?php

namespace app\library\Uploader;

require './vendor/zos/aws-autoloader.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use think\Exception;

class CtyunZos
{
    private $s3Client;

    private static $bucket;


    public static function initBucket() {
        if (self::$bucket === null) {
            self::$bucket = config('zos.bucket');
        }
    }


    public function __construct($accessKey, $secretKey, $endpoint)
    {
        $this->s3Client = new S3Client([
            'credentials' => [
                'key' => $accessKey,
                'secret' => $secretKey
            ],
            'region' => 'us-east-1',
            'endpoint' => $endpoint,
            'http'  => [
                'verify' => true,
            ]
        ]);
    }

    /** 上传文件
     * @param $bucket 桶名称
     * @param $db_name 数据库类型
     * @param $file 文件
     * @param $acl 权限 默认是private
     * @return array|\Aws\Result
     */
    public function upload($db_name, $file, $acl="public-read")
    {
        self::initBucket();
        $bucket = self::$bucket;

        // 获取文件扩展名
        $origin_ext = $file->getUploadExtension();

        // 获取文件内容
        $get_file = $file->getRealPath();
        $file_content = file_get_contents($get_file);

        // 生成目录
        $time = time();
        $y = date('Y', $time);
        $m = date('m', $time);
        $d = date('d', $time);

        $path = $db_name . '/' . $y . '/' . $m . '/' . $d . '/';

        // 生成文件名
        $mTime = mTime();
        $file_name = $mTime . '.' . $origin_ext;
        // 路径
        $file_path = $path . $file_name;

        try {
            $result = $this->s3Client->putObject([
                'Bucket' => $bucket,
                'Key' => $file_path,
                'Body' => $file_content,
                "ACL" => $acl
            ]);

            $result['success'] = true;

            // 组装返回数据
            $result['date'] = [
                'file_name' => $file->getUploadName(),
                'file_path' => $file_path,
                'file_size' => $file->getSize(),
                'file_type' => $origin_ext,
                'file_hash' => hash_file('sha256', $get_file),
            ];
            $result['message'] = "File uploaded successfully.";
        } catch (S3Exception $e) {
            $result['message'] = "S3 error: " . $e->getMessage();
        } catch (AwsException $e) {
            $result['message'] = "AWS error: " . $e->getMessage();
            $result['data'] = [
                'aws_request_id' => $e->getAwsRequestId(),
                'aws_error_type' => $e->getAwsErrorType(),
                'aws_error_code' => $e->getAwsErrorCode(),
                'error_details' => $e->toArray()
            ];
        } catch (\Exception $e) {
            $result['message'] = "General error: " . $e->getMessage();
        }
        return $result;
    }

    /**
     * 文件下载
     * @param $bucket 桶
     * @param $path 路径
     * @return array|\Aws\Result
     */
    public function download($path)
    {
        self::initBucket();
        $bucket = self::$bucket;

        try {
            $result = $this->s3Client->getObject([
                'Bucket' => $bucket,
                'Key' => $path ,
            ]);
            // 获取文件流
            $stream = $result['Body'];

            // 设置文件名（可以根据需要动态生成）
            $filename = basename($path);

            // 设置响应头，提示浏览器下载文件
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . $stream->getSize());

            // 将流内容输出到浏览器
            while (!$stream->eof()) {
                echo $stream->read(1024); // 每次读取 1024 字节
            }

            // 中止脚本，避免输出其他内容
            exit;

        } catch (S3Exception $e) {
            $result['message'] = "S3 error: " . $e->getMessage();
        } catch (AwsException $e) {
            $result['message'] = "AWS error: " . $e->getMessage();
            $result['data'] = [
                'aws_request_id' => $e->getAwsRequestId(),
                'aws_error_type' => $e->getAwsErrorType(),
                'aws_error_code' => $e->getAwsErrorCode(),
                'error_details' => $e->toArray()
            ];
        } catch (\Exception $e) {
            $result['message'] = "General error: " . $e->getMessage();
        }
        return $result;
    }

    /**
     * 删除文件
     * @param $path 删除文件
     * @param $bucket 桶
     * @return array|\Aws\Result
     */
    public function delete($path)
    {
        self::initBucket();
        $bucket = self::$bucket;

        try {
            $result = $this->s3Client->DeleteObject([
                'Bucket' => $bucket,
                'Key' => $path ,
            ]);

            $result['success'] = true;
            $result['message'] = "File delete successfully.";

        } catch (S3Exception $e) {
            $result['message'] = "S3 error: " . $e->getMessage();
        } catch (AwsException $e) {
            $result['message'] = "AWS error: " . $e->getMessage();
            $result['data'] = [
                'aws_request_id' => $e->getAwsRequestId(),
                'aws_error_type' => $e->getAwsErrorType(),
                'aws_error_code' => $e->getAwsErrorCode(),
                'error_details' => $e->toArray()
            ];
        } catch (\Exception $e) {
            $result['message'] = "General error: " . $e->getMessage();
        }
        return $result;
    }

    /**
     * 分段上传文件
     * @param $localFilePath 本地文件
     * @param $path 远程path
     * @param $bucket 桶
     * @return void
     * @throws Exception
     */
    public function uploadLargeFile($localFilePath, $path, $acl = "public-read")
    {
        self::initBucket();
        $bucket = self::$bucket;

        try{
            $initiateResponse = $this->s3Client->createMultipartUpload([
                'Bucket' => $bucket,
                "Key" => $path,
            ]);
            $uploadId = $initiateResponse['UploadId'];

            // 打开本地文件
            $fileHandle = fopen($localFilePath, 'r');
            if (!$fileHandle) {
                throw new Exception("Failed to open file: " . $localFilePath);
            }

            $partNumber = 0;
            $parts = [];

            // 分段读取并上传文件
            while (!feof($fileHandle)) {
                $partNumber++;
                $data = fread($fileHandle, 10 * 1024 * 1024); // 每段10MB
                if ($data === false) {
                    break;
                }
                $partResponse = $this->s3Client->uploadPart([
                    'Bucket' => $bucket,
                    'Key' => $path,
                    'UploadId' => $uploadId,
                    'PartNumber' => $partNumber,
                    'Body' => $data,
                    "ACL" => $acl
                ]);

                $parts[] = [
                    'PartNumber' => $partNumber,
                    'ETag' => $partResponse['ETag']
                ];
            }

            fclose($fileHandle);

            // 完成分段上传
            $result = $this->s3Client->completeMultipartUpload([
                'Bucket' => $bucket,
                'Key' => $path,
                'UploadId' => $uploadId,
                'MultipartUpload' => [
                    'Parts' => $parts
                ]
            ]);

            $result['success'] = true;
            $result['message'] = "File uploaded successfully.";
            $result['object_url'] = $result['Location'];

        } catch (S3Exception $e) {
            $result['success'] = false;
            $result['message'] = "S3 error: " . $e->getMessage();
        } catch (AwsException $e) {
            $result['success'] = false;
            $result['message'] = "AWS error: " . $e->getMessage();
            $result['data'] = [
                'aws_request_id' => $e->getAwsRequestId(),
                'aws_error_type' => $e->getAwsErrorType(),
                'aws_error_code' => $e->getAwsErrorCode(),
                'error_details' => $e->toArray()
            ];
        } catch (\Exception $e) {
            $result['success'] = false;
            $result['message'] = "General error: " . $e->getMessage();
        }

        return $result;
    }

    /** 生成预签名上传链接
     * @param $bucketName
     * @param $objectKey
     * @param $expireMinutes
     * @return string
     */
    function generatePresignedUploadUrl($bucketName, $path, $expireMinutes) {
        $cmd = $this->s3Client->getCommand('PutObject', [
            'Bucket' => $bucketName,
            'Key'    => $path
        ]);
        $request = $this->s3Client->createPresignedRequest($cmd, "+" . $expireMinutes . " minutes");
        $presignedUrl = (string)$request->getUri();
        return $presignedUrl;
    }




}