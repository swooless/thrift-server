<?php declare(strict_types=1);

namespace Swooless\ThriftServer\Server;

use swoole_process;
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

    private function lock(): ProcessLock
    {
        if (!$this->lock) {
            $this->lock = new ProcessLock($this->serverName);
        }
        return $this->lock;
    }
}