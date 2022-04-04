<?php

declare(strict_types=1);

namespace VendorName\Skeleton\Attributes;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

#[Attribute(Attribute::TARGET_METHOD), NamedArgumentConstructor]
class Guarded
{
    public function __construct(
        private string $tokenField = 'token'
    ) {
    }

    /**
     * Get the key of token field
     */
    public function getTokenField(): string
    {
        return $this->tokenField;
    }
}
