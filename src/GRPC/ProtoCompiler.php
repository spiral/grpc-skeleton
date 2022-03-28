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
        private FilesInterface $files,
        private ?string $protocBinaryPath = null
    ) {
    }

    /**
     * @throws CompileException
     */
    public function compile(string $protoFile): CompileResult
    {
        $tmpDir = $this->tmpDir();

        \exec(
            \sprintf(
                'protoc %s --php_out=%s --php-grpc_out=%s -I %s %s 2>&1',
                $this->protocBinaryPath ? '--plugin=' . $this->protocBinaryPath : '',
                \escapeshellarg($tmpDir),
                \escapeshellarg($tmpDir),
                \escapeshellarg(dirname($protoFile)),
                \implode(' ', \array_map('escapeshellarg', $this->getProtoFiles($protoFile)))
            ),
            $output
        );

        $output = \trim(\implode("\n", $output), "\n ,");

        if ($output !== '') {
            $this->files->deleteDirectory($tmpDir);
            throw new CompileException($output);
        }

        // copying files (using relative path and namespace)
        $result = [];
        $services = [];
        foreach ($this->files->getFiles($tmpDir) as $file) {
            $result[] = $file = $this->copy($tmpDir, $file);

            if (\str_ends_with($file, 'Interface.php')) {
                $services[] = (new ServiceClientGenerator($this->files))->generate($file);
            }
        }

        $this->files->deleteDirectory($tmpDir);

        return new CompileResult($result, $services);
    }

    private function copy(string $tmpDir, string $file): string
    {
        $startPath = \str_replace('\\', '/', $this->baseNamespace);
        $source = \ltrim($this->files->relativePath($file, $tmpDir), '\\/');
        if (\str_starts_with($source, $startPath)) {
            $source = \ltrim(\substr($source, \strlen($startPath)), '\\/');
        }

        $target = $this->files->normalizePath($this->basePath . '/' . $source);

        $this->files->ensureDirectory(\dirname($target));
        $this->files->copy($file, $target);

        return $target;
    }

    private function tmpDir(): string
    {
        $directory = \sys_get_temp_dir() . '/' . \spl_object_hash($this);
        $this->files->ensureDirectory($directory);
        $this->files->deleteDirectory($directory, true);

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
