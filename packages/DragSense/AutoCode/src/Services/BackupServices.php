<?php

namespace DragSense\AutoCode\Services;

use DragSense\AutoCode\Http\Exceptions\ApiError;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Illuminate\Support\Facades\Log;

class BackupServices
{
    protected $dataDir;
    protected $backupDir;
    protected $publicDir;

    public function __construct()
    {
        $this->dataDir = storage_path('app/private/data');
        $this->backupDir = storage_path('app/private/backups');
        $this->publicDir = storage_path('app/public');

        // Ensure the backup directory exists
        if (!File::exists($this->backupDir)) {
            try {
                File::makeDirectory($this->backupDir, 0755, true);
            } catch (\Exception $e) {
                Log::error('Failed to create backup directory: ' . $e->getMessage());
                throw new \Exception('Failed to create backup directory.', 500);
            }
        }
    }

    /**
     * Creates a directory and copies files from source to destination.
     *
     * @param string $destinationDir The destination directory path.
     * @param string $sourceDir The source directory path.
     * @param string $dirName The name of the directory to be created.
     * @return void
     * @throws ApiError If the source directory does not exist.
     */
    private function createDirectoryAndCopyFiles($destinationDir, $sourceDir, $dirName)
    {
        $dirPath = $destinationDir . '/' . $dirName;
        File::makeDirectory($dirPath, 0755, true);

        if (!File::exists($sourceDir)) {
            File::deleteDirectory($destinationDir);
            throw new ApiError("{$dirName} folder not found.", 404);
        }

        File::copyDirectory($sourceDir, $dirPath);
    }

    /**
     * Takes a snapshot of the database and saves it to the destination directory.
     *
     * @param string $destinationDir The directory to save the snapshot.
     * @return void
     * @throws ApiError If the database backup fails.
     */
    private function takeSnapshot($destinationDir)
    {
        try {
            $dumpFile = $destinationDir . '/' . env('DB_DATABASE') . '.sql';
            $command = sprintf(
                'mysqldump -u"%s" -p"%s" %s > %s 2>&1',
                env('DB_USERNAME'),
                env('DB_PASSWORD'),
                env('DB_DATABASE'),
                $dumpFile
            );
            $output = shell_exec($command);
            if ($output) {
                File::deleteDirectory($destinationDir);
                throw new ApiError('Database backup failed: ' . $output, 500);
            }
        } catch (\Exception $e) {
            File::deleteDirectory($destinationDir);
            throw new ApiError($e->getMessage() ?: 'Database backup failed.', 500);
        }
    }

    /**
     * Creates a ZIP archive of the backup directory and cleans up the directory.
     *
     * @param string $destinationDir The directory to be zipped.
     * @return void
     * @throws ApiError If ZIP creation fails.
     */
    private function createZipAndCleanUp($destinationDir)
    {
        $zipPath = $destinationDir . '.zip';
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($destinationDir),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($destinationDir) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();
            File::deleteDirectory($destinationDir);
        } else {
            throw new ApiError('Failed to create zip file.', 500);
        }
    }

    /**
     * Extracts a ZIP archive to the specified directory.
     *
     * @param string $destinationDir The directory to extract the ZIP file.
     * @return void
     * @throws ApiError If ZIP extraction fails.
     */
    private function extractZip($destinationDir)
    {
        $zipPath = $destinationDir . '.zip';
        $zip = new ZipArchive;

        if ($zip->open($zipPath) === true) {
            $zip->extractTo($destinationDir);
            $zip->close();
        } else {
            throw new ApiError('Failed to extract zip file.', 500);
        }
    }

    /**
     * Restores a database snapshot from the specified directory.
     *
     * @param string $destinationDir The directory containing the snapshot.
     * @return void
     * @throws ApiError If the database restore fails.
     */
    private function restoreSnapshot($destinationDir)
    {
        try {
            $dumpFile = $destinationDir . '/' . env('DB_DATABASE') . '.sql';

            $command = sprintf(
                'mysql -u"%s" -p"%s" %s < %s 2>&1',
                env('DB_USERNAME'),
                env('DB_PASSWORD'),
                env('DB_DATABASE'),
                $dumpFile
            );
            $output = shell_exec($command);
            if ($output) {
                throw new ApiError('Database restore failed: ' . $output, 500);
            }
        } catch (\Exception $e) {
            File::deleteDirectory($destinationDir);
            throw new ApiError($e->getMessage() ?: 'Database restore failed.', 500);
        }
    }

    /**
     * Installs a backup by extracting it, restoring the database, and copying files.
     *
     * @param string $id The backup identifier.
     * @return array Status of the operation.
     * @throws ApiError If the backup does not exist or restoration fails.
     */
    public function installBackup($id)
    {
        $destinationDir = $this->backupDir . '/' . $id;

        if (!File::exists($destinationDir . '.zip')) {
            throw new ApiError('Backup not found.', 404);
        }

        // Extract the ZIP file
        $this->extractZip($destinationDir);

        // Restore the database
        $this->restoreSnapshot($destinationDir);

        // Copy data directory
        $this->copyDirectory($destinationDir, 'data', $this->dataDir);

        // Copy static directory
        $this->copyDirectory($destinationDir, 'static', $this->publicDir . '/static');

        // Clean up the extracted files
        File::deleteDirectory($destinationDir);

        return ['status' => true];
    }

    /**
     * Copies a directory from the backup to the target directory.
     *
     * @param string $destinationDir The backup directory path.
     * @param string $dirName The name of the directory to copy.
     * @param string $targetDir The target directory path.
     * @return void
     */
    private function copyDirectory($destinationDir, $dirName, $targetDir)
    {
        $dirPath = $destinationDir . '/' . $dirName;
        if (File::exists($dirPath)) {
            File::copyDirectory($dirPath, $targetDir);
        }
    }

    /**
     * Creates a backup by copying files and taking a database snapshot.
     *
     * @param string $id The backup identifier.
     * @return array Status of the operation.
     * @throws ApiError If the backup already exists.
     */
    public function createBackup($id)
    {
        $destinationDir = $this->backupDir . '/' . $id;

        if (File::exists($destinationDir . '.zip')) {
            throw new ApiError('Backup already exists.', 409);
        }

        File::makeDirectory($destinationDir, 0755, true);

        // Copy data directory
        $this->createDirectoryAndCopyFiles($destinationDir, $this->dataDir, 'data');

        // Copy static directory
        $this->createDirectoryAndCopyFiles($destinationDir, $this->publicDir . '/static', 'static');

        // Take a snapshot of the database
        $this->takeSnapshot($destinationDir);

        // Create a ZIP archive and clean up the directory
        $this->createZipAndCleanUp($destinationDir);

        return ['status' => true];
    }

    /**
     * Updates an existing backup by recreating it.
     *
     * @param string $id The backup identifier.
     * @return array Status of the operation.
     * @throws ApiError If the backup does not exist.
     */
    public function updateBackup($id)
    {
        $destinationDir = $this->backupDir . '/' . $id;

        if (!File::exists($destinationDir . '.zip')) {
            throw new ApiError('Backup not found.', 404);
        }

        // Re-create the backup
        return $this->createBackup($id);
    }

    /**
     * Retrieves the path to a backup ZIP file.
     *
     * @param string $id The backup identifier.
     * @return string The path to the backup ZIP file.
     * @throws ApiError If the backup does not exist.
     */
    public function getBackup($id)
    {
        $zipPath = $this->backupDir . '/' . $id . '.zip';

        if (!File::exists($zipPath)) {
            throw new ApiError('Backup not found.', 404);
        }

        return $zipPath;
    }

    /**
     * Deletes a backup ZIP file.
     *
     * @param string $id The backup identifier.
     * @return array Status of the operation.
     * @throws ApiError If the backup does not exist.
     */
    public function deleteBackup($id)
    {
        $zipPath = $this->backupDir . '/' . $id . '.zip';

        if (!File::exists($zipPath)) {
            throw new ApiError('Backup not found.', 404);
        }

        File::delete($zipPath);

        return ['status' => true];
    }
}
