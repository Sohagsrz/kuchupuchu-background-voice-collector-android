<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>KuchuPuchu • Voice Player Studio</title>
    <!-- Vite Compiled Production Tailwind CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
</head>
<body class="bg-[#0b0f19] text-gray-100 min-h-screen font-sans selection:bg-amber-500 selection:text-black pb-48 sm:pb-36"
      x-data="audioStudio({{ Js::from($initialPagination) }})" x-init="init()">

    <!-- Top Navigation Bar -->
    <nav class="sticky top-0 z-40 backdrop-blur-xl bg-[#0b0f19]/85 border-b border-gray-800/80 px-3 sm:px-6 lg:px-8 py-3 transition-all">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <!-- Brand & Tagline -->
            <div class="flex items-center space-x-2.5 sm:space-x-3">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-amber-300 p-0.5 shadow-lg shadow-amber-500/20 flex items-center justify-center shrink-0">
                    <div class="w-full h-full bg-[#0b0f19] rounded-[10px] flex items-center justify-center text-amber-400 font-extrabold text-base sm:text-lg">
                        🐼
                    </div>
                </div>
                <div>
                    <div class="flex items-center space-x-1.5 sm:space-x-2">
                        <span class="font-extrabold text-base sm:text-lg tracking-tight bg-gradient-to-r from-white via-gray-100 to-gray-400 bg-clip-text text-transparent">
                            KuchuPuchu
                        </span>
                        <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider px-1.5 sm:px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20">
                            Voice Studio
                        </span>
                    </div>
                    <p class="text-[10px] sm:text-xs text-gray-400 hidden xs:block truncate">Background Audio Vault & Player</p>
                </div>
            </div>

            <!-- Header Actions -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                @auth
                    <div class="hidden md:flex items-center space-x-2 px-2.5 py-1.5 rounded-xl bg-gray-800/60 border border-gray-700/50 text-xs text-gray-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="font-medium truncate max-w-[120px]">{{ auth()->user()->name ?? 'Admin' }}</span>
                    </div>

                    <a href="/admin" class="flex items-center space-x-1.5 text-xs font-semibold px-2.5 sm:px-3.5 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 text-amber-300 transition shadow-sm active:scale-95">
                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>Filament</span>
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
    <main class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 pt-5 sm:pt-8 space-y-6 sm:space-y-8">

        <!-- Metrics Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
            <div class="backdrop-blur-md bg-gray-900/60 border border-gray-800/80 rounded-xl sm:rounded-2xl p-3.5 sm:p-5 shadow-sm">
                <div class="flex items-center justify-between text-xs text-gray-400 uppercase tracking-wider font-semibold">
                    <span>Recordings</span>
                    <span class="text-amber-400">🎙</span>
                </div>
                <p class="text-xl sm:text-3xl font-extrabold text-white mt-1 sm:mt-2">{{ $stats['totalRecordings'] }}</p>
                <p class="text-[10px] sm:text-[11px] text-gray-500 mt-0.5">Uploaded 60s segments</p>
            </div>

            <div class="backdrop-blur-md bg-gray-900/60 border border-gray-800/80 rounded-xl sm:rounded-2xl p-3.5 sm:p-5 shadow-sm">
                <div class="flex items-center justify-between text-xs text-gray-400 uppercase tracking-wider font-semibold">
                    <span>Duration</span>
                    <span class="text-emerald-400">⏱</span>
                </div>
                <p class="text-xl sm:text-3xl font-extrabold text-white mt-1 sm:mt-2">{{ $stats['totalDuration'] }}</p>
                <p class="text-[10px] sm:text-[11px] text-gray-500 mt-0.5">Total audio captured</p>
            </div>

            <div class="backdrop-blur-md bg-gray-900/60 border border-gray-800/80 rounded-xl sm:rounded-2xl p-3.5 sm:p-5 shadow-sm">
                <div class="flex items-center justify-between text-xs text-gray-400 uppercase tracking-wider font-semibold">
                    <span>Storage</span>
                    <span class="text-blue-400">💾</span>
                </div>
                <p class="text-xl sm:text-3xl font-extrabold text-white mt-1 sm:mt-2">{{ $stats['totalSize'] }}</p>
                <p class="text-[10px] sm:text-[11px] text-gray-500 mt-0.5">AAC-LC audio files</p>
            </div>

            <div class="backdrop-blur-md bg-gray-900/60 border border-gray-800/80 rounded-xl sm:rounded-2xl p-3.5 sm:p-5 shadow-sm">
                <div class="flex items-center justify-between text-xs text-gray-400 uppercase tracking-wider font-semibold">
                    <span>Phones</span>
                    <span class="text-purple-400">📱</span>
                </div>
                <p class="text-xl sm:text-3xl font-extrabold text-white mt-1 sm:mt-2">{{ $stats['totalDevices'] }}</p>
                <p class="text-[10px] sm:text-[11px] text-gray-500 mt-0.5 truncate">Unique devices logged</p>
            </div>
        </div>

        <!-- Filter & Search Section -->
        <div class="space-y-3">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 sm:gap-4">
                <div>
                    <h2 class="text-lg sm:text-xl font-extrabold tracking-tight text-white flex items-center space-x-2">
                        <span>Audio Recordings</span>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-gray-800 text-amber-400 border border-gray-700" x-text="totalCount">
                            {{ $initialPagination['total'] }}
                        </span>
                    </h2>
                    <p class="text-xs text-gray-400 mt-0.5">Continuous auto-loading voice segments with phone directory filtering & instant seeking</p>
                </div>

                <!-- Controls: Phone Device Dropdown & Search Bar -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 sm:gap-3">
                    <!-- Phone Directory Filter Dropdown -->
                    <div class="relative min-w-[170px]">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <span>📱</span>
                        </div>
                        <select 
                            x-model="selectedDevice" 
                            @change="onDeviceChange()"
                            class="w-full pl-8 pr-8 py-2.5 text-xs bg-gray-900/90 border border-gray-800 rounded-xl focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500/50 focus:outline-none text-gray-200 transition appearance-none cursor-pointer">
                            <option value="">All Phones / Devices</option>
                            <template x-for="dev in devicesList" :key="dev">
                                <option :value="dev" x-text="dev"></option>
                            </template>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>

                    <!-- Search Bar with Live Debounce -->
                    <div class="w-full sm:w-72 relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            x-model="searchQuery" 
                            @input="onSearchInput()"
                            placeholder="Search filename, ID, phone..." 
                            class="w-full pl-9 pr-4 py-2.5 text-xs bg-gray-900/90 border border-gray-800 rounded-xl focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500/50 focus:outline-none text-gray-200 placeholder-gray-500 transition shadow-inner">
                    </div>
                </div>
            </div>

            <!-- Quick Filter Device Pills -->
            <div class="flex items-center space-x-2 overflow-x-auto pb-1 scrollbar-none" x-show="devicesList.length > 0">
                <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider shrink-0">Phones:</span>
                <button 
                    type="button" 
                    @click="setDeviceFilter('')" 
                    class="px-3 py-1 rounded-full text-xs font-medium transition shrink-0"
                    :class="!selectedDevice ? 'bg-amber-500 text-gray-950 font-bold shadow' : 'bg-gray-800/80 text-gray-300 hover:bg-gray-700/80 border border-gray-700/50'">
                    All Devices
                </button>
                <template x-for="dev in devicesList" :key="dev">
                    <button 
                        type="button" 
                        @click="setDeviceFilter(dev)" 
                        class="px-3 py-1 rounded-full text-xs font-medium transition shrink-0 flex items-center space-x-1"
                        :class="selectedDevice === dev ? 'bg-amber-500 text-gray-950 font-bold shadow' : 'bg-gray-800/80 text-gray-300 hover:bg-gray-700/80 border border-gray-700/50'">
                        <span>📱</span>
                        <span x-text="dev"></span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Recordings Cards Grid (Infinity Scroll) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
            <template x-for="recording in recordingsList" :key="recording.id">
                <div 
                    class="backdrop-blur-md bg-gray-900/70 border rounded-2xl p-4 sm:p-5 shadow-sm transition-all duration-200 group hover:shadow-md cursor-pointer"
                    :class="currentTrack && currentTrack.id === recording.id 
                        ? 'border-amber-500/80 bg-amber-500/10 ring-1 ring-amber-500/30' 
                        : 'border-gray-800/80 hover:border-gray-700/80'"
                    @click="playTrack(recording)">
                    
                    <div class="flex items-start justify-between gap-3">
                        <!-- Play / Pause Icon Button -->
                        <div 
                            class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center shrink-0 transition shadow-md"
                            :class="currentTrack && currentTrack.id === recording.id && isPlaying 
                                ? 'bg-amber-500 text-gray-950 ring-4 ring-amber-500/20' 
                                : 'bg-gray-800/90 group-hover:bg-amber-500 group-hover:text-gray-950 text-gray-300'">
                            <template x-if="currentTrack && currentTrack.id === recording.id && isPlaying">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M6.75 5.25a.75.75 0 01.75.75v12a.75.75 0 01-1.5 0v-12a.75.75 0 01.75-.75zm9 0a.75.75 0 01.75.75v12a.75.75 0 01-1.5 0v-12a.75.75 0 01.75-.75z" clip-rule="evenodd" /></svg>
                            </template>
                            <template x-if="!(currentTrack && currentTrack.id === recording.id && isPlaying)">
                                <svg class="w-6 h-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M4.5 5.653c0-1.426 1.529-2.33 2.779-1.643l11.54 6.348c1.295.712 1.295 2.573 0 3.285L7.28 19.991c-1.25.687-2.779-.217-2.779-1.643V5.653z" clip-rule="evenodd" /></svg>
                            </template>
                        </div>

                        <div class="min-w-0 flex-1">
                            <h4 class="text-sm font-bold text-gray-100 truncate group-hover:text-amber-400 transition" :title="recording.filename" x-text="recording.filename"></h4>
                            
                            <!-- Ago Time & Phone Directory Badge -->
                            <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                <span class="inline-flex items-center space-x-1 text-xs font-semibold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-md border border-amber-500/20">
                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span x-text="recording.ago_time"></span>
                                </span>

                                <span class="inline-flex items-center space-x-1 text-[10px] font-semibold text-sky-400 bg-sky-500/10 px-2 py-0.5 rounded-md border border-sky-500/20 truncate max-w-[150px]" :title="`Phone Directory: ${recording.device_name}`">
                                    <span>📱</span>
                                    <span x-text="recording.device_name"></span>
                                </span>
                                
                                <span class="text-[10px] text-gray-500 font-mono hidden sm:inline truncate" x-text="recording.exact_time"></span>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shrink-0" x-text="recording.status"></span>
                    </div>

                    <!-- Bottom Info Row -->
                    <div class="mt-3.5 pt-3 border-t border-gray-800/80 flex items-center justify-between text-xs text-gray-400 font-mono">
                        <div class="flex items-center space-x-3">
                            <span class="flex items-center space-x-1 text-gray-300 font-bold">
                                <span>⏱</span>
                                <span x-text="recording.duration_text"></span>
                            </span>
                            <span class="flex items-center space-x-1 text-gray-400">
                                <span>💾</span>
                                <span x-text="recording.size"></span>
                            </span>
                        </div>

                        <!-- Download Link -->
                        <a 
                            :href="recording.url" 
                            :download="recording.filename"
                            @click.stop
                            class="p-1.5 rounded-lg hover:bg-gray-800 hover:text-amber-400 text-gray-400 transition"
                            title="Download audio">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </template>

            <!-- Empty State -->
            <div x-show="!isLoading && recordingsList.length === 0" class="col-span-full py-16 text-center text-gray-500">
                <p class="text-4xl">🎙</p>
                <p class="text-base font-bold text-gray-300 mt-2">No voice recordings found</p>
                <p class="text-xs text-gray-500 mt-1">Start recording from the KuchuPuchu Android app to stream audio segments here.</p>
            </div>

            <!-- Infinity Scroll Sentinel & Loader Card -->
            <div 
                x-ref="sentinel" 
                class="col-span-full py-8 text-center flex flex-col items-center justify-center space-y-3">
                
                <div x-show="isLoading" class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-full bg-gray-900/95 border border-amber-500/40 text-xs font-semibold text-amber-400 shadow-xl backdrop-blur-md">
                    <svg class="animate-spin -ml-0.5 mr-2 h-4 w-4 text-amber-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Loading more recordings...</span>
                </div>

                <div x-show="!hasMore && recordingsList.length > 0 && !isLoading" class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-gray-900/80 border border-gray-800 text-xs text-gray-400 font-mono">
                    <span class="text-emerald-400">✓</span>
                    <span>All recordings loaded (<span x-text="recordingsList.length"></span> segments)</span>
                </div>
            </div>
        </div>
    </main>

    <!-- Master Bottom Floating Audio Player Bar (Fully Mobile Friendly & Responsive) -->
    <div 
        class="fixed bottom-2 sm:bottom-4 inset-x-2 sm:inset-x-4 max-w-5xl mx-auto z-50 backdrop-blur-2xl bg-[#0f172a]/95 border border-gray-700/80 rounded-2xl p-3 sm:p-4 shadow-2xl transition-all duration-300"
        :class="currentTrack ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12 pointer-events-none'">
        
        <!-- Hidden Audio Element -->
        <audio 
            x-ref="audioPlayer"
            @timeupdate="onTimeUpdate()"
            @loadedmetadata="onMetadataLoaded()"
            @ended="isPlaying = false"
            @play="isPlaying = true"
            @pause="isPlaying = false"
            preload="metadata">
        </audio>

        <!-- Player Content Layout -->
        <div class="flex flex-col space-y-2.5">
            <!-- Row 1: Track Info & Playback Buttons -->
            <div class="flex items-center justify-between gap-3">
                <!-- Track Details -->
                <div class="flex items-center space-x-2.5 min-w-0 flex-1">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0">
                        <template x-if="isPlaying">
                            <div class="flex items-end space-x-0.5 h-4">
                                <span class="w-1 bg-amber-400 animate-pulse h-2.5 rounded"></span>
                                <span class="w-1 bg-amber-400 animate-pulse h-4 rounded" style="animation-delay: 150ms;"></span>
                                <span class="w-1 bg-amber-400 animate-pulse h-1.5 rounded" style="animation-delay: 300ms;"></span>
                                <span class="w-1 bg-amber-400 animate-pulse h-3.5 rounded" style="animation-delay: 75ms;"></span>
                            </div>
                        </template>
                        <template x-if="!isPlaying">
                            <span class="text-base sm:text-lg">🎵</span>
                        </template>
                    </div>

                    <div class="min-w-0 flex-1">
                        <h5 class="text-xs sm:text-sm font-bold text-white truncate" x-text="currentTrack ? currentTrack.filename : ''"></h5>
                        <p class="text-[10px] sm:text-xs text-amber-400/90 font-mono truncate" x-text="currentTrack ? `📱 ${currentTrack.device_name || 'Device'} • ${currentTrack.ago_time} • ${currentTrack.duration_text} • ${currentTrack.size}` : ''"></p>
                    </div>
                </div>

                <!-- Central Control Buttons -->
                <div class="flex items-center space-x-2 sm:space-x-3 shrink-0">
                    <button type="button" @click="skip(-10)" class="text-gray-400 hover:text-white transition p-1.5 active:scale-95" title="Rewind 10 seconds">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z" /></svg>
                    </button>

                    <button type="button" @click="togglePlay()" class="w-10 h-10 sm:w-11 sm:h-11 rounded-full bg-amber-500 hover:bg-amber-400 text-gray-950 font-bold flex items-center justify-center shadow-lg active:scale-90 transition">
                        <template x-if="!isPlaying">
                            <svg class="w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M4.5 5.653c0-1.426 1.529-2.33 2.779-1.643l11.54 6.348c1.295.712 1.295 2.573 0 3.285L7.28 19.991c-1.25.687-2.779-.217-2.779-1.643V5.653z" clip-rule="evenodd" /></svg>
                        </template>
                        <template x-if="isPlaying">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M6.75 5.25a.75.75 0 01.75.75v12a.75.75 0 01-1.5 0v-12a.75.75 0 01.75-.75zm9 0a.75.75 0 01.75.75v12a.75.75 0 01-1.5 0v-12a.75.75 0 01.75-.75z" clip-rule="evenodd" /></svg>
                        </template>
                    </button>

                    <button type="button" @click="skip(10)" class="text-gray-400 hover:text-white transition p-1.5 active:scale-95" title="Forward 10 seconds">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z" /></svg>
                    </button>

                    <!-- Speed Toggle (Mobile Compact) -->
                    <select x-model="playbackRate" class="bg-gray-800 text-[11px] text-gray-200 border border-gray-700 rounded-lg px-1.5 py-1 focus:ring-1 focus:ring-amber-500 focus:outline-none">
                        <option value="0.75">0.75x</option>
                        <option value="1.0" selected>1.0x</option>
                        <option value="1.25">1.25x</option>
                        <option value="1.5">1.5x</option>
                        <option value="2.0">2.0x</option>
                    </select>
                </div>
            </div>

            <!-- Row 2: Touch-Friendly Interactive Scrubber & Time -->
            <div class="w-full flex items-center space-x-2 sm:space-x-3 text-[11px] font-mono text-gray-400">
                <span class="w-10 text-right shrink-0" x-text="formatTime(currentTime)">00:00</span>
                
                <!-- Native Touch Range Slider for Smooth Mobile & Desktop Seeking -->
                <div class="relative w-full flex items-center">
                    <input 
                        type="range" 
                        class="touch-slider w-full h-2 cursor-pointer"
                        min="0" 
                        :max="duration > 0 ? duration : 60" 
                        step="0.1"
                        :value="currentTime"
                        @input="onSeekInput($event)"
                        @change="onSeekChange($event)"
                        @touchstart="isSeeking = true"
                        @touchend="isSeeking = false">
                </div>

                <span class="w-10 shrink-0 font-bold text-gray-300" x-text="formatTime(duration)">00:00</span>
            </div>
        </div>
    </div>

    <!-- Alpine.js Audio Studio Logic -->
    <script>
        function audioStudio(initialPagination) {
            const initData = initialPagination || {};
            return {
                recordingsList: initData.data || [],
                currentPage: initData.current_page || 1,
                lastPage: initData.last_page || 1,
                hasMore: initData.has_more ?? false,
                totalCount: initData.total ?? 0,
                selectedDevice: initData.selected_device || '',
                devicesList: initData.devices || [],
                isLoading: false,

                currentTrack: null,
                isPlaying: false,
                currentTime: 0,
                duration: 0,
                volume: 1.0,
                playbackRate: 1.0,
                searchQuery: '',
                isSeeking: false,
                debounceTimer: null,
                observer: null,

                init() {
                    this.$watch('volume', val => {
                        if (this.$refs.audioPlayer) this.$refs.audioPlayer.volume = val;
                    });
                    this.$watch('playbackRate', val => {
                        if (this.$refs.audioPlayer) this.$refs.audioPlayer.playbackRate = val;
                    });

                    this.setupIntersectionObserver();
                    this.setupWindowScrollListener();
                },

                setupIntersectionObserver() {
                    if (!('IntersectionObserver' in window)) return;
                    this.observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting && this.hasMore && !this.isLoading) {
                                this.loadMore();
                            }
                        });
                    }, {
                        rootMargin: '250px',
                        threshold: 0.05
                    });

                    this.$nextTick(() => {
                        if (this.$refs.sentinel) {
                            this.observer.observe(this.$refs.sentinel);
                        }
                    });
                },

                setupWindowScrollListener() {
                    window.addEventListener('scroll', () => {
                        if (this.isLoading || !this.hasMore) return;
                        const scrollBottom = window.innerHeight + window.scrollY;
                        const docHeight = document.documentElement.offsetHeight;
                        if (docHeight - scrollBottom < 300) {
                            this.loadMore();
                        }
                    }, { passive: true });
                },

                loadMore() {
                    if (this.isLoading || !this.hasMore) return;
                    this.fetchPage(this.currentPage + 1, false);
                },

                setDeviceFilter(dev) {
                    this.selectedDevice = dev;
                    this.onDeviceChange();
                },

                onDeviceChange() {
                    this.currentPage = 1;
                    this.hasMore = true;
                    this.fetchPage(1, true);
                },

                async fetchPage(page, reset = false) {
                    if (this.isLoading) return;
                    this.isLoading = true;

                    try {
                        const params = new URLSearchParams({
                            page: page,
                            format: 'json'
                        });
                        if (this.selectedDevice && this.selectedDevice.trim().length > 0) {
                            params.append('device', this.selectedDevice.trim());
                        }
                        if (this.searchQuery && this.searchQuery.trim().length > 0) {
                            params.append('search', this.searchQuery.trim());
                        }

                        const res = await fetch(`/studio?${params.toString()}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!res.ok) {
                            throw new Error(`HTTP error ${res.status}`);
                        }

                        const json = await res.json();
                        const items = json.data || [];

                        if (reset) {
                            this.recordingsList = items;
                        } else {
                            const existingIds = new Set(this.recordingsList.map(r => r.id));
                            const newItems = items.filter(r => !existingIds.has(r.id));
                            this.recordingsList = [...this.recordingsList, ...newItems];
                        }

                        this.currentPage = json.current_page;
                        this.lastPage = json.last_page;
                        this.hasMore = json.has_more;
                        this.totalCount = json.total;
                        if (json.devices && json.devices.length > 0) {
                            this.devicesList = json.devices;
                        }
                    } catch (err) {
                        console.error('Infinity scroll load error:', err);
                    } finally {
                        this.isLoading = false;
                    }
                },

                onSearchInput() {
                    clearTimeout(this.debounceTimer);
                    this.debounceTimer = setTimeout(() => {
                        this.currentPage = 1;
                        this.hasMore = true;
                        this.fetchPage(1, true);
                    }, 300);
                },

                playTrack(track) {
                    if (this.currentTrack && this.currentTrack.id === track.id) {
                        this.togglePlay();
                        return;
                    }
                    this.currentTrack = track;
                    this.currentTime = 0;
                    // Pre-fill duration from database immediately so seekbar works before audio loads!
                    this.duration = track.duration_seconds || 60;

                    this.$nextTick(() => {
                        const player = this.$refs.audioPlayer;
                        if (player) {
                            player.src = track.url;
                            player.currentTime = 0;
                            player.playbackRate = parseFloat(this.playbackRate);
                            player.play().catch(e => console.log('Autoplay policy prevented playback:', e));
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
                        player.play().catch(e => console.log('Playback error:', e));
                        this.isPlaying = true;
                    }
                },

                onTimeUpdate() {
                    const player = this.$refs.audioPlayer;
                    if (!player || this.isSeeking) return;
                    this.currentTime = player.currentTime;
                },

                onMetadataLoaded() {
                    const player = this.$refs.audioPlayer;
                    if (!player) return;
                    if (isFinite(player.duration) && player.duration > 0) {
                        this.duration = player.duration;
                    }
                },

                onSeekInput(e) {
                    this.isSeeking = true;
                    this.currentTime = parseFloat(e.target.value);
                },

                onSeekChange(e) {
                    const player = this.$refs.audioPlayer;
                    const targetTime = parseFloat(e.target.value);
                    if (player) {
                        player.currentTime = targetTime;
                    }
                    this.currentTime = targetTime;
                    this.isSeeking = false;
                },

                skip(seconds) {
                    const player = this.$refs.audioPlayer;
                    if (!player) return;
                    const target = Math.max(0, Math.min(this.duration || 60, player.currentTime + seconds));
                    player.currentTime = target;
                    this.currentTime = target;
                },

                formatTime(seconds) {
                    if (isNaN(seconds) || seconds < 0) return '00:00';
                    const m = Math.floor(seconds / 60);
                    const s = Math.floor(seconds % 60);
                    return `${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`;
                },

                refreshPage() {
                    this.currentPage = 1;
                    this.hasMore = true;
                    this.fetchPage(1, true);
                }
            };
        }
    </script>
</body>
</html>
