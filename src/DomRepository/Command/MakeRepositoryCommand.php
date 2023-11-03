<?php

namespace Dovutuan\Laracom\DomRepository\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeRepositoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository {name} {--ser}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository file';

    public function handle()
    {
        $name = $this->argument('name');
        $name = str_replace('/', '\\', $name);

        $pathNamespace = implode('\\', array_slice(explode('\\', $name), 0, -1)) ?? null;

        $className = class_basename($name);

        $this->makeRepository($className, $pathNamespace);
        $this->makeService($className);
    }

    /**
     * function make repository
     * @param string $className
     * @param string|null $pathNamespace
     * @return void
     */
    private function makeRepository(string $className, string $pathNamespace = null): void
    {
        $className = str_replace(REPOSITORY_NAME, '', $className);
        $className = $className . REPOSITORY_NAME;

        $fileName = $className . '.php';

        $path = $pathNamespace ? app_path(REPOSITORIES_NAME . "\\$pathNamespace") : app_path(REPOSITORIES_NAME);
        $filePath = "$path\\$fileName";

        if (!is_dir($path)) {
            mkdir($path, recursive: true);
        }

        if (File::exists($filePath)) {
            $this->error('Repository already exists!');
            return;
        }

        file_put_contents($filePath, $this->generateRepositoryContent($className, $pathNamespace));

        $this->info("Repository [$filePath] created successfully.");
    }

    /**
     * function make repository
     * @param string $className
     * @return void
     */
    private function makeService(string $className): void
    {
        if ($this->option('ser')) {
            $className = str_replace(REPOSITORY_NAME, '', $className);
            $className = $className . SERVICE_NAME;
            $fileName = $className . '.php';

            $path = app_path(SERVICES_NAME);
            $filePath = "$path\\$fileName";

            if (!is_dir($path)) {
                mkdir($path, recursive: true);
            }

            if (File::exists($filePath)) {
                $this->error('Service already exists!');
                return;
            }

            file_put_contents($filePath, $this->generateServiceContent($className));

            $this->info("Service [$filePath] created successfully.");
        }
    }

    /**
     * function generate repository content
     * @param string $className
     * @param string|null $pathNamespace
     * @return string
     */
    private function generateRepositoryContent(string $className, string $pathNamespace = null): string
    {
        $namespace = $pathNamespace ? "namespace App\Repositories\\{$pathNamespace};" : "namespace App\Repositories;";
        $use = "use Dovutuan\Laracom\DomRepository\BaseRepository;";
        $className = "class {$className} extends BaseRepository";
        return "<?php\n\n$namespace\n\n$use\n\n$className\n{\n}\n";
    }

    /**
     * function generate service content
     * @param string $className
     * @return string
     */
    private function generateServiceContent(string $className): string
    {
        $namespace = "namespace App\Services;";
        $className = "class {$className}";
        return "<?php\n\n$namespace\n\n$className\n{\n}\n";
    }
}