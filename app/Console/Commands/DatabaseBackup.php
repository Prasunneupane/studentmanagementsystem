<?php

namespace App\Console\Commands;

use File;
use Illuminate\Console\Command;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Take a backup of the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host');

        $backupPath = database_path('backup');
        $fileName = 'db_backup_' . now()->format('Y_m_d_His') . '.sql';
        $fullPath = $backupPath . '/' . $fileName;

        // Ensure directory exists
        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        $mysqldump = 'D:\xampp\mysql\bin\mysqldump.exe';

        $command = "\"$mysqldump\" -h $dbHost -u $dbUser -p$dbPass $dbName > \"$fullPath\"";

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Database backup failed');
            return Command::FAILURE;
        } 

        $this->info('Database backup created successfully!');
        return Command::SUCCESS;
    }
}
