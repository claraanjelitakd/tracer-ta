<!--
  Halaman Direktori Mahasiswa & Alumni (Program Studi)
  File: resources/js/Pages/AdminProdi/Alumni/Index.vue

  Warna Resmi Solid UKDW:
  - Hijau: #0D542B
  - Kuning: #FDC700
  - Putih & Netral: #FFFFFF / #F8FAFC
  Struktur: Fixed Sidebar + DataTables Pagination + Filter Tahun/Semester/Status Khusus Prodi
-->
<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Sidebar from '../Components/Sidebar.vue';

const props = defineProps({
    user: Object,
    prodi: Object,
    alumnis: {
        type: Array,
        default: () => [],
    },
    daftarTahun: {
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

// State Filter
const search = ref(props.filters.search || '');
const tahun = ref(props.filters.tahun || 'all');
const semester = ref(props.filters.semester || 'all');
const status = ref(props.filters.status || 'all');

// Pagination lokal DataTables
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
    router.get('/prodi/alumni', {
        search: search.value || undefined,
        tahun: tahun.value !== 'all' ? tahun.value : undefined,
        semester: semester.value !== 'all' ? semester.value : undefined,
        status: status.value !== 'all' ? status.value : undefined,
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
    tahun.value = 'all';
    semester.value = 'all';
    status.value = 'all';
    currentPage.value = 1;
    router.get('/prodi/alumni', {}, { preserveState: true });
};
</script>

<template>
    <Head :title="`Direktori Alumni - ${prodi?.nama_prodi || 'Program Studi'}`" />

    <div class="min-h-screen bg-[#f8fafc] text-gray-800 font-sans flex">
        <!-- Sidebar Resmi Program Studi -->
        <Sidebar :user="user" :prodi="prodi" />

        <!-- Area Konten Utama -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-72">
            
            <!-- Header Solid Hijau Resmi UKDW #0D542B -->
            <header class="bg-[#0D542B] text-white pt-8 pb-16 px-4 sm:px-6 lg:px-8">
                <div class="w-full max-w-[1400px] mx-auto flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2 text-xs text-white/80 font-medium mb-2">
                            <Link href="/prodi/dashboard" class="hover:underline">Dashboard</Link>
                            <span>/</span>
                            <span class="text-white font-bold">Data Alumni Prodi</span>
                        </div>

                        <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                            Alumni {{ prodi?.nama_prodi || 'Program Studi' }}
                        </h1>
                        <p class="text-white/90 text-sm sm:text-base font-normal mt-1 max-w-2xl leading-relaxed">
                            Database mahasiswa & lulusan program studi, audit keterisian kuesioner tracer study univ & prodi.
                        </p>
                    </div>

                    <!-- Ringkasan Cepat di Header -->
                    <div class="bg-black/15 border border-white/20 px-6 py-4 rounded-2xl text-left md:text-right text-white">
                        <span class="text-xs text-white/80 font-bold uppercase tracking-wider block">Kelengkapan Tracer</span>
                        <span class="text-3xl font-black text-[#FDC700] block">{{ stats.persentase_selesai }}%</span>
                        <span class="text-xs text-white/90 font-medium">{{ stats.total_selesai }} dari {{ stats.total_alumni }} Mahasiswa Selesai</span>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="w-full max-w-[1400px] mx-auto -mt-10 px-4 sm:px-6 lg:px-8 space-y-6 pb-16">
                
                <!-- 4 Kartu Statistik Ringkas -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
                    <div class="bg-white rounded-2xl p-6 shadow-sm">
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Alumni Prodi</p>
                        <p class="text-3xl font-extrabold text-gray-900 tracking-tight mt-2">{{ stats.total_alumni }}</p>
                        <p class="text-xs text-gray-400 mt-1">Terdaftar di {{ prodi?.nama_prodi }}</p>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm">
                        <p class="text-xs font-bold text-[#0D542B] uppercase tracking-wider">Tracer Selesai</p>
                        <p class="text-3xl font-extrabold text-[#0D542B] tracking-tight mt-2">{{ stats.total_selesai }}</p>
                        <p class="text-xs text-gray-400 mt-1">Profil & seluruh kuesioner lengkap</p>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm">
                        <p class="text-xs font-bold text-gray-700 uppercase tracking-wider">Belum Selesai</p>
                        <p class="text-3xl font-extrabold text-gray-900 tracking-tight mt-2">{{ stats.total_belum_selesai }}</p>
                        <p class="text-xs text-gray-400 mt-1">Masih dalam proses</p>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm">
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Rasio Partisipasi</p>
                        <p class="text-3xl font-extrabold text-[#0D542B] tracking-tight mt-2">{{ stats.persentase_selesai }}%</p>
                        <p class="text-xs text-gray-400 mt-1">Keterisian kuesioner</p>
                    </div>
                </div>

                <!-- Panel Filter -->
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-5">
                        <h2 class="text-sm font-extrabold text-gray-900 uppercase tracking-wider">
                            Filter & Pencarian Alumni Prodi
                        </h2>
                        <button 
                            @click="resetFilters" 
                            class="text-xs text-[#0D542B] hover:underline font-bold transition-colors cursor-pointer"
                        >
                            Reset Filter
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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

                        <!-- Filter Tahun Kelulusan -->
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Tahun Kelulusan</label>
                            <select 
                                v-model="tahun" 
                                @change="applyFilters" 
                                class="w-full text-xs rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 font-medium text-gray-900 focus:bg-white focus:border-[#0D542B] focus:ring-1 focus:ring-[#0D542B] outline-none"
                            >
                                <option value="all">Semua Tahun (All)</option>
                                <option v-for="t in daftarTahun" :key="t" :value="t">
                                    {{ t }}
                                </option>
                            </select>
                        </div>

                        <!-- Filter Semester Kelulusan -->
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Semester Kelulusan</label>
                            <select 
                                v-model="semester" 
                                @change="applyFilters" 
                                class="w-full text-xs rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 font-medium text-gray-900 focus:bg-white focus:border-[#0D542B] focus:ring-1 focus:ring-[#0D542B] outline-none"
                            >
                                <option value="all">Semua Semester (All)</option>
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
                                <option value="all">Semua Status (All)</option>
                                <option value="selesai">Selesai</option>
                                <option value="belum_selesai">Belum Selesai</option>
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
                            <span class="font-bold text-gray-900">{{ alumnis.length }}</span> total alumni
                        </div>
                    </div>

                    <!-- Table Content -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-100 uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th class="py-4 px-5">No</th>
                                    <th class="py-4 px-5">Mahasiswa / Alumni</th>
                                    <th class="py-4 px-5">Tahun & Semester</th>
                                    <th class="py-4 px-5 text-center">Profil</th>
                                    <th class="py-4 px-5 text-center">Kuesioner Univ</th>
                                    <th class="py-4 px-5 text-center">Kuesioner Prodi</th>
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

                                    <!-- Periode Kelulusan -->
                                    <td class="py-4 px-5">
                                        <span class="font-medium text-gray-600">
                                            {{ alumni.tahun_akademik_lulus }}
                                        </span>
                                    </td>

                                    <!-- Kelengkapan Profil (%) -->
                                    <td class="py-4 px-5 text-center">
                                        <span class="font-extrabold text-xs" :class="alumni.kelengkapan.profile.is_complete ? 'text-[#0D542B]' : 'text-gray-700'">
                                            {{ alumni.kelengkapan.profile.percentage }}%
                                        </span>
                                    </td>

                                    <!-- Kuesioner Univ (%) -->
                                    <td class="py-4 px-5 text-center">
                                        <span class="font-extrabold text-xs" :class="alumni.kelengkapan.questionnaire.is_complete ? 'text-[#0D542B]' : 'text-gray-700'">
                                            {{ alumni.kelengkapan.questionnaire.percentage }}%
                                        </span>
                                    </td>

                                    <!-- Kuesioner Prodi (%) -->
                                    <td class="py-4 px-5 text-center">
                                        <span class="font-extrabold text-xs" :class="alumni.kelengkapan.prodi.is_complete ? 'text-[#0D542B]' : 'text-gray-700'">
                                            {{ alumni.kelengkapan.prodi.percentage }}%
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
                                            :href="`/prodi/alumni/${alumni.id}`" 
                                            class="px-4 py-1.5 bg-[#0D542B] hover:bg-[#08381c] text-white rounded-xl text-xs font-bold transition-all inline-block cursor-pointer"
                                        >
                                            Lihat Detail
                                        </Link>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="alumnis.length === 0">
                                    <td colspan="8" class="py-16 text-center text-gray-500">
                                        <p class="font-bold text-gray-800 text-sm">Tidak Ada Mahasiswa Ditemukan</p>
                                        <p class="text-xs text-gray-400 mt-1">Tidak ada catatan data alumni yang cocok dengan filter aktif.</p>
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
