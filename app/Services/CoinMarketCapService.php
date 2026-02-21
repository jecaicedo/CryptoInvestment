<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CoinMarketCapService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.coinmarketcap.base_url');
        $this->apiKey = config('services.coinmarketcap.key');
    }

    public function search(string $query): array
    {
        $response = Http::withHeaders([
            'X-CMC_PRO_API_KEY' => $this->apiKey,
            'Accept'            => 'application/json',
        ])->get($this->baseUrl . '/v1/cryptocurrency/listings/latest', [
            'limit'   => 200,
            'convert' => 'USD',
            'sort'    => 'market_cap',
        ]);

        if ($response->failed() || !isset($response->json()['data'])) {
            return [];
        }

        $q = strtolower(trim($query));

        return collect($response->json('data', []))
            ->filter(fn($item) =>
                str_contains(strtolower($item['name']), $q) ||
                str_contains(strtolower($item['symbol']), $q)
            )
            ->take(10)
            ->map(fn($item) => [
                'id'     => $item['id'],
                'name'   => $item['name'],
                'symbol' => $item['symbol'],
                'slug'   => $item['slug'],
            ])
            ->values()
            ->toArray();
    }

    public function getQuotes(array $cmcIds): array
    {
        if (empty($cmcIds)) {
            return [];
        }

        $response = Http::withHeaders([
            'X-CMC_PRO_API_KEY' => $this->apiKey,
            'Accept' => 'application/json',
        ])->get($this->baseUrl . '/v2/cryptocurrency/quotes/latest', [
            'id' => implode(',', $cmcIds),
            'convert' => 'USD',
        ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json('data', []);
    }

    private function resolveGeckoId(string $slug, string $name, string $symbol): string
    {
        $staticMap = [
            'bitcoin'      => 'bitcoin',
            'ethereum'     => 'ethereum',
            'bnb'          => 'binancecoin',
            'solana'       => 'solana',
            'xrp'          => 'ripple',
            'dogecoin'     => 'dogecoin',
            'cardano'      => 'cardano',
            'toncoin'      => 'the-open-network',
            'avalanche-2'  => 'avalanche-2',
            'usd-coin'     => 'usd-coin',
            'polkadot'     => 'polkadot',
            'chainlink'    => 'chainlink',
            'litecoin'     => 'litecoin',
            'bitcoin-cash' => 'bitcoin-cash',
            'stellar'      => 'stellar',
            'monero'       => 'monero',
            'ethereum-classic' => 'ethereum-classic',
            'cosmos'       => 'cosmos',
            'uniswap'      => 'uniswap',
            'internet-computer' => 'internet-computer',
            'aptos'        => 'aptos',
            'near-protocol' => 'near',
            'optimism-ethereum' => 'optimism',
            'arbitrum'     => 'arbitrum',
            'polygon'      => 'matic-network',
            'shiba-inu'    => 'shiba-inu',
            'pepe'         => 'pepe',
            'wrapped-bitcoin' => 'wrapped-bitcoin',
            'dai'          => 'dai',
            'tether'       => 'tether',
            'tron'         => 'tron',
            'filecoin'     => 'filecoin',
            'hedera'       => 'hedera-hashgraph',
            'vechain'      => 'vechain',
            'algorand'     => 'algorand',
            'flow'         => 'flow',
            'aave'         => 'aave',
            'maker'        => 'maker',
            'the-sandbox'  => 'the-sandbox',
            'decentraland' => 'decentraland',
            'axie-infinity' => 'axie-infinity',
            'render'       => 'render-token',
            'injective'    => 'injective-protocol',
            'sui'          => 'sui',
            'sei'          => 'sei-network',
            'celestia'     => 'celestia',
        ];

        if (isset($staticMap[$slug])) {
            return $staticMap[$slug];
        }

        $response = Http::withHeaders(['Accept' => 'application/json'])
            ->get('https://api.coingecko.com/api/v3/search', [
                'query' => $symbol,
            ]);

        if ($response->ok()) {
            $coins = $response->json('coins', []);
            foreach ($coins as $coin) {
                if (
                    strtolower($coin['symbol']) === strtolower($symbol) ||
                    strtolower($coin['name'])   === strtolower($name)
                ) {
                    return $coin['id'];
                }
            }
        }

        return $slug;
    }

    public function getCoinGeckoHistory(string $slug, string $range, string $name = '', string $symbol = ''): array
    {
        $geckoId = $this->resolveGeckoId($slug, $name, $symbol);

        $days = match($range) {
            '1h'  => 1,
            '24h' => 1,
            '7d'  => 7,
            '30d' => 30,
            default => 1,
        };

        $response = Http::withHeaders(['Accept' => 'application/json'])
            ->get("https://api.coingecko.com/api/v3/coins/{$geckoId}/market_chart", [
                'vs_currency' => 'usd',
                'days'        => $days,
            ]);

        if ($response->failed()) {
            return [];
        }

        $prices = $response->json('prices', []);

        if ($range === '1h') {
            $oneHourAgo = now()->subHour()->timestamp * 1000;
            $prices = array_values(array_filter($prices, fn($p) => $p[0] >= $oneHourAgo));
        }

        return array_map(fn($p) => [
            'price'       => $p[1],
            'recorded_at' => date('Y-m-d\TH:i:s\Z', intval($p[0] / 1000)),
        ], $prices);
    }
}