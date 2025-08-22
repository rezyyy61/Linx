<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Http\Request;

class PublicVerificationController extends Controller
{
    public function resend(Request $request, EmailVerificationService $service)
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        return $service->resendPublic($data['email']);
    }
}
