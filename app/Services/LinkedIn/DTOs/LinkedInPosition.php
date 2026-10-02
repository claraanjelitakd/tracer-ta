<?php

namespace App\Services\LinkedIn\DTOs;

/**
 * DTO LinkedInPosition
 *
 * Merepresentasikan data pengalaman kerja / posisi profesional
 * yang mengacu pada spesifikasi dokumentasi LinkedIn Position Fields.
 *
 * Catatan penting:
 * - `endMonthYear: null` menandakan bahwa posisi ini masih aktif berlangsung (current position).
 * - `geoPositionLocation` menyimpan informasi display lokasi jika tersedia.
 */
class LinkedInPosition
{
    /**
     * @param  int|string  $id  ID posisi pada LinkedIn
     * @param  array  $companyName  Struktur lokalisasi nama perusahaan
     * @param  string  $localizedCompanyName  Nama perusahaan terlokalisasi
     * @param  array  $title  Struktur lokalisasi jabatan/posisi
     * @param  string  $localizedTitle  Judul jabatan terlokalisasi
     * @param  array{year: int, month: int}|null  $startMonthYear  Waktu mulai posisi
     * @param  array{year: int, month: int}|null  $endMonthYear  Waktu selesai posisi (null jika posisi aktif)
     * @param  array|null  $geoPositionLocation  Struktur geografis lokasi LinkedIn
     * @param  string|null  $localizedDisplayLocationName  Nama lokasi tampilan
     */
    public function __construct(
        public int|string $id,
        public array $companyName,
        public string $localizedCompanyName,
        public array $title,
        public string $localizedTitle,
        public ?array $startMonthYear = null,
        public ?array $endMonthYear = null,
        public ?array $geoPositionLocation = null,
        public ?string $localizedDisplayLocationName = null,
    ) {}

    /**
     * Mengecek apakah posisi ini adalah posisi yang masih berlangsung (aktif).
     */
    public function isCurrent(): bool
    {
        return $this->endMonthYear === null;
    }

    /**
     * Mendapatkan tahun mulai posisi.
     */
    public function getStartYear(): int
    {
        return (int) ($this->startMonthYear['year'] ?? 0);
    }

    /**
     * Mendapatkan bulan mulai posisi.
     */
    public function getStartMonth(): int
    {
        return (int) ($this->startMonthYear['month'] ?? 1);
    }

    /**
     * Mendapatkan tahun selesai posisi.
     */
    public function getEndYear(): int
    {
        return (int) ($this->endMonthYear['year'] ?? 0);
    }

    /**
     * Mendapatkan bulan selesai posisi.
     */
    public function getEndMonth(): int
    {
        return (int) ($this->endMonthYear['month'] ?? 1);
    }

    /**
     * Membuat instance DTO dari data array struktur resmi/simulasi.
     */
    public static function fromArray(array $data): self
    {
        $localizedCompanyName = $data['localizedCompanyName']
            ?? ($data['companyName']['localized']['en_US'] ?? 'Perusahaan Tanpa Nama');

        $localizedTitle = $data['localizedTitle']
            ?? ($data['title']['localized']['en_US'] ?? 'Karyawan');

        $localizedLocation = $data['geoPositionLocation']['localizedDisplayLocationName']
            ?? ($data['geoPositionLocation']['displayLocationName']['localized']['en_US'] ?? null);

        return new self(
            id: $data['id'] ?? uniqid('pos_'),
            companyName: $data['companyName'] ?? ['localized' => ['en_US' => $localizedCompanyName]],
            localizedCompanyName: $localizedCompanyName,
            title: $data['title'] ?? ['localized' => ['en_US' => $localizedTitle]],
            localizedTitle: $localizedTitle,
            startMonthYear: $data['startMonthYear'] ?? null,
            endMonthYear: $data['endMonthYear'] ?? null,
            geoPositionLocation: $data['geoPositionLocation'] ?? null,
            localizedDisplayLocationName: $localizedLocation,
        );
    }

    /**
     * Konversi ke representasi array.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'companyName' => $this->companyName,
            'localizedCompanyName' => $this->localizedCompanyName,
            'title' => $this->title,
            'localizedTitle' => $this->localizedTitle,
            'startMonthYear' => $this->startMonthYear,
            'endMonthYear' => $this->endMonthYear,
            'geoPositionLocation' => $this->geoPositionLocation,
            'localizedDisplayLocationName' => $this->localizedDisplayLocationName,
            'is_current' => $this->isCurrent(),
        ];
    }
}
