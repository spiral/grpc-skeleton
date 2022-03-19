<?php

declare(strict_types=1);

namespace VendorName\Skeleton\Config;

use Spiral\Core\InjectableConfig;

final class CompilerConfig extends InjectableConfig
{
    public const CONFIG = 'grpcCompiler';

    public function getBinaryPath(): ?string
    {
        return $this->config['binaryPath'] ?? null;
    }

    /**
     * @return array<string>
     */
    public function getServices(): array
    {
        return (array)($this->config['services'] ?? []);
    }
}
