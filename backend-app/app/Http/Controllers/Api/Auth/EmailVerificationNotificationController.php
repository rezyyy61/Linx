<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    public function __construct(private EmailVerificationService $service) {}

    public function __invoke(Request $request): JsonResponse
    {
        return $this->service->resend($request->user());
    }
}
