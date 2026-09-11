<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SystemLogController extends Controller
{
    /**
     * Display a listing of system logs.
     */
    public function index(Request $request)
    {
        $logPath = storage_path('logs/laravel.log');
        $logs = [];

        if (File::exists($logPath)) {
            $file = new \SplFileObject($logPath, 'r');
            $file->seek(PHP_INT_MAX);
            $lastLine = $file->key();
            
            // Read last 300 lines
            $lines = max(0, $lastLine - 300); 
            $file->seek($lines);

            while (!$file->eof()) {
                $line = trim($file->current());
                if (!empty($line)) {
                    // Parse Laravel log format: [YYYY-MM-DD HH:MM:SS] env.LEVEL: message
                    preg_match('/^\[(.*?)\] (.*?)\.(.*?): (.*)/', $line, $matches);
                    
                    if (count($matches) >= 5) {
                        // Extract a fake component for UI purposes if present, else just 'system'
                        $component = 'system';
                        $message = trim($matches[4]);
                        
                        // If message starts with [component], parse it
                        if (preg_match('/^\[(.*?)\] (.*)/', $message, $compMatches)) {
                            $component = $compMatches[1];
                            $message = $compMatches[2];
                        }

                        $logs[] = [
                            'timestamp' => $matches[1],
                            'environment' => $matches[2],
                            'level' => strtoupper($matches[3]),
                            'component' => $component,
                            'message' => $message,
                        ];
                    } else if (count($logs) > 0) {
                        // Append stack traces/multiline to the previous log
                        $logs[count($logs) - 1]['message'] .= "\n" . $line;
                    }
                }
                $file->next();
            }
        }

        // Return latest logs first
        return response()->json([
            'status' => 'success',
            'data' => array_reverse($logs)
        ]);
    }

    /**
     * Clear the system logs buffer.
     */
    public function destroy()
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (File::exists($logPath)) {
            File::put($logPath, '');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'System logs cleared successfully.'
        ]);
    }
}
