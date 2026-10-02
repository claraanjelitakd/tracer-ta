<?php

namespace App\Services\LinkedIn\Providers;

use App\Models\Biodata;
use App\Services\LinkedIn\Contracts\LinkedInProfileProvider;
use App\Services\LinkedIn\DTOs\LinkedInProfile;
use App\Services\LinkedIn\Exceptions\LinkedInProfileNotFoundException;

/**
 * MockLinkedInProvider
 *
 * Penyedia Mock untuk simulasi layanan profil LinkedIn eksternal berdasarkan
 * struktur data yang didokumentasikan resmi oleh LinkedIn (Profile API & Position Fields).
 *
 * =================================================================================
 * PEMBERITAHUAN DOKUMENTASI & INTEGRITAS:
 * 1. Ini adalah simulasi layanan profil eksternal untuk pengujian sistem Tracer Study.
 * 2. Struktur profil mengacu pada LinkedIn Profile API & Basic Profile Fields:
 *    (id, firstName, lastName, headline, vanityName, profilePicture).
 * 3. Struktur pengalaman kerja mengacu pada LinkedIn Position Fields:
 *    (companyName, title, startMonthYear, endMonthYear, geoPositionLocation).
 * 4. Bagian `positions` pada mock ini adalah kontrak simulasi gabungan internal untuk
 *    kebutuhan tracer study, dan BUKAN klaim bahwa endpoint resmi `/v2/me` mengembalikan
 *    field `positions` secara bawaan.
 * 5. Provider ini TIDAK melakukan web scraping, TIDAK menggunakan OAuth, dan TIDAK
 *    mengakses server LinkedIn secara langsung.
 * =================================================================================
 */
class MockLinkedInProvider implements LinkedInProfileProvider
{
    /**
     * Mengambil dan mensimulasikan data profil eksternal LinkedIn berdasarkan username.
     *
     * @param  string  $username  Username / vanity handle profil LinkedIn.
     *
     * @throws LinkedInProfileNotFoundException
     */
    public function findByUsername(string $username): ?LinkedInProfile
    {
        $cleanUsername = trim(strtolower($username));

        // 1. Skenario Uji: Username kosong
        if (empty($cleanUsername)) {
            return null;
        }

        // 2. Skenario Uji: Simulasi 404 / PROFILE_NOT_FOUND
        // Jika username diawali 'notfound_', 'test_404', atau bernilai 'notfound'
        if (str_starts_with($cleanUsername, 'notfound_') || str_starts_with($cleanUsername, '404_') || $cleanUsername === 'notfound') {
            throw new LinkedInProfileNotFoundException($username);
        }

        // 3. Prioritas: Cek apakah ada berkas Mock JSON di direktori storage/app/mock/linkedin
        $mockJsonData = $this->loadFromMockJsonFile($cleanUsername);
        if ($mockJsonData !== null) {
            return LinkedInProfile::fromArray($mockJsonData);
        }

        // 4. Fallback: Bangun data simulasi realistis berdasarkan biodata lokal atau generator terstruktur
        $simulatedData = $this->generateSimulatedExternalResponse($cleanUsername);

        if (! $simulatedData) {
            throw new LinkedInProfileNotFoundException($username);
        }

        return LinkedInProfile::fromArray($simulatedData);
    }

    /**
     * Membaca dan mem-parsing berkas Mock JSON dari direktori mock_path.
     */
    protected function loadFromMockJsonFile(string $username): ?array
    {
        $mockBasePath = config('services.linkedin.mock_path')
            ?: config('linkedin.mock_path')
            ?: storage_path('app/mock/linkedin');

        // Pastikan path absolut
        if (! str_starts_with($mockBasePath, '/') && ! preg_match('/^[a-zA-Z]:\\\\/', $mockBasePath)) {
            $mockBasePath = base_path($mockBasePath);
        }

        $filePath = rtrim($mockBasePath, '/\\').DIRECTORY_SEPARATOR."{$username}.json";

        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $decoded = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Membangun payload JSON simulasi respons eksternal yang mengacu pada schema resmi.
     */
    protected function generateSimulatedExternalResponse(string $username): array
    {
        // Cari biodata lokal untuk mengontekstualisasikan data profil (nama, prodi, posisi eksisting)
        $biodata = Biodata::with(['perusahaan', 'dataAkademik'])
            ->where(function ($q) use ($username) {
                $q->where('linkedin_username', $username)
                    ->orWhere('linkedin_url', 'like', "%/{$username}%")
                    ->orWhere('linkedin_url', 'like', "%/{$username}");
            })
            ->first();

        // Tentukan nama depan & belakang
        $fullName = $biodata?->nama ?? $this->formatNameFromUsername($username);
        $nameParts = explode(' ', trim($fullName));
        $firstName = $nameParts[0] ?? 'Alumni';
        $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : 'Duta Wacana';

        // Tentukan data posisi & perusahaan secara dinamis
        $positionData = $this->resolveSimulatedPosition($biodata, $username);

        $personId = 'mock-person-'.substr(md5($username), 0, 8);
        $assetId = 'mock-asset-'.substr(md5($username), 8, 8);

        $headline = "{$positionData['title']} at {$positionData['company']}";

        return [
            'id' => $personId,
            'firstName' => [
                'localized' => [
                    'en_US' => $firstName,
                    'id_ID' => $firstName,
                ],
                'preferredLocale' => [
                    'country' => 'ID',
                    'language' => 'id',
                ],
            ],
            'localizedFirstName' => $firstName,
            'lastName' => [
                'localized' => [
                    'en_US' => $lastName,
                    'id_ID' => $lastName,
                ],
                'preferredLocale' => [
                    'country' => 'ID',
                    'language' => 'id',
                ],
            ],
            'localizedLastName' => $lastName,
            'headline' => [
                'localized' => [
                    'en_US' => $headline,
                    'id_ID' => $headline,
                ],
                'preferredLocale' => [
                    'country' => 'ID',
                    'language' => 'id',
                ],
            ],
            'localizedHeadline' => $headline,
            'vanityName' => $username,
            'profilePicture' => [
                'displayImage' => "urn:li:digitalmediaAsset:{$assetId}",
            ],
            'positions' => [
                // Posisi aktif saat ini (endMonthYear: null)
                [
                    'id' => 100000 + abs(crc32($username) % 900000),
                    'companyName' => [
                        'localized' => [
                            'en_US' => $positionData['company'],
                            'id_ID' => $positionData['company'],
                        ],
                        'preferredLocale' => [
                            'country' => 'ID',
                            'language' => 'id',
                        ],
                    ],
                    'localizedCompanyName' => $positionData['company'],
                    'title' => [
                        'localized' => [
                            'en_US' => $positionData['title'],
                            'id_ID' => $positionData['title'],
                        ],
                        'preferredLocale' => [
                            'country' => 'ID',
                            'language' => 'id',
                        ],
                    ],
                    'localizedTitle' => $positionData['title'],
                    'startMonthYear' => [
                        'year' => $positionData['start_year'],
                        'month' => $positionData['start_month'],
                    ],
                    'endMonthYear' => null, // Posisi aktif saat ini
                    'geoPositionLocation' => [
                        'displayLocationName' => [
                            'localized' => [
                                'en_US' => $positionData['location'],
                                'id_ID' => $positionData['location'],
                            ],
                            'preferredLocale' => [
                                'country' => 'ID',
                                'language' => 'id',
                            ],
                        ],
                        'localizedDisplayLocationName' => $positionData['location'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Menghasilkan data posisi & perusahaan simulasi.
     */
    protected function resolveSimulatedPosition(?Biodata $biodata, string $username): array
    {
        // 1. Jika biodata sudah memiliki perusahaan & jabatan riil, prioritaskan sinkronisasi realistis
        $existingCompany = $biodata?->perusahaan?->nama_perusahaan;
        $existingTitle = $biodata?->posisi_jabatan;

        if ($existingCompany && $existingTitle) {
            return [
                'company' => $existingCompany,
                'title' => $existingTitle,
                'start_year' => (int) ($biodata->tahun_lulus ?: 2023),
                'start_month' => 6,
                'location' => 'Yogyakarta, Indonesia',
            ];
        }

        // 2. Daftar perusahaan teknologi & industri terkemuka untuk variasi mock yang realistis
        $fallbackCompanies = [
            'PT Bank Central Asia Tbk',
            'PT Telkom Indonesia (Persero) Tbk',
            'PT GoTo Gojek Tokopedia Tbk',
            'PT Shopee International Indonesia',
            'PT Bukalapak.com Tbk',
            'PT Astra International Tbk',
            'PT Gameloft Indonesia',
            'PT Duta Wacana Solusindo',
            'PT Global Digital Niaga (Blibli)',
            'PT Traveloka Indonesia',
        ];

        $fallbackTitles = [
            'Software Engineer',
            'Full Stack Developer',
            'Frontend Engineer',
            'Backend Engineer',
            'Data Analyst',
            'Product Manager',
            'Quality Assurance Engineer',
            'UI/UX Designer',
            'System Analyst',
            'Cloud Infrastructure Engineer',
        ];

        $fallbackLocations = [
            'Daerah Istimewa Yogyakarta, Indonesia',
            'Jakarta, Indonesia',
            'Surabaya, Indonesia',
            'Bandung, Indonesia',
            'Tangerang, Banten, Indonesia',
        ];

        $hash = abs(crc32($username));
        $company = $existingCompany ?: $fallbackCompanies[$hash % count($fallbackCompanies)];
        $title = $existingTitle ?: $fallbackTitles[($hash >> 3) % count($fallbackTitles)];
        $location = $fallbackLocations[($hash >> 5) % count($fallbackLocations)];
        $startYear = (int) ($biodata?->tahun_lulus ?: (2022 + ($hash % 3)));

        return [
            'company' => $company,
            'title' => $title,
            'start_year' => $startYear,
            'start_month' => ($hash % 12) + 1,
            'location' => $location,
        ];
    }

    /**
     * Format nama manusia dari username jika biodata lokal belum memiliki nama.
     */
    protected function formatNameFromUsername(string $username): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9]/', ' ', $username);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));

        return ucwords($clean ?: 'Alumni Duta Wacana');
    }
}
