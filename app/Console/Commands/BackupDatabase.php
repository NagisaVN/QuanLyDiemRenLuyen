<?php

namespace App\Console\Commands;

use App\Models\Backup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database';
    protected $description = 'Backup MySQL database and keep the seven newest files.';

    public function handle(): int
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $fileName = 'backup-'.now()->format('Ymd-His').'.sql';
        $relativePath = 'backups/'.$fileName;
        $fullPath = Storage::path($relativePath);
        $laragonDump = 'D:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe';
        $mysqldumpBinary = env('MYSQLDUMP_PATH', File::exists($laragonDump) ? $laragonDump : 'mysqldump');

        File::ensureDirectoryExists(dirname($fullPath));

        $mysqldump = collect([
            $mysqldumpBinary,
            "-h{$host}",
            "-u{$username}",
            $password ? "-p{$password}" : '',
            $database,
            "--result-file={$fullPath}",
        ])->filter()->values()->all();

        $process = new Process($mysqldump);
        $process->setTimeout(300);
        $process->run();

        $backup = Backup::create([
            'file_name' => $fileName,
            'path' => $relativePath,
            'size' => File::exists($fullPath) ? File::size($fullPath) : null,
            'status' => $process->isSuccessful() ? 'success' : 'failed',
            'message' => $process->isSuccessful() ? null : $process->getErrorOutput(),
        ]);

        Backup::latest()->skip(7)->take(PHP_INT_MAX)->get()->each(function (Backup $old) {
            Storage::delete($old->path);
            $old->delete();
        });

        $this->info("Backup {$backup->status}: {$fileName}");

        return $process->isSuccessful() ? self::SUCCESS : self::FAILURE;
    }
}
