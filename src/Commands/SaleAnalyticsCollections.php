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

class SaleAnalyticsCollections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ton-nft:sale-analytics-collections {limit=100}';


    function handle()
    {
        $ton_nft = new TonNft();
        $collections = Collection::whereIn('state', ['mint', 'part-mint'])
            ->whereHas('nft_items', function ($q) {
                $q->whereNull('analytics_updated_at');
                $q->orWhere('analytics_updated_at', '<=', Carbon::now()->subDays(1)->toDateTimeString());
            })
            ->orderBy('order', 'ASC')->get();

        $max = (int)$this->argument('limit');
        $count = 0;
        foreach ($collections as $collection) {
            $this->info(sprintf('Start %s', $collection->title));

            $nfts = $collection->nft_items()->where(function ($q) {
                $q->whereNull('analytics_updated_at');
                $q->orWhere('analytics_updated_at', '<=', Carbon::now()->subDays(3)->toDateTimeString());
            })->get();

            if (!$nfts->count()) {
                $this->line('Не найдено NFT для анализа');
                continue;
            }

            $this->line('* Найдено ' . $nfts->count() . ' NFT   для анализа');

            foreach ($nfts as $nft) {
                if ($count >= $max) break 2;

                $this->line(sprintf('- Анализирую %s(%d)', $nft->address, $count));

                $sales = $ton_nft->nft->getSalesHistory($nft->address);

                if ($sales->count()) {
                    foreach ($sales as $sale) {
                        NftAnalytics::updateOrCreate([
                            'hash' => $sale['hash'],
                            'address' => $sale['address']
                        ], $sale);
                    }

                }

                $nft->update(['analytics_updated_at' => now()->toDateTimeString()]);

                $count++;
            }
        }
    }
}
