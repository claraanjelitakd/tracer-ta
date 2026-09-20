<!--
  Halaman: Dashboard Utama Admin Fakultas
  File: resources/js/Pages/AdminFakultas/Dashboard.vue

  Warna Resmi Solid UKDW:
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
            <!-- Header Solid Hijau Resmi UKDW #0D542B -->
            <header class="bg-[#0D542B] text-white pt-8 pb-16 px-4 sm:px-6 lg:px-8">
                <div class="w-full max-w-[1400px] mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center px-3 py-1 bg-black/20 text-white rounded-full text-xs font-bold uppercase tracking-wider mb-2">
                            Administrator Fakultas
                        </div>
                        <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                            Dashboard {{ fakultas?.nama_fakultas || 'Fakultas' }}
                        </h1>
                        <p class="text-white/90 text-sm sm:text-base font-normal mt-1 max-w-2xl leading-relaxed">
                            Pemantauan capaian kuesioner tracer study seluruh program studi di bawah naungan fakultas.
                        </p>
                    </div>

                    <div class="bg-black/15 border border-white/20 px-5 py-3 rounded-2xl text-left md:text-right text-white">
                        <span class="text-xs text-white/80 font-semibold uppercase tracking-wider block">Partisipasi Fakultas</span>
                        <span class="text-3xl font-black text-[#FDC700] block">{{ stats.persentase_selesai }}%</span>
                        <span class="text-xs text-white/90 font-medium">{{ stats.total_selesai }} dari {{ stats.total_alumni }} Responden</span>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="w-full max-w-[1400px] mx-auto -mt-10 px-4 sm:px-6 lg:px-8 space-y-6 pb-16">
                
                <!-- 4 KPI Summary Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Alumni Fakultas</p>
                        <p class="text-3xl font-extrabold text-gray-900 tracking-tight mt-2">{{ stats.total_alumni }}</p>
                        <p class="text-xs text-gray-400 mt-1">Seluruh prodi di fakultas</p>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <p class="text-xs font-bold text-[#0D542B] uppercase tracking-wider">Tracer Selesai</p>
                        <p class="text-3xl font-extrabold text-[#0D542B] tracking-tight mt-2">{{ stats.total_selesai }}</p>
                        <p class="text-xs text-gray-400 mt-1">Profil & kuesioner lengkap</p>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <p class="text-xs font-bold text-gray-700 uppercase tracking-wider">Belum Selesai</p>
                        <p class="text-3xl font-extrabold text-gray-900 tracking-tight mt-2">{{ stats.total_belum_selesai }}</p>
                        <p class="text-xs text-gray-400 mt-1">Masih dalam pengisian</p>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Cakupan Prodi</p>
                        <p class="text-3xl font-extrabold text-[#0D542B] tracking-tight mt-2">{{ stats.total_prodi }}</p>
                        <p class="text-xs text-gray-400 mt-1">Program studi aktif</p>
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
                                            <div class="flex-1 bg-gray-200 rounded-full h-2 overflow-hidden">
                                                <div 
                                                    class="h-2 rounded-full transition-all duration-500" 
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
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
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
        </div>
    </div>
</template>
