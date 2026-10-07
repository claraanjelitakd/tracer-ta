<!--
  Halaman Direktori Mahasiswa & Alumni (Super Admin)
  File: resources/js/Pages/SuperAdmin/Alumni/Index.vue
  
  Warna Resmi Solid UKDW (Sesuai Logo & Gambar Pengguna):
  - Hijau: #0D542B (Solid, tanpa gradasi berlebihan)
  - Kuning: #FDC700 (Kuning UKDW murni)
  - Putih: #FFFFFF
  Desain profesional, bersih, elegan, bebas border berlebih dan bebas efek hover border.
-->
<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
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
    daftarTarget: {
        type: Array,
        default: () => [],
    },
    prodis: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({
            total_alumni: 0,
            total_selesai: 0,
            total_belum_selesai: 0,
            persentase_selesai: 0,
        }),
    },
});

// State Filter (Tahun Kelulusan default ke tahun terbaru)
const defaultTahun = props.filters.tahun || props.daftarTahun[0] || '';
const search = ref(props.filters.search || '');
const tahun = ref(defaultTahun);
const semester = ref(props.filters.semester || 'all');
const target = ref(props.filters.target || 'all');
const status = ref(props.filters.status || 'all');
const prodiId = ref(props.filters.prodi_id || 'all');

// Pagination lokal ala DataTables
const perPage = ref(10);
const currentPage = ref(1);

const totalPages = computed(() => {
    return Math.ceil(props.alumnis.length / perPage.value) || 1;
});

const paginatedAlumnis = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return props.alumnis.slice(start, start + perPage.value);
});

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

// Terapkan Filter ke URL
let searchTimeout = null;
const applyFilters = () => {
    currentPage.value = 1;
    router.get('/superadmin/alumni', {
        search: search.value || undefined,
        tahun: tahun.value || undefined,
        semester: semester.value !== 'all' ? semester.value : undefined,
        target: target.value !== 'all' ? target.value : undefined,
        status: status.value !== 'all' ? status.value : undefined,
        prodi_id: prodiId.value !== 'all' ? prodiId.value : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const handleSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
};

const resetFilters = () => {
    search.value = '';
    tahun.value = props.daftarTahun[0] || '';
    semester.value = 'all';
    target.value = 'all';
    status.value = 'all';
    prodiId.value = 'all';
    currentPage.value = 1;
    router.get('/superadmin/alumni', { tahun: tahun.value }, { preserveState: true });
};

// Fitur Unduh ZIP per Program Studi untuk Tahun Filter Tertentu
const handleDownloadZip = () => {
    const selectedYear = tahun.value || props.daftarTahun[0] || '';

    if (prodiId.value && prodiId.value !== 'all') {
        const url = `/superadmin/alumni/export-zip?prodi_id=${prodiId.value}&tahun=${encodeURIComponent(selectedYear)}`;
        window.location.href = url;
    } else {
        const prodiOptions = {};
        props.prodis.forEach((p) => {
            prodiOptions[p.id] = `${p.kode_prodi} - ${p.nama_prodi}`;
        });

        Swal.fire({
            title: 'Unduh ZIP Rekap Excel',
            text: `Pilih Program Studi untuk mengunduh arsip ZIP berisi berkas Excel (.xls) per alumni pada Tahun Kelulusan ${selectedYear}:`,
            input: 'select',
            inputOptions: prodiOptions,
            inputPlaceholder: '-- Pilih Program Studi --',
            showCancelButton: true,
            confirmButtonText: 'Unduh ZIP Sekarang',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#0D542B',
            cancelButtonColor: '#64748b',
            inputValidator: (value) => {
                if (!value) {
                    return 'Silakan pilih Program Studi terlebih dahulu!';
                }
            },
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                const url = `/superadmin/alumni/export-zip?prodi_id=${result.value}&tahun=${encodeURIComponent(selectedYear)}`;
                window.location.href = url;
            }
        });
    }
};
</script>

<template>
    <Head title="Daftar Mahasiswa & Alumni - Super Admin" />

    <div class="min-h-screen bg-[#f8fafc] text-gray-800 font-sans flex">
        <!-- Sidebar Resmi Super Admin -->
        <Sidebar />

        <!-- Area Konten Utama -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-72">
            
            <!-- Header Solid Hijau Resmi UKDW #0D542B -->
            <header class="bg-[#0D542B] text-white pt-8 pb-16 px-4 sm:px-6 lg:px-8">
                <div class="w-full max-w-[1400px] mx-auto flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2 text-xs text-white/80 font-medium mb-2">
                            <Link href="/superadmin/dashboard" class="hover:underline">Dashboard</Link>
                            <span>/</span>
                            <span class="text-white font-bold">Data Alumni</span>
                        </div>

                        <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                            Daftar Mahasiswa & Hasil Tracer
                        </h1>
                        <p class="text-white/90 text-sm sm:text-base font-normal mt-1 max-w-2xl leading-relaxed">
                            Direktori data mahasiswa seluruh program studi dan pemantauan status kelengkapan kuesioner tracer study UKDW.
                        </p>

                        <!-- Tombol Aksi Cepat Header: Kelola Soal & Download ZIP -->
                        <div class="flex items-center gap-3 mt-4 flex-wrap">
                            <Link 
                                href="/superadmin/evaluasi-atasan"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-white/15 hover:bg-white/25 border border-white/25 text-white rounded-xl text-xs font-bold transition-all shadow-xs"
                            >
                                <svg class="w-4 h-4 text-[#FDC700]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                <span>Kelola Soal Evaluasi Atasan</span>
                            </Link>

                            <button 
                                type="button"
                                @click="handleDownloadZip"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-[#FDC700] hover:bg-[#e5b400] text-gray-900 rounded-xl text-xs font-extrabold transition-all shadow-xs cursor-pointer"
                                title="Unduh arsip ZIP berisi berkas Excel per alumni untuk Program Studi pada tahun yang difilter"
                            >
                                <svg class="w-4 h-4 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span>Download ZIP (Excel per Alumni)</span>
                            </button>
                        </div>
                    </div>

                    <!-- Ringkasan Cepat di Header (Warna Kuning Resmi #FDC700) -->
                    <div class="bg-black/15 border border-white/20 px-6 py-4 rounded-2xl text-left md:text-right text-white">
                        <span class="text-xs text-white/80 font-bold uppercase tracking-wider block">Tingkat Kelulusan Kuesioner</span>
                        <span class="text-3xl font-black text-[#FDC700] block">{{ stats.persentase_selesai }}%</span>
                        <span class="text-xs text-white/90 font-medium">{{ stats.total_selesai }} dari {{ stats.total_alumni }} Mahasiswa Selesai</span>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="w-full max-w-[1400px] mx-auto -mt-10 px-4 sm:px-6 lg:px-8 space-y-6">
                
                <!-- 4 Kartu Statistik Ringkas & Profesional -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
                    <div class="bg-white rounded-2xl p-6 shadow-sm">
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Mahasiswa</p>
                        <p class="text-3xl font-extrabold text-gray-900 tracking-tight mt-2">{{ stats.total_alumni }}</p>
                        <p class="text-xs text-gray-400 mt-1">Tahun {{ tahun }}</p>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm">
                        <p class="text-xs font-bold text-[#0D542B] uppercase tracking-wider">Kuesioner Selesai</p>
                        <p class="text-3xl font-extrabold text-[#0D542B] tracking-tight mt-2">{{ stats.total_selesai }}</p>
                        <p class="text-xs text-gray-400 mt-1">Lengkap 100%</p>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm">
                        <p class="text-xs font-bold text-amber-600 uppercase tracking-wider">Belum Selesai</p>
                        <p class="text-3xl font-extrabold text-amber-600 tracking-tight mt-2">{{ stats.total_belum_selesai }}</p>
                        <p class="text-xs text-gray-400 mt-1">Perlu tindak lanjut</p>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm">
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Rasio Penyelesaian</p>
                        <p class="text-3xl font-extrabold text-[#0D542B] tracking-tight mt-2">{{ stats.persentase_selesai }}%</p>
                        <p class="text-xs text-gray-400 mt-1">Kepatuhan responden</p>
                    </div>
                </div>

                <!-- Panel Filter Komprehensif -->
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-5">
                        <div class="flex items-center gap-3">
                            <h2 class="text-sm font-extrabold text-gray-900 uppercase tracking-wider">
                                Filter & Pencarian Mahasiswa
                            </h2>
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-50 text-[#0D542B] font-bold">
                                Tahun Kelulusan: {{ tahun }}
                            </span>
                        </div>
                        <div class="flex items-center gap-4">
                            <button 
                                @click="handleDownloadZip"
                                class="text-xs text-[#0D542B] hover:text-[#08381c] font-bold inline-flex items-center gap-1 cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Unduh ZIP Prodi
                            </button>
                            <button 
                                @click="resetFilters" 
                                class="text-xs text-gray-500 hover:text-gray-800 font-bold transition-colors cursor-pointer"
                            >
                                Reset Filter
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                        <!-- Pencarian Nama / NIM -->
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Cari Nama / NIM</label>
                            <input 
                                type="text" 
                                v-model="search" 
                                @input="handleSearchInput" 
                                placeholder="Ketik nama atau NIM..." 
                                class="w-full text-xs rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 font-medium text-gray-900 focus:bg-white focus:border-[#0D542B] focus:ring-1 focus:ring-[#0D542B] outline-none"
                            />
                        </div>

                        <!-- Filter Tahun Kelulusan (Hanya daftar tahun aktual, tanpa 'all', default tahun terbaru) -->
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Tahun Kelulusan *</label>
                            <select 
                                v-model="tahun" 
                                @change="applyFilters" 
                                class="w-full text-xs rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 font-bold text-gray-900 focus:bg-white focus:border-[#0D542B] focus:ring-1 focus:ring-[#0D542B] outline-none"
                            >
                                <option v-for="t in daftarTahun" :key="t" :value="t">
                                    {{ t }}
                                </option>
                            </select>
                        </div>

                        <!-- Filter Target Kelulusan -->
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Target Kelulusan</label>
                            <select 
                                v-model="target" 
                                @change="applyFilters" 
                                class="w-full text-xs rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 font-medium text-gray-900 focus:bg-white focus:border-[#0D542B] focus:ring-1 focus:ring-[#0D542B] outline-none"
                            >
                                <option value="all">Semua Target</option>
                                <option v-for="tgt in daftarTarget" :key="tgt" :value="tgt">
                                    Target {{ tgt }}
                                </option>
                            </select>
                        </div>

                        <!-- Filter Semester Kelulusan -->
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Semester</label>
                            <select 
                                v-model="semester" 
                                @change="applyFilters" 
                                class="w-full text-xs rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 font-medium text-gray-900 focus:bg-white focus:border-[#0D542B] focus:ring-1 focus:ring-[#0D542B] outline-none"
                            >
                                <option value="all">Semua Semester</option>
                                <option value="Gasal">Gasal</option>
                                <option value="Genap">Genap</option>
                            </select>
                        </div>

                        <!-- Filter Status Pengisian -->
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Status Kuesioner</label>
                            <select 
                                v-model="status" 
                                @change="applyFilters" 
                                class="w-full text-xs rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 font-medium text-gray-900 focus:bg-white focus:border-[#0D542B] focus:ring-1 focus:ring-[#0D542B] outline-none"
                            >
                                <option value="all">Semua Status</option>
                                <option value="selesai">Selesai</option>
                                <option value="belum_selesai">Belum Selesai</option>
                            </select>
                        </div>

                        <!-- Filter Program Studi -->
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Program Studi</label>
                            <select 
                                v-model="prodiId" 
                                @change="applyFilters" 
                                class="w-full text-xs rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 font-medium text-gray-900 focus:bg-white focus:border-[#0D542B] focus:ring-1 focus:ring-[#0D542B] outline-none"
                            >
                                <option value="all">Semua Program Studi</option>
                                <option v-for="p in prodis" :key="p.id" :value="p.id">
                                    {{ p.kode_prodi }} - {{ p.nama_prodi }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tabel Mahasiswa ala DataTables -->
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                    <!-- DataTables Top Bar -->
                    <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50/50">
                        <div class="flex items-center gap-2 text-xs font-medium text-gray-600">
                            <span>Tampilkan</span>
                            <select v-model="perPage" class="text-xs rounded-lg border border-gray-200 bg-white py-1.5 px-2.5 font-bold text-gray-800 outline-none">
                                <option :value="5">5</option>
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                            </select>
                            <span>data per halaman</span>
                        </div>

                        <div class="text-xs text-gray-500 font-medium">
                            Menampilkan <span class="font-bold text-gray-900">{{ (currentPage - 1) * perPage + 1 }}</span> &ndash; 
                            <span class="font-bold text-gray-900">{{ Math.min(currentPage * perPage, alumnis.length) }}</span> dari 
                            <span class="font-bold text-gray-900">{{ alumnis.length }}</span> total mahasiswa (Tahun {{ tahun }})
                        </div>
                    </div>

                    <!-- Table Content -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-100 uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th class="py-4 px-5">No</th>
                                    <th class="py-4 px-5">Mahasiswa / Alumni</th>
                                    <th class="py-4 px-5">Program Studi</th>
                                    <th class="py-4 px-5">Target Kelulusan</th>
                                    <th class="py-4 px-5 text-center">Profil</th>
                                    <th class="py-4 px-5 text-center">Kuesioner</th>
                                    <th class="py-4 px-5 text-center">Evaluasi Atasan</th>
                                    <th class="py-4 px-5 text-center">Status</th>
                                    <th class="py-4 px-5 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr 
                                    v-for="(alumni, idx) in paginatedAlumnis" 
                                    :key="alumni.id" 
                                    class="hover:bg-gray-50 transition-colors"
                                >
                                    <td class="py-4 px-5 font-mono font-medium text-gray-400">
                                        {{ (currentPage - 1) * perPage + idx + 1 }}
                                    </td>

                                    <!-- Identitas Mahasiswa -->
                                    <td class="py-4 px-5">
                                        <div class="font-extrabold text-gray-900 text-xs sm:text-sm">
                                            {{ alumni.nama }}
                                        </div>
                                        <div class="font-mono text-gray-500 text-[11px] mt-0.5">
                                            {{ alumni.nim }}
                                        </div>
                                    </td>

                                    <!-- Program Studi -->
                                    <td class="py-4 px-5">
                                        <span class="font-semibold text-gray-800">{{ alumni.prodi }}</span>
                                    </td>

                                    <!-- Periode / Target Kelulusan -->
                                    <td class="py-4 px-5">
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-50 text-[#0D542B] border border-emerald-200 text-[11px] font-bold">
                                            <span>Target: {{ alumni.tahun_akademik_lulus }}</span>
                                        </div>
                                        <div class="text-[10px] text-gray-400 mt-1">
                                            Tahun {{ alumni.tahun_lulus }} &bull; Status: <span class="font-semibold text-gray-600">{{ alumni.status_yudisium }}</span>
                                        </div>
                                    </td>

                                    <!-- Kelengkapan Profil (%) -->
                                    <td class="py-4 px-5 text-center">
                                        <span class="font-extrabold text-xs" :class="alumni.kelengkapan.profile.is_complete ? 'text-[#0D542B]' : 'text-gray-700'">
                                            {{ alumni.kelengkapan.profile.percentage }}%
                                        </span>
                                    </td>

                                    <!-- Kuesioner Wajib (%) -->
                                    <td class="py-4 px-5 text-center">
                                        <span class="font-extrabold text-xs" :class="alumni.kelengkapan.questionnaire.is_complete ? 'text-[#0D542B]' : 'text-gray-700'">
                                            {{ alumni.kelengkapan.questionnaire.percentage }}%
                                        </span>
                                    </td>

                                    <!-- Evaluasi Atasan (Badge Status) -->
                                    <td class="py-4 px-5 text-center">
                                        <span 
                                            v-if="alumni.evaluasi_atasan?.is_submitted"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-[#0D542B]"
                                            :title="`Telah diisi pada ${alumni.evaluasi_atasan.submitted_at}`"
                                        >
                                            <svg class="w-3 h-3 text-[#0D542B]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                            Sudah Diisi
                                        </span>
                                        <span 
                                            v-else-if="alumni.evaluasi_atasan?.has_evaluasi"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800"
                                            title="Menunggu pengisian dari pimpinan/atasan tempat bekerja"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            Menunggu
                                        </span>
                                        <span 
                                            v-else
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-500"
                                        >
                                            Belum Ada
                                        </span>
                                    </td>

                                    <!-- Status Akhir -->
                                    <td class="py-4 px-5 text-center">
                                        <span 
                                            v-if="alumni.kelengkapan.is_complete" 
                                            class="px-3.5 py-1 bg-[#0D542B] text-white font-bold rounded-full text-xs inline-block"
                                        >
                                            Selesai
                                        </span>
                                        <span 
                                            v-else 
                                            class="px-3.5 py-1 bg-[#FDC700] text-black font-bold rounded-full text-xs inline-block"
                                        >
                                            Belum Selesai
                                        </span>
                                    </td>

                                    <!-- Tombol Aksi Detail -->
                                    <td class="py-4 px-5 text-center">
                                        <Link 
                                            :href="`/superadmin/alumni/${alumni.id}`" 
                                            class="px-4 py-1.5 bg-[#0D542B] hover:bg-[#08381c] text-white rounded-xl text-xs font-bold transition-all inline-block cursor-pointer"
                                        >
                                            Lihat Detail
                                        </Link>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="alumnis.length === 0">
                                    <td colspan="9" class="py-16 text-center text-gray-500">
                                        <p class="font-bold text-gray-800 text-sm">Tidak Ada Mahasiswa Ditemukan</p>
                                        <p class="text-xs text-gray-400 mt-1">Tidak ada catatan data alumni untuk Tahun Kelulusan {{ tahun }} yang cocok dengan filter aktif.</p>
                                        <button 
                                            @click="resetFilters" 
                                            class="mt-4 px-5 py-2 bg-[#0D542B] text-white text-xs font-bold rounded-xl hover:bg-[#08381c] transition-colors"
                                        >
                                            Reset Filter
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- DataTables Pagination Controls -->
                    <div v-if="alumnis.length > 0" class="p-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50/50">
                        <div class="text-xs text-gray-500">
                            Halaman <span class="font-bold text-gray-900">{{ currentPage }}</span> dari <span class="font-bold text-gray-900">{{ totalPages }}</span>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button 
                                @click="goToPage(currentPage - 1)" 
                                :disabled="currentPage === 1"
                                class="px-3 py-1.5 bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                            >
                                Sebelumnya
                            </button>
                            
                            <button 
                                v-for="p in totalPages" 
                                :key="p"
                                @click="goToPage(p)"
                                class="w-8 h-8 rounded-xl text-xs font-bold transition-colors"
                                :class="currentPage === p ? 'bg-[#0D542B] text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-100'"
                            >
                                {{ p }}
                            </button>

                            <button 
                                @click="goToPage(currentPage + 1)" 
                                :disabled="currentPage === totalPages"
                                class="px-3 py-1.5 bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                            >
                                Selanjutnya
                            </button>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>
</template>
