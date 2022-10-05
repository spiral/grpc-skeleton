<?php

declare(strict_types=1);

namespace VendorName\Skeleton\GRPC;

use Spiral\RoadRunner\GRPC\ContextInterface;

final class RequestContext implements ContextInterface
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        private array $values = []
    ) {
    }

    /**
     * Add value to the metadata.
     */
    public function withTelemetry(array $context): ContextInterface
    {

        $metadata = $this->getValue('metadata', []);
        $metadata['telemetry'] = $context;

        return $this->withMetadata($metadata);
    }

    /**
     * Add value to the metadata.
     */
    public function withToken(?string $token, string $key = 'token'): ContextInterface
    {
        if ($token === null) {
            return $this;
        }

        $metadata = $this->getValue('metadata', []);
        $metadata[$key] = [$token];

        return $this->withMetadata($metadata);
    }

    /**
     * Get token from the metadata.
     */
    public function getToken(string $key = 'token'): ?string
    {
        return $this->getValue('metadata', [])[$key][0] ?? null;
    }

    /**
     * Set metadata to the context.
     */
    public function withMetadata(array $metadata): ContextInterface
    {
        return $this->withValue('metadata', $metadata);
    }

    /**
     * Set options to the context.
     */
    public function withOptions(array $metadata): ContextInterface
    {
        return $this->withValue('options', $metadata);
    }

    /**
     * Add value to the context.
     */
    public function withValue(string $key, $value): ContextInterface
    {
        $ctx = clone $this;
        $ctx->values[$key] = $value;

        return $ctx;
    }

    /**
     * Get value from the context.
     */
    public function getValue(string $key, $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    /**
     * Get all values from the context.
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
