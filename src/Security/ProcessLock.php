<?php declare(strict_types=1);

namespace Swooless\ThriftServer\Security;

use swoole_process;

class ProcessLock
{
    private $file;

    public function __construct(string $key = 'thrift-swoole')
    {
        $this->file = sys_get_temp_dir() . "/{$key}.pid";

        if (!file_exists($this->file)) {
            file_put_contents($this->file, '');
        }
    }

    public function lock(int $pid): bool
    {
        return file_put_contents($this->file, $pid);
    }

    public function isLock(): bool
    {
        $pid = $this->pid();

        if ($pid > 0 && swoole_process::kill($pid, 0)) {
            return true;
        }
        return false;
    }

    public function pid(): ?int
    {
        return (int)file_get_contents($this->file);
    }
}