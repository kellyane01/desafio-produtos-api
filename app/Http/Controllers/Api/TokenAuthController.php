<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class TokenAuthController extends Controller
{
    /**
     * @group Autenticação
     * Realiza o login e gera um token de acesso para futuras requisições autenticadas.
     *
     * @unauthenticated
     * @response 200 {"token_type":"Bearer","access_token":"1|pC6m2GdEXEMPLO"}
     * @response 422 {"message":"The given data was invalid.","errors":{"email":["Estas credenciais não conferem."]}}
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $request->ensureIsNotRateLimited();

        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($request->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($request->throttleKey());

        $token = $user->createToken('api_token');

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
        ]);
    }

    /**
     * @group Autenticação
     * Revoga o token de acesso atualmente em uso.
     *
     * @response 204 {}
     */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->noContent();
    }

    /**
     * @group Autenticação
     * Retorna os dados do usuário autenticado.
     *
     * @responseField id integer Identificador do usuário autenticado.
     * @responseField name string Nome do usuário.
     * @responseField email string Endereço de e-mail.
     * @responseField created_at string Data de criação do cadastro em formato ISO 8601.
     * @responseField updated_at string Data da última atualização em formato ISO 8601.
     *
     * @response 200 {"id":1,"name":"Administrador","email":"admin@example.com","created_at":"2024-01-10T14:32:11Z","updated_at":"2024-05-18T09:21:44Z"}
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
