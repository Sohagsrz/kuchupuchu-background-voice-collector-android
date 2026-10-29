<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recording;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RecordingApiController extends Controller
{
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file',
            'segment_id' => 'required|string',
            'started_at' => 'nullable|string',
            'ended_at' => 'nullable|string',
            'duration_ms' => 'nullable|numeric',
            'checksum' => 'nullable|string',
        ]);

        $segmentId = $request->input('segment_id');
        $bearerToken = $request->bearerToken();

        // Idempotency check: if segment already uploaded, return existing record ID
        $existing = Recording::where('segment_id', $segmentId)->first();
        if ($existing) {
            Log::info("Idempotent upload received for existing segment: {$segmentId}");
            return response()->json([
                'success' => true,
                'segment_id' => $segmentId,
                'recording_id' => 'srv_rec_' . $existing->id,
            ]);
        }

        $file = $request->file('file');
        $filename = $file->getClientOriginalName() ?: ("audio_" . time() . "_{$segmentId}.m4a");

        // Save file to public disk under recordings directory
        $storedPath = $file->storeAs('recordings', $filename, 'public');

        // Checksum verification
        $actualSha256 = hash_file('sha256', Storage::disk('public')->path($storedPath));
        $clientChecksum = $request->input('checksum');

        $startedAt = $request->input('started_at') ? Carbon::parse($request->input('started_at')) : null;
        $endedAt = $request->input('ended_at') ? Carbon::parse($request->input('ended_at')) : null;
        $durationMs = (int) $request->input('duration_ms', 0);

        $recording = Recording::create([
            'segment_id' => $segmentId,
            'filename' => $filename,
            'file_path' => $storedPath,
            'mime_type' => $file->getClientMimeType() ?: 'audio/mp4',
            'size_bytes' => $file->getSize(),
            'duration_ms' => $durationMs,
            'checksum' => $actualSha256,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'device_token' => $bearerToken ? substr($bearerToken, 0, 8) . '...' : null,
            'status' => 'UPLOADED',
        ]);

        Log::info("Successfully stored recording #{$recording->id} ({$filename}, {$recording->size_bytes} bytes)");

        return response()->json([
            'success' => true,
            'segment_id' => $segmentId,
            'recording_id' => 'srv_rec_' . $recording->id,
        ], 200);
    }
}
