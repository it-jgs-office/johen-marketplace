<?php

return [
    // Timeout request ke API pihak ketiga (detik)
    'timeout' => 5,

    // Lama cache hasil cek per brand+user_id+zone_id (menit)
    'cache_ttl' => 5,

    // Cache hasil cek untuk mengurangi request berulang ke validator komunitas.
    'mobile_legends_cache_ttl' => 30,

    // Base URL validator komunitas (agregator Codashop) — type 'isan'
    'isan_url' => 'https://api.isan.eu.org/nickname',

    // Endpoint akun GoPay Games — type 'gopay'
    'gopay_url' => 'https://gopay.co.id/games/v1/order/user-account',

    /*
    |--------------------------------------------------------------------------
    | Resolver deteksi akun per brand
    |--------------------------------------------------------------------------
    |
    | Key = pola nama brand (wildcard *, case-insensitive).
    | type 'enka' → GET https://enka.network/{path}, nickname diambil dari
    | 'nickname_path' (dot notation).
    | type 'isan' → GET {isan_url}/{game}?<params>, nickname dari field 'name'
    | saat success=true. Template {user_id}/{zone_id} diganti otomatis.
    | type 'gopay' → POST JSON ke {gopay_url}: {code, data:{userId, zoneId}},
    | valid saat success=true/message=success, nickname dari data.username dll.
    | Brand yang tidak terdaftar → netral.
    |
    */

    'brands' => [
        'mobile legends*' => [
            'type' => 'isan',
            'game' => 'ml',
            'params' => ['id' => '{user_id}', 'server' => '{zone_id}'],
            // Isan hanya mendukung akun Mobile Legends ID/SEA.
            'region' => 'Indonesia',
        ],
        'free fire*' => [
            'type' => 'isan',
            'game' => 'ff',
            'params' => ['id' => '{user_id}'],
        ],
        'arena of valor*' => [
            'type' => 'isan',
            'game' => 'aov',
            'params' => ['id' => '{user_id}'],
        ],
        'call of duty*' => [
            'type' => 'isan',
            'game' => 'cod',
            'params' => ['id' => '{user_id}'],
        ],
        'valorant*' => [
            'type' => 'isan',
            'game' => 'valo',
            'params' => ['id' => '{user_id}'],
        ],
        'point blank*' => [
            'type' => 'isan',
            'game' => 'pb',
            'params' => ['id' => '{user_id}'],
        ],
        'pubg mobile*' => [
            'type' => 'gopay',
            'code' => 'PUBG_ID',
        ],
        'genshin impact*' => [
            'type' => 'enka',
            'path' => 'api/uid/{user_id}',
            'nickname_path' => 'playerInfo.nickname',
        ],
        'honkai star rail*' => [
            'type' => 'enka',
            'path' => 'api/hsr/uid/{user_id}',
            'nickname_path' => 'detailInfo.nickname',
        ],
        'zenless zone zero*' => [
            'type' => 'enka',
            'path' => 'api/zzz/uid/{user_id}',
            'nickname_path' => 'playerInfo.nickname',
        ],
    ],
];
