<?php

namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
use App\Models\PriceHistory;
use App\Services\CoinMarketCapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CryptoController extends Controller
{
    public function __construct(private CoinMarketCapService $service) {}

    public function index()
    {
        return view('dashboard');
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:1']);
        $results = $this->service->search($request->q);
        return response()->json($results);
    }

    public function getTracked(): JsonResponse
    {
        $tracked = Cryptocurrency::where('is_tracked', true)->get();

        if ($tracked->isEmpty()) {
            return response()->json([]);
        }

        $ids = $tracked->pluck('cmc_id')->toArray();
        $quotes = $this->service->getQuotes($ids);

        $data = $tracked->map(function ($crypto) use ($quotes) {
            $quoteData = $quotes[(string) $crypto->cmc_id] ?? null;
            $quote = $quoteData['quote']['USD'] ?? null;

            if ($quote) {
                PriceHistory::create([
                    'cryptocurrency_id'  => $crypto->id,
                    'price'              => $quote['price'] ?? 0,
                    'percent_change_1h'  => $quote['percent_change_1h'] ?? null,
                    'percent_change_24h' => $quote['percent_change_24h'] ?? null,
                    'percent_change_7d'  => $quote['percent_change_7d'] ?? null,
                    'volume_24h'         => $quote['volume_24h'] ?? null,
                    'market_cap'         => $quote['market_cap'] ?? null,
                    'recorded_at'        => now(),
                ]);
            }

            return [
                'id'                 => $crypto->id,
                'cmc_id'             => $crypto->cmc_id,
                'name'               => $crypto->name,
                'symbol'             => $crypto->symbol,
                'price'              => $quote['price'] ?? 0,
                'slug'               => $crypto->slug,
                'percent_change_1h'  => $quote['percent_change_1h'] ?? 0,
                'percent_change_24h' => $quote['percent_change_24h'] ?? 0,
                'percent_change_7d'  => $quote['percent_change_7d'] ?? 0,
                'volume_24h'         => $quote['volume_24h'] ?? 0,
                'market_cap'         => $quote['market_cap'] ?? 0,
            ];
        });

        return response()->json($data);
    }


    public function track(Request $request): JsonResponse
    {
        $request->validate([
            'cmc_id' => 'required|integer',
            'name' => 'required|string',
            'symbol' => 'required|string|max:20',
            'slug' => 'required|string',
        ]);

        $crypto = Cryptocurrency::updateOrCreate(
            ['cmc_id' => $request->cmc_id],
            [
                'name' => $request->name,
                'symbol' => $request->symbol,
                'slug' => $request->slug,
                'is_tracked' => true,
            ]
        );

        return response()->json($crypto, 201);
    }

    public function untrack(int $id): JsonResponse
    {
        $crypto = Cryptocurrency::findOrFail($id);
        $crypto->update(['is_tracked' => false]);
        return response()->json(['success' => true]);
    }

    public function history(int $id, Request $request): JsonResponse
    {
        $range = $request->get('range', '24h');

        $from = match ($range) {
            '1h'  => now()->subHour(),
            '24h' => now()->subDay(),
            '7d'  => now()->subWeek(),
            '30d' => now()->subMonth(),
            default => now()->subDay(),
        };

        $history = PriceHistory::where('cryptocurrency_id', $id)
            ->where('recorded_at', '>=', $from)
            ->orderBy('recorded_at')
            ->get(['price', 'recorded_at']);

        return response()->json($history);
    }

    public function coinGeckoHistory(Request $request): JsonResponse
    {
        $request->validate([
            'slug'   => 'required|string',
            'range'  => 'required|string|in:1h,24h,7d,30d',
            'name'   => 'sometimes|string',
            'symbol' => 'sometimes|string',
        ]);

        $data = $this->service->getCoinGeckoHistory(
            $request->slug,
            $request->range,
            $request->get('name', ''),
            $request->get('symbol', '')
        );

        if (empty($data)) {
            return response()->json(['error' => 'No se pudo obtener el historial'], 422);
        }

        return response()->json($data);
    }
}