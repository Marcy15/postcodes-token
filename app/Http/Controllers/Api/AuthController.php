<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * @api {post} /api/login Bejelentkezés (tananyag-kompatibilis útvonal)
     * @apiName LegacyLogin
     * @apiGroup Auth
     * @apiVersion 1.0.0
     * @apiDescription Az /api/auth/login végponttal azonos működés. Rate limit: 10 kérés/perc.
     * @apiBody {String} email Admin email címe.
     * @apiBody {String} password Admin jelszava.
     * @apiSuccess {String} token Sanctum Bearer token.
     */
    /**
     * @api {post} /api/auth/login Bejelentkezés és token igénylése
     * @apiName Login
     * @apiGroup Auth
     * @apiVersion 1.0.0
     * @apiDescription Admin belépés. Nincs nyilvános regisztráció. A jelszó legfeljebb 10 próbálkozással tesztelhető percenként.
     * @apiBody {String} email Az admin email címe.
     * @apiBody {String} password Az admin jelszava.
     * @apiSuccess {String} token Egyszer megjelenített Sanctum Bearer token.
     * @apiSuccess {String} token_type Bearer.
     * @apiSuccess {Object} user A felhasználó nyilvános adatai.
     * @apiError (401) InvalidCredentials Hibás belépési adatok.
     * @apiExample {curl} Példa:
     *     curl -X POST http://127.0.0.1:8000/api/auth/login -H "Content-Type: application/json" -d '{"email":"admin@example.local","password":"SAJAT_JELSZO"}'
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Hibás email cím vagy jelszó.'], 401);
        }

        $token = $user->createToken('postcodes-api', ['*'], now()->addDays(7));

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ]);
    }

    /**
     * @api {get} /api/auth/me Jelenlegi felhasználó
     * @apiName Me
     * @apiGroup Auth
     * @apiVersion 1.0.0
     * @apiHeader {String} Authorization Bearer TOKEN.
     * @apiPermission token
     * @apiSuccess {Object} user A bejelentkezett felhasználó adatai.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json(['user' => [
            'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
        ]]);
    }

    /**
     * @api {post} /api/auth/logout Token visszavonása
     * @apiName Logout
     * @apiGroup Auth
     * @apiVersion 1.0.0
     * @apiHeader {String} Authorization Bearer TOKEN.
     * @apiPermission token
     * @apiSuccess {String} message Kijelentkezés megtörtént.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Sikeres kijelentkezés.']);
    }
}
