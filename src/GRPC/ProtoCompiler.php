<?php

declare(strict_types=1);

namespace VendorName\Skeleton\GRPC;

use Spiral\Files\FilesInterface;
use VendorName\Skeleton\GRPC\Exception\CompileException;

final class ProtoCompiler
{
    private FilesInterface $files;
    private string $basePath;
    private string $baseNamespace;
    private ?string $protocBinaryPath;

    public function __construct(
        string $basePath,
        string $baseNamespace,
        FilesInterface $files,
        ?string $protocBinaryPath = null
    ) {
        $this->basePath = $basePath;
        $this->baseNamespace = str_replace('\\', '/', rtrim($baseNamespace, '\\'));
        $this->files = $files;
        $this->protocBinaryPath = $protocBinaryPath;
    }

    /**
     * @throws CompileException
     */
    public function compile(string $protoFile): array
    {
        $tmpDir = $this->tmpDir();

        exec(
            sprintf(
                'protoc %s --php_out=%s --php-grpc_out=%s -I %s %s 2>&1',
                $this->protocBinaryPath ? '--plugin=' . $this->protocBinaryPath : '',
                escapeshellarg($tmpDir),
                escapeshellarg($tmpDir),
                escapeshellarg(dirname($protoFile)),
                implode(' ', array_map('escapeshellarg', $this->getProtoFiles($protoFile)))
            ),
            $output
        );

        $output = trim(implode("\n", $output), "\n ,");

        if ($output !== '') {
            $this->files->deleteDirectory($tmpDir);
            throw new CompileException($output);
        }

        // copying files (using relative path and namespace)
        $result = [];
        foreach ($this->files->getFiles($tmpDir) as $file) {
            $result[] = $file = $this->copy($tmpDir, $file);

            if (str_ends_with($file, 'Interface.php')) {
                $this->files->write(
                    str_replace('Interface.php', 'Client.php', $file),
                    $this->generateClientService($file)
                );
            }
        }

        $this->files->deleteDirectory($tmpDir);

        return $result;
    }

    private function copy(string $tmpDir, string $file): string
    {
        $source = ltrim($this->files->relativePath($file, $tmpDir), '\\/');
        if (strpos($source, $this->baseNamespace) === 0) {
            $source = ltrim(substr($source, strlen($this->baseNamespace)), '\\/');
        }

        $target = $this->files->normalizePath($this->basePath . '/' . $source);

        $this->files->ensureDirectory(dirname($target));
        $this->files->copy($file, $target);

        return $target;
    }

    private function tmpDir(): string
    {
        $directory = sys_get_temp_dir() . '/' . spl_object_hash($this);
        $this->files->ensureDirectory($directory);

        return $this->files->normalizePath($directory, true);
    }

    /**
     * Include all proto files from the directory.
     */
    private function getProtoFiles(string $protoFile): array
    {
        return [$protoFile];
    }

    private function generateClientService(string $file): string
    {
        $file = \Nette\PhpGenerator\PhpFile::fromCode(file_get_contents($file));

        $namespace = $file->getNamespaces()[array_key_first($file->getNamespaces())];
        $class = $namespace->getClasses()[array_key_first($namespace->getClasses())];

        $file = new \Nette\PhpGenerator\PhpFile;
        $file->setStrictTypes();

        $client = new \Nette\PhpGenerator\PhpNamespace($namespace->getName());
        $file->addNamespace($client);
        $clientClass = $client->addClass(str_replace('Interface', 'Client', $class->getName()));
        $clientClass->addExtend(\Grpc\BaseStub::class);

        foreach ($class->getMethods() as $method) {
            $clientMethod = $clientClass->addMethod($method->getName());
            $clientMethod->setParameters([$request = $method->getParameters()['in']]);
            $clientMethod->addParameter('metadata')->setType('array')->setDefaultValue([]);
            $clientMethod->addParameter('options')->setType('array')->setDefaultValue([]);

            $clientMethod->addBody(
                \sprintf(
                    <<<'EOL'
return $this->_simpleRequest(
    '/%s',
    $%s,
    [%s::class, 'decode'],
    $metadata,
    $options
);
EOL,
                    \str_replace('"', '', (string)$class->getConstants()['NAME']->getValue()) . '/' . $method->getName(),
                    $request->getName(),
                    $method->getReturnType()
                )
            );
        }

        $printer = new \Nette\PhpGenerator\Printer;
        return (string) $printer->printFile($file);
    }
}
