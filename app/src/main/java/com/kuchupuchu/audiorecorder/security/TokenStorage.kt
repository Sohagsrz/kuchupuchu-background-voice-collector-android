package com.kuchupuchu.audiorecorder.security

import android.content.Context
import android.content.SharedPreferences
import android.util.Log
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKey

class TokenStorage(private val context: Context) {

    private val prefs: SharedPreferences by lazy {
        try {
            val masterKey = MasterKey.Builder(context)
                .setKeyScheme(MasterKey.KeyScheme.AES256_GCM)
                .build()
            EncryptedSharedPreferences.create(
                context,
                SECURE_PREFS_FILE,
                masterKey,
                EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
                EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM
            )
        } catch (e: Exception) {
            Log.w(TAG, "EncryptedSharedPreferences unavailable, falling back to private SharedPreferences", e)
            context.getSharedPreferences(FALLBACK_PREFS_FILE, Context.MODE_PRIVATE)
        }
    }

    var apiToken: String
        get() = prefs.getString(KEY_API_TOKEN, "") ?: ""
        set(value) {
            prefs.edit().putString(KEY_API_TOKEN, value).apply()
        }

    fun clearToken() {
        prefs.edit().remove(KEY_API_TOKEN).apply()
    }

    companion object {
        private const val TAG = "TokenStorage"
        private const val SECURE_PREFS_FILE = "kuchupuchu_secure_prefs"
        private const val FALLBACK_PREFS_FILE = "kuchupuchu_fallback_prefs"
        private const val KEY_API_TOKEN = "key_api_token"
    }
}
