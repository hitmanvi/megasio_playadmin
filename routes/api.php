<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\GameGroupController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderArchiveController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\PendingReviewSummaryController;
use App\Http\Controllers\PaymentMethodFieldConfigController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\GameCategoryController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WithdrawController;
use App\Http\Controllers\UserActivityController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InvitationRewardController;
use App\Http\Controllers\UserMetaController;
use App\Http\Controllers\UserPaymentExtraInfoController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SiteLinkController;
use App\Http\Controllers\BonusTaskController;
use App\Http\Controllers\WeeklyCashbackController;
use App\Http\Controllers\AirdropController;
use App\Http\Controllers\PromotionCodeController;
use App\Http\Controllers\PromotionCodeClaimController;
use App\Http\Controllers\BundleController;
use App\Http\Controllers\BundlePurchaseController;
use App\Http\Controllers\RedeemController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\BlacklistController;
use App\Http\Controllers\UserTagLogController;
use App\Http\Controllers\VipLevelController;
use App\Http\Controllers\VipLevelGroupController;
use App\Http\Controllers\ArticleGroupController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\AgentLinkController;
use App\Http\Controllers\SiteConfigController;
use App\Http\Controllers\OpenSearchStatsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('login', [AdminAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AdminAuthController::class, 'logout']);
    Route::get('mine', [AdminAuthController::class, 'mine']);
    Route::get('pending-review-summary', PendingReviewSummaryController::class);
    Route::get('site-config', [SiteConfigController::class, 'index']);
    Route::put('password', [AdminAuthController::class, 'updatePassword']);
    Route::post('two-factor/bind', [AdminAuthController::class, 'bindTwoFactor']);
    Route::post('two-factor/bind/confirm', [AdminAuthController::class, 'bindTwoFactorConfirm']);
    Route::post('two-factor/rebind', [AdminAuthController::class, 'rebindTwoFactor']);
    Route::post('two-factor/rebind/confirm', [AdminAuthController::class, 'rebindTwoFactorConfirm']);
    
    // Themes routes
    Route::get('themes', [ThemeController::class, 'index']);
    Route::post('themes', [ThemeController::class, 'store']);
    Route::get('themes/{theme}', [ThemeController::class, 'show']);
    Route::put('themes/{theme}', [ThemeController::class, 'update']);
    
    // Game categories routes
    Route::get('game-categories', [GameCategoryController::class, 'index']);
    Route::post('game-categories', [GameCategoryController::class, 'store']);
    Route::get('game-categories/{gameCategory}', [GameCategoryController::class, 'show']);
    Route::put('game-categories/{gameCategory}', [GameCategoryController::class, 'update']);
    
    // Brands routes
    Route::get('brands', [BrandController::class, 'index']);
    Route::get('brands/options', [BrandController::class, 'options']);
    Route::get('brands/{brand}', [BrandController::class, 'show']);
    Route::post('brands', [BrandController::class, 'store']);
    Route::put('brands/{brand}', [BrandController::class, 'update']);
    Route::delete('brands/{brand}', [BrandController::class, 'destroy']);
    
           // Brand details routes
           Route::post('brands/{brand}/details', [BrandController::class, 'storeDetail']);
           Route::put('brands/{brand}/details/{detail}', [BrandController::class, 'updateDetail']);
           Route::delete('brands/{brand}/details/{detail}', [BrandController::class, 'destroyDetail']);

           // Upload routes
           Route::post('upload', [UploadController::class, 'file']);

           // Games routes
           Route::get('games', [GameController::class, 'index']);
           Route::get('games/{game}', [GameController::class, 'show']);
           Route::put('games/{game}', [GameController::class, 'update']);
            
           // Banners routes
           Route::get('banners', [BannerController::class, 'index']);
           Route::post('banners', [BannerController::class, 'store']);
           Route::get('banners/{banner}', [BannerController::class, 'show']);
           Route::put('banners/{banner}', [BannerController::class, 'update']);
           Route::delete('banners/{banner}', [BannerController::class, 'destroy']);
           
           // Game groups routes
           Route::get('game-groups', [GameGroupController::class, 'index']);
           Route::post('game-groups', [GameGroupController::class, 'store']);
           Route::get('game-groups/{gameGroup}', [GameGroupController::class, 'show']);
           Route::put('game-groups/{gameGroup}', [GameGroupController::class, 'update']);
           Route::delete('game-groups/{gameGroup}', [GameGroupController::class, 'destroy']);
           
           // Game group games routes
           Route::post('game-groups/{gameGroup}/games', [GameGroupController::class, 'attachGame']);
           Route::post('game-groups/{gameGroup}/games/attach', [GameGroupController::class, 'attachGames']);
           Route::delete('game-groups/{gameGroup}/games/{game}', [GameGroupController::class, 'detachGame']);
           Route::post('game-groups/{gameGroup}/games/detach', [GameGroupController::class, 'detachGames']);
           Route::put('game-groups/{gameGroup}/games/order', [GameGroupController::class, 'updateGameOrder']);
           
           // Orders routes
           Route::get('orders', [OrderController::class, 'index']);
           Route::get('order-archives', [OrderArchiveController::class, 'index']);
           
           // Payment methods routes
           Route::get('payment-methods', [PaymentMethodController::class, 'index']);
           Route::post('payment-methods', [PaymentMethodController::class, 'store']);
           Route::get('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'show']);
           Route::put('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update']);
           Route::post('payment-methods/sync', [PaymentMethodController::class, 'sync']);

           Route::get('payment-method-field-configs', [PaymentMethodFieldConfigController::class, 'index']);
           Route::post('payment-method-field-configs', [PaymentMethodFieldConfigController::class, 'store']);
           Route::get('payment-method-field-configs/{paymentMethodFieldConfig}', [PaymentMethodFieldConfigController::class, 'show']);
           Route::put('payment-method-field-configs/{paymentMethodFieldConfig}', [PaymentMethodFieldConfigController::class, 'update']);
           Route::delete('payment-method-field-configs/{paymentMethodFieldConfig}', [PaymentMethodFieldConfigController::class, 'destroy']);
           
           // Deposits routes
           Route::get('deposits', [DepositController::class, 'index']);
           Route::post('deposits/{deposit}/resolve', [DepositController::class, 'resolve']);

           // Transactions routes
           Route::get('transactions/types', [TransactionController::class, 'types']);
           Route::get('transactions', [TransactionController::class, 'index']);
           
           // Withdraws routes
           Route::get('withdraws', [WithdrawController::class, 'index']);
           Route::get('withdraws/counts', [WithdrawController::class, 'counts']);
           Route::post('withdraws/{withdraw}/pass', [WithdrawController::class, 'pass']);
           Route::post('withdraws/{withdraw}/reject', [WithdrawController::class, 'reject']);
           
           // User activities routes
           Route::get('user-activities', [UserActivityController::class, 'index']);
           
           // Users routes
           Route::get('users', [UserController::class, 'index']);
           Route::get('users/{uid}', [UserController::class, 'show']);
           Route::put('users/{user}', [UserController::class, 'update']);
           Route::get('users/{uid}/invitation-rewards/stats', [InvitationRewardController::class, 'stats']);
           Route::get('users/{uid}/vip-bonus-stats', [UserController::class, 'vipBonusStats']);
           Route::get('users/{uid}/invitation-rewards/aggregates', [InvitationRewardController::class, 'aggregatesByInvitation']);
           Route::get('users/{uid}/metas', [UserMetaController::class, 'show']);
           Route::post('users/{user}/ban', [UserController::class, 'ban']);
           Route::post('users/{user}/unban', [UserController::class, 'unban']);
           Route::get('user-payment-extra-infos', [UserPaymentExtraInfoController::class, 'index']);
           Route::put('user-payment-extra-infos/{userPaymentExtraInfo}', [UserPaymentExtraInfoController::class, 'update']);
           Route::delete('user-payment-extra-infos/{userPaymentExtraInfo}', [UserPaymentExtraInfoController::class, 'destroy']);
           Route::get('bonus-tasks/stats', [BonusTaskController::class, 'stats']);
           Route::get('bonus-tasks', [BonusTaskController::class, 'index']);
           Route::get('weekly-cashbacks/stats', [WeeklyCashbackController::class, 'stats']);
           Route::get('weekly-cashbacks', [WeeklyCashbackController::class, 'index']);
           Route::get('airdrops', [AirdropController::class, 'index']);
           Route::post('airdrops', [AirdropController::class, 'store']);
           Route::get('promotion-codes/options/type', [PromotionCodeController::class, 'typeOptions']);
           Route::get('promotion-codes/options/status', [PromotionCodeController::class, 'statusOptions']);
           Route::get('promotion-codes/code-exists', [PromotionCodeController::class, 'codeExists']);
           Route::get('promotion-codes', [PromotionCodeController::class, 'index']);
           Route::post('promotion-codes', [PromotionCodeController::class, 'store']);
           Route::get('promotion-codes/{promotionCode}', [PromotionCodeController::class, 'show']);
           Route::put('promotion-codes/{promotionCode}', [PromotionCodeController::class, 'update']);
           Route::get('promotion-code-claims', [PromotionCodeClaimController::class, 'index']);
           Route::delete('promotion-code-claims/{promotionCodeClaim}', [PromotionCodeClaimController::class, 'destroy']);
           
           // Currencies routes
           Route::get('currencies', [CurrencyController::class, 'index']);
           Route::post('currencies', [CurrencyController::class, 'store']);
           Route::get('currencies/{currency}', [CurrencyController::class, 'show']);
           Route::put('currencies/{currency}', [CurrencyController::class, 'update']);
           Route::delete('currencies/{currency}', [CurrencyController::class, 'destroy']);
           
           // KYCs routes
           Route::get('kycs', [KycController::class, 'index']);
           Route::post('kycs/{kyc}/approve', [KycController::class, 'approve']);
           Route::post('kycs/{kyc}/reject', [KycController::class, 'reject']);
           
           // Settings routes
           Route::get('settings', [SettingController::class, 'index']);
           Route::post('settings', [SettingController::class, 'store']);
           Route::post('settings/batch', [SettingController::class, 'batchUpdate']);
           Route::get('settings/{setting}', [SettingController::class, 'show']);
           Route::put('settings/{setting}', [SettingController::class, 'update']);
           Route::delete('settings/{setting}', [SettingController::class, 'destroy']);

           Route::get('site-links', [SiteLinkController::class, 'index']);
           Route::post('site-links', [SiteLinkController::class, 'store']);
           Route::get('site-links/{site_link}', [SiteLinkController::class, 'show']);
           Route::put('site-links/{site_link}', [SiteLinkController::class, 'update']);
           Route::delete('site-links/{site_link}', [SiteLinkController::class, 'destroy']);
           
           // Bundles routes
           Route::get('bundles', [BundleController::class, 'index']);
           Route::post('bundles', [BundleController::class, 'store']);
           Route::get('bundles/{bundle}', [BundleController::class, 'show']);
           Route::put('bundles/{bundle}', [BundleController::class, 'update']);
           Route::delete('bundles/{bundle}', [BundleController::class, 'destroy']);
           
           // Bundle purchases routes
           Route::get('bundle-purchases', [BundlePurchaseController::class, 'index']);
           
           // Redeems routes
           Route::get('redeems', [RedeemController::class, 'index']);
           Route::post('redeems/{redeem}/pass', [RedeemController::class, 'pass']);
           Route::post('redeems/{redeem}/reject', [RedeemController::class, 'reject']);
           
           // Tags routes
           Route::get('tags', [TagController::class, 'index']);
           Route::post('tags', [TagController::class, 'store']);
           Route::get('tags/{tag}', [TagController::class, 'show']);
           Route::put('tags/{tag}', [TagController::class, 'update']);
           Route::delete('tags/{tag}', [TagController::class, 'destroy']);
           Route::get('tags/{tag}/users', [TagController::class, 'getUsers']);
           
           // User tags routes
           Route::post('users/{user}/tags/attach', [TagController::class, 'attachToUser']);
           Route::post('users/{user}/tags/detach', [TagController::class, 'detachFromUser']);
           Route::post('users/{user}/tags/sync', [TagController::class, 'syncUserTags']);
           
           // Blacklist routes
           Route::get('blacklists', [BlacklistController::class, 'index']);
           Route::post('blacklists', [BlacklistController::class, 'store']);
           Route::delete('blacklists/{blacklist}', [BlacklistController::class, 'destroy']);
           
           // User tag logs routes
           Route::get('user-tag-logs', [UserTagLogController::class, 'index']);
           
           // VIP level groups routes
           Route::get('vip-level-groups', [VipLevelGroupController::class, 'index']);
           Route::post('vip-level-groups', [VipLevelGroupController::class, 'store']);
           Route::get('vip-level-groups/{vipLevelGroup}', [VipLevelGroupController::class, 'show']);
           Route::put('vip-level-groups/{vipLevelGroup}', [VipLevelGroupController::class, 'update']);
           Route::delete('vip-level-groups/{vipLevelGroup}', [VipLevelGroupController::class, 'destroy']);
           
           // VIP levels routes
           Route::get('vip-levels', [VipLevelController::class, 'index']);
           Route::post('vip-levels/batch', [VipLevelController::class, 'batchStore']);
           Route::post('vip-levels', [VipLevelController::class, 'store']);
           Route::get('vip-levels/{vipLevel}', [VipLevelController::class, 'show']);
           Route::put('vip-levels/{vipLevel}', [VipLevelController::class, 'update']);
           Route::delete('vip-levels/{vipLevel}', [VipLevelController::class, 'destroy']);
           
           // Article groups routes
           Route::get('article-groups', [ArticleGroupController::class, 'index']);
           Route::get('article-groups/options', [ArticleGroupController::class, 'options']);
           Route::post('article-groups', [ArticleGroupController::class, 'store']);
           Route::get('article-groups/{articleGroup}', [ArticleGroupController::class, 'show']);
           Route::put('article-groups/{articleGroup}', [ArticleGroupController::class, 'update']);
           Route::delete('article-groups/{articleGroup}', [ArticleGroupController::class, 'destroy']);
           
           // Articles routes
           Route::get('articles', [ArticleController::class, 'index']);
           Route::post('articles', [ArticleController::class, 'store']);
           Route::get('articles/{article}', [ArticleController::class, 'show']);
           Route::put('articles/{article}', [ArticleController::class, 'update']);
           Route::delete('articles/{article}', [ArticleController::class, 'destroy']);
           
           // Agents routes
           Route::get('agents', [AgentController::class, 'index']);
           Route::get('agents/with-links', [AgentController::class, 'listWithLinks']);
           Route::post('agents', [AgentController::class, 'store']);
           Route::get('agents/{agent}', [AgentController::class, 'show']);
           Route::put('agents/{agent}', [AgentController::class, 'update']);
           Route::post('agents/{agent}/reset-two-factor', [AgentController::class, 'resetTwoFactor']);
           Route::delete('agents/{agent}', [AgentController::class, 'destroy']);

           // Agent links routes
           Route::get('agent-links', [AgentLinkController::class, 'index']);
           Route::post('agent-links', [AgentLinkController::class, 'store']);
           Route::get('agent-links/{agentLink}', [AgentLinkController::class, 'show']);
           Route::put('agent-links/{agentLink}', [AgentLinkController::class, 'update']);
           Route::delete('agent-links/{agentLink}', [AgentLinkController::class, 'destroy']);

           // OpenSearch 统计
           Route::get('opensearch/stats/user-deposit-withdraw-totals', [OpenSearchStatsController::class, 'userDepositWithdrawTotals']);
           Route::get('opensearch/stats/daily', [OpenSearchStatsController::class, 'dailyStats']);
       });