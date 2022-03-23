<?php

declare(strict_types=1);

namespace VendorName\Skeleton\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\Container;

class SkeletonBootloader extends Bootloader
{
    protected const BINDINGS = [];
    protected const SINGLETONS = [];
    protected const DEPENDENCIES = [];

    public function boot(Container $container): void
    {
    }

    public function start(Container $container): void
    {
    }
}
