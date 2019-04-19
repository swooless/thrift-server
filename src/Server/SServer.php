<?php declare(strict_types=1);

namespace Swooless\ThriftServer\Server;

use Exception;
use swoole_process;
use Swooless\ThriftServer\Transport\SSocket;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Server\TServer;
use Swooless\ThriftServer\Security\ProcessLock;

class SServer extends TServer
{
    /** @var string */
    private $serverName = 'product_server';
    private $lock;
    const SIGTERM = 15;

    public function serve()
    {
        if (!$this->lock()->isLock()) {
            if ($this->transport_ instanceof SServerSocket) {
                $this->transport_->on('receive', function ($server, $fd, $from_id, $data) {
                    try {
                        echo "from id: {$from_id}\n";
                        $socket = $this->transport_->accept();
                        if ($socket instanceof SSocket) {
                            $socket->setHandle($fd);
                            $socket->buffer = $data;
                            $socket->server = $server;
                            $protocol = new TBinaryProtocol($socket, false, false);
                            $this->processor_->process($protocol, $protocol);
                        }
                    } catch (Exception $e) {
                        error_log(sprintf("CODE:%s\nMESSAGE:%s\nTrace:%s\n",
                            $e->getCode(),
                            $e->getMessage(),
                            $e->getTraceAsString()
                        ));
                    }
                });
                $this->transport_->listen();
            }
        } else {
            echo "Service has started" . PHP_EOL;
        }
    }

    /**
     * Stops the server serving
     *
     * @return void
     */
    public function stop()
    {
        if ($this->lock()->isLock()) {
            swoole_process::kill($this->lock()->pid(), self::SIGTERM);
        } else {
            echo "Service not started!" . PHP_EOL;
        }
    }

    /**
     * @param string $serverName
     */
    public function setServerName(string $serverName): void
    {
        $this->serverName = $serverName;
    }

    public function lock(): ProcessLock
    {
        if (!$this->lock) {
            $this->lock = new ProcessLock($this->serverName);
        }
        return $this->lock;
    }
}