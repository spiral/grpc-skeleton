<?php

declare(strict_types=1);

namespace VendorName\Skeleton\Attributes;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

#[Attribute(Attribute::TARGET_METHOD), NamedArgumentConstructor]
final class InjectInterceptor
{
    public function __construct(
        public readonly string $class,
    ) {
    }
}
