<?php

declare(strict_types=1);

namespace VendorName\Skeleton\GRPC;

use Spiral\Files\FilesInterface;
use VendorName\Skeleton\GRPC\Exception\CompileException;

final class ProtoCompiler
{
    public function __construct(
        private string $basePath,
        private string $baseNamespace,
        private string $bootloaderPath,
        private FilesInterface $files,
        private ?string $protocBinaryPath = null
    ) {
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
                escapeshellarg($this->basePath),
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
                (new ServiceClientGenerator($this->files, $this->bootloaderPath))->generate($file);
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

        $target = $this->files->normalizePath($this->basePath . '/src/' . $source);

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
}
