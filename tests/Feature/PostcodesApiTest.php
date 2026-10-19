<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A RefreshDatabase ténylegesen lefuttatja AZ ÖSSZES MIGRÁCIÓT, ideértve az
 * importot is, elkülönített :memory: SQLite adatbázison.
 */
class PostcodesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_ures_adatbazison_a_migracio_minden_adatot_betolt(): void
    {
        $this->assertSame(3574, DB::table('cities')->count());
        $this->assertSame(20, DB::table('counties')->count());
        $this->assertSame(0, DB::table('cities')
            ->leftJoin('counties', 'cities.id_county', '=', 'counties.id')
            ->whereNull('counties.id')->count());
        $this->assertDatabaseHas('cities', ['city' => 'Vác', 'zip_code' => 2600]);
        $this->assertDatabaseHas('counties', ['id' => 14, 'name' => 'Pest']);
        $this->assertDatabaseHas('counties', ['id' => 1, 'name' => 'Budapest']);
    }

    public function test_kereses_megye_es_iranyitoszam_mukodik(): void
    {
        $this->getJson('/api/cities?q=Vác')->assertOk()->assertJsonFragment(['city' => 'Vác']);
        $this->getJson('/api/cities?county_id=14&zip_code=2600')
            ->assertOk()->assertJsonFragment(['city' => 'Vác']);
        $this->getJson('/api/cities?county=Pest&zip_code=2600')
            ->assertOk()->assertJsonFragment(['city' => 'Vác']);
        $this->getJson('/api/cities/postcode/2600')
            ->assertOk()->assertJsonFragment(['city' => 'Vác']);
        $this->getJson('/api/counties?q=Pest')->assertOk()->assertJsonFragment(['name' => 'Pest']);
        $this->getJson('/api/counties/14/cities')->assertOk()->assertJsonStructure(['data', 'links', 'meta']);
        $this->getJson('/api/cities?per_page=5')->assertOk()->assertJsonCount(5, 'data');
    }

    public function test_irast_token_nelkul_nem_enged(): void
    {
        $this->postJson('/api/cities', ['zip_code' => 2600, 'city' => 'Teszt', 'id_county' => 14])
            ->assertUnauthorized();
        $this->postJson('/api/counties', ['name' => 'Teszt'])->assertUnauthorized();
        $this->patchJson('/api/cities/1', ['city' => 'Teszt'])->assertUnauthorized();
        $this->deleteJson('/api/cities/1')->assertUnauthorized();
        $this->deleteJson('/api/counties/14')->assertUnauthorized();
    }

    public function test_login_token_teljes_crud_logout(): void
    {
        User::query()->create([
            'name' => 'Teszt Admin',
            'email' => 'admin@example.test',
            'password' => 'Jelszo12345!'
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.test', 'password' => 'Jelszo12345!'
        ])->assertOk()->assertJsonPath('token_type', 'Bearer');
        $token = $login->json('token');
        $this->assertNotEmpty($token);
        $this->withToken($token)->getJson('/api/auth/me')->assertOk();

        $county = $this->postJson('/api/counties', ['name' => 'Teszt megye'])
            ->assertCreated();
        $countyId = $county->json('data.id');
        $city = $this->postJson('/api/cities', [
            'zip_code' => 2600, 'city' => 'Teszt település', 'id_county' => $countyId,
        ])->assertCreated();
        $cityId = $city->json('data.id');

        $this->patchJson('/api/cities/'.$cityId, ['city' => 'Frissített település'])
            ->assertOk()->assertJsonPath('data.city', 'Frissített település');
        $this->patchJson('/api/counties/'.$countyId, ['name' => 'Teszt megye 2'])
            ->assertOk()->assertJsonPath('data.name', 'Teszt megye 2');
        $this->deleteJson('/api/counties/'.$countyId)->assertStatus(409);
        $this->deleteJson('/api/cities/'.$cityId)->assertNoContent();
        $this->deleteJson('/api/counties/'.$countyId)->assertNoContent();

        $this->postJson('/api/auth/logout')->assertOk();
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_hibas_bejelentkezes_validacio_es_hianyzo_rekord(): void
    {
        User::query()->create(['name'=>'Admin','email'=>'admin@example.test','password'=>'Jelszo12345!']);
        $this->postJson('/api/auth/login', [
            'email'=>'admin@example.test', 'password'=>'rossz',
        ])->assertUnauthorized();
        $this->getJson('/api/cities?per_page=101')->assertUnprocessable();
        $this->getJson('/api/cities?zip_code=12')->assertUnprocessable();
        $this->getJson('/api/cities/9999999')->assertNotFound();
    }
}
