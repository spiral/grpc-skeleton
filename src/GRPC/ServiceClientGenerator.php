<?php

declare(strict_types=1);

namespace VendorName\Skeleton\GRPC;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\Printer;
use Spiral\Files\FilesInterface;
use VendorName\Skeleton\Config\GRPCServicesConfig;

final class ServiceClientGenerator
{
    public function __construct(
        private FilesInterface $files,
        private string $bootloaderPath
    ) {
    }

    public function generate(string $interfacePath): void
    {
        $interfaceFile = PhpFile::fromCode($this->files->read($interfacePath));

        $interfaceNamespace = $interfaceFile->getNamespaces()[array_key_first($interfaceFile->getNamespaces())];
        $interfaceClass = $interfaceNamespace->getClasses()[array_key_first($interfaceNamespace->getClasses())];

        $file = new PhpFile;
        $file->setStrictTypes();

        $client = new \Nette\PhpGenerator\PhpNamespace($interfaceNamespace->getName());
        $file->addNamespace($client);
        $clientClass = $client->addClass(str_replace('Interface', 'Client', $interfaceClass->getName()));
        $clientClass->addExtend(\Grpc\BaseStub::class);
        $clientClass->addImplement($interfaceClass->getName());

        foreach ($interfaceClass->getMethods() as $method) {
            $clientMethod = $clientClass->addMethod($method->getName());
            $clientMethod->setParameters($method->getParameters());
            $clientMethod->setReturnType($method->getReturnType());

            $clientMethod->addBody(
                \sprintf(
                    <<<'EOL'
[$response, $status] = $this->_simpleRequest(
    '/'.self::NAME.'/%s',
    $in,
    [%s::class, 'decode'],
    $metadata,
    $options
)->wait();

return $response;
EOL,
                    $method->getName(),
                    $clientMethod->getReturnType()
                )
            );
        }

        $file->addUse($interfaceClass->getName());

        $this->files->write(
            str_replace('Interface.php', 'Client.php', $interfacePath),
            (new Printer)->printFile($file)
        );

        $this->updateBootloader($interfaceClass, $clientClass);
    }

    private function updateBootloader(
        ClassType $interfaceClass,
        ClassType $serviceClass
    ) {
        $bootloader = PhpFile::fromCode($this->files->read($this->bootloaderPath));
        $bootloaderNamespace = $bootloader->getNamespaces()[array_key_first($bootloader->getNamespaces())];
        $bootloaderClass = $bootloaderNamespace->getClasses()[array_key_first($bootloaderNamespace->getClasses())];

        $singletons = (array)$bootloaderClass->getConstants()['SINGLETONS'];
        if (!array_key_exists($interfaceClass->getName(), $singletons)) {
            $singletons[$interfaceClass->getName()] = [
                new Literal('static::class'),
                $methodName = 'init' . $serviceClass->getName(),
            ];

            $method = $bootloaderClass->addMethod($methodName);
            $method->addParameter('config')->setType(GRPCServicesConfig::class);

            $method->addBody(
                \sprintf(
                    <<<'EOL'
return new %s(
    $config->get(%s::class)['host'],
    [
        'credentials' => \Grpc\ChannelCredentials::createInsecure(),
    ]
);
EOL,
                    $serviceClass->getName(),
                    $serviceClass->getName()
                )
            );

            $method->setReturnType($interfaceClass->getName());
        }

        $this->files->write(
            $this->bootloaderPath,
            (new Printer)->printFile($bootloader)
        );
    }
}
