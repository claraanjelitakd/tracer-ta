<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Navbar from './Components/Navbar.vue';

defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
    alumni: {
        type: Object,
        default: () => ({}),
    },
    biodata: {
        type: Object,
        default: () => ({}),
    },
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
        default: 30,
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
    prodiPercentage: {
        type: Number,
        default: 100,
    },
    overallPercentage: {
        type: Number,
        default: 0,
    },
    isOverallComplete: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <Head title="Dashboard Alumni - Tracer Study UKDW" />

    <div class="min-h-screen bg-slate-50 font-sans pb-20">
        <!-- Navigasi Utama Terpadu Alumni -->
        <Navbar :user="user" />

        <!-- Container Dashboard Responsif Proporsional (Max-W 7XL: ~1280px Selaras Navbar) -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header Dashboard -->
            <header class="pt-8 sm:pt-10 pb-6 sm:pb-8">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                        Tahapan Pengisian Tracer Study
                    </h1>
                    <p class="text-sm sm:text-base text-slate-500 mt-1.5">
                        Selesaikan 3 tahapan berikut untuk melengkapi pelaporan data tracer study Anda.
                    </p>
                </div>
            </header>

            <!-- Area Kartu: Responsif, Proporsional, Tidak Over-Stretch dan Tidak Gepeng -->
            <main class="py-2">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-7 lg:gap-8">
                    
                    <!-- KARTU 1: DATA DIRI & KARIER -->
                    <div class="bg-white rounded-2xl p-6 sm:p-7 border border-slate-200 shadow-2xs hover:shadow-md transition-all duration-200 flex flex-col justify-between min-h-[380px]">
                        <div>
                            <div class="flex items-center justify-between h-6 mb-3">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                    Tahap 1
                                </span>
                                <span 
                                    v-if="profileCompleted" 
                                    class="px-2.5 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-[#0D542B] border border-emerald-200"
                                >
                                    Selesai
                                </span>
                                <span 
                                    v-else 
                                    class="px-2.5 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200"
                                >
                                    Belum Lengkap
                                </span>
                            </div>

                            <h2 class="text-lg sm:text-xl font-bold text-slate-900 mb-2">
                                Profil & Riwayat Karier
                            </h2>
                            
                            <p class="text-xs sm:text-sm text-slate-500 leading-relaxed mb-6">
                                Pemutakhiran data domisili, kontak, serta riwayat pekerjaan alumni.
                            </p>

                            <div class="bg-slate-50 rounded-xl p-4 sm:p-5 border border-slate-100">
                                <div class="flex items-center justify-between text-xs sm:text-sm mb-2">
                                    <span class="font-medium text-slate-600">Kelengkapan Data</span>
                                    <span class="font-bold text-slate-900">{{ profilePercentage }}%</span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                                    <div 
                                        class="bg-[#0D542B] h-2.5 rounded-full transition-all duration-300" 
                                        :style="{ width: `${profilePercentage}%` }"
                                    ></div>
                                </div>
                                <div class="text-[11px] sm:text-xs text-slate-400 mt-2.5">
                                    {{ profileFilledCount }} dari {{ profileTotalCount }} data terisi
                                </div>
                            </div>
                        </div>

                        <Link 
                            href="/alumni/profile" 
                            class="h-11 sm:h-12 w-full inline-flex items-center justify-center text-xs sm:text-sm font-semibold rounded-xl text-white bg-[#0D542B] hover:bg-[#093d1f] transition-colors cursor-pointer shadow-2xs mt-6"
                        >
                            {{ profileCompleted ? 'Perbarui Profil' : 'Lengkapi Profil' }}
                        </Link>
                    </div>

                    <!-- KARTU 2: KUESIONER UNIVERSITAS -->
                    <div class="bg-white rounded-2xl p-6 sm:p-7 border border-slate-200 shadow-2xs hover:shadow-md transition-all duration-200 flex flex-col justify-between min-h-[380px]">
                        <div>
                            <div class="flex items-center justify-between h-6 mb-3">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                    Tahap 2
                                </span>
                                <span 
                                    v-if="questionnaireCompleted" 
                                    class="px-2.5 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-[#0D542B] border border-emerald-200"
                                >
                                    Selesai
                                </span>
                                <span 
                                    v-else 
                                    class="px-2.5 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200"
                                >
                                    Belum Lengkap
                                </span>
                            </div>

                            <h2 class="text-lg sm:text-xl font-bold text-slate-900 mb-2">
                                Kuesioner Universitas
                            </h2>
                            
                            <p class="text-xs sm:text-sm text-slate-500 leading-relaxed mb-6">
                                Kuesioner pelacakan jejak alumni standar nasional Dikti.
                            </p>

                            <div class="bg-slate-50 rounded-xl p-4 sm:p-5 border border-slate-100">
                                <div class="flex items-center justify-between text-xs sm:text-sm mb-2">
                                    <span class="font-medium text-slate-600">Keterisian Butir Wajib</span>
                                    <span class="font-bold text-slate-900">{{ questionnairePercentage }}%</span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                                    <div 
                                        class="bg-[#0D542B] h-2.5 rounded-full transition-all duration-300" 
                                        :style="{ width: `${questionnairePercentage}%` }"
                                    ></div>
                                </div>
                                <div class="text-[11px] sm:text-xs text-slate-400 mt-2.5">
                                    {{ questionnaireAnsweredCount }} dari {{ questionnaireTotalCount }} butir terjawab
                                </div>
                            </div>
                        </div>

                        <Link 
                            href="/alumni/kuesioner" 
                            class="h-11 sm:h-12 w-full inline-flex items-center justify-center text-xs sm:text-sm font-semibold rounded-xl text-white bg-[#0D542B] hover:bg-[#093d1f] transition-colors cursor-pointer shadow-2xs mt-6"
                        >
                            {{ questionnaireCompleted ? 'Lihat Kuesioner' : 'Isi Kuesioner' }}
                        </Link>
                    </div>

                    <!-- KARTU 3: KUESIONER PROGRAM STUDI -->
                    <div class="bg-white rounded-2xl p-6 sm:p-7 border border-slate-200 shadow-2xs hover:shadow-md transition-all duration-200 flex flex-col justify-between min-h-[380px] md:col-span-2 lg:col-span-1">
                        <div>
                            <div class="flex items-center justify-between h-6 mb-3">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                    Tahap 3
                                </span>
                                <span 
                                    v-if="prodiCompleted && prodiQuestionsCount > 0" 
                                    class="px-2.5 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-[#0D542B] border border-emerald-200"
                                >
                                    Selesai
                                </span>
                                <span 
                                    v-else-if="prodiQuestionsCount === 0" 
                                    class="px-2.5 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200"
                                >
                                    Belum Ada Soal
                                </span>
                                <span 
                                    v-else 
                                    class="px-2.5 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200"
                                >
                                    Belum Lengkap
                                </span>
                            </div>

                            <h2 class="text-lg sm:text-xl font-bold text-slate-900 mb-2">
                                Kuesioner Program Studi
                            </h2>
                            
                            <p class="text-xs sm:text-sm text-slate-500 leading-relaxed mb-6">
                                Evaluasi kurikulum dan proses pembelajaran tingkat program studi.
                            </p>

                            <div class="bg-slate-50 rounded-xl p-4 sm:p-5 border border-slate-100">
                                <div class="flex items-center justify-between text-xs sm:text-sm mb-2">
                                    <span class="font-medium text-slate-600">Progres Kuesioner Prodi</span>
                                    <span class="font-bold text-slate-900">{{ prodiPercentage }}%</span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                                    <div 
                                        class="bg-[#0D542B] h-2.5 rounded-full transition-all duration-300" 
                                        :style="{ width: `${prodiPercentage}%` }"
                                    ></div>
                                </div>
                                <div class="text-[11px] sm:text-xs text-slate-400 mt-2.5">
                                    <span v-if="prodiQuestionsCount > 0">
                                        {{ prodiAnsweredCount }} dari {{ prodiQuestionsCount }} butir terjawab
                                    </span>
                                    <span v-else>
                                        Belum ada instrumen soal prodi
                                    </span>
                                </div>
                            </div>
                        </div>

                        <Link 
                            href="/alumni/kuesioner-prodi" 
                            class="h-11 sm:h-12 w-full inline-flex items-center justify-center text-xs sm:text-sm font-semibold rounded-xl text-white bg-[#0D542B] hover:bg-[#093d1f] transition-colors cursor-pointer shadow-2xs mt-6"
                        >
                            {{ prodiCompleted ? 'Lihat Kuesioner Prodi' : 'Isi Kuesioner Prodi' }}
                        </Link>
                    </div>

                </div>
            </main>
        </div>
    </div>
</template>
