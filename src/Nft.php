<?php

namespace Karakushan\TonNft;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class Nft
{
    protected array $interfaces = ['nft_sale_get_gems'];

    /**
     * Возвращает историю продаж
     *
     * @param string $address
     * @return Collection
     */
    function getSalesHistory(string $address): Collection
    {

        $market_place_addresses = ['EQBYTuYbLf8INxFtD8tQeNk5ZLy-nAX9ahQbG_yl1qQ-GEMS', 'EQDrLq-X6jKZNHAScgghh0h1iog3StK71zn8dcmrOj8jPWRA'];
        $transactions = new Transactions();
        $sales = collect([]);
        $transactions_all = $transactions->getAllTransactions($address, true);
        $transactions_all_sorts = $transactions_all->sortBy(function ($item) {
            return intval($item['transaction_id']['lt']);
        });

        $txs = $transactions_all_sorts->filter(function ($item) use ($address) {
            return isset($item['in_msg']['account']['interfaces'])
                && (in_array('nft_sale_get_gems', $item['in_msg']['account']['interfaces']) || in_array('nft_sale', $item['in_msg']['account']['interfaces']))
                && $item['in_msg']['destination'] == $address;
        });

        foreach ($txs as $transaction) {
            $nft_sale_contract_address = $transaction['in_msg']['source'];
            $nft_sale_txs = $transactions->getAllTransactions($nft_sale_contract_address, true);

            if (!$nft_sale_txs->count()) break;

            foreach ($nft_sale_txs->sortBy('utime') as $key => $nft_sale_tx) {
                $in_address = $nft_sale_tx['in_msg']['source'];
                $out_address = $nft_sale_tx['in_msg']['destination'];

                // Если адрес отправителя принадлежит маркетплейсу
                if (in_array($in_address, $market_place_addresses)) continue;

                // Если получатель сам контракт
                if ($out_address != $nft_sale_contract_address) continue;

                // Если получатель данный NFT
                if ($in_address == $address) continue;

                // Если NFT была снят с продажи
                if (isset($nft_sale_tx['out_msgs'][0]['destination']) && $nft_sale_tx['out_msgs'][0]['destination'] == $address) continue;

                $sales->push([
                    'price' => floatval($nft_sale_tx['in_msg']['value'] / 1000000000),
                    'time' => Carbon::parse($nft_sale_tx['utime'])->toDateTimeString(),
                    'hash' => $nft_sale_tx['transaction_id']['hash'],
                    'buyer_address' => $nft_sale_tx['in_msg']['source'],
                    'sale_contract' => $nft_sale_contract_address,
                    'address' => $address
                ]);

                break;
            }
        }

        return $sales;
    }
}
