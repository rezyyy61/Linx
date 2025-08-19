<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetService
{
    public function sendLink(Request $request, string $email): array
    {
        try {
            Password::broker()->sendResetLink(['email' => $email]);

            return ['http' => 202, 'message' => 'reset_link_sent'];
        } catch (\Throwable $e) {
            Log::error('password.forgot', ['email' => $email, 'ip' => $request->ip(), 'error' => $e->getMessage()]);

            return ['http' => 500, 'message' => 'mail_send_failed'];
        }
    }

    public function reset(Request $request, array $data): array
    {
        try {
            $status = Password::reset($data, function (User $user, string $password) {
                $user->password = $password;
                $user->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            });

            if ($status === Password::PASSWORD_RESET) {
                return ['http' => 200, 'message' => 'password_reset'];
            }

            return ['http' => 422, 'message' => 'invalid_token'];
        } catch (\Throwable $e) {
            Log::error('password.reset', ['email' => $data['email'] ?? null, 'ip' => $request->ip(), 'error' => $e->getMessage()]);

            return ['http' => 500, 'message' => 'reset_failed'];
        }
    }
}
