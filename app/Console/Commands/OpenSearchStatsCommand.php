<?php

namespace App\Console\Commands;

use App\Services\OpenSearchService;
use Illuminate\Console\Command;

class OpenSearchStatsCommand extends Command
{
    protected $signature = 'opensearch:stats
                            {--type=daily : 统计类型: daily 按日统计, totals 用户充提汇总}
                            {--timezone=Asia/Shanghai : 时区（type=daily 时使用）}
                            {--date-from= : 起始日期 Y-m-d}
                            {--date-to= : 结束日期 Y-m-d}
                            {--agent-id= : 按代理 ID 筛选}
                            {--agent-link-id= : 按推广链接 ID 筛选}
                            {--uid= : 按用户 uid 筛选（仅 totals）}
                            {--json : 以 JSON 输出}';

    protected $description = '查看 OpenSearch 统计数据（测试用）';

    public function handle(): int
    {
        $service = new OpenSearchService();

        if (! $service->isEnabled()) {
            $this->error('OpenSearch 未启用，请设置 OPENSEARCH_ENABLED=true 并配置连接。');

            return self::FAILURE;
        }

        if (! $service->ping()) {
            $this->error('OpenSearch 连接失败，请检查 OPENSEARCH_HOSTS 等配置。');

            return self::FAILURE;
        }

        $this->info('OpenSearch 连接正常。');

        $type = $this->option('type');
        if ($type === 'totals') {
            return $this->outputTotals($service);
        }

        return $this->outputDaily($service);
    }

    private function outputTotals(OpenSearchService $service): int
    {
        $options = [];
        if ($this->option('uid')) {
            $options['uid'] = $this->option('uid');
        }
        if ($this->option('date-from')) {
            $options['date_from'] = $this->option('date-from');
        }
        if ($this->option('date-to')) {
            $options['date_to'] = $this->option('date-to');
        }
        if ($this->option('agent-id') !== null && $this->option('agent-id') !== '') {
            $options['agent_id'] = (int) $this->option('agent-id');
        }
        if ($this->option('agent-link-id') !== null && $this->option('agent-link-id') !== '') {
            $options['agent_link_id'] = (int) $this->option('agent-link-id');
        }

        $result = $service->getUserDepositWithdrawTotals($options);

        if (! $result['success']) {
            $this->error($result['error'] ?? '获取失败');

            return self::FAILURE;
        }

        $data = $result['data'] ?? [];
        if ($this->option('json')) {
            $this->line(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        if (empty($data)) {
            $this->warn('无数据');

            return self::SUCCESS;
        }

        $this->table(
            ['user_id', 'deposit_total', 'deposit_completed_total', 'withdraw_total', 'withdraw_completed_total'],
            array_map(fn ($row) => [
                $row['user_id'],
                $row['deposit_total'] ?? 0,
                $row['deposit_completed_total'] ?? 0,
                $row['withdraw_total'] ?? 0,
                $row['withdraw_completed_total'] ?? 0,
            ], $data)
        );
        $this->info('共 ' . count($data) . ' 条');

        return self::SUCCESS;
    }

    private function outputDaily(OpenSearchService $service): int
    {
        $options = [
            'timezone' => $this->option('timezone') ?: 'Asia/Shanghai',
        ];
        if ($this->option('date-from')) {
            $options['date_from'] = $this->option('date-from');
        }
        if ($this->option('date-to')) {
            $options['date_to'] = $this->option('date-to');
        }
        if ($this->option('agent-id') !== null && $this->option('agent-id') !== '') {
            $options['agent_id'] = (int) $this->option('agent-id');
        }
        if ($this->option('agent-link-id') !== null && $this->option('agent-link-id') !== '') {
            $options['agent_link_id'] = (int) $this->option('agent-link-id');
        }

        $result = $service->getDailyStats($options);

        if (! $result['success']) {
            $this->error($result['error'] ?? '获取失败');

            return self::FAILURE;
        }

        $data = $result['data'] ?? [];
        if ($this->option('json')) {
            $this->line(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        if (empty($data)) {
            $this->warn('无数据');

            return self::SUCCESS;
        }

        $this->table(
            [
                'date',
                'dau',
                'registered',
                'first_deposit',
                'reg+first',
                'dep_created_u',
                'dep_done_u',
                'withdraw_u',
                'dep_created_n',
                'dep_done_n',
                'withdraw_n',
                'reg_dep_amt',
                'first_dep_amt',
            ],
            array_map(fn ($row) => [
                $row['date'],
                $row['dau'],
                $row['registered_count'],
                $row['first_deposit_users_count'],
                $row['registered_and_first_deposit_count'] ?? 0,
                $row['deposit_created_users_count'],
                $row['deposit_completed_users_count'],
                $row['withdraw_completed_users_count'],
                $row['deposit_created_count'] ?? 0,
                $row['deposit_completed_count'] ?? 0,
                $row['withdraw_completed_count'] ?? 0,
                $row['registered_users_deposit_amount'],
                $row['first_deposit_users_deposit_amount'],
            ], $data)
        );
        $this->info('共 ' . count($data) . ' 天');

        return self::SUCCESS;
    }
}
