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
            
            <!-- Header Solid Hijau Resmi UKDW #0D542B -->
            <header class="bg-[#0D542B] text-white pt-8 pb-16 px-4 sm:px-6 lg:px-8">
                <div class="w-full max-w-[1400px] mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center px-3 py-1 bg-black/20 text-white rounded-full text-xs font-bold uppercase tracking-wider mb-2">
                        Super Administrator
                    </div>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                        Dashboard Super Admin
                    </h1>
                    <p class="text-white/90 text-sm sm:text-base font-normal mt-1 max-w-2xl leading-relaxed">
                        Pusat kendali instrumen kuesioner, pemantauan kelengkapan tracer study, dan direktori data mahasiswa UKDW.
                    </p>
                </div>

                <div class="bg-black/15 border border-white/20 px-5 py-3 rounded-2xl text-left md:text-right text-white">
                    <span class="text-xs text-white/80 font-semibold uppercase tracking-wider block">Akun Pengguna</span>
                    <span class="text-base font-extrabold block text-[#FDC700]">{{ user?.name || 'Super Admin' }}</span>
                    <span class="text-xs text-white/80">{{ user?.email || 'admin@ukdw.ac.id' }}</span>
                </div>
            </div>
        </header>

        <!-- Main Body Area -->
        <main class="w-full max-w-[1400px] mx-auto -mt-10 px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- Grid 4 Kartu KPI Metrik (Putih Bersih, Tanpa Border Hover) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                
                <!-- KPI 1: Total Pertanyaan -->
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Total Pertanyaan
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700">
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
                        {{ stats?.total_sections || 0 }}
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        Tahapan kuesioner
                    </p>
                </div>

                <!-- KPI 3: Basis Data Alumni -->
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
                        {{ stats?.total_alumni || 0 }}
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        Dari {{ stats?.total_prodi || 0 }} Program Studi
                    </p>
                </div>

                <!-- KPI 4: Partisipasi Respon -->
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
                        {{ stats?.total_responden || 0 }}
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        Alumni telah mengisi
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
                            class="inline-flex items-center px-6 py-3 bg-[#0D542B] hover:bg-[#08381c] text-white text-xs font-bold rounded-xl shadow-xs transition-all cursor-pointer"
                        >
                            <span>Buka Data Mahasiswa</span>
                            <span class="ml-1.5">&rarr;</span>
                        </Link>
                    </div>
                </div>

                <!-- Modul 2: Kelola Instrumen Kuesioner -->
                <div class="bg-white rounded-3xl p-8 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="px-3 py-1 bg-[#FDC700] text-black text-xs font-bold rounded-lg uppercase tracking-wider">
                                Instrumen Kuesioner
                            </span>
                            <span class="text-xs font-semibold text-gray-400">
                                Butir Pertanyaan & Section
                            </span>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 tracking-tight mb-2">
                            Pengelolaan Butir Pertanyaan & Bagian
                        </h2>
                        <p class="text-sm text-gray-600 leading-relaxed">
                            Kelola butir pertanyaan Tracer Study, opsi jawaban, atur alur lompatan (*jump logic*), susun urutan pertanyaan, dan tentukan kuesioner khusus program studi.
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
                            class="inline-flex items-center px-6 py-3 bg-[#0D542B] hover:bg-[#08381c] text-white text-xs font-bold rounded-xl shadow-xs transition-all cursor-pointer"
                        >
                            <span>Kelola Pertanyaan</span>
                            <span class="ml-1.5">&rarr;</span>
                        </Link>
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
