<?php

/**
 * Konfigurasi Driver Sinkronisasi Profil LinkedIn.
 *
 * Mengatur driver yang aktif untuk penyedia profil profesional eksternal.
 * Opsi driver:
 * - 'mock' : Menggunakan MockLinkedInProvider (membaca Mock JSON / fallback dinamis).
 * - 'api'  : Menggunakan ApiLinkedInProvider (future server-to-server API resmi).
 */
return [
    /*
    |--------------------------------------------------------------------------
    | LinkedIn Profile Driver
    |--------------------------------------------------------------------------
    |
    | Driver aktif yang digunakan: 'mock' (default) atau 'api'.
    |
    */
    'driver' => env('LINKEDIN_DRIVER', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | Mock Storage Path
    |--------------------------------------------------------------------------
    |
    | Jalur direktori penyimpanan berkas Mock JSON ({username}.json).
    |
    */
    'mock_path' => env('LINKEDIN_MOCK_PATH', storage_path('app/mock/linkedin')),

    /*
    |--------------------------------------------------------------------------
    | Server-to-Server API Configuration
    |--------------------------------------------------------------------------
    |
    | Digunakan saat driver diatur ke 'api'. API Key tidak boleh dibagikan
    | ke frontend, database, atau log aplikasi.
    |
    */
    'api_base_url' => env('LINKEDIN_API_BASE_URL'),
    'api_key' => env('LINKEDIN_API_KEY'),
];
