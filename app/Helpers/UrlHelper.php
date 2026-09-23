<?php

namespace App\Helpers;

use Illuminate\Http\Request;

class UrlHelper
{
    /**
     * Resolve the frontend base URL dynamically.
     * Supports:
     * - Testing/Dev: https://dev.tidcraft.com (API: https://devapi.tidcraft.com)
     * - Production/Live: https://tidcraft.com (API: https://api.tidcraft.com)
     * - Localhost: http://localhost:3000
     * - Explicit override via FRONTEND_URL in .env
     */
    public static function getFrontendUrl(?Request $request = null): string
    {
        // 1. Explicit environment variable if configured
        $envFrontend = env('FRONTEND_URL');
        if (!empty($envFrontend)) {
            return rtrim($envFrontend, '/');
        }

        $req = $request ?: request();

        // 2. Check Origin header sent by browser on CORS requests
        if ($req) {
            $origin = $req->header('Origin');
            if ($origin) {
                $parsedOrigin = parse_url($origin);
                $host = strtolower($parsedOrigin['host'] ?? '');
                if (str_contains($host, 'dev.tidcraft.com')) {
                    return 'https://dev.tidcraft.com';
                }
                if (str_contains($host, 'tidcraft.com')) {
                    return 'https://tidcraft.com';
                }
                if (str_contains($host, 'localhost') || str_contains($host, '127.0.0.1')) {
                    $port = isset($parsedOrigin['port']) ? ':' . $parsedOrigin['port'] : '';
                    return ($parsedOrigin['scheme'] ?? 'http') . '://' . $host . $port;
                }
            }

            // 3. Check Referer header
            $referer = $req->header('Referer');
            if ($referer) {
                $parsedReferer = parse_url($referer);
                $host = strtolower($parsedReferer['host'] ?? '');
                if (str_contains($host, 'dev.tidcraft.com')) {
                    return 'https://dev.tidcraft.com';
                }
                if (str_contains($host, 'tidcraft.com')) {
                    return 'https://tidcraft.com';
                }
            }

            // 4. Check current Host of API request
            $currentHost = strtolower($req->getHost());
            if (str_contains($currentHost, 'devapi') || str_contains($currentHost, 'dev.')) {
                return 'https://dev.tidcraft.com';
            }
            if (str_contains($currentHost, 'api.tidcraft.com') || (str_contains($currentHost, 'tidcraft.com') && !str_contains($currentHost, 'dev'))) {
                return 'https://tidcraft.com';
            }
        }

        // 5. Check config('app.url')
        $appUrl = config('app.url');
        if ($appUrl) {
            $appHost = strtolower(parse_url($appUrl, PHP_URL_HOST) ?? '');
            if (str_contains($appHost, 'devapi') || str_contains($appHost, 'dev.')) {
                return 'https://dev.tidcraft.com';
            }
            if (str_contains($appHost, 'api.tidcraft.com') || (str_contains($appHost, 'tidcraft.com') && !str_contains($appHost, 'dev'))) {
                return 'https://tidcraft.com';
            }
            if (str_contains($appHost, 'localhost') || str_contains($appHost, '127.0.0.1')) {
                return 'http://localhost:3000';
            }
        }

        // Default fallback to live domain
        return 'https://tidcraft.com';
    }

    /**
     * Get frontend login URL (/login, never /client/login)
     */
    public static function getLoginUrl(?Request $request = null): string
    {
        return self::getFrontendUrl($request) . '/login';
    }

    /**
     * Get frontend contact-us URL
     */
    public static function getContactUrl(?Request $request = null): string
    {
        return self::getFrontendUrl($request) . '/contact-us';
    }

    /**
     * Get frontend client dashboard URL
     */
    public static function getDashboardUrl(?Request $request = null): string
    {
        return self::getFrontendUrl($request) . '/profile';
    }

    /**
     * Resolve the API / backend base URL dynamically.
     * Supports:
     * - Testing/Dev: https://devapi.tidcraft.com
     * - Production/Live: https://api.tidcraft.com
     * - Localhost: config('app.url') or request()->root()
     */
    public static function getApiUrl(?Request $request = null): string
    {
        $envApi = env('API_URL');
        if (!empty($envApi)) {
            return rtrim($envApi, '/');
        }

        $req = $request ?: request();

        if ($req) {
            $origin = $req->header('Origin') ?: $req->header('Referer');
            if ($origin) {
                $host = strtolower(parse_url($origin, PHP_URL_HOST) ?? '');
                if (str_contains($host, 'dev.tidcraft.com') || str_contains($host, 'devapi')) {
                    return 'https://devapi.tidcraft.com';
                }
                if (str_contains($host, 'tidcraft.com')) {
                    return 'https://api.tidcraft.com';
                }
            }

            $currentHost = strtolower($req->getHost());
            if (str_contains($currentHost, 'devapi') || str_contains($currentHost, 'dev.')) {
                return 'https://devapi.tidcraft.com';
            }
            if (str_contains($currentHost, 'api.tidcraft.com') || (str_contains($currentHost, 'tidcraft.com') && !str_contains($currentHost, 'dev'))) {
                return 'https://api.tidcraft.com';
            }
        }

        $appUrl = config('app.url');
        if ($appUrl) {
            $appHost = strtolower(parse_url($appUrl, PHP_URL_HOST) ?? '');
            if (str_contains($appHost, 'devapi') || str_contains($appHost, 'dev.')) {
                return 'https://devapi.tidcraft.com';
            }
            if (str_contains($appHost, 'api.tidcraft.com') || (str_contains($appHost, 'tidcraft.com') && !str_contains($appHost, 'dev'))) {
                return 'https://api.tidcraft.com';
            }
            if (str_contains($appHost, 'localhost') || str_contains($appHost, '127.0.0.1')) {
                return rtrim($appUrl, '/');
            }
        }

        return 'https://api.tidcraft.com';
    }

    /**
     * Resolve a public storage asset URL hosted on the backend API.
     * E.g. /storage/settings/xyz.jpg -> https://devapi.tidcraft.com/storage/settings/xyz.jpg
     */
    public static function getStorageUrl(?string $path = null, ?Request $request = null): string
    {
        if (empty($path)) {
            return self::getApiUrl($request) . '/storage';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return self::correctStorageUrl($path);
        }

        $cleanPath = ltrim($path, '/');
        if (!str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = 'storage/' . $cleanPath;
        }

        return self::getApiUrl($request) . '/' . $cleanPath;
    }

    /**
     * Correct any storage URLs mistakenly pointing to the frontend domain (dev.tidcraft.com or tidcraft.com)
     * so they always point to the working API backend domain (devapi.tidcraft.com or api.tidcraft.com).
     */
    public static function correctStorageUrl(string $content): string
    {
        // Convert any dev frontend storage URLs to devapi backend storage URLs
        $content = preg_replace('#https?://(?:www\.)?dev\.tidcraft\.com/storage/#i', 'https://devapi.tidcraft.com/storage/', $content);

        // Convert any production frontend storage URLs to production api backend storage URLs
        $content = preg_replace('#https?://(?:www\.)?(?<!api\.)tidcraft\.com/storage/#i', 'https://api.tidcraft.com/storage/', $content);

        return $content;
    }
}
