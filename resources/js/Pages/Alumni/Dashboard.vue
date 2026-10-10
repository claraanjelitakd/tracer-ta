<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Navbar from './Components/Navbar.vue';

const props = defineProps({
    user: Object,
    alumni: Object,
    biodata: Object,
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
    hasSupervisor: {
        type: Boolean,
        default: false,
    },
    evaluasiSubmitted: {
        type: Boolean,
        default: false,
    },
    supervisorEmail: {
        type: String,
        default: null,
    },
    supervisorName: {
        type: String,
        default: null,
    },
});

// State interaktif modal detail field belum lengkap
const showMissingProfileModal = ref(false);
const showMissingQuestionnaireModal = ref(false);

// Menghitung status akumulasi menyeluruh
const completedStepsCount = computed(() => {
    let count = 0;
    if (props.profileCompleted) count++;
    if (props.questionnaireCompleted) count++;
    if (props.prodiQuestionsCount === 0 || props.prodiCompleted) count++;
    if (props.evaluasiSubmitted || props.hasSupervisor) count++;
    return count;
});

const overallPercentage = computed(() => {
    return Math.round((completedStepsCount.value / 4) * 100);
});

// Menentukan rute tindakan selanjutnya yang paling prioritas
const nextAction = computed(() => {
    if (!props.profileCompleted) {
        return {
            title: 'Lengkapi Profil & Pekerjaan',
            desc: 'Masih ada data identitas atau tempat kerja yang perlu diisi.',
            url: '/alumni/profile',
            btnText: 'Lanjutkan Profil',
        };
    }
    if (!props.questionnaireCompleted) {
        return {
            title: 'Isi Kuesioner Universitas',
            desc: 'Selesaikan butir kuesioner pelacakan jejak nasional Kemdikbud.',
            url: '/alumni/kuesioner',
            btnText: 'Isi Kuesioner Umum',
        };
    }
    if (props.prodiQuestionsCount > 0 && !props.prodiCompleted) {
        return {
            title: 'Isi Kuesioner Program Studi',
            desc: 'Berikan evaluasi spesifik kurikulum program studi Anda.',
            url: '/alumni/kuesioner-prodi',
            btnText: 'Isi Kuesioner Prodi',
        };
    }
    return {
        title: 'Semua Tahapan Telah Lengkap',
        desc: 'Terima kasih atas kontribusi Anda dalam pengisian Tracer Study UKDW.',
        url: '/alumni/profile',
        btnText: 'Tinjau Profil Saya',
    };
});

// Label ramah untuk nama-nama field yang belum diisi
const formatFieldName = (fieldName) => {
    const dictionary = {
        nik: 'Nomor Induk Kependudukan (NIK)',
        npwp: 'NPWP',
        nomor_hp: 'Nomor Handphone / WhatsApp',
        email_pribadi: 'Alamat Email Aktif',
        alamat_domisili: 'Alamat Domisili Sekarang',
        provinsi: 'Provinsi Domisili',
        kota_kabupaten: 'Kota / Kabupaten Domisili',
        status_bekerja: 'Status Bekerja Saat Ini',
        nama_perusahaan: 'Nama Perusahaan / Instansi Bekerja',
        posisi_jabatan: 'Posisi / Jabatan Pekerjaan',
        nama_atasan: 'Nama Lengkap Atasan Langsung',
        email_atasan: 'Email Resmi Atasan Langsung',
        nomor_hp_atasan: 'Kontak Handphone Atasan',
        penghasilan_pertama: 'Perkiraan Gaji / Penghasilan Pertama',
        waktu_tunggu_kerja: 'Waktu Tunggu Mendapatkan Kerja',
        linkedin_url: 'Tautan Akun LinkedIn',
    };
    return dictionary[fieldName] || fieldName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};
</script>

<template>
    <Head title="Dashboard Alumni - Tracer Study UKDW" />

    <div class="min-h-screen bg-slate-50 relative overflow-hidden pb-16 font-sans">
        <!-- Navbar Terpadu Alumni -->
        <Navbar :user="user" />

        <!-- Header Banner Solid Resmi UKDW #0D542B -->
        <section class="w-full bg-[#0D542B] pt-8 pb-20 px-4 sm:px-6 lg:px-8 text-white relative shadow-sm">
            <div class="max-w-7xl mx-auto flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/15 text-green-100 rounded-md text-xs font-semibold mb-3 backdrop-blur-xs">
                        <span class="w-2 h-2 rounded-full bg-[#FDC700]"></span>
                        <span>Portal Resmi Alumni Tracer Study UKDW</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-white tracking-tight">
                        Selamat Datang, {{ alumni?.nama || user?.name }}!
                    </h1>
                    <p class="text-green-100 text-xs sm:text-sm max-w-2xl mt-1.5 leading-relaxed font-normal">
                        NIM: <strong class="text-white">{{ alumni?.nim || user?.username }}</strong> &bull; 
                        Program Studi: <strong class="text-white">{{ alumni?.prodi?.nama_prodi || 'UKDW' }}</strong>
                        <span v-if="alumni?.tahun_lulus"> &bull; Lulus: <strong class="text-white">{{ alumni.tahun_lulus }}</strong></span>
                    </p>
                </div>

                <!-- Kartu Ringkasan Akumulasi Progres (Interaktif) -->
                <div class="bg-white/10 border border-white/20 rounded-2xl p-4 sm:p-5 text-white max-w-md w-full backdrop-blur-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-green-100">Status Kelengkapan</span>
                        <span class="text-sm font-extrabold text-[#FDC700]">{{ overallPercentage }}% Selesai</span>
                    </div>
                    <div class="w-full bg-white/20 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-[#FDC700] h-2.5 rounded-full transition-all duration-700 ease-out" :style="{ width: `${overallPercentage}%` }"></div>
                    </div>
                    <div class="mt-2.5 flex items-center justify-between text-xs text-green-100">
                        <span>{{ completedStepsCount }} dari 4 tahapan terpenuhi</span>
                        <span v-if="overallPercentage === 100" class="text-[#FDC700] font-bold">Terverifikasi Lengkap</span>
                        <span v-else class="text-white font-medium">Perlu Dilengkapi</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Body Area -->
        <main class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-10 space-y-8">

            <!-- Banner Notifikasi Aksi Cepat (Call-to-Action) -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-[#0D542B] shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold uppercase tracking-wider px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md">Langkah Selanjutnya</span>
                            <span v-if="overallPercentage === 100" class="text-xs font-bold text-emerald-800">Tuntas</span>
                        </div>
                        <h2 class="text-base font-bold text-slate-900 mt-1">{{ nextAction.title }}</h2>
                        <p class="text-xs text-slate-500 mt-0.5">{{ nextAction.desc }}</p>
                    </div>
                </div>
                <Link 
                    :href="nextAction.url" 
                    class="w-full md:w-auto inline-flex items-center justify-center px-5 py-2.5 text-xs font-bold rounded-xl text-white bg-[#0D542B] hover:bg-[#08381c] transition-all shadow-xs shrink-0 cursor-pointer"
                >
                    {{ nextAction.btnText }} &rarr;
                </Link>
            </div>

            <!-- Grid 3 Kartu Menu Utama Dashboard Alumni -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-stretch">
                
                <!-- KARTU 1: STATUS PROFIL & BIODATA -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs hover:shadow-md transition-all duration-300 flex flex-col justify-between h-full relative">
                    <div>
                        <!-- Header Status Badge -->
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600">Tahap 1</span>
                            <span v-if="profileCompleted" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-[#0D542B] border border-emerald-200">
                                Sudah Lengkap
                            </span>
                            <span v-else class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-900 border border-amber-200">
                                Belum Lengkap
                            </span>
                        </div>
                        
                        <!-- Judul & Deskripsi -->
                        <h3 class="text-lg font-bold text-slate-900 mb-1.5">Profil & Riwayat Karir</h3>
                        <p class="text-slate-500 text-xs leading-relaxed mb-5">
                            Kelola identitas diri, kontak aktif, riwayat jabatan, dan instansi tempat Anda berkarya.
                        </p>
                        
                        <!-- Box Progress Bar -->
                        <div class="bg-slate-50 rounded-xl p-4 mb-5 border border-slate-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-slate-700">Keterisian Biodata</span>
                                <span class="text-xs font-extrabold text-[#0D542B]">{{ profilePercentage }}%</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                                <div class="bg-[#0D542B] h-2 rounded-full transition-all duration-500" :style="{ width: `${profilePercentage}%` }"></div>
                            </div>
                            <div class="mt-2.5 flex items-center justify-between text-xs text-slate-600">
                                <span>{{ profileFilledCount }} dari {{ profileTotalCount }} atribut terisi</span>
                                <button 
                                    v-if="!profileCompleted && profileMissingFields.length > 0"
                                    type="button"
                                    @click="showMissingProfileModal = true"
                                    class="text-amber-800 font-bold hover:underline cursor-pointer flex items-center gap-1"
                                >
                                    <span>{{ profileMissingFields.length }} belum</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </button>
                                <span v-else class="text-emerald-800 font-bold">Lengkap</span>
                            </div>
                        </div>
                    </div>

                    <Link href="/alumni/profile" class="inline-flex items-center justify-center w-full px-4 py-3 text-xs font-bold rounded-xl text-white bg-[#0D542B] hover:bg-[#08381c] transition-colors shadow-xs cursor-pointer">
                        Kelola Data Profil
                    </Link>
                </div>

                <!-- KARTU 2: STATUS KUESIONER TRACER STUDY UNIVERSITAS -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs hover:shadow-md transition-all duration-300 flex flex-col justify-between h-full relative">
                    <div>
                        <!-- Header Status Badge -->
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600">Tahap 2</span>
                            <span v-if="questionnaireCompleted" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-[#0D542B] border border-emerald-200">
                                Wajib Lengkap
                            </span>
                            <span v-else class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-900 border border-amber-200">
                                Belum Lengkap
                            </span>
                        </div>
                        
                        <!-- Judul & Deskripsi -->
                        <h3 class="text-lg font-bold text-slate-900 mb-1.5">Kuesioner Universitas</h3>
                        <p class="text-slate-500 text-xs leading-relaxed mb-5">
                            Isi butir evaluasi pelacakan jejak alumni standar nasional Dikti untuk akreditasi kampus UKDW.
                        </p>
                        
                        <!-- Box Progress Bar -->
                        <div class="bg-slate-50 rounded-xl p-4 mb-5 border border-slate-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-slate-700">Butir Wajib Dikti</span>
                                <span class="text-xs font-extrabold text-[#0D542B]">{{ questionnairePercentage }}%</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                                <div class="bg-[#0D542B] h-2 rounded-full transition-all duration-500" :style="{ width: `${questionnairePercentage}%` }"></div>
                            </div>
                            <div class="mt-2.5 flex items-center justify-between text-xs text-slate-600">
                                <span>{{ questionnaireAnsweredCount }} dari {{ questionnaireTotalCount }} butir wajib</span>
                                <button 
                                    v-if="!questionnaireCompleted && questionnaireMissing.length > 0"
                                    type="button"
                                    @click="showMissingQuestionnaireModal = true"
                                    class="text-amber-800 font-bold hover:underline cursor-pointer flex items-center gap-1"
                                >
                                    <span>{{ questionnaireMissing.length }} butir belum</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </button>
                                <span v-else class="text-emerald-800 font-bold">Lengkap</span>
                            </div>
                        </div>
                    </div>

                    <Link href="/alumni/kuesioner" class="inline-flex items-center justify-center w-full px-4 py-3 text-xs font-bold rounded-xl text-white bg-[#0D542B] hover:bg-[#08381c] transition-colors shadow-xs cursor-pointer">
                        Isi Kuesioner Universitas
                    </Link>
                </div>

                <!-- KARTU 3: KUESIONER PROGRAM STUDI -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs hover:shadow-md transition-all duration-300 flex flex-col justify-between h-full relative">
                    <div>
                        <!-- Header Status Badge -->
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600">Tahap 3</span>
                            <span v-if="prodiCompleted && prodiQuestionsCount > 0" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-[#0D542B] border border-emerald-200">
                                Sudah Lengkap
                            </span>
                            <span v-else-if="prodiQuestionsCount === 0" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                Belum Ada Soal
                            </span>
                            <span v-else class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-900 border border-amber-200">
                                Belum Lengkap
                            </span>
                        </div>
                        
                        <!-- Judul & Deskripsi -->
                        <h3 class="text-lg font-bold text-slate-900 mb-1.5">Kuesioner Program Studi</h3>
                        <p class="text-slate-500 text-xs leading-relaxed mb-5">
                            Evaluasi spesifik kurikulum dan fasilitas pada Program Studi <strong>{{ alumni?.prodi?.nama_prodi || 'Anda' }}</strong>.
                        </p>
                        
                        <!-- Box Progress Bar -->
                        <div class="bg-slate-50 rounded-xl p-4 mb-5 border border-slate-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-slate-700">Progres Kuesioner Prodi</span>
                                <span class="text-xs font-extrabold text-[#0D542B]">
                                    {{ prodiQuestionsCount > 0 ? Math.round((prodiAnsweredCount / prodiQuestionsCount) * 100) : 100 }}%
                                </span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                                <div 
                                    class="bg-[#0D542B] h-2 rounded-full transition-all duration-500" 
                                    :style="{ width: `${prodiQuestionsCount > 0 ? (prodiAnsweredCount / prodiQuestionsCount) * 100 : 100}%` }"
                                ></div>
                            </div>
                            <div class="mt-2.5 flex items-center justify-between text-xs text-slate-600">
                                <span>{{ prodiAnsweredCount }} dari {{ prodiQuestionsCount }} soal terisi</span>
                                <span class="font-bold text-emerald-800 truncate max-w-[120px]">{{ alumni?.prodi?.kode_prodi || 'Prodi' }}</span>
                            </div>
                        </div>
                    </div>

                    <Link href="/alumni/kuesioner-prodi" class="inline-flex items-center justify-center w-full px-4 py-3 text-xs font-bold rounded-xl text-white bg-[#0D542B] hover:bg-[#08381c] transition-colors shadow-xs cursor-pointer">
                        Buka Kuesioner Prodi
                    </Link>
                </div>

            </div>

            <!-- KARTU TAMBAHAN: STATUS SURVEI ATASAN LANGSUNG -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600">Tahap 4</span>
                                <span v-if="evaluasiSubmitted" class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    Atasan Sudah Mengisi
                                </span>
                                <span v-else-if="hasSupervisor" class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                    Tautan Terkirim ke Atasan
                                </span>
                                <span v-else class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-900 border border-amber-200">
                                    Belum Ada Email Atasan
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 mt-1">Survei Evaluasi Pengguna Lulusan (Atasan Langsung)</h3>
                            <p class="text-xs text-slate-600 mt-0.5">
                                <span v-if="hasSupervisor">
                                    Survei dikirim ke: <strong>{{ supervisorEmail }}</strong> ({{ supervisorName || 'Atasan' }}).
                                </span>
                                <span v-else>
                                    Lengkapi data atasan langsung di tempat Anda bekerja pada menu Profil agar sistem dapat mengirimkan kuesioner evaluasi atasan secara otomatis.
                                </span>
                            </p>
                        </div>
                    </div>

                    <Link 
                        href="/alumni/profile" 
                        class="inline-flex items-center justify-center px-4 py-2.5 text-xs font-bold rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors shrink-0"
                    >
                        Kelola Kontak Atasan
                    </Link>
                </div>
            </div>

        </main>

        <!-- MODAL INTERAKTIF: RINCIAN FIELD PROFIL YANG BELUM LENGKAP -->
        <div v-if="showMissingProfileModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 relative animate-in fade-in zoom-in-95 duration-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h4 class="text-base font-bold text-slate-900">Rincian Data Profil Belum Lengkap</h4>
                        <p class="text-xs text-slate-500">Silakan lengkapi atribut berikut di halaman profil:</p>
                    </div>
                    <button 
                        @click="showMissingProfileModal = false" 
                        type="button" 
                        class="text-slate-400 hover:text-slate-600 p-1 rounded-lg"
                    >
                        ✕
                    </button>
                </div>
                <div class="my-4 max-h-64 overflow-y-auto space-y-2 pr-1">
                    <div 
                        v-for="(field, idx) in profileMissingFields" 
                        :key="idx" 
                        class="flex items-center gap-2.5 p-2.5 bg-amber-50/70 border border-amber-100 rounded-xl text-xs text-amber-900 font-medium"
                    >
                        <span class="w-5 h-5 rounded-full bg-amber-200 text-amber-900 font-bold flex items-center justify-center text-[10px] shrink-0">{{ idx + 1 }}</span>
                        <span>{{ formatFieldName(field) }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button 
                        @click="showMissingProfileModal = false" 
                        type="button" 
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl cursor-pointer"
                    >
                        Tutup
                    </button>
                    <Link 
                        href="/alumni/profile" 
                        class="px-4 py-2 text-xs font-bold text-white bg-[#0D542B] hover:bg-[#08381c] rounded-xl cursor-pointer"
                    >
                        Lengkapi Sekarang &rarr;
                    </Link>
                </div>
            </div>
        </div>

        <!-- MODAL INTERAKTIF: RINCIAN SOAL KUESIONER YANG BELUM DIJAWAB -->
        <div v-if="showMissingQuestionnaireModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 relative animate-in fade-in zoom-in-95 duration-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h4 class="text-base font-bold text-slate-900">Soal Wajib Belum Dijawab</h4>
                        <p class="text-xs text-slate-500">Terdapat {{ questionnaireMissing.length }} butir pertanyaan belum terisi:</p>
                    </div>
                    <button 
                        @click="showMissingQuestionnaireModal = false" 
                        type="button" 
                        class="text-slate-400 hover:text-slate-600 p-1 rounded-lg"
                    >
                        ✕
                    </button>
                </div>
                <div class="my-4 max-h-64 overflow-y-auto space-y-2 pr-1">
                    <div 
                        v-for="(soal, idx) in questionnaireMissing" 
                        :key="idx" 
                        class="flex items-center justify-between p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800"
                    >
                        <span class="font-bold text-[#0D542B]">{{ soal }}</span>
                        <span class="text-[11px] text-slate-500">Wajib Diisi</span>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button 
                        @click="showMissingQuestionnaireModal = false" 
                        type="button" 
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl cursor-pointer"
                    >
                        Tutup
                    </button>
                    <Link 
                        href="/alumni/kuesioner" 
                        class="px-4 py-2 text-xs font-bold text-white bg-[#0D542B] hover:bg-[#08381c] rounded-xl cursor-pointer"
                    >
                        Buka Formulir Kuesioner &rarr;
                    </Link>
                </div>
            </div>
        </div>

    </div>
</template>
