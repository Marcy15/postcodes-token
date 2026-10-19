<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CityResource;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CityController extends Controller
{
    /**
     * @api {get} /api/cities Települések listája, keresés, szűrés
     * @apiName ListCities
     * @apiGroup Cities
     * @apiVersion 1.0.0
     * @apiDescription Részleges, ékezetérzékenysége az SQL kollációtól függ. Lapozható lista. A megye hiányozhat a hibás forrásadatok miatt.
     * @apiQuery {String} [q] Részleges településnév vagy irányítószám.
     * @apiQuery {Number} [zip_code] Pontos 4 jegyű irányítószám.
     * @apiQuery {Number} [county_id] Megye azonosítója (cities.id_county).
     * @apiQuery {String} [county] Részleges megyenévre szűrés.
     * @apiQuery {Number} [per_page=15] Oldalméret (1–100).
     * @apiQuery {Number} [page=1] Oldalszám.
     * @apiSuccess {Object[]} data Településrekordok.
     * @apiSuccess {Object} meta Lapozási metaadatok.
     * @apiExample {curl} Városnév és megye:
     *     curl "http://127.0.0.1:8000/api/cities?q=Vác&county_id=14"
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'q' => ['sometimes', 'string', 'max:80'],
            'zip_code' => ['sometimes', 'integer', 'between:1000,9999'],
            'county_id' => ['sometimes', 'integer', 'min:1'],
            'county' => ['sometimes', 'string', 'max:50'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $query = City::query()->with('county:id,name');
        if (isset($filters['q']) && trim($filters['q']) !== '') {
            $term = trim($filters['q']);
            $query->where(fn ($q) => $q->where('city', 'like', "%{$term}%")
                ->orWhere('zip_code', 'like', "%{$term}%"));
        }
        if (isset($filters['zip_code'])) {
            $query->where('zip_code', (int) $filters['zip_code']);
        }
        if (isset($filters['county_id'])) {
            $query->where('id_county', (int) $filters['county_id']);
        }
        if (isset($filters['county']) && trim($filters['county']) !== '') {
            $name = trim($filters['county']);
            $query->whereHas('county', fn ($q) => $q->where('name', 'like', "%{$name}%"));
        }

        return CityResource::collection(
            $query->orderBy('city')->orderBy('zip_code')->orderBy('id')
                ->paginate((int) ($filters['per_page'] ?? 15))->appends($request->query())
        );
    }

    /**
     * @api {get} /api/cities/postcode/:zip_code Irányítószám keresése
     * @apiName GetCitiesByPostcode
     * @apiGroup Cities
     * @apiVersion 1.0.0
     * @apiParam {Number} zip_code 4 jegyű irányítószám.
     * @apiSuccess {Object[]} data Az összes hozzá tartozó település (akár több is).
     */
    public function byPostcode(int $zip_code): AnonymousResourceCollection
    {
        Validator::make(['zip_code' => $zip_code], [
            'zip_code' => ['required', 'integer', 'between:1000,9999'],
        ])->validate();

        return CityResource::collection(
            City::query()->with('county:id,name')->where('zip_code', $zip_code)
                ->orderBy('city')->get()
        );
    }

    /**
     * @api {get} /api/cities/:city Egy település lekérdezése
     * @apiName GetCity
     * @apiGroup Cities
     * @apiVersion 1.0.0
     * @apiParam {Number} city A cities.id rekordazonosító.
     * @apiSuccess {Object} data Városadatok, irányítószám és megye.
     * @apiError (404) NotFound Nincs ilyen rekord.
     */
    public function show(City $city): CityResource
    {
        return new CityResource($city->load('county:id,name'));
    }

    /**
     * @api {post} /api/cities Település létrehozása
     * @apiName CreateCity
     * @apiGroup Cities
     * @apiVersion 1.0.0
     * @apiPermission token
     * @apiHeader {String} Authorization Bearer TOKEN.
     * @apiBody {Number} zip_code 4 jegyű irányítószám.
     * @apiBody {String} city Település neve (max. 50 karakter).
     * @apiBody {Number} id_county Létező counties.id.
     * @apiSuccess (201) {Object} data Létrehozott rekord.
     * @apiError (422) ValidationError Hibás mezők.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(self::validationRules(false));
        $city = City::query()->create($data);
        return (new CityResource($city->load('county:id,name')))
            ->response()->setStatusCode(201);
    }

    /**
     * @api {patch} /api/cities/:city Település részleges módosítása
     * @apiName UpdateCity
     * @apiGroup Cities
     * @apiVersion 1.0.0
     * @apiPermission token
     * @apiHeader {String} Authorization Bearer TOKEN.
     * @apiParam {Number} city Rekord ID.
     * @apiBody {Number} [zip_code] Új irányítószám.
     * @apiBody {String} [city] Új településnév.
     * @apiBody {Number} [id_county] Létező megyeazonosító.
     * @apiDescription PUT módszerrel is hívható; mindkettő részleges módosításként működik.
     */
    public function update(Request $request, City $city): CityResource
    {
        $data = $request->validate(self::validationRules(true));
        $city->update($data);
        return new CityResource($city->refresh()->load('county:id,name'));
    }

    /**
     * @api {delete} /api/cities/:city Település törlése
     * @apiName DeleteCity
     * @apiGroup Cities
     * @apiVersion 1.0.0
     * @apiPermission token
     * @apiHeader {String} Authorization Bearer TOKEN.
     * @apiParam {Number} city A törlendő rekord ID-ja.
     * @apiSuccess (204) NoContent Üres válasz.
     */
    public function destroy(City $city): \Illuminate\Http\Response
    {
        $city->delete();
        return response()->noContent();
    }

    private static function validationRules(bool $partial): array
    {
        $required = $partial ? 'sometimes' : 'required';
        return [
            'zip_code' => [$required, 'integer', 'between:1000,9999'],
            'city' => [$required, 'string', 'max:50'],
            'id_county' => [$required, 'integer', Rule::exists('counties', 'id')],
        ];
    }
}
