<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaCapiService
{
    /**
     * Send event to Meta Conversions API (CAPI)
     *
     * @param string $eventName 'Lead', 'Schedule', or 'Purchase'
     * @param string|null $eventId
     * @param string|null $email
     * @param string|null $phone
     * @param string|null $name
     * @param string|null $fbp
     * @param string|null $fbc
     * @param Request|null $request
     * @param array $customData Optional custom parameters (e.g. value, currency)
     * @return void
     */
    public static function sendEvent(
        string $eventName,
        ?string $eventId = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $name = null,
        ?string $fbp = null,
        ?string $fbc = null,
        ?Request $request = null,
        array $customData = []
    ): void {
        try {
            $pixelId = config('services.meta.pixel_id') ?? env('META_PIXEL_ID', '2062031277814890');
            $token = config('services.meta.capi_token') ?? env('META_CAPI_TOKEN');

            if (empty($token) || empty($pixelId)) {
                return;
            }

            $req = $request ?: request();

            $cleanPhone = preg_replace('/\D/', '', $phone ?? '');
            $nameParts = array_values(array_filter(explode(' ', trim($name ?? ''))));
            $firstName = $nameParts[0] ?? '';
            $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : '';

            $clientIp = $req ? ($req->header('CF-Connecting-IP') ?? $req->header('X-Forwarded-For') ?? $req->ip()) : null;
            if ($clientIp && str_contains($clientIp, ',')) {
                $clientIp = trim(explode(',', $clientIp)[0]);
            }
            $userAgent = $req ? $req->header('User-Agent') : null;

            $userData = array_filter([
                'client_ip_address' => $clientIp,
                'client_user_agent' => $userAgent,
            ]);

            if (!empty($email)) {
                $userData['em'] = [hash('sha256', strtolower(trim($email)))];
            }
            if (!empty($cleanPhone)) {
                $userData['ph'] = [hash('sha256', $cleanPhone)];
            }
            if (!empty($firstName)) {
                $userData['fn'] = [hash('sha256', strtolower(trim($firstName)))];
            }
            if (!empty($lastName)) {
                $userData['ln'] = [hash('sha256', strtolower(trim($lastName)))];
            }
            if (!empty($fbp)) {
                $userData['fbp'] = $fbp;
            }
            if (!empty($fbc)) {
                $userData['fbc'] = $fbc;
            }

            $sourceUrl = $req ? ($req->header('Referer') ?? $req->header('Origin') ?? 'https://tidcraft.com') : 'https://tidcraft.com';

            $eventPayload = [
                'event_name' => $eventName,
                'event_time' => time(),
                'action_source' => 'website',
                'event_source_url' => $sourceUrl,
                'user_data' => $userData,
            ];

            if (!empty($eventId)) {
                $eventPayload['event_id'] = $eventId;
            }

            if (!empty($customData)) {
                $eventPayload['custom_data'] = $customData;
            }

            $body = [
                'data' => [$eventPayload],
            ];

            $url = "https://graph.facebook.com/v19.0/{$pixelId}/events?access_token={$token}";

            $response = Http::timeout(5)->post($url, $body);

            if (!$response->successful()) {
                Log::warning('Meta CAPI API Error: ' . $response->body());
            }
        } catch (\Throwable $e) {
            Log::error('Meta CAPI Exception: ' . $e->getMessage());
        }
    }
}
