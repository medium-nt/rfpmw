<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContractorDadataPartyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        config([
            'services.dadata.token' => 'test-api-token',
            'services.dadata.url'   => 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party',
        ]);
    }

    // -------------------------------------------------------
    //  Unauthenticated user is redirected to login
    // -------------------------------------------------------

    public function testGuestIsRedirectedToLogin(): void
    {
        $this->get(route('contractors.dadata.party', ['inn' => '7712345678']))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------
    //  Authenticated user with valid 10-digit INN gets 200 + JSON
    // -------------------------------------------------------

    public function testAuthenticatedUserCanSearchCompanyBy10DigitInn(): void
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

        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('contractors.dadata.party', ['inn' => '7712345678']))
            ->assertOk()
            ->assertJson([
                'suggestions' => [
                    [
                        'name'    => 'ООО «Ромашка»',
                        'inn'     => '7712345678',
                        'address' => 'г Москва, ул Ленина, д 1',
                        'website' => 'https://www.romashka.ru',
                    ],
                ],
            ]);
    }

    // -------------------------------------------------------
    //  Authenticated user with valid 12-digit INN (ИП)
    // -------------------------------------------------------

    public function testAuthenticatedUserCanSearchCompanyBy12DigitInn(): void
    {
        Http::fake([
            '*' => Http::response([
                'suggestions' => [
                    [
                        'value' => 'ИП Иванов И И',
                        'data'  => [
                            'inn'     => '771234567890',
                            'address' => ['value' => 'г Москва, ул Мира, д 10'],
                            'email'   => null,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->manager()->create();

        $this->actingAs($user)
            ->get(route('contractors.dadata.party', ['inn' => '771234567890']))
            ->assertOk()
            ->assertJson([
                'suggestions' => [
                    [
                        'name'    => 'ИП Иванов И И',
                        'inn'     => '771234567890',
                        'address' => 'г Москва, ул Мира, д 10',
                        'website' => null,
                    ],
                ],
            ]);
    }

    // -------------------------------------------------------
    //  Validation: too few digits → 422
    // -------------------------------------------------------

    public function testInvalidInnWithTooFewDigitsReturns422(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('contractors.dadata.party', ['inn' => '12345']))
            ->assertUnprocessable();
    }

    // -------------------------------------------------------
    //  Validation: letters in INN → 422
    // -------------------------------------------------------

    public function testInvalidInnWithLettersReturns422(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('contractors.dadata.party', ['inn' => 'abcdefghij']))
            ->assertUnprocessable();
    }

    // -------------------------------------------------------
    //  Validation: empty INN → 422
    // -------------------------------------------------------

    public function testEmptyInnReturns422(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('contractors.dadata.party'))
            ->assertUnprocessable();
    }

    // -------------------------------------------------------
    //  DaData returns empty suggestions → still 200 with empty array
    // -------------------------------------------------------

    public function testEmptyDaDataResponseReturnsEmptySuggestionsArray(): void
    {
        Http::fake([
            '*' => Http::response(['suggestions' => []], 200),
        ]);

        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('contractors.dadata.party', ['inn' => '7712345678']))
            ->assertOk()
            ->assertJson(['suggestions' => []]);
    }

    // -------------------------------------------------------
    //  DaData HTTP error → still 200 with empty suggestions
    // -------------------------------------------------------

    public function testDaDataHttpErrorReturnsEmptySuggestions(): void
    {
        Http::fake([
            '*' => Http::response('Service Unavailable', 503),
        ]);

        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('contractors.dadata.party', ['inn' => '7712345678']))
            ->assertOk()
            ->assertJson(['suggestions' => []]);
    }
}
