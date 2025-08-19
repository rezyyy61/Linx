<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    public function __construct(private EmailVerificationService $service)
    {
    }

    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        return $this->service->verify($request);
    }
}
