<?php

namespace App\Services\DaData;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Тонкая обёртка над DaData Suggestions API для поиска компаний по ИНН.
 *
 * @see https://dadata.ru/api/find-party/
 */
class DadataService
{
    /**
     * Таймаут исходящего запроса к DaData (в секундах).
     */
    private const TIMEOUT = 5;

    /**
     * Найти компании по ИНН через DaData findById/party.
     *
     * Возвращает нормализованный список найденных компаний. Каждый элемент содержит:
     * name (string) — наименование компании; inn (string) — ИНН; address (string) —
     * юридический адрес; website (string|null) — сайт из data.sites (доступен только
     * на платном тарифе DaData). При ошибке или пустом ответе возвращается пустой массив.
     *
     * @return array<int, array{name: string, inn: string, address: string, website: ?string}>
     */
    public function findPartyByInn(string $inn): array
    {
        $token = config('services.dadata.token');
        $url = config('services.dadata.url');

        if (blank($token) || blank($url)) {
            throw new RuntimeException('DaData: не задан DADATA_API_KEY или DADATA_URL в .env');
        }

        /** @var Response $response */
        $response = Http::withHeaders([
            'Authorization' => 'Token ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->timeout(self::TIMEOUT)
            ->post($url, ['query' => $inn]);

        if ($response->failed()) {
            Log::warning('DaData find-party failed', [
                'inn' => $inn,
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            return [];
        }

        $suggestions = $response->json('suggestions', []);

        return collect($suggestions)
            ->map(fn (array $item) => [
                'name' => (string) ($item['value'] ?? ''),
                'inn' => (string) ($item['data']['inn'] ?? $inn),
                'address' => (string) ($item['data']['address']['value'] ?? ''),
                'website' => $this->extractWebsite($item['data']['sites'] ?? null),
            ])
            ->filter(fn (array $item) => filled($item['name']))
            ->values()
            ->all();
    }

    /**
     * Извлечь первый сайт из массива data.sites ответа DaData.
     *
     * Поле sites доступно только на платном тарифе; на бесплатном приходит null.
     * Формат: массив строк вида ["www.example.ru", ...] либо массив объектов.
     */
    private function extractWebsite(mixed $sites): ?string
    {
        if (blank($sites) || !is_array($sites)) {
            return null;
        }

        foreach ($sites as $site) {
            // DaData может вернуть либо строку, либо объект { value: "..." }
            $value = is_array($site) ? ($site['value'] ?? null) : $site;
            if (blank($value)) {
                continue;
            }

            return $this->normalizeWebsite((string) $value);
        }

        return null;
    }

    /**
     * Привести сайт к каноничному виду с https:// схемой.
     */
    private function normalizeWebsite(string $value): string
    {
        $host = parse_url($value, PHP_URL_HOST) ?: $value;

        return 'https://' . ltrim($host, '/');
    }
}
