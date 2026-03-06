<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BuildOpenApiCommand extends Command
{
    protected $signature = 'openapi:build
                            {--output= : Output path (default: resources/swagger/openapi.json)}';

    protected $description = 'Merge split OpenAPI sources (base + paths/* + schemas) into a single openapi.json';

    public function handle(): int
    {
        $sourcesDir = resource_path('swagger/sources');
        $pathsDir = $sourcesDir.'/paths';
        $output = $this->option('output') ?? resource_path('swagger/openapi.json');

        if (! File::isDirectory($sourcesDir)) {
            $this->error("Sources directory not found: {$sourcesDir}");
            $this->line('Run php artisan openapi:split first to create it from the current openapi.json.');

            return self::FAILURE;
        }

        $basePath = $sourcesDir.'/base.json';
        if (! File::exists($basePath)) {
            $this->error("Base file not found: {$basePath}");

            return self::FAILURE;
        }

        $base = $this->decodeJson(File::get($basePath), 'base.json');
        if ($base === null) {
            return self::FAILURE;
        }

        $base['paths'] = [];

        if (File::isDirectory($pathsDir)) {
            $pathFiles = File::glob($pathsDir.'/*.json');
            foreach ($pathFiles as $pathFile) {
                $name = pathinfo($pathFile, PATHINFO_FILENAME);
                $content = File::get($pathFile);
                $data = $this->decodeJson($content, $name.'.json');
                if ($data === null) {
                    return self::FAILURE;
                }
                if (! is_array($data)) {
                    $this->error("Path file must be a JSON object: {$name}.json");

                    return self::FAILURE;
                }
                $base['paths'] = array_merge($base['paths'], $data);
            }
            $this->info('Merged '.count($pathFiles).' path file(s).');
        }

        $schemasPath = $sourcesDir.'/schemas.json';
        if (File::exists($schemasPath)) {
            $schemas = $this->decodeJson(File::get($schemasPath), 'schemas.json');
            if ($schemas === null) {
                return self::FAILURE;
            }
            $base['components'] = $base['components'] ?? [];
            $base['components']['schemas'] = $schemas;
        }

        $json = json_encode($base, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $this->error('Failed to encode merged OpenAPI: '.json_last_error_msg());

            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($output));
        File::put($output, $json);

        $this->info("Written: {$output}");

        return self::SUCCESS;
    }

    private function decodeJson(string $content, string $label): array|null
    {
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Invalid JSON in {$label}: ".json_last_error_msg());

            return null;
        }

        return $data;
    }
}
