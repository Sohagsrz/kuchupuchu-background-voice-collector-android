package com.kuchupuchu.audiorecorder.data.database

import android.content.Context
import androidx.room.Database
import androidx.room.Room
import androidx.room.RoomDatabase
import androidx.room.TypeConverter
import androidx.room.TypeConverters
import com.kuchupuchu.audiorecorder.data.models.RecordingStatus

class Converters {
    @TypeConverter
    fun fromStatus(status: RecordingStatus?): String? = status?.name

    @TypeConverter
    fun toStatus(value: String?): RecordingStatus? =
        value?.let { runCatching { RecordingStatus.valueOf(it) }.getOrDefault(RecordingStatus.QUEUED) }
}

@Database(
    entities = [RecordingEntity::class],
    version = 1,
    exportSchema = false
)
@TypeConverters(Converters::class)
abstract class AppDatabase : RoomDatabase() {

    abstract fun recordingDao(): RecordingDao

    companion object {
        @Volatile
        private var INSTANCE: AppDatabase? = null

        fun getInstance(context: Context): AppDatabase {
            return INSTANCE ?: synchronized(this) {
                INSTANCE ?: Room.databaseBuilder(
                    context.applicationContext,
                    AppDatabase::class.java,
                    "kuchupuchu_recordings.db"
                ).fallbackToDestructiveMigration()
                    .build()
                    .also { INSTANCE = it }
            }
        }
    }
}
