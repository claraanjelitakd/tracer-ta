<!--
  Halaman Detail Mahasiswa & Audit Kuesioner Tracer (Admin Program Studi)
  File: resources/js/Pages/AdminProdi/Alumni/Show.vue

  Warna Resmi Solid UKDW (Sesuai Standar Super Admin):
  - Hijau: #0D542B (Solid, tanpa gradasi)
  - Kuning: #FDC700 (Kuning UKDW murni)
  - Putih: #FFFFFF
  Desain profesional, bersih, bebas border bertumpuk, bebas icon berlebih / slop.
-->
<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Navbar from '../Components/Navbar.vue';

const props = defineProps({
    user: Object,
    prodi: Object,
    alumni: {
        type: Object,
        required: true,
    },
    evaluasi: {
        type: Object,
        required: true,
    },
    univSections: {
        type: Array,
        default: () => [],
    },
    prodiSections: {
        type: Array,
        default: () => [],
    },
    prodiEvaluasi: {
        type: Object,
        default: () => ({
            is_complete: false,
            percentage: 0,
            answered_count: 0,
            total_questions: 0,
        }),
    },
    formData: {
        type: Object,
        default: () => ({}),
    },
});

// Tab Utama: 'kuesioner_univ', 'kuesioner_prodi', 'profil'
const activeMainTab = ref('kuesioner_prodi');

// Sub-Tab Profil: 'pribadi', 'akademik', 'orangtua', 'karier'
const activeProfileTab = ref('pribadi');

// Filter Section Univ
const activeUnivSectionId = ref('all');
const filteredUnivSections = computed(() => {
    if (activeUnivSectionId.value === 'all') return props.univSections;
    return props.univSections.filter(s => s.id === activeUnivSectionId.value);
});

// Filter Section Prodi
const activeProdiSectionId = ref('all');
const filteredProdiSections = computed(() => {
    if (activeProdiSectionId.value === 'all') return props.prodiSections;
    return props.prodiSections.filter(s => s.id === activeProdiSectionId.value);
});

// Helper
const semesterKelulusan = computed(() => {
    return props.alumni?.data_akademik?.tahun_akademik_lulus || props.alumni?.tahun_lulus || '-';
});

const statusYudisium = computed(() => {
    return props.alumni?.data_akademik?.yudisium?.proses_yudisium || 'Lulus';
});
</script>

<template>
    <Head :title="`Detail Mahasiswa - ${alumni.data_akademik?.nama || alumni.nim} - Admin Prodi`" />

    <div class="min-h-screen bg-[#f8fafc] text-gray-800 font-sans pb-24">
        <!-- Navbar Terpadu Admin Prodi -->
        <Navbar :user="user" :prodi="prodi" />

        <!-- Header Solid Hijau Resmi UKDW #0D542B -->
        <header class="bg-[#0D542B] text-white pt-10 pb-20">
            <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2 text-xs text-white/80 font-medium mb-3">
                    <Link href="/prodi/dashboard" class="hover:underline">Dashboard</Link>
                    <span>/</span>
                    <Link href="/prodi/alumni" class="hover:underline">Data Alumni</Link>
                    <span>/</span>
                    <span class="text-white font-bold">Detail Mahasiswa</span>
                </div>

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                    <div>
                        <!-- Badges Header Solid UKDW -->
                        <div class="flex flex-wrap items-center gap-2.5 mb-2.5">
                            <span class="px-3.5 py-1 bg-[#FDC700] text-black font-bold text-xs rounded-full">
                                Yudisium: {{ statusYudisium }}
                            </span>
                            <span class="px-3.5 py-1 bg-black/20 text-white font-medium text-xs rounded-full">
                                Periode: {{ semesterKelulusan }}
                            </span>
                            <span 
                                class="px-3.5 py-1 text-xs font-bold rounded-full"
                                :class="evaluasi.is_complete ? 'bg-white text-[#0D542B]' : 'bg-[#FDC700] text-black'"
                            >
                                Status Tracer: {{ evaluasi.status }}
                            </span>
                        </div>

                        <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                            {{ alumni.data_akademik?.nama || alumni.user?.name || 'Mahasiswa UKDW' }}
                        </h1>
                        
                        <p class="text-white/90 text-xs sm:text-sm mt-1 font-medium">
                            NIM: <span class="font-mono font-bold text-white">{{ alumni.nim }}</span> &bull; 
                            Program Studi: <span class="font-semibold text-white">{{ prodi?.nama_prodi || alumni.prodi?.nama_prodi || '-' }}</span>
                        </p>
                    </div>

                    <div class="shrink-0">
                        <Link 
                            href="/prodi/alumni"
                            class="inline-flex items-center px-5 py-2.5 bg-white/15 hover:bg-white/25 border border-white/20 text-white text-xs font-bold rounded-xl transition-all cursor-pointer"
                        >
                            &larr; Kembali ke Daftar
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 -mt-10 space-y-6">
            
            <!-- Card Ringkasan Status Audit 3 Dimensi -->
            <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                    
                    <!-- 1. Status Profil -->
                    <div class="pb-4 md:pb-0 md:pr-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Data Profil</span>
                            <span 
                                class="text-xs font-bold px-3 py-0.5 rounded-full"
                                :class="evaluasi.profile?.is_complete ? 'bg-[#0D542B] text-white' : 'bg-[#FDC700] text-black'"
                            >
                                {{ evaluasi.profile?.percentage || 0 }}% Terisi
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden mb-2">
                            <div 
                                class="h-2 rounded-full transition-all bg-[#0D542B]"
                                :style="{ width: `${evaluasi.profile?.percentage || 0}%` }"
                            ></div>
                        </div>
                        <p class="text-xs text-gray-500">
                            {{ evaluasi.profile?.is_complete ? 'Data profil lengkap.' : `${evaluasi.profile?.missing_fields?.length || 0} butir data profil belum lengkap.` }}
                        </p>
                    </div>

                    <!-- 2. Status Kuesioner Universitas -->
                    <div class="pt-4 md:pt-0 md:px-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Kuesioner Universitas</span>
                            <span 
                                class="text-xs font-bold px-3 py-0.5 rounded-full"
                                :class="evaluasi.questionnaire?.is_complete ? 'bg-[#0D542B] text-white' : 'bg-[#FDC700] text-black'"
                            >
                                {{ evaluasi.questionnaire?.percentage || 0 }}% Terjawab
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden mb-2">
                            <div 
                                class="h-2 rounded-full transition-all bg-[#0D542B]"
                                :style="{ width: `${evaluasi.questionnaire?.percentage || 0}%` }"
                            ></div>
                        </div>
                        <p class="text-xs text-gray-500">
                            {{ evaluasi.questionnaire?.is_complete ? 'Seluruh pertanyaan wajib tracer universitas selesai.' : `${(evaluasi.questionnaire?.total_mandatory || 0) - (evaluasi.questionnaire?.answered_count || 0)} pertanyaan wajib universitas belum dijawab.` }}
                        </p>
                    </div>

                    <!-- 3. Status Kuesioner Prodi -->
                    <div class="pt-4 md:pt-0 md:pl-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Kuesioner Prodi</span>
                            <span 
                                class="text-xs font-bold px-3 py-0.5 rounded-full"
                                :class="prodiEvaluasi.is_complete ? 'bg-[#0D542B] text-white' : 'bg-[#FDC700] text-black'"
                            >
                                {{ prodiEvaluasi.percentage }}% ({{ prodiEvaluasi.answered_count }}/{{ prodiEvaluasi.total_questions }})
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden mb-2">
                            <div 
                                class="h-2 rounded-full transition-all bg-[#0D542B]"
                                :style="{ width: `${prodiEvaluasi.percentage}%` }"
                            ></div>
                        </div>
                        <p class="text-xs text-gray-500">
                            {{ prodiEvaluasi.is_complete ? 'Seluruh butir kuesioner program studi telah diisi lengkap.' : `${prodiEvaluasi.total_questions - prodiEvaluasi.answered_count} pertanyaan kuesioner prodi belum terjawab.` }}
                        </p>
                    </div>

                </div>
            </div>

            <!-- Tab Navigasi Utama: Kuesioner Prodi, Kuesioner Universitas, Profil Mahasiswa -->
            <div class="flex items-center gap-4 border-b border-gray-200">
                <button 
                    @click="activeMainTab = 'kuesioner_prodi'"
                    class="py-3 px-2 text-sm font-bold border-b-2 transition-all cursor-pointer"
                    :class="activeMainTab === 'kuesioner_prodi' ? 'border-[#0D542B] text-[#0D542B]' : 'border-transparent text-gray-400 hover:text-gray-700'"
                >
                    Kuesioner Khusus Prodi ({{ prodiEvaluasi.answered_count }}/{{ prodiEvaluasi.total_questions }})
                </button>

                <button 
                    @click="activeMainTab = 'kuesioner_univ'"
                    class="py-3 px-2 text-sm font-bold border-b-2 transition-all cursor-pointer"
                    :class="activeMainTab === 'kuesioner_univ' ? 'border-[#0D542B] text-[#0D542B]' : 'border-transparent text-gray-400 hover:text-gray-700'"
                >
                    Kuesioner Tracer Universitas
                </button>

                <button 
                    @click="activeMainTab = 'profil'"
                    class="py-3 px-2 text-sm font-bold border-b-2 transition-all cursor-pointer"
                    :class="activeMainTab === 'profil' ? 'border-[#0D542B] text-[#0D542B]' : 'border-transparent text-gray-400 hover:text-gray-700'"
                >
                    Detail Profil Mahasiswa
                </button>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 1: JAWABAN KUESIONER PROGRAM STUDI                   -->
            <!-- ========================================================= -->
            <div v-if="activeMainTab === 'kuesioner_prodi'" class="space-y-6">
                <!-- Filter Section Prodi -->
                <div class="bg-white p-5 rounded-2xl shadow-sm">
                    <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">
                        Filter Bagian (Section) Prodi:
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button 
                            @click="activeProdiSectionId = 'all'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer"
                            :class="activeProdiSectionId === 'all' ? 'bg-[#0D542B] text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                        >
                            Semua Bagian ({{ prodiSections.length }})
                        </button>
                        <button 
                            v-for="s in prodiSections" 
                            :key="s.id"
                            @click="activeProdiSectionId = s.id"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2"
                            :class="activeProdiSectionId === s.id ? 'bg-[#0D542B] text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                        >
                            <span>{{ s.title }}</span>
                            <span 
                                v-if="s.unanswered_mandatory_count > 0"
                                class="px-2 py-0.2 rounded-full text-[10px] font-bold"
                                :class="activeProdiSectionId === s.id ? 'bg-white/20 text-white' : 'bg-[#FDC700] text-black'"
                            >
                                {{ s.unanswered_mandatory_count }} belum
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredProdiSections.length === 0" class="bg-white p-12 text-center rounded-2xl shadow-sm text-gray-400">
                    Belum ada butir pertanyaan pada kuesioner program studi ini.
                </div>

                <!-- Kartu Section Kuesioner Prodi -->
                <div 
                    v-for="section in filteredProdiSections" 
                    :key="section.id"
                    class="bg-white rounded-2xl shadow-sm overflow-hidden"
                >
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-lg bg-[#0D542B] text-white font-bold text-xs flex items-center justify-center">
                                {{ section.order }}
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">
                                    {{ section.title }}
                                </h3>
                                <p v-if="section.description" class="text-xs text-gray-500 mt-0.5">
                                    {{ section.description }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span 
                                v-if="section.unanswered_mandatory_count > 0"
                                class="px-3 py-1 bg-[#FDC700] text-black font-bold text-xs rounded-full"
                            >
                                {{ section.unanswered_mandatory_count }} Belum Dijawab
                            </span>
                            <span v-else class="px-3 py-1 bg-[#0D542B] text-white font-bold text-xs rounded-full">
                                Lengkap
                            </span>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div 
                            v-for="q in section.questions" 
                            :key="q.id"
                            class="bg-white rounded-xl p-5 border border-gray-100"
                        >
                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2 mb-2">
                                <div class="flex items-start gap-2.5">
                                    <span class="px-2.5 py-1 bg-gray-100 text-gray-700 font-mono text-xs font-bold rounded-lg shrink-0">
                                        {{ q.code }}
                                    </span>
                                    <h4 class="text-sm font-bold text-gray-900 leading-snug">
                                        {{ q.question_text }}
                                    </h4>
                                </div>
                                <span 
                                    class="text-[11px] font-bold px-2.5 py-0.5 rounded-md self-start"
                                    :class="q.is_mandatory ? 'bg-[#FDC700]/30 text-amber-900' : 'bg-gray-100 text-gray-500'"
                                >
                                    {{ q.is_mandatory ? 'Wajib' : 'Opsional' }}
                                </span>
                            </div>

                            <!-- Jawaban Alumni -->
                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <span class="text-xs text-gray-400 font-semibold uppercase tracking-wider block mb-1">Jawaban Alumni:</span>
                                <div 
                                    v-if="q.is_answered"
                                    class="p-3 bg-emerald-50/70 border border-emerald-100 rounded-xl text-xs font-bold text-[#0D542B] leading-relaxed"
                                >
                                    {{ q.answer_text }}
                                </div>
                                <div 
                                    v-else 
                                    class="p-3 bg-gray-50 rounded-xl text-xs text-gray-400 italic"
                                >
                                    Belum dijawab oleh alumni
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 2: JAWABAN KUESIONER UNIVERSITAS                      -->
            <!-- ========================================================= -->
            <div v-if="activeMainTab === 'kuesioner_univ'" class="space-y-6">
                <!-- Filter Section Univ -->
                <div class="bg-white p-5 rounded-2xl shadow-sm">
                    <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">
                        Filter Bagian (Section) Universitas:
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button 
                            @click="activeUnivSectionId = 'all'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer"
                            :class="activeUnivSectionId === 'all' ? 'bg-[#0D542B] text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                        >
                            Semua Bagian ({{ univSections.length }})
                        </button>
                        <button 
                            v-for="s in univSections" 
                            :key="s.id"
                            @click="activeUnivSectionId = s.id"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2"
                            :class="activeUnivSectionId === s.id ? 'bg-[#0D542B] text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                        >
                            <span>{{ s.title }}</span>
                            <span 
                                v-if="s.unanswered_mandatory_count > 0"
                                class="px-2 py-0.2 rounded-full text-[10px] font-bold"
                                :class="activeUnivSectionId === s.id ? 'bg-white/20 text-white' : 'bg-[#FDC700] text-black'"
                            >
                                {{ s.unanswered_mandatory_count }} belum
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Kartu Section Kuesioner Univ -->
                <div 
                    v-for="section in filteredUnivSections" 
                    :key="section.id"
                    class="bg-white rounded-2xl shadow-sm overflow-hidden"
                >
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-lg bg-[#0D542B] text-white font-bold text-xs flex items-center justify-center">
                                {{ section.order }}
                            </span>
                            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">
                                {{ section.title }}
                            </h3>
                        </div>

                        <span 
                            v-if="section.unanswered_mandatory_count > 0"
                            class="px-3 py-1 bg-[#FDC700] text-black font-bold text-xs rounded-full"
                        >
                            {{ section.unanswered_mandatory_count }} Wajib Belum Dijawab
                        </span>
                        <span v-else class="px-3 py-1 bg-[#0D542B] text-white font-bold text-xs rounded-full">
                            Lengkap
                        </span>
                    </div>

                    <div class="p-6 space-y-4">
                        <div 
                            v-for="q in (section.subpertanyaans || section.questions)" 
                            :key="q.id"
                            class="bg-white rounded-xl p-5 border border-gray-100"
                        >
                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2 mb-2">
                                <div class="flex items-start gap-2.5">
                                    <span class="px-2.5 py-1 bg-gray-100 text-gray-700 font-mono text-xs font-bold rounded-lg shrink-0">
                                        {{ q.kode_pertanyaan || q.code }}
                                    </span>
                                    <h4 class="text-sm font-bold text-gray-900 leading-snug">
                                        {{ q.subpertanyaan || q.question_text }}
                                    </h4>
                                </div>
                                <span 
                                    class="text-[11px] font-bold px-2.5 py-0.5 rounded-md self-start"
                                    :class="(q.wajib ?? q.is_mandatory) ? 'bg-[#FDC700]/30 text-amber-900' : 'bg-gray-100 text-gray-500'"
                                >
                                    {{ (q.wajib ?? q.is_mandatory) ? 'Wajib' : 'Opsional' }}
                                </span>
                            </div>

                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <span class="text-xs text-gray-400 font-semibold uppercase tracking-wider block mb-1">Jawaban Alumni:</span>
                                <div 
                                    v-if="q.is_answered"
                                    class="p-3 bg-emerald-50/70 border border-emerald-100 rounded-xl text-xs font-bold text-[#0D542B] leading-relaxed"
                                >
                                    {{ q.answer_text }}
                                </div>
                                <div 
                                    v-else 
                                    class="p-3 bg-gray-50 rounded-xl text-xs text-gray-400 italic"
                                >
                                    Belum dijawab oleh alumni
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 3: DETAIL PROFIL MAHASISWA                            -->
            <!-- ========================================================= -->
            <div v-if="activeMainTab === 'profil'" class="bg-white rounded-3xl p-6 md:p-8 shadow-sm space-y-6">
                <!-- Sub Tab Navigasi Profil -->
                <div class="flex flex-wrap gap-2 border-b border-gray-100 pb-4">
                    <button 
                        @click="activeProfileTab = 'pribadi'"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer"
                        :class="activeProfileTab === 'pribadi' ? 'bg-[#0D542B] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    >
                        1. Biodata Pribadi
                    </button>
                    <button 
                        @click="activeProfileTab = 'akademik'"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer"
                        :class="activeProfileTab === 'akademik' ? 'bg-[#0D542B] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    >
                        2. Akademik & Yudisium
                    </button>
                    <button 
                        @click="activeProfileTab = 'orangtua'"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer"
                        :class="activeProfileTab === 'orangtua' ? 'bg-[#0D542B] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    >
                        3. Data Orang Tua
                    </button>
                    <button 
                        @click="activeProfileTab = 'karier'"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer"
                        :class="activeProfileTab === 'karier' ? 'bg-[#0D542B] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    >
                        4. Pekerjaan & Perusahaan
                    </button>
                </div>

                <!-- Konten 1: Biodata Pribadi -->
                <div v-if="activeProfileTab === 'pribadi'" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Nama Lengkap</div>
                        <div class="font-bold text-gray-900 text-sm">{{ formData.nama || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">NIM</div>
                        <div class="font-mono font-bold text-gray-900 text-sm">{{ formData.nim || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Tempat, Tanggal Lahir</div>
                        <div class="font-medium text-gray-800">{{ formData.tempat_lahir || '-' }}, {{ formData.tanggal_lahir || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Jenis Kelamin / Agama</div>
                        <div class="font-medium text-gray-800">{{ formData.jenis_kelamin || '-' }} &bull; {{ formData.agama || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Nomor Telepon / WhatsApp</div>
                        <div class="font-medium text-gray-800">{{ formData.nomor_telepon || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Email Pribadi</div>
                        <div class="font-medium text-gray-800">{{ formData.email_pribadi || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2 md:col-span-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Alamat Saat Ini</div>
                        <div class="font-medium text-gray-800">{{ formData.alamat_saat_ini || '-' }}</div>
                    </div>
                </div>

                <!-- Konten 2: Data Akademik & Yudisium -->
                <div v-if="activeProfileTab === 'akademik'" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Angkatan Masuk</div>
                        <div class="font-bold text-gray-900 text-sm">{{ formData.angkatan_masuk || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Tahun Akademik Kelulusan</div>
                        <div class="font-bold text-gray-900 text-sm">{{ formData.tahun_akademik_lulus || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Indeks Prestasi Kumulatif (IPK)</div>
                        <div class="font-bold text-[#0D542B] text-base">{{ formData.ipk || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Total SKS Diselesaikan</div>
                        <div class="font-medium text-gray-800">{{ formData.total_sks || '-' }} SKS</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2 md:col-span-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Judul Tugas Akhir / Skripsi</div>
                        <div class="font-bold text-gray-900 text-sm leading-relaxed">{{ formData.judul_ta || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Dosen Pembimbing 1 & 2</div>
                        <div class="font-medium text-gray-800">{{ formData.dosen_pembimbing_1 || '-' }} / {{ formData.dosen_pembimbing_2 || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Hasil Yudisium</div>
                        <div class="font-medium text-gray-800">{{ formData.keterangan_hasil_yudisium || formData.proses_yudisium || '-' }}</div>
                    </div>
                </div>

                <!-- Konten 3: Data Orang Tua -->
                <div v-if="activeProfileTab === 'orangtua'" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Nama Orang Tua / Wali</div>
                        <div class="font-bold text-gray-900 text-sm">{{ formData.nama_orang_tua || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Pekerjaan Orang Tua</div>
                        <div class="font-medium text-gray-800">{{ formData.pekerjaan_orang_tua || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Nomor Telepon Orang Tua</div>
                        <div class="font-medium text-gray-800">{{ formData.nomor_telepon_orang_tua || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Kota / Domisili Orang Tua</div>
                        <div class="font-medium text-gray-800">{{ formData.kota_orang_tua || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2 md:col-span-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Alamat Orang Tua</div>
                        <div class="font-medium text-gray-800">{{ formData.alamat_orang_tua || '-' }}</div>
                    </div>
                </div>

                <!-- Konten 4: Karier & Perusahaan -->
                <div v-if="activeProfileTab === 'karier'" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Nama Tempat Kerja / Perusahaan</div>
                        <div class="font-bold text-gray-900 text-sm">{{ formData.nama_perusahaan || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Posisi / Jabatan</div>
                        <div class="font-medium text-gray-800">{{ formData.posisi_jabatan || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Skala Instansi</div>
                        <div class="font-medium text-gray-800">{{ formData.company_skala || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Status Verifikasi Perusahaan</div>
                        <div class="font-medium text-gray-800">{{ formData.company_status_verifikasi || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Nama Atasan Langsung</div>
                        <div class="font-medium text-gray-800">{{ formData.nama_atasan || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">Kontak Atasan (Email / Telepon)</div>
                        <div class="font-medium text-gray-800">{{ formData.email_atasan || '-' }} / {{ formData.telepon_atasan || '-' }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl space-y-2 md:col-span-2">
                        <div class="text-gray-400 uppercase font-bold text-[10px]">LinkedIn URL</div>
                        <div class="font-mono text-gray-700">
                            <a v-if="formData.linkedin_url" :href="formData.linkedin_url" target="_blank" class="text-blue-600 hover:underline">
                                {{ formData.linkedin_url }}
                            </a>
                            <span v-else>-</span>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>
</template>
