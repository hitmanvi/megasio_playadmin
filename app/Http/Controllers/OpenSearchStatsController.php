<?php

namespace App\Http\Controllers;

use App\Enums\Err;
use App\Services\OpenSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * OpenSearch 统计接口
 * 从 OpenSearch 获取用户充提等聚合数据
 */
class OpenSearchStatsController extends Controller
{
    /**
     * 获取用户充提金额汇总
     * 每个用户一条数据：充值总额、提现总额、成功充值总额、成功提现总额
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function userDepositWithdrawTotals(Request $request): JsonResponse
    {
        $service = new OpenSearchService();

        if (!$service->isEnabled()) {
            return $this->error(Err::ERROR, 'OpenSearch is not enabled');
        }

        if (!$service->ping()) {
            return $this->error(Err::ERROR, 'OpenSearch connection failed');
        }

        $options = [];
        if ($request->filled('uid')) {
            $options['uid'] = $request->input('uid');
        }
        if ($request->filled('date_from')) {
            $options['date_from'] = $request->input('date_from');
        }
        if ($request->filled('date_to')) {
            $options['date_to'] = $request->input('date_to');
        }
        if ($request->has('agent_id')) {
            $options['agent_id'] = $request->input('agent_id');
        }
        if ($request->has('agent_link_id')) {
            $options['agent_link_id'] = $request->input('agent_link_id');
        }

        $result = $service->getUserDepositWithdrawTotals($options);

        if (!$result['success']) {
            return $this->error(Err::ERROR, $result['error'] ?? 'Failed to fetch stats');
        }

        return $this->responseItem($result['data'] ?? []);
    }

    /**
     * 按日统计（按时区聚合）
     * 过滤：agent_id, agent_link_id, date_from, date_to；按 timezone 的日期分桶。
     */
    public function dailyStats(Request $request): JsonResponse
    {
        $request->validate([
            'timezone' => 'required|string|max:64',
            'date_from' => 'nullable|string|date',
            'date_to' => 'nullable|string|date',
            'agent_id' => 'nullable|integer',
            'agent_link_id' => 'nullable|integer',
        ]);

        $service = new OpenSearchService();

        if (!$service->isEnabled()) {
            return $this->error(Err::ERROR, 'OpenSearch is not enabled');
        }

        if (!$service->ping()) {
            return $this->error(Err::ERROR, 'OpenSearch connection failed');
        }

        $options = ['timezone' => $request->input('timezone')];
        if ($request->filled('date_from')) {
            $options['date_from'] = $request->input('date_from');
        }
        if ($request->filled('date_to')) {
            $options['date_to'] = $request->input('date_to');
        }
        if ($request->has('agent_id')) {
            $options['agent_id'] = $request->input('agent_id');
        }
        if ($request->has('agent_link_id')) {
            $options['agent_link_id'] = $request->input('agent_link_id');
        }

        $result = $service->getDailyStats($options);

        if (!$result['success']) {
            return $this->error(Err::ERROR, $result['error'] ?? 'Failed to fetch daily stats');
        }

        return $this->responseItem($result['data'] ?? []);
    }
}
