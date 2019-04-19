<?php declare(strict_types=1);

namespace Swooless\ThriftServer\Tests;

use Swooless\ThriftServer\Server\SServerSocket;
use PHPUnit\Framework\TestCase;
use Swooless\ThriftServer\Server\SServer;
use Thrift\Factory\TBinaryProtocolFactory;
use Thrift\Factory\TTransportFactory;

class ServerStartTest extends TestCase
{
    public function testStart()
    {
        $processor = new class
        {
            //
        };

        $transportFactory = new TTransportFactory();
        $protocolFactory = new TBinaryProtocolFactory(true, true);

        $transport = new SServerSocket('127.0.0.1', '9090');

        $transport->on('receive', function () {
            echo "receive" . PHP_EOL;
        });
        $transport->on('start', function () {
            echo "start" . PHP_EOL;
        });
        $transport->on('task', function () {
            echo "task" . PHP_EOL;
        });
        $transport->on('finish', function () {
            echo "finish" . PHP_EOL;
        });

        $transport->on('WorkerStart', function ($server, $id) {
            /** @var $server \swoole_server */
            echo $server->host . ':' . $server->port . PHP_EOL;
            echo "Worker Start [{$id}]" . PHP_EOL;
        });

        $server = new SServer($processor, $transport, $transportFactory, $transportFactory, $protocolFactory, $protocolFactory);
        $server->serve();
    }
}