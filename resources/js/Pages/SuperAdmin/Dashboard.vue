<!--
  Halaman: Dashboard Utama Super Admin
  File: resources/js/Pages/SuperAdmin/Dashboard.vue
  
  Warna Resmi Solid UKDW (Sesuai Logo & Gambar Pengguna):
  - Hijau: #0D542B (Solid, tanpa gradasi)
  - Kuning: #FDC700 (Kuning UKDW murni)
  - Putih: #FFFFFF
  Desain profesional, bersih, bebas border bertumpuk-tumpuk, bebas border hover.
-->
<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Sidebar from '@/Pages/SuperAdmin/Components/Sidebar.vue';

const props = defineProps({
    user: Object,
    stats: Object,
});
</script>

<template>
    <Head title="Dashboard Super Admin - Tracer Study UKDW" />

    <div class="min-h-screen bg-[#f8fafc] text-gray-800 flex font-sans">
        
        <!-- Sidebar Terpadu Super Admin (Desktop + Mobile Drawer) -->
        <Sidebar :user="user" />

        <!-- Area Konten Utama -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-72">
            
            <!-- Header Halaman Bersih & Flat -->
            <div class="bg-white border-b border-gray-200 px-6 py-5">
                <div class="w-full flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 text-xs text-gray-500 font-medium mb-1">
                            <span>Tracer Study</span>
                            <span>/</span>
                            <span class="text-gray-800 font-semibold">Super Admin</span>
                        </div>
                        <h1 class="text-xl font-bold text-gray-900">
                            Dashboard Super Administrator
                        </h1>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Pusat kendali instrumen kuesioner, pemantauan kelengkapan tracer study, dan direktori data mahasiswa UKDW.
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <div class="px-3.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-700 text-xs">
                            <span class="text-[10px] text-slate-500 uppercase tracking-wider block font-semibold">Akun Aktif</span>
                            <span class="font-bold text-[#0D542B] text-xs">{{ user?.name || 'Super Admin' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Body Area (Penuh, Flat, Tanpa Negative Margin) -->
            <main class="flex-1 p-6 space-y-6 overflow-x-auto min-w-0">
            
            <!-- Grid 4 Kartu KPI Metrik (Putih Bersih, Tanpa Border Hover) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                
                <!-- KPI 1: Total Pertanyaan -->
                <div class="bg-white rounded-lg p-5 border border-slate-200 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Total Pertanyaan
                        </span>
                        <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-gray-100 text-gray-700">
                            Instrumen
                        </span>
                    </div>
                    <div class="text-3xl font-extrabold text-gray-900 tracking-tight mt-3">
                        {{ stats?.total_pertanyaan || 0 }}
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        Butir instrumen aktif
                    </p>
                </div>

                <!-- KPI 2: Total Bagian (Sections) -->
                <div class="bg-white rounded-lg p-5 border border-slate-200 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Bagian (Section)
                        </span>
                        <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-gray-100 text-gray-700">
                            Struktur
                        </span>
                    </div>
                    <div class="text-3xl font-extrabold text-gray-900 tracking-tight mt-3">
                        {{ stats?.total_sections || 0 }}
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        Tahapan kuesioner
                    </p>
                </div>

                <!-- KPI 3: Basis Data Alumni -->
                <div class="bg-white rounded-lg p-5 border border-slate-200 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Alumni Terdaftar
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

                <!-- KPI 4: Partisipasi Respon -->
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
                        Alumni telah mengisi
                    </p>
                </div>

            </div>

            <!-- Grid Modul Navigasi Utama -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- Modul 1: Direktori Mahasiswa & Hasil Tracer -->
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
                            Daftar Mahasiswa & Kelengkapan Tracer
                        </h2>
                        <p class="text-sm text-gray-600 leading-relaxed">
                            Pantau seluruh mahasiswa berdasarkan tahun dan semester kelulusan. Tinjau status kelengkapan kuesioner wajib, persentase data profil, dan bantu mutasi data profil jika diperlukan.
                        </p>
                    </div>

                    <div class="mt-8 pt-4 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-xs text-gray-500 font-medium">
                            Filter Tahun, Semester, Status & Prodi
                        </span>
                        <Link 
                            href="/superadmin/alumni" 
                            class="inline-flex items-center px-6 py-3 bg-[#0D542B] hover:bg-[#08381c] text-white text-xs font-bold rounded-lg shadow-xs transition-all cursor-pointer"
                        >
                            <span>Buka Data Mahasiswa</span>
                            <span class="ml-1.5">&rarr;</span>
                        </Link>
                    </div>
                </div>

                <!-- Modul 2: Kelola Instrumen Kuesioner Master -->
                <div class="bg-white rounded-lg p-6 border border-slate-200 shadow-2xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="px-3 py-1 bg-[#FDC700] text-black text-xs font-bold rounded-lg uppercase tracking-wider">
                                Kuesioner Master
                            </span>
                            <span class="text-xs font-semibold text-gray-400">
                                Pertanyaan Utama
                            </span>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 tracking-tight mb-2">
                            Pengelolaan Kuesioner Inti / Master
                        </h2>
                        <p class="text-sm text-gray-600 leading-relaxed">
                            Kelola butir pertanyaan Tracer Study utama, opsi jawaban, atur alur lompatan (*jump logic*), susun urutan pertanyaan, dan tentukan section induk.
                        </p>
                    </div>

                    <div class="mt-8 pt-4 border-t border-gray-100 flex items-center justify-between">
                        <Link 
                            href="/superadmin/sections"
                            class="text-xs font-bold text-[#0D542B] hover:underline"
                        >
                            Kelola Section &rarr;
                        </Link>
                        <Link 
                            href="/superadmin/pertanyaan" 
                            class="inline-flex items-center px-6 py-3 bg-[#0D542B] hover:bg-[#08381c] text-white text-xs font-bold rounded-lg shadow-xs transition-all cursor-pointer"
                        >
                            <span>Kelola Pertanyaan</span>
                            <span class="ml-1.5">&rarr;</span>
                        </Link>
                    </div>
                </div>

                <!-- Modul 3: Pengaturan Kuesioner Per Prodi -->
                <div class="bg-white rounded-lg p-6 border border-slate-200 shadow-2xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="px-3 py-1 bg-emerald-100 text-[#0D542B] border border-emerald-300 text-xs font-bold rounded-lg uppercase tracking-wider">
                                Kuesioner Prodi
                            </span>
                            <span class="text-xs font-semibold text-emerald-800 font-bold">
                                Superadmin Control
                            </span>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 tracking-tight mb-2">
                            Pengaturan Kuesioner Per Prodi
                        </h2>
                        <p class="text-sm text-gray-600 leading-relaxed">
                            Pilih program studi spesifik lalu kelola daftar bagian (section), butir pertanyaan, dan opsi jawaban khusus untuk prodi tersebut.
                        </p>
                    </div>

                    <div class="mt-8 pt-4 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-xs text-slate-500 font-medium">
                            Kelola Section & Pertanyaan Prodi
                        </span>
                        <Link 
                            href="/superadmin/prodi-kuesioner" 
                            class="inline-flex items-center px-6 py-3 bg-[#0D542B] hover:bg-[#08381c] text-white text-xs font-bold rounded-lg shadow-xs transition-all cursor-pointer"
                        >
                            <span>Buka Kuesioner Prodi</span>
                            <span class="ml-1.5">&rarr;</span>
                        </Link>
                    </div>
                </div>

                <!-- Modul 4: ACC Perusahaan Pengajuan Alumni (All / Per Prodi) -->
                <div class="bg-white rounded-lg p-6 border border-slate-200 shadow-2xs flex flex-col justify-between md:col-span-2 lg:col-span-3">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-3 py-1 bg-amber-500 text-white text-xs font-bold rounded-lg uppercase tracking-wider">
                                    ACC Perusahaan
                                </span>
                                <span v-if="stats?.total_pending_perusahaan > 0" class="px-2.5 py-0.5 bg-amber-100 text-amber-900 font-bold text-xs rounded-full border border-amber-300">
                                    {{ stats.total_pending_perusahaan }} Perusahaan Menunggu ACC
                                </span>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 tracking-tight">
                                Verifikasi & Approval Perusahaan Pengajuan Alumni
                            </h2>
                            <p class="text-sm text-gray-600 leading-relaxed mt-1">
                                Otoritas Superadmin untuk melakukan verifikasi langsung, penggantian master perusahaan (auto replace), penyuntingan atribut, atau penolakan pengajuan tempat kerja alumni secara menyeluruh (All) maupun terfilter per Program Studi.
                            </p>
                        </div>
                        <div class="shrink-0">
                            <Link 
                                href="/superadmin/perusahaan" 
                                class="inline-flex items-center px-6 py-3 bg-[#0D542B] hover:bg-[#08381c] text-white text-xs font-bold rounded-lg shadow-xs transition-all cursor-pointer whitespace-nowrap"
                            >
                                <span>Kelola ACC Perusahaan</span>
                                <span class="ml-1.5">&rarr;</span>
                            </Link>
                        </div>
                    </div>
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
