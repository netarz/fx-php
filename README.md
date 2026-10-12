# netarz/fx

PHP client for the [NetArz FX API](https://netarz.ir/fx-api): Tehran open-market exchange rates in Iranian Toman for USD, EUR, AED, TRY, GBP and the other currencies on the [NetArz rate board](https://netarz.ir/rates), with buy/sell prices, a converter and, on the Pro plan, history and the daily archive.

PHP 8.1+, ext-curl. No other dependencies.

```bash
composer config repositories.netarz-fx vcs https://github.com/netarz/fx-php
composer require netarz/fx:^0.1
# Packagist release coming soon: composer require netarz/fx
```

```php
use NetArz\Fx\Client;
use NetArz\Fx\FxException;

$fx = new Client(getenv('NETARZ_FX_KEY'));   // create a free key at https://netarz.ir/fx

$usd = $fx->rate('USD');
echo $usd['buy'], ' / ', $usd['sell'];         // Toman

echo $fx->convert('USD', 'IRT', 250)['result']; // 250 dollars in Toman

try {
    $fx->history('USD', ['range' => '30d', 'interval' => '1d']); // Pro plan
} catch (FxException $e) {
    echo $e->status, ' ', $e->errorCode, ' ', $e->seenIp;
}
```

## Things to know

- **Keys are locked.** A server-side key answers only from the IP addresses you list on the app. The client connects over IPv4 by default so the source address matches what you listed; pass `ipv4: false` if you listed an IPv6 address. When the API refuses an address, `FxException::$seenIp` tells you which one it saw.
- **Free and Pro.** The free plan needs only an account and returns delayed rates. Pro returns live rates and unlocks history, OHLC, stats and the archive. See [plans](https://netarz.ir/docs/fx/plans).
- **Cache.** GET answers are reused for `$ttl` seconds (default 120) inside one `Client` object. For a shared cache across requests, put your own (Redis, APCu, Laravel `Cache::remember`) in front of it.
- Laravel and WordPress: see [fx-api-examples/laravel](https://github.com/netarz/fx-api-examples/tree/main/laravel) and the [WordPress plugin](https://github.com/netarz/netarz-fx-wordpress).
- Rates are for information; NetArz does not buy or sell these currencies.

Docs: [netarz.ir/docs/fx](https://netarz.ir/docs/fx) · Reference: [netarz.ir/docs/fx/reference](https://netarz.ir/docs/fx/reference)

## فارسی

کتابخانهٔ PHP برای [API نرخ ارز نِت اَرز](https://netarz.ir/fx-api): نرخ خرید و فروش دلار، یورو، درهم، لیر و ارزهای دیگر به تومان، همراه مبدل ارز. فقط افزونهٔ curl را لازم دارد. کلید رایگان را از [پنل API نرخ ارز](https://netarz.ir/fx) بگیرید. کلید سمت سرور فقط از IP‌هایی که در پنل ثبت کرده‌اید جواب می‌گیرد.

MIT License · NetArz
