<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @hideFromAPIDocumentation
 * Endpoint de gerenciamento de login de usuários
 */
class AuthController extends Controller
{

    /**
     * Get a JWT via given credentials.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticate(Request $request)
    {
        $messages = [
            'email.required' => 'O campo email é obrigatório',
            'password.required' => 'O campo senha é obrigatório',
            'password.min' => 'O campo senha deve conter no mínimo 6 caracteres'
        ];

        $valid = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required|min:6',
        ], $messages);

        if ($valid->fails()) {
            return response()->json(['error' => $valid->messages()->first()], 403);
        }

        $credentials = $request->only(['email', 'password']);

        $token = auth()->attempt($credentials);

        if (!$token) {
            return response()->json(['error' => 'Acesso não autorizado'], 403);
        }

        return $this->respondWithToken($token, auth('api')->user());
    }

    /**
     * Refresh a token.
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        $payload = JWTAuth::manager()->getJWTProvider()->decode(JWTAuth::getToken()->get());
        $exp = Carbon::createFromTimestamp($payload['exp']);
        if ($exp->diffInMinutes(now(), false) > (config('jwt.timeout_ttl') / 60)) {
            return response()->json(['error' => 'Token expirado'], 401);
        }

        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Montagem das permissões do usuário
     * @param User $user
     * @return array Permissões do usuário
     */
    protected function buildRoles(User $user)
    {
        return $user->roles->map(function ($item) {
            return $item->makeHidden('permissions');
        });
    }

    protected function buildPermissions(User $user): array
    {
        return $user->getAllPermissions()->unique('id')->all();
    }

    /**
     * Get the token array structure.
     * @param string $token
     * @param User|null $user
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken(string $token, ?User $user = null)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
//        'all_permissions' => empty($user) ? null : $this->buildPermissions($user),
//        'roles' => empty($user) ? null : $this->buildRoles($user),
    }
}
