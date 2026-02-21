# 💲 CryptoInvestment

Panel web en tiempo real para seguimiento personalizado de criptomonedas. Muestra precios actualizados, cambios porcentuales, volumen de mercado e historial de precios desde una sola página sin recargas.

---

## ¿Qué necesitas antes de empezar?

- PHP 8.2 o superior
- Composer
- MySQL
- Una API Key gratuita de [CoinMarketCap](https://coinmarketcap.com/api/)

---

## Instalación paso a paso

### 1. Clona el repositorio

```bash
git clone https://github.com/tu-usuario/cryptoinvestment.git
cd cryptoinvestment
```

### 2. Instala las dependencias

```bash
composer install
```

### 3. Crea el archivo de configuración

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configura la base de datos y tu API Key

Abre el archivo `.env` y edita estas líneas:

```env
DB_DATABASE=cryptoinvestment
DB_USERNAME=tu_usuario_mysql
DB_PASSWORD=tu_contraseña_mysql

COINMARKETCAP_API_KEY=tu_api_key_aqui
```

### 5. Crea la base de datos

Entra a MySQL y ejecuta:

```sql
CREATE DATABASE cryptoinvestment;
```

### 6. Crea las tablas y carga las criptos iniciales

```bash
php artisan migrate
php artisan db:seed
```

Esto crea las tablas necesarias y carga 10 criptomonedas populares por defecto (Bitcoin, Ethereum, Solana, XRP, BNB, entre otras).

### 7. Levanta el servidor

```bash
php artisan serve
```

Abre el navegador en [http://localhost:8000](http://localhost:8000) y listo.

---

## ¿Cómo se usa?

**Ver precios en tiempo real**
Al entrar verás las criptomonedas cargadas con sus precios actuales, cambios porcentuales en 1h, 24h y 7d, volumen y market cap. Los precios se refrescan automáticamente cada 2 minutos. También puedes refrescar manualmente con el botón **Refrescar** en la barra superior.

**Buscar y agregar monedas**
Usa la barra de búsqueda en la parte superior para encontrar cualquier criptomoneda por nombre o símbolo. Haz clic en **+ Agregar** para incluirla en tu panel.

**Quitar una moneda**
Pasa el mouse sobre la tarjeta de la moneda y haz clic en la **×** que aparece en la esquina superior derecha.

**Ver historial de precios (local)**
Haz clic en **Ver historial** dentro de cualquier tarjeta. Muestra los precios guardados localmente desde que agregaste la moneda, con rangos de 1H, 24H, 7D y 30D.

**Ver historial completo con CoinGecko**
Haz clic en el botón verde **Historial CoinGecko** en la esquina inferior derecha. Selecciona la moneda y el rango de tiempo. Trae datos históricos reales sin depender del historial local.

---

## Sobre la API Key gratuita de CoinMarketCap

El plan gratuito tiene un límite de **10,000 créditos al mes**. Con el intervalo de refresco de 2 minutos y hasta 10 monedas en seguimiento, el consumo estimado es de unos 3,000 créditos diarios, bien dentro del límite.

Si quieres cambiar el intervalo de actualización, busca esta línea en `resources/views/dashboard.blade.php`:

```javascript
refreshInterval = setInterval(loadTracked, 120000); // 2 minutos
```

Cambia `120000` al tiempo que prefieras en milisegundos.

---

## Guardado de historial automático

El sistema guarda el precio de cada moneda cada vez que se refresca el panel. Para habilitar también el guardado automático en segundo plano, agrega esta línea al crontab de tu servidor:

```bash
* * * * * cd /ruta/del/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

O puedes ejecutarlo manualmente cuando quieras:

```bash
php artisan crypto:fetch-prices
```

---

## Estructura del proyecto

```
app/
├── Console/Commands/FetchCryptoPrices.php   → Comando para guardar precios
├── Http/Controllers/CryptoController.php    → Lógica principal
├── Models/Cryptocurrency.php                → Modelo de criptomoneda
├── Models/PriceHistory.php                  → Modelo de historial
└── Services/CoinMarketCapService.php        → Integración con APIs

database/
├── migrations/                              → Estructura de la base de datos
└── seeders/CryptocurrencySeeder.php         → Criptos iniciales

resources/views/
└── dashboard.blade.php                      → Toda la interfaz (SPA)

routes/
├── web.php                                  → Ruta principal
└── api.php                                  → Endpoints del panel
```

---

## APIs utilizadas

| API | Uso | Requiere Key |
|---|---|---|
| CoinMarketCap | Precios en tiempo real y búsqueda | Sí (gratuita) |
| CoinGecko | Historial completo de precios | No |