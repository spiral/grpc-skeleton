<?php

declare(strict_types=1);

namespace VendorName\Skeleton\Generators;

use Nette\PhpGenerator\Method;
use Spiral\Core\InterceptableCore;
use Spiral\Files\FilesInterface;
use VendorName\Skeleton\Config\GRPCServicesConfig;
use VendorName\Skeleton\GRPC\ServiceClientCore;

/**
 * @internal
 */
final class BootloaderGenerator
{
    private const READONLY_COMMENT = 'Don\'t edit this method manually, it is generated by GRPC services generator.';
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
        $servicesMethod->setComment(self::READONLY_COMMENT);
        $servicesMethod->setBody(<<<'BODY'
$credentials = \Grpc\ChannelCredentials::createInsecure();


BODY
        );

        $initConfigMethod = $this->bootloader->getMethod('initConfig');
        $initConfigMethod->setComment(self::READONLY_COMMENT);

        foreach ($services as $service) {
            $this->addSingleton($servicesMethod, $service[0], $service[1]);
        }

        $this->initConfig($initConfigMethod, $services);

        $this->bootloader->addUse(GRPCServicesConfig::class);

        $this->files->write(
            $this->bootloaderPath,
            $this->bootloader->getContent()
        );
    }

    public function initConfig(Method $initMethod, array $services)
    {
        $servicesArray = [];
        $port = 9000;
        foreach ($services as $service) {
            $servicesArray[] = \sprintf(
                '%s::class => [\'host\' => $env->get(\'%s_HOST\', \'127.0.0.1:%d\')],',
                $service[1]->getClassName(),
                \strtoupper(\str_replace('Client', '', $service[1]->getClassName())),
                $port
            );
            $port++;
        }


        $body = \sprintf(
            <<<'EOL'
$this->config->setDefaults(
    GRPCServicesConfig::CONFIG,
    [
        'services' => [
            %s
        ],
    ]
);
EOL,
            \implode("\n\t\t\t", $servicesArray)
        );

        $initMethod->setBody($body);
    }

    private function addSingleton(Method $servicesMethod, ParsedClass $interface, ParsedClass $client)
    {
        $servicesMethod->addBody(
            \sprintf(
                <<<'EOL'
$container->bindSingleton(
    %s::class,
    static fn(GRPCServicesConfig $config): %s =>  new %s(
        new InterceptableCore(new ServiceClientCore(
            $config->getService(%s::class)['host'],
            ['credentials' => $credentials]
        ))
    )
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
        $this->bootloader->addUse(ServiceClientCore::class);
        $this->bootloader->addUse(InterceptableCore::class);
    }
}