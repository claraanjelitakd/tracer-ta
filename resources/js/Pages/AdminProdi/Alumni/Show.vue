<!--
  Halaman Detail & Audit Kuesioner Alumni (Admin Program Studi)
  File: resources/js/Pages/AdminProdi/Alumni/Show.vue

  Format Tampilan (Identik dengan Super Admin):
  1. Detail Profil (Urutan 1) - FormPribadi, FormAkademik, FormOrangTua, FormKarier
  2. Kuesioner Universitas (Urutan 2, Tampilan Data Tables ala Excel)
  3. Kuesioner Program Studi: [Nama Prodi] (Urutan 3, Tampilan Data Tables ala Excel)
-->
<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import Sidebar from '../Components/Sidebar.vue';

// Mengimpor 4 Komponen Profil Alumni Lengkap
import FormPribadi from '../../Alumni/Profil/Components/FormPribadi.vue';
import FormAkademik from '../../Alumni/Profil/Components/FormAkademik.vue';
import FormOrangTua from '../../Alumni/Profil/Components/FormOrangTua.vue';
import FormKarier from '../../Alumni/Profil/Components/FormKarier.vue';

const props = defineProps({
    user: Object,
    prodi: Object,
    biodata: Object,
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
    negaras: {
        type: Array,
        default: () => [],
    },
    refOptions: {
        type: Object,
        default: () => ({}),
    },
});

// Urutan Tab Utama: 'profil' (1), 'kuesioner' (2), 'kuesioner_prodi' (3)
const activeMainTab = ref('profil');

// Sub-Tab Profil: 'pribadi', 'akademik', 'orangtua', 'karier'
const activeProfileTab = ref('pribadi');

// Form data reaktif untuk edit profil oleh Admin Program Studi
const form = useForm(JSON.parse(JSON.stringify(props.formData || {})));

// Simpan perubahan profil oleh Admin Program Studi
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
            form.post(`/prodi/alumni/${props.alumni.id}/profile`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Disimpan',
                        text: 'Data profil alumni telah berhasil diperbarui oleh Admin Program Studi.',
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

// Getter Informasi Mahasiswa
const semesterKelulusan = computed(() => {
    return props.formData.tahun_akademik_lulus || props.alumni.yudisium?.tahun_akademik_lulus || props.alumni.data_akademik?.tahun_akademik_lulus || '-';
});

const statusYudisium = computed(() => {
    return props.formData.proses_yudisium || props.alumni.yudisium?.proses_yudisium || props.alumni.yudisium?.keterangan_hasil_yudisium || 'Lulus';
});
</script>

<template>
    <Head :title="`Detail Mahasiswa - ${alumni.data_akademik?.nama || alumni.nama || alumni.nim} - ${prodi?.nama_prodi || 'Program Studi'}`" />

    <div class="min-h-screen bg-[#f8fafc] text-gray-800 font-sans flex overscroll-none">
        <!-- Sidebar Resmi Program Studi -->
        <Sidebar :user="user" :prodi="prodi" />

        <!-- Area Konten Utama -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-72 overscroll-none">
            
            <!-- Header Solid Hijau Resmi UKDW #0D542B -->
            <header class="bg-[#0D542B] text-white py-6 px-4 sm:px-6 lg:px-8 border-b border-[#0A4322]">
                <div class="max-w-[1400px] mx-auto">
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

                            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                                {{ alumni.data_akademik?.nama || alumni.user?.name || alumni.nama || 'Mahasiswa UKDW' }}
                            </h1>
                            
                            <p class="text-white/90 text-xs sm:text-sm mt-1 font-medium">
                                NIM: <span class="font-mono font-bold text-white">{{ alumni.nim }}</span> &bull; 
                                Program Studi: <span class="font-semibold text-white">{{ prodi?.nama_prodi || alumni.prodi?.nama_prodi || '-' }}</span> &bull;
                                Fakultas: <span class="font-semibold text-white">{{ prodi?.fakultas?.nama_fakultas || alumni.prodi?.fakultas?.nama_fakultas || '-' }}</span>
                            </p>
                        </div>

                        <div class="flex items-center gap-3 shrink-0 flex-wrap">
                            <a 
                                :href="`/prodi/alumni/${alumni.id}/export-excel`"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#FDC700] hover:bg-[#e5b400] text-black text-xs font-extrabold rounded-xl transition-all shadow-sm cursor-pointer"
                                title="Download seluruh butir pertanyaan & jawaban mahasiswa ini ke file Excel (.xls)"
                            >
                                <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                <span>Download Excel (.xls)</span>
                            </a>

                            <Link 
                                href="/prodi/alumni"
                                class="inline-flex items-center px-5 py-2.5 bg-white/15 hover:bg-white/25 border border-white/20 text-white text-xs font-bold rounded-xl transition-all"
                            >
                                &larr; Kembali ke Daftar
                            </Link>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
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
                                {{ evaluasi.profile?.is_complete ? 'Data profil lengkap (Biodata Pribadi, Akademik, Orang Tua, Perusahaan, & Atasan).' : `${evaluasi.profile?.missing_fields?.length || 0} butir data profil belum terisi lengkap.` }}
                            </p>
                        </div>

                        <!-- Status Kuesioner Wajib Univ -->
                        <div class="pt-4 md:pt-0 md:px-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">2. Kuesioner Universitas</span>
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
                                {{ evaluasi.questionnaire?.is_complete ? 'Seluruh butir pertanyaan wajib telah dijawab oleh alumni.' : `${(evaluasi.questionnaire?.total_mandatory || 0) - (evaluasi.questionnaire?.answered_count || 0)} pertanyaan wajib belum dijawab.` }}
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
                                    {{ prodiEvaluasi.percentage || 0 }}% Terjawab
                                </span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden mb-2">
                                <div 
                                    class="h-2 rounded-full transition-all bg-[#0D542B]"
                                    :style="{ width: `${prodiEvaluasi.percentage || 0}%` }"
                                ></div>
                            </div>
                            <p class="text-xs text-gray-500">
                                {{ prodiEvaluasi.is_complete ? 'Seluruh butir kuesioner prodi telah diisi lengkap.' : `${(prodiEvaluasi.total_questions || 0) - (prodiEvaluasi.answered_count || 0)} pertanyaan prodi belum dijawab.` }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ============================================================= -->
                <!-- TAB NAVIGASI UTAMA (URUTAN 1, 2, 3)                           -->
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

                    <!-- Tab 3: Kuesioner Program Studi (Urutan 3) -->
                    <button 
                        v-if="alumni.prodi_id"
                        @click="activeMainTab = 'kuesioner_prodi'"
                        class="py-3 px-5 text-xs sm:text-sm font-bold rounded-xl transition-all cursor-pointer flex items-center gap-2 shrink-0"
                        :class="activeMainTab === 'kuesioner_prodi' ? 'bg-[#0D542B] text-white shadow-sm' : 'bg-gray-50 hover:bg-gray-100 text-gray-600'"
                    >
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black" :class="activeMainTab === 'kuesioner_prodi' ? 'bg-white text-[#0D542B]' : 'bg-gray-200 text-gray-700'">3</span>
                        <span>Kuesioner Program Studi: {{ prodi?.nama_prodi || alumni.prodi?.nama_prodi || 'Program Studi' }}</span>
                    </button>
                </div>

                <!-- ============================================================= -->
                <!-- HALAMAN 1: DETAIL PROFIL MAHASISWA (LENGKAP 4 SUB-TAB)        -->
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

                        <!-- Tombol Simpan Profil oleh Admin Program Studi -->
                        <button 
                            @click="simpanProfilAlumni"
                            :disabled="form.processing"
                            class="px-6 py-2.5 bg-[#0D542B] hover:bg-[#08381c] text-white rounded-xl text-xs font-extrabold transition-all flex items-center justify-center cursor-pointer shadow-xs disabled:opacity-50"
                        >
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>Simpan Perubahan Profil</span>
                        </button>
                    </div>

                    <!-- Komponen Form Profil Alumni -->
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
                            :negaras="negaras"
                            :companies="companies" 
                            :alumniData="alumni" 
                            :refOptions="refOptions"
                        />
                    </div>
                </div>

                <!-- ============================================================= -->
                <!-- HALAMAN 2: KUESIONER UNIVERSITAS (TABEL EXCEL)                -->
                <!-- ============================================================= -->
                <div v-if="activeMainTab === 'kuesioner'" class="space-y-4">
                    <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-xs border border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Pilih Section / Bagian:</span>
                            <select 
                                v-model="activeSectionId"
                                class="text-xs font-bold bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-gray-800 focus:ring-2 focus:ring-[#0D542B] focus:outline-none"
                            >
                                <option value="all">Semua Section (Tampilkan Seluruh Butir)</option>
                                <option v-for="sec in sections" :key="sec.id" :value="sec.id">
                                    Seksi {{ sec.order }}: {{ sec.title }}
                                </option>
                            </select>
                        </div>

                        <div class="relative w-full md:w-80">
                            <input 
                                v-model="searchQueryUniv"
                                type="text" 
                                placeholder="Cari kode pertanyaan / isi jawaban..."
                                class="w-full text-xs bg-gray-50 border border-gray-300 rounded-xl pl-9 pr-4 py-2 text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-[#0D542B] focus:outline-none"
                            />
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-xs border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-100 border-b border-gray-200 text-[11px] font-extrabold text-gray-700 uppercase tracking-wider">
                                        <th class="py-3 px-4 w-12 text-center border-r border-gray-200">No</th>
                                        <th class="py-3 px-4 w-28 text-center border-r border-gray-200">Kode</th>
                                        <th class="py-3 px-4 border-r border-gray-200">Pertanyaan Instrumen</th>
                                        <th class="py-3 px-4 w-28 text-center border-r border-gray-200">Tipe</th>
                                        <th class="py-3 px-4 w-24 text-center border-r border-gray-200">Sifat</th>
                                        <th class="py-3 px-4 w-32 text-center border-r border-gray-200">Status</th>
                                        <th class="py-3 px-4 w-72">Jawaban Responden</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="section in filteredSections" :key="section.id">
                                        <tr class="bg-[#FDC700] text-black font-extrabold text-xs border-y-2 border-yellow-400">
                                            <td colspan="7" class="py-3 px-4">
                                                <div class="flex items-center justify-between">
                                                    <span>SEKSI {{ section.order }}: {{ section.title }}</span>
                                                    <span v-if="section.unanswered_mandatory_count > 0" class="text-[11px] font-bold px-2.5 py-0.5 bg-red-600 text-white rounded-full">
                                                        {{ section.unanswered_mandatory_count }} Wajib Belum Terisi
                                                    </span>
                                                    <span v-else class="text-[11px] font-bold px-2.5 py-0.5 bg-[#0D542B] text-white rounded-full">
                                                        Seksi Lengkap
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>

                                        <template v-for="(q, idx) in section.subpertanyaans" :key="q.id">
                                            <tr 
                                                v-if="q.is_header"
                                                class="bg-[#FEF08A] text-yellow-950 font-bold text-xs border-b border-yellow-200"
                                            >
                                                <td class="py-2.5 px-4 text-center border-r border-yellow-200 text-gray-500 font-mono">{{ idx + 1 }}</td>
                                                <td class="py-2.5 px-4 text-center border-r border-yellow-200 font-mono font-bold">{{ q.kode_pertanyaan }}</td>
                                                <td colspan="5" class="py-2.5 px-4 uppercase tracking-wide">
                                                    {{ q.subpertanyaan }}
                                                </td>
                                            </tr>

                                            <tr 
                                                v-else
                                                class="border-b border-gray-200 text-xs transition-colors"
                                                :class="q.is_answered ? 'bg-white hover:bg-gray-50' : 'bg-[#FFF1F2] hover:bg-rose-100/60'"
                                            >
                                                <td class="py-3 px-4 text-center border-r border-gray-200 text-gray-500 font-mono">{{ idx + 1 }}</td>
                                                <td class="py-3 px-4 text-center border-r border-gray-200 font-mono font-bold text-[#0D542B]">{{ q.kode_pertanyaan }}</td>
                                                <td class="py-3 px-4 border-r border-gray-200 font-medium text-gray-900 leading-relaxed">{{ q.subpertanyaan }}</td>
                                                <td class="py-3 px-4 text-center border-r border-gray-200 text-gray-500 font-mono text-[11px]">{{ q.type }}</td>
                                                <td class="py-3 px-4 text-center border-r border-gray-200">
                                                    <span 
                                                        class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase"
                                                        :class="q.is_mandatory ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'"
                                                    >
                                                        {{ q.is_mandatory ? 'Wajib' : 'Opsional' }}
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4 text-center border-r border-gray-200">
                                                    <span 
                                                        v-if="q.is_answered" 
                                                        class="font-black text-sm text-[#0D542B]"
                                                        title="Terjawab"
                                                    >
                                                        v
                                                    </span>
                                                    <span 
                                                        v-else 
                                                        class="font-black text-sm text-red-600"
                                                        title="Belum Dijawab"
                                                    >
                                                        x
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4 font-semibold" :class="q.is_answered ? 'text-gray-900' : 'text-rose-500 italic'">
                                                    {{ q.is_answered ? q.answer : '— Belum diisi —' }}
                                                </td>
                                            </tr>
                                        </template>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ============================================================= -->
                <!-- HALAMAN 3: KUESIONER PROGRAM STUDI (TABEL EXCEL)              -->
                <!-- ============================================================= -->
                <div v-if="activeMainTab === 'kuesioner_prodi'" class="space-y-4">
                    <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-xs border border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Pilih Section Prodi:</span>
                            <select 
                                v-model="activeProdiSectionId"
                                class="text-xs font-bold bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-gray-800 focus:ring-2 focus:ring-[#0D542B] focus:outline-none"
                            >
                                <option value="all">Semua Section Prodi (Tampilkan Seluruh Butir)</option>
                                <option v-for="sec in prodiSections" :key="sec.id" :value="sec.id">
                                    Seksi {{ sec.order }}: {{ sec.title }}
                                </option>
                            </select>
                        </div>

                        <div class="relative w-full md:w-80">
                            <input 
                                v-model="searchQueryProdi"
                                type="text" 
                                placeholder="Cari kode pertanyaan / isi jawaban prodi..."
                                class="w-full text-xs bg-gray-50 border border-gray-300 rounded-xl pl-9 pr-4 py-2 text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-[#0D542B] focus:outline-none"
                            />
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-xs border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-100 border-b border-gray-200 text-[11px] font-extrabold text-gray-700 uppercase tracking-wider">
                                        <th class="py-3 px-4 w-12 text-center border-r border-gray-200">No</th>
                                        <th class="py-3 px-4 w-28 text-center border-r border-gray-200">Kode</th>
                                        <th class="py-3 px-4 border-r border-gray-200">Pertanyaan Khusus Program Studi</th>
                                        <th class="py-3 px-4 w-28 text-center border-r border-gray-200">Tipe</th>
                                        <th class="py-3 px-4 w-24 text-center border-r border-gray-200">Sifat</th>
                                        <th class="py-3 px-4 w-32 text-center border-r border-gray-200">Status</th>
                                        <th class="py-3 px-4 w-72">Jawaban Responden</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="section in filteredProdiSections" :key="section.id">
                                        <tr class="bg-[#FDC700] text-black font-extrabold text-xs border-y-2 border-yellow-400">
                                            <td colspan="7" class="py-3 px-4">
                                                <div class="flex items-center justify-between">
                                                    <span>SEKSI {{ section.order }}: {{ section.title }}</span>
                                                    <span v-if="section.unanswered_mandatory_count > 0" class="text-[11px] font-bold px-2.5 py-0.5 bg-red-600 text-white rounded-full">
                                                        {{ section.unanswered_mandatory_count }} Belum Dijawab
                                                    </span>
                                                    <span v-else class="text-[11px] font-bold px-2.5 py-0.5 bg-[#0D542B] text-white rounded-full">
                                                        Seksi Lengkap
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>

                                        <template v-for="(q, idx) in section.questions" :key="q.id">
                                            <tr 
                                                v-if="q.is_header"
                                                class="bg-[#FEF08A] text-yellow-950 font-bold text-xs border-b border-yellow-200"
                                            >
                                                <td class="py-2.5 px-4 text-center border-r border-yellow-200 text-gray-500 font-mono">{{ idx + 1 }}</td>
                                                <td class="py-2.5 px-4 text-center border-r border-yellow-200 font-mono font-bold">{{ q.code || `P${q.id}` }}</td>
                                                <td colspan="5" class="py-2.5 px-4 uppercase tracking-wide">
                                                    {{ q.question_text }}
                                                </td>
                                            </tr>

                                            <tr 
                                                v-else
                                                class="border-b border-gray-200 text-xs transition-colors"
                                                :class="q.is_answered ? 'bg-white hover:bg-gray-50' : 'bg-[#FFF1F2] hover:bg-rose-100/60'"
                                            >
                                                <td class="py-3 px-4 text-center border-r border-gray-200 text-gray-500 font-mono">{{ idx + 1 }}</td>
                                                <td class="py-3 px-4 text-center border-r border-gray-200 font-mono font-bold text-[#0D542B]">{{ q.code || `P${q.id}` }}</td>
                                                <td class="py-3 px-4 border-r border-gray-200 font-medium text-gray-900 leading-relaxed">{{ q.question_text }}</td>
                                                <td class="py-3 px-4 text-center border-r border-gray-200 text-gray-500 font-mono text-[11px]">{{ q.type }}</td>
                                                <td class="py-3 px-4 text-center border-r border-gray-200">
                                                    <span 
                                                        class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase"
                                                        :class="q.is_mandatory ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'"
                                                    >
                                                        {{ q.is_mandatory ? 'Wajib' : 'Opsional' }}
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4 text-center border-r border-gray-200">
                                                    <span 
                                                        v-if="q.is_answered" 
                                                        class="font-black text-sm text-[#0D542B]"
                                                        title="Terjawab"
                                                    >
                                                        v
                                                    </span>
                                                    <span 
                                                        v-else 
                                                        class="font-black text-sm text-red-600"
                                                        title="Belum Dijawab"
                                                    >
                                                        x
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4 font-semibold" :class="q.is_answered ? 'text-gray-900' : 'text-rose-500 italic'">
                                                    {{ q.is_answered ? q.answer_text : '— Belum diisi —' }}
                                                </td>
                                            </tr>
                                        </template>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>
</template>
