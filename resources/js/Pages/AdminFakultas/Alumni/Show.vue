<!--
  Halaman Detail & Audit Kuesioner Alumni (Admin Fakultas)
  File: resources/js/Pages/AdminFakultas/Alumni/Show.vue

  Warna Resmi Solid UKDW:
  - Hijau: #0D542B
  - Kuning: #FDC700
  - Putih & Netral: #FFFFFF / #F8FAFC
  Struktur: 3 Tab Utama (1. Detail Profile, 2. Kuesioner Univ, 3. Kuesioner Program Studi)
-->
<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Sidebar from '../Components/Sidebar.vue';

const props = defineProps({
    user: Object,
    fakultas: Object,
    biodata: Object,
    alumni: Object,
    evaluasi: Object,
    sections: Array,
    prodiSections: Array,
    prodiEvaluasi: Object,
    formData: Object,
});

// Tab Aktif: 'profile' (1), 'univ' (2), 'prodi' (3)
const activeTab = ref('profile');

// Filter Dropdown Seksi
const selectedUnivSectionId = ref('all');
const selectedProdiSectionId = ref('all');

// Filtered Sections
const filteredUnivSections = computed(() => {
    if (selectedUnivSectionId.value === 'all') {
        return props.sections || [];
    }
    return (props.sections || []).filter(s => String(s.id) === String(selectedUnivSectionId.value));
});

const filteredProdiSections = computed(() => {
    if (selectedProdiSectionId.value === 'all') {
        return props.prodiSections || [];
    }
    return (props.prodiSections || []).filter(s => String(s.id) === String(selectedProdiSectionId.value));
});
</script>

<template>
    <Head :title="`Detail Mahasiswa - ${biodata.nama || biodata.nim} - ${fakultas?.nama_fakultas || 'Fakultas'}`" />

    <div class="min-h-screen bg-[#f8fafc] text-gray-800 font-sans flex">
        <!-- Sidebar Resmi Fakultas -->
        <Sidebar :user="user" :fakultas="fakultas" />

        <!-- Area Konten Utama -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-72">
            
            <!-- Header Solid Hijau Resmi UKDW #0D542B -->
            <header class="bg-[#0D542B] text-white pt-8 pb-16 px-4 sm:px-6 lg:px-8">
                <div class="w-full max-w-[1400px] mx-auto flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2 text-xs text-white/80 font-medium mb-2">
                            <Link href="/fakultas/dashboard" class="hover:underline">Dashboard</Link>
                            <span>/</span>
                            <Link href="/fakultas/alumni" class="hover:underline">Data Alumni</Link>
                            <span>/</span>
                            <span class="text-white font-bold">Detail Mahasiswa</span>
                        </div>

                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                                {{ biodata.nama || 'Mahasiswa UKDW' }}
                            </h1>
                            <span 
                                class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider"
                                :class="evaluasi?.is_complete ? 'bg-white text-[#0D542B]' : 'bg-[#FDC700] text-black'"
                            >
                                {{ evaluasi?.is_complete ? 'Selesai' : 'Belum Selesai' }}
                            </span>
                        </div>

                        <p class="text-white/90 text-xs sm:text-sm font-normal mt-1">
                            NIM: <span class="font-mono font-bold">{{ biodata.nim }}</span> &bull; 
                            Prodi: <span class="font-bold">{{ biodata.prodi?.nama_prodi || '-' }}</span> &bull; 
                            Fakultas: <span class="font-bold">{{ fakultas?.nama_fakultas || biodata.prodi?.fakultas?.nama_fakultas || '-' }}</span>
                        </p>
                    </div>

                    <!-- Tombol Aksi Header -->
                    <div class="flex flex-wrap items-center gap-3 shrink-0">
                        <a 
                            :href="`/fakultas/alumni/${biodata.id}/export-excel`"
                            target="_blank"
                            class="px-4 py-2.5 bg-[#FDC700] hover:bg-[#e5b500] text-black text-xs font-bold rounded-xl transition-all shadow-sm flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>Download Excel (.CSV)</span>
                        </a>

                        <Link 
                            href="/fakultas/alumni" 
                            class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white text-xs font-bold rounded-xl transition-all border border-white/20"
                        >
                            &larr; Kembali
                        </Link>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="w-full max-w-[1400px] mx-auto -mt-10 px-4 sm:px-6 lg:px-8 space-y-6 pb-16">
                
                <!-- 3 TAB NAVIGASI UTAMA -->
                <div class="bg-white rounded-2xl shadow-sm p-2 flex flex-wrap gap-2 border border-gray-100">
                    <button
                        type="button"
                        @click="activeTab = 'profile'"
                        class="flex-1 min-w-[180px] py-3 px-4 rounded-xl text-xs font-extrabold transition-all text-center flex items-center justify-center gap-2 cursor-pointer"
                        :class="activeTab === 'profile' ? 'bg-[#0D542B] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                    >
                        <span>1. Detail Profile Mahasiswa</span>
                        <span 
                            class="px-2 py-0.5 rounded-full text-[10px]"
                            :class="activeTab === 'profile' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"
                        >
                            {{ evaluasi?.profile?.is_complete ? '100%' : '50%' }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'univ'"
                        class="flex-1 min-w-[180px] py-3 px-4 rounded-xl text-xs font-extrabold transition-all text-center flex items-center justify-center gap-2 cursor-pointer"
                        :class="activeTab === 'univ' ? 'bg-[#0D542B] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                    >
                        <span>2. Kuesioner Universitas (Tracer Study)</span>
                        <span 
                            class="px-2 py-0.5 rounded-full text-[10px]"
                            :class="activeTab === 'univ' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"
                        >
                            {{ evaluasi?.questionnaire?.percentage || 0 }}%
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'prodi'"
                        class="flex-1 min-w-[180px] py-3 px-4 rounded-xl text-xs font-extrabold transition-all text-center flex items-center justify-center gap-2 cursor-pointer"
                        :class="activeTab === 'prodi' ? 'bg-[#0D542B] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                    >
                        <span>3. Kuesioner Program Studi: {{ biodata.prodi?.nama_prodi || 'Prodi' }}</span>
                        <span 
                            class="px-2 py-0.5 rounded-full text-[10px]"
                            :class="activeTab === 'prodi' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"
                        >
                            {{ prodiEvaluasi?.percentage || 0 }}%
                        </span>
                    </button>
                </div>

                <!-- ============================================================= -->
                <!-- TAB 1: DETAIL PROFILE                                         -->
                <!-- ============================================================= -->
                <div v-if="activeTab === 'profile'" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Data Pribadi & Kontak -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <h3 class="text-sm font-extrabold text-gray-900 border-b border-gray-100 pb-3 mb-4">
                                Data Pribadi & Kontak
                            </h3>
                            <dl class="divide-y divide-gray-100 text-xs">
                                <div class="py-2.5 grid grid-cols-3">
                                    <dt class="text-gray-500 font-semibold">Nama Lengkap</dt>
                                    <dd class="col-span-2 font-bold text-gray-900">{{ formData.nama || '-' }}</dd>
                                </div>
                                <div class="py-2.5 grid grid-cols-3">
                                    <dt class="text-gray-500 font-semibold">NIM</dt>
                                    <dd class="col-span-2 font-mono font-bold text-gray-900">{{ formData.nim || '-' }}</dd>
                                </div>
                                <div class="py-2.5 grid grid-cols-3">
                                    <dt class="text-gray-500 font-semibold">Email Pribadi</dt>
                                    <dd class="col-span-2 text-gray-900">{{ formData.email_pribadi || '-' }}</dd>
                                </div>
                                <div class="py-2.5 grid grid-cols-3">
                                    <dt class="text-gray-500 font-semibold">Nomor Telepon / WA</dt>
                                    <dd class="col-span-2 text-gray-900">{{ formData.nomor_telepon || '-' }}</dd>
                                </div>
                                <div class="py-2.5 grid grid-cols-3">
                                    <dt class="text-gray-500 font-semibold">Alamat Saat Ini</dt>
                                    <dd class="col-span-2 text-gray-900">{{ formData.alamat_saat_ini || '-' }}</dd>
                                </div>
                                <div class="py-2.5 grid grid-cols-3">
                                    <dt class="text-gray-500 font-semibold">LinkedIn</dt>
                                    <dd class="col-span-2 font-mono text-gray-900">{{ formData.linkedin_username || formData.linkedin_url || '-' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Data Akademik & Yudisium -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <h3 class="text-sm font-extrabold text-gray-900 border-b border-gray-100 pb-3 mb-4">
                                Rekam Jejak Akademik & Yudisium
                            </h3>
                            <dl class="divide-y divide-gray-100 text-xs">
                                <div class="py-2.5 grid grid-cols-3">
                                    <dt class="text-gray-500 font-semibold">Program Studi</dt>
                                    <dd class="col-span-2 font-bold text-gray-900">{{ biodata.prodi?.nama_prodi || '-' }}</dd>
                                </div>
                                <div class="py-2.5 grid grid-cols-3">
                                    <dt class="text-gray-500 font-semibold">Fakultas</dt>
                                    <dd class="col-span-2 font-bold text-[#0D542B]">{{ fakultas?.nama_fakultas || biodata.prodi?.fakultas?.nama_fakultas || '-' }}</dd>
                                </div>
                                <div class="py-2.5 grid grid-cols-3">
                                    <dt class="text-gray-500 font-semibold">IPK Kelulusan</dt>
                                    <dd class="col-span-2 font-mono font-bold text-gray-900">{{ formData.ip_kumulatif || '-' }}</dd>
                                </div>
                                <div class="py-2.5 grid grid-cols-3">
                                    <dt class="text-gray-500 font-semibold">Semester Lulus</dt>
                                    <dd class="col-span-2 font-bold text-[#0D542B]">{{ formData.tahun_akademik_lulus || '-' }}</dd>
                                </div>
                                <div class="py-2.5 grid grid-cols-3">
                                    <dt class="text-gray-500 font-semibold">Status Yudisium</dt>
                                    <dd class="col-span-2">
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-green-100 text-green-800">
                                            {{ formData.proses_yudisium || 'Lulus' }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="py-2.5 grid grid-cols-3">
                                    <dt class="text-gray-500 font-semibold">Judul Tugas Akhir</dt>
                                    <dd class="col-span-2 text-gray-700 italic">{{ formData.judul_ta || '-' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Data Pekerjaan & Perusahaan -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 lg:col-span-2">
                            <h3 class="text-sm font-extrabold text-gray-900 border-b border-gray-100 pb-3 mb-4">
                                Informasi Pekerjaan & Instansi Terkini
                            </h3>
                            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                                <div class="p-3 bg-gray-50 rounded-xl">
                                    <dt class="text-gray-500 font-semibold mb-1">Nama Perusahaan / Kantor</dt>
                                    <dd class="font-bold text-gray-900 text-sm">{{ formData.nama_perusahaan || 'Belum Terisi' }}</dd>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-xl">
                                    <dt class="text-gray-500 font-semibold mb-1">Posisi / Jabatan</dt>
                                    <dd class="font-bold text-gray-900 text-sm">{{ formData.posisi_jabatan || 'Belum Terisi' }}</dd>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-xl">
                                    <dt class="text-gray-500 font-semibold mb-1">Bidang / Keahlian</dt>
                                    <dd class="font-bold text-gray-900 text-sm">{{ formData.expert || 'Belum Terisi' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- ============================================================= -->
                <!-- TAB 2: KUESIONER UNIVERSITAS (TRACER STUDY)                   -->
                <!-- ============================================================= -->
                <div v-if="activeTab === 'univ'" class="space-y-6">
                    <!-- Dropdown Filter Section Univ -->
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Filter Bagian / Seksi:</span>
                            <select 
                                v-model="selectedUnivSectionId" 
                                class="text-xs rounded-xl border border-gray-200 bg-gray-50 py-2 px-3 font-bold text-gray-900 focus:bg-white focus:border-[#0D542B] outline-none"
                            >
                                <option value="all">Semua Bagian (Tampilkan Seluruhnya)</option>
                                <option v-for="sec in sections" :key="sec.id" :value="sec.id">
                                    Seksi {{ sec.order }}: {{ sec.title }} ({{ sec.unanswered_mandatory_count }} Belum Dijawab)
                                </option>
                            </select>
                        </div>

                        <div class="text-xs font-bold text-gray-500">
                            Total: <span class="text-[#0D542B]">{{ sections.length }}</span> Seksi Kuesioner Universitas
                        </div>
                    </div>

                    <!-- Tabel Jawaban per Section ala Excel -->
                    <div v-for="sec in filteredUnivSections" :key="sec.id" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <!-- Header Seksi Kuning Standar Excel #FDC700 -->
                        <div class="bg-[#FDC700] text-gray-950 px-6 py-3.5 flex items-center justify-between font-black text-xs uppercase tracking-wider">
                            <span>Seksi {{ sec.order }}: {{ sec.title }}</span>
                            <span v-if="sec.unanswered_mandatory_count > 0" class="px-2.5 py-0.5 bg-rose-600 text-white rounded-full text-[10px] font-bold">
                                {{ sec.unanswered_mandatory_count }} Wajib Belum Dijawab
                            </span>
                            <span v-else class="px-2.5 py-0.5 bg-[#0D542B] text-white rounded-full text-[10px] font-bold">
                                Lengkap
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200 uppercase tracking-wider text-[11px]">
                                    <tr>
                                        <th class="py-3 px-4 w-12 text-center">No</th>
                                        <th class="py-3 px-4 w-28">Kode</th>
                                        <th class="py-3 px-4">Pertanyaan / Instrumen</th>
                                        <th class="py-3 px-4 w-24 text-center">Sifat</th>
                                        <th class="py-3 px-4 w-32 text-center">Status</th>
                                        <th class="py-3 px-4 w-1/3">Jawaban / Respon Alumni</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr 
                                        v-for="(sub, sIdx) in sec.subpertanyaans" 
                                        :key="sub.id"
                                        :class="[
                                            sub.is_header ? 'bg-amber-50/70 font-bold text-amber-950' : 'hover:bg-gray-50/70'
                                        ]"
                                    >
                                        <td class="py-3 px-4 text-center font-mono text-gray-400">
                                            {{ sIdx + 1 }}
                                        </td>
                                        <td class="py-3 px-4 font-mono font-bold" :class="sub.is_header ? 'text-amber-900' : 'text-[#0D542B]'">
                                            {{ sub.kode_pertanyaan }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <span :class="sub.is_header ? 'font-extrabold text-xs text-amber-950' : 'text-gray-800'">
                                                {{ sub.subpertanyaan }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span v-if="sub.is_header" class="text-gray-400">-</span>
                                            <span v-else-if="sub.is_mandatory" class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                Wajib
                                            </span>
                                            <span v-else class="px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">
                                                Opsional
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span v-if="sub.is_header" class="text-amber-800 font-bold text-[10px]">Header</span>
                                            <span v-else-if="sub.is_answered" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-800">
                                                Terjawab
                                            </span>
                                            <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                                Belum Dijawab
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span v-if="sub.is_header" class="text-gray-400 italic">(Header Bagian)</span>
                                            <span v-else-if="sub.is_answered" class="font-bold text-gray-900 bg-gray-50 px-2 py-1 rounded block border border-gray-100">
                                                {{ sub.answer }}
                                            </span>
                                            <span v-else class="text-rose-500 font-medium italic">
                                                (Belum diisi oleh alumni)
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ============================================================= -->
                <!-- TAB 3: KUESIONER PROGRAM STUDI                                -->
                <!-- ============================================================= -->
                <div v-if="activeTab === 'prodi'" class="space-y-6">
                    <!-- Dropdown Filter Section Prodi -->
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Filter Bagian Prodi:</span>
                            <select 
                                v-model="selectedProdiSectionId" 
                                class="text-xs rounded-xl border border-gray-200 bg-gray-50 py-2 px-3 font-bold text-gray-900 focus:bg-white focus:border-[#0D542B] outline-none"
                            >
                                <option value="all">Semua Bagian (Tampilkan Seluruhnya)</option>
                                <option v-for="sec in prodiSections" :key="sec.id" :value="sec.id">
                                    {{ sec.title }} ({{ sec.unanswered_mandatory_count }} Belum Dijawab)
                                </option>
                            </select>
                        </div>

                        <div class="text-xs font-bold text-gray-500">
                            Program Studi: <span class="text-[#0D542B] font-extrabold">{{ biodata.prodi?.nama_prodi || '-' }}</span>
                        </div>
                    </div>

                    <!-- State Kosong jika prodi belum memiliki kuesioner -->
                    <div v-if="prodiSections.length === 0" class="bg-white rounded-2xl p-12 text-center text-gray-400 border border-gray-100">
                        <p class="font-bold text-gray-800 text-sm">Belum Ada Kuesioner Khusus untuk Program Studi Ini</p>
                        <p class="text-xs text-gray-500 mt-1">Admin Program Studi belum mempublikasikan instrumen kuesioner internal.</p>
                    </div>

                    <!-- Tabel Jawaban per Section Prodi ala Excel -->
                    <div v-for="sec in filteredProdiSections" :key="sec.id" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <!-- Header Seksi Kuning Standar Excel #FDC700 -->
                        <div class="bg-[#FDC700] text-gray-950 px-6 py-3.5 flex items-center justify-between font-black text-xs uppercase tracking-wider">
                            <span>{{ sec.title }}</span>
                            <span v-if="sec.unanswered_mandatory_count > 0" class="px-2.5 py-0.5 bg-rose-600 text-white rounded-full text-[10px] font-bold">
                                {{ sec.unanswered_mandatory_count }} Wajib Belum Dijawab
                            </span>
                            <span v-else class="px-2.5 py-0.5 bg-[#0D542B] text-white rounded-full text-[10px] font-bold">
                                Lengkap
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200 uppercase tracking-wider text-[11px]">
                                    <tr>
                                        <th class="py-3 px-4 w-12 text-center">No</th>
                                        <th class="py-3 px-4 w-28">Kode</th>
                                        <th class="py-3 px-4">Pertanyaan / Instrumen Prodi</th>
                                        <th class="py-3 px-4 w-24 text-center">Sifat</th>
                                        <th class="py-3 px-4 w-32 text-center">Status</th>
                                        <th class="py-3 px-4 w-1/3">Jawaban / Respon Alumni</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr 
                                        v-for="(q, qIdx) in sec.questions" 
                                        :key="q.id"
                                        :class="[
                                            q.is_header ? 'bg-amber-50/70 font-bold text-amber-950' : 'hover:bg-gray-50/70'
                                        ]"
                                    >
                                        <td class="py-3 px-4 text-center font-mono text-gray-400">
                                            {{ qIdx + 1 }}
                                        </td>
                                        <td class="py-3 px-4 font-mono font-bold" :class="q.is_header ? 'text-amber-900' : 'text-[#0D542B]'">
                                            {{ q.code || '-' }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <span :class="q.is_header ? 'font-extrabold text-xs text-amber-950' : 'text-gray-800'">
                                                {{ q.question_text }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span v-if="q.is_header" class="text-gray-400">-</span>
                                            <span v-else-if="q.is_mandatory" class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                Wajib
                                            </span>
                                            <span v-else class="px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">
                                                Opsional
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span v-if="q.is_header" class="text-amber-800 font-bold text-[10px]">Header</span>
                                            <span v-else-if="q.is_answered" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-800">
                                                Terjawab
                                            </span>
                                            <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                                Belum Dijawab
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span v-if="q.is_header" class="text-gray-400 italic">(Header Bagian)</span>
                                            <span v-else-if="q.is_answered" class="font-bold text-gray-900 bg-gray-50 px-2 py-1 rounded block border border-gray-100">
                                                {{ q.answer_text }}
                                            </span>
                                            <span v-else class="text-rose-500 font-medium italic">
                                                (Belum diisi oleh alumni)
                                            </span>
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
