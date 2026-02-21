<?php

namespace App\Console\Commands;

use App\Models\Cryptocurrency;
use App\Models\PriceHistory;
use App\Services\CoinMarketCapService;
use Illuminate\Console\Command;

class FetchCryptoPrices extends Command
{
    protected $signature = 'crypto:fetch-prices';
    protected $description = 'Fetch and store current prices for all tracked cryptocurrencies';

    public function handle(CoinMarketCapService $service): void
    {
        $tracked = Cryptocurrency::where('is_tracked', true)->get();

        if ($tracked->isEmpty()) {
            return;
        }

        $ids = $tracked->pluck('cmc_id')->toArray();
        $quotes = $service->getQuotes($ids);

        foreach ($tracked as $crypto) {
            $quoteData = $quotes[(string) $crypto->cmc_id] ?? null;
            $quote = $quoteData['quote']['USD'] ?? null;

            if (!$quote) {
                continue;
            }

            PriceHistory::create([
                'cryptocurrency_id' => $crypto->id,
                'price' => $quote['price'] ?? 0,
                'percent_change_1h' => $quote['percent_change_1h'] ?? null,
                'percent_change_24h' => $quote['percent_change_24h'] ?? null,
                'percent_change_7d' => $quote['percent_change_7d'] ?? null,
                'volume_24h' => $quote['volume_24h'] ?? null,
                'market_cap' => $quote['market_cap'] ?? null,
                'recorded_at' => now(),
            ]);
        }

        $this->info('Prices fetched and stored: ' . now());
    }
}