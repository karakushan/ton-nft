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

class SaleAnalyticsCollection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ton-nft:sale-analytics-collection {collection} {limit=100}';


    function handle()
    {
        $ton_nft = new TonNft();
        $nfts =
            CollectionNft::where('collection_address', $this->argument('collection'))
                ->where(function ($q) {
                    $q->whereNull('analytics_updated_at');
                    $q->orWhere('analytics_updated_at', '<=', Carbon::now()->subDays(3)->toDateTimeString());
                })
                ->limit($this->argument('limit'))
                ->get();

        if (!$nfts->count()) {
            $this->info('Не найдено NFT для анализа');
            return 0;
        }

        $this->info('Найдено ' . $nfts->count() . ' NFT   для анализа');

        foreach ($nfts as $nft) {
            $sales = $ton_nft->nft->getSalesHistory($nft->address);

            if ($sales->count()) {
                foreach ($sales as $sale) {
                    NftAnalytics::updateOrCreate([
                        'hash' => $sale['hash'],
                        'address' => $sale['address']
                    ], $sale);
                }
                $this->info('Анализирую ' . $sale['address']);
            }

            $nft->update(['analytics_updated_at' => Carbon::now()->toDateTimeString()]);
        }
    }
}
