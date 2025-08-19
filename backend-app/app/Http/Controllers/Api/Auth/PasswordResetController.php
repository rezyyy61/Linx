<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    public function __construct(private PasswordResetService $service) {}

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $res = $this->service->sendLink($request, (string) $request->input('email'));

        return response()->json(['message' => $res['message']], (int) $res['http']);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->only('email', 'password', 'password_confirmation', 'token');
        $res = $this->service->reset($request, $data);

        return response()->json(['message' => $res['message']], (int) $res['http']);
    }
}
