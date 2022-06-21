# Ton NFT

## HTTP API

### Toncenter
``https://toncenter.com/api/v2/``

### Tonapi
``https://tonapi.io/docs/``

## Artisan команды

Собирает статистику продаж всей коллекции

``php artisan ton-nft:sale-analytics-collection {collection} {limit=100}``
    
Собирает статистику продаж отдельного NFT

``php artisan ton-nft:sale-analytics-nft {address}``

Собирает статистику продаж по всем NFT

``php artisan ton-nft:sale-analytics {limit=20}``



