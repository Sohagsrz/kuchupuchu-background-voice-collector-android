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

        $recordings = $query->get();

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

        return view('studio.index', compact('recordings', 'stats', 'search'));
    }
}
