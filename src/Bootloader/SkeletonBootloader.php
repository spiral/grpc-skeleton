<?php

declare(strict_types=1);

namespace VendorName\Skeleton\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Boot\EnvironmentInterface;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Core\Container;
use Spiral\Core\InterceptableCore;
use Spiral\RoadRunner\GRPC\InvokerInterface;
use VendorName\Skeleton\GRPC\Interceptors\ValidateRequestResponseInterceptor;
use VendorName\Skeleton\GRPC\InvokerCore;
use VendorName\Skeleton\GRPC\Invoker;
use VendorName\Skeleton\Config\GRPCServicesConfig;

class SkeletonBootloader extends Bootloader
{
    protected const DEPENDENCIES = [
        \Ruvents\SpiralJwt\JwtAuthBootloader::class,
    ];

    public function __construct(
        private readonly ConfiguratorInterface $config
    ) {
    }


    public function init(EnvironmentInterface $env): void
    {
        $this->initConfig($env);
    }

    public function boot(Container $container): void
    {
        $container->bindSingleton(
            InvokerInterface::class,
            static function () use ($container): InvokerInterface {
                $core = new InterceptableCore(
                    new InvokerCore(new \Spiral\RoadRunner\GRPC\Invoker())
                );
                $core->addInterceptor(new ValidateRequestResponseInterceptor());

                return new Invoker($container, $core);
            }
        );

        $this->initServices($container);
    }

    // Do not edit this method. It will be replaced by the code generator.
    private function initConfig(EnvironmentInterface $env)
    {
        $this->config->setDefaults(
            GRPCServicesConfig::CONFIG,
            [
                'services' => [],
            ]
        );
    }

    // Do not edit this method. It will be replaced by the code generator.
    private function initServices(Container $container): void
    {
    }
}
