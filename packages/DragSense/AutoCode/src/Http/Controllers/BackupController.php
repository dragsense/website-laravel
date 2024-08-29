<?php

namespace DragSense\AutoCode\Http\Controllers;

use DragSense\AutoCode\Http\Exceptions\ApiError;
use DragSense\AutoCode\Services\BackupServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\JsonResponse;

class BackupController extends Controller
{
    // Dependency injection for the BackupServices
    protected $backupServices;

    public function __construct(BackupServices $backupServices)
    {
        $this->backupServices = $backupServices;
    }

    /**
     * Stream a backup file for download.
     *
     * @param string $id The ID of the backup to retrieve.
     * @return StreamedResponse
     * @throws ApiError
     */
    public function getBackup($id): StreamedResponse
    {
        // Retrieve the file path from the backup service
        $folderPath = $this->backupServices->getBackup($id);
        $folderPath = str_replace('\\', '/', $folderPath); // Normalize path

        // Log the full path for debugging
        \Log::info('Backup folder path: ' . $folderPath);

        // Check if the file exists before proceeding
        if (!file_exists($folderPath)) {
            throw new ApiError("File not found at location.", 404);
        }

        // Get the file size safely
        try {
            $fileSize = filesize($folderPath);
        } catch (\Exception $e) {
            throw new ApiError("Unable to retrieve the file size for file at location.", 404);
        }

        // Stream the file for download
        return response()->stream(function () use ($folderPath) {
            $stream = fopen($folderPath, 'rb');
            if ($stream === false) {
                throw new ApiError("Unable to open file for reading.", 404);
            }
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => 'application/zip',
            'Content-Length' => $fileSize,
            'Content-Disposition' => 'attachment; filename="backup-' . $id . '-' . now()->format('Y-m-d') . '.zip"',
        ]);
    }

    /**
     * Create a new backup.
     *
     * @param Request $request The request containing backup data.
     * @return JsonResponse
     * @throws ApiError
     */
    public function createBackup(Request $request): JsonResponse
    {
        $requestData = $request->all();

        // Ensure the backup ID is provided
        if (!isset($requestData['_id'])) {
            throw new ApiError('Backup ID is required.', 400);
        }

        $id = $requestData['_id'];

        // Create the backup and return the response
        $backup = $this->backupServices->createBackup($id);
        return response()->json(['backup' => $backup], 201);
    }

    /**
     * Update an existing backup.
     *
     * @param Request $request The request containing update data.
     * @param string $id The ID of the backup to update.
     * @return JsonResponse
     */
    public function updateBackup(Request $request, $id): JsonResponse
    {
        // Update the backup and return the response
        $backup = $this->backupServices->updateBackup($id);
        return response()->json(['backup' => $backup]);
    }

    /**
     * Delete a backup.
     *
     * @param string $id The ID of the backup to delete.
     * @return JsonResponse
     */
    public function deleteBackup($id): JsonResponse
    {
        // Delete the backup and return a no-content response
        $this->backupServices->deleteBackup($id);
        return response()->json(null, 204);
    }

    /**
     * Install a backup.
     *
     * @param string $id The ID of the backup to install.
     * @return JsonResponse
     */
    public function installBackup($id): JsonResponse
    {
        // Install the backup and return the response
        $backup = $this->backupServices->installBackup($id);
        return response()->json(['backup' => $backup]);
    }
}
