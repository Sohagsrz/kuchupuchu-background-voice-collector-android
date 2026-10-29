<x-filament-panels::page>
    <div x-data="{
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
        }
    }" class="space-y-6">

        <!-- Stats Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center font-bold text-xl">
                    <x-heroicon-o-microphone class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Recordings</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['totalRecordings'] }}</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center font-bold text-xl">
                    <x-heroicon-o-clock class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Duration</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['totalDuration'] }}</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center font-bold text-xl">
                    <x-heroicon-o-server class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Storage Space</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['totalSize'] }}</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-500 flex items-center justify-center font-bold text-xl">
                    <x-heroicon-o-sparkles class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Latest Upload</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ $stats['latestTime'] }}</p>
                </div>
            </div>
        </div>

        <!-- Master Studio Audio Player Bar -->
        <div class="bg-gradient-to-r from-gray-900 via-gray-850 to-gray-900 text-white rounded-2xl p-6 shadow-xl border border-gray-800 transition-all">
            <!-- Hidden Audio Element -->
            <audio 
                x-ref="audioPlayer"
                @timeupdate="currentTime = $el.currentTime"
                @loadedmetadata="duration = $el.duration"
                @ended="isPlaying = false"
                @play="isPlaying = true"
                @pause="isPlaying = false">
            </audio>

            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <!-- Track Details -->
                <div class="flex items-center space-x-4 w-full md:w-1/3">
                    <div class="w-14 h-14 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0">
                        <template x-if="isPlaying">
                            <!-- Animated Soundwave Equalizer -->
                            <div class="flex items-end space-x-1 h-6">
                                <span class="w-1 bg-amber-400 animate-pulse h-4 rounded"></span>
                                <span class="w-1 bg-amber-400 animate-pulse h-6 rounded" style="animation-delay: 150ms;"></span>
                                <span class="w-1 bg-amber-400 animate-pulse h-3 rounded" style="animation-delay: 300ms;"></span>
                                <span class="w-1 bg-amber-400 animate-pulse h-5 rounded" style="animation-delay: 75ms;"></span>
                            </div>
                        </template>
                        <template x-if="!isPlaying">
                            <x-heroicon-s-play-circle class="w-8 h-8" />
                        </template>
                    </div>

                    <div class="min-w-0">
                        <h4 class="font-bold text-sm text-gray-100 truncate" x-text="currentTrack ? currentTrack.title : 'No Recording Selected'"></h4>
                        <p class="text-xs text-gray-400 truncate mt-0.5" x-text="currentTrack ? currentTrack.details : 'Select a recording below to start listening'"></p>
                    </div>
                </div>

                <!-- Central Playback Controls & Scrubber -->
                <div class="flex flex-col items-center w-full md:w-1/2 space-y-2">
                    <div class="flex items-center space-x-4">
                        <button type="button" @click="skip(-10)" :disabled="!currentTrack" class="text-gray-400 hover:text-white disabled:opacity-30 transition p-2">
                            <x-heroicon-m-backward class="w-5 h-5" />
                        </button>

                        <button type="button" @click="togglePlay()" :disabled="!currentTrack" class="w-12 h-12 rounded-full bg-amber-500 hover:bg-amber-400 text-gray-950 font-bold flex items-center justify-center shadow-lg hover:scale-105 active:scale-95 disabled:opacity-40 transition">
                            <template x-if="!isPlaying">
                                <x-heroicon-s-play class="w-6 h-6 ml-0.5" />
                            </template>
                            <template x-if="isPlaying">
                                <x-heroicon-s-pause class="w-6 h-6" />
                            </template>
                        </button>

                        <button type="button" @click="skip(10)" :disabled="!currentTrack" class="text-gray-400 hover:text-white disabled:opacity-30 transition p-2">
                            <x-heroicon-m-forward class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Progress Bar & Timestamps -->
                    <div class="w-full flex items-center space-x-3 text-xs text-gray-400">
                        <span class="w-10 text-right font-mono" x-text="formatTime(currentTime)"></span>
                        <div class="relative w-full h-2 bg-gray-700 hover:bg-gray-600 rounded-full cursor-pointer overflow-hidden" @click="seek($event)">
                            <div class="h-full bg-amber-500 rounded-full transition-all duration-75" :style="`width: ${(currentTime / (duration || 1)) * 100}%`"></div>
                        </div>
                        <span class="w-10 font-mono" x-text="formatTime(duration)"></span>
                    </div>
                </div>

                <!-- Volume & Speed Controls -->
                <div class="flex items-center justify-end space-x-4 w-full md:w-1/4">
                    <div class="flex items-center space-x-2">
                        <x-heroicon-m-speaker-wave class="w-4 h-4 text-gray-400" />
                        <input type="range" min="0" max="1" step="0.05" x-model="volume" class="w-20 accent-amber-500 cursor-pointer h-1.5 bg-gray-700 rounded-lg">
                    </div>

                    <select x-model="playbackRate" class="bg-gray-800 text-xs text-gray-200 border border-gray-700 rounded-lg px-2 py-1 focus:ring-amber-500 focus:outline-none">
                        <option value="0.75">0.75x</option>
                        <option value="1.0" selected>1.0x</option>
                        <option value="1.25">1.25x</option>
                        <option value="1.5">1.5x</option>
                        <option value="2.0">2.0x</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Search & Recordings Section -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <!-- Header & Search Bar -->
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">All Recorded Audio Segments</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Click any recording to stream immediately in high quality</p>
                </div>

                <div class="w-full sm:w-72 relative">
                    <x-heroicon-o-magnifying-glass class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                    <input 
                        type="text" 
                        x-model="searchQuery" 
                        placeholder="Search by filename or segment ID..." 
                        class="w-full pl-10 pr-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none text-gray-900 dark:text-white">
                </div>
            </div>

            <!-- Recordings Grid / List -->
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($recordings as $recording)
                    @php
                        $audioUrl = $recording->audio_url;
                        $startedAt = $recording->started_at ? $recording->started_at->format('M d, Y • H:i:s T') : 'Unknown';
                    @endphp

                    <div 
                        x-show="!searchQuery || '{{ strtolower($recording->filename . ' ' . $recording->segment_id) }}'.includes(searchQuery.toLowerCase())"
                        class="p-4 hover:bg-amber-500/5 dark:hover:bg-gray-800/50 transition flex items-center justify-between gap-4 group"
                        :class="currentTrack && currentTrack.id === {{ $recording->id }} ? 'bg-amber-500/10 dark:bg-amber-500/10 border-l-4 border-amber-500' : ''">
                        
                        <!-- Left: Play button + Info -->
                        <div class="flex items-center space-x-4 min-w-0">
                            <!-- Play Button -->
                            <button 
                                type="button" 
                                @click="playTrack({
                                    id: {{ $recording->id }},
                                    title: '{{ $recording->filename }}',
                                    details: 'Duration: {{ $recording->formatted_duration }} • {{ $recording->formatted_size }}',
                                    url: '{{ $audioUrl }}'
                                })"
                                class="w-11 h-11 rounded-full flex items-center justify-center shrink-0 shadow-sm transition"
                                :class="currentTrack && currentTrack.id === {{ $recording->id }} && isPlaying 
                                    ? 'bg-amber-500 text-gray-950 scale-105' 
                                    : 'bg-gray-100 hover:bg-amber-500 hover:text-gray-950 dark:bg-gray-800 text-gray-700 dark:text-gray-300'">
                                <template x-if="currentTrack && currentTrack.id === {{ $recording->id }} && isPlaying">
                                    <x-heroicon-s-pause class="w-5 h-5" />
                                </template>
                                <template x-if="!(currentTrack && currentTrack.id === {{ $recording->id }} && isPlaying)">
                                    <x-heroicon-s-play class="w-5 h-5 ml-0.5" />
                                </template>
                            </button>

                            <!-- Metadata -->
                            <div class="min-w-0">
                                <div class="flex items-center space-x-2">
                                    <h5 class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate group-hover:text-amber-600 dark:group-hover:text-amber-400 transition">
                                        {{ $recording->filename }}
                                    </h5>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400">
                                        {{ $recording->status }}
                                    </span>
                                </div>

                                <div class="flex flex-wrap items-center text-xs text-gray-500 dark:text-gray-400 gap-x-4 gap-y-1 mt-1 font-mono">
                                    <span>⏱ {{ $recording->formatted_duration }}</span>
                                    <span>💾 {{ $recording->formatted_size }}</span>
                                    <span>📅 {{ $startedAt }}</span>
                                    <span class="truncate max-w-xs text-gray-400" title="{{ $recording->segment_id }}">ID: {{ substr($recording->segment_id, 0, 8) }}…</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Actions -->
                        <div class="flex items-center space-x-2 shrink-0">
                            <!-- Direct Download -->
                            <a 
                                href="{{ $audioUrl }}" 
                                download="{{ $recording->filename }}"
                                title="Download audio segment"
                                class="p-2 text-gray-500 hover:text-amber-600 dark:text-gray-400 dark:hover:text-amber-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition">
                                <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-microphone class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
                        <p class="font-medium">No voice recordings found yet.</p>
                        <p class="text-xs mt-1">Start recording from the KuchuPuchu Android app to see audio segments appear here.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</x-filament-panels::page>
