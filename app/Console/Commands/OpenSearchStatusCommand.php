<?php

namespace App\Console\Commands;

use App\Services\OpenSearchService;
use Illuminate\Console\Command;
use Throwable;

/**
 * 检测 OpenSearch 是否启用、能否连通，并输出集群 health 与根路径信息。
 */
class OpenSearchStatusCommand extends Command
{
    protected $signature = 'opensearch:status
                            {--json : 以 JSON 打印结构化结果（便于脚本解析）}';

    protected $description = '检测 OpenSearch 配置、连通性与集群状态（ping + cluster health + info）';

    public function handle(): int
    {
        $enabled = (bool) config('opensearch.enabled', false);
        $hosts = config('opensearch.hosts', []);
        $hasAuth = (bool) (config('opensearch.username') && config('opensearch.password'));

        $payload = [
            'enabled' => $enabled,
            'hosts' => $hosts,
            'auth_configured' => $hasAuth,
            'index_prefix' => config('opensearch.index_prefix'),
            'connect_timeout' => config('opensearch.connect_timeout'),
            'request_timeout' => config('opensearch.request_timeout'),
            'ping' => null,
            'info' => null,
            'cluster_health' => null,
            'error' => null,
        ];

        if (! $enabled) {
            $payload['error'] = 'OpenSearch 未启用（OPENSEARCH_ENABLED=false）';
            $this->emit($payload);

            return self::FAILURE;
        }

        $service = new OpenSearchService();
        $client = $service->getClient();
        if ($client === null) {
            $payload['error'] = '无法创建 OpenSearch 客户端';
            $this->emit($payload);

            return self::FAILURE;
        }

        try {
            $ok = $service->ping();
            $payload['ping'] = $ok;
            if (! $ok) {
                $payload['error'] = 'Ping 失败';
                $this->emit($payload);

                return self::FAILURE;
            }
        } catch (Throwable $e) {
            $payload['ping'] = false;
            $payload['error'] = 'Ping 异常: ' . $e->getMessage();
            $this->emit($payload);

            return self::FAILURE;
        }

        try {
            $payload['info'] = $this->toArrayRecursive($client->info());
        } catch (Throwable $e) {
            $payload['info'] = null;
            $payload['error'] = 'GET / 失败: ' . $e->getMessage();
            $this->emit($payload);

            return self::FAILURE;
        }

        try {
            $payload['cluster_health'] = $this->toArrayRecursive($client->cluster()->health());
        } catch (Throwable $e) {
            $payload['cluster_health'] = null;
            $payload['error'] = 'Cluster health 失败: ' . $e->getMessage();
            $this->emit($payload);

            return self::FAILURE;
        }

        $payload['error'] = null;
        $this->emit($payload);

        return self::SUCCESS;
    }

    /**
     * @param  mixed  $value
     */
    private function toArrayRecursive($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_object($value)) {
            return json_decode(json_encode($value), true) ?? [];
        }

        return [];
    }

    private function emit(array $payload): void
    {
        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return;
        }

        $this->line('OPENSEARCH_ENABLED: ' . ($payload['enabled'] ? 'true' : 'false'));
        $this->line('Hosts: ' . implode(', ', $payload['hosts'] ?: ['(empty)']));
        $this->line('Basic auth configured: ' . ($payload['auth_configured'] ? 'yes' : 'no'));
        $this->line('index_prefix: ' . (string) ($payload['index_prefix'] ?? ''));
        $this->line(sprintf(
            'Timeouts: connect=%ds, request=%ds',
            (int) ($payload['connect_timeout'] ?? 0),
            (int) ($payload['request_timeout'] ?? 0)
        ));

        if (! empty($payload['error'])) {
            $this->newLine();
            $this->error($payload['error']);

            return;
        }

        $this->newLine();
        $this->info('Ping: OK');

        $info = $payload['info'] ?? [];
        $version = $info['version'] ?? [];
        $this->line('Cluster / node name: ' . ($info['cluster_name'] ?? '-') . ' / ' . ($info['name'] ?? '-'));
        if ($version !== []) {
            $this->line(sprintf(
                'Distribution: %s %s (Lucene %s)',
                $version['distribution'] ?? '?',
                $version['number'] ?? '?',
                $version['lucene_version'] ?? '?'
            ));
        }

        $h = $payload['cluster_health'] ?? [];
        if ($h !== []) {
            $this->newLine();
            $status = strtoupper((string) ($h['status'] ?? '?'));
            $style = match ($status) {
                'GREEN' => 'info',
                'YELLOW' => 'comment',
                'RED' => 'error',
                default => 'line',
            };
            $this->line("Cluster health status: <fg=white;options=bold>{$status}</>");
            $detail = sprintf(
                '  nodes=%s active_shards=%s primary=%s relocating=%s init=%s unassigned=%s pending_tasks=%s',
                $h['number_of_nodes'] ?? '-',
                $h['active_shards'] ?? '-',
                $h['active_primary_shards'] ?? '-',
                $h['relocating_shards'] ?? '-',
                $h['initializing_shards'] ?? '-',
                $h['unassigned_shards'] ?? '-',
                $h['number_of_pending_tasks'] ?? '-'
            );
            match ($style) {
                'info' => $this->info($detail),
                'comment' => $this->comment($detail),
                'error' => $this->error($detail),
                default => $this->line($detail),
            };
        }
    }
}
