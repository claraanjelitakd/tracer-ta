<?php

namespace App\Services\LinkedIn\Providers;

use App\Services\LinkedIn\Contracts\LinkedInProfileProvider;
use App\Services\LinkedIn\DTOs\LinkedInPosition;
use App\Services\LinkedIn\DTOs\LinkedInProfile;
use App\Services\LinkedIn\Exceptions\LinkedInProfileNotFoundException;
use App\Services\LinkedIn\Exceptions\LinkedInSyncException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ApiLinkedInProvider
 *
 * Penyedia profil resmi eksternal yang disiapkan untuk integrasi
 * API resmi LinkedIn Server-to-Server di masa mendatang.
 *
 * =================================================================================
 * PEMBERITAHUAN ARSITEKTUR & KEAMANAN:
 * 1. Provider ini HANYA aktif ketika konfigurasi LINKEDIN_DRIVER='api' pada berkas .env.
 * 2. Kredensial dibaca eksklusif melalui Laravel config ('services.linkedin.api_key' &
 *    'services.linkedin.api_base_url') dan TIDAK BOLEH diekspos ke frontend, database,
 *    maupun pesan error audit log.
 * 3. Provider ini TIDAK mengarang endpoint sembarangan. Ketika dokumentasi resmi
 *    akses server-to-server disahkan oleh LinkedIn Partner Program, rute endpoint
 *    konkret dapat langsung disematkan pada method executeRequest() tanpa merombak
 *    lapisan controller, service, mapper, maupun frontend.
 * 4. Bertanggung jawab menormalisasi respons JSON eksternal menjadi DTO LinkedInProfile.
 * =================================================================================
 */
class ApiLinkedInProvider implements LinkedInProfileProvider
{
    protected ?string $apiKey;

    protected ?string $apiBaseUrl;

    protected int $timeoutSeconds;

    /**
     * Inisialisasi provider dengan konfigurasi terpusat.
     */
    public function __construct(
        ?string $apiKey = null,
        ?string $apiBaseUrl = null,
        int $timeoutSeconds = 15,
        protected ?HttpFactory $http = null,
    ) {
        $this->apiKey = $apiKey ?? config('services.linkedin.api_key');
        $this->apiBaseUrl = $apiBaseUrl ?? config('services.linkedin.api_base_url');
        $this->timeoutSeconds = $timeoutSeconds;
        $this->http = $http ?? Http::getFacadeRoot();
    }

    /**
     * Mengambil dan menormalisasi profil LinkedIn dari API server-to-server.
     *
     * @param  string  $username  Username / vanity name profil alumni.
     *
     * @throws LinkedInProfileNotFoundException
     * @throws LinkedInSyncException
     */
    public function findByUsername(string $username): ?LinkedInProfile
    {
        $cleanUsername = trim(strtolower($username));

        // 1. Skenario: Username kosong
        if (empty($cleanUsername)) {
            return null;
        }

        // 2. Validasi kelengkapan konfigurasi API Server-to-Server
        $this->ensureConfigured();

        // 3. Eksekusi pemanggilan HTTP resmi dengan penanganan error terisolasi
        try {
            $response = $this->executeRequest($cleanUsername);

            // Tangani status kode HTTP respons
            return $this->handleApiResponse($response, $cleanUsername);

        } catch (LinkedInProfileNotFoundException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            Log::error('LinkedIn API Connection Timeout: Gagal terhubung ke host eksternal.');
            throw new LinkedInSyncException('Koneksi ke server LinkedIn API eksternal terputus atau timeout. Silakan periksa jaringan.');
        } catch (Throwable $e) {
            Log::error('LinkedIn API Provider Unexpected Error: '.$e->getMessage());
            throw new LinkedInSyncException('Terjadi kendala saat berkomunikasi dengan server LinkedIn API.');
        }
    }

    /**
     * Memastikan API Key dan Base URL telah terkonfigurasi di server.
     *
     * @throws LinkedInSyncException
     */
    protected function ensureConfigured(): void
    {
        if (empty($this->apiKey) || empty($this->apiBaseUrl)) {
            throw new LinkedInSyncException(
                'Konfigurasi kredensial LinkedIn API (LINKEDIN_API_KEY / LINKEDIN_API_BASE_URL) belum diatur pada berkas environment server.'
            );
        }
    }

    /**
     * Membangun dan mengeksekusi request HTTP client ke external API.
     */
    protected function executeRequest(string $username): Response
    {
        $url = rtrim($this->apiBaseUrl, '/').'/profiles/'.urlencode($username);

        return $this->http
            ->timeout($this->timeoutSeconds)
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Accept' => 'application/json',
                'User-Agent' => 'UKDW-TracerStudy-LinkedInSync/2.0',
            ])
            ->get($url);
    }

    /**
     * Memproses status kode HTTP dari LinkedIn API eksternal.
     *
     * @throws LinkedInProfileNotFoundException
     * @throws LinkedInSyncException
     */
    protected function handleApiResponse(Response $response, string $username): LinkedInProfile
    {
        $status = $response->status();

        // 404 NOT FOUND
        if ($status === 404) {
            throw new LinkedInProfileNotFoundException($username);
        }

        // 401 UNAUTHORIZED / 403 FORBIDDEN
        if ($status === 401 || $status === 403) {
            Log::error("LinkedIn API Authentication Failed: HTTP Status {$status}. Kredensial tidak valid atau masa berlaku habis.");
            throw new LinkedInSyncException('Otorisasi LinkedIn API ditolak oleh server eksternal. Periksa kredensial server.');
        }

        // 429 TOO MANY REQUESTS
        if ($status === 429) {
            Log::warning('LinkedIn API Rate Limited: Kuota batas request terlampaui.');
            throw new LinkedInSyncException('Batas kuota request LinkedIn API terlampaui. Silakan coba kembali dalam beberapa saat.');
        }

        // 500 SERVER ERROR
        if ($status >= 500) {
            Log::error("LinkedIn API Server Error: HTTP Status {$status}.");
            throw new LinkedInSyncException('Server LinkedIn API sedang mengalami gangguan (HTTP '.$status.').');
        }

        if (! $response->successful()) {
            throw new LinkedInSyncException("Permintaan profil LinkedIn gagal dengan status HTTP {$status}.");
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new LinkedInSyncException('Format respons dari LinkedIn API tidak valid (Bukan JSON).');
        }

        return $this->normalizeApiResponse($payload, $username);
    }

    /**
     * Menormalisasi payload JSON dari API eksternal menjadi DTO LinkedInProfile aplikasi.
     * Mengikuti format yang identik dengan Mock Provider agar mapper tidak memerlukan perubahan.
     */
    public function normalizeApiResponse(array $payload, string $fallbackUsername): LinkedInProfile
    {
        $localizedFirstName = $payload['localizedFirstName']
            ?? ($payload['firstName']['localized']['en_US'] ?? '');

        $localizedLastName = $payload['localizedLastName']
            ?? ($payload['lastName']['localized']['en_US'] ?? '');

        $localizedHeadline = $payload['localizedHeadline']
            ?? ($payload['headline']['localized']['en_US'] ?? '');

        $vanityName = $payload['vanityName'] ?? $fallbackUsername;

        $positions = [];
        if (! empty($payload['positions']) && is_array($payload['positions'])) {
            foreach ($payload['positions'] as $posData) {
                if (is_array($posData)) {
                    $positions[] = LinkedInPosition::fromArray($posData);
                }
            }
        }

        return new LinkedInProfile(
            id: (string) ($payload['id'] ?? uniqid('person_')),
            firstName: $payload['firstName'] ?? ['localized' => ['en_US' => $localizedFirstName]],
            localizedFirstName: $localizedFirstName,
            lastName: $payload['lastName'] ?? ['localized' => ['en_US' => $localizedLastName]],
            localizedLastName: $localizedLastName,
            headline: $payload['headline'] ?? ['localized' => ['en_US' => $localizedHeadline]],
            localizedHeadline: $localizedHeadline,
            vanityName: $vanityName,
            profilePicture: $payload['profilePicture'] ?? null,
            positions: $positions,
        );
    }
}
