<?php

namespace Karakushan\TonNft\Commands;

use App\Models\Collection;
use App\Models\CollectionNft;
use App\Models\NftAnalytics;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Karakushan\TonNft\TonNft;
use Karakushan\TonNft\Transactions;

class SaleAnalyticsNft extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ton-nft:sale-analytics-nft {address}';


    function handle()
    {
        $ton_nft = new TonNft();
        $address = $this->argument('address');
        $this->info('Анализирую ' . $address);
        $sales = $ton_nft->nft->getSalesHistory($this->argument('address'));
        Storage::put('txs/' . $address . '--sales.json', $sales->toJson(JSON_PRETTY_PRINT));

        $nft = CollectionNft::where('address', $address)->firstOrFail();

        if ($sales->count()) {
            foreach ($sales as $sale) {
                NftAnalytics::updateOrCreate([
                    'hash' => $sale['hash'],
                    'address' => $sale['address']
                ], $sale);
            }

        }

        $nft->update(['analytics_updated_at' => Carbon::now()->toDateTimeString()]);
    }
}
