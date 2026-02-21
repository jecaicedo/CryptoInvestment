<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CryptoInvestment</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%2310b981'/><text x='50' y='72' font-size='70' font-family='Arial' font-weight='bold' text-anchor='middle' fill='white'>$</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <style>
        body { background-color: #0f1117; color: #e2e8f0; font-family: 'Segoe UI', sans-serif; }
        .card { background: #1a1d2e; border: 1px solid #2d3148; border-radius: 12px; }
        .card:hover { border-color: #6366f1; transition: border-color 0.2s; }
        .badge-up { color: #10b981; }
        .badge-down { color: #ef4444; }
        .search-dropdown { background: #1a1d2e; border: 1px solid #2d3148; border-radius: 8px; max-height: 280px; overflow-y: auto; }
        .search-item:hover { background: #2d3148; cursor: pointer; }
        .modal-overlay { background: rgba(0,0,0,0.75); }
        .range-btn { padding: 4px 12px; border-radius: 6px; font-size: 13px; cursor: pointer; border: 1px solid #2d3148; background: #1a1d2e; color: #94a3b8; }
        .range-btn.active { background: #6366f1; color: white; border-color: #6366f1; }
        ::-webkit-scrollbar { width: 6px; } ::-webkit-scrollbar-track { background: #1a1d2e; } ::-webkit-scrollbar-thumb { background: #2d3148; border-radius: 3px; }
        .pulse-dot { width: 8px; height: 8px; background: #10b981; border-radius: 50%; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
        .spinner { border: 2px solid #2d3148; border-top-color: #6366f1; border-radius: 50%; width: 16px; height: 16px; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body class="min-h-screen">

<header class="sticky top-0 z-40" style="background:#0f1117; border-bottom:1px solid #1a1d2e;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div>
                <h1 class="text-xl font-bold text-white">₿ CryptoInvestment</h1>
                <p class="text-xs" style="color:#64748b;">Panel de seguimiento en tiempo real</p>
            </div>
        </div>
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <div class="flex items-center gap-2">
                <div class="pulse-dot"></div>
                <span id="last-update" class="text-xs" style="color:#64748b;">Actualizando...</span>
                <button onclick="manualRefresh()" id="btn-refresh" title="Refrescar precios" style="background:#1a1d2e; border:1px solid #2d3148; border-radius:6px; padding:4px 8px; cursor:pointer; color:#a5b4fc; display:flex; align-items:center; gap:4px; font-size:12px;">
                    <svg id="refresh-icon" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <polyline points="23 4 23 10 17 10"/>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                    </svg>
                    Refrescar
                </button>
            </div>
            <div class="relative flex-1 sm:w-72" id="search-container">
                <input
                    id="search-input"
                    type="text"
                    placeholder="Buscar criptomoneda..."
                    class="w-full px-4 py-2 rounded-lg text-sm outline-none"
                    style="background:#1a1d2e; border:1px solid #2d3148; color:#e2e8f0;"
                />
                <div id="search-results" class="search-dropdown absolute left-0 right-0 top-full mt-1 z-50 hidden"></div>
            </div>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
    <div id="empty-state" class="hidden flex flex-col items-center justify-center py-24 text-center">
        <div class="text-6xl mb-4">📊</div>
        <h2 class="text-xl font-semibold text-white mb-2">Sin criptomonedas seleccionadas</h2>
        <p style="color:#64748b;" class="text-sm">Busca y agrega criptomonedas usando la barra de búsqueda</p>
    </div>

    <div id="loading-state" class="flex justify-center items-center py-24">
        <div class="spinner" style="width:32px; height:32px;"></div>
    </div>

    <div id="crypto-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"></div>
</main>

<div id="chart-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center modal-overlay p-4">
    <div class="card w-full max-w-3xl p-6" style="max-height:90vh; overflow-y:auto;">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 id="modal-title" class="text-lg font-bold text-white"></h2>
                <p id="modal-subtitle" class="text-sm" style="color:#64748b;"></p>
                <p class="text-xs mt-1" style="color:#475569;">Datos almacenados localmente · Registrados cada 5 min desde que se agregó la moneda</p>           
            </div>
            <button id="close-modal" class="text-2xl leading-none" style="color:#64748b;">&times;</button>
        </div>
        <div class="flex gap-2 mb-4" id="range-selector">
            <button class="range-btn active" data-range="1h">1H</button>
            <button class="range-btn" data-range="24h">24H</button>
            <button class="range-btn" data-range="7d">7D</button>
            <button class="range-btn" data-range="30d">30D</button>
        </div>
        <div id="chart-loading" class="hidden justify-center py-8"><div class="spinner" style="width:24px;height:24px;"></div></div>
        <div id="chart-no-data" class="hidden text-center py-8" style="color:#64748b;">
            <p>Sin datos históricos para este rango.</p>
            <p class="text-xs mt-1">Los datos se almacenan cada 5 minutos desde que se agrega la moneda.</p>
        </div>
        <div style="position:relative; height:300px;">
            <canvas id="price-chart"></canvas>
        </div>
        <p class="text-xs text-center mt-3 px-2 py-2 rounded-lg" style="color:#64748b; background:#0f1117; border:1px solid #1e2235;">
            Este historial refleja los precios capturados y guardados en la base de datos local desde el momento en que comenzaste a hacer seguimiento de esta criptomoneda. Para ver datos históricos completos, usa el botón <span style="color:#10b981; font-weight:600;">Historial CoinGecko</span> en la esquina inferior derecha.
        </p>
    </div>
</div>

<button id="btn-coingecko" onclick="openCoinGeckoModal()" title="Ver historial CoinGecko" style="position:fixed; bottom:24px; right:24px; z-index:60; background:linear-gradient(135deg,#10b981,#059669); color:white; border:none; border-radius:50px; padding:12px 20px; font-size:13px; font-weight:600; cursor:pointer; box-shadow:0 4px 20px rgba(16,185,129,0.4); display:flex; align-items:center; gap:8px;">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
    Historial CoinGecko
</button>

<div id="cg-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center modal-overlay p-4">
    <div class="card w-full max-w-3xl p-6" style="max-height:90vh; overflow-y:auto;">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <span style="color:#10b981;">●</span> Historial CoinGecko
                </h2>
                <p class="text-xs" style="color:#64748b;">Datos históricos reales por rango de tiempo</p>
            </div>
            <button onclick="closeCoinGeckoModal()" class="text-2xl leading-none" style="color:#64748b;">&times;</button>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <select id="cg-crypto-select" class="flex-1 px-3 py-2 rounded-lg text-sm outline-none" style="background:#0f1117; border:1px solid #2d3148; color:#e2e8f0;">
                <option value="">Selecciona una criptomoneda...</option>
            </select>
            <div class="flex gap-2" id="cg-range-selector">
                <button class="range-btn active" data-range="1h">1H</button>
                <button class="range-btn" data-range="24h">24H</button>
                <button class="range-btn" data-range="7d">7D</button>
                <button class="range-btn" data-range="30d">30D</button>
            </div>
        </div>

        <div id="cg-loading" class="hidden justify-center py-8"><div class="spinner" style="width:24px;height:24px;"></div></div>
        <div id="cg-no-data" class="hidden text-center py-8" style="color:#64748b;">
            <p>Sin datos disponibles para este rango.</p>
        </div>
        <div id="cg-error" class="hidden text-center py-8" style="color:#ef4444;">
            <p>No se pudo obtener el historial. Verifica el slug de la moneda.</p>
        </div>
        <div id="cg-chart-wrap" style="position:relative; height:300px;">
            <canvas id="cg-price-chart"></canvas>
        </div>
        <div id="cg-stats" class="hidden grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4"></div>
    </div>
</div>

<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const BASE = '/api';
    let priceChart = null;
    let currentCryptoId = null;
    let currentRange = '1h';
    let refreshInterval = null;

    const $grid = document.getElementById('crypto-grid');
    const $empty = document.getElementById('empty-state');
    const $loading = document.getElementById('loading-state');
    const $modal = document.getElementById('chart-modal');
    const $searchInput = document.getElementById('search-input');
    const $searchResults = document.getElementById('search-results');
    const $lastUpdate = document.getElementById('last-update');
    let cgCurrentSymbol = null;

    function formatPrice(val) {
        if (val >= 1) return '$' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return '$' + Number(val).toFixed(6);
    }

    function formatLarge(val) {
        if (val >= 1e9) return '$' + (val / 1e9).toFixed(2) + 'B';
        if (val >= 1e6) return '$' + (val / 1e6).toFixed(2) + 'M';
        return '$' + Number(val).toLocaleString('en-US');
    }

    function pctHtml(val) {
        const cls = val >= 0 ? 'badge-up' : 'badge-down';
        const arrow = val >= 0 ? '▲' : '▼';
        return `<span class="${cls}">${arrow} ${Math.abs(val).toFixed(2)}%</span>`;
    }

    function buildCard(c) {
        return `
        <div class="card p-4 relative group" id="card-${c.id}" data-id="${c.id}" data-cmc="${c.cmc_id}" data-name="${c.name}" data-symbol="${c.symbol}" data-slug="${c.slug}">
            <button onclick="removeCrypto(${c.id})" class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity text-lg leading-none" style="color:#ef4444;" title="Quitar">&times;</button>
            <div class="flex items-center gap-2 mb-3 cursor-pointer" onclick="openChart(${c.id}, '${c.name}', '${c.symbol}')">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold" style="background:#2d3148; color:#a5b4fc;">
                    ${c.symbol.substring(0, 2)}
                </div>
                <div>
                    <p class="font-semibold text-white text-sm">${c.name}</p>
                    <p class="text-xs" style="color:#64748b;">${c.symbol}</p>
                </div>
            </div>
            <p class="text-xl font-bold text-white mb-2">${formatPrice(c.price)}</p>
            <div class="grid grid-cols-3 gap-1 text-xs mb-3">
                <div><p style="color:#64748b;">1h</p>${pctHtml(c.percent_change_1h)}</div>
                <div><p style="color:#64748b;">24h</p>${pctHtml(c.percent_change_24h)}</div>
                <div><p style="color:#64748b;">7d</p>${pctHtml(c.percent_change_7d)}</div>
            </div>
            <div class="text-xs space-y-1" style="border-top:1px solid #2d3148; padding-top:8px;">
                <div class="flex justify-between"><span style="color:#64748b;">Volumen 24h</span><span class="text-white">${formatLarge(c.volume_24h)}</span></div>
                <div class="flex justify-between"><span style="color:#64748b;">Market Cap</span><span class="text-white">${formatLarge(c.market_cap)}</span></div>
            </div>
            <button onclick="openChart(${c.id}, '${c.name}', '${c.symbol}')" class="mt-3 w-full text-xs py-1.5 rounded-lg" style="background:#2d3148; color:#a5b4fc;">
                Ver historial ↗
            </button>
        </div>`;
    }

    async function loadTracked() {
        try {
            const res = await fetch(`${BASE}/crypto/tracked`);
            const data = await res.json();
            $loading.classList.add('hidden');

            if (!data.length) {
                $empty.classList.remove('hidden');
                $grid.innerHTML = '';
                return;
            }

            $empty.classList.add('hidden');

            const existingIds = new Set([...$grid.querySelectorAll('[data-id]')].map(el => el.dataset.id));
            const newIds = new Set(data.map(c => String(c.id)));

            existingIds.forEach(id => {
                if (!newIds.has(id)) {
                    document.getElementById(`card-${id}`)?.remove();
                }
            });

            data.forEach(c => {
                const existing = document.getElementById(`card-${c.id}`);
                if (existing) {
                    existing.outerHTML = buildCard(c);
                } else {
                    $grid.insertAdjacentHTML('beforeend', buildCard(c));
                }
            });

            const now = new Date();
            $lastUpdate.textContent = `Actualizado: ${now.toLocaleTimeString('es-CO')}`;
        } catch (e) {
            $loading.classList.add('hidden');
            $lastUpdate.textContent = 'Error al actualizar';
        }
    }

    async function removeCrypto(id) {
        await fetch(`${BASE}/crypto/track/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF }
        });
        document.getElementById(`card-${id}`)?.remove();
        if (!$grid.querySelector('[data-id]')) {
            $empty.classList.remove('hidden');
        }
    }

    async function openChart(id, name, symbol) {
        currentCryptoId = id;
        document.getElementById('modal-title').textContent = `${name} (${symbol})`;
        document.getElementById('modal-subtitle').textContent = 'Historial de precios';
        $modal.classList.remove('hidden');
        document.querySelectorAll('.range-btn').forEach(b => b.classList.toggle('active', b.dataset.range === currentRange));
        await loadChart();
    }

    async function loadChart() {
        const chartLoading = document.getElementById('chart-loading');
        const chartNoData  = document.getElementById('chart-no-data');
        chartLoading.classList.remove('hidden');
        chartLoading.classList.add('flex');
        chartNoData.classList.add('hidden');

        const res  = await fetch(`${BASE}/crypto/${currentCryptoId}/history?range=${currentRange}`);
        const data = await res.json();

        chartLoading.classList.add('hidden');
        chartLoading.classList.remove('flex');

        if (!data.length) {
            chartNoData.classList.remove('hidden');
            if (priceChart) { priceChart.destroy(); priceChart = null; }
            return;
        }

        const labels = data.map(d => new Date(d.recorded_at));
        const prices = data.map(d => parseFloat(d.price));

        const ctx   = document.getElementById('price-chart').getContext('2d');
        if (priceChart) priceChart.destroy();

        const isUp  = prices[prices.length - 1] >= prices[0];
        const color = isUp ? '#10b981' : '#ef4444';

        priceChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    data: prices,
                    borderColor: color,
                    backgroundColor: color + '20',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1a1d2e',
                        borderColor: '#2d3148',
                        borderWidth: 1,
                        callbacks: {
                            label: c => formatPrice(c.parsed.y),
                            title: c => {
                                const ts = c[0].parsed.x;
                                return new Date(ts).toLocaleString('es-CO');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            displayFormats: {
                                minute: 'HH:mm',
                                hour:   'dd/MM HH:mm',
                                day:    'dd/MM',
                            }
                        },
                        grid:  { color: '#1a1d2e' },
                        ticks: { color: '#64748b', maxTicksLimit: 6 }
                    },
                    y: {
                        grid:  { color: '#1a1d2e' },
                        ticks: { color: '#64748b', callback: v => formatPrice(v) }
                    }
                }
            }
        });
    }

    document.getElementById('close-modal').addEventListener('click', () => {
        $modal.classList.add('hidden');
        if (priceChart) { priceChart.destroy(); priceChart = null; }
    });

    $modal.addEventListener('click', (e) => {
        if (e.target === $modal) {
            $modal.classList.add('hidden');
            if (priceChart) { priceChart.destroy(); priceChart = null; }
        }
    });

    document.getElementById('range-selector').addEventListener('click', async (e) => {
        if (!e.target.classList.contains('range-btn')) return;
        currentRange = e.target.dataset.range;
        document.querySelectorAll('.range-btn').forEach(b => b.classList.toggle('active', b === e.target));
        await loadChart();
    });

    let searchTimeout = null;
    $searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        const q = $searchInput.value.trim();
        if (q.length < 2) { $searchResults.classList.add('hidden'); return; }
        searchTimeout = setTimeout(async () => {
            const res = await fetch(`${BASE}/crypto/search?q=${encodeURIComponent(q)}`);
            const data = await res.json();
            if (!data.length) { $searchResults.classList.add('hidden'); return; }
            $searchResults.innerHTML = data.map(c => `
                <div class="search-item flex items-center justify-between px-3 py-2 text-sm" data-cmc="${c.id}" data-name="${c.name}" data-symbol="${c.symbol}" data-slug="${c.slug}">
                    <div>
                        <span class="text-white font-medium">${c.name}</span>
                        <span class="ml-2 text-xs" style="color:#64748b;">${c.symbol}</span>
                    </div>
                    <span class="text-xs" style="color:#6366f1;">+ Agregar</span>
                </div>
            `).join('');
            $searchResults.classList.remove('hidden');
        }, 350);
    });

    $searchResults.addEventListener('click', async (e) => {
        const item = e.target.closest('.search-item');
        if (!item) return;
        $searchInput.value = '';
        $searchResults.classList.add('hidden');

        await fetch(`${BASE}/crypto/track`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({
                cmc_id: parseInt(item.dataset.cmc),
                name: item.dataset.name,
                symbol: item.dataset.symbol,
                slug: item.dataset.slug,
            })
        });

        await loadTracked();
    });

    document.addEventListener('click', (e) => {
        if (!document.getElementById('search-container').contains(e.target)) {
            $searchResults.classList.add('hidden');
        }
    });

    async function manualRefresh() {
        const icon = document.getElementById('refresh-icon');
        const btn  = document.getElementById('btn-refresh');
        icon.style.animation = 'spin 0.8s linear infinite';
        btn.style.opacity    = '0.6';
        btn.style.pointerEvents = 'none';
        await loadTracked();
        icon.style.animation = '';
        btn.style.opacity    = '1';
        btn.style.pointerEvents = 'auto';
    }

    loadTracked();
    refreshInterval = setInterval(loadTracked, 60000);

    let cgChart = null;
    let cgCurrentRange = '1h';
    let cgCurrentSlug = null;
    let cgCurrentName = null;

    const slugMap = {
        'bitcoin':     'bitcoin',
        'ethereum':    'ethereum',
        'bnb':         'binancecoin',
        'solana':      'solana',
        'xrp':         'xrp',
        'dogecoin':    'dogecoin',
        'cardano':     'cardano',
        'toncoin':     'the-open-network',
        'avalanche-2': 'avalanche-2',
        'usd-coin':    'usd-coin',
    };

    function openCoinGeckoModal() {
        document.getElementById('cg-modal').classList.remove('hidden');
        populateCgSelect();
    }

    function closeCoinGeckoModal() {
        document.getElementById('cg-modal').classList.add('hidden');
        if (cgChart) { cgChart.destroy(); cgChart = null; }
        document.getElementById('cg-stats').classList.add('hidden');
        document.getElementById('cg-stats').innerHTML = '';
        document.getElementById('cg-no-data').classList.add('hidden');
        document.getElementById('cg-error').classList.add('hidden');
    }

    function populateCgSelect() {
        const select = document.getElementById('cg-crypto-select');
        const cards  = $grid.querySelectorAll('[data-id]');
        const current = select.value;
        select.innerHTML = '<option value="">Selecciona una criptomoneda...</option>';

        cards.forEach(card => {
            const slug   = card.dataset.slug;
            const name   = card.dataset.name;
            const sym    = card.dataset.symbol;
            const opt    = document.createElement('option');
            opt.value          = slug;
            opt.dataset.name   = name;
            opt.dataset.symbol = sym;
            opt.textContent    = `${name} (${sym})`;
            if (slug === current) opt.selected = true;
            select.appendChild(opt);
        });
    }

    async function loadCgChart() {
        if (!cgCurrentSlug) return;

        const cgLoading = document.getElementById('cg-loading');
        const cgNoData  = document.getElementById('cg-no-data');
        const cgError   = document.getElementById('cg-error');
        const cgStats   = document.getElementById('cg-stats');

        cgLoading.classList.remove('hidden');
        cgLoading.classList.add('flex');
        cgNoData.classList.add('hidden');
        cgError.classList.add('hidden');
        cgStats.classList.add('hidden');
        cgStats.innerHTML = '';

        try {
            const res = await fetch(
                `${BASE}/crypto/coingecko-history?slug=${encodeURIComponent(cgCurrentSlug)}&range=${cgCurrentRange}&name=${encodeURIComponent(cgCurrentName)}&symbol=${encodeURIComponent(cgCurrentSymbol)}`
            );
            const data = await res.json();

            cgLoading.classList.add('hidden');
            cgLoading.classList.remove('flex');

            if (!res.ok || data.error || !data.length) {
                cgNoData.classList.remove('hidden');
                if (cgChart) { cgChart.destroy(); cgChart = null; }
                return;
            }

            const labels    = data.map(d => new Date(d.recorded_at));
            const priceVals = data.map(d => parseFloat(d.price));
            const isUp      = priceVals[priceVals.length - 1] >= priceVals[0];
            const color     = isUp ? '#10b981' : '#ef4444';

            const ctx = document.getElementById('cg-price-chart').getContext('2d');
            if (cgChart) cgChart.destroy();

            cgChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        data: priceVals,
                        borderColor: color,
                        backgroundColor: color + '20',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1a1d2e',
                            borderColor: '#2d3148',
                            borderWidth: 1,
                            callbacks: {
                                label: c => formatPrice(c.parsed.y),
                                title: c => new Date(c[0].parsed.x).toLocaleString('es-CO')
                            }
                        }
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                displayFormats: {
                                    minute: 'HH:mm',
                                    hour:   'dd/MM HH:mm',
                                    day:    'dd/MM',
                                }
                            },
                            grid:  { color: '#1a1d2e' },
                            ticks: { color: '#64748b', maxTicksLimit: 6 }
                        },
                        y: {
                            grid:  { color: '#1a1d2e' },
                            ticks: { color: '#64748b', callback: v => formatPrice(v) }
                        }
                    }
                }
            });

            const minP    = Math.min(...priceVals);
            const maxP    = Math.max(...priceVals);
            const current = priceVals[priceVals.length - 1];
            const change  = ((current - priceVals[0]) / priceVals[0] * 100).toFixed(2);
            const changeC = parseFloat(change) >= 0 ? '#10b981' : '#ef4444';
            const arrow   = parseFloat(change) >= 0 ? '▲' : '▼';

            cgStats.innerHTML = `
                <div class="text-center p-3 rounded-lg" style="background:#0f1117;">
                    <p class="text-xs mb-1" style="color:#64748b;">Precio actual</p>
                    <p class="font-bold text-white text-sm">${formatPrice(current)}</p>
                </div>
                <div class="text-center p-3 rounded-lg" style="background:#0f1117;">
                    <p class="text-xs mb-1" style="color:#64748b;">Cambio ${cgCurrentRange.toUpperCase()}</p>
                    <p class="font-bold text-sm" style="color:${changeC};">${arrow} ${Math.abs(change)}%</p>
                </div>
                <div class="text-center p-3 rounded-lg" style="background:#0f1117;">
                    <p class="text-xs mb-1" style="color:#64748b;">Mínimo</p>
                    <p class="font-bold text-white text-sm">${formatPrice(minP)}</p>
                </div>
                <div class="text-center p-3 rounded-lg" style="background:#0f1117;">
                    <p class="text-xs mb-1" style="color:#64748b;">Máximo</p>
                    <p class="font-bold text-white text-sm">${formatPrice(maxP)}</p>
                </div>
            `;
            cgStats.classList.remove('hidden');

        } catch (e) {
            cgLoading.classList.add('hidden');
            cgLoading.classList.remove('flex');
            document.getElementById('cg-error').classList.remove('hidden');
        }
    }

    document.getElementById('cg-crypto-select').addEventListener('change', function () {
        const selected  = this.options[this.selectedIndex];
        cgCurrentSlug   = this.value;
        cgCurrentName   = selected ? selected.dataset.name   ?? '' : '';
        cgCurrentSymbol = selected ? selected.dataset.symbol ?? '' : '';
        if (cgCurrentSlug) loadCgChart();
    });
    
    document.getElementById('cg-range-selector').addEventListener('click', (e) => {
        if (!e.target.classList.contains('range-btn')) return;
        cgCurrentRange = e.target.dataset.range;
        document.querySelectorAll('#cg-range-selector .range-btn').forEach(b => b.classList.toggle('active', b === e.target));
        if (cgCurrentSlug) loadCgChart();
    });

    document.getElementById('cg-modal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('cg-modal')) closeCoinGeckoModal();
    });

</script>

</body>
</html>