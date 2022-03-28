<?php

declare(strict_types=1);

namespace VendorName\Skeleton\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Core\Container;
use VendorName\Skeleton\Config\GRPCServicesConfig;

class SkeletonBootloader extends Bootloader
{
	public function __construct(private ConfiguratorInterface $config)
	{
	}

	public function boot(): void
	{
		$this->initGrpcServicesConfig();
	}

	public function start(Container $container): void
	{
		$this->initServices($container);
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

	private function initServices(Container $container): void
	{

	}
}
