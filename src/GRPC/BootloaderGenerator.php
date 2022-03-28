<?php

declare(strict_types=1);

namespace VendorName\Skeleton\GRPC;

use Nette\PhpGenerator\Method;
use Spiral\Files\FilesInterface;
use VendorName\Skeleton\Config\GRPCServicesConfig;

final class BootloaderGenerator
{
    private ParsedClass $bootloader;

    public function __construct(
        private FilesInterface $files,
        private string $bootloaderPath
    )
    {
        $this->bootloader = new ParsedClass($this->files->read($this->bootloaderPath));
    }

    /**
     * @param array<int, array{0: ParsedClass, 1: ParsedClass}> $services
     */
    public function generate(array $services): void
    {
        $servicesMethod = $this->bootloader->getMethod('initServices');
        $servicesMethod->setBody(null);

        foreach ($services as $service) {
            $this->addSingleton($servicesMethod, $service[0], $service[1]);
        }

        $this->bootloader->addUse(GRPCServicesConfig::class);

        $this->files->write(
            $this->bootloaderPath,
            $this->bootloader->getContent()
        );
    }

    private function addSingleton(Method $servicesMethod, ParsedClass $interface, ParsedClass $client)
    {
        $servicesMethod->addBody(
            \sprintf(
                <<<'EOL'
$container->bindSingleton(
    %s::class,
    static function(GRPCServicesConfig $config) : %s {
        return new %s(
            $config->getService(%s::class)['host'] ?? '127.0.0.1:9000',
            [
                'credentials' => \Grpc\ChannelCredentials::createInsecure(),
            ]
        );
    }
);

EOL,
                $interface->getClassName(),
                $interface->getClassName(),
                $client->getClassName(),
                $client->getClassName(),
            )
        );

        $this->bootloader->addUse($interface->getClassNameWithNamespace());
        $this->bootloader->addUse($client->getClassNameWithNamespace());
    }
}
