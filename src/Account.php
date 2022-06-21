<?php

namespace Karakushan\TonNft;

use GuzzleHttp\Client;

class Account
{
    protected string $tonapi_base_url = 'https://tonapi.io/v1';

    function getAccount($account)
    {
        $content = null;

        try {
            $client = new Client();
            $response = $client->request('GET', $this->tonapi_base_url . '/blockchain/getAccount', [
                'query' => [
                    'account' => $account
                ],
                'delay' => 4000,
                'verify' => false
            ]);
            if ($response->getStatusCode() == 200) $content = json_decode((string)$response->getBody(),JSON_PRETTY_PRINT);

        } catch (\Exception $exception) {
            var_dump($exception->getMessage());
        }

        return $content;
    }
}
