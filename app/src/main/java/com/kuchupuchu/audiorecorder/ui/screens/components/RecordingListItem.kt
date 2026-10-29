package com.kuchupuchu.audiorecorder.ui.screens.components

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.kuchupuchu.audiorecorder.data.database.RecordingEntity
import com.kuchupuchu.audiorecorder.data.models.RecordingStatus
import com.kuchupuchu.audiorecorder.ui.theme.StatusCorrupt
import com.kuchupuchu.audiorecorder.ui.theme.StatusFailed
import com.kuchupuchu.audiorecorder.ui.theme.StatusQueued
import com.kuchupuchu.audiorecorder.ui.theme.StatusUploaded
import com.kuchupuchu.audiorecorder.ui.theme.StatusUploading
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

@Composable
fun RecordingListItem(
    index: Int,
    item: RecordingEntity,
    formattedSize: String,
    onRetryClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    val timeFormat = SimpleDateFormat("HH:mm:ss", Locale.getDefault())
    val startTimeStr = timeFormat.format(Date(item.startedAt))
    val endTimeStr = timeFormat.format(Date(item.endedAt))

    val statusColor = when (item.status) {
        RecordingStatus.READY, RecordingStatus.QUEUED -> StatusQueued
        RecordingStatus.UPLOADING -> StatusUploading
        RecordingStatus.UPLOADED -> StatusUploaded
        RecordingStatus.FAILED -> StatusFailed
        RecordingStatus.CORRUPT -> StatusCorrupt
    }

    Card(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(10.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surface
        ),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 14.dp, vertical = 10.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Index
            Text(
                text = "$index.",
                fontWeight = FontWeight.Bold,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )

            Spacer(modifier = Modifier.width(12.dp))

            Column(
                modifier = Modifier.weight(1f)
            ) {
                // Time Range
                Text(
                    text = "$startTimeStr – $endTimeStr",
                    fontWeight = FontWeight.SemiBold,
                    style = MaterialTheme.typography.bodyMedium
                )

                Spacer(modifier = Modifier.height(2.dp))

                // Size & Duration
                val durationSec = item.durationMs / 1000
                Text(
                    text = "$formattedSize | ${durationSec}s",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                // Error message if present
                if (!item.lastError.isNullOrBlank()) {
                    Text(
                        text = item.lastError,
                        style = MaterialTheme.typography.labelSmall,
                        color = StatusFailed,
                        maxLines = 1
                    )
                }
            }

            Spacer(modifier = Modifier.width(8.dp))

            // Status Badge
            Box(
                modifier = Modifier
                    .clip(RoundedCornerShape(6.dp))
                    .background(statusColor.copy(alpha = 0.15f))
                    .padding(horizontal = 10.dp, vertical = 4.dp),
                contentAlignment = Alignment.Center
            ) {
                Text(
                    text = item.status.displayLabel,
                    color = statusColor,
                    fontWeight = FontWeight.Bold,
                    fontSize = 12.sp
                )
            }

            // Retry action if failed
            if (item.status == RecordingStatus.FAILED) {
                Spacer(modifier = Modifier.width(4.dp))
                IconButton(
                    onClick = onRetryClick,
                    modifier = Modifier.size(36.dp)
                ) {
                    Icon(
                        imageVector = Icons.Default.Refresh,
                        contentDescription = "Retry upload",
                        tint = MaterialTheme.colorScheme.primary,
                        modifier = Modifier.size(20.dp)
                    )
                }
            }
        }
    }
}
