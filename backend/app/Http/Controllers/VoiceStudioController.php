<?php

namespace App\Http\Controllers;

use App\Models\Recording;
use Illuminate\Http\Request;

class VoiceStudioController extends Controller
{
    public function index(Request $request)
    {
        $query = Recording::query()->orderBy('started_at', 'desc');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('filename', 'like', "%{$search}%")
                  ->orWhere('segment_id', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 12);
        if ($perPage <= 0 || $perPage > 50) {
            $perPage = 12;
        }

        $paginator = $query->paginate($perPage);

        $transformRecording = function (Recording $recording) {
            return [
                'id' => $recording->id,
                'filename' => $recording->filename,
                'segment_id' => $recording->segment_id,
                'duration_text' => $recording->formatted_duration,
                'duration_seconds' => (int) ($recording->duration_ms / 1000),
                'size' => $recording->formatted_size,
                'ago_time' => $recording->started_at ? $recording->started_at->diffForHumans() : 'Recently',
                'exact_time' => $recording->started_at ? $recording->started_at->format('M d, Y • H:i:s T') : 'Unknown',
                'url' => $recording->audio_url,
                'status' => $recording->status,
            ];
        };

        $items = collect($paginator->items())->map($transformRecording)->values();

        if ($request->wantsJson() || $request->ajax() || $request->input('format') === 'json') {
            return response()->json([
                'data' => $items,
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ]);
        }

        $totalRecordings = Recording::count();
        $totalBytes = Recording::sum('size_bytes');
        $totalMs = Recording::sum('duration_ms');

        $totalSeconds = (int) ($totalMs / 1000);
        $hours = (int) ($totalSeconds / 3600);
        $minutes = (int) (($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;
        $formattedDuration = $hours > 0
            ? sprintf('%02dh %02dm %02ds', $hours, $minutes, $seconds)
            : sprintf('%02dm %02ds', $minutes, $seconds);

        $sizeFormatted = $totalBytes < 1048576
            ? round($totalBytes / 1024, 1) . ' KB'
            : round($totalBytes / 1048576, 2) . ' MB';

        $latest = Recording::latest('started_at')->first();

        $stats = [
            'totalRecordings' => $totalRecordings,
            'totalDuration' => $formattedDuration,
            'totalSize' => $sizeFormatted,
            'latestTime' => $latest ? $latest->started_at?->diffForHumans() : 'No recordings yet',
        ];

        $initialPagination = [
            'data' => $items,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'has_more' => $paginator->hasMorePages(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
        ];

        return view('studio.index', compact('stats', 'search', 'initialPagination'));
    }
}
