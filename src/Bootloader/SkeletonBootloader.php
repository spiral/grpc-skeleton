<?php

declare(strict_types=1);

namespace VendorName\Skeleton\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Config\ConfiguratorInterface;
use VendorName\Skeleton\Config\GRPCServicesConfig;

class SkeletonBootloader extends Bootloader
{
    protected const BINDINGS = [];
    protected const SINGLETONS = [];
    protected const DEPENDENCIES = [];

    public function __construct(
        private ConfiguratorInterface $config
    ) {
    }

    public function boot(): void
    {
        $this->initGrpcServicesConfig();
    }

    private function initGrpcServicesConfig()
    {
        $this->config->setDefaults(
            GRPCServicesConfig::CONFIG,
            [
                'services' => [],
            ]
        );
    }
}
