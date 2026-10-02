<?php

namespace App\Services\LinkedIn\DTOs;

/**
 * DTO LinkedInProfile
 *
 * Merepresentasikan profil LinkedIn eksternal yang mengacu pada
 * spesifikasi LinkedIn Basic Profile Fields & Profile API resmi.
 *
 * Catatan Arsitektur:
 * - Struktur ini menggabungkan profil dasar (id, vanityName, headline, nama)
 *   dengan array `positions` (Position Fields) untuk kebutuhan tracer study.
 * - Ini merupakan DTO independen yang tidak terikat pada framework database
 *   maupun data internal model Eloquent.
 */
class LinkedInProfile
{
    /**
     * @param  string  $id  Person ID unik pada LinkedIn
     * @param  array  $firstName  Struktur lokalisasi nama depan
     * @param  string  $localizedFirstName  Nama depan terlokalisasi
     * @param  array  $lastName  Struktur lokalisasi nama belakang
     * @param  string  $localizedLastName  Nama belakang terlokalisasi
     * @param  array  $headline  Struktur lokalisasi headline profil
     * @param  string  $localizedHeadline  Headline profil terlokalisasi
     * @param  string  $vanityName  Username / public handle URL LinkedIn
     * @param  array|null  $profilePicture  Aset foto profil jika tersedia
     * @param  LinkedInPosition[]  $positions  Daftar pengalaman kerja / posisi
     */
    public function __construct(
        public string $id,
        public array $firstName,
        public string $localizedFirstName,
        public array $lastName,
        public string $localizedLastName,
        public array $headline,
        public string $localizedHeadline,
        public string $vanityName,
        public ?array $profilePicture = null,
        public array $positions = [],
    ) {}

    /**
     * Mendapatkan nama lengkap terlokalisasi.
     */
    public function getFullName(): string
    {
        return trim("{$this->localizedFirstName} {$this->localizedLastName}");
    }

    /**
     * Menghasilkan URL publik profil LinkedIn berdasarkan vanityName.
     */
    public function getPublicProfileUrl(): string
    {
        return "https://www.linkedin.com/in/{$this->vanityName}";
    }

    /**
     * Membuat instance DTO dari data array terstruktur.
     */
    public static function fromArray(array $data): self
    {
        $localizedFirstName = $data['localizedFirstName']
            ?? ($data['firstName']['localized']['en_US'] ?? '');

        $localizedLastName = $data['localizedLastName']
            ?? ($data['lastName']['localized']['en_US'] ?? '');

        $localizedHeadline = $data['localizedHeadline']
            ?? ($data['headline']['localized']['en_US'] ?? '');

        $positions = [];
        if (! empty($data['positions']) && is_array($data['positions'])) {
            foreach ($data['positions'] as $posData) {
                if ($posData instanceof LinkedInPosition) {
                    $positions[] = $posData;
                } elseif (is_array($posData)) {
                    $positions[] = LinkedInPosition::fromArray($posData);
                }
            }
        }

        return new self(
            id: $data['id'] ?? 'mock-person-'.uniqid(),
            firstName: $data['firstName'] ?? ['localized' => ['en_US' => $localizedFirstName]],
            localizedFirstName: $localizedFirstName,
            lastName: $data['lastName'] ?? ['localized' => ['en_US' => $localizedLastName]],
            localizedLastName: $localizedLastName,
            headline: $data['headline'] ?? ['localized' => ['en_US' => $localizedHeadline]],
            localizedHeadline: $localizedHeadline,
            vanityName: $data['vanityName'] ?? '',
            profilePicture: $data['profilePicture'] ?? null,
            positions: $positions,
        );
    }

    /**
     * Konversi ke representasi array terstruktur.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'localizedFirstName' => $this->localizedFirstName,
            'lastName' => $this->lastName,
            'localizedLastName' => $this->localizedLastName,
            'headline' => $this->headline,
            'localizedHeadline' => $this->localizedHeadline,
            'vanityName' => $this->vanityName,
            'profilePicture' => $this->profilePicture,
            'positions' => array_map(fn (LinkedInPosition $p) => $p->toArray(), $this->positions),
        ];
    }
}
