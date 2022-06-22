<?php

namespace Karakushan\TonNft;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class Transactions
{
    protected string $base_url = 'https://toncenter.com/api/v2';
    protected $client;


    public function __construct()
    {
        $this->client = Http::withoutVerifying()
            ->withHeaders(['X-API-Key' => config('ton-nft.ton_api_key')]);

        if (config('ton-nft.ton_center_base_url')) $this->base_url = config('ton-nft.ton_center_base_url');

    }

    /**
     * Get  transaction history of a given address.
     *
     * @return array
     */
    public function getTransactions(string $address, array $args = [])
    {

        $response = $this->client->get($this->base_url . '/getTransactions', array_merge([
            'address' => $address,
        ], $args));

        $transactions = [];

        if ($response->ok()) {
            $json = $response->json();
            $transactions = isset($json['result']) ? (array)$json['result'] : [];
        } else {
            Log::channel('nft_analytics')
                ->warning(sprintf('Ошибка при получении транзакций адреса %s', $address), (array)json_decode($response->body(), true));
        }

        return new Collection($transactions);
    }

    /**
     * Get all transaction history of a given address.
     *
     * @param string $address
     * @param bool $getAccount
     * @return Collection
     */
    function getAllTransactions(string $address, bool $getAccount = false): Collection
    {
        $haveTxs = true;
        $last_tx = null;
        $limit_per_query = 50;
        $total_txs = [];
        $nft_account = new Account();

        while ($haveTxs) {
            $args = ['limit' => $limit_per_query];
            if (!is_null($last_tx)) {
                $args['lt'] = $last_tx['transaction_id']['lt'];
                $args['hash'] = $last_tx['transaction_id']['hash'];
            }

            $txs = $this->getTransactions($address, $args);

            if ($txs->count() < $limit_per_query) {
                $haveTxs = false;
            }

            foreach ($txs as $tx) {
                if (in_array($tx['transaction_id']['hash'], array_keys($total_txs))) continue;
                if (is_array($tx['in_msg']['source'])) continue;

                if ($getAccount) {
                    $tx['in_msg']['account'] = $nft_account->getAccount($tx['in_msg']['source']);
                    sleep(1);
                }
                $total_txs[$tx['transaction_id']['hash']] = $tx;
            }

            $last_tx = end($txs);
        }

        return new Collection($total_txs);
    }
}
