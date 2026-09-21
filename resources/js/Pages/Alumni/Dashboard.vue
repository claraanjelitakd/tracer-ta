<!--
  Halaman: Dashboard Alumni (Frontend)
  File: resources/js/Pages/Alumni/Dashboard.vue
  
  DIRELOAD OLEH BACKEND DARI:
  Controller: App\Http\Controllers\Alumni\Dashboard\DashboardController.php (method tampilkanDashboard)
  Route URL : /alumni/dashboard (GET)
-->
<script setup>
// Mengimpor modul resmi dari Inertia.js:
// - Head   : Untuk mengubah judul tab browser (<title>)
// - Link   : Komponen navigasi pengganti <a href> agar pindah halaman tanpa reload layar putih
// - router : Untuk mengirim perintah cepat ke backend (seperti logout, delete, POST)
import { Head, Link, router } from '@inertiajs/vue3';
import Navbar from './Components/Navbar.vue';

/**
 * ====================================================================
 * MENERIMA DATA (PROPS) DARI BACKEND
 * ====================================================================
 * Data di bawah ini dikirim langsung oleh DashboardController.php (baris 52-58)
 * melalui fungsi Inertia::render('Alumni/Dashboard', [...])
 */
defineProps({
    user: Object,
    alumni: Object,
    profilePercentage: {
        type: Number,
        default: 0,
    },
    profileCompleted: {
        type: Boolean,
        default: false,
    },
    profileFilledCount: {
        type: Number,
        default: 0,
    },
    profileTotalCount: {
        type: Number,
        default: 23,
    },
    profileMissingFields: {
        type: Array,
        default: () => [],
    },
    questionnairePercentage: {
        type: Number,
        default: 0,
    },
    questionnaireCompleted: {
        type: Boolean,
        default: false,
    },
    questionnaireAnsweredCount: {
        type: Number,
        default: 0,
    },
    questionnaireTotalCount: {
        type: Number,
        default: 61,
    },
    questionnaireMissing: {
        type: Array,
        default: () => [],
    },
    prodiQuestionsCount: {
        type: Number,
        default: 0,
    },
    prodiAnsweredCount: {
        type: Number,
        default: 0,
    },
    prodiCompleted: {
        type: Boolean,
        default: false,
    },
});

/**
 * ====================================================================
 * FUNGSI-FUNGSI AKSI JAVASCRIPT
 * ====================================================================
 */

/**
 * Fungsi logout:
 * - Dijalankan saat tombol "Logout" di navbar atas diklik (@click="logout")
 * - Mengirim request POST ke URL '/logout'
 * - Ditangani di Backend oleh: Laravel Fortify / AuthenticatedSessionController
 * - Efek: Sesi user dihapus dari server, lalu user diarahkan kembali ke halaman Login/Home
 */
const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <Head title="Dashboard Alumni - Tracer Study" />

    <div class="min-h-screen bg-slate-50 relative overflow-hidden pb-16">
        <!-- Navbar Terpadu Alumni -->
        <Navbar :user="user" />

        <!-- Background Banner & Welcome Header -->
        <div class="w-full bg-[#0D542B] pt-10 pb-24 px-4 sm:px-6 lg:px-8 text-center relative shadow-sm">
            <transition appear name="fade-down">
                <div class="max-w-3xl mx-auto">
                    <h2 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight mb-3">Selamat datang kembali, {{ user.name }}!</h2>
                    <p class="text-green-100 text-sm md:text-base font-medium">Terima kasih telah berkontribusi. Mari lengkapi data Anda untuk membantu peningkatan mutu dan akreditasi kampus kita tercinta.</p>
                </div>
            </transition>
        </div>

        <!-- Main Content -->
        <main class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-12">

            <!-- Grid 3 Kartu Menu Utama Dashboard Alumni -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8 items-stretch">
                <!-- ======================================================= -->
                <!-- KARTU 1: STATUS PROFIL & BIODATA                        -->
                <!-- ======================================================= -->
                <transition appear name="fade-up-1">
                    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden flex flex-col justify-between h-full border border-gray-100">
                        <div class="flex-1 flex flex-col">
                            <!-- Header Status Badge (Tumpul / Rounded) -->
                            <div class="flex items-center justify-end mb-4">
                                <span v-if="profileCompleted" class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-[#0D542B]">
                                    Sudah Lengkap
                                </span>
                                <span v-else class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-[#FDC700] text-amber-950">
                                    Belum Lengkap
                                </span>
                            </div>
                            
                            <!-- Judul & Deskripsi Singkat -->
                            <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Profil & Biodata</h3>
                            <p class="text-gray-500 text-xs sm:text-sm leading-relaxed mb-6 flex-1">
                                Kelola identitas diri, data akademik, riwayat pekerjaan, dan informasi personal Anda lainnya di sini.
                            </p>
                            
                            <!-- Box Progress Bar & Persentase Kelengkapan Profil (Rata Sama Tinggi dengan mt-auto) -->
                            <div class="bg-slate-50 rounded-2xl p-4 sm:p-5 mb-6 mt-auto border border-gray-100">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Kelengkapan Profil</span>
                                    <span class="text-sm font-extrabold text-[#0D542B]">{{ profilePercentage }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-[#0D542B] h-2.5 rounded-full transition-all duration-500" :style="{ width: `${profilePercentage}%` }"></div>
                                </div>
                                <div class="mt-2.5 flex items-center justify-between text-xs text-gray-500">
                                    <span>{{ profileFilledCount }} dari {{ profileTotalCount }} data terisi</span>
                                    <span v-if="!profileCompleted && profileMissingFields.length > 0" class="text-amber-800 font-semibold">{{ profileMissingFields.length }} belum lengkap</span>
                                    <span v-else class="text-emerald-700 font-semibold">Lengkap</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Aksi Navigasi Profil (Tanpa Tanda Panah) -->
                        <Link href="/alumni/profile" class="inline-flex items-center justify-center w-full px-5 py-3.5 text-sm font-bold rounded-2xl text-white bg-[#0D542B] hover:bg-[#093f20] transition-colors shadow-xs cursor-pointer">
                            Kelola Data Profil
                        </Link>
                    </div>
                </transition>

                <!-- ======================================================= -->
                <!-- KARTU 2: STATUS KUESIONER TRACER STUDY                  -->
                <!-- ======================================================= -->
                <transition appear name="fade-up-2">
                    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden flex flex-col justify-between h-full border border-gray-100">
                        <div class="flex-1 flex flex-col">
                            <!-- Header Status Badge (Tumpul / Rounded) -->
                            <div class="flex items-center justify-end mb-4">
                                <span v-if="questionnaireCompleted" class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-[#0D542B]">
                                    Sudah Diselesaikan
                                </span>
                                <span v-else class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-[#FDC700] text-amber-950">
                                    Belum Diselesaikan
                                </span>
                            </div>
                            
                            <!-- Judul & Deskripsi Singkat -->
                            <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Kuesioner Tracer Study</h3>
                            <p class="text-gray-500 text-xs sm:text-sm leading-relaxed mb-6 flex-1">
                                Isi kuesioner resmi dari universitas untuk memberikan umpan balik (<em>feedback</em>) bagi pengembangan kurikulum.
                            </p>
                            
                            <!-- Box Progress Bar & Persentase Kuesioner Wajib (Rata Sama Tinggi dengan mt-auto) -->
                            <div class="bg-slate-50 rounded-2xl p-4 sm:p-5 mb-6 mt-auto border border-gray-100">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Kuesioner Universitas</span>
                                    <span class="text-sm font-extrabold text-[#0D542B]">{{ questionnairePercentage }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-[#0D542B] h-2.5 rounded-full transition-all duration-500" :style="{ width: `${questionnairePercentage}%` }"></div>
                                </div>
                                <div class="mt-2.5 flex items-center justify-between text-xs text-gray-500">
                                    <span>{{ questionnaireAnsweredCount }} dari {{ questionnaireTotalCount }} soal wajib</span>
                                    <span v-if="!questionnaireCompleted && questionnaireMissing.length > 0" class="text-amber-800 font-semibold">{{ questionnaireMissing.length }} belum</span>
                                    <span v-else class="text-emerald-700 font-semibold">Lengkap</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Aksi Navigasi Kuesioner Universitas (Tanpa Tanda Panah) -->
                        <Link href="/alumni/kuesioner" class="inline-flex items-center justify-center w-full px-5 py-3.5 text-sm font-bold rounded-2xl text-white bg-[#0D542B] hover:bg-[#093f20] transition-colors shadow-xs cursor-pointer">
                            Isi Kuesioner Umum
                        </Link>
                    </div>
                </transition>

                <!-- ======================================================= -->
                <!-- KARTU 3: KUESIONER PROGRAM STUDI                        -->
                <!-- ======================================================= -->
                <transition appear name="fade-up-2">
                    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden flex flex-col justify-between h-full border border-gray-100">
                        <div class="flex-1 flex flex-col">
                            <!-- Header Status Badge (Tumpul / Rounded) -->
                            <div class="flex items-center justify-end mb-4">
                                <span v-if="prodiCompleted && prodiQuestionsCount > 0" class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-[#0D542B]">
                                    Sudah Diselesaikan
                                </span>
                                <span v-else-if="prodiQuestionsCount === 0" class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-gray-100 text-gray-600">
                                    Belum Ada Soal
                                </span>
                                <span v-else class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-[#FDC700] text-amber-950">
                                    Belum Lengkap
                                </span>
                            </div>
                            
                            <!-- Judul & Deskripsi Singkat -->
                            <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Kuesioner Program Studi</h3>
                            <p class="text-gray-500 text-xs sm:text-sm leading-relaxed mb-6 flex-1">
                                Evaluasi khusus kurikulum, materi kuliah, dan fasilitas di Program Studi <strong>{{ alumni?.prodi?.nama_prodi || 'Anda' }}</strong>.
                            </p>
                            
                            <!-- Box Progress Bar & Persentase Kuesioner Prodi (Rata Sama Tinggi dengan mt-auto) -->
                            <div class="bg-slate-50 rounded-2xl p-4 sm:p-5 mb-6 mt-auto border border-gray-100">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Status Kuesioner Prodi</span>
                                    <span class="text-sm font-extrabold text-[#0D542B]">
                                        {{ prodiQuestionsCount > 0 ? Math.round((prodiAnsweredCount / prodiQuestionsCount) * 100) : 0 }}%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                    <div 
                                        class="bg-[#0D542B] h-2.5 rounded-full transition-all duration-500" 
                                        :style="{ width: `${prodiQuestionsCount > 0 ? (prodiAnsweredCount / prodiQuestionsCount) * 100 : 0}%` }"
                                    ></div>
                                </div>
                                <div class="mt-2.5 flex items-center justify-between text-xs text-gray-500">
                                    <span>{{ prodiAnsweredCount }} dari {{ prodiQuestionsCount }} soal terisi</span>
                                    <span class="font-semibold text-emerald-800 truncate max-w-[120px]">{{ alumni?.prodi?.nama_prodi }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Aksi Navigasi Kuesioner Prodi (Tanpa Tanda Panah) -->
                        <Link href="/alumni/kuesioner-prodi" class="inline-flex items-center justify-center w-full px-5 py-3.5 text-sm font-bold rounded-2xl text-white bg-[#0D542B] hover:bg-[#093f20] transition-colors shadow-xs cursor-pointer">
                            Buka Kuesioner Prodi
                        </Link>
                    </div>
                </transition>
            </div>
        </main>
    </div>
</template>

<style scoped>
.fade-down-enter-active {
    transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}
.fade-down-enter-from {
    opacity: 0;
    transform: translateY(-30px);
}

.fade-up-1-enter-active {
    transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.1s;
}
.fade-up-1-enter-from {
    opacity: 0;
    transform: translateY(40px);
}

.fade-up-2-enter-active {
    transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.25s;
}
.fade-up-2-enter-from {
    opacity: 0;
    transform: translateY(40px);
}
</style>
