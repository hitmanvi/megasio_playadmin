<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SplitOpenApiCommand extends Command
{
    protected $signature = 'openapi:split
                            {--input= : Input openapi.json path}
                            {--force : Overwrite existing source files}';

    protected $description = 'Split a single openapi.json into source files (base + paths/* + schemas)';

    public function handle(): int
    {
        $input = $this->option('input') ?? resource_path('swagger/openapi.json');
        $sourcesDir = resource_path('swagger/sources');
        $pathsDir = $sourcesDir.'/paths';

        if (! File::exists($input)) {
            $this->error("Input file not found: {$input}");

            return self::FAILURE;
        }

        $content = File::get($input);
        $spec = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON: '.json_last_error_msg());

            return self::FAILURE;
        }

        if (! $this->option('force') && File::isDirectory($sourcesDir)) {
            if (! $this->confirm('Sources directory already exists. Overwrite?', false)) {
                return self::SUCCESS;
            }
        }

        File::ensureDirectoryExists($pathsDir);

        $paths = $spec['paths'] ?? [];
        $schemas = $spec['components']['schemas'] ?? [];
        unset($spec['paths'], $spec['components']['schemas']);
        if (isset($spec['components']) && empty($spec['components'])) {
            unset($spec['components']);
        }
        if (isset($spec['components']['securitySchemes'])) {
            // keep securitySchemes in base
        }

        $pathGroups = $this->groupPathsByPrefix($paths);

        $this->writeJson($sourcesDir.'/base.json', $spec);
        $this->info('Written base.json');

        foreach ($pathGroups as $filename => $pathItem) {
            $path = $pathsDir.'/'.$filename.'.json';
            $this->writeJson($path, $pathItem);
            $this->line("  paths/{$filename}.json (".count($pathItem).' path(s))');
        }

        $this->writeJson($sourcesDir.'/schemas.json', $schemas);
        $this->info('Written schemas.json');

        $this->newLine();
        $this->info('Split complete. Edit files in resources/swagger/sources/ then run: php artisan openapi:build');

        return self::SUCCESS;
    }

    private function groupPathsByPrefix(array $paths): array
    {
        $groups = [];
        foreach ($paths as $pathKey => $value) {
            $prefix = trim((string) strtok($pathKey, '/'));
            $prefix = $prefix ?: 'root';
            if (! isset($groups[$prefix])) {
                $groups[$prefix] = [];
            }
            $groups[$prefix][$pathKey] = $value;
        }

        return $groups;
    }

    private function writeJson(string $path, mixed $data): void
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        File::put($path, $json);
    }
}
