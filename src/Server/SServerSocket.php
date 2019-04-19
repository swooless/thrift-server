<?php declare(strict_types=1);

namespace Swooless\ThriftServer\Server;

use swoole_server;
use Swooless\ThriftServer\Transport\SSocket;
use Thrift\Server\TServerTransport;
use Thrift\Transport\TTransport;

class SServerSocket extends TServerTransport
{
    /** @var string */
    private $host;
    /** @var int */
    private $port;
    /** @var swoole_server */
    private $swoole_server;
    /** @var array */
    private $config = [];

    public function __construct($host, $port, $config = [])
    {
        $this->host = $host;
        $this->port = $port;
        $this->config = $config;

        $this->swoole_server = new swoole_server($this->host, intval($this->port));
        $this->swoole_server->set($this->initConfig());
    }

    public function getSwooleServer(): swoole_server
    {
        return $this->swoole_server;
    }

    /**
     * 添加swoole的时间监听
     * @param string $name
     * @param callable $fun
     */
    public function on(string $name, callable $fun)
    {
        $this->swoole_server->on($name, $fun);
    }

    /**
     * List for new clients
     *
     * @return void
     */
    public function listen()
    {
        $this->swoole_server->start();
    }

    /**
     * Close the server
     *
     * @return void
     */
    public function close()
    {
        $this->swoole_server->shutdown();
    }

    /**
     * Subclasses should use this to implement
     * accept.
     *
     * @return TTransport
     */
    protected function acceptImpl(): TTransport
    {
        return new SSocket();
    }

    /**
     * init config
     *
     * @return array
     */
    private function initConfig(): array
    {
        if (!$this->config) {
            $logPath = realpath(getenv('SWOOLE_LOGS_PATH') ?: './logs');

            if ($logPath == false) {
                $logPath = sys_get_temp_dir();
            }

            $logPath .= DIRECTORY_SEPARATOR . date('Ymd') . '-swoole.log';

            // default config
            $this->config = [
                'daemonize' => getenv('SWOOLE_HTTP_DAEMONIZE') ?: false,
                // Normally this value should be 1~4 times larger according to your cpu cores.
                'reactor_num' => getenv('SWOOLE_HTTP_REACTOR_NUM') ?: swoole_cpu_num(),
                'worker_num' => getenv('SWOOLE_HTTP_WORKER_NUM') ?: swoole_cpu_num(),
                'task_worker_num' => getenv('SWOOLE_HTTP_TASK_WORKER_NUM') ?: swoole_cpu_num(),
                // The data to receive can't be larger than buffer_output_size.
                'package_max_length' => 20 * 1024 * 1024,
                // The data to send can't be larger than buffer_output_size.
                'buffer_output_size' => 10 * 1024 * 1024,
                // Max buffer size for socket connections
                'socket_buffer_size' => 128 * 1024 * 1024,
                'log_file' => $logPath,
                'dispatch_mode' => 1, //1: 轮循, 3: 争抢
                'open_length_check' => true, //打开包长检测
                'package_length_type' => 'N', //长度的类型，参见PHP的pack函数
                'package_length_offset' => 0,   //第N个字节是包长度的值
                'package_body_offset' => 4,   //从第几个字节计算长度
                // Worker will restart after processing this number of requests
                'max_request' => 3000,
                // Enable coroutine send
                //'send_yield' => true,
                // You must add --enable-openssl while compiling Swoole
                'ssl_cert_file' => getenv('SWOOLE_SSL_CERT_FILE') ?: null,
                'ssl_key_file' => getenv('SWOOLE_SSL_KEY_FILE') ?: null
            ];
        }
        return $this->config;
    }
}