<?php

namespace App\Services\LinkedIn\Contracts;

use App\Services\LinkedIn\DTOs\LinkedInProfile;
use App\Services\LinkedIn\Exceptions\LinkedInProfileNotFoundException;
use App\Services\LinkedIn\Exceptions\LinkedInSyncException;

/**
 * Interface LinkedInProfileProvider
 *
 * Kontrak abstraksi penyedia layanan profil LinkedIn eksternal.
 * Memisahkan lapisan pemanggil (controller / service) dari implementasi konkret,
 * sehingga provider mock saat ini dapat digantikan oleh provider resmi di masa mendatang
 * tanpa mengubah logika bisnis aplikasi.
 */
interface LinkedInProfileProvider
{
    /**
     * Mengambil data profil LinkedIn berdasarkan username/vanity name publik.
     *
     * @param  string  $username  Username atau vanity name anggota LinkedIn.
     * @return LinkedInProfile|null Mengembalikan DTO profil jika ditemukan, atau null jika tidak ada.
     *
     * @throws LinkedInProfileNotFoundException
     * @throws LinkedInSyncException
     */
    public function findByUsername(string $username): ?LinkedInProfile;
}
