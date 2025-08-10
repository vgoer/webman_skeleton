<?php

namespace utils;

class Env
{
    public static function init(): void
    {
        global $argv;
        $env = false;
        $base_path = base_path() . '/.env.';
        // 从环境变量获取 APP_ENV
        foreach ($argv as $key => $value) {
            if ($value == '-e' && isset($argv[$key + 1]) && str_contains($argv[$key + 1], '=')) {
                list($name, $value) = explode('=', $argv[$key + 1]);
                $envFilePath = $base_path . $value;
                if ($name == 'APP_ENV' && file_exists($envFilePath)) {
                    $env = self::parseEnvFile($envFilePath);
                    if ($env) {
                        self::initEnv($env, $value);
                        return;
                    }
                }
            }
        }
        // 如果没有环境变量，则从 .env 或 .env.pro 中获取
        $file = 'dev';
        if (!file_exists($base_path . $file)) $file = 'pro';
        $env = self::parseEnvFile($base_path . $file);
        if (!$env) {
            error_log("Error: .env or .env.pro file not found.");
            exit('Error LINE:' . __LINE__);
        }
        self::initEnv($env, $file);
    }

    private static function parseEnvFile($file): false|array
    {
        if (!file_exists($file)) return false;
        return parse_ini_file($file, true) ?? false;
    }

    /**
     * @param array $env
     * @param string $envFilePath
     * @return void
     */
    protected static function initEnv(array $env, string $envFilePath): void
    {
        self::printEnv($envFilePath);
        //初始化ENV 配置
        foreach ($env as $key => $val) {
            //过滤首字母#或者'//'开头的注释
            if (str_starts_with($key, '#') || str_starts_with($key, '//')) continue;
            if (is_array($val)) {
                foreach ($val as $k => $v) { //如果是二维数组 item = PHP_KEY_KEY
                    $item = $key . '_' . $k;
                    putenv("$item=$v");
                }
            } else {
                putenv("$key=$val");
            }
        }

    }

    /**
     * 多进程下只输出一次 env 名字
     * @param string $envFilePath .env 名字
     * @return void
     */
    private static function printEnv(string $envFilePath): void
    {
        $lockFilePath = runtime_path() . '/env_printed.lock';
        $timeout = 3;

        $lockFile = fopen($lockFilePath, 'a+');
        if ($lockFile === false) {
            error_log("Could not open lock file: " . $lockFilePath);
            return;
        }
        if (!flock($lockFile, LOCK_EX)) {
            error_log("Could not acquire lock on file: " . $lockFilePath);
            fclose($lockFile);
            return;
        }
        try {
            if (file_exists($lockFilePath)) {
                $modifiedTime = filemtime($lockFilePath);
                if (time() - $modifiedTime > $timeout) {
                    // 锁失效，输出语句并更新时间
                    echo "Load env file: " . $envFilePath . PHP_EOL;
                    touch($lockFilePath); // 更新修改时间为当前时间
                }
                // 如果锁未失效，不输出
            } else {
                // 文件不存在，输出语句并创建文件
                echo "Load env file: " . $envFilePath . PHP_EOL;
                touch($lockFilePath); // 创建文件并设置修改时间为当前时间
            }
        } finally {
            flock($lockFile, LOCK_UN);
            fclose($lockFile);
        }
    }
}

