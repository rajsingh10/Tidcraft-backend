<?php

return [

    /*
     * Which URL paths this CORS config applies to.
     * Must explicitly list any non-/api path that needs CORS —
     * this is exactly what was missing before ('login' wasn't listed).
     */
    'paths' => ['api/*', 'login', 'logout', 'sanctum/csrf-cookie', 'up'],

    /*
     * Which HTTP methods are allowed. '*' = all methods (GET, POST, etc).
     * Fine to leave as '*' unless you want to restrict further.
     */
    'allowed_methods' => ['*'],

    /*
     * Which frontend domains are allowed to call this API.
     * Must be an EXACT list (no '*') because supports_credentials is true below.
     * Add every real frontend domain that will call this API.
     */
    'allowed_origins' => [
        'https://dev.tidcraft.com',
        'https://tidcraft.com',
        'https://www.tidcraft.com',
    ],

    /*
     * Regex-based origin matching, for cases like all subdomains.
     * Leave empty unless you specifically need wildcard subdomain matching.
     */
    'allowed_origins_patterns' => [],

    /*
     * Which request headers the browser is allowed to send.
     * '*' covers Content-Type, Authorization, etc. Fine to leave as-is.
     */
    'allowed_headers' => ['*'],

    /*
     * Which response headers JavaScript is allowed to read.
     * Usually empty unless your frontend needs to read custom headers back.
     */
    'exposed_headers' => [],

    /*
     * How long (seconds) the browser can cache a preflight OPTIONS response.
     * 0 = no caching, always re-check. Fine for now; can raise to 3600+ later
     * once everything's stable, to reduce preflight request overhead.
     */
    'max_age' => 0,

    /*
     * MUST be true if your frontend sends cookies or Authorization headers
     * with requests (which this admin panel's session-based login does).
     * This is why allowed_origins above can't use '*'.
     */
    'supports_credentials' => true,

];
