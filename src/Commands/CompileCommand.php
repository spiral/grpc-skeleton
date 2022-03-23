<?php

declare(strict_types=1);

namespace VendorName\Skeleton\Commands;

use Codedungeon\PHPCliColors\Color;
use Spiral\Files\FilesInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use VendorName\Skeleton\GRPC\ProtoCompiler;

final class CompileCommand extends Command
{
    private FilesInterface $files;
    private string $root;
    private string $binaryPath;
    private array $services;

    public function __construct(
        FilesInterface $files,
        array $services,
        string $binaryPath,
        string $root
    ) {
        parent::__construct('compile-proto-files');

        $this->files = $files;
        $this->root = $root;
        $this->binaryPath = $binaryPath;
        $this->services = $services;
    }

    public function getDescription(): string
    {
        return 'Generate GPRC service code using protobuf specification';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $binaryPath = $this->binaryPath;

        if ($binaryPath !== null && ! file_exists($binaryPath)) {
            $io->error(\sprintf('PHP Server plugin binary `%s` not found. ', $binaryPath));

            return self::FAILURE;
        }

        $compiler = new ProtoCompiler(
            $this->getPath(),
            $this->getNamespace(),
            $this->files,
            $binaryPath
        );

        foreach ($this->services as $protoFile) {
            if (! file_exists($protoFile)) {
                $io->error(\sprintf('Proto file `%s` not found.', $protoFile));
                continue;
            }

            $io->info(\sprintf('Compiling <fg=cyan>`%s`</fg=cyan>:\n', $protoFile));

            try {
                $result = $compiler->compile($protoFile);
            } catch (\Throwable $e) {
                $io->writeln(\sprintf('<error>Error:</error> <fg=red>%s</fg=red>', $e->getMessage()));
                continue;
            }

            if ($result === []) {
                $io->info(\sprintf('No files were generated for `%s`.\n', $protoFile));
                continue;
            }

            foreach ($result as $file) {
                $io->writeln(\sprintf(
                    "<fg=green>•</fg=green> %s%s%s\n",
                    Color::LIGHT_WHITE,
                    $this->files->relativePath($file, $this->root),
                    Color::RESET
                ));
            }
        }

        return self::SUCCESS;
    }

    /**
     * Get or detect base source code path.
     */
    protected function getPath(): string
    {
        return __DIR__. '/../';
    }

    /**
     * Get or detect base namespace.
     */
    protected function getNamespace(): string
    {
        return 'VendorName\\Skeleton';
    }
}
