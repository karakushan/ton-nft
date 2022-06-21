<?php

namespace Karakushan\TonNft;

class TonNft
{
    public Transactions $transactions;
    public Account $account;
    public Nft $nft;

    public function __construct()
    {
        $this->transactions = new Transactions();
        $this->account = new Account();
        $this->nft = new Nft();
    }
}
