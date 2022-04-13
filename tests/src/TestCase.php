<?php

namespace VendorName\Skeleton\Tests;

abstract class TestCase extends \Spiral\Testing\TestCase
{
    public function rootDirectory(): string
    {
        return __DIR__.'/../';
    }

    public function defineBootloaders(): array
    {
        return [
            \Spiral\Boot\Bootloader\ConfigurationBootloader::class,
            \VendorName\Skeleton\Bootloader\SkeletonBootloader::class,
            // ...
        ];
    }
}
