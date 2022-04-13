<?php

declare(strict_types=1);

namespace VendorName\Skeleton\Tests\GRPC;

use VendorName\Skeleton\GRPC\RequestContext;
use VendorName\Skeleton\Tests\TestCase;

final class RequestContextTest extends TestCase
{
    private RequestContext $ctx;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ctx = new RequestContext([
            'foo' => 'bar'
        ]);
    }

    public function testNonExistTokenShouldReturnNull(): void
    {
        $this->assertNull($this->ctx->getToken());
    }

    public function testNullableTokenShouldNotBeStored(): void
    {
        $ctx = $this->ctx->withToken(null);

        $this->assertNull($ctx->getToken());
    }

    public function testTokenShouldBeStored(): void
    {
        $ctx = $this->ctx->withToken('baz');

        $this->assertSame('baz', $ctx->getToken());
    }
}
