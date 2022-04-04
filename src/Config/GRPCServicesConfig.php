<?php

declare(strict_types=1);

namespace VendorName\Skeleton\Config;

use Spiral\Core\InjectableConfig;

final class GRPCServicesConfig extends InjectableConfig
{
    public const CONFIG = 'grpcServices';

    /**
     * @var array<class-string, array{host: string}>
     */
    protected $config = [
        'services' => [],
    ];

    /**
     * Get service definition.
     *
     * @return array{host: string}
     */
    public function getService(string $name): array
    {
        return $this->config['services'][$name] ?? [
            'host' => 'localhost'
        ];
    }
}
