<?php

namespace App\Console\Commands;

use App\Services\OpenSearchService;
use Illuminate\Console\Command;

/**
 * 检查充提相关 index：mapping 里 uid/user_id 字段类型、文档是否带 uid、抽样内容；可选用 uid 测 getUserDepositWithdrawTotals。
 */
class OpenSearchUidProbeCommand extends Command
{
    protected $signature = 'opensearch:probe-uid
                            {--uid= : 测试该值传给 getUserDepositWithdrawTotals 的返回条数与首条摘要}
                            {--sample=3 : 每个 index 抽样打印的文档数}';

    protected $description = '检查充提 index 的 uid 字段 mapping 与文档情况，并可选测试按 uid 拉取用户汇总';

    private const EVENT_KEYS = [
        'deposit_created' => '充值创建 events-deposit-created',
        'deposit_completed' => '充值成功 events-deposit-completed',
        'withdraw_created' => '提现创建 events-withdraw-created',
        'withdraw_completed' => '提现成功 events-withdraw-completed',
    ];

    public function handle(): int
    {
        $service = new OpenSearchService();

        if (! $service->isEnabled()) {
            $this->error('OpenSearch 未启用（OPENSEARCH_ENABLED）');

            return self::FAILURE;
        }

        if (! $service->ping()) {
            $this->error('OpenSearch 连接失败');

            return self::FAILURE;
        }

        $this->info('OpenSearch 连接正常。');
        $this->newLine();

        $sample = max(1, min(20, (int) $this->option('sample')));

        foreach (self::EVENT_KEYS as $eventType => $label) {
            $index = $service->getIndexForEvent($eventType);
            $this->line("<fg=cyan>━━ {$label} ━━</> index: <fg=yellow>{$index}</>");

            $total = $this->safeTotal($service, $index);
            if ($total === null) {
                $this->warn('  无法查询（index 可能不存在或无权访问）');
                $this->newLine();

                continue;
            }

            $mapping = $service->getIndexMapping($index);
            $this->line('  <fg=gray>mapping</> uid: '.$this->formatFieldMapping($mapping['uid'] ?? null).'  user_id: '.$this->formatFieldMapping($mapping['user_id'] ?? null));
            if (isset($mapping['uid']) && ($mapping['uid']['type'] ?? '') === 'text' && empty($mapping['uid']['fields']['keyword'] ?? null)) {
                $this->warn('  ⚠ uid 为 text 且无 .keyword 子字段时，term 查整串会匹配不到，建议重建 index 或加 keyword 子字段');
            }

            $withUid = $this->safeTotal($service, $index, ['exists' => ['field' => 'uid']]);
            $pct = $total > 0 && $withUid !== null ? round(100 * $withUid / $total, 2) : 0;
            $this->line("  文档总数: {$total}");
            $this->line('  含字段 <fg=green>uid</> 的文档数: '.($withUid ?? '?').($withUid !== null && $total > 0 ? " ({$pct}%)" : ''));

            $hits = $this->sampleHits($service, $index, $sample);
            if (empty($hits)) {
                $this->line('  抽样: 无命中');
            } else {
                $this->line("  抽样 {$sample} 条 _source 字段与 uid/user_id:");
                foreach ($hits as $i => $hit) {
                    $src = $hit['_source'] ?? [];
                    $keys = implode(', ', array_keys($src));
                    $uid = $src['uid'] ?? '(无)';
                    $userId = $src['user_id'] ?? '(无)';
                    $this->line('    #'.($i + 1)." keys=[{$keys}]  uid=<fg=magenta>{$uid}</>  user_id=<fg=magenta>{$userId}</>");
                }
            }
            $this->newLine();
        }

        if ($this->option('uid') !== null && $this->option('uid') !== '') {
            $uid = (string) $this->option('uid');
            $this->line('<fg=cyan>━━ getUserDepositWithdrawTotals(uid) ━━</>');
            $this->line("  参数 uid = <fg=yellow>{$uid}</>");

            $result = $service->getUserDepositWithdrawTotals(['uid' => $uid]);
            if (! $result['success']) {
                $this->error('  失败: '.($result['error'] ?? 'unknown'));

                return self::FAILURE;
            }

            $rows = $result['data'] ?? [];
            $this->line('  返回用户数: <fg=green>'.count($rows).'</>');
            if (count($rows) > 0) {
                $r = $rows[0];
                $this->line('  首条: user_id='.$r['user_id']
                    .' deposit_total='.($r['deposit_total'] ?? 0)
                    .' deposit_completed_total='.($r['deposit_completed_total'] ?? 0)
                    .' withdraw_total='.($r['withdraw_total'] ?? 0)
                    .' withdraw_completed_total='.($r['withdraw_completed_total'] ?? 0));
                if (! empty($r['user']['uid'])) {
                    $this->line('  库用户 uid: '.$r['user']['uid']);
                }
            } else {
                $this->warn('  无数据：若文档只有 user_id 无 uid，请传数字 user_id 或库里存在的业务 uid。');
            }
        } else {
            $this->comment('提示: 加 --uid=某值 可测试按 uid 拉取用户充提汇总。');
        }

        return self::SUCCESS;
    }

    private function safeTotal(OpenSearchService $service, string $index, ?array $queryClause = null): ?int
    {
        $query = $queryClause !== null
            ? ['query' => $queryClause]
            : ['query' => ['match_all' => (object) []]];

        $r = $service->search($index, $query, ['size' => 0]);
        if (! $r['success']) {
            return null;
        }

        return (int) ($r['total'] ?? 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sampleHits(OpenSearchService $service, string $index, int $size): array
    {
        $r = $service->search($index, ['query' => ['match_all' => (object) []]], [
            'size' => $size,
            'sort' => [['@timestamp' => ['order' => 'desc']]],
        ]);
        if (! $r['success']) {
            return [];
        }

        return $r['hits'] ?? [];
    }

    private function formatFieldMapping(?array $field): string
    {
        if ($field === null || ! is_array($field)) {
            return '<fg=red>(无)</>';
        }
        $type = $field['type'] ?? '?';
        $out = $type;
        if (! empty($field['fields']['keyword']['type'])) {
            $out .= ' + .keyword';
        }
        return $out;
    }
}
