<?php

namespace Karakushan\TonNft;

use Illuminate\Support\ServiceProvider;
use Karakushan\TonNft\Commands\SaleAnalytics;
use Karakushan\TonNft\Commands\SaleAnalyticsCollection;
use Karakushan\TonNft\Commands\SaleAnalyticsCollections;
use Karakushan\TonNft\Commands\SaleAnalyticsNft;
use Karakushan\TonNft\Commands\UpdateNftIndex;
use Karakushan\TonNft\Commands\UpdateNftMeta;

class TonNftServiceProvider extends ServiceProvider
{
    /**
     * Загрузка любых служб приложения.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/ton-nft.php' => config_path('ton-nft.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                SaleAnalytics::class,
                SaleAnalyticsCollection::class,
                SaleAnalyticsCollections::class,
                SaleAnalyticsNft::class,
                UpdateNftIndex::class,
                UpdateNftMeta::class
            ]);
        }
    }
}
