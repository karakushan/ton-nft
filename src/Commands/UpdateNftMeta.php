<?php

namespace Karakushan\TonNft\Commands;

use App\Actions\MakeImageSizes;
use App\Models\CollectionNft;
use App\Models\File;
use Carbon\Carbon;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use stringEncode\Exception;
use GuzzleHttp\Exception\ClientException;

class UpdateNftMeta extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ton-nft:update-metadata {address?} {limit=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the meta data of each NFT';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $items = CollectionNft::when($this->argument('address'), function ($q) {
            $q->where('collection_address', $this->argument('address'));
        })
            ->whereNull('file_id')
            ->whereNotNull('metadata_url')
            ->where(function ($q) {
                $q->whereDate('meta_updated_at', '<=', now()->subDays(3)->toDateTimeString());
                $q->orWhereNull('meta_updated_at');
            })
            ->orderBy('index', 'ASC')
            ->limit($this->argument('limit'))
            ->get();

        $client = new \GuzzleHttp\Client([
            'timeout' => 120,
            'verify' => false,
        ]);

        foreach ($items as $item) {
            $this->info('Start ' . $item->address);
            $item->update(['meta_updated_at' => now()->toDateTimeString()]);
            try {
                $response = Http::withoutVerifying()
                    ->retry(3, 100)
                    ->get($item->metadata_url);
            } catch (\GuzzleHttp\Exception\RequestException|\Illuminate\Http\Client\RequestException|\Illuminate\Http\Client\ConnectionException $exception) {
                $this->info($exception->getMessage());
                continue;

            }

            if ($response->failed() || !$response->ok()) continue;


            $json = json_decode(str_replace(['NaN'], ['""'], $response->body()), true);
            $name = $json['name'] ?? '';
            $description = $json['description'] ?? '';
            $image = $json['image'] ?? '';
            $attributes = $json['attributes'] ?? '';

            if ($image) {
                $ext = pathinfo($image, PATHINFO_EXTENSION);
                $image_name = $item->address . '.' . $ext;

                try {

                    $response = null;

                    try {
                        $response = $client->request('GET', $image);
                    } catch (ClientException $e) {
                        echo $e->getMessage();
                    }

                    if (!$response || $response->getStatusCode() != 200) continue;

                    $body = (string)$response->getBody();

                    $original_url = Storage::putFileAs(public_path('/upload/nfts/'), $image, $image_name);

                    $this->info(Storage::url($original_url));

                    $files = [];
                    if (!in_array($ext, ['svg'])) {
                        $files = MakeImageSizes::run($body, ['230x230', '456x456'], '/upload/nfts/');
                    }

                    $media = File::create([
                        'original_url' => $original_url,
                        'sizes' => $files
                    ]);

                    $this->info('FILE ID: ' . $media->id);

                    $item->update([
                        'title' => $name,
                        'description' => $description,
                        'image' => '',
                        'file_id' => $media->id,
                        'attributes' => $attributes
                    ]);


                } catch (\Exception $e) {
                    $this->info($e->getMessage());
                    continue;
                }

            }
        }
        return 0;
    }
}
