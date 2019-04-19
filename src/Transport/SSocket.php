<?php declare(strict_types=1);

namespace Swooless\ThriftServer\Transport;

use Thrift\Exception\TTransportException;
use Thrift\Factory\TStringFuncFactory;
use Thrift\Transport\TFramedTransport;

class SSocket extends TFramedTransport
{
    public $buffer = '';
    public $offset = 0;
    public $server;
    protected $fd;
    protected $read_ = true;
    protected $rBuf_ = '';
    protected $wBuf_ = '';

    public function setHandle($fd)
    {
        $this->fd = $fd;
    }

    /**
     * @param int $len
     * @return bool|string
     * @throws TTransportException
     */
    public function read($len)
    {
        if (!$this->read_) {
            return $this->_read($len);
        }

        if (0 === TStringFuncFactory::create()->strlen($this->rBuf_)) {
            $this->readFrame();
        }
        // Just return full buff
        if ($len >= TStringFuncFactory::create()->strlen($this->rBuf_)) {
            $out = $this->rBuf_;
            $this->rBuf_ = null;
            return $out;
        }

        // Return TStringFuncFactory::create()->substr
        $out = TStringFuncFactory::create()->substr($this->rBuf_, 0, $len);
        $this->rBuf_ = TStringFuncFactory::create()->substr($this->rBuf_, $len);
        return $out;
    }

    /**
     * @param $len
     * @return bool|string
     * @throws TTransportException
     */
    public function _read($len)
    {
        if (strlen($this->buffer) - $this->offset < $len) {
            throw new TTransportException('SSocket[' . strlen($this->buffer) . '] read ' . $len . ' bytes failed.');
        }
        $data = substr($this->buffer, $this->offset, $len);
        $this->offset += $len;
        return $data;
    }

    function readFrame()
    {
        $buf = $this->_read(4);
        $val = unpack('N', $buf);
        $sz = $val[1];

        $this->rBuf_ = $this->_read($sz);
    }

    public function write($buf, $len = null)
    {
        $this->wBuf_ .= $buf;
    }

    function flush()
    {
        $out = pack('N', strlen($this->wBuf_));
        $out .= $this->wBuf_;
        $this->server->send($this->fd, $out);
        $this->wBuf_ = '';
    }
}
