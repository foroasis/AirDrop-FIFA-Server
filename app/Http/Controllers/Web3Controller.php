<?php


namespace App\Http\Controllers\Api;


use App\Constant\PayTypeConstant;
use App\Daos\Web3Dao;
use App\Exceptions\CreateServiceException;
use App\Exceptions\InvalidParamsException;
use App\Exceptions\Web3OrderException;
use App\Http\Requests\CreateGroupResultRequest;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\CreateTransferRequest;
use App\Models\Web3\ChainButtonModel;
use App\Models\Web3\ChainTypeCurrencyModel;
use App\Models\Web3\ChainTypeModel;
use App\Models\Web3\CurrencyPriceModel;
use App\Models\Web3\CurrentModel;
use App\Models\Web3\GroupModel;
use App\Models\Web3\OrderLogsModel;
use App\Models\Web3\TokenTypeModel;
use App\Models\Web3\TransactionCenterModel;
use App\Models\Web3\WalletTypeModel;
use App\Services\TgChatService;
use App\Services\Web3\BaseService;
use App\Services\Web3\Web3Service;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as FoundationResponse;

class Web3Controller extends BaseController
{

    /**
     * @var Request
     */
    public $request;

    /**
     * @var Web3Service
     */
    public $service;

    /**
     * AppController constructor.
     * @param Request $request
     */
    public function __construct(Request $request, Web3Service $web3Service)
    {
        $this->service = $web3Service;
        $this->request = $request;
    }


    /**
     * 获取钱包的相关配置
     * @return \Illuminate\Http\JsonResponse
     */
    public function config(Request $request)
    {
        //1.6.2版本的取全新的数据
        $version = $request->get('version', '');
        if (version_compare($version, '1.6.2') < 0) {
            $cache = Web3Dao::getWeb3Config();
            if ($cache) {
                $res = json_decode($cache, true);
                return $this->success($res);
            }
        }

        //缓存
        $cache = Web3Dao::getWeb3Config162();
        if ($cache) {
            $res = json_decode($cache, true);
            return $this->success($res);
        }

        $walletType = WalletTypeModel::query()
            ->where('status', 1)
            ->select('id', 'name')
            ->get();

        $chainType = ChainTypeModel::query()
            ->where('status', 1)
            ->orderBy('sort')
            ->select('id', 'explorer_url', 'name', 'icon', 'rpc_url', 'main_currency_id', 'main_currency_name')
            ->get();

        $tokenType = TokenTypeModel::query()
            ->where('status', 1)
            ->select('id', 'name')
            ->get();

        $currency = CurrentModel::query()
            ->where('status', 1)
            ->get()
            ->keyBy('id');

        //获取链对应的币数据
        $currencyIds = $currency->pluck('id')->toArray();
        $chainIDs = $chainType->pluck('id')->toArray();
        $chainTypeCurrency = ChainTypeCurrencyModel::query()
            ->whereIn('chain_id', $chainIDs)
            ->whereIn('currency_id', $currencyIds)
            ->get();

        //获取链对应的按钮配置数据
        $chainButton = ChainButtonModel::query()
            ->whereIn('chain_id', $chainIDs)
            ->get();
        foreach ($chainType as $key => $value) {

            //获取按钮
            $chainType[$key]->button = $chainButton
                ->where('chain_id', $value->id)
                ->where('status', 1)
                ->sortBy('sort')
                ->values();

            //获取币
            $chainType[$key]->currency = $chainTypeCurrency
                ->where('chain_id', $value->id)
                ->sortBy('sort')
                ->map(function ($item) use ($currency) {
                    //获取对应的币的基本数据
                    $tempCurrency = $currency[$item->currency_id];
                    return [
                        'id' => $tempCurrency->id,
                        'coin_id' => $item->coin_id,
                        'decimal' => $tempCurrency->decimal,
                        'name' => $tempCurrency->name,
                        'is_main_currency' => $item->is_main_currency,
                        'icon' => $tempCurrency->icon,
                    ];
                })->values();
        }

        //social tokens
        $socialTokens = config('wallet.social_tokens');

        $res = compact('walletType', 'chainType', 'tokenType', 'socialTokens');

        //设置缓存
        Web3Dao::setWeb3Config162($res);
        return $this->success($res);
    }


    /**
     * 创建订单
     * @param CreateOrderRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws CreateServiceException
     */
    public function createOrder(CreateOrderRequest $request)
    {
        $txHash = $this->request->get('tx_hash');
        $groupId = $this->request->get('group_id');
        $platform = $this->request->get('platform', 0);

        if ($platform == config('game.platform.group_space')) {
            $userId = 0;
        } else {
            $userId = Auth::id();
        }
        //查询订单是否存在，不存在 则生成
        $orderInfo = $this->service->getOrderInfo($userId, $txHash);
        if (empty($orderInfo)) {
            $orderInfo = $this->service->makeOrder($request, $groupId, $txHash, $userId, $platform);
            if (empty($orderInfo)) {
                throw new CreateServiceException(__("exception.create_order_error"));
            }
        }
        //投放队列
        $queueStartTime = time() + 20;
        $nums = 1;
        $this->getQueueService()->doEthHash($txHash, $queueStartTime, $nums);
        return $this->success($orderInfo);
    }


    /**
     * 查询订单结果
     * @return \Illuminate\Http\JsonResponse
     * @throws CreateServiceException
     * @throws InvalidParamsException
     */
    public function orderResult()
    {
        $txHash = $this->request->get('tx_hash');
        if (!$txHash) {
            throw new InvalidParamsException(__('exception.data_param_error'));
        }
        $platform = $this->request->get('platform', 0);
        if ($platform == config('game.platform.group_space')) {
            $userId = 0;
        } else {
            $userId = Auth::id();
        }
        $orderInfo = $this->service->getOrderInfo($userId, $txHash);
        if (empty($orderInfo)) {
            throw new CreateServiceException(__("exception.order_not_exist"));
        }
        return $this->success($orderInfo);
    }


    /**
     * 资格入群
     * @return \Illuminate\Http\JsonResponse
     * @throws CreateServiceException
     * @throws InvalidParamsException
     * @throws Web3OrderException
     */
    public function accreditGroup()
    {
        $groupId = $this->request->get('group_id');
        if (empty($groupId)) {
            throw new InvalidParamsException(__('exception.data_param_error'));
        }


        //判断群是否是资格审核的群
        $groupInfo = $this->service->getGroupById($groupId, false);
        if (empty($groupInfo)) {
            throw new CreateServiceException(__("exception.group_not_exist"));
        }

        //判断是否重新获取链接
        $is_reacquire = $this->request->get('is_reacquire', 0);
        if ($groupInfo->join_type == 3 && empty($is_reacquire)) {
            throw new InvalidParamsException(__('exception.data_param_error'));
        }

        if ($is_reacquire && $groupInfo->join_type != 1) {
            $paymentAccount = $this->request->get('payment_account', '');
            if (empty($paymentAccount)) {
                throw new InvalidParamsException(__('exception.data_param_error'));
            }
            $count = $this->service->getAddressOrderInfoCount($groupId, $paymentAccount);
            if ($count >= 5) {
                return $this->unprocessable("", $this->groupLinkExceed);
            }
        }

        //判断是否是资格入群
        if ($groupInfo->join_type == 2) {

            //账户校验
            $paymentAccount = $this->request->get('payment_account', '');
            if (empty($paymentAccount)) {
                throw new InvalidParamsException(__('exception.data_param_error'));
            }

            //校验是否已经购买过
            if ($groupInfo->platform == 5) {
                $userOrderInfo = $this->service->getAddressOrderInfo($groupId, $paymentAccount);
                if (!empty($userOrderInfo) && empty($is_reacquire)) {
                    throw new InvalidParamsException(__('exception.order_is_exist'));
                }
            }

            //判断对应的链
            $baseService = new BaseService();
            $service = $baseService->getServiceByChainId($groupInfo['chain_id']);
            if ($service === false) {
                throw new InvalidParamsException(__('exception.data_param_error'));
            }

            //判断协议
            if ($groupInfo->token_name == 'ERC20') {
                $balance = $service->getBalance($paymentAccount, $groupInfo->currency_name);
                Log::info("getBalance", [$balance, $groupInfo->amount]);
                if (bccomp($balance, $groupInfo->amount, 18) === -1) {
                    return $this->unprocessable(__("exception.balance_error"), FoundationResponse::HTTP_UNPROCESSABLE_ENTITY);
                }
            } elseif ($groupInfo->token_name == 'ERC721') {
                $balance = $service->getErc721Balance($paymentAccount, $groupInfo->token_address);

                Log::info("getBalance", [$paymentAccount, $groupInfo->token_address, $balance]);
                if (empty($balance)) {
                    return $this->unprocessable(__("exception.balance_error"), FoundationResponse::HTTP_UNPROCESSABLE_ENTITY);
                }
            } elseif ($groupInfo->token_name == 'ERC1155') {
                if ($groupInfo->nft_token_id == 'all' || empty($groupInfo->nft_token_id)) {
                    $nft_token_id = $this->request->get('nft_token_id');
                    if (empty($nft_token_id)) {
                        throw new InvalidParamsException(__('exception.data_param_error'));
                    }
                } else {
                    $nft_token_id = $groupInfo->nft_token_id;
                }

                $balance = $service->getErc1155Balance($paymentAccount, $groupInfo->token_address, $nft_token_id);
                Log::info("getErc1155Balance", [$paymentAccount, $groupInfo->token_address, $nft_token_id, $balance]);
                if (empty($balance)) {
                    return $this->unprocessable(__("exception.balance_error"), FoundationResponse::HTTP_UNPROCESSABLE_ENTITY);
                }
            }
        }

        try {

            //判断是否是免费的 并且存在链接的
            if ($groupInfo->join_type == 1 && !empty($groupInfo->chat_link)) {
                $inviteInfo['invite_link'] = $groupInfo->chat_link;
            } else {
                $inviteInfo = $this->getTelegramService()->newInviteLink(null, $groupInfo->chat_id, 1, strtotime('+1 hour'));
                if (!$inviteInfo) {
                    Log::info("generate_link_error", [$inviteInfo]);
                    throw new CreateServiceException(__("exception.generate_link_error"));
                }
            }

        } catch (\Exception $exception) {

            Log::info("generate_link_error", [$exception->getMessage()]);
            return $this->unprocessable(__("exception.generate_link_error"), $this->tgChatLinkNotFindStatusCode);
        }

        //创建订单
        $platform = $this->request->get('platform', 0);
        if ($platform == config('game.platform.group_space')) {
            $userId = 0;
        } else {
            $userId = Auth::id();
        }
        $orderInfo = $this->service->makeOrder($this->request, $groupId, '', $userId, $platform);

        // 记录商品信息并完成发货
        $orderInfo->amount_balance = $balance ?? 0;
        $orderInfo->status = PayTypeConstant::ORDER_STATUS_SHIP;
        $ship = $orderInfo['ship'];
        $ship['url'] = $inviteInfo['invite_link'];
        $orderInfo->ship = $ship;
        $orderInfo->save();

        // 店铺+1
        $groupInfo->ship += 1;
        $groupInfo->save();

        //记录日志
        OrderLogsModel::query()->create(['order_id' => $orderInfo->id, 'info' => '订单发货成功：' . $inviteInfo['invite_link']]);

        return $this->success($orderInfo);
    }

    /**
     * @return TelegramService
     */
    private function getTelegramService()
    {
        return app(TelegramService::class);
    }


}
