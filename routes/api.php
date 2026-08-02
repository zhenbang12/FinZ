<?php

use Illuminate\Support\Facades\Route;

Route::get('/debug-recovery', function () {
    $output = "=== DEEP DATABASE RECOVERY SEARCH ===\n";
    $searchDirs = ['/app/database', '/app/storage', '/tmp', '/var/tmp', '/app'];
    $foundFiles = [];
    foreach ($searchDirs as $dir) {
        if (is_dir($dir)) {
            try {
                $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
                foreach ($rii as $file) {
                    if ($file->isDir()) continue;
                    $path = $file->getPathname();
                    if (preg_match('/\.(sqlite|db|bak|sql)/i', $path)) {
                        $size = filesize($path);
                        $foundFiles[] = "$path ($size bytes)";
                        try {
                            $pdo = new \PDO("sqlite:" . $path);
                            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(\PDO::FETCH_COLUMN);
                            $foundFiles[] = "   -> Tables: " . implode(', ', $tables);
                            if (in_array('transactions', $tables)) {
                                $txCount = $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
                                $foundFiles[] = "   -> Transactions Count: " . $txCount;
                            }
                        } catch (\Throwable $t) {}
                    }
                }
            } catch (\Throwable $e) {}
        }
    }
    $output .= implode("\n", array_unique($foundFiles)) . "\n";
    return response($output, 200)->header('Content-Type', 'text/plain');
});
