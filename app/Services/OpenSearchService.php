<?php

namespace App\Services;

use App\Models\Deposit;
use App\Models\User;
use Carbon\Carbon;
use OpenSearch\Client;
use OpenSearch\ClientBuilder;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * OpenSearch 基础交互服务（兼容 AWS OpenSearch）
 *
 * 用于统计功能：事件发生时上传到 OpenSearch。
 * - 不同事件上传到不同 index
 * - 不同 index 设计不同模版
 * - 支持多个 index 聚合查询
 */
class OpenSearchService
{
    protected ?Client $client = null;

    protected bool $enabled = false;

    protected string $indexPrefix = '';

    public function __construct()
    {
        $this->enabled = config('opensearch.enabled', false);
        $this->indexPrefix = rtrim(config('opensearch.index_prefix', 'playapi'), '-');
    }

    protected function debug(string $message, array $context = []): void
    {
        if (config('opensearch.debug', false)) {
            Log::debug('[OpenSearch] ' . $message, $context);
        }
    }

    /**
     * 规范化 host：https 无端口时补 :443，避免 opensearch-php 错误使用 9200（AWS OpenSearch 兼容）
     */
    protected function normalizeHost(string $host): string
    {
        if (empty($host)) {
            return $host;
        }
        $parsed = parse_url($host);
        if (!is_array($parsed) || !isset($parsed['host'])) {
            return $host;
        }
        if (isset($parsed['port'])) {
            return $host;
        }
        $scheme = $parsed['scheme'] ?? 'http';
        $path = $parsed['path'] ?? '';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
        $port = ($scheme === 'https') ? ':443' : ':9200';

        return $scheme . '://' . $parsed['host'] . $port . $path . $query . $fragment;
    }

    /**
     * 获取 OpenSearch 客户端（懒加载）
     */
    public function getClient(): ?Client
    {
        if (!$this->enabled) {
            return null;
        }

        if ($this->client === null) {
            $this->client = $this->buildClient();
        }

        return $this->client;
    }

    /**
     * 构建 OpenSearch 客户端
     */
    protected function buildClient(): Client
    {
        $hosts = config('opensearch.hosts', ['http://localhost:9200']);
        $hosts = array_filter(array_map('trim', $hosts));
        $hosts = !empty($hosts) ? $hosts : ['http://localhost:9200'];

        // AWS OpenSearch: https URL 无端口时 opensearch-php 会错误使用 9200，需显式加 :443
        $hosts = array_map([$this, 'normalizeHost'], $hosts);

        $params = [
            'hosts' => $hosts,
        ];

        if (($username = config('opensearch.username')) && ($password = config('opensearch.password'))) {
            $params['basicAuthentication'] = [$username, $password];
        }

        $builder = ClientBuilder::create();
        $builder->setHosts($params['hosts']);

        if (isset($params['basicAuthentication'])) {
            $builder->setBasicAuthentication(
                $params['basicAuthentication'][0],
                $params['basicAuthentication'][1]
            );
        }

        if ($logger = Log::getLogger()) {
            $builder->setLogger($logger);
        }

        $client = $builder->build();
        $this->debug('Client built', ['hosts' => $params['hosts'], 'auth' => isset($params['basicAuthentication'])]);
        return $client;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * 获取完整 index 名称（带前缀）
     */
    public function getIndexName(string $indexSuffix): string
    {
        return $this->indexPrefix . '-' . ltrim($indexSuffix, '-');
    }

    /**
     * 根据事件类型获取 index 名称
     */
    public function getIndexForEvent(string $eventType): string
    {
        $indices = config('opensearch.event_indices', []);
        $suffix = $indices[$eventType] ?? 'events-' . str_replace('_', '-', $eventType);

        return $this->getIndexName($suffix);
    }

    /**
     * 健康检查 / Ping
     */
    public function ping(): bool
    {
        $client = $this->getClient();
        if (!$client) {
            return false;
        }

        try {
            $this->debug('Ping start');
            $client->ping();
            $this->debug('Ping ok');
            return true;
        } catch (Throwable $e) {
            Log::warning('OpenSearch ping failed', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 索引单个文档
     *
     * @param  string  $index  index 名称（完整名或后缀，会自动加前缀）
     * @param  array  $document  文档内容
     * @param  string|null  $id  文档 ID，为空则自动生成
     * @return array{success: bool, id?: string, error?: string} 索引结果
     */
    public function indexDocument(string $index, array $document, ?string $id = null): array
    {
        $client = $this->getClient();
        if (!$client) {
            return ['success' => false, 'error' => 'OpenSearch disabled'];
        }

        $indexName = str_contains($index, '-') ? $index : $this->getIndexName($index);

        $params = [
            'index' => $indexName,
            'body' => $document,
        ];
        if ($id !== null) {
            $params['id'] = $id;
        }

        try {
            $this->debug('Index document', ['index' => $indexName, 'id' => $id, 'document_keys' => array_keys($document)]);
            $response = $client->index($params);
            $responseArray = is_array($response) ? $response : (array) $response;
            $docId = $responseArray['_id'] ?? $id;
            $this->debug('Index document ok', ['index' => $indexName, '_id' => $docId]);

            return [
                'success' => true,
                'id' => $docId,
            ];
        } catch (Throwable $e) {
            $this->debug('Index document failed', ['index' => $indexName, 'error' => $e->getMessage()]);
            Log::error('OpenSearch index failed', [
                'index' => $indexName,
                'message' => $e->getMessage(),
                'document' => $document,
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 批量索引文档
     *
     * @param  string  $index  index 名称
     * @param  array<int, array>  $documents  [['id' => 'xxx', 'body' => [...]], ...]，id 可选
     * @return array{success: bool, indexed: int, errors: array, error?: string}
     */
    public function bulkIndex(string $index, array $documents): array
    {
        $client = $this->getClient();
        if (!$client) {
            return ['success' => false, 'indexed' => 0, 'errors' => [], 'error' => 'OpenSearch disabled'];
        }

        $indexName = str_contains($index, '-') ? $index : $this->getIndexName($index);

        $body = [];
        foreach ($documents as $doc) {
            $action = ['index' => ['_index' => $indexName]];
            if (!empty($doc['id'])) {
                $action['index']['_id'] = $doc['id'];
            }
            $body[] = $action;
            $body[] = $doc['body'] ?? $doc;
        }

        if (empty($body)) {
            return ['success' => true, 'indexed' => 0, 'errors' => []];
        }

        try {
            $this->debug('Bulk index', ['index' => $indexName, 'count' => count($documents)]);
            $response = $client->bulk(['body' => $body]);
            $responseArray = is_array($response) ? $response : (array) $response;

            $errors = [];
            $indexed = 0;
            if (isset($responseArray['items'])) {
                foreach ($responseArray['items'] as $item) {
                    $op = $item['index'] ?? $item['create'] ?? $item;
                    if (isset($op['error'])) {
                        $errors[] = $op['error'];
                    } else {
                        $indexed++;
                    }
                }
            }

            $this->debug('Bulk index ok', ['index' => $indexName, 'indexed' => $indexed, 'errors_count' => count($errors)]);
            return [
                'success' => empty($errors),
                'indexed' => $indexed,
                'errors' => $errors,
            ];
        } catch (Throwable $e) {
            $this->debug('Bulk index failed', ['index' => $indexName, 'error' => $e->getMessage()]);
            Log::error('OpenSearch bulk index failed', [
                'index' => $indexName,
                'message' => $e->getMessage(),
                'count' => count($documents),
            ]);
            return [
                'success' => false,
                'indexed' => 0,
                'errors' => [$e->getMessage()],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 根据 ID 获取文档
     *
     * @return array{success: bool, document?: array, found?: bool, error?: string}
     */
    public function getDocument(string $index, string $id): array
    {
        $client = $this->getClient();
        if (!$client) {
            return ['success' => false, 'error' => 'OpenSearch disabled'];
        }

        $indexName = str_contains($index, '-') ? $index : $this->getIndexName($index);

        try {
            $this->debug('Get document', ['index' => $indexName, 'id' => $id]);
            $response = $client->get([
                'index' => $indexName,
                'id' => $id,
            ]);
            $responseArray = is_array($response) ? $response : (array) $response;

            return [
                'success' => true,
                'found' => ($responseArray['found'] ?? false),
                'document' => $responseArray['_source'] ?? null,
                '_id' => $responseArray['_id'] ?? $id,
                '_index' => $responseArray['_index'] ?? $indexName,
            ];
        } catch (Throwable $e) {
            $this->debug('Get document failed', ['index' => $indexName, 'id' => $id, 'error' => $e->getMessage()]);
            $isNotFound = str_contains($e->getMessage(), '404') || str_contains($e->getMessage(), 'not_found');
            if (!$isNotFound) {
                Log::error('OpenSearch get document failed', [
                    'index' => $indexName,
                    'id' => $id,
                    'message' => $e->getMessage(),
                ]);
            }
            return [
                'success' => $isNotFound,
                'found' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 搜索（支持单 index 或多 index 聚合）
     *
     * @param  string|array  $indices  index 名称或索引名数组，支持通配符如 'events-*'
     * @param  array  $query  OpenSearch 查询 DSL
     * @param  array  $options  size, from, sort, aggs 等
     * @return array{success: bool, hits?: array, total?: int, aggregations?: array, error?: string}
     */
    public function search(string|array $indices, array $query = [], array $options = []): array
    {
        $client = $this->getClient();
        if (!$client) {
            return ['success' => false, 'error' => 'OpenSearch disabled'];
        }

        $indexNames = is_array($indices)
            ? array_map(fn ($i) => str_contains($i, '-') ? $i : $this->getIndexName($i), $indices)
            : (str_contains($indices, '-') ? $indices : $this->getIndexName($indices));

        $params = [
            'index' => $indexNames,
            'body' => array_filter($query) ?: ['query' => ['match_all' => (object) []]],
        ];

        if (isset($options['size'])) {
            $params['body']['size'] = (int) $options['size'];
        }
        if (isset($options['from'])) {
            $params['body']['from'] = (int) $options['from'];
        }
        if (isset($options['sort'])) {
            $params['body']['sort'] = $options['sort'];
        }
        if (isset($options['aggs'])) {
            $params['body']['aggs'] = $options['aggs'];
        }
        if (isset($options['_source'])) {
            $params['body']['_source'] = $options['_source'];
        }

        try {
            $this->debug('Search', ['indices' => $indexNames, 'query' => $params['body']]);
            $response = $client->search($params);
            $responseArray = is_array($response) ? $response : (array) $response;

            $total = $responseArray['hits']['total'] ?? 0;
            if (is_array($total) && isset($total['value'])) {
                $total = $total['value'];
            }

            $this->debug('Search ok', ['indices' => $indexNames, 'total' => (int) $total, 'hits_count' => count($responseArray['hits']['hits'] ?? [])]);
            return [
                'success' => true,
                'hits' => $responseArray['hits']['hits'] ?? [],
                'total' => (int) $total,
                'aggregations' => $responseArray['aggregations'] ?? [],
            ];
        } catch (Throwable $e) {
            $this->debug('Search failed', ['indices' => $indexNames, 'error' => $e->getMessage()]);
            Log::error('OpenSearch search failed', [
                'indices' => $indexNames,
                'message' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 多 index 聚合搜索（便捷方法）
     *
     * @param  array  $indexPatterns  ['events-*', 'events-deposit'] 等
     * @param  array  $query  查询
     * @param  array  $options  选项
     */
    public function searchMultiple(array $indexPatterns, array $query = [], array $options = []): array
    {
        $indices = array_map(fn ($p) => $this->getIndexName($p), $indexPatterns);

        return $this->search($indices, $query, $options);
    }

    /**
     * 创建 index（含 settings）
     */
    public function createIndex(string $index, array $settings = [], array $mappings = []): array
    {
        $client = $this->getClient();
        if (!$client) {
            return ['success' => false, 'error' => 'OpenSearch disabled'];
        }

        $indexName = str_contains($index, '-') ? $index : $this->getIndexName($index);

        $body = [];
        if (!empty($settings)) {
            $body['settings'] = $settings;
        }
        if (!empty($mappings)) {
            $body['mappings'] = $mappings;
        }

        try {
            $client->indices()->create([
                'index' => $indexName,
                'body' => $body,
            ]);
            return ['success' => true];
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'resource_already_exists')) {
                return ['success' => true, 'existed' => true];
            }
            Log::error('OpenSearch create index exception', [
                'index' => $indexName,
                'message' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 创建/更新 index template
     *
     * @param  string  $name  template 名称
     * @param  array  $indexPatterns  匹配的 index 模式，如 ['playapi-events-*']
     * @param  array  $template  含 settings 和 mappings
     */
    public function putIndexTemplate(string $name, array $indexPatterns, array $template): array
    {
        $client = $this->getClient();
        if (!$client) {
            return ['success' => false, 'error' => 'OpenSearch disabled'];
        }

        $patterns = array_map(
            fn ($p) => str_contains($p, $this->indexPrefix) ? $p : $this->getIndexName($p),
            $indexPatterns
        );

        $body = [
            'index_patterns' => $patterns,
            ...$template,
        ];

        try {
            $client->indices()->putIndexTemplate([
                'name' => $name,
                'body' => $body,
            ]);
            return ['success' => true];
        } catch (Throwable $e) {
            Log::error('OpenSearch put index template failed', [
                'name' => $name,
                'message' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 检查 index 是否存在
     */
    public function indexExists(string $index): bool
    {
        $client = $this->getClient();
        if (!$client) {
            return false;
        }

        $indexName = str_contains($index, '-') ? $index : $this->getIndexName($index);

        try {
            return $client->indices()->exists(['index' => $indexName]);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * 上报事件到 OpenSearch（便捷方法）
     *
     * @param  string  $eventType  事件类型，对应 config.event_indices
     * @param  array  $payload  事件数据
     * @param  string|null  $id  文档 ID，指定时用于幂等（相同 ID 覆盖），不传则自动生成
     * @return array{success: bool, id?: string, error?: string}
     */
    public function indexEvent(string $eventType, array $payload, ?string $id = null): array
    {
        $index = $this->getIndexForEvent($eventType);
        $this->debug('Index event', ['event_type' => $eventType, 'index' => $index, 'id' => $id, 'payload_keys' => array_keys($payload)]);

        $document = array_merge([
            'event_type' => $eventType,
            '@timestamp' => now()->toIso8601String(),
        ], $payload);

        return $this->indexDocument($index, $document, $id);
    }

    /**
     * 规范化时区：支持 UTC+4、UTC-4、UTC+8 等格式，转为 Carbon 可用的时区
     */
    protected function normalizeTimezone(string $timezone): string
    {
        $timezone = trim($timezone);
        if (strtoupper($timezone) === 'UTC') {
            return 'UTC';
        }
        if (preg_match('/^UTC([+-])(\d+)(?::(\d+))?$/i', $timezone, $m)) {
            $sign = $m[1];
            $h = str_pad((int) $m[2], 2, '0', STR_PAD_LEFT);
            $min = isset($m[3]) ? str_pad((int) $m[3], 2, '0', STR_PAD_LEFT) : '00';
            return $sign . $h . ':' . $min;
        }
        return $timezone;
    }

    /**
     * 构建用户充提汇总的过滤条件
     *
     * @param  array  $options  uid, date_from, date_to, agent_id, agent_link_id, timezone
     * @return array  OpenSearch bool query
     */
    protected function buildUserDepositWithdrawFilters(array $options): array
    {
        $must = [];
        $timezone = $this->normalizeTimezone($options['timezone'] ?? 'UTC');

        if (!empty($options['uid'])) {
            $must[] = ['term' => ['uid' => (string) $options['uid']]];
        }
        if (array_key_exists('agent_id', $options) && $options['agent_id'] !== '' && $options['agent_id'] !== null) {
            $must[] = ['term' => ['agent_id' => (int) $options['agent_id']]];
        }
        if (array_key_exists('agent_link_id', $options) && $options['agent_link_id'] !== '' && $options['agent_link_id'] !== null) {
            $must[] = ['term' => ['agent_link_id' => (int) $options['agent_link_id']]];
        }

        $dateFrom = $options['date_from'] ?? null;
        $dateTo = $options['date_to'] ?? null;
        if ($dateFrom || $dateTo) {
            $range = [];
            if ($dateFrom) {
                $start = Carbon::parse($dateFrom . ' 00:00:00', $timezone)->utc()->format('c');
                $range['gte'] = $start;
            }
            if ($dateTo) {
                $end = Carbon::parse($dateTo . ' 23:59:59', $timezone)->utc()->format('c');
                $range['lte'] = $end;
            }
            if (!empty($range)) {
                $must[] = ['range' => ['@timestamp' => $range]];
            }
        }

        if (empty($must)) {
            return ['query' => ['match_all' => (object) []]];
        }

        return ['query' => ['bool' => ['must' => $must]]];
    }

    /**
     * 获取用户充提金额汇总（从 OpenSearch 聚合）
     * 每个用户一条数据：充值/提现金额、用户基本信息；首次/末次成功充值时间从数据库取（与请求时间范围无关）。
     *
     * @param  array  $options  size, uid, date_from, date_to, agent_id, agent_link_id, timezone
     * @return array{success: bool, data?: array<int, array{user_id: int, user: array|null, first_deposit_at: string|null, last_deposit_at: string|null, deposit_total: float, withdraw_total: float, deposit_completed_total: float, withdraw_completed_total: float}>, error?: string}
     */
    public function getUserDepositWithdrawTotals(array $options = []): array
    {
        $client = $this->getClient();
        if (!$client) {
            return ['success' => false, 'error' => 'OpenSearch disabled'];
        }

        $size = (int) ($options['size'] ?? 10000);
        $depositIndex = $this->getIndexForEvent('deposit_completed');
        $withdrawIndex = $this->getIndexForEvent('withdraw_completed');

        $query = $this->buildUserDepositWithdrawFilters($options);

        $depositAggs = [
            'by_user' => [
                'terms' => [
                    'field' => 'user_id',
                    'size' => $size,
                ],
                'aggs' => [
                    'total' => ['sum' => ['field' => 'amount']],
                    'completed' => [
                        'filter' => ['term' => ['event_type' => 'deposit_completed']],
                        'aggs' => ['sum_amount' => ['sum' => ['field' => 'amount']]],
                    ],
                ],
            ],
        ];

        $withdrawAggs = [
            'by_user' => [
                'terms' => [
                    'field' => 'user_id',
                    'size' => $size,
                ],
                'aggs' => [
                    'total' => ['sum' => ['field' => 'amount']],
                    'completed' => [
                        'filter' => ['term' => ['event_type' => 'withdraw_completed']],
                        'aggs' => ['sum_amount' => ['sum' => ['field' => 'amount']]],
                    ],
                ],
            ],
        ];

        $depositResult = $this->search($depositIndex, $query, ['size' => 0, 'aggs' => $depositAggs]);
        $withdrawResult = $this->search($withdrawIndex, $query, ['size' => 0, 'aggs' => $withdrawAggs]);

        if (!$depositResult['success']) {
            return ['success' => false, 'error' => $depositResult['error'] ?? 'Deposit aggregation failed'];
        }
        if (!$withdrawResult['success']) {
            return ['success' => false, 'error' => $withdrawResult['error'] ?? 'Withdraw aggregation failed'];
        }

        $depositBuckets = $depositResult['aggregations']['by_user']['buckets'] ?? [];
        $withdrawBuckets = $withdrawResult['aggregations']['by_user']['buckets'] ?? [];

        $users = [];
        foreach ($depositBuckets as $b) {
            $userId = (int) $b['key'];
            $completed = $b['completed'] ?? [];
            $users[$userId] = [
                'user_id' => $userId,
                'deposit_total' => (float) ($b['total']['value'] ?? 0),
                'deposit_completed_total' => (float) ($completed['sum_amount']['value'] ?? 0),
                'withdraw_total' => 0.0,
                'withdraw_completed_total' => 0.0,
            ];
        }
        foreach ($withdrawBuckets as $b) {
            $userId = (int) $b['key'];
            if (!isset($users[$userId])) {
                $users[$userId] = [
                    'user_id' => $userId,
                    'deposit_total' => 0.0,
                    'deposit_completed_total' => 0.0,
                    'withdraw_total' => 0.0,
                    'withdraw_completed_total' => 0.0,
                ];
            }
            $users[$userId]['withdraw_total'] = (float) ($b['total']['value'] ?? 0);
            $users[$userId]['withdraw_completed_total'] = (float) ($b['completed']['sum_amount']['value'] ?? 0);
        }

        $data = array_values($users);
        usort($data, fn ($a, $b) => $a['user_id'] <=> $b['user_id']);

        $userIds = array_column($data, 'user_id');

        // 从数据库取各用户首次/末次成功充值时间（与请求时间范围无关）
        $depositTimes = Deposit::query()
            ->whereIn('user_id', $userIds)
            ->whereNotNull('completed_at')
            ->selectRaw('user_id, MIN(completed_at) as first_deposit_at, MAX(completed_at) as last_deposit_at')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        // 批量拉取用户信息并合并
        $usersMap = User::whereIn('id', $userIds)->get()->keyBy('id');
        foreach ($data as &$row) {
            $user = $usersMap->get($row['user_id']);
            $row['user'] = $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => $user->status,
            ] : null;
            $times = $depositTimes->get($row['user_id']);
            $row['first_deposit_at'] = $times && $times->first_deposit_at ? Carbon::parse($times->first_deposit_at)->format('Y-m-d H:i:s') : null;
            $row['last_deposit_at'] = $times && $times->last_deposit_at ? Carbon::parse($times->last_deposit_at)->format('Y-m-d H:i:s') : null;
        }
        unset($row);

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * 按日统计（按时区聚合）
     * 过滤条件：agent_id, agent_link_id, date_from, date_to；聚合按给定 timezone 的日期分桶。
     *
     * @param  array  $options  timezone(必填), date_from, date_to, agent_id, agent_link_id
     * @return array{success: bool, data?: array<int, array{date: string, dau: int, registered_count: int, first_deposit_users_count: int, registered_and_first_deposit_count: int, deposit_completed_users_count: int, deposit_created_count: int, deposit_completed_count: int, withdraw_completed_count: int, deposit_amount: float, withdraw_amount: float, registered_users_deposit_amount: float, first_deposit_users_deposit_amount: float}>, error?: string}
     */
    public function getDailyStats(array $options = []): array
    {
        $client = $this->getClient();
        if (!$client) {
            return ['success' => false, 'error' => 'OpenSearch disabled'];
        }

        $timezone = $this->normalizeTimezone($options['timezone'] ?? 'UTC');
        $filter = $this->buildUserDepositWithdrawFilters($options);
        $size = (int) ($options['size'] ?? 50000);

        // 按日分桶：使用 @timestamp，1 天间隔，按时区，空桶也返回
        $dateHistogram = [
            'date_histogram' => [
                'field' => '@timestamp',
                'calendar_interval' => '1d',
                'time_zone' => $timezone,
                'min_doc_count' => 0,
            ],
        ];

        $indexLogin = $this->getIndexForEvent('user_logged_in');
        $indexRegistered = $this->getIndexForEvent('user_registered');
        $indexFirstDeposit = $this->getIndexForEvent('first_deposit_completed');
        $indexDeposit = $this->getIndexForEvent('deposit_completed');
        $indexWithdraw = $this->getIndexForEvent('withdraw_completed');

        // 登录：每日 DAU（去重 user_id）
        $aggsLogin = ['by_day' => array_merge($dateHistogram, [
            'aggs' => ['dau' => ['cardinality' => ['field' => 'user_id']]],
        ])];
        // 注册：每日注册条数 + 当日注册 user_id 列表（用于算「今日注册用户充值金额」等）
        $aggsRegistered = ['by_day' => array_merge($dateHistogram, [
            'aggs' => [
                'registered_count' => ['value_count' => ['field' => 'user_id']],
                'user_ids' => ['terms' => ['field' => 'user_id', 'size' => $size]],
            ],
        ])];
        // 首充：每日首充用户数 + 当日首充 user_id 列表
        $aggsFirstDeposit = ['by_day' => array_merge($dateHistogram, [
            'aggs' => [
                'first_deposit_users_count' => ['cardinality' => ['field' => 'user_id']],
                'user_ids' => ['terms' => ['field' => 'user_id', 'size' => $size]],
            ],
        ])];
        // 充值：按 event_type 分 deposit_created / deposit_completed；completed 下再按 user 汇总金额（用于注册/首充用户金额）
        $aggsDeposit = ['by_day' => array_merge($dateHistogram, [
            'aggs' => [
                'deposit_created' => [
                    'filter' => ['term' => ['event_type' => 'deposit_created']],
                    'aggs' => ['users_count' => ['cardinality' => ['field' => 'user_id']]],
                ],
                'deposit_completed' => [
                    'filter' => ['term' => ['event_type' => 'deposit_completed']],
                    'aggs' => [
                        'users_count' => ['cardinality' => ['field' => 'user_id']],
                        'total_amount' => ['sum' => ['field' => 'amount']],
                        'by_user' => ['terms' => ['field' => 'user_id', 'size' => $size], 'aggs' => ['sum_amount' => ['sum' => ['field' => 'amount']]]],
                    ],
                ],
            ],
        ])];
        // 提现：仅统计 withdraw_completed，人数 + 总金额
        $aggsWithdraw = ['by_day' => array_merge($dateHistogram, [
            'aggs' => [
                'withdraw_completed' => [
                    'filter' => ['term' => ['event_type' => 'withdraw_completed']],
                    'aggs' => [
                        'users_count' => ['cardinality' => ['field' => 'user_id']],
                        'total_amount' => ['sum' => ['field' => 'amount']],
                    ],
                ],
            ],
        ])];

        $rLogin = $this->search($indexLogin, $filter, ['size' => 0, 'aggs' => $aggsLogin]);
        $rRegistered = $this->search($indexRegistered, $filter, ['size' => 0, 'aggs' => $aggsRegistered]);
        $rFirstDeposit = $this->search($indexFirstDeposit, $filter, ['size' => 0, 'aggs' => $aggsFirstDeposit]);
        $rDeposit = $this->search($indexDeposit, $filter, ['size' => 0, 'aggs' => $aggsDeposit]);
        $rWithdraw = $this->search($indexWithdraw, $filter, ['size' => 0, 'aggs' => $aggsWithdraw]);

        foreach (['rLogin' => $rLogin, 'rRegistered' => $rRegistered, 'rFirstDeposit' => $rFirstDeposit, 'rDeposit' => $rDeposit, 'rWithdraw' => $rWithdraw] as $name => $r) {
            if (!$r['success']) {
                return ['success' => false, 'error' => $r['error'] ?? $name . ' aggregation failed'];
            }
        }

        $bucketsLogin = $rLogin['aggregations']['by_day']['buckets'] ?? [];
        $bucketsRegistered = $rRegistered['aggregations']['by_day']['buckets'] ?? [];
        $bucketsFirstDeposit = $rFirstDeposit['aggregations']['by_day']['buckets'] ?? [];
        $bucketsDeposit = $rDeposit['aggregations']['by_day']['buckets'] ?? [];
        $bucketsWithdraw = $rWithdraw['aggregations']['by_day']['buckets'] ?? [];

        // 合并所有出现过的日期，排序后逐日输出
        $days = [];
        foreach ($bucketsDeposit as $b) {
            $days[$b['key_as_string']] = true;
        }
        foreach (array_merge($bucketsLogin, $bucketsRegistered, $bucketsFirstDeposit, $bucketsWithdraw) as $b) {
            $days[$b['key_as_string']] = true;
        }
        $days = array_keys($days);
        sort($days);

        $byDay = function (array $buckets): array {
            $map = [];
            foreach ($buckets as $b) {
                $map[$b['key_as_string']] = $b;
            }
            return $map;
        };

        $regMap = $byDay($bucketsRegistered);
        $firstMap = $byDay($bucketsFirstDeposit);
        $depositMap = $byDay($bucketsDeposit);
        $loginMap = $byDay($bucketsLogin);
        $withdrawMap = $byDay($bucketsWithdraw);

        $data = [];
        foreach ($days as $dateStr) {
            $regBucket = $regMap[$dateStr] ?? null;
            $firstBucket = $firstMap[$dateStr] ?? null;
            $depBucket = $depositMap[$dateStr] ?? null;
            $loginBucket = $loginMap[$dateStr] ?? null;
            $withdrawBucket = $withdrawMap[$dateStr] ?? null;

            // 当日注册用户 ID 集合（用于筛选「今日注册用户充值金额」）
            $registeredUserIds = [];
            if ($regBucket && !empty($regBucket['user_ids']['buckets'])) {
                foreach ($regBucket['user_ids']['buckets'] as $ub) {
                    $registeredUserIds[(int) $ub['key']] = true;
                }
            }
            // 当日首充用户 ID 集合（用于筛选「今日首充用户充值金额」）
            $firstDepositUserIds = [];
            if ($firstBucket && !empty($firstBucket['user_ids']['buckets'])) {
                foreach ($firstBucket['user_ids']['buckets'] as $ub) {
                    $firstDepositUserIds[(int) $ub['key']] = true;
                }
            }

            // 当日注册用户的充值金额合计；当日首充用户的充值金额合计（从 by_user 里按 uid 筛）
            $registeredUsersDepositAmount = 0.0;
            $firstDepositUsersDepositAmount = 0.0;
            if ($depBucket && !empty($depBucket['deposit_completed']['by_user']['buckets'])) {
                foreach ($depBucket['deposit_completed']['by_user']['buckets'] as $ub) {
                    $uid = (int) $ub['key'];
                    $sum = (float) ($ub['sum_amount']['value'] ?? 0);
                    if (isset($registeredUserIds[$uid])) {
                        $registeredUsersDepositAmount += $sum;
                    }
                    if (isset($firstDepositUserIds[$uid])) {
                        $firstDepositUsersDepositAmount += $sum;
                    }
                }
            }

            // 当日注册且当日首充的人数
            $registeredAndFirstDepositCount = count(array_intersect_key($registeredUserIds, $firstDepositUserIds));

            $data[] = [
                'date' => $dateStr,
                'dau' => (int) ($loginBucket['dau']['value'] ?? 0),
                'registered_count' => (int) ($regBucket['registered_count']['value'] ?? 0),
                'first_deposit_users_count' => (int) ($firstBucket['first_deposit_users_count']['value'] ?? 0),
                'registered_and_first_deposit_count' => $registeredAndFirstDepositCount,
                'deposit_completed_users_count' => (int) ($depBucket['deposit_completed']['users_count']['value'] ?? 0),
                'deposit_created_count' => (int) ($depBucket['deposit_created']['doc_count'] ?? 0),
                'deposit_completed_count' => (int) ($depBucket['deposit_completed']['doc_count'] ?? 0),
                'withdraw_completed_count' => (int) ($withdrawBucket['withdraw_completed']['doc_count'] ?? 0),
                'deposit_amount' => round((float) ($depBucket['deposit_completed']['total_amount']['value'] ?? 0), 2),
                'withdraw_amount' => round((float) ($withdrawBucket['withdraw_completed']['total_amount']['value'] ?? 0), 2),
                'registered_users_deposit_amount' => round($registeredUsersDepositAmount, 2),
                'first_deposit_users_deposit_amount' => round($firstDepositUsersDepositAmount, 2),
            ];
        }

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * 应用配置中的 index 模版
     *
     * @param  string|null  $onlyName  仅应用指定名称的模版，null 则应用全部
     * @return array{success: bool, applied: array, errors: array}
     */
    public function applyIndexTemplates(?string $onlyName = null): array
    {
        $client = $this->getClient();
        if (!$client) {
            return ['success' => false, 'applied' => [], 'errors' => ['OpenSearch disabled']];
        }

        $templates = config('opensearch.index_templates', []);
        if ($onlyName !== null) {
            $templates = isset($templates[$onlyName]) ? [$onlyName => $templates[$onlyName]] : [];
        }
        $applied = [];
        $errors = [];

        foreach ($templates as $name => $config) {
            $patterns = $config['index_patterns'] ?? [];
            $template = $config['template'] ?? [];

            if (empty($patterns) || empty($template)) {
                $errors[] = "Template {$name}: missing index_patterns or template";
                continue;
            }

            $fullPatterns = array_map(fn ($p) => $this->getIndexName($p), $patterns);
            $result = $this->putIndexTemplate($name, $fullPatterns, $template);

            if ($result['success']) {
                $applied[] = $name;
            } else {
                $errors[] = "Template {$name}: " . ($result['error'] ?? 'unknown');
            }
        }

        return [
            'success' => empty($errors),
            'applied' => $applied,
            'errors' => $errors,
        ];
    }

}
