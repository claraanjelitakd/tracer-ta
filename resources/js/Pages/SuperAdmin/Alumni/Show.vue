<!--
  Halaman Detail Mahasiswa & Audit Kuesioner Tracer Study (Super Admin)
  File: resources/js/Pages/SuperAdmin/Alumni/Show.vue
  
  Format Tampilan:
  1. Detail Profil (Urutan 1)
  2. Kuesioner Universitas (Urutan 2, Tampilan Data Tables ala Excel)
  3. Kuesioner Program Studi: [Nama Prodi] (Urutan 3, Tampilan Data Tables ala Excel)
  
  Aturan Tampilan Data Table Excel:
  - Header Section / Question Header: Background Kuning (#FDC700), tanpa kolom jawaban.
  - Butir Belum Dijawab: Background Merah lembut (#FFF1F2 / rose-50) & status merah tegas.
  - Butir Terjawab: Background Putih/Hijau (#FFFFFF / emerald-50) & status hijau terisi.
-->
<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import Sidebar from '../Components/Sidebar.vue';

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

// Urutan Tab Utama: 'profil' (1), 'kuesioner' (2), 'kuesioner_prodi' (3)
const activeMainTab = ref('profil');

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

// State Pencarian Teks di Data Table
const searchQueryUniv = ref('');
const searchQueryProdi = ref('');

// Filter section pada tab kuesioner universitas ('all' atau ID section)
const activeSectionId = ref('all');

const filteredSections = computed(() => {
    let list = activeSectionId.value === 'all'
        ? props.sections
        : props.sections.filter(s => s.id === activeSectionId.value);

    if (!searchQueryUniv.value.trim()) {
        return list;
    }

    const q = searchQueryUniv.value.toLowerCase();
    return list.map(section => {
        const matchingQuestions = (section.subpertanyaans || []).filter(item => {
            return (item.kode_pertanyaan && item.kode_pertanyaan.toLowerCase().includes(q)) ||
                   (item.subpertanyaan && item.subpertanyaan.toLowerCase().includes(q)) ||
                   (item.answer && String(item.answer).toLowerCase().includes(q)) ||
                   (item.type && item.type.toLowerCase().includes(q));
        });
        return {
            ...section,
            subpertanyaans: matchingQuestions,
        };
    }).filter(s => s.subpertanyaans.length > 0);
});

// Filter section pada tab kuesioner prodi
const activeProdiSectionId = ref('all');

const filteredProdiSections = computed(() => {
    let list = activeProdiSectionId.value === 'all'
        ? props.prodiSections
        : props.prodiSections.filter(s => s.id === activeProdiSectionId.value);

    if (!searchQueryProdi.value.trim()) {
        return list;
    }

    const q = searchQueryProdi.value.toLowerCase();
    return list.map(section => {
        const matchingQuestions = (section.questions || []).filter(item => {
            return (item.code && item.code.toLowerCase().includes(q)) ||
                   (item.question_text && item.question_text.toLowerCase().includes(q)) ||
                   (item.answer_text && String(item.answer_text).toLowerCase().includes(q)) ||
                   (item.type && item.type.toLowerCase().includes(q));
        });
        return {
            ...section,
            questions: matchingQuestions,
        };
    }).filter(s => s.questions.length > 0);
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

    <div class="min-h-screen bg-[#f8fafc] text-gray-800 font-sans flex overscroll-none">
        <!-- Sidebar Resmi Super Admin -->
        <Sidebar />

        <!-- Area Konten Utama -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-72 overscroll-none">
            
            <!-- Header Solid Hijau Resmi UKDW #0D542B -->
            <header class="bg-[#0D542B] text-white py-6 px-4 sm:px-6 lg:px-8 border-b border-[#0A4322]">
                <div class="max-w-[1400px] mx-auto">
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

                            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
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
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#FDC700] hover:bg-[#e5b400] text-black text-xs font-extrabold rounded-xl transition-all shadow-sm cursor-pointer"
                                title="Download seluruh butir pertanyaan & jawaban mahasiswa ini ke file Excel (CSV)"
                            >
                                <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                <span>Download Excel (CSV)</span>
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

            <!-- Main Content (Stay & Solid Layout) -->
            <main class="max-w-[1400px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6 pb-16">
                
                <!-- Card Ringkasan Status Audit -->
                <div class="bg-white rounded-2xl p-6 md:p-8 shadow-xs border border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                        <!-- Status Profil -->
                        <div class="pb-4 md:pb-0 md:pr-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">1. Kelengkapan Profil</span>
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
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">2. Kuesioner Universitas</span>
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
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">3. Kuesioner Prodi</span>
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

                <!-- ============================================================= -->
                <!-- TAB NAVIGASI UTAMA (URUTAN 1, 2, 3 SESUAI PERMINTAAN)        -->
                <!-- 1. Detail Profil                                              -->
                <!-- 2. Kuesioner Universitas                                      -->
                <!-- 3. Kuesioner Program Studi: [Nama Prodi]                      -->
                <!-- ============================================================= -->
                <div class="bg-white rounded-2xl shadow-xs border border-gray-200 p-2 flex items-center gap-2 overflow-x-auto no-scrollbar">
                    <!-- Tab 1: Detail Profil (Urutan 1) -->
                    <button 
                        @click="activeMainTab = 'profil'"
                        class="py-3 px-5 text-xs sm:text-sm font-bold rounded-xl transition-all cursor-pointer flex items-center gap-2 shrink-0"
                        :class="activeMainTab === 'profil' ? 'bg-[#0D542B] text-white shadow-sm' : 'bg-gray-50 hover:bg-gray-100 text-gray-600'"
                    >
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black" :class="activeMainTab === 'profil' ? 'bg-white text-[#0D542B]' : 'bg-gray-200 text-gray-700'">1</span>
                        <span>Detail Profile</span>
                    </button>

                    <!-- Tab 2: Kuesioner Universitas (Urutan 2) -->
                    <button 
                        @click="activeMainTab = 'kuesioner'"
                        class="py-3 px-5 text-xs sm:text-sm font-bold rounded-xl transition-all cursor-pointer flex items-center gap-2 shrink-0"
                        :class="activeMainTab === 'kuesioner' ? 'bg-[#0D542B] text-white shadow-sm' : 'bg-gray-50 hover:bg-gray-100 text-gray-600'"
                    >
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black" :class="activeMainTab === 'kuesioner' ? 'bg-white text-[#0D542B]' : 'bg-gray-200 text-gray-700'">2</span>
                        <span>Kuesioner Universitas</span>
                    </button>

                    <!-- Tab 3: Kuesioner Program Studi: Nama Prodi (Urutan 3) -->
                    <button 
                        v-if="alumni.prodi_id"
                        @click="activeMainTab = 'kuesioner_prodi'"
                        class="py-3 px-5 text-xs sm:text-sm font-bold rounded-xl transition-all cursor-pointer flex items-center gap-2 shrink-0"
                        :class="activeMainTab === 'kuesioner_prodi' ? 'bg-[#0D542B] text-white shadow-sm' : 'bg-gray-50 hover:bg-gray-100 text-gray-600'"
                    >
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black" :class="activeMainTab === 'kuesioner_prodi' ? 'bg-white text-[#0D542B]' : 'bg-gray-200 text-gray-700'">3</span>
                        <span>Kuesioner Program Studi: {{ alumni.prodi?.nama_prodi || 'Program Studi' }}</span>
                    </button>
                </div>

                <!-- ============================================================= -->
                <!-- HALAMAN 1: DETAIL PROFIL MAHASISWA                            -->
                <!-- ============================================================= -->
                <div v-if="activeMainTab === 'profil'" class="space-y-6">
                    
                    <!-- Sub-navigasi Tab Profil & Tombol Simpan -->
                    <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-xs border border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex flex-wrap gap-2">
                            <button 
                                @click="activeProfileTab = 'pribadi'"
                                class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                                :class="activeProfileTab === 'pribadi' ? 'bg-[#0D542B] text-white shadow-xs' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                            >
                                1. Data Pribadi
                            </button>
                            <button 
                                @click="activeProfileTab = 'akademik'"
                                class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                                :class="activeProfileTab === 'akademik' ? 'bg-[#0D542B] text-white shadow-xs' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                            >
                                2. Data Akademik
                            </button>
                            <button 
                                @click="activeProfileTab = 'orangtua'"
                                class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                                :class="activeProfileTab === 'orangtua' ? 'bg-[#0D542B] text-white shadow-xs' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                            >
                                3. Data Orang Tua
                            </button>
                            <button 
                                @click="activeProfileTab = 'karier'"
                                class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                                :class="activeProfileTab === 'karier' ? 'bg-[#0D542B] text-white shadow-xs' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                            >
                                4. Karier & Perusahaan
                            </button>
                        </div>

                        <!-- Tombol Simpan Profil oleh Super Admin (Hijau Solid UKDW #0D542B) -->
                        <button 
                            @click="simpanProfilAlumni"
                            :disabled="form.processing"
                            class="px-6 py-2.5 bg-[#0D542B] hover:bg-[#08381c] text-white rounded-xl text-xs font-extrabold transition-all flex items-center justify-center cursor-pointer shadow-xs disabled:opacity-50"
                        >
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>Simpan Perubahan Profil</span>
                        </button>
                    </div>

                    <!-- Komponen Form Profil Alumni (Ukuran Konsisten Full-Width Card) -->
                    <div class="space-y-6">
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
                            class="px-7 py-3 bg-[#0D542B] hover:bg-[#08381c] text-white rounded-xl text-xs font-extrabold transition-all cursor-pointer shadow-sm disabled:opacity-50"
                        >
                            <span v-if="form.processing">Menyimpan Perubahan...</span>
                            <span v-else>Simpan Perubahan Profil Alumni</span>
                        </button>
                    </div>
                </div>

                <!-- ============================================================= -->
                <!-- HALAMAN 2: KUESIONER UNIVERSITAS (TABLE BERSIH & DROPDOWN)    -->
                <!-- ============================================================= -->
                <div v-else-if="activeMainTab === 'kuesioner'" class="space-y-6">
                    
                    <!-- Heading & Bar Filter / Pencarian -->
                    <div class="bg-white p-5 rounded-2xl shadow-xs border border-gray-200 space-y-4">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-base sm:text-lg font-black text-gray-900 flex items-center gap-2">
                                    <span class="w-2.5 h-5 bg-[#0D542B] rounded-full inline-block"></span>
                                    Kuesioner Tracer Study Universitas
                                </h2>
                                <p class="text-xs text-gray-500 mt-0.5">Tampilan data tables terstruktur untuk seluruh butir instrumen dan jawaban tracer study.</p>
                            </div>
                        </div>

                        <!-- Dropdown Filter Bagian & Input Pencarian -->
                        <div class="pt-3 border-t border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <!-- Dropdown Section Langsung Nama Section -->
                            <div class="flex items-center gap-2.5 w-full md:w-auto">
                                <label class="text-xs font-bold text-gray-600 whitespace-nowrap">Filter Bagian:</label>
                                <select 
                                    v-model="activeSectionId"
                                    class="w-full md:w-96 px-3.5 py-2 bg-gray-50 hover:bg-white border border-gray-300 rounded-xl text-xs font-semibold text-gray-800 focus:ring-2 focus:ring-[#0D542B]/20 focus:border-[#0D542B] transition-all cursor-pointer"
                                >
                                    <option value="all">Semua Bagian ({{ sections.length }} Bagian)</option>
                                    <option 
                                        v-for="s in sections" 
                                        :key="s.id" 
                                        :value="s.id"
                                    >
                                        {{ s.title || s.section || ('Bagian ' + s.order) }} {{ s.unanswered_mandatory_count > 0 ? `(${s.unanswered_mandatory_count} belum)` : '' }}
                                    </option>
                                </select>
                            </div>

                            <!-- Input Pencarian Cepat -->
                            <div class="w-full md:w-80">
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        v-model="searchQueryUniv"
                                        placeholder="Cari kode, pertanyaan, jawaban..."
                                        class="w-full pl-9 pr-4 py-2 bg-gray-50 hover:bg-white border border-gray-300 rounded-xl text-xs font-medium text-gray-800 focus:ring-2 focus:ring-[#0D542B]/20 focus:border-[#0D542B] focus:bg-white transition-all"
                                    />
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DATA TABLE STANDAR (KUESIONER UNIVERSITAS) -->
                    <div class="bg-white rounded-2xl shadow-xs border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-700 border-b border-gray-200">
                                        <th class="w-12 text-center py-3.5 px-3 font-bold text-xs uppercase tracking-wider border-r border-gray-200">No</th>
                                        <th class="w-24 text-center py-3.5 px-3 font-bold text-xs uppercase tracking-wider border-r border-gray-200">Kode</th>
                                        <th class="py-3.5 px-4 font-bold text-xs uppercase tracking-wider border-r border-gray-200 min-w-[280px]">Pertanyaan / Deskripsi Instrumen</th>
                                        <th class="w-24 text-center py-3.5 px-3 font-bold text-xs uppercase tracking-wider border-r border-gray-200">Tipe</th>
                                        <th class="w-20 text-center py-3.5 px-3 font-bold text-xs uppercase tracking-wider border-r border-gray-200">Sifat</th>
                                        <th class="w-32 text-center py-3.5 px-3 font-bold text-xs uppercase tracking-wider border-r border-gray-200">Status</th>
                                        <th class="w-80 py-3.5 px-4 font-bold text-xs uppercase tracking-wider min-w-[240px]">Jawaban Alumni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="section in filteredSections" :key="section.id">
                                        <!-- BARIS HEADER SECTION (KUNING LEMBUT, NAMA SECTION LANGSUNG) -->
                                        <tr class="bg-amber-100/75 text-amber-950 border-y border-amber-200 font-bold">
                                            <td colspan="7" class="py-3 px-4 text-xs tracking-wide">
                                                <div class="flex items-center justify-between">
                                                    <span>{{ section.title || section.section || ('Bagian ' + section.order) }}</span>
                                                    <span v-if="section.unanswered_mandatory_count > 0" class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                                        {{ section.unanswered_mandatory_count }} Wajib Belum Dijawab
                                                    </span>
                                                    <span v-else class="px-2 py-0.5 rounded text-[10px] font-medium bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                        Lengkap
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- BARIS BUTIR SOAL PER SECTION -->
                                        <tr 
                                            v-for="(q, idx) in section.subpertanyaans" 
                                            :key="q.id"
                                            :class="[
                                                q.is_header 
                                                    ? 'bg-amber-50/60 text-amber-950 font-bold border-b border-gray-200'
                                                    : 'bg-white hover:bg-gray-50/80 border-b border-gray-100'
                                            ]"
                                            class="transition-colors text-xs text-gray-800"
                                        >
                                            <!-- Kolom No -->
                                            <td class="text-center py-2.5 px-3 text-gray-500 font-medium border-r border-gray-100">
                                                {{ idx + 1 }}
                                            </td>

                                            <!-- Kolom Kode -->
                                            <td class="text-center py-2.5 px-3 font-mono font-semibold text-gray-700 border-r border-gray-100">
                                                {{ q.kode_pertanyaan || '-' }}
                                            </td>

                                            <!-- Kolom Pertanyaan (Jika header, span ke kanan) -->
                                            <td v-if="q.is_header" colspan="5" class="py-2.5 px-4 font-bold text-amber-950 text-xs">
                                                {{ q.subpertanyaan }}
                                            </td>

                                            <!-- Kolom Pertanyaan Biasa -->
                                            <td v-else class="py-2.5 px-4 font-medium text-gray-900 border-r border-gray-100 leading-relaxed">
                                                {{ q.subpertanyaan }}
                                            </td>

                                            <!-- Kolom Tipe -->
                                            <td v-if="!q.is_header" class="text-center py-2.5 px-3 capitalize font-normal text-gray-500 border-r border-gray-100 text-[11px]">
                                                {{ q.type || 'text' }}
                                            </td>

                                            <!-- Kolom Sifat -->
                                            <td v-if="!q.is_header" class="text-center py-2.5 px-3 border-r border-gray-100">
                                                <span v-if="q.is_mandatory" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                                    Wajib
                                                </span>
                                                <span v-else class="px-2 py-0.5 rounded text-[10px] font-normal bg-gray-50 text-gray-500">
                                                    Opsional
                                                </span>
                                            </td>

                                            <!-- Kolom Status -->
                                            <td v-if="!q.is_header" class="text-center py-2.5 px-3 border-r border-gray-100">
                                                <span v-if="q.is_answered" class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    Terjawab
                                                </span>
                                                <span v-else class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                                    Belum Dijawab
                                                </span>
                                            </td>

                                            <!-- Kolom Jawaban Alumni -->
                                            <td v-if="!q.is_header" class="py-2.5 px-4 text-gray-900 leading-relaxed">
                                                <span v-if="q.is_answered" class="text-xs font-semibold text-gray-900 whitespace-pre-line">
                                                    {{ q.answer }}
                                                </span>
                                                <span v-else class="text-xs text-rose-500 font-medium italic">
                                                    -
                                                </span>
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- State Kosong / Pencarian Nihil -->
                                    <tr v-if="filteredSections.length === 0">
                                        <td colspan="7" class="py-12 text-center text-gray-400 font-medium">
                                            Tidak ada butir pertanyaan yang sesuai dengan filter atau pencarian Anda.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ============================================================= -->
                <!-- HALAMAN 3: KUESIONER PROGRAM STUDI: [NAMA PRODI]              -->
                <!-- ============================================================= -->
                <div v-else-if="activeMainTab === 'kuesioner_prodi'" class="space-y-6">
                    
                    <!-- Heading & Bar Filter / Pencarian -->
                    <div class="bg-white p-5 rounded-2xl shadow-xs border border-gray-200 space-y-4">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-base sm:text-lg font-black text-gray-900 flex items-center gap-2">
                                    <span class="w-2.5 h-5 bg-[#0D542B] rounded-full inline-block"></span>
                                    Kuesioner Program Studi: {{ alumni.prodi?.nama_prodi || 'Program Studi' }}
                                </h2>
                                <p class="text-xs text-gray-500 mt-0.5">Tampilan data tables terstruktur untuk butir evaluasi kurikulum dan kepuasan khusus program studi.</p>
                            </div>
                        </div>

                        <!-- Dropdown Filter Bagian Prodi & Input Pencarian -->
                        <div class="pt-3 border-t border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <!-- Dropdown Section Langsung Nama Section -->
                            <div class="flex items-center gap-2.5 w-full md:w-auto">
                                <label class="text-xs font-bold text-gray-600 whitespace-nowrap">Filter Bagian:</label>
                                <select 
                                    v-model="activeProdiSectionId"
                                    class="w-full md:w-96 px-3.5 py-2 bg-gray-50 hover:bg-white border border-gray-300 rounded-xl text-xs font-semibold text-gray-800 focus:ring-2 focus:ring-[#0D542B]/20 focus:border-[#0D542B] transition-all cursor-pointer"
                                >
                                    <option value="all">Semua Bagian ({{ prodiSections.length }} Bagian)</option>
                                    <option 
                                        v-for="s in prodiSections" 
                                        :key="s.id" 
                                        :value="s.id"
                                    >
                                        {{ s.title }} {{ s.unanswered_mandatory_count > 0 ? `(${s.unanswered_mandatory_count} belum)` : '' }}
                                    </option>
                                </select>
                            </div>

                            <!-- Input Pencarian Cepat -->
                            <div class="w-full md:w-80">
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        v-model="searchQueryProdi"
                                        placeholder="Cari kode, instrumen, jawaban..."
                                        class="w-full pl-9 pr-4 py-2 bg-gray-50 hover:bg-white border border-gray-300 rounded-xl text-xs font-medium text-gray-800 focus:ring-2 focus:ring-[#0D542B]/20 focus:border-[#0D542B] focus:bg-white transition-all"
                                    />
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DATA TABLE STANDAR (KUESIONER PROGRAM STUDI) -->
                    <div class="bg-white rounded-2xl shadow-xs border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-700 border-b border-gray-200">
                                        <th class="w-12 text-center py-3.5 px-3 font-bold text-xs uppercase tracking-wider border-r border-gray-200">No</th>
                                        <th class="w-24 text-center py-3.5 px-3 font-bold text-xs uppercase tracking-wider border-r border-gray-200">Kode</th>
                                        <th class="py-3.5 px-4 font-bold text-xs uppercase tracking-wider border-r border-gray-200 min-w-[280px]">Pertanyaan / Deskripsi Instrumen</th>
                                        <th class="w-24 text-center py-3.5 px-3 font-bold text-xs uppercase tracking-wider border-r border-gray-200">Tipe</th>
                                        <th class="w-20 text-center py-3.5 px-3 font-bold text-xs uppercase tracking-wider border-r border-gray-200">Sifat</th>
                                        <th class="w-32 text-center py-3.5 px-3 font-bold text-xs uppercase tracking-wider border-r border-gray-200">Status</th>
                                        <th class="w-80 py-3.5 px-4 font-bold text-xs uppercase tracking-wider min-w-[240px]">Jawaban Alumni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="section in filteredProdiSections" :key="section.id">
                                        <!-- BARIS HEADER SECTION PRODI (KUNING LEMBUT, NAMA SECTION LANGSUNG) -->
                                        <tr class="bg-amber-100/75 text-amber-950 border-y border-amber-200 font-bold">
                                            <td colspan="7" class="py-3 px-4 text-xs tracking-wide">
                                                <span>{{ section.title }}</span>
                                                <span v-if="section.description" class="font-normal text-gray-700 ml-1">
                                                    &mdash; {{ section.description }}
                                                </span>
                                            </td>
                                        </tr>

                                        <!-- BARIS BUTIR SOAL PRODI -->
                                        <tr 
                                            v-for="(q, idx) in section.questions" 
                                            :key="q.id"
                                            :class="[
                                                q.is_header 
                                                    ? 'bg-amber-50/60 text-amber-950 font-bold border-b border-gray-200'
                                                    : 'bg-white hover:bg-gray-50/80 border-b border-gray-100'
                                            ]"
                                            class="transition-colors text-xs text-gray-800"
                                        >
                                            <!-- Kolom No -->
                                            <td class="text-center py-2.5 px-3 text-gray-500 font-medium border-r border-gray-100">
                                                {{ idx + 1 }}
                                            </td>

                                            <!-- Kolom Kode -->
                                            <td class="text-center py-2.5 px-3 font-mono font-semibold text-gray-700 border-r border-gray-100">
                                                {{ q.code || '-' }}
                                            </td>

                                            <!-- Kolom Pertanyaan (Jika header, span ke kanan) -->
                                            <td v-if="q.is_header" colspan="5" class="py-2.5 px-4 font-bold text-amber-950 text-xs">
                                                {{ q.question_text }}
                                            </td>

                                            <!-- Kolom Pertanyaan Biasa -->
                                            <td v-else class="py-2.5 px-4 font-medium text-gray-900 border-r border-gray-100 leading-relaxed">
                                                {{ q.question_text }}
                                            </td>

                                            <!-- Kolom Tipe -->
                                            <td v-if="!q.is_header" class="text-center py-2.5 px-3 capitalize font-normal text-gray-500 border-r border-gray-100 text-[11px]">
                                                {{ q.type || 'text' }}
                                            </td>

                                            <!-- Kolom Sifat -->
                                            <td v-if="!q.is_header" class="text-center py-2.5 px-3 border-r border-gray-100">
                                                <span v-if="q.is_mandatory" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                                    Wajib
                                                </span>
                                                <span v-else class="px-2 py-0.5 rounded text-[10px] font-normal bg-gray-50 text-gray-500">
                                                    Opsional
                                                </span>
                                            </td>

                                            <!-- Kolom Status -->
                                            <td v-if="!q.is_header" class="text-center py-2.5 px-3 border-r border-gray-100">
                                                <span v-if="q.is_answered" class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    Terjawab
                                                </span>
                                                <span v-else class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                                    Belum Dijawab
                                                </span>
                                            </td>

                                            <!-- Kolom Jawaban Alumni -->
                                            <td v-if="!q.is_header" class="py-2.5 px-4 text-gray-900 leading-relaxed">
                                                <span v-if="q.is_answered" class="text-xs font-semibold text-gray-900 whitespace-pre-line">
                                                    {{ q.answer_text }}
                                                </span>
                                                <span v-else class="text-xs text-rose-500 font-medium italic">
                                                    -
                                                </span>
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- State Kosong -->
                                    <tr v-if="filteredProdiSections.length === 0">
                                        <td colspan="7" class="py-12 text-center text-gray-400 font-medium">
                                            Tidak ada butir kuesioner prodi untuk program studi mahasiswa ini atau tidak sesuai filter pencarian.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>
</template>
