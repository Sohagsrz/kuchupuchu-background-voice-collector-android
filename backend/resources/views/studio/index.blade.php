<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KuchuPuchu • Voice Player Studio</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
</head>
<body class="bg-[#0b0f19] text-gray-100 min-h-screen font-sans selection:bg-amber-500 selection:text-black pb-36"
      x-data="audioStudio()" x-init="init()">

    <!-- Top Navigation Bar -->
    <nav class="sticky top-0 z-40 backdrop-blur-xl bg-[#0b0f19]/80 border-b border-gray-800/80 px-4 lg:px-8 py-3.5 transition-all">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <!-- Brand & Tagline -->
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-amber-300 p-0.5 shadow-lg shadow-amber-500/20 flex items-center justify-center">
                    <div class="w-full h-full bg-[#0b0f19] rounded-[10px] flex items-center justify-center text-amber-400 font-extrabold text-lg">
                        🐼
                    </div>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-extrabold text-lg tracking-tight bg-gradient-to-r from-white via-gray-100 to-gray-400 bg-clip-text text-transparent">
                            KuchuPuchu
                        </span>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20">
                            Voice Studio
                        </span>
                    </div>
                    <p class="text-xs text-gray-400">Offline Upload Queue • Background Audio Vault</p>
                </div>
            </div>

            <!-- Header Actions -->
            <div class="flex items-center space-x-3">
                @auth
                    <div class="hidden sm:flex items-center space-x-2 px-3 py-1.5 rounded-xl bg-gray-800/60 border border-gray-700/50 text-xs text-gray-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span class="font-medium">{{ auth()->user()->name ?? 'Admin' }}</span>
                    </div>

                    <a href="/admin" class="flex items-center space-x-2 text-xs font-semibold px-3.5 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 text-amber-300 transition shadow-sm hover:scale-105 active:scale-95">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>Filament Admin</span>
                    </a>

                    <form method="POST" action="/admin/logout" class="inline">
                        @csrf
                        <button type="submit" class="p-2 rounded-xl bg-gray-800/80 hover:bg-rose-500/20 hover:text-rose-400 hover:border-rose-500/30 border border-gray-700/60 text-gray-400 transition" title="Log Out">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                        </button>
                    </form>
                @endauth

                <button @click="refreshPage()" class="p-2 rounded-xl bg-gray-800/80 hover:bg-gray-700/80 border border-gray-700/60 text-gray-300 transition" title="Refresh">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-4 lg:px-8 pt-8 space-y-8">

        <!-- Metrics Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="backdrop-blur-md bg-gray-900/60 border border-gray-800/80 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-amber-500/30 transition">
                <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-amber-500/10 rounded-full blur-xl group-hover:bg-amber-500/20 transition"></div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Recordings</span>
                    <span class="text-amber-400">🎙</span>
                </div>
                <p class="text-3xl font-extrabold text-white mt-2">{{ $stats['totalRecordings'] }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Uploaded 60s segments</p>
            </div>

            <div class="backdrop-blur-md bg-gray-900/60 border border-gray-800/80 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-emerald-500/30 transition">
                <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-emerald-500/10 rounded-full blur-xl group-hover:bg-emerald-500/20 transition"></div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Duration</span>
                    <span class="text-emerald-400">⏱</span>
                </div>
                <p class="text-3xl font-extrabold text-white mt-2">{{ $stats['totalDuration'] }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Total audio duration</p>
            </div>

            <div class="backdrop-blur-md bg-gray-900/60 border border-gray-800/80 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-blue-500/30 transition">
                <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-blue-500/10 rounded-full blur-xl group-hover:bg-blue-500/20 transition"></div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Storage</span>
                    <span class="text-blue-400">💾</span>
                </div>
                <p class="text-3xl font-extrabold text-white mt-2">{{ $stats['totalSize'] }}</p>
                <p class="text-[11px] text-gray-500 mt-1">AAC-LC audio files</p>
            </div>

            <div class="backdrop-blur-md bg-gray-900/60 border border-gray-800/80 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-purple-500/30 transition">
                <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-purple-500/10 rounded-full blur-xl group-hover:bg-purple-500/20 transition"></div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Latest Activity</span>
                    <span class="text-purple-400">⚡</span>
                </div>
                <p class="text-lg font-bold text-gray-200 mt-3 truncate">{{ $stats['latestTime'] }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Automatic sync</p>
            </div>
        </div>

        <!-- Filter & Search Section -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-extrabold tracking-tight text-white flex items-center space-x-2">
                    <span>Audio Recordings</span>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-gray-800 text-gray-300 border border-gray-700">
                        {{ count($recordings) }}
                    </span>
                </h2>
                <p class="text-xs text-gray-400 mt-0.5">Stream, seek, download, or inspect captured audio segments</p>
            </div>

            <!-- Live Search Bar -->
            <div class="w-full sm:w-80 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input 
                    type="text" 
                    x-model="searchQuery" 
                    placeholder="Search by filename or segment ID..." 
                    class="w-full pl-10 pr-4 py-2.5 text-xs bg-gray-900/90 border border-gray-800 rounded-xl focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500/50 focus:outline-none text-gray-200 placeholder-gray-500 transition shadow-inner">
            </div>
        </div>

        <!-- Recordings Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($recordings as $recording)
                @php
                    $audioUrl = $recording->audio_url;
                    $startedAt = $recording->started_at ? $recording->started_at->format('M d, Y • H:i:s') : 'Unknown';
                @endphp

                <div 
                    x-show="!searchQuery || '{{ strtolower($recording->filename . ' ' . $recording->segment_id) }}'.includes(searchQuery.toLowerCase())"
                    class="backdrop-blur-md bg-gray-900/70 border rounded-2xl p-5 shadow-sm transition-all duration-200 group hover:shadow-md hover:scale-[1.01]"
                    :class="currentTrack && currentTrack.id === {{ $recording->id }} 
                        ? 'border-amber-500/80 bg-amber-500/5 ring-1 ring-amber-500/30' 
                        : 'border-gray-800/80 hover:border-gray-700/80'">
                    
                    <div class="flex items-start justify-between gap-3">
                        <!-- Play / Pause Icon Button -->
                        <button 
                            type="button" 
                            @click="playTrack({
                                id: {{ $recording->id }},
                                filename: '{{ $recording->filename }}',
                                segment_id: '{{ $recording->segment_id }}',
                                duration: '{{ $recording->formatted_duration }}',
                                size: '{{ $recording->formatted_size }}',
                                started_at: '{{ $startedAt }}',
                                url: '{{ $audioUrl }}'
                            })"
                            class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition shadow-md hover:scale-105 active:scale-95"
                            :class="currentTrack && currentTrack.id === {{ $recording->id }} && isPlaying 
                                ? 'bg-amber-500 text-gray-950 ring-4 ring-amber-500/20' 
                                : 'bg-gray-800/90 group-hover:bg-amber-500 group-hover:text-gray-950 text-gray-300'">
                            <template x-if="currentTrack && currentTrack.id === {{ $recording->id }} && isPlaying">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M6.75 5.25a.75.75 0 01.75.75v12a.75.75 0 01-1.5 0v-12a.75.75 0 01.75-.75zm9 0a.75.75 0 01.75.75v12a.75.75 0 01-1.5 0v-12a.75.75 0 01.75-.75z" clip-rule="evenodd" /></svg>
                            </template>
                            <template x-if="!(currentTrack && currentTrack.id === {{ $recording->id }} && isPlaying)">
                                <svg class="w-6 h-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M4.5 5.653c0-1.426 1.529-2.33 2.779-1.643l11.54 6.348c1.295.712 1.295 2.573 0 3.285L7.28 19.991c-1.25.687-2.779-.217-2.779-1.643V5.653z" clip-rule="evenodd" /></svg>
                            </template>
                        </button>

                        <div class="min-w-0 flex-1">
                            <h4 class="text-sm font-bold text-gray-100 truncate group-hover:text-amber-400 transition" title="{{ $recording->filename }}">
                                {{ $recording->filename }}
                            </h4>
                            <p class="text-[11px] font-mono text-gray-400 mt-0.5 truncate" title="Segment UUID: {{ $recording->segment_id }}">
                                ID: {{ substr($recording->segment_id, 0, 16) }}…
                            </p>
                        </div>

                        <!-- Status Badge -->
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shrink-0">
                            {{ $recording->status }}
                        </span>
                    </div>

                    <!-- Audio Metadata Chips -->
                    <div class="mt-4 pt-3 border-t border-gray-800/80 flex items-center justify-between text-xs text-gray-400 font-mono">
                        <div class="flex items-center space-x-3">
                            <span class="flex items-center space-x-1 text-gray-300">
                                <span>⏱</span>
                                <span>{{ $recording->formatted_duration }}</span>
                            </span>
                            <span class="flex items-center space-x-1">
                                <span>💾</span>
                                <span>{{ $recording->formatted_size }}</span>
                            </span>
                        </div>

                        <!-- Download Button -->
                        <a 
                            href="{{ $audioUrl }}" 
                            download="{{ $recording->filename }}"
                            class="p-1.5 rounded-lg hover:bg-gray-800 hover:text-amber-400 text-gray-400 transition"
                            title="Download .m4a audio file">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                        </a>
                    </div>

                    <div class="mt-2 text-[10px] text-gray-500 font-mono truncate">
                        📅 {{ $startedAt }} UTC
                    </div>
                </div>
            @empty
                <div class="col-span-full py-20 text-center text-gray-500">
                    <p class="text-4xl">🎙</p>
                    <p class="text-lg font-bold text-gray-300 mt-2">No recordings found</p>
                    <p class="text-xs text-gray-500 mt-1">Start recording from the KuchuPuchu Android app to stream audio segments here.</p>
                </div>
            @endforelse
        </div>
    </main>

    <!-- Master Bottom Floating Audio Player Bar -->
    <div 
        class="fixed bottom-4 inset-x-4 max-w-5xl mx-auto z-50 backdrop-blur-2xl bg-gray-900/95 border border-gray-700/80 rounded-2xl p-4 shadow-2xl transition-all duration-300"
        :class="currentTrack ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8 pointer-events-none'">
        
        <!-- Hidden Audio Element -->
        <audio 
            x-ref="audioPlayer"
            @timeupdate="currentTime = $el.currentTime"
            @loadedmetadata="duration = $el.duration"
            @ended="isPlaying = false"
            @play="isPlaying = true"
            @pause="isPlaying = false">
        </audio>

        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <!-- Currently Playing Info -->
            <div class="flex items-center space-x-3 w-full md:w-1/4 min-w-0">
                <div class="w-12 h-12 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0">
                    <template x-if="isPlaying">
                        <div class="flex items-end space-x-1 h-5">
                            <span class="w-1 bg-amber-400 animate-pulse h-3 rounded"></span>
                            <span class="w-1 bg-amber-400 animate-pulse h-5 rounded" style="animation-delay: 150ms;"></span>
                            <span class="w-1 bg-amber-400 animate-pulse h-2 rounded" style="animation-delay: 300ms;"></span>
                            <span class="w-1 bg-amber-400 animate-pulse h-4 rounded" style="animation-delay: 75ms;"></span>
                        </div>
                    </template>
                    <template x-if="!isPlaying">
                        <span class="text-xl">🎵</span>
                    </template>
                </div>

                <div class="min-w-0">
                    <h5 class="text-xs font-bold text-white truncate" x-text="currentTrack ? currentTrack.filename : ''"></h5>
                    <p class="text-[10px] text-amber-400/90 font-mono truncate" x-text="currentTrack ? `${currentTrack.duration} • ${currentTrack.size}` : ''"></p>
                </div>
            </div>

            <!-- Central Controls & Scrubber -->
            <div class="flex flex-col items-center w-full md:w-1/2 space-y-1.5">
                <div class="flex items-center space-x-3">
                    <button type="button" @click="skip(-10)" class="text-gray-400 hover:text-white transition p-1" title="Rewind 10 seconds">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z" /></svg>
                    </button>

                    <button type="button" @click="togglePlay()" class="w-10 h-10 rounded-full bg-amber-500 hover:bg-amber-400 text-gray-950 font-bold flex items-center justify-center shadow-lg hover:scale-105 active:scale-95 transition">
                        <template x-if="!isPlaying">
                            <svg class="w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M4.5 5.653c0-1.426 1.529-2.33 2.779-1.643l11.54 6.348c1.295.712 1.295 2.573 0 3.285L7.28 19.991c-1.25.687-2.779-.217-2.779-1.643V5.653z" clip-rule="evenodd" /></svg>
                        </template>
                        <template x-if="isPlaying">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M6.75 5.25a.75.75 0 01.75.75v12a.75.75 0 01-1.5 0v-12a.75.75 0 01.75-.75zm9 0a.75.75 0 01.75.75v12a.75.75 0 01-1.5 0v-12a.75.75 0 01.75-.75z" clip-rule="evenodd" /></svg>
                        </template>
                    </button>

                    <button type="button" @click="skip(10)" class="text-gray-400 hover:text-white transition p-1" title="Forward 10 seconds">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z" /></svg>
                    </button>
                </div>

                <!-- Scrubber -->
                <div class="w-full flex items-center space-x-2 text-[11px] font-mono text-gray-400">
                    <span class="w-9 text-right" x-text="formatTime(currentTime)"></span>
                    <div class="relative w-full h-1.5 bg-gray-800 hover:bg-gray-700 rounded-full cursor-pointer overflow-hidden" @click="seek($event)">
                        <div class="h-full bg-gradient-to-r from-amber-500 to-amber-300 rounded-full transition-all duration-75" :style="`width: ${(currentTime / (duration || 1)) * 100}%`"></div>
                    </div>
                    <span class="w-9" x-text="formatTime(duration)"></span>
                </div>
            </div>

            <!-- Volume & Speed Controls -->
            <div class="flex items-center justify-end space-x-3 w-full md:w-1/4">
                <div class="flex items-center space-x-1.5">
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                    </svg>
                    <input type="range" min="0" max="1" step="0.05" x-model="volume" class="w-16 accent-amber-500 cursor-pointer h-1 bg-gray-800 rounded-lg">
                </div>

                <select x-model="playbackRate" class="bg-gray-800/90 text-[11px] text-gray-200 border border-gray-700 rounded-lg px-2 py-1 focus:ring-1 focus:ring-amber-500 focus:outline-none">
                    <option value="0.75">0.75x</option>
                    <option value="1.0" selected>1.0x</option>
                    <option value="1.25">1.25x</option>
                    <option value="1.5">1.5x</option>
                    <option value="2.0">2.0x</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Alpine.js Audio Studio Logic -->
    <script>
        function audioStudio() {
            return {
                currentTrack: null,
                isPlaying: false,
                currentTime: 0,
                duration: 0,
                volume: 1.0,
                playbackRate: 1.0,
                searchQuery: '',

                init() {
                    this.$watch('volume', val => {
                        if (this.$refs.audioPlayer) this.$refs.audioPlayer.volume = val;
                    });
                    this.$watch('playbackRate', val => {
                        if (this.$refs.audioPlayer) this.$refs.audioPlayer.playbackRate = val;
                    });
                },

                playTrack(track) {
                    if (this.currentTrack && this.currentTrack.id === track.id) {
                        this.togglePlay();
                        return;
                    }
                    this.currentTrack = track;
                    this.$nextTick(() => {
                        const player = this.$refs.audioPlayer;
                        if (player) {
                            player.src = track.url;
                            player.play();
                            this.isPlaying = true;
                        }
                    });
                },

                togglePlay() {
                    const player = this.$refs.audioPlayer;
                    if (!player) return;
                    if (this.isPlaying) {
                        player.pause();
                        this.isPlaying = false;
                    } else {
                        player.play();
                        this.isPlaying = true;
                    }
                },

                seek(e) {
                    const player = this.$refs.audioPlayer;
                    if (!player || !this.duration) return;
                    const rect = e.currentTarget.getBoundingClientRect();
                    const clickPos = (e.clientX - rect.left) / rect.width;
                    player.currentTime = clickPos * this.duration;
                },

                skip(seconds) {
                    const player = this.$refs.audioPlayer;
                    if (!player) return;
                    player.currentTime = Math.max(0, Math.min(this.duration, player.currentTime + seconds));
                },

                formatTime(seconds) {
                    if (isNaN(seconds) || seconds < 0) return '00:00';
                    const m = Math.floor(seconds / 60);
                    const s = Math.floor(seconds % 60);
                    return `${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`;
                },

                refreshPage() {
                    window.location.reload();
                }
            }
        }
    </script>
</body>
</html>
