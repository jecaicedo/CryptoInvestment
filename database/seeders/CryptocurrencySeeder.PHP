<?php

namespace Database\Seeders;

use App\Models\Cryptocurrency;
use Illuminate\Database\Seeder;

class CryptocurrencySeeder extends Seeder
{
    public function run(): void
    {
        $cryptos = [
            ['cmc_id' => 1,     'name' => 'Bitcoin',  'symbol' => 'BTC', 'slug' => 'bitcoin'],
            ['cmc_id' => 1027,  'name' => 'Ethereum', 'symbol' => 'ETH', 'slug' => 'ethereum'],
            ['cmc_id' => 1839,  'name' => 'BNB',      'symbol' => 'BNB', 'slug' => 'bnb'],
            ['cmc_id' => 5426,  'name' => 'Solana',   'symbol' => 'SOL', 'slug' => 'solana'],
            ['cmc_id' => 52,    'name' => 'XRP',      'symbol' => 'XRP', 'slug' => 'xrp'],
            ['cmc_id' => 74,    'name' => 'Dogecoin', 'symbol' => 'DOGE','slug' => 'dogecoin'],
            ['cmc_id' => 2010,  'name' => 'Cardano',  'symbol' => 'ADA', 'slug' => 'cardano'],
            ['cmc_id' => 11419, 'name' => 'Toncoin',  'symbol' => 'TON', 'slug' => 'toncoin'],
            ['cmc_id' => 5805,  'name' => 'Avalanche','symbol' => 'AVAX','slug' => 'avalanche-2'],
            ['cmc_id' => 3408,  'name' => 'USDC',     'symbol' => 'USDC','slug' => 'usd-coin'],
        ];

        foreach ($cryptos as $crypto) {
            Cryptocurrency::updateOrCreate(
                ['cmc_id' => $crypto['cmc_id']],
                array_merge($crypto, ['is_tracked' => true])
            );
        }
    }
}