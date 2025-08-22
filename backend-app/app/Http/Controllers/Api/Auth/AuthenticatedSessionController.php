<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private AuthService $auth) {}

    public function store(LoginRequest $request): JsonResponse
    {
        $email = (string) $request->input('email');
        $password = (string) $request->input('password');
        $remember = (bool) $request->boolean('remember');

        $result = $this->auth->login($request, $email, $password, $remember);

        if (isset($result['error'])) {
            $payload = ['message' => $result['error']];
            if (isset($result['code'])) {
                $payload['code'] = $result['code'];
            }

            return response()->json($payload, (int) $result['http']);
        }

        return response()->json(['user' => $result['user']], (int) $result['http']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->auth->me($request)]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->auth->logout($request);

        return response()->json(['ok' => true]);
    }
}
