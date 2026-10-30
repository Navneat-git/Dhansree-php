<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixUploadLimitsCommand extends Command
{
    protected $signature = 'video-studio:fix-upload-limits';
    protected $description = 'Inspect and configure PHP & Nginx upload limits for Video Studio';

    public function handle(): int
    {
        $this->info("── Inspecting PHP Upload Limits for Video Studio ──");

        $cliUpload = ini_get('upload_max_filesize');
        $cliPost = ini_get('post_max_size');
        $cliMemory = ini_get('memory_limit');

        $this->line("Current CLI upload_max_filesize : {$cliUpload}");
        $this->line("Current CLI post_max_size       : {$cliPost}");
        $this->line("Current CLI memory_limit        : {$cliMemory}");
        $this->line("Loaded CLI php.ini              : " . (php_ini_loaded_file() ?: 'None'));

        $this->newLine();
        $this->info("Scanning candidate PHP-FPM and Web php.ini files on system...");

        $candidates = [
            '/etc/php/8.3/apache2/php.ini',
            '/etc/php/8.3/fpm/php.ini',
            '/etc/php/8.2/apache2/php.ini',
            '/etc/php/8.2/fpm/php.ini',
            '/etc/php/8.1/apache2/php.ini',
            '/etc/php/8.1/fpm/php.ini',
            '/etc/php/8.3/cli/php.ini',
        ];

        $foundAny = false;
        foreach ($candidates as $iniPath) {
            if (file_exists($iniPath)) {
                $foundAny = true;
                $content = file_get_contents($iniPath);
                preg_match('/^upload_max_filesize\s*=\s*(.*)$/m', $content, $mUpload);
                preg_match('/^post_max_size\s*=\s*(.*)$/m', $content, $mPost);

                $upVal = trim($mUpload[1] ?? 'unknown');
                $postVal = trim($mPost[1] ?? 'unknown');

                $this->line("  [Found] {$iniPath}");
                $this->line("          upload_max_filesize = {$upVal}");
                $this->line("          post_max_size       = {$postVal}");

                if (is_writable($iniPath)) {
                    $newContent = preg_replace('/^upload_max_filesize\s*=.*$/m', 'upload_max_filesize = 100M', $content);
                    $newContent = preg_replace('/^post_max_size\s*=.*$/m', 'post_max_size = 100M', $newContent);
                    file_put_contents($iniPath, $newContent);
                    $this->info("          ✓ Automatically updated to 100M!");
                }
            }
        }

        // Ensure public/.user.ini is present
        $userIniPath = public_path('.user.ini');
        $userIniContent = "; PHP User Configuration for Web Server & PHP-FPM\nupload_max_filesize = 100M\npost_max_size = 100M\nmemory_limit = 512M\nmax_execution_time = 300\nmax_input_time = 300\n";
        File::put($userIniPath, $userIniContent);
        $this->info("✓ Created/Updated public/.user.ini with 100M limit.");

        $this->newLine();
        $this->info("── Quick 1-Step Fix for Apache2 on Lightsail (Ubuntu) ──");
        $this->line("Run these commands to apply 100M upload limit to Apache2 and PHP:");
        $this->newLine();
        $this->warn("sudo sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 100M/' /etc/php/8.3/apache2/php.ini 2>/dev/null || true");
        $this->warn("sudo sed -i 's/^post_max_size = .*/post_max_size = 100M/' /etc/php/8.3/apache2/php.ini 2>/dev/null || true");
        $this->warn("sudo sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 100M/' /etc/php/8.3/fpm/php.ini 2>/dev/null || true");
        $this->warn("sudo sed -i 's/^post_max_size = .*/post_max_size = 100M/' /etc/php/8.3/fpm/php.ini 2>/dev/null || true");
        $this->warn("sudo systemctl restart apache2");
        $this->warn("sudo systemctl restart php8.3-fpm 2>/dev/null || true");
        $this->newLine();

        return 0;
    }
}
