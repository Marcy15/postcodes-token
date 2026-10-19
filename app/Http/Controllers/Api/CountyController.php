<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CityResource;
use App\Http\Resources\CountyResource;
use App\Models\County;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class CountyController extends Controller
{
    /**
     * @api {get} /api/counties Megyék lekérdezése és keresése
     * @apiName ListCounties
     * @apiGroup Counties
     * @apiVersion 1.0.0
     * @apiQuery {String} [q] Részleges megyenév.
     * @apiQuery {Number} [per_page=50] Oldalméret (1–100).
     * @apiQuery {Number} [page=1] Oldalszám.
     * @apiSuccess {Object[]} data Megyék, mindegyik cities_count számlálóval.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'q' => ['sometimes', 'string', 'max:50'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $query = County::query()->withCount('cities');
        if (isset($filters['q']) && trim($filters['q']) !== '') {
            $query->where('name', 'like', '%'.trim($filters['q']).'%');
        }

        return CountyResource::collection(
            $query->orderBy('name')->orderBy('id')
                ->paginate((int) ($filters['per_page'] ?? 50))->appends($request->query())
        );
    }

    /**
     * @api {get} /api/counties/:county Egy megye
     * @apiName GetCounty
     * @apiGroup Counties
     * @apiVersion 1.0.0
     * @apiParam {Number} county Megye ID-ja.
     * @apiSuccess {Object} data Megye és településrekordok száma.
     */
    public function show(County $county): CountyResource
    {
        return new CountyResource($county->loadCount('cities'));
    }

    /**
     * @api {get} /api/counties/:county/cities Egy megye települései
     * @apiName GetCountyCities
     * @apiGroup Counties
     * @apiVersion 1.0.0
     * @apiParam {Number} county Megye ID-ja.
     * @apiQuery {Number} [per_page=15] Oldalméret (1–100).
     * @apiQuery {Number} [page=1] Oldalszám.
     * @apiSuccess {Object[]} data A megyéhez tartozó települések.
     */
    public function cities(Request $request, County $county): AnonymousResourceCollection
    {
        $params = $request->validate([
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        return CityResource::collection(
            $county->cities()->with('county:id,name')->orderBy('city')->orderBy('zip_code')
                ->paginate((int) ($params['per_page'] ?? 15))->appends($request->query())
        );
    }

    /**
     * @api {post} /api/counties Megye létrehozása
     * @apiName CreateCounty
     * @apiGroup Counties
     * @apiVersion 1.0.0
     * @apiPermission token
     * @apiHeader {String} Authorization Bearer TOKEN.
     * @apiBody {String} name Megyenév (max. 50 karakter).
     * @apiSuccess (201) {Object} data A létrehozott megye.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:50']]);
        $county = County::query()->create($data)->loadCount('cities');
        return (new CountyResource($county))->response()->setStatusCode(201);
    }

    /**
     * @api {patch} /api/counties/:county Megye módosítása
     * @apiName UpdateCounty
     * @apiGroup Counties
     * @apiVersion 1.0.0
     * @apiPermission token
     * @apiHeader {String} Authorization Bearer TOKEN.
     * @apiParam {Number} county A módosítandó megye ID-ja.
     * @apiBody {String} [name] Új név.
     * @apiDescription PUT módszerrel is hívható; részleges frissítés.
     */
    public function update(Request $request, County $county): CountyResource
    {
        $data = $request->validate(['name' => ['sometimes', 'required', 'string', 'max:50']]);
        $county->update($data);
        return new CountyResource($county->refresh()->loadCount('cities'));
    }

    /**
     * @api {delete} /api/counties/:county Megye törlése
     * @apiName DeleteCounty
     * @apiGroup Counties
     * @apiVersion 1.0.0
     * @apiPermission token
     * @apiHeader {String} Authorization Bearer TOKEN.
     * @apiParam {Number} county Megye ID-ja.
     * @apiError (409) HasCities Megyéhez települések tartoznak, ezért nem törölhető.
     * @apiSuccess (204) NoContent Üres válasz.
     */
    public function destroy(County $county): Response|JsonResponse
    {
        if ($county->cities()->exists()) {
            return response()->json([
                'message' => 'A megye nem törölhető, mert települések tartoznak hozzá.',
            ], 409);
        }

        $county->delete();
        return response()->noContent();
    }
}
