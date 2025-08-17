<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;
use Symfony\Component\HttpFoundation\IpUtils;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // ── Route notifications (یک‌بار در بوت، نه داخل auth)
        $slackHook = (string) config('horizon.slack_webhook_url', '');
        if ($slackHook !== '') {
            $channel = (string) config('horizon.slack_channel', '#general');
            Horizon::routeSlackNotificationsTo($slackHook, $channel);
        }

        $notifyMail = (string) config('horizon.notify_mail', '');
        if ($notifyMail !== '') {
            Horizon::routeMailNotificationsTo($notifyMail);
        }

        // ── Auth callback
        Horizon::auth(function ($request) {
            $enabled = filter_var((string) config('horizon.enabled', true), FILTER_VALIDATE_BOOL);
            if (! $enabled) {
                return false;
            }

            // محیط local همیشه مجاز
            if (app()->environment('local')) {
                return true;
            }

            // اجازه بر اساس IP/CIDR
            $allowedRanges = $this->csvToList((string) config('horizon.ip_allow', ''));
            if (! empty($allowedRanges)) {
                $ip = $request->ip();
                foreach ($allowedRanges as $range) {
                    if ($range !== '' && IpUtils::checkIp($ip, $range)) {
                        return true;
                    }
                }
            }

            // اجازه بر اساس ایمیل‌های مجاز
            if (auth()->check()) {
                $allowedEmails = $this->csvToList((string) config('horizon.allowed_emails', ''));
                if (! empty($allowedEmails) && in_array(auth()->user()->email, $allowedEmails, true)) {
                    return true;
                }
            }

            return false;
        });
    }

    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            $allowedEmails = $this->csvToList((string) config('horizon.allowed_emails', ''));

            return $user && ! empty($allowedEmails) && in_array($user->email, $allowedEmails, true);
        });
    }

    /**
     * تبدیل CSV به آرایه‌ی trim‌شده (خالی‌ها حذف می‌شن)
     *
     * @return array<int, string>
     */
    private function csvToList(string $csv): array
    {
        if ($csv === '') {
            return [];
        }

        $parts = array_map(static fn ($s) => trim($s), explode(',', $csv));

        return array_values(array_filter($parts, static fn ($s) => $s !== ''));
    }
}
