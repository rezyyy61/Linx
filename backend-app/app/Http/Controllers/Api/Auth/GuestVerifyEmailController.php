<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Http\Request;

class GuestVerifyEmailController extends Controller
{
    public function __invoke(Request $request, $id, $hash, EmailVerificationService $service)
    {
        return $service->verifySigned($request, (int) $id, (string) $hash);
    }
}
