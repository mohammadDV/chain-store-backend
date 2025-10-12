<?php

namespace Core\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

/**
 * Register domain services and repositories.
 *
 * This class bind services, repositories and other things with an interface to their implementation.
 * Making them available for dependency injection.
 */
class DomainRegistrationRepository extends ServiceProvider
{
    private array $basePaths = [
        "Repositories",
        "Services",
    ];

    /**
     * Gets a list of domain paths (relative to the domain directory) to scan for contracts.
     */
    protected function getDomainPaths(): array
    {
        // get all folders within the domain directory
        $domainPath = base_path('src/Domain');
        $directories = File::directories($domainPath);

        // get the relative path of each directory
        return array_map(function ($directory) use ($domainPath) {
            return str_replace($domainPath . '/', '', $directory);
        }, $directories);
    }

    protected function bindClasses(): void
    {
        $domainPaths = $this->getDomainPaths();
        foreach ($domainPaths as $domainPath) {
            foreach ($this->basePaths as $basePath) {
                $contractPath = base_path("src/Domain/{$domainPath}/{$basePath}/Contracts");
                // if the contracts directory does not exist, skip
                if (!File::exists($contractPath)) {
                    continue;
                }
                $contractFiles = File::allFiles($contractPath);


                foreach ($contractFiles as $contractFile) {
                    $contractClass = $this->getContractClassname($domainPath, $basePath, $contractFile);
                    $implementationClass = $this->getImplementationClassname($domainPath, $basePath, $contractFile);
                    $this->app->bind($contractClass, $implementationClass);
                }
            }
        }
    }
    /**
     * Get the fully qualified contract class name from a file.
     *
     * @param \SplFileInfo $file
     * @return string
     */
    protected function getContractClassname(string $domainPath, string $basePath, \SplFileInfo $file): string
    {
        $namespace = "Domain\\{$domainPath}\\{$basePath}\\Contracts";
        $className = str_replace('.php', '', $file->getFilename());
        return "$namespace\\$className";
    }

    /**
     * Get the fully qualified contract class name from a file.
     *
     * @param \SplFileInfo $file
     * @return string
     */
    protected function getImplementationClassname(string $domainPath, string $basePath, \SplFileInfo $file): string
    {
        $namespace = "Domain\\{$domainPath}\\{$basePath}";
        $className = str_replace('.php', '', $file->getFilename());
        if (str_starts_with($className, 'I')) {
            $className = substr($className, 1);
        }
        return "$namespace\\$className";
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->bindClasses();
    }
}
