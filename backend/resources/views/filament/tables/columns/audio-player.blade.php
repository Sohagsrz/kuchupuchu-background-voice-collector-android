<div class="py-1">
    @php
        $url = $getRecord()?->audio_url;
    @endphp

    @if($url)
        <div class="flex items-center space-x-2">
            <audio controls preload="none" class="h-8 max-w-xs rounded shadow-sm">
                <source src="{{ $url }}" type="audio/mp4">
                Your browser does not support the audio element.
            </audio>
        </div>
    @else
        <span class="text-xs text-gray-400">No file</span>
    @endif
</div>
