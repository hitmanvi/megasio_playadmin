<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SopayService
{
    const SOPAY_STATUS_PREPARING    = 0; // 准备中
	const SOPAY_STATUS_PAYING       = 1; // 支付中
	const SOPAY_STATUS_CONFIRMING   = 2; // 确认中
	const SOPAY_STATUS_SUCCEED      = 3; // 已完成
	const SOPAY_STATUS_FAILED       = 4; // 失败
	const SOPAY_STATUS_EXPIRED      = 5; // 已过期
	const SOPAY_STATUS_DELAYED      = 6; // 延迟支付
	const SOPAY_STATUS_INSUFFICIENT = 7; // 已支付 但是金额小于订单金额
	const SOPAY_STATUS_REJECT       = 8; // 订单已拒绝


    protected $endpoint;
    protected $appKey;
    protected $appId;
    protected $callbackUrl;
    protected $returnUrl;
    public function __construct()
    {
        $this->endpoint    = config('services.sopay.endpoint');
        $this->appId       = config('services.sopay.app_id');
        $this->appKey      = config('services.sopay.app_key');
        $this->callbackUrl = config('services.sopay.callback_url');
        $this->returnUrl   = config('services.sopay.return_url');
    }

    public function getPayments()
    {
        $params = ['method'=> 'payments'];
        $params = $this->sign($params);
        
        $url    = $this->endpoint . '/api/payments/list';
        $resp   = Http::get($url, $params);
        $res    = $resp->json();

        return $res;
    }

    public function getCoins()
    {
        $params = ['method'=> 'coins'];
        $params = $this->sign($params);
        $url    = $this->endpoint . '/api/coins';
        $resp   = Http::get($url, $params);
        $res    = $resp->json();
        return $res;
    }


    public function withdraw(array $withdrawData, array $extraInfo,  int $type,  int $paymentId)
    {
        $params = [
            'amount'       => $withdrawData['amount'],
            'type'         => $type, 
            'symbol'       => $withdrawData['symbol'],
            'coin_type'    => $withdrawData['coin_type'],
            'subject'      => 'withdraw',
            'out_trade_no' => $withdrawData['out_trade_no'],
            'user_ip'      => $withdrawData['user_ip'],
            'callback_url' => $this->callbackUrl,
            'return_url'   => $this->returnUrl,
            'method'       => 'withdraw',
        ];
        if ($type == 2) {
            $params['payment_id'] = (int) $paymentId;
            if(!empty($withdrawData['channel_id'])) $params['channel_id'] = (int) $withdrawData['channel_id'];
        }
        foreach ($extraInfo as $key => $value) {
            $params[$key] = trim($value);
        }
        $params = $this->sign($params);
        $url    = $this->endpoint . '/api/orders/withdraw';
        $resp   = Http::post($url, $params);
        $res    = $resp->json();
        Log::error('sopay withdraw: ', $res);
        return $res;
    }

    private function sign($params)
    {
        $params['app_id']    = $this->appId;
        $params['timestamp'] = time();
        ksort($params);
        $presign        = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $presign        = md5($presign);
        $sign           = hash_hmac('sha256', $presign, $this->appKey);
        $params['sign'] = $sign;
        return $params;
    }
}
