<!--
  Halaman: Dashboard Utama Admin Fakultas
  File: resources/js/Pages/AdminFakultas/Dashboard.vue

  Warna Resmi Solid UKDW (Identik dengan Super Admin):
  - Hijau: #0D542B
  - Kuning: #FDC700
  - Putih & Netral: #FFFFFF / #F8FAFC
-->
<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Sidebar from './Components/Sidebar.vue';

const props = defineProps({
    user: Object,
    fakultas: Object,
    prodis: Array,
    prodiSummaries: Array,
    recentAlumni: Array,
    stats: {
        type: Object,
        default: () => ({
            total_alumni: 0,
            total_selesai: 0,
            total_belum_selesai: 0,
            persentase_selesai: 0,
            total_prodi: 0,
        }),
    },
});
</script>

<template>
    <Head :title="`Dashboard Admin Fakultas - ${fakultas?.nama_fakultas || 'Fakultas'}`" />

    <div class="min-h-screen bg-[#f8fafc] text-gray-800 font-sans flex">
        <!-- Sidebar Terpadu Admin Fakultas -->
        <Sidebar :user="user" :fakultas="fakultas" />

        <!-- Area Konten Utama -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-72">
            <!-- Header Halaman Bersih & Flat -->
            <div class="bg-white border-b border-gray-200 px-6 py-5">
                <div class="w-full flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 text-xs text-gray-500 font-medium mb-1">
                            <span>Tracer Study</span>
                            <span>/</span>
                            <span class="text-gray-800 font-semibold">Dashboard Fakultas</span>
                        </div>
                        <h1 class="text-xl font-bold text-gray-900">
                            Dashboard {{ fakultas?.nama_fakultas || 'Fakultas' }}
                        </h1>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Pemantauan capaian kuesioner tracer study seluruh program studi di bawah naungan fakultas.
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <div class="px-3.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-700 text-xs">
                            <span class="text-[10px] text-slate-500 uppercase tracking-wider block font-semibold">Partisipasi Fakultas</span>
                            <span class="font-bold text-[#0D542B] text-xs">{{ stats.persentase_selesai }}% ({{ stats.total_selesai }}/{{ stats.total_alumni }})</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area (Penuh, Flat, Tanpa Negative Margin) -->
            <main class="flex-1 p-6 space-y-6 overflow-x-auto min-w-0">
                
                <!-- 4 KPI Summary Cards (Identik dengan Super Admin) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div class="bg-white rounded-lg p-5 border border-slate-200 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Alumni</span>
                            <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-gray-100 text-gray-700">Fakultas</span>
                        </div>
                        <div class="text-3xl font-extrabold text-gray-900 tracking-tight mt-3">{{ stats.total_alumni }}</div>
                        <p class="mt-1 text-xs text-gray-400">Seluruh prodi di fakultas</p>
                    </div>

                    <div class="bg-white rounded-lg p-5 border border-slate-200 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-[#0D542B] uppercase tracking-wider">Tracer Selesai</span>
                            <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-[#0D542B] text-white">Lengkap</span>
                        </div>
                        <div class="text-3xl font-extrabold text-[#0D542B] tracking-tight mt-3">{{ stats.total_selesai }}</div>
                        <p class="mt-1 text-xs text-gray-400">Profil & kuesioner lengkap</p>
                    </div>

                    <div class="bg-white rounded-lg p-5 border border-slate-200 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Belum Selesai</span>
                            <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-gray-100 text-gray-700">Proses</span>
                        </div>
                        <div class="text-3xl font-extrabold text-gray-900 tracking-tight mt-3">{{ stats.total_belum_selesai }}</div>
                        <p class="mt-1 text-xs text-gray-400">Masih dalam pengisian</p>
                    </div>

                    <div class="bg-white rounded-lg p-5 border border-slate-200 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Cakupan Prodi</span>
                            <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-gray-100 text-gray-700">Struktur</span>
                        </div>
                        <div class="text-3xl font-extrabold text-gray-900 tracking-tight mt-3">{{ stats.total_prodi }}</div>
                        <p class="mt-1 text-xs text-gray-400">Program studi aktif</p>
                    </div>
                </div>

                <!-- Grid 2 Modul Navigasi Utama -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Modul 1: Direktori Alumni Fakultas -->
                    <div class="bg-white rounded-lg p-6 border border-slate-200 shadow-2xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="px-3 py-1 bg-[#0D542B] text-white text-xs font-bold rounded-lg uppercase tracking-wider">
                                    Direktori Fakultas
                                </span>
                                <span class="text-xs font-semibold text-gray-400">
                                    Filter & Audit Alumni
                                </span>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 tracking-tight mb-2">
                                Data Alumni {{ fakultas?.nama_fakultas }}
                            </h2>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                Pantau seluruh data mahasiswa dan alumni berdasarkan program studi, tahun kelulusan, dan status kelengkapan kuesioner tracer study.
                            </p>
                        </div>

                        <div class="mt-8 pt-4 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-xs text-gray-500 font-medium">
                                Filter Prodi, Tahun & Status
                            </span>
                            <Link 
                                href="/fakultas/alumni" 
                                class="inline-flex items-center px-6 py-3 bg-[#0D542B] hover:bg-[#08381c] text-white text-xs font-bold rounded-lg shadow-xs transition-all cursor-pointer"
                            >
                                <span>Buka Data Alumni</span>
                                <span class="ml-1.5">&rarr;</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Modul 2: Evaluasi Partisipasi Prodi -->
                    <div class="bg-white rounded-lg p-6 border border-slate-200 shadow-2xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="px-3 py-1 bg-[#FDC700] text-black text-xs font-bold rounded-lg uppercase tracking-wider">
                                    Capaian Prodi
                                </span>
                                <span class="text-xs font-semibold text-gray-400">
                                    {{ prodis?.length || stats.total_prodi }} Program Studi
                                </span>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 tracking-tight mb-2">
                                Distribusi Respon Program Studi
                            </h2>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                Evaluasi tingkat kelengkapan pengisian tracer study pada setiap program studi di lingkungan {{ fakultas?.nama_fakultas }}.
                            </p>
                        </div>

                        <div class="mt-8 pt-4 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-xs font-medium text-gray-500">
                                Tingkat Selesai: <strong class="text-[#0D542B]">{{ stats.persentase_selesai }}%</strong>
                            </span>
                            <Link 
                                href="/fakultas/alumni" 
                                class="inline-flex items-center px-6 py-3 bg-[#0D542B] hover:bg-[#08381c] text-white text-xs font-bold rounded-lg shadow-xs transition-all cursor-pointer"
                            >
                                <span>Lihat Seluruh Alumni</span>
                                <span class="ml-1.5">&rarr;</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Tabel Distribusi Partisipasi per Program Studi dalam Fakultas -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h3 class="text-sm font-black uppercase tracking-wider text-gray-900">
                                Distribusi Partisipasi per Program Studi
                            </h3>
                            <p class="text-xs text-gray-500">Rekapitulasi respon tracer study program studi di {{ fakultas?.nama_fakultas }}.</p>
                        </div>
                        <Link 
                            href="/fakultas/alumni" 
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
                                    <th class="px-6 py-3.5 text-center">Responden Selesai</th>
                                    <th class="px-6 py-3.5 text-left w-52">Tingkat Partisipasi</th>
                                    <th class="px-6 py-3.5 text-center">Aksi</th>
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
                                                    class="h-2 rounded-md transition-all duration-500" 
                                                    :class="prodi.response_rate >= 50 ? 'bg-[#0D542B]' : 'bg-[#FDC700]'"
                                                    :style="{ width: `${Math.min(prodi.response_rate, 100)}%` }"
                                                ></div>
                                            </div>
                                            <span class="w-10 text-right font-bold text-gray-800">{{ prodi.response_rate }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <Link 
                                            :href="`/fakultas/alumni?prodi_id=${prodi.id}`"
                                            class="px-3 py-1.5 bg-gray-100 hover:bg-[#0D542B] hover:text-white rounded-lg text-xs font-bold text-gray-800 transition-colors inline-block"
                                        >
                                            Filter Alumni
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 5 Data Alumni Terbaru -->
                <div v-if="recentAlumni && recentAlumni.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-sm font-black uppercase tracking-wider text-gray-900">
                            5 Mahasiswa / Alumni Terkini
                        </h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-left text-xs">
                            <thead class="bg-gray-50 text-gray-600 font-bold uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th class="px-6 py-3.5">NIM & Nama</th>
                                    <th class="px-6 py-3.5">Program Studi</th>
                                    <th class="px-6 py-3.5">Tahun Lulus</th>
                                    <th class="px-6 py-3.5 text-center">Status</th>
                                    <th class="px-6 py-3.5 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="alumni in recentAlumni" :key="alumni.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-extrabold text-gray-900">{{ alumni.nama }}</div>
                                        <div class="font-mono text-gray-500 text-[11px]">{{ alumni.nim }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-800">
                                        {{ alumni.prodi }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">
                                        {{ alumni.tahun_lulus }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span 
                                            v-if="alumni.is_complete"
                                            class="inline-block px-3 py-1 bg-[#0D542B] text-white font-bold text-xs rounded-md"
                                        >
                                            Selesai
                                        </span>
                                        <span 
                                            v-else
                                            class="inline-block px-3 py-1 bg-[#FDC700] text-black font-bold text-xs rounded-md"
                                        >
                                            Belum Selesai
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <Link 
                                            :href="`/fakultas/alumni/${alumni.id}`"
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

            <!-- Footer Elegan & Minimalis -->
            <footer class="text-gray-400 text-xs text-center mt-16 py-6 border-t border-gray-100">
                <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
                    &copy; {{ new Date().getFullYear() }} Universitas Kristen Duta Wacana. Hak Cipta Dilindungi.
                </div>
            </footer>
        </div>
    </div>
</template>
