<!--
  Halaman: Dashboard Utama Admin Biro 3
  File: resources/js/Pages/AdminBiroTiga/Dashboard.vue
  
  Warna Resmi Solid UKDW (Identik dengan Super Admin):
  - Hijau: #0D542B (Solid, tanpa gradasi berlebih)
  - Kuning: #FDC700 (Kuning UKDW murni)
  - Putih: #FFFFFF
  Desain profesional, bersih, konsisten di seluruh stakeholder.
-->
<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Sidebar from './Components/Sidebar.vue';

const props = defineProps({
    user: Object,
    stats: {
        type: Object,
        default: () => ({
            total_alumni: 0,
            total_responden: 0,
            persentase_respon: 0,
            total_pertanyaan: 0,
            total_prodi: 0,
            alumni_linkedin: 0,
        }),
    },
    prodiSummaries: {
        type: Array,
        default: () => [],
    },
    recentAlumni: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Dashboard Admin Biro 3 - Tracer Study UKDW" />

    <div class="min-h-screen bg-[#f8fafc] text-gray-800 flex font-sans">
        
        <!-- Sidebar Terpadu Admin Biro 3 -->
        <Sidebar :user="user" />

        <!-- Area Konten Utama -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-72">
            
            <!-- Header Solid Hijau Resmi UKDW #0D542B -->
            <header class="bg-[#0D542B] text-white pt-8 pb-16 px-4 sm:px-6 lg:px-8">
                <div class="w-full max-w-[1400px] mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center px-3 py-1 bg-black/20 text-white rounded-md text-xs font-bold uppercase tracking-wider mb-2">
                            Biro 3 Kemahasiswaan & Alumni
                        </div>
                        <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                            Dashboard Admin Biro 3
                        </h1>
                        <p class="text-white/90 text-sm sm:text-base font-normal mt-1 max-w-2xl leading-relaxed">
                            Pusat pemantauan capaian tracer study universitas, verifikasi basis data alumni, dan audit kelengkapan kuesioner UKDW.
                        </p>
                    </div>

                    <div class="bg-black/15 border border-white/20 px-5 py-3 rounded-2xl text-left md:text-right text-white">
                        <span class="text-xs text-white/80 font-semibold uppercase tracking-wider block">Partisipasi Universitas</span>
                        <span class="text-base font-extrabold block text-[#FDC700]">{{ stats?.persentase_respon || 0 }}% Respon</span>
                        <span class="text-xs text-white/80">{{ stats?.total_responden || 0 }} dari {{ stats?.total_alumni || 0 }} Alumni</span>
                    </div>
                </div>
            </header>

            <!-- Main Body Area -->
            <main class="w-full max-w-[1400px] mx-auto -mt-10 px-4 sm:px-6 lg:px-8 space-y-6 pb-16">
                
                <!-- Grid 4 Kartu KPI Metrik (Identik dengan Super Admin) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    
                    <!-- KPI 1: Total Alumni -->
                    <div class="bg-white rounded-lg p-5 border border-slate-200 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Total Alumni
                            </span>
                            <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-gray-100 text-gray-700">
                                Database
                            </span>
                        </div>
                        <div class="text-3xl font-extrabold text-gray-900 tracking-tight mt-3">
                            {{ stats?.total_alumni || 0 }}
                        </div>
                        <p class="mt-1 text-xs text-gray-400">
                            Dari {{ stats?.total_prodi || 0 }} Program Studi
                        </p>
                    </div>

                    <!-- KPI 2: Responden Masuk -->
                    <div class="bg-white rounded-lg p-5 border border-slate-200 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Responden Masuk
                            </span>
                            <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-[#0D542B] text-white">
                                Respon
                            </span>
                        </div>
                        <div class="text-3xl font-extrabold text-[#0D542B] tracking-tight mt-3">
                            {{ stats?.total_responden || 0 }}
                        </div>
                        <p class="mt-1 text-xs text-gray-400">
                            {{ stats?.persentase_respon || 0 }}% tingkat respon
                        </p>
                    </div>

                    <!-- KPI 3: LinkedIn Terdata -->
                    <div class="bg-white rounded-lg p-5 border border-slate-200 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Profil LinkedIn
                            </span>
                            <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-gray-100 text-gray-700">
                                Karir
                            </span>
                        </div>
                        <div class="text-3xl font-extrabold text-gray-900 tracking-tight mt-3">
                            {{ stats?.alumni_linkedin || 0 }}
                        </div>
                        <p class="mt-1 text-xs text-gray-400">
                            Tersinkronisasi karir
                        </p>
                    </div>

                    <!-- KPI 4: Total Pertanyaan -->
                    <div class="bg-white rounded-lg p-5 border border-slate-200 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Butir Instrumen
                            </span>
                            <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-gray-100 text-gray-700">
                                Instrumen
                            </span>
                        </div>
                        <div class="text-3xl font-extrabold text-gray-900 tracking-tight mt-3">
                            {{ stats?.total_pertanyaan || 0 }}
                        </div>
                        <p class="mt-1 text-xs text-gray-400">
                            Pertanyaan kuesioner aktif
                        </p>
                    </div>

                </div>

                <!-- Grid 2 Modul Navigasi Utama -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Modul 1: Data Alumni & Audit Tracer -->
                    <div class="bg-white rounded-lg p-6 border border-slate-200 shadow-2xs flex flex-col justify-between">
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
                                Data Alumni & Kelengkapan Kuesioner
                            </h2>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                Pantau seluruh mahasiswa berdasarkan tahun dan semester kelulusan. Tinjau status kelengkapan kuesioner wajib, persentase data profil, serta verifikasi profil LinkedIn.
                            </p>
                        </div>

                        <div class="mt-8 pt-4 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-xs text-gray-500 font-medium">
                                Filter Tahun, Semester, Status & Prodi
                            </span>
                            <Link 
                                href="/biro3/alumni" 
                                class="inline-flex items-center px-6 py-3 bg-[#0D542B] hover:bg-[#08381c] text-white text-xs font-bold rounded-lg shadow-xs transition-all cursor-pointer"
                            >
                                <span>Buka Data Alumni</span>
                                <span class="ml-1.5">&rarr;</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Modul 2: Rekapitulasi per Program Studi -->
                    <div class="bg-white rounded-lg p-6 border border-slate-200 shadow-2xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="px-3 py-1 bg-[#FDC700] text-black text-xs font-bold rounded-lg uppercase tracking-wider">
                                    Capaian Prodi
                                </span>
                                <span class="text-xs font-semibold text-gray-400">
                                    {{ prodiSummaries?.length || 0 }} Program Studi
                                </span>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 tracking-tight mb-2">
                                Distribusi Responden per Program Studi
                            </h2>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                Evaluasi tingkat keterisian kuesioner Tracer Study pada masing-masing program studi untuk memastikan tercapainya target IKU universitas.
                            </p>
                        </div>

                        <div class="mt-8 pt-4 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-xs font-medium text-gray-500">
                                Tingkat Partisipasi: <strong class="text-[#0D542B]">{{ stats?.persentase_respon || 0 }}%</strong>
                            </span>
                            <Link 
                                href="/biro3/alumni" 
                                class="inline-flex items-center px-6 py-3 bg-[#0D542B] hover:bg-[#08381c] text-white text-xs font-bold rounded-lg shadow-xs transition-all cursor-pointer"
                            >
                                <span>Lihat Laporan Lengkap</span>
                                <span class="ml-1.5">&rarr;</span>
                            </Link>
                        </div>
                    </div>

                </div>

                <!-- Tabel Distribusi Partisipasi per Program Studi -->
                <div v-if="prodiSummaries && prodiSummaries.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h3 class="text-sm font-black uppercase tracking-wider text-gray-900">
                                Rekapitulasi Partisipasi Program Studi
                            </h3>
                            <p class="text-xs text-gray-500">Tingkat respons kuesioner per program studi di lingkungan UKDW.</p>
                        </div>
                        <Link 
                            href="/biro3/alumni" 
                            class="text-xs font-bold text-[#0D542B] hover:underline"
                        >
                            Lihat Semua Alumni &rarr;
                        </Link>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-left text-xs">
                            <thead class="bg-gray-50 text-gray-600 font-bold uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th class="px-6 py-3.5 w-20">Kode</th>
                                    <th class="px-6 py-3.5">Nama Program Studi</th>
                                    <th class="px-6 py-3.5 text-center">Total Alumni</th>
                                    <th class="px-6 py-3.5 text-center">Responden Masuk</th>
                                    <th class="px-6 py-3.5 text-left w-52">Tingkat Partisipasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="prodi in prodiSummaries" :key="prodi.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 font-mono font-bold text-gray-900">
                                        {{ prodi.kode_prodi }}
                                    </td>
                                    <td class="px-6 py-4 font-bold text-gray-900">
                                        {{ prodi.nama_prodi }}
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-gray-700">
                                        {{ prodi.total_alumni }}
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-[#0D542B]">
                                        {{ prodi.total_responden }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-1 bg-gray-200 rounded-md h-2 overflow-hidden">
                                                <div 
                                                    class="bg-[#0D542B] h-2 rounded-md transition-all"
                                                    :style="{ width: `${Math.min(prodi.response_rate, 100)}%` }"
                                                ></div>
                                            </div>
                                            <span class="font-mono font-bold text-gray-700 text-xs w-12 text-right">
                                                {{ prodi.response_rate }}%
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>

            <!-- Footer Elegan & Minimalis -->
            <footer class="text-gray-400 text-xs text-center mt-16 py-6 border-t border-gray-100">
                <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
                    &copy; {{ new Date().getFullYear() }} Universitas Kristen Duta Wacana. Hak Cipta Dilindungi.
                </div>
            </footer>

        </div>
    </div>
</template>
