<?php


namespace App\Services\Web3;


use App\Constant\PayTypeConstant;
use App\Constant\Web3TagConstant;
use App\Daos\CurrencyUsdDao;
use App\Daos\Web3Dao;
use App\Exceptions\CreateServiceException;
use App\Models\GroupSpaceChatRecordModel;
use App\Models\GroupSpaceUserWalletModel;
use App\Models\Web3\ChainTypeCurrencyModel;
use App\Models\Web3\ChainTypeModel;
use App\Models\Web3\CurrencyPriceModel;
use App\Models\Web3\CurrentModel;
use App\Models\Web3\GroupModel;
use App\Models\Web3\GroupTagsModel;
use App\Models\Web3\OrderLogsModel;
use App\Models\Web3\OrdersModel;
use App\Models\Web3\TagsModel;
use App\Models\Web3\Web3TransferRecordModel;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Web3Service extends BaseService
{

    /**
     * @var GroupModel
     */
    public $model;

    /**
     * Web3Repository constructor.
     * @param GroupModel $groupModel
     */
    public function __construct(GroupModel $groupModel)
    {
        $this->model = $groupModel;
    }

    /**
     * 获取群详情
     * @param $groupId
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model|null
     */
    public function getGroupById($groupId, $toWei = true)
    {
        $res = $this->model->newQuery()->find($groupId);
        if ($res && $toWei) {
            $res->amount_to_wei = CurrencyService::format($res->currency_name, $res->amount);
        }
        return $res;
    }


    /**
     * 获取钱包获取链接的个数
     * @param $groupId
     * @param $address
     * @return int
     */
    public function getAddressOrderInfoCount($groupId, $address)
    {
        return OrdersModel::query()
            ->where('group_id', $groupId)
            ->where('payment_account', $address)
            ->where('status', 2)
            ->count();
    }

    /**
     * 获取我的订单
     * @param $userId
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAddressOrderInfo($groupId, $address)
    {
        $res = OrdersModel::query()
            ->where('group_id', $groupId)
            ->where('payment_account', $address)
            ->where('status', 2)
            ->select('group_id', 'tx_hash', 'ship', 'created_at')
            ->orderBy('id', 'desc')
            ->first();
        if ($res) {
            $res->ship = json_decode($res->ship, true);
            $res->timestamp = strtotime($res->created_at);
            unset($res->created_at);
        }
        return $res;
    }

}
