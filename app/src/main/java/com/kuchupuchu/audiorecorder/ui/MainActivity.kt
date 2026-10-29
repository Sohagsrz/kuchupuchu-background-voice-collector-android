package com.kuchupuchu.audiorecorder.ui

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.viewModels
import androidx.compose.animation.AnimatedContent
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.togetherWith
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.Surface
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import com.kuchupuchu.audiorecorder.KuchuPuchuApp
import com.kuchupuchu.audiorecorder.ui.screens.MainScreen
import com.kuchupuchu.audiorecorder.ui.screens.SettingsScreen
import com.kuchupuchu.audiorecorder.ui.theme.KuchuPuchuTheme
import com.kuchupuchu.audiorecorder.ui.viewmodel.MainViewModel
import com.kuchupuchu.audiorecorder.ui.viewmodel.SettingsViewModel

enum class Screen {
    MAIN,
    SETTINGS
}

class MainActivity : ComponentActivity() {

    private val mainViewModel: MainViewModel by viewModels {
        val app = application as KuchuPuchuApp
        MainViewModel.Factory(
            app.container.recordingRepository,
            app.container.settingsRepository,
            app.container.uploadScheduler
        )
    }

    private val settingsViewModel: SettingsViewModel by viewModels {
        val app = application as KuchuPuchuApp
        SettingsViewModel.Factory(
            app.container.settingsRepository,
            app.container.uploadRepository,
            app.container.recordingRepository
        )
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()

        setContent {
            KuchuPuchuTheme {
                Surface(modifier = Modifier.fillMaxSize()) {
                    var currentScreen by remember { mutableStateOf(Screen.MAIN) }

                    AnimatedContent(
                        targetState = currentScreen,
                        transitionSpec = { fadeIn() togetherWith fadeOut() },
                        label = "ScreenTransition"
                    ) { screen ->
                        when (screen) {
                            Screen.MAIN -> {
                                MainScreen(
                                    viewModel = mainViewModel,
                                    onNavigateToSettings = { currentScreen = Screen.SETTINGS }
                                )
                            }
                            Screen.SETTINGS -> {
                                SettingsScreen(
                                    viewModel = settingsViewModel,
                                    onNavigateBack = { currentScreen = Screen.MAIN }
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}
