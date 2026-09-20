<!--
  Halaman: Dashboard Utama Admin Program Studi
  File: resources/js/Pages/AdminProdi/Dashboard.vue
  
  Warna Resmi Solid UKDW (Identik dengan Super Admin):
  - Hijau: #0D542B (Solid, tanpa gradasi berlebih)
  - Kuning: #FDC700 (Kuning UKDW murni)
  - Putih: #FFFFFF
  Desain profesional, bersih, bebas border bertumpuk, bebas icon berlebih / slop.
-->
<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Sidebar from './Components/Sidebar.vue';

const props = defineProps({
    user: Object,
    prodi: Object,
    stats: {
        type: Object,
        default: () => ({
            totalAlumni: 0,
            totalSections: 0,
            totalPertanyaan: 0,
            totalRespondenProdi: 0,
            persentasePartisipasi: 0,
            totalUnivSelesai: 0,
            persentaseUniv: 0,
        }),
    },
    recentAlumnis: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head :title="`Dashboard Admin Prodi - ${prodi?.nama_prodi || 'Tracer Study UKDW'}`" />

    <div class="min-h-screen bg-[#f8fafc] text-gray-800 font-sans flex">
        <!-- Sidebar Terpadu Admin Program Studi -->
        <Sidebar :user="user" :prodi="prodi" />

        <!-- Area Konten Utama -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-72">
            <!-- Header Solid Hijau Resmi UKDW #0D542B -->
            <header class="bg-[#0D542B] text-white pt-8 pb-16 px-4 sm:px-6 lg:px-8">
                <div class="w-full max-w-[1400px] mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center px-3 py-1 bg-black/20 text-white rounded-full text-xs font-bold uppercase tracking-wider mb-2">
                            Administrator Program Studi
                        </div>
                        <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                            Dashboard {{ prodi?.nama_prodi || 'Program Studi' }}
                        </h1>
                        <p class="text-white/90 text-sm sm:text-base font-normal mt-1 max-w-2xl leading-relaxed">
                            Pusat kendali instrumen kuesioner prodi, pemantauan kelengkapan tracer study alumni prodi, dan direktori data alumni UKDW.
                        </p>
                    </div>


                <div class="bg-black/15 border border-white/20 px-5 py-3 rounded-2xl text-left md:text-right text-white">
                    <span class="text-xs text-white/80 font-semibold uppercase tracking-wider block">Program Studi</span>
                    <span class="text-base font-extrabold block text-[#FDC700]">{{ prodi?.nama_prodi }} ({{ prodi?.kode_prodi }})</span>
                    <span class="text-xs text-white/80">{{ user?.name }} &bull; {{ user?.username }}</span>
                </div>
            </div>
        </header>

        <!-- Main Body Area -->
        <main class="w-full max-w-[1400px] mx-auto -mt-10 px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- Grid 4 Kartu KPI Metrik (Putih Bersih, Tanpa Border Hover) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                
                <!-- KPI 1: Total Pertanyaan Prodi -->
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Pertanyaan Prodi
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700">
                            Instrumen
                        </span>
                    </div>
                    <div class="text-3xl font-extrabold text-gray-900 tracking-tight mt-3">
                        {{ stats.totalPertanyaan }}
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        Butir instrumen aktif prodi
                    </p>
                </div>

                <!-- KPI 2: Total Bagian (Sections) -->
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Bagian (Section)
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700">
                            Struktur
                        </span>
                    </div>
                    <div class="text-3xl font-extrabold text-gray-900 tracking-tight mt-3">
                        {{ stats.totalSections }}
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        Tahapan kuesioner prodi
                    </p>
                </div>

                <!-- KPI 3: Basis Data Alumni Prodi -->
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Alumni Terdaftar
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700">
                            Database
                        </span>
                    </div>
                    <div class="text-3xl font-extrabold text-gray-900 tracking-tight mt-3">
                        {{ stats.totalAlumni }}
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        Mahasiswa & alumni prodi
                    </p>
                </div>

                <!-- KPI 4: Partisipasi Respon Prodi -->
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Responden Masuk
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#0D542B] text-white">
                            Respon
                        </span>
                    </div>
                    <div class="text-3xl font-extrabold text-[#0D542B] tracking-tight mt-3">
                        {{ stats.totalRespondenProdi }}
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        {{ stats.persentasePartisipasi }}% partisipasi alumni prodi
                    </p>
                </div>

            </div>

            <!-- Grid 2 Modul Navigasi Utama -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Modul 1: Direktori Mahasiswa & Hasil Tracer -->
                <div class="bg-white rounded-3xl p-8 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="px-3 py-1 bg-[#0D542B] text-white text-xs font-bold rounded-lg uppercase tracking-wider">
                                Direktori Mahasiswa
                            </span>
                            <span class="text-xs font-semibold text-gray-400">
                                DataTables & Audit Tracer
                            </span>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 tracking-tight mb-2">
                            Daftar Mahasiswa & Hasil Tracer Prodi
                        </h2>
                        <p class="text-sm text-gray-600 leading-relaxed">
                            Pantau alumni program studi {{ prodi?.nama_prodi }} berdasarkan tahun dan semester kelulusan. Tinjau kelengkapan kuesioner universitas, kuesioner prodi, serta detail profil.
                        </p>
                    </div>

                    <div class="mt-8 pt-4 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-xs text-gray-500 font-medium">
                            Filter Tahun, Semester, & Status Respon
                        </span>
                        <Link 
                            href="/prodi/alumni" 
                            class="inline-flex items-center px-6 py-3 bg-[#0D542B] hover:bg-[#08381c] text-white text-xs font-bold rounded-xl shadow-xs transition-all cursor-pointer"
                        >
                            Buka Direktori Alumni &rarr;
                        </Link>
                    </div>
                </div>

                <!-- Modul 2: Kelola Instrumen Kuesioner & Section -->
                <div class="bg-white rounded-3xl p-8 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="px-3 py-1 bg-[#FDC700] text-black text-xs font-bold rounded-lg uppercase tracking-wider">
                                Pengaturan Instrumen
                            </span>
                            <span class="text-xs font-semibold text-gray-400">
                                Section & Pertanyaan
                            </span>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 tracking-tight mb-2">
                            Kelola Kuesioner & Bagian (Section)
                        </h2>
                        <p class="text-sm text-gray-600 leading-relaxed">
                            Susun instrumen kuesioner evaluasi kurikulum prodi. Tambahkan bagian kuesioner, atur urutan pertanyaan, dan kelola pilihan opsi yang relevan bagi lulusan.
                        </p>
                    </div>

                    <div class="mt-8 pt-4 border-t border-gray-100 flex items-center justify-between gap-2">
                        <Link 
                            href="/prodi/sections" 
                            class="inline-flex items-center px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-bold rounded-xl transition-all cursor-pointer"
                        >
                            Kelola Section
                        </Link>
                        <Link 
                            href="/prodi/pertanyaan" 
                            class="inline-flex items-center px-6 py-3 bg-[#0D542B] hover:bg-[#08381c] text-white text-xs font-bold rounded-xl shadow-xs transition-all cursor-pointer"
                        >
                            Kelola Butir Pertanyaan &rarr;
                        </Link>
                    </div>
                </div>

            </div>

            <!-- Tabel Ringkasan Alumni Terbaru (Bersih ala DataTables) -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <div>
                        <h3 class="text-sm font-extrabold text-gray-900 uppercase tracking-wider">
                            Alumni Terbaru & Status Pengisian
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Status kelengkapan kuesioner universitas dan prodi untuk alumni terdaftar terbaru
                        </p>
                    </div>
                    <Link 
                        href="/prodi/alumni"
                        class="text-xs font-bold text-[#0D542B] hover:underline"
                    >
                        Lihat Seluruh Alumni &rarr;
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-100 uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="py-3.5 px-5">NIM & Mahasiswa</th>
                                <th class="py-3.5 px-5">Tahun Kelulusan</th>
                                <th class="py-3.5 px-5 text-center">Kuesioner Universitas</th>
                                <th class="py-3.5 px-5 text-center">Kuesioner Prodi</th>
                                <th class="py-3.5 px-5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="recentAlumnis.length === 0">
                                <td colspan="5" class="py-10 text-center text-gray-400">
                                    Belum ada data alumni terdata untuk program studi ini.
                                </td>
                            </tr>
                            <tr 
                                v-for="alumni in recentAlumnis" 
                                :key="alumni.id"
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <td class="py-4 px-5">
                                    <div class="font-extrabold text-gray-900 text-sm">{{ alumni.nama }}</div>
                                    <div class="font-mono text-gray-400 text-xs mt-0.5">{{ alumni.nim }}</div>
                                </td>
                                <td class="py-4 px-5 text-gray-600 font-medium">
                                    {{ alumni.tahun_lulus }}
                                </td>
                                <td class="py-4 px-5 text-center">
                                    <span 
                                        v-if="alumni.is_univ_complete"
                                        class="inline-block px-3 py-1 bg-[#0D542B] text-white font-bold text-xs rounded-full"
                                    >
                                        Selesai
                                    </span>
                                    <span 
                                        v-else
                                        class="inline-block px-3 py-1 bg-gray-100 text-gray-600 font-medium text-xs rounded-full"
                                    >
                                        Belum Lengkap
                                    </span>
                                </td>
                                <td class="py-4 px-5 text-center">
                                    <span 
                                        v-if="alumni.is_prodi_complete"
                                        class="inline-block px-3 py-1 bg-[#0D542B] text-white font-bold text-xs rounded-full"
                                    >
                                        Selesai
                                    </span>
                                    <span 
                                        v-else
                                        class="inline-block px-3 py-1 bg-[#FDC700] text-black font-bold text-xs rounded-full"
                                    >
                                        Belum Selesai
                                    </span>
                                </td>
                                <td class="py-4 px-5 text-center">
                                    <Link 
                                        :href="`/prodi/alumni/${alumni.id}`"
                                        class="inline-flex items-center px-3.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-bold rounded-lg transition-colors cursor-pointer"
                                    >
                                        Detail
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
        </div>
    </div>
</template>
