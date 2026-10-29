<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Recording extends Model
{
    use HasFactory;

    protected $fillable = [
        'segment_id',
        'filename',
        'device_name',
        'file_path',
        'mime_type',
        'size_bytes',
        'duration_ms',
        'checksum',
        'started_at',
        'ended_at',
        'device_token',
        'status',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'size_bytes' => 'integer',
        'duration_ms' => 'integer',
    ];

    public function getAudioUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function getFormattedDurationAttribute(): string
    {
        $seconds = (int) ($this->duration_ms / 1000);
        $minutes = (int) ($seconds / 60);
        $remSeconds = $seconds % 60;
        return sprintf('%02d:%02d', $minutes, $remSeconds);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size_bytes;
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return round($bytes / 1048576, 2) . ' MB';
    }
}
