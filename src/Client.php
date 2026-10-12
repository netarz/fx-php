<?php

declare(strict_types=1);

namespace NetArz\Fx;

/**
 * Client for the NetArz FX API (Iranian Toman exchange rates).
 *
 *     $fx = new \NetArz\Fx\Client(getenv('NETARZ_FX_KEY'));
 *     $usd = $fx->rate('USD');                       // ['code' => 'USD', 'buy' => …, 'sell' => …]
 *     $toman = $fx->convert('USD', 'IRT', 250)['result'];
 *
 * Needs ext-curl and ext-json. Docs: https://netarz.ir/docs/fx
 */
final class Client
{
    public const VERSION = '0.1.0';
    public const DEFAULT_BASE_URL = 'https://netarz.ir/api/fx/v1';

    /** @var array<string, array{0: float, 1: mixed}> */
    private array $cache = [];

    /**
     * @param string $key     the app key (fx-ntz-v1-…)
     * @param int    $ttl     seconds a GET answer is reused; 0 turns the cache off
     * @param bool   $ipv4    connect over IPv4 so the source address is the one you allow-listed
     */
    public function __construct(
        private readonly string $key,
        private readonly string $baseUrl = self::DEFAULT_BASE_URL,
        private readonly int $ttl = 120,
        private readonly int $timeout = 10,
        private readonly bool $ipv4 = true,
    ) {
        if ($key === '') {
            throw new \InvalidArgumentException('Pass an API key (create one at https://netarz.ir/fx).');
        }
    }

    /** @return array{data: list<array<string, mixed>>, meta: array<string, mixed>} */
    public function rates(array $codes = []): array
    {
        return $this->request('GET', '/rates', ['codes' => $codes ? implode(',', $codes) : null]);
    }

    /** @return array<string, mixed> */
    public function rate(string $code): array
    {
        return $this->request('GET', '/rates/'.rawurlencode(strtoupper($code)))['data'];
    }

    public function currencies(): array
    {
        return $this->request('GET', '/currencies');
    }

    /**
     * $from / $to: a currency code, IRT (Toman) or IRR (Rial).
     *
     * @return array{from: string, to: string, amount: float|int, side: string, rate: float|int, result: float|int}
     */
    public function convert(string $from, string $to, float|int $amount = 1, string $side = 'mid'): array
    {
        return $this->request('GET', '/convert', ['from' => $from, 'to' => $to, 'amount' => $amount, 'side' => $side])['data'];
    }

    /** Pro plan: range, from, to, interval, format. */
    public function history(string $code, array $params = []): mixed
    {
        return $this->request('GET', '/history/'.rawurlencode(strtoupper($code)), $params);
    }

    /** Pro plan: date or from/to, format. */
    public function archive(string $code, array $params = []): mixed
    {
        return $this->request('GET', '/archive/'.rawurlencode(strtoupper($code)), $params);
    }

    public function status(): array
    {
        return $this->request('GET', '/status');
    }

    /** Your app: plan, limits and today's usage. */
    public function me(): array
    {
        return $this->request('GET', '/me')['data'];
    }

    /** Call any endpoint. Returns decoded JSON, or the body as a string for CSV answers. */
    public function request(string $method, string $path, array $params = [], ?array $body = null): mixed
    {
        $params = array_filter($params, static fn ($v) => $v !== null);
        $url = rtrim($this->baseUrl, '/').$path.($params ? '?'.http_build_query($params) : '');

        if ($method === 'GET' && $this->ttl > 0 && isset($this->cache[$url]) && microtime(true) - $this->cache[$url][0] < $this->ttl) {
            return $this->cache[$url][1];
        }

        $headers = [
            'Authorization: Bearer '.$this->key,
            'Accept: application/json',
            'User-Agent: netarz-fx-php/'.self::VERSION,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $body === null ? $headers : [...$headers, 'Content-Type: application/json'],
            CURLOPT_IPRESOLVE => $this->ipv4 ? CURL_IPRESOLVE_V4 : CURL_IPRESOLVE_WHATEVER,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
        }

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new FxException(0, ['code' => 'network_error', 'message' => $error]);
        }

        $data = str_contains($type, 'json') ? json_decode((string) $raw, true) : $raw;

        if ($status >= 400) {
            throw new FxException($status, is_array($data) ? ($data['error'] ?? []) : ['message' => substr((string) $raw, 0, 200)]);
        }

        if ($method === 'GET' && $this->ttl > 0) {
            $this->cache[$url] = [microtime(true), $data];
        }

        return $data;
    }
}
