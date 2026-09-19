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

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                <!-- ======================================================= -->
                <!-- CARD 1: STATUS PROFIL & BIODATA                         -->
                <!-- ======================================================= -->
                <transition appear name="fade-up-1">
                    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-6">
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-[#0D542B]/10 text-[#0D542B]">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                </div>
                                
                                <span v-if="profileCompleted" class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-[#0D542B]">
                                    Sudah Lengkap
                                </span>
                                <span v-else class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-[#FDC700] text-amber-950">
                                    Belum Lengkap
                                </span>
                            </div>
                            
                            <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Profil & Biodata</h3>
                            <p class="text-gray-500 text-xs sm:text-sm leading-relaxed mb-6">Kelola identitas diri, data akademik, riwayat pekerjaan, dan informasi personal Anda lainnya di sini.</p>
                            
                            <!-- Progress Bar & Persentase Profil -->
                            <div class="bg-slate-50 rounded-2xl p-4 mb-6">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Kelengkapan Profil</span>
                                    <span class="text-sm font-extrabold text-[#0D542B]">{{ profilePercentage }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-[#0D542B] h-2.5 rounded-full transition-all duration-500" :style="{ width: `${profilePercentage}%` }"></div>
                                </div>
                                <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                                    <span>{{ profileFilledCount }} dari {{ profileTotalCount }} data terisi</span>
                                    <span v-if="!profileCompleted && profileMissingFields.length > 0" class="text-amber-800 font-semibold">{{ profileMissingFields.length }} belum lengkap</span>
                                </div>
                            </div>
                        </div>

                        <Link href="/alumni/profile" class="inline-flex items-center justify-center w-full px-5 py-3.5 text-sm font-bold rounded-xl text-white bg-[#0D542B] hover:bg-[#093f20] transition-colors shadow-sm">
                            Kelola Data Profil
                            <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </Link>
                    </div>
                </transition>

                <!-- ======================================================= -->
                <!-- CARD 2: STATUS KUESIONER TRACER STUDY                   -->
                <!-- ======================================================= -->
                <transition appear name="fade-up-2">
                    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-6">
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-[#0D542B]/10 text-[#0D542B]">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                </div>
                                
                                <span v-if="questionnaireCompleted" class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-[#0D542B]">
                                    Sudah Diselesaikan
                                </span>
                                <span v-else class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-[#FDC700] text-amber-950">
                                    Belum Diselesaikan
                                </span>
                            </div>
                            
                            <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Kuesioner Tracer Study</h3>
                            <p class="text-gray-500 text-xs sm:text-sm leading-relaxed mb-6">Isi kuesioner resmi dari universitas untuk memberikan umpan balik (<em>feedback</em>) bagi pengembangan kurikulum.</p>
                            
                            <!-- Progress Bar & Persentase Kuesioner Wajib -->
                            <div class="bg-slate-50 rounded-2xl p-4 mb-6">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Kuesioner Universitas</span>
                                    <span class="text-sm font-extrabold text-[#0D542B]">{{ questionnairePercentage }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-[#0D542B] h-2.5 rounded-full transition-all duration-500" :style="{ width: `${questionnairePercentage}%` }"></div>
                                </div>
                                <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                                    <span>{{ questionnaireAnsweredCount }} dari {{ questionnaireTotalCount }} soal wajib</span>
                                    <span v-if="!questionnaireCompleted && questionnaireMissing.length > 0" class="text-amber-800 font-semibold">{{ questionnaireMissing.length }} belum</span>
                                </div>
                            </div>
                        </div>

                        <Link href="/alumni/kuesioner" class="inline-flex items-center justify-center w-full px-5 py-3.5 text-sm font-bold rounded-xl text-white bg-[#0D542B] hover:bg-[#093f20] transition-colors shadow-sm">
                            Isi Kuesioner Umum
                            <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </Link>
                    </div>
                </transition>

                <!-- ======================================================= -->
                <!-- CARD 3: KUESIONER PROGRAM STUDI                         -->
                <!-- ======================================================= -->
                <transition appear name="fade-up-2">
                    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-6">
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-amber-50 text-amber-700 font-black text-xl">
                                    🎓
                                </div>
                                
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
                            
                            <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Kuesioner Program Studi</h3>
                            <p class="text-gray-500 text-xs sm:text-sm leading-relaxed mb-6">
                                Evaluasi khusus kurikulum, materi kuliah, dan fasilitas di Program Studi <strong>{{ alumni?.prodi?.nama_prodi || 'Anda' }}</strong>.
                            </p>
                            
                            <!-- Status Pengisian Kuesioner Prodi -->
                            <div class="bg-slate-50 rounded-2xl p-4 mb-6">
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
                                <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                                    <span>{{ prodiAnsweredCount }} dari {{ prodiQuestionsCount }} soal terisi</span>
                                    <span class="font-semibold text-emerald-800">{{ alumni?.prodi?.nama_prodi }}</span>
                                </div>
                            </div>
                        </div>

                        <Link href="/alumni/kuesioner-prodi" class="inline-flex items-center justify-center w-full px-5 py-3.5 text-sm font-bold rounded-xl text-white bg-[#0D542B] hover:bg-[#093f20] transition-colors shadow-sm">
                            Buka Kuesioner Prodi
                            <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
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
