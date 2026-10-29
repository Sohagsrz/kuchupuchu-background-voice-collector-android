<?php

namespace App\Filament\Pages;

use App\Models\Recording;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class VoiceStudio extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';

    protected static ?string $navigationLabel = 'Voice Player Studio';

    protected static ?string $title = 'Voice Player Studio';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.voice-studio';

    public function getRecordings(): Collection
    {
        return Recording::orderBy('started_at', 'desc')->get();
    }

    public function getStats(): array
    {
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

        return [
            'totalRecordings' => $totalRecordings,
            'totalDuration' => $formattedDuration,
            'totalSize' => $sizeFormatted,
            'latestTime' => $latest ? $latest->started_at?->diffForHumans() : 'No recordings yet',
        ];
    }

    protected function getViewData(): array
    {
        return [
            'stats' => $this->getStats(),
            'recordings' => $this->getRecordings(),
        ];
    }
}
