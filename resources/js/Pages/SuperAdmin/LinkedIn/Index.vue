<!--
  Halaman Sinkronisasi LinkedIn (Super Admin)
  File: resources/js/Pages/SuperAdmin/LinkedIn/Index.vue

  Fungsi:
  - Memilih Tahun Kelulusan untuk memfilter alumni.
  - Menampilkan ringkasan metrik (Total, Dengan LinkedIn, Berhasil, Gagal, Dilewati).
  - Melakukan sinkronisasi data profil secara individual maupun massal (bulk)
    melalui Mock LinkedIn Provider tanpa reload seluruh halaman (asynchronous).
  - Visual status reaktif: Belum Disinkronkan -> Menyinkronkan... -> Berhasil / Gagal.
-->
<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import Sidebar from '../Components/Sidebar.vue';

const props = defineProps({
    alumnis: {
        type: Array,
        default: () => [],
    },
    daftarTahun: {
        type: Array,
        default: () => [],
    },
    tahunTerpilih: {
        type: String,
        default: '',
    },
    stats: {
        type: Object,
        default: () => ({
            total_alumni: 0,
            dengan_linkedin: 0,
            berhasil_sinkron: 0,
            gagal_sinkron: 0,
            dilewati: 0,
        }),
    },
});

// State Filter & Pencarian
const selectedTahun = ref(props.tahunTerpilih || (props.daftarTahun[0] || ''));
const search = ref('');

// State Data Alumni Lokal (Reaktif untuk update tanpa reload halaman)
const alumniList = ref(props.alumnis.map(item => ({
    ...item,
    isSyncing: false,
})));

// State Metrik Lokal
const localStats = ref({ ...props.stats });

// State Bulk Sync
const isBulkSyncing = ref(false);
const bulkProgress = ref({ current: 0, total: 0, percentage: 0 });

// Pagination
const perPage = ref(10);
const currentPage = ref(1);

// Filter Data Alumni berdasarkan Pencarian
const filteredAlumni = computed(() => {
    let result = alumniList.value;

    if (search.value.trim()) {
        const query = search.value.toLowerCase().trim();
        result = result.filter(item =>
            (item.nim && item.nim.toLowerCase().includes(query)) ||
            (item.nama && item.nama.toLowerCase().includes(query)) ||
            (item.linkedin_username && item.linkedin_username.toLowerCase().includes(query)) ||
            (item.perusahaan && item.perusahaan.toLowerCase().includes(query)) ||
            (item.posisi && item.posisi.toLowerCase().includes(query))
        );
    }

    return result;
});

// Pagination computed
const totalPages = computed(() => {
    return Math.ceil(filteredAlumni.value.length / perPage.value) || 1;
});

const paginatedAlumni = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredAlumni.value.slice(start, start + perPage.value);
});

// Aksi Terapkan Filter Tahun Kelulusan
const applyTahunFilter = () => {
    router.get('/superadmin/linkedin-sync', {
        tahun: selectedTahun.value,
    }, {
        preserveState: false,
        preserveScroll: true,
    });
};

// Ambil Token CSRF
const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
};

// Toast Notifikasi Sederhana
const showToast = (icon, title) => {
    Swal.fire({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        icon,
        title,
    });
};

// =========================================================================
// SINKRONISASI INDIVIDUAL (ASYNC TANPA RELOAD HALAMAN)
// =========================================================================
const syncSingle = async (alumni) => {
    if (alumni.isSyncing) return;

    if (!alumni.linkedin_username) {
        showToast('warning', 'LinkedIn username belum tersedia.');
        return;
    }

    alumni.isSyncing = true;
    alumni.status_sync = 'Menyinkronkan...';
    alumni.status_color = 'indigo';

    try {
        const response = await fetch(`/superadmin/linkedin-sync/${alumni.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // Perbarui data baris secara reaktif
            alumni.perusahaan = data.alumni.perusahaan;
            alumni.posisi = data.alumni.posisi;
            alumni.status_sync = data.alumni.status_sync;
            alumni.status_color = data.alumni.status_color;
            alumni.terakhir_sync = data.alumni.terakhir_sync;
            alumni.terakhir_sync_human = data.alumni.terakhir_sync_human;

            showToast('success', `${alumni.nama}: ${data.message}`);
            recalculateLocalStats();
        } else {
            alumni.status_sync = 'Gagal';
            alumni.status_color = 'rose';
            showToast('error', `${alumni.nama}: ${data.message || 'Sinkronisasi gagal.'}`);
            recalculateLocalStats();
        }
    } catch (err) {
        alumni.status_sync = 'Gagal';
        alumni.status_color = 'rose';
        showToast('error', `${alumni.nama}: Terjadi kesalahan jaringan.`);
        recalculateLocalStats();
    } finally {
        alumni.isSyncing = false;
    }
};

// Hitung Ulang Metrik Lokal setelah Sync
const recalculateLocalStats = () => {
    let berhasil = 0;
    let gagal = 0;
    let dilewati = 0;

    alumniList.value.forEach(item => {
        if (!item.linkedin_username) {
            dilewati++;
        } else if (item.status_sync === 'Berhasil') {
            berhasil++;
        } else if (item.status_sync === 'Gagal') {
            gagal++;
        }
    });

    localStats.value.berhasil_sinkron = berhasil;
    localStats.value.gagal_sinkron = gagal;
    localStats.value.dilewati = dilewati;
};

// =========================================================================
// SINKRONISASI MASSAL (BULK SYNC DENGAN VISUAL PROGRESS)
// =========================================================================
const handleBulkSync = () => {
    const candidates = alumniList.value.filter(a => !!a.linkedin_username);

    if (candidates.length === 0) {
        Swal.fire({
            title: 'Tidak Ada Akun LinkedIn',
            text: `Tidak ada alumni pada tahun ${selectedTahun.value} yang memiliki username LinkedIn.`,
            icon: 'info',
            confirmButtonColor: '#0D542B',
        });
        return;
    }

    Swal.fire({
        title: `Sinkronkan Tahun ${selectedTahun.value}?`,
        html: `
            <div class="text-left text-xs text-slate-600 space-y-2">
                <p>Sistem akan menyinkronkan profil LinkedIn untuk <b>${candidates.length} alumni</b> yang memiliki username.</p>
                <p class="text-amber-700 bg-amber-50 p-2.5 rounded-lg border border-amber-200">
                    Proses berjalan secara independen per-alumni menggunakan Mock LinkedIn Provider resmi UKDW.
                </p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0D542B',
        cancelButtonColor: '#9CA3AF',
        confirmButtonText: 'Ya, Jalankan Sinkronisasi',
        cancelButtonText: 'Batal',
        reverseButtons: true,
    }).then(async (result) => {
        if (result.isConfirmed) {
            await executeBulkSync(candidates);
        }
    });
};

const executeBulkSync = async (candidates) => {
    isBulkSyncing.value = true;
    bulkProgress.value = {
        current: 0,
        total: candidates.length,
        percentage: 0,
    };

    let countBerhasil = 0;
    let countGagal = 0;
    const totalAlumniTahun = alumniList.value.length;
    const countDilewati = totalAlumniTahun - candidates.length;

    for (let i = 0; i < candidates.length; i++) {
        const alumni = candidates[i];
        bulkProgress.value.current = i + 1;
        bulkProgress.value.percentage = Math.round(((i + 1) / candidates.length) * 100);

        alumni.isSyncing = true;
        alumni.status_sync = 'Menyinkronkan...';
        alumni.status_color = 'indigo';

        try {
            const response = await fetch(`/superadmin/linkedin-sync/${alumni.id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
            });

            const data = await response.json();

            if (response.ok && data.success) {
                alumni.perusahaan = data.alumni.perusahaan;
                alumni.posisi = data.alumni.posisi;
                alumni.status_sync = data.alumni.status_sync;
                alumni.status_color = data.alumni.status_color;
                alumni.terakhir_sync = data.alumni.terakhir_sync;
                alumni.terakhir_sync_human = data.alumni.terakhir_sync_human;
                countBerhasil++;
            } else {
                alumni.status_sync = 'Gagal';
                alumni.status_color = 'rose';
                countGagal++;
            }
        } catch (e) {
            alumni.status_sync = 'Gagal';
            alumni.status_color = 'rose';
            countGagal++;
        } finally {
            alumni.isSyncing = false;
        }
    }

    isBulkSyncing.value = false;
    recalculateLocalStats();

    // Dialog Hasil Ringkasan Selesai (Format Resmi Sesuai Spesifikasi)
    Swal.fire({
        title: 'Sinkronisasi Selesai',
        html: `
            <div class="text-left font-mono text-xs bg-slate-50 p-4 rounded-lg border border-slate-200 space-y-1.5 text-slate-800">
                <div class="flex justify-between border-b pb-1 text-slate-500 font-sans font-semibold">
                    <span>Tahun Kelulusan</span>
                    <span class="text-slate-900">${selectedTahun.value}</span>
                </div>
                <div class="flex justify-between pt-1">
                    <span>Total Alumni</span>
                    <span class="font-bold text-slate-900">${totalAlumniTahun}</span>
                </div>
                <div class="flex justify-between text-emerald-700">
                    <span>Berhasil</span>
                    <span class="font-bold">${countBerhasil}</span>
                </div>
                <div class="flex justify-between text-rose-700">
                    <span>Gagal</span>
                    <span class="font-bold">${countGagal}</span>
                </div>
                <div class="flex justify-between text-amber-700">
                    <span>Dilewati</span>
                    <span class="font-bold">${countDilewati}</span>
                </div>
            </div>
        `,
        icon: 'success',
        confirmButtonColor: '#0D542B',
        confirmButtonText: 'Tutup',
    });
};
</script>

<template>
    <Head title="Sinkronisasi LinkedIn - Super Admin UKDW" />

    <div class="min-h-screen bg-slate-50 flex">
        <!-- SIDEBAR RESMI SUPER ADMIN -->
        <Sidebar />

        <!-- AREA KONTEN UTAMA -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-72">
            <main class="flex-1 p-4 lg:p-6 max-w-7xl w-full mx-auto space-y-5">
                
                <!-- HEADER HALAMAN & BADGE PROVIDER MOCK -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-4 lg:p-5 rounded-xl border border-slate-200 shadow-2xs">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-lg lg:text-xl font-bold text-slate-900">
                                Sinkronisasi LinkedIn
                            </h1>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                Mock LinkedIn Provider
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            Simulasi sinkronisasi profil karier alumni UKDW mengacu pada standar resmi LinkedIn Profile & Position Fields.
                        </p>
                    </div>

                    <!-- TOMBOL AKSI CEPAT BULK SYNC -->
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="handleBulkSync"
                            :disabled="isBulkSyncing"
                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold text-white bg-[#0D542B] hover:bg-[#093c1f] focus:ring-2 focus:ring-emerald-700 focus:outline-none transition-colors shadow-2xs disabled:opacity-50 cursor-pointer"
                        >
                            <svg v-if="!isBulkSyncing" class="w-4 h-4 shrink-0 fill-current" viewBox="0 0 24 24">
                                <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                            </svg>
                            <svg v-else class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>{{ isBulkSyncing ? `Menyinkronkan... (${bulkProgress.percentage}%)` : `Sinkronkan Tahun ${selectedTahun}` }}</span>
                        </button>
                    </div>
                </div>

                <!-- PROGRESS BAR BULK SYNC (JIKA SEDANG BERJALAN) -->
                <div v-if="isBulkSyncing" class="bg-white p-3.5 rounded-xl border border-emerald-200 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between text-xs font-semibold text-slate-700">
                        <span class="flex items-center gap-1.5 text-emerald-800">
                            <span class="w-2 h-2 rounded-full bg-emerald-600 animate-ping"></span>
                            Memproses sinkronisasi massal angkatan {{ selectedTahun }}...
                        </span>
                        <span class="text-slate-600 font-mono">{{ bulkProgress.current }} / {{ bulkProgress.total }} Alumni ({{ bulkProgress.percentage }}%)</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div
                            class="bg-[#0D542B] h-2 rounded-full transition-all duration-300"
                            :style="{ width: `${bulkProgress.percentage}%` }"
                        ></div>
                    </div>
                </div>

                <!-- 5 KARTU METRIK SUMMARY KPI (SESUAI DOKUMEN SPESIFIKASI) -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    <!-- 1. Total Alumni -->
                    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">
                            Total Alumni
                        </span>
                        <div class="flex items-baseline justify-between mt-1.5">
                            <span class="text-xl font-bold text-slate-900 font-mono">{{ localStats.total_alumni }}</span>
                            <span class="text-[10px] text-slate-400">Lulus {{ selectedTahun }}</span>
                        </div>
                    </div>

                    <!-- 2. Dengan LinkedIn -->
                    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
                        <span class="text-[11px] font-semibold text-sky-700 uppercase tracking-wider block">
                            Dengan LinkedIn
                        </span>
                        <div class="flex items-baseline justify-between mt-1.5">
                            <span class="text-xl font-bold text-sky-900 font-mono">{{ localStats.dengan_linkedin }}</span>
                            <span class="text-[10px] text-sky-600">Punya username</span>
                        </div>
                    </div>

                    <!-- 3. Berhasil Sinkron -->
                    <div class="bg-white p-3.5 rounded-xl border border-emerald-100 bg-emerald-50/20 shadow-2xs">
                        <span class="text-[11px] font-semibold text-emerald-800 uppercase tracking-wider block">
                            Berhasil Sinkron
                        </span>
                        <div class="flex items-baseline justify-between mt-1.5">
                            <span class="text-xl font-bold text-emerald-900 font-mono">{{ localStats.berhasil_sinkron }}</span>
                            <span class="text-[10px] text-emerald-700 font-medium">Data terpetakan</span>
                        </div>
                    </div>

                    <!-- 4. Gagal -->
                    <div class="bg-white p-3.5 rounded-xl border border-rose-100 bg-rose-50/20 shadow-2xs">
                        <span class="text-[11px] font-semibold text-rose-800 uppercase tracking-wider block">
                            Gagal
                        </span>
                        <div class="flex items-baseline justify-between mt-1.5">
                            <span class="text-xl font-bold text-rose-900 font-mono">{{ localStats.gagal_sinkron }}</span>
                            <span class="text-[10px] text-rose-600">Error / 404</span>
                        </div>
                    </div>

                    <!-- 5. Dilewati -->
                    <div class="bg-white p-3.5 rounded-xl border border-amber-100 bg-amber-50/20 shadow-2xs col-span-2 sm:col-span-1">
                        <span class="text-[11px] font-semibold text-amber-800 uppercase tracking-wider block">
                            Dilewati
                        </span>
                        <div class="flex items-baseline justify-between mt-1.5">
                            <span class="text-xl font-bold text-amber-900 font-mono">{{ localStats.dilewati }}</span>
                            <span class="text-[10px] text-amber-600">Tanpa username</span>
                        </div>
                    </div>
                </div>

                <!-- FILTER TAHUN KELULUSAN & PENCARIAN ALUMNI -->
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <div class="flex items-center gap-2">
                            <label for="filter-tahun" class="text-xs font-semibold text-slate-700 shrink-0">
                                Tahun Kelulusan:
                            </label>
                            <select
                                id="filter-tahun"
                                v-model="selectedTahun"
                                class="text-xs font-medium text-slate-900 bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-600 focus:outline-none"
                            >
                                <option v-for="th in daftarTahun" :key="th" :value="th">
                                    Tahun {{ th }}
                                </option>
                            </select>
                        </div>

                        <button
                            type="button"
                            @click="applyTahunFilter"
                            class="px-3.5 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300 transition-colors cursor-pointer"
                        >
                            Tampilkan
                        </button>
                    </div>

                    <!-- Pencarian Nama / NIM Cepat -->
                    <div class="relative w-full md:w-72">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari NIM, nama, perusahaan..."
                            class="w-full pl-8 pr-3 py-2 text-xs bg-slate-50 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-600 focus:outline-none"
                        />
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- TABEL DATA ALUMNI -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-700">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[10px] tracking-wider">
                                <tr>
                                    <th class="px-4 py-3">NIM</th>
                                    <th class="px-4 py-3">Nama Alumni</th>
                                    <th class="px-3 py-3 text-center">Tahun Lulus</th>
                                    <th class="px-4 py-3">LinkedIn Username</th>
                                    <th class="px-4 py-3">Perusahaan</th>
                                    <th class="px-4 py-3">Posisi / Jabatan</th>
                                    <th class="px-3 py-3 text-center">Status Sync</th>
                                    <th class="px-3 py-3 text-center">Terakhir Sync</th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-if="paginatedAlumni.length === 0">
                                    <td colspan="9" class="px-4 py-8 text-center text-slate-400">
                                        Tidak ada data alumni yang cocok dengan kriteria filter.
                                    </td>
                                </tr>

                                <tr
                                    v-for="alumni in paginatedAlumni"
                                    :key="alumni.id"
                                    class="hover:bg-slate-50/75 transition-colors"
                                >
                                    <!-- NIM -->
                                    <td class="px-4 py-3 font-mono font-medium text-slate-900">
                                        {{ alumni.nim }}
                                    </td>

                                    <!-- Nama -->
                                    <td class="px-4 py-3 font-semibold text-slate-900">
                                        {{ alumni.nama }}
                                    </td>

                                    <!-- Tahun Kelulusan -->
                                    <td class="px-3 py-3 text-center font-mono text-slate-600">
                                        {{ alumni.tahun_lulus }}
                                    </td>

                                    <!-- LinkedIn Username -->
                                    <td class="px-4 py-3">
                                        <div v-if="alumni.linkedin_username" class="flex items-center gap-1.5">
                                            <span class="font-mono text-sky-700">@{{ alumni.linkedin_username }}</span>
                                            <a
                                                v-if="alumni.linkedin_url"
                                                :href="alumni.linkedin_url"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-sky-600 hover:text-sky-800 transition-colors"
                                                title="Buka Profil"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                </svg>
                                            </a>
                                        </div>
                                        <span v-else class="text-slate-400 italic text-[11px]">- Belum ada -</span>
                                    </td>

                                    <!-- Perusahaan -->
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ alumni.perusahaan }}
                                    </td>

                                    <!-- Posisi -->
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ alumni.posisi }}
                                    </td>

                                    <!-- Status Sync (Badge Warna) -->
                                    <td class="px-3 py-3 text-center whitespace-nowrap">
                                        <!-- Menyinkronkan -->
                                        <span
                                            v-if="alumni.isSyncing"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 animate-pulse"
                                        >
                                            <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                            </svg>
                                            Menyinkronkan...
                                        </span>

                                        <!-- Berhasil -->
                                        <span
                                            v-else-if="alumni.status_sync === 'Berhasil'"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                            Berhasil
                                        </span>

                                        <!-- Gagal -->
                                        <span
                                            v-else-if="alumni.status_sync === 'Gagal'"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-800 border border-rose-200"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                            Gagal
                                        </span>

                                        <!-- Belum Ada Username -->
                                        <span
                                            v-else-if="!alumni.linkedin_username"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200"
                                        >
                                            Dilewati (Kosong)
                                        </span>

                                        <!-- Belum Disinkronkan -->
                                        <span
                                            v-else
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200"
                                        >
                                            Belum Disinkronkan
                                        </span>
                                    </td>

                                    <!-- Terakhir Sync -->
                                    <td class="px-3 py-3 text-center whitespace-nowrap font-mono text-[11px] text-slate-500">
                                        <span :title="alumni.terakhir_sync_human">{{ alumni.terakhir_sync }}</span>
                                    </td>

                                    <!-- Aksi Individual -->
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <button
                                            type="button"
                                            @click="syncSingle(alumni)"
                                            :disabled="alumni.isSyncing || !alumni.linkedin_username || isBulkSyncing"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[11px] font-semibold transition-colors shadow-2xs disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                                            :class="[
                                                alumni.linkedin_username
                                                    ? 'bg-[#0D542B] text-white hover:bg-[#093c1f]'
                                                    : 'bg-slate-100 text-slate-400'
                                            ]"
                                        >
                                            <svg v-if="!alumni.isSyncing" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            <svg v-else class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                            </svg>
                                            <span>Sinkronkan</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- FOOTER & PAGINASI LOKAL -->
                    <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600">
                        <div>
                            Menampilkan <b>{{ paginatedAlumni.length }}</b> dari <b>{{ filteredAlumni.length }}</b> alumni
                            <span v-if="filteredAlumni.length !== alumniList.length" class="text-slate-400">
                                (difilter dari total {{ alumniList.length }})
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="text-slate-500">Per halaman:</span>
                            <select
                                v-model="perPage"
                                class="text-xs bg-white border border-slate-300 rounded px-2 py-1 focus:ring-1 focus:ring-emerald-600 focus:outline-none"
                            >
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                            </select>

                            <div class="flex items-center gap-1 ml-2">
                                <button
                                    type="button"
                                    @click="currentPage = Math.max(1, currentPage - 1)"
                                    :disabled="currentPage === 1"
                                    class="px-2 py-1 rounded border border-slate-300 bg-white hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                                >
                                    &laquo;
                                </button>
                                <span class="px-2 font-mono">{{ currentPage }} / {{ totalPages }}</span>
                                <button
                                    type="button"
                                    @click="currentPage = Math.min(totalPages, currentPage + 1)"
                                    :disabled="currentPage === totalPages"
                                    class="px-2 py-1 rounded border border-slate-300 bg-white hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                                >
                                    &raquo;
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>
</template>
