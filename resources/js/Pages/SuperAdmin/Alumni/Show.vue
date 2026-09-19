<!--
  Halaman Detail Mahasiswa & Audit Kuesioner Tracer Study (Super Admin)
  File: resources/js/Pages/SuperAdmin/Alumni/Show.vue
  
  Warna Resmi Solid UKDW (Sesuai Logo & Gambar Pengguna):
  - Hijau: #0D542B (Solid, tanpa gradasi)
  - Kuning: #FDC700 (Kuning UKDW murni)
  - Putih: #FFFFFF
  Desain profesional, bersih, bebas border bertumpuk-tumpuk, bebas border hover.
-->
<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import Navbar from '../Components/Navbar.vue';

// Mengimpor 4 Komponen Profil Alumni
import FormPribadi from '../../Alumni/Profil/Components/FormPribadi.vue';
import FormAkademik from '../../Alumni/Profil/Components/FormAkademik.vue';
import FormOrangTua from '../../Alumni/Profil/Components/FormOrangTua.vue';
import FormKarier from '../../Alumni/Profil/Components/FormKarier.vue';

const props = defineProps({
    alumni: {
        type: Object,
        required: true,
    },
    evaluasi: {
        type: Object,
        required: true,
    },
    sections: {
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
    provinces: {
        type: Array,
        default: () => [],
    },
    kabupatens: {
        type: Array,
        default: () => [],
    },
    companies: {
        type: Array,
        default: () => [],
    },
});

// Tab Utama: 'kuesioner', 'kuesioner_prodi', atau 'profil'
const activeMainTab = ref('kuesioner');

// Sub-Tab Profil: 'pribadi', 'akademik', 'orangtua', 'karier'
const activeProfileTab = ref('pribadi');

// Form data reaktif untuk edit profil oleh Super Admin
const form = useForm(JSON.parse(JSON.stringify(props.formData || {})));

// Simpan perubahan profil oleh Super Admin
const simpanProfilAlumni = () => {
    Swal.fire({
        title: 'Konfirmasi Simpan Profil',
        text: 'Apakah Anda yakin ingin memperbarui data profil alumni ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0D542B',
        cancelButtonColor: '#9CA3AF',
        confirmButtonText: 'Ya, Simpan',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            form.post(`/superadmin/alumni/${props.alumni.id}/profile`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Disimpan',
                        text: 'Data profil alumni telah berhasil diperbarui oleh Super Admin.',
                        confirmButtonColor: '#0D542B',
                    });
                },
                onError: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan',
                        text: 'Periksa kembali data yang dimasukkan.',
                        confirmButtonColor: '#0D542B',
                    });
                },
            });
        }
    });
};

// Filter section pada tab kuesioner universitas ('all' atau ID section)
const activeSectionId = ref('all');

const filteredSections = computed(() => {
    if (activeSectionId.value === 'all') {
        return props.sections;
    }
    return props.sections.filter(s => s.id === activeSectionId.value);
});

// Filter section pada tab kuesioner prodi
const activeProdiSectionId = ref('all');

const filteredProdiSections = computed(() => {
    if (activeProdiSectionId.value === 'all') {
        return props.prodiSections;
    }
    return props.prodiSections.filter(s => s.id === activeProdiSectionId.value);
});

// Total pertanyaan wajib yang belum dijawab
const totalUnansweredMandatory = computed(() => {
    return props.sections.reduce((acc, s) => acc + (s.unanswered_mandatory_count || 0), 0);
});

// Helper Semester & Yudisium
const semesterKelulusan = computed(() => {
    return props.alumni?.data_akademik?.tahun_akademik_lulus || props.alumni?.tahun_lulus || '-';
});

const statusYudisium = computed(() => {
    return props.alumni?.data_akademik?.yudisium?.proses_yudisium || 'Lulus';
});
</script>

<template>
    <Head :title="`Detail Mahasiswa - ${alumni.data_akademik?.nama || alumni.nim} - Super Admin`" />

    <div class="min-h-screen bg-[#f8fafc] text-gray-800 font-sans pb-24">
        <!-- Navbar Resmi Super Admin -->
        <Navbar />

        <!-- Header Solid Hijau Resmi UKDW #0D542B (Tanpa gradasi) -->
        <header class="bg-[#0D542B] text-white pt-10 pb-20">
            <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2 text-xs text-white/80 font-medium mb-3">
                    <Link href="/superadmin/dashboard" class="hover:underline">Dashboard</Link>
                    <span>/</span>
                    <Link href="/superadmin/alumni" class="hover:underline">Data Alumni</Link>
                    <span>/</span>
                    <span class="text-white font-bold">Detail Mahasiswa</span>
                </div>

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                    <div>
                        <!-- Badges Header Solid UKDW -->
                        <div class="flex flex-wrap items-center gap-2.5 mb-2.5">
                            <!-- Badge Yudisium: Kuning UKDW #FDC700 murni -->
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
                            Program Studi: <span class="font-semibold text-white">{{ alumni.prodi?.nama_prodi || '-' }}</span>
                        </p>
                    </div>

                    <div class="flex items-center gap-3 shrink-0 flex-wrap">
                        <a 
                            :href="`/superadmin/alumni/${alumni.id}/export-excel`"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#FDC700] hover:bg-[#e5b400] text-black text-xs font-extrabold rounded-xl transition-all shadow-sm"
                            title="Download seluruh butir pertanyaan & jawaban mahasiswa ini ke file Excel (CSV)"
                        >
                            <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            <span>Download Excel Jawaban</span>
                        </a>

                        <Link 
                            href="/superadmin/alumni"
                            class="inline-flex items-center px-5 py-2.5 bg-white/15 hover:bg-white/25 border border-white/20 text-white text-xs font-bold rounded-xl transition-all"
                        >
                            &larr; Kembali ke Daftar
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 -mt-10 space-y-6">
            
            <!-- Card Ringkasan Status Audit (Card Putih Bersih, Tanpa Border Bertumpuk) -->
            <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                    <!-- Status Profil -->
                    <div class="pb-4 md:pb-0 md:pr-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Kelengkapan Data Profil</span>
                            <span 
                                class="text-xs font-bold px-3 py-0.5 rounded-full"
                                :class="evaluasi.profile.is_complete ? 'bg-[#0D542B] text-white' : 'bg-[#FDC700] text-black'"
                            >
                                {{ evaluasi.profile.percentage }}% Terisi
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden mb-2">
                            <div 
                                class="h-2 rounded-full transition-all bg-[#0D542B]"
                                :style="{ width: `${evaluasi.profile.percentage}%` }"
                            ></div>
                        </div>
                        <p class="text-xs text-gray-500">
                            {{ evaluasi.profile.is_complete ? 'Data profil lengkap (Biodata Pribadi, Akademik, Orang Tua, Perusahaan, & Atasan).' : `${evaluasi.profile.missing_fields.length} butir data profil belum terisi lengkap.` }}
                        </p>
                    </div>

                    <!-- Status Kuesioner Wajib Univ -->
                    <div class="pt-4 md:pt-0 md:px-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Kuesioner Wajib Tracer</span>
                            <span 
                                class="text-xs font-bold px-3 py-0.5 rounded-full"
                                :class="evaluasi.questionnaire.is_complete ? 'bg-[#0D542B] text-white' : 'bg-[#FDC700] text-black'"
                            >
                                {{ evaluasi.questionnaire.percentage }}% Terjawab
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden mb-2">
                            <div 
                                class="h-2 rounded-full transition-all bg-[#0D542B]"
                                :style="{ width: `${evaluasi.questionnaire.percentage}%` }"
                            ></div>
                        </div>
                        <p class="text-xs text-gray-500">
                            {{ evaluasi.questionnaire.is_complete ? 'Seluruh butir pertanyaan wajib telah dijawab oleh alumni.' : `${evaluasi.questionnaire.total_mandatory - evaluasi.questionnaire.answered_count} pertanyaan wajib belum dijawab.` }}
                        </p>
                    </div>

                    <!-- Status Kuesioner Prodi -->
                    <div class="pt-4 md:pt-0 md:pl-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Kuesioner Khusus Prodi</span>
                            <span 
                                class="text-xs font-bold px-3 py-0.5 rounded-full"
                                :class="prodiEvaluasi.is_complete ? 'bg-[#0D542B] text-white' : 'bg-[#FDC700] text-black'"
                            >
                                {{ prodiEvaluasi.percentage }}% Terjawab
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden mb-2">
                            <div 
                                class="h-2 rounded-full transition-all bg-[#0D542B]"
                                :style="{ width: `${prodiEvaluasi.percentage}%` }"
                            ></div>
                        </div>
                        <p class="text-xs text-gray-500">
                            {{ prodiEvaluasi.is_complete ? 'Seluruh butir kuesioner prodi telah diisi lengkap.' : `${prodiEvaluasi.total_questions - prodiEvaluasi.answered_count} pertanyaan prodi belum dijawab.` }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tab Navigasi Utama: Kuesioner Tracer, Kuesioner Prodi, Profil Mahasiswa -->
            <div class="flex items-center gap-4 border-b border-gray-200">
                <button 
                    @click="activeMainTab = 'kuesioner'"
                    class="py-3 px-2 text-sm font-bold border-b-2 transition-all cursor-pointer"
                    :class="activeMainTab === 'kuesioner' ? 'border-[#0D542B] text-[#0D542B]' : 'border-transparent text-gray-400 hover:text-gray-700'"
                >
                    Jawaban Kuesioner Tracer
                </button>

                <button 
                    v-if="alumni.prodi_id"
                    @click="activeMainTab = 'kuesioner_prodi'"
                    class="py-3 px-2 text-sm font-bold border-b-2 transition-all cursor-pointer"
                    :class="activeMainTab === 'kuesioner_prodi' ? 'border-[#0D542B] text-[#0D542B]' : 'border-transparent text-gray-400 hover:text-gray-700'"
                >
                    Kuesioner Khusus Prodi ({{ prodiEvaluasi.answered_count }}/{{ prodiEvaluasi.total_questions }})
                </button>

                <button 
                    @click="activeMainTab = 'profil'"
                    class="py-3 px-2 text-sm font-bold border-b-2 transition-all cursor-pointer"
                    :class="activeMainTab === 'profil' ? 'border-[#0D542B] text-[#0D542B]' : 'border-transparent text-gray-400 hover:text-gray-700'"
                >
                    Detail Profil Mahasiswa (Edit & Simpan)
                </button>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 1: JAWABAN KUESIONER TRACER STUDY                     -->
            <!-- ========================================================= -->
            <div v-if="activeMainTab === 'kuesioner'" class="space-y-6">
                
                <!-- Navigasi Filter Section (Card Putih Bersih) -->
                <div class="bg-white p-5 rounded-2xl shadow-sm">
                    <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">
                        Filter Bagian Kuesioner:
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <!-- Opsi Semua Bagian -->
                        <button 
                            @click="activeSectionId = 'all'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2"
                            :class="activeSectionId === 'all' ? 'bg-[#0D542B] text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                        >
                            <span>Semua Bagian (All Sections)</span>
                            <span 
                                v-if="totalUnansweredMandatory > 0"
                                class="px-2 py-0.2 rounded-full text-[10px] font-bold"
                                :class="activeSectionId === 'all' ? 'bg-white/20 text-white' : 'bg-[#FDC700] text-black'"
                            >
                                {{ totalUnansweredMandatory }} belum
                            </span>
                        </button>

                        <!-- Tombol Setiap Section -->
                        <button 
                            v-for="s in sections" 
                            :key="s.id"
                            @click="activeSectionId = s.id"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2"
                            :class="activeSectionId === s.id ? 'bg-[#0D542B] text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                        >
                            <span>{{ s.title }}</span>
                            <span 
                                v-if="s.unanswered_mandatory_count > 0"
                                class="px-2 py-0.2 rounded-full text-[10px] font-bold"
                                :class="activeSectionId === s.id ? 'bg-white/20 text-white' : 'bg-[#FDC700] text-black'"
                            >
                                {{ s.unanswered_mandatory_count }} belum
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Kartu Section Kuesioner (Card Putih Bersih) -->
                <div 
                    v-for="section in filteredSections" 
                    :key="section.id"
                    class="bg-white rounded-2xl shadow-sm overflow-hidden"
                >
                    <!-- Header Section Bersih -->
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-lg bg-[#0D542B] text-white font-bold text-xs flex items-center justify-center">
                                {{ section.order }}
                            </span>
                            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">
                                {{ section.title }}
                            </h3>
                        </div>
                        
                        <div class="flex items-center gap-2">
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
                    </div>

                    <!-- Daftar Butir Soal & Jawaban (Card Putih Rapi & Bebas Hover Border) -->
                    <div class="p-6 space-y-4">
                        <div 
                            v-for="q in (section.subpertanyaans || section.questions)" 
                            :key="q.id"
                            class="bg-white rounded-xl p-5 border border-gray-100 transition-all"
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

                                <!-- Label Wajib vs Opsional -->
                                <div class="shrink-0">
                                    <span 
                                        v-if="q.wajib ?? q.is_mandatory"
                                        class="px-2.5 py-0.5 bg-[#FDC700] text-black font-bold text-[10px] rounded-full uppercase tracking-wider"
                                    >
                                        Wajib
                                    </span>
                                    <span 
                                        v-else
                                        class="px-2.5 py-0.5 bg-gray-100 text-gray-600 font-medium text-[10px] rounded-full uppercase tracking-wider"
                                    >
                                        Opsional
                                    </span>
                                </div>
                            </div>

                            <!-- Kotak Jawaban Alumni (Elegan, Bersih, Border Hijau Solid UKDW #0D542B Kiri Rapi) -->
                            <div class="mt-3 pl-0 sm:pl-9">
                                <div 
                                    v-if="q.is_answered"
                                    class="p-4 rounded-xl bg-gray-50 border-l-4 border-l-[#0D542B]"
                                >
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-[11px] font-bold text-[#0D542B] uppercase tracking-wider block">
                                            Jawaban Alumni:
                                        </span>
                                        <span class="text-[10px] font-bold text-[#0D542B]">
                                            Terisi
                                        </span>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900 block leading-relaxed">
                                        {{ q.answer_text }}
                                    </span>
                                </div>

                                <div 
                                    v-else-if="q.is_mandatory"
                                    class="p-4 rounded-xl bg-gray-50 border-l-4 border-l-[#FDC700]"
                                >
                                    <span class="font-bold uppercase tracking-wider text-xs block mb-0.5 text-black">
                                        Belum Dijawab (Wajib Diisi)
                                    </span>
                                    <span class="text-xs text-gray-600 font-medium">Alumni belum memberikan jawaban untuk butir pertanyaan wajib ini.</span>
                                </div>

                                <div 
                                    v-else
                                    class="p-3 bg-gray-50 rounded-xl text-xs text-gray-400 italic"
                                >
                                    (Tidak diisi &mdash; Opsional)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB: JAWABAN KUESIONER KHUSUS PROGRAM STUDI               -->
            <!-- ========================================================= -->
            <div v-else-if="activeMainTab === 'kuesioner_prodi'" class="space-y-6">
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

                <div v-if="filteredProdiSections.length === 0" class="bg-white p-12 text-center rounded-2xl shadow-sm text-gray-400">
                    Tidak ada butir pertanyaan prodi untuk program studi mahasiswa ini.
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
            <!-- TAB 3: DETAIL PROFIL MAHASISWA                            -->
            <!-- ========================================================= -->
            <div v-else-if="activeMainTab === 'profil'" class="space-y-6">
                
                <!-- Sub-navigasi Tab Profil (Persis Profil Alumni) & Tombol Simpan -->
                <div class="bg-white p-5 rounded-2xl shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex flex-wrap gap-2">
                        <button 
                            @click="activeProfileTab = 'pribadi'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer"
                            :class="activeProfileTab === 'pribadi' ? 'bg-[#0D542B] text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                        >
                            1. Data Pribadi
                        </button>
                        <button 
                            @click="activeProfileTab = 'akademik'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer"
                            :class="activeProfileTab === 'akademik' ? 'bg-[#0D542B] text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                        >
                            2. Data Akademik
                        </button>
                        <button 
                            @click="activeProfileTab = 'orangtua'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer"
                            :class="activeProfileTab === 'orangtua' ? 'bg-[#0D542B] text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                        >
                            3. Data Orang Tua
                        </button>
                        <button 
                            @click="activeProfileTab = 'karier'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer"
                            :class="activeProfileTab === 'karier' ? 'bg-[#0D542B] text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                        >
                            4. Karier & Perusahaan
                        </button>
                    </div>

                    <!-- Tombol Simpan Profil oleh Super Admin (Hijau Solid UKDW #0D542B) -->
                    <button 
                        @click="simpanProfilAlumni"
                        :disabled="form.processing"
                        class="px-6 py-2.5 bg-[#0D542B] hover:bg-[#08381c] text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center cursor-pointer disabled:opacity-50"
                    >
                        <span v-if="form.processing">Menyimpan...</span>
                        <span v-else>Simpan Perubahan Profil</span>
                    </button>
                </div>

                <!-- Komponen Anak Form Profil Alumni -->
                <div class="bg-white rounded-2xl p-6 md:p-8 shadow-sm">
                    <FormPribadi 
                        v-show="activeProfileTab === 'pribadi'"
                        :form="form" 
                        :provinces="provinces" 
                        :kabupatens="kabupatens" 
                    />

                    <FormAkademik 
                        v-show="activeProfileTab === 'akademik'"
                        :form="form" 
                    />

                    <FormOrangTua 
                        v-show="activeProfileTab === 'orangtua'"
                        :form="form" 
                        :provinces="provinces" 
                        :kabupatens="kabupatens" 
                    />

                    <FormKarier 
                        v-show="activeProfileTab === 'karier'"
                        :form="form" 
                        :provinces="provinces" 
                        :kabupatens="kabupatens" 
                        :companies="companies" 
                        :alumniData="alumni" 
                    />
                </div>

                <!-- Tombol Simpan Bawah -->
                <div class="flex justify-end pt-2">
                    <button 
                        @click="simpanProfilAlumni"
                        :disabled="form.processing"
                        class="px-7 py-3 bg-[#0D542B] hover:bg-[#08381c] text-white rounded-xl text-xs font-bold transition-all cursor-pointer disabled:opacity-50"
                    >
                        <span v-if="form.processing">Menyimpan Perubahan...</span>
                        <span v-else>Simpan Perubahan Profil Alumni</span>
                    </button>
                </div>
            </div>

        </main>
    </div>
</template>
