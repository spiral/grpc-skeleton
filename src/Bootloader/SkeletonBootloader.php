<?php

declare(strict_types=1);

namespace VendorName\Skeleton\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\Container;
use VendorName\Skeleton\Commands;
use VendorName\Skeleton\Config\CompilerConfig;
use Spiral\Console\Bootloader\ConsoleBootloader;

class SkeletonBootloader extends Bootloader
{
    protected const BINDINGS = [];
    protected const SINGLETONS = [];
    protected const DEPENDENCIES = [
        ConsoleBootloader::class
    ];

    public function boot(Container $container, ConsoleBootloader $console): void
    {
        $this->initConfig();

        $console->addCommand(Commands\GenerateCommand::class);
    }

    public function start(Container $container): void
    {
    }

    private function initConfig(): void
    {
        $this->config->setDefaults(
            CompilerConfig::CONFIG,
            [
                'binaryPath' => __DIR__.'/../protoc-gen-php-grpc'
            ]
        );
    }
}
