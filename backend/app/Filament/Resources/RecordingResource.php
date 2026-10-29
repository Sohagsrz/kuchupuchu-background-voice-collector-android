<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecordingResource\Pages;
use App\Models\Recording;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class RecordingResource extends Resource
{
    protected static ?string $model = Recording::class;

    protected static ?string $navigationIcon = 'heroicon-o-microphone';

    protected static ?string $navigationLabel = 'Voice Recordings';

    protected static ?string $modelLabel = 'Audio Recording';

    protected static ?string $pluralModelLabel = 'Audio Recordings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('segment_id')
                    ->label('Segment ID')
                    ->disabled()
                    ->required(),
                Forms\Components\TextInput::make('device_name')
                    ->label('Phone / Device')
                    ->disabled(),
                Forms\Components\TextInput::make('filename')
                    ->label('Filename')
                    ->disabled(),
                Forms\Components\TextInput::make('duration_ms')
                    ->label('Duration (ms)')
                    ->numeric()
                    ->disabled(),
                Forms\Components\TextInput::make('size_bytes')
                    ->label('File Size (bytes)')
                    ->numeric()
                    ->disabled(),
                Forms\Components\DateTimePicker::make('started_at')
                    ->label('Started At')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('ended_at')
                    ->label('Ended At')
                    ->disabled(),
                Forms\Components\TextInput::make('checksum')
                    ->label('SHA-256 Checksum')
                    ->disabled(),
                Forms\Components\Textarea::make('notes')
                    ->label('Admin Notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('started_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\ViewColumn::make('audio')
                    ->label('Listen')
                    ->view('filament.tables.columns.audio-player'),

                Tables\Columns\TextColumn::make('filename')
                    ->label('Filename')
                    ->searchable()
                    ->limit(24)
                    ->tooltip(fn (Recording $record): string => $record->filename),

                Tables\Columns\TextColumn::make('device_name')
                    ->label('Phone / Device')
                    ->icon('heroicon-m-device-phone-mobile')
                    ->badge()
                    ->color('warning')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->formatStateUsing(fn (Recording $record): string => $record->formatted_duration)
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('size_bytes')
                    ->label('Size')
                    ->formatStateUsing(fn (Recording $record): string => $record->formatted_size),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Recorded Time (UTC)')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('checksum')
                    ->label('Checksum')
                    ->limit(8)
                    ->copyable()
                    ->copyMessage('SHA-256 checksum copied')
                    ->tooltip(fn (Recording $record): ?string => $record->checksum),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'UPLOADED' => 'success',
                        'FAILED' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('device_name')
                    ->label('Phone / Device')
                    ->options(fn () => Recording::whereNotNull('device_name')->distinct()->pluck('device_name', 'device_name')->toArray()),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'UPLOADED' => 'Uploaded',
                        'FAILED' => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Recording $record) {
                        $fullPath = Storage::disk('public')->path($record->file_path);
                        if (file_exists($fullPath)) {
                            return response()->download($fullPath, $record->filename);
                        }
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Audio Playback')
                    ->schema([
                        Infolists\Components\ViewEntry::make('audio')
                            ->label('Playback')
                            ->view('filament.tables.columns.audio-player'),
                    ]),

                Infolists\Components\Section::make('Segment Information')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('segment_id')
                            ->label('Segment UUID')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('device_name')
                            ->label('Phone / Device')
                            ->badge()
                            ->color('warning'),
                        Infolists\Components\TextEntry::make('filename')
                            ->label('Stored Filename'),
                        Infolists\Components\TextEntry::make('formatted_duration')
                            ->label('Duration'),
                        Infolists\Components\TextEntry::make('formatted_size')
                            ->label('File Size'),
                        Infolists\Components\TextEntry::make('started_at')
                            ->label('Recording Start (UTC)')
                            ->dateTime('Y-m-d H:i:s'),
                        Infolists\Components\TextEntry::make('ended_at')
                            ->label('Recording End (UTC)')
                            ->dateTime('Y-m-d H:i:s'),
                        Infolists\Components\TextEntry::make('checksum')
                            ->label('SHA-256 Checksum')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('device_token')
                            ->label('Device Token Identifier'),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecordings::route('/'),
            'create' => Pages\CreateRecording::route('/create'),
            'view' => Pages\ViewRecording::route('/{record}'),
            'edit' => Pages\EditRecording::route('/{record}/edit'),
        ];
    }
}
