<?php

namespace App\Helpers;

class QueueRunner
{
    /**
     * Programmatically starts a background queue worker that processes jobs and then exits.
     * This is useful for environments (like XAMPP or shared hosting) where a persistent
     * Supervisor process is not configured.
     */
    public static function runBackground()
    {
        $artisan = base_path('artisan');
        // --stop-when-empty ensures the worker exits after processing current jobs
        // --timeout=600 gives Cloud Functions deployment enough time to finish GCP builds (2-4 mins)
        $command = "php \"{$artisan}\" queue:work --stop-when-empty --timeout=600 --tries=2";
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows background execution
            pclose(popen("start /B {$command} > NUL 2>&1", "r"));
        } else {
            // Linux/Mac background execution
            exec("{$command} > /dev/null 2>&1 &");
        }
    }
}
