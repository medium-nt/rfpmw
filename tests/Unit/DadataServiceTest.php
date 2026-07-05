<?php

namespace Tests\Unit;

use App\Services\DaData\DadataService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class DadataServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure config has valid defaults so tests can selectively override
        config([
            'services.dadata.token' => 'test-api-token',
            'services.dadata.url'   => 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party',
        ]);
    }

    // -------------------------------------------------------
    //  Happy path: single suggestion
    // -------------------------------------------------------

    public function testFindPartyByInnReturnsNormalizedData(): void
    {
        Http::fake([
            '*' => Http::response([
                'suggestions' => [
                    [
                        'value' => 'ООО «Ромашка»',
                        'data'  => [
                            'inn'     => '7712345678',
                            'address' => ['value' => 'г Москва, ул Ленина, д 1'],
                            'sites'   => ['www.romashka.ru'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new DadataService();
        $result = $service->findPartyByInn('7712345678');

        $this->assertCount(1, $result);
        $this->assertSame('ООО «Ромашка»', $result[0]['name']);
        $this->assertSame('7712345678', $result[0]['inn']);
        $this->assertSame('г Москва, ул Ленина, д 1', $result[0]['address']);
        $this->assertSame('https://www.romashka.ru', $result[0]['website']);
    }

    // -------------------------------------------------------
    //  Happy path: multiple suggestions
    // -------------------------------------------------------

    public function testFindPartyByInnReturnsMultipleSuggestions(): void
    {
        Http::fake([
            '*' => Http::response([
                'suggestions' => [
                    [
                        'value' => 'ООО «Ромашка»',
                        'data'  => [
                            'inn'     => '7712345678',
                            'address' => ['value' => 'г Москва, ул Ленина, д 1'],
                            'sites'   => null,
                        ],
                    ],
                    [
                        'value' => 'ООО «Лотос»',
                        'data'  => [
                            'inn'     => '7712345678',
                            'address' => ['value' => 'г Москва, ул Мира, д 5'],
                            'sites'   => ['lotos.ru'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new DadataService();
        $result = $service->findPartyByInn('7712345678');

        $this->assertCount(2, $result);

        $this->assertSame('ООО «Ромашка»', $result[0]['name']);
        $this->assertNull($result[0]['website']);

        $this->assertSame('ООО «Лотос»', $result[1]['name']);
        $this->assertSame('https://lotos.ru', $result[1]['website']);
    }

    // -------------------------------------------------------
    //  Empty response
    // -------------------------------------------------------

    public function testFindPartyByInnReturnsEmptyArrayWhenNoSuggestions(): void
    {
        Http::fake([
            '*' => Http::response(['suggestions' => []], 200),
        ]);

        $service = new DadataService();
        $result = $service->findPartyByInn('0000000000');

        $this->assertSame([], $result);
    }

    // -------------------------------------------------------
    //  HTTP error (500)
    // -------------------------------------------------------

    public function testFindPartyByInnReturnsEmptyArrayOnHttpError(): void
    {
        Http::fake([
            '*' => Http::response('Internal Server Error', 500),
        ]);

        $service = new DadataService();
        $result = $service->findPartyByInn('7712345678');

        $this->assertSame([], $result);
    }

    // -------------------------------------------------------
    //  Missing token throws RuntimeException
    // -------------------------------------------------------

    public function testFindPartyByInnThrowsWhenTokenIsEmpty(): void
    {
        config(['services.dadata.token' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DaData: не задан DADATA_API_KEY или DADATA_URL в .env');

        $service = new DadataService();
        $service->findPartyByInn('7712345678');
    }

    // -------------------------------------------------------
    //  Missing URL also throws RuntimeException
    // -------------------------------------------------------

    public function testFindPartyByInnThrowsWhenUrlIsEmpty(): void
    {
        config(['services.dadata.url' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DaData: не задан DADATA_API_KEY или DADATA_URL в .env');

        $service = new DadataService();
        $service->findPartyByInn('7712345678');
    }

    // -------------------------------------------------------
    //  Website normalization edge cases
    // -------------------------------------------------------

    public function testFindPartyByInnNormalizesWebsiteField(): void
    {
        Http::fake([
            '*' => Http::response([
                'suggestions' => [
                    [
                        'value' => 'ООО «Тест»',
                        'data'  => [
                            'inn'     => '7712345678',
                            'address' => ['value' => 'г Москва'],
                            'sites'   => ['http://example.com/path'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new DadataService();
        $result = $service->findPartyByInn('7712345678');

        // normalizeWebsite parses URL and prepends https://
        $this->assertSame('https://example.com', $result[0]['website']);
    }

    public function testFindPartyByInnHandlesMissingDataKeysGracefully(): void
    {
        Http::fake([
            '*' => Http::response([
                'suggestions' => [
                    [
                        'value' => 'ООО «Пустышка»',
                        // 'data' key is completely missing
                    ],
                ],
            ], 200),
        ]);

        $service = new DadataService();
        $result = $service->findPartyByInn('7712345678');

        $this->assertCount(1, $result);
        $this->assertSame('ООО «Пустышка»', $result[0]['name']);
        $this->assertSame('7712345678', $result[0]['inn']); // falls back to $inn param
        $this->assertSame('', $result[0]['address']);
        $this->assertNull($result[0]['website']);
    }

    public function testFindPartyByInnFiltersOutEntriesWithBlankName(): void
    {
        Http::fake([
            '*' => Http::response([
                'suggestions' => [
                    [
                        'value' => '',
                        'data'  => [
                            'inn'     => '7712345678',
                            'address' => ['value' => 'г Москва'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new DadataService();
        $result = $service->findPartyByInn('7712345678');

        // Entry with blank name should be filtered out
        $this->assertSame([], $result);
    }
}
