<?php

namespace Karakushan\TonNft\Commands;

use App\Models\Collection;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class UpdateNftIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ton-nft:update-index {address?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обновляет метаданные каждой коллекции';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->argument('address')) {
            $this->update_items($this->argument('address'));
        } else {
            $collections = Collection::whereNotNull('contract_address')
                ->whereIn('state', ['mint', 'part-mint'])
                ->get();
            foreach ($collections as $collection) {
                $this->update_items($collection->contract_address);
            }
        }

    }

    function update_items($address)
    {
        $collection = Collection::where('contract_address', $address)->firstOrFail();
        $this->info(sprintf('Обновляем NFT коллекции %s %s', $collection->title,$collection->contract_address));

        $process = Process::fromShellCommandline('node node/UpdateNftIndex.js ' . $collection->contract_address);
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo 'ERR > ' . $buffer;
            } else {
                echo 'OUT > ' . $buffer;
            }
        });
    }
}
