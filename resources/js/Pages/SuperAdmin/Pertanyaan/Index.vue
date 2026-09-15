<!--
  Halaman Utama Kelola Pertanyaan & Alur Percabangan Kuesioner (Superadmin)
  
  Fungsi:
  Portal kendali terpusat bagi Superadmin untuk mengelola seluruh instrumen kuesioner Tracer Study.
  Desain mengikuti estetika bersih, profesional, dan modern seperti pada modul Profil Alumni.
-->
<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Swal from 'sweetalert2';

import Navbar from '@/Pages/SuperAdmin/Components/Navbar.vue';
import SectionTabs from './Components/SectionTabs.vue';
import QuestionCard from './Components/QuestionCard.vue';
import QuestionModal from './Components/QuestionModal.vue';
import OptionModal from './Components/OptionModal.vue';

// Properti yang dikirimkan oleh SuperAdmin\KelolaPertanyaan\DaftarPertanyaanController
const props = defineProps({
    subpertanyaans: {
        type: Array,
        default: () => [],
    },
    sections: {
        type: Array,
        default: () => [],
    },
    prodis: {
        type: Array,
        default: () => [],
    },
    availableJumpTargets: {
        type: Array,
        default: () => [],
    },
    targetQuestionMap: {
        type: Object,
        default: () => ({}),
    },
});

// ========================================================
// 1. STATE NAVIGASI SECTION & SINKRONISASI URL
// ========================================================
const SUPERADMIN_SECTION_STORAGE_KEY = 'tracerstudy_superadmin_pertanyaan_section';

const getInitialSectionId = () => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        const secParam = urlParams.get('sec_id');
        if (secParam) {
            const parsed = parseInt(secParam, 10);
            if (props.sections.some((s) => s.id === parsed)) return parsed;
        }
        const cached = localStorage.getItem(SUPERADMIN_SECTION_STORAGE_KEY);
        if (cached) {
            const parsed = parseInt(cached, 10);
            if (props.sections.some((s) => s.id === parsed)) return parsed;
        }
    }
    return props.sections.length > 0 ? props.sections[0].id : null;
};

const activeSectionId = ref(getInitialSectionId());

watch(activeSectionId, (newId) => {
    if (typeof window !== 'undefined' && newId) {
        localStorage.setItem(SUPERADMIN_SECTION_STORAGE_KEY, newId.toString());
        const url = new URL(window.location.href);
        url.searchParams.set('sec_id', newId.toString());
        window.history.replaceState({}, '', url.toString());
    }
});

const handleSelectSection = (sectionId) => {
    activeSectionId.value = sectionId;
};

// Objek data section yang sedang aktif
const currentActiveSection = computed(() => {
    return props.sections.find((s) => s.id === activeSectionId.value) || null;
});

// Filter pencarian teks atau kode pertanyaan
const searchQuery = ref('');
const isReordering = ref(false);

// Pertanyaan yang termasuk di dalam section aktif dan lolos filter pencarian
const activeSectionQuestions = computed(() => {
    if (!activeSectionId.value) return [];

    return props.subpertanyaans
        .filter((q) => q.kelompok_pertanyaan_id === activeSectionId.value)
        .filter((q) => {
            if (!searchQuery.value.trim()) return true;
            const query = searchQuery.value.toLowerCase();
            const matchCode = q.kode_pertanyaan?.toLowerCase().includes(query);
            const matchText = q.subpertanyaan?.toLowerCase().includes(query);
            return matchCode || matchText;
        })
        .sort((a, b) => a.order - b.order);
});

// ========================================================
// 1.1 LOGIKA KHUSUS SECTION F17 (DUAL MATRIX PAIRING)
// ========================================================
// Deteksi khusus apakah section yang sedang aktif memuat pertanyaan F17
const isF17Section = computed(() => {
    return activeSectionQuestions.value.some((q) => q.kode_pertanyaan && q.kode_pertanyaan.startsWith('F17-'));
});

// Mode tampilan pada Section F17: 'paired' (berpasangan) atau 'flat' (kartu individual)
const f17ViewMode = ref('paired');

// Helper untuk mengekstrak nama aspek bersih tanpa suffix jenis kolom
const getCleanAspectName = (text) => {
    if (!text) return '';
    if (text.includes('—')) return text.split('—')[0].trim();
    if (text.includes('-')) return text.split('-')[0].trim();
    return text;
};

// Menghitung nomor order berikutnya secara global kuesioner agar pertanyaan baru berada di urutan paling akhir
const calculatedNextOrder = computed(() => {
    if (!props.subpertanyaans || props.subpertanyaans.length === 0) return 1;
    const maxOrder = Math.max(...props.subpertanyaans.map((q) => q.order || 0));
    return maxOrder + 1;
});

// Menggabungkan pertanyaan F17 menjadi pasangan ganjil & genap (A vs B) berdasarkan nomor kode numerik F17
const f17AspectPairs = computed(() => {
    if (!isF17Section.value) return [];
    const questions = activeSectionQuestions.value;

    // 1. Petakan pertanyaan F17 berdasarkan nomor urut numerik di kodenya (misal F17-55 => 55)
    const questionByNum = {};
    const unnumberedQuestions = [];

    questions.forEach((q) => {
        const match = q.kode_pertanyaan ? q.kode_pertanyaan.match(/^F17-(\d+)$/i) : null;
        if (match) {
            const num = parseInt(match[1], 10);
            questionByNum[num] = q;
        } else {
            unnumberedQuestions.push(q);
        }
    });

    // 2. Kumpulkan semua nomor aspek unik: Aspek = Math.ceil(Nomor / 2)
    const aspectIndices = new Set();
    Object.keys(questionByNum).forEach((numStr) => {
        const num = parseInt(numStr, 10);
        const aspectIdx = Math.ceil(num / 2);
        aspectIndices.add(aspectIdx);
    });

    // 3. Susun pasangan berdasarkan nomor aspek yang terurut secara numerik
    const sortedAspectIndices = Array.from(aspectIndices).sort((a, b) => a - b);
    const pairs = [];

    sortedAspectIndices.forEach((aspectNum) => {
        const numA = (aspectNum - 1) * 2 + 1;
        const numB = (aspectNum - 1) * 2 + 2;

        const qA = questionByNum[numA] || null;
        const qB = questionByNum[numB] || null;

        const aspectName = qA 
            ? getCleanAspectName(qA.subpertanyaan) 
            : (qB ? getCleanAspectName(qB.subpertanyaan) : `Aspek #${aspectNum}`);

        pairs.push({
            aspectNumber: aspectNum,
            aspectName,
            expectedCodeA: `F17-${numA}`,
            expectedCodeB: `F17-${numB}`,
            qA,
            qB,
        });
    });

    // 4. Jika ada butir yang tidak berformat F17-angka, masukkan di akhir
    for (let i = 0; i < unnumberedQuestions.length; i += 2) {
        pairs.push({
            aspectNumber: pairs.length + 1,
            aspectName: getCleanAspectName(unnumberedQuestions[i].subpertanyaan),
            expectedCodeA: unnumberedQuestions[i].kode_pertanyaan,
            expectedCodeB: unnumberedQuestions[i + 1]?.kode_pertanyaan || null,
            qA: unnumberedQuestions[i],
            qB: unnumberedQuestions[i + 1] || null,
        });
    }

    return pairs;
});

// ========================================================
// 2. MODAL PERTANYAAN (TAMBAH & EDIT)
// ========================================================
const showQuestionModal = ref(false);
const isEditingQuestion = ref(false);
const selectedQuestion = ref(null);

const openAddQuestionModal = () => {
    isEditingQuestion.value = false;
    selectedQuestion.value = null;
    showQuestionModal.value = true;
};

const openEditQuestionModal = (q) => {
    isEditingQuestion.value = true;
    selectedQuestion.value = q;
    showQuestionModal.value = true;
};

const closeQuestionModal = () => {
    showQuestionModal.value = false;
    selectedQuestion.value = null;
};

// Hapus butir pertanyaan via SweetAlert2
const handleDeleteQuestion = (q) => {
    Swal.fire({
        title: 'Hapus Pertanyaan?',
        html: `
            <div class="text-center space-y-2">
                <p class="text-sm text-gray-600">Apakah Anda yakin ingin menghapus butir pertanyaan:</p>
                <div class="inline-block px-3 py-1 rounded-lg bg-red-50 text-red-700 font-mono font-bold text-sm border border-red-200">
                    ${q.kode_pertanyaan} — ${q.subpertanyaan?.substring(0, 50)}...
                </div>
                <p class="text-xs text-red-500 font-medium">Seluruh opsi jawaban terkait juga akan terhapus.</p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/superadmin/pertanyaan/${q.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: `Pertanyaan ${q.kode_pertanyaan} telah dihapus.`,
                        icon: 'success',
                        confirmButtonColor: '#005B3C',
                        confirmButtonText: 'Tutup',
                    });
                },
                onError: () => {
                    Swal.fire({
                        title: 'Gagal Menghapus!',
                        text: 'Terjadi kesalahan pada sistem saat menghapus data.',
                        icon: 'error',
                        confirmButtonColor: '#d33',
                    });
                },
            });
        }
    });
};

// Pindah urutan pertanyaan naik / turun
const handleMoveQuestion = (q, direction) => {
    isReordering.value = true;
    router.post(
        '/superadmin/pertanyaan/reorder',
        {
            id: q.id,
            direction: direction,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isReordering.value = false;
            },
        }
    );
};

// ========================================================
// 3. MODAL OPSI JAWABAN (TAMBAH & EDIT)
// ========================================================
const showOptionModal = ref(false);
const isEditingOption = ref(false);
const selectedQuestionForOption = ref(null);
const selectedOption = ref(null);

const openAddOptionModal = (q) => {
    selectedQuestionForOption.value = q;
    isEditingOption.value = false;
    selectedOption.value = null;
    showOptionModal.value = true;
};

const openEditOptionModal = (q, opt) => {
    selectedQuestionForOption.value = q;
    isEditingOption.value = true;
    selectedOption.value = opt;
    showOptionModal.value = true;
};

const closeOptionModal = () => {
    showOptionModal.value = false;
    selectedQuestionForOption.value = null;
    selectedOption.value = null;
};

// Hapus opsi jawaban via SweetAlert2
const handleDeleteOption = (opt) => {
    Swal.fire({
        title: 'Hapus Opsi Jawaban?',
        text: `Hapus opsi "${opt.option_text}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/superadmin/pertanyaan/options/${opt.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Pilihan opsi jawaban telah dihapus.',
                        icon: 'success',
                        confirmButtonColor: '#005B3C',
                        confirmButtonText: 'Tutup',
                    });
                },
                onError: () => {
                    Swal.fire({
                        title: 'Gagal Menghapus!',
                        text: 'Terjadi kendala saat menghapus opsi jawaban.',
                        icon: 'error',
                        confirmButtonColor: '#d33',
                    });
                },
            });
        }
    });
};
</script>

<template>
    <Head title="Kelola Kuesioner - Super Admin" />

    <div class="min-h-screen bg-[#f8fafc] flex flex-col font-sans pb-24">
        
        <!-- Navbar Terpadu -->
        <Navbar />

        <!-- Header Solid Hijau Resmi UKDW #0D542B -->
        <header class="bg-[#0D542B] text-white pt-10 pb-20">
            <div class="w-full max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
                <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                    Kelola Kuesioner
                </h1>
                <p class="text-white/90 font-medium mt-1 max-w-2xl text-sm sm:text-base leading-relaxed">
                    Konfigurasi butir pertanyaan, opsi jawaban, dan alur percabangan (*jump logic*) kuesioner Tracer Study.
                </p>
            </div>
        </header>

        <!-- Main Card Container (Mirip Form Profil Alumni) -->
        <main class="w-full max-w-[1400px] mx-auto -mt-16 px-4 sm:px-6 lg:px-8 relative z-20">
            <div class="bg-white rounded-[2rem] shadow-xl border border-gray-100 overflow-hidden relative transition-all duration-300">
                
                <!-- Section Tabs (Terintegrasi rapi di bagian atas kartu) -->
                <SectionTabs
                    :sections="sections"
                    :activeSectionId="activeSectionId"
                    :subpertanyaans="subpertanyaans"
                    @select="handleSelectSection"
                />

                <!-- Konten Bagian Kuesioner -->
                <div class="p-6 md:p-10 space-y-6">
                    
                    <!-- Bar Aksi: Judul Bagian, Search, dan Tombol Tambah -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-gray-100">
                        <div>
                            <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">
                                {{ currentActiveSection?.title || 'Bagian Kuesioner' }}
                            </h2>
                            <p class="text-xs sm:text-sm text-gray-500 mt-0.5 font-medium">
                                Bagian {{ currentActiveSection?.order || '•' }} • Memuat {{ activeSectionQuestions.length }} butir pertanyaan aktif.
                            </p>
                        </div>

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <!-- Search Bar -->
                            <div class="relative min-w-[240px]">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </span>
                                <input
                                    type="text"
                                    v-model="searchQuery"
                                    placeholder="Cari kode atau teks..."
                                    class="w-full pl-9 pr-4 py-2.5 rounded-xl text-xs sm:text-sm border border-gray-200 focus:ring-2 focus:ring-[#005B3C] focus:border-transparent bg-gray-50/50"
                                />
                            </div>

                            <!-- Tombol Tambah Pertanyaan (Hijau Resmi UKDW #0D542B) -->
                            <button
                                type="button"
                                @click="openAddQuestionModal"
                                class="px-6 py-2.5 bg-[#0D542B] hover:bg-[#08381c] text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs transition-all flex items-center justify-center gap-2 cursor-pointer shrink-0"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                <span>Tambah Pertanyaan</span>
                            </button>
                        </div>
                    </div>

                    <!-- Banner Informasi & Pengaturan Khusus F17 Dual Matrix -->
                    <div v-if="isF17Section" class="p-5 rounded-2xl bg-gradient-to-br from-emerald-50/90 to-teal-50/60 border border-emerald-200/90 shadow-xs space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-[#005B3C] text-white flex items-center justify-center font-black text-sm shadow-xs shrink-0">
                                    F17
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="font-extrabold text-sm sm:text-base text-gray-900">
                                             Instrumen Evaluasi Kompetensi (Dual Matrix A vs B)
                                        </h4>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#005B3C] text-white">
                                            {{ f17AspectPairs.length }} Aspek ({{ activeSectionQuestions.length }} Butir Soal)
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-0.5">
                                        Di sisi alumni, pasangan soal ganjil & genap otomatis disatukan menjadi 1 baris tabel evaluasi berdampingan.
                                    </p>
                                </div>
                            </div>

                            <!-- Tombol Alih Mode Tampilan: Matriks Berpasangan vs Butir Terpisah -->
                            <div class="flex items-center bg-white p-1 rounded-xl border border-emerald-200/80 shadow-2xs self-start sm:self-auto shrink-0">
                                <button
                                    type="button"
                                    @click="f17ViewMode = 'paired'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
                                    :class="f17ViewMode === 'paired' ? 'bg-[#005B3C] text-white shadow-2xs' : 'text-gray-600 hover:text-gray-900'"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                                    <span>Matriks Aspek ({{ f17AspectPairs.length }})</span>
                                </button>
                                <button
                                    type="button"
                                    @click="f17ViewMode = 'flat'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
                                    :class="f17ViewMode === 'flat' ? 'bg-[#005B3C] text-white shadow-2xs' : 'text-gray-600 hover:text-gray-900'"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                                    <span>Semua Butir ({{ activeSectionQuestions.length }})</span>
                                </button>
                            </div>
                        </div>

                        <!-- Ringkasan Aturan Baku Penulisan -->
                        <div class="pt-3 border-t border-emerald-200/60 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 text-xs text-emerald-950">
                            <div class="p-2.5 rounded-xl bg-white/80 border border-emerald-100 flex items-start gap-2">
                                <span class="text-base leading-none">🔹</span>
                                <div>
                                    <strong class="text-emerald-800 font-bold block">Kolom A: Nomor Ganjil</strong>
                                    <span class="text-[11px] text-gray-600">Menilai kompetensi yang dikuasai alumni (contoh: <code class="bg-gray-100 px-1 rounded">F17-1</code>, <code class="bg-gray-100 px-1 rounded">F17-3</code>).</span>
                                </div>
                            </div>
                            <div class="p-2.5 rounded-xl bg-white/80 border border-emerald-100 flex items-start gap-2">
                                <span class="text-base leading-none">🔸</span>
                                <div>
                                    <strong class="text-teal-800 font-bold block">Kolom B: Nomor Genap</strong>
                                    <span class="text-[11px] text-gray-600">Menilai kontribusi perguruan tinggi (contoh: <code class="bg-gray-100 px-1 rounded">F17-2</code>, <code class="bg-gray-100 px-1 rounded">F17-4</code>).</span>
                                </div>
                            </div>
                            <div class="p-2.5 rounded-xl bg-white/80 border border-emerald-100 flex items-start gap-2 sm:col-span-2 lg:col-span-1">
                                <span class="text-base leading-none">⭐</span>
                                <div>
                                    <strong class="text-gray-800 font-bold block">Format Teks & Tipe Soal</strong>
                                    <span class="text-[11px] text-gray-600">Gunakan tanda pisah <code class="bg-gray-100 px-1 rounded">—</code>, tipe <strong>Pilihan Tunggal (Radio)</strong> atau <strong>Skala Rating (1-5)</strong>.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAMPILAN KHUSUS F17: DAFTAR ASPEK BERPASANGAN (A vs B) -->
                    <div v-if="isF17Section && f17ViewMode === 'paired'" class="space-y-4">
                        <div
                            v-for="pair in f17AspectPairs"
                            :key="'pair_' + pair.aspectNumber"
                            class="bg-white rounded-2xl border border-gray-200 hover:border-emerald-300 shadow-xs hover:shadow-md transition-all overflow-hidden"
                        >
                            <!-- Header Bar Aspek -->
                            <div class="px-5 py-3 bg-gray-50/80 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div class="flex items-center gap-3">
                                    <span class="px-2.5 py-0.5 rounded-lg text-xs font-black bg-emerald-100 text-[#005B3C]">
                                        Aspek #{{ pair.aspectNumber }}
                                    </span>
                                    <h3 class="font-extrabold text-sm sm:text-base text-gray-900 tracking-tight">
                                        {{ pair.aspectName }}
                                    </h3>
                                </div>

                                <div class="text-[11px] font-semibold text-gray-500">
                                    Berpasangan: 
                                    <span class="text-emerald-700 font-bold font-mono">{{ pair.qA ? pair.qA.kode_pertanyaan : (pair.expectedCodeA + ' (Belum)') }}</span> 
                                    & 
                                    <span class="text-teal-700 font-bold font-mono">{{ pair.qB ? pair.qB.kode_pertanyaan : (pair.expectedCodeB + ' (Belum)') }}</span>
                                </div>
                            </div>

                            <!-- Dua Kolom: Kolom A vs Kolom B -->
                            <div class="p-4 sm:p-5 grid grid-cols-1 lg:grid-cols-2 gap-4">
                                <!-- Kartu Soal Kolom A (Kompetensi Dikuasai) -->
                                <div v-if="pair.qA" class="p-4 rounded-xl bg-emerald-50/30 border border-emerald-100 space-y-2.5 relative">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-extrabold bg-emerald-100 text-emerald-800 font-mono">
                                            Kolom A • {{ pair.qA.kode_pertanyaan }}
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <button
                                                type="button"
                                                @click="openEditQuestionModal(pair.qA)"
                                                class="px-2 py-1 text-xs font-bold text-gray-600 hover:text-[#005B3C] hover:bg-white rounded-lg transition-colors cursor-pointer"
                                                title="Edit Soal A"
                                            >
                                                ✎ Edit
                                            </button>
                                            <button
                                                type="button"
                                                @click="handleDeleteQuestion(pair.qA)"
                                                class="px-2 py-1 text-xs font-bold text-gray-400 hover:text-red-600 hover:bg-white rounded-lg transition-colors cursor-pointer"
                                                title="Hapus Soal A"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                    </div>

                                    <p class="text-xs sm:text-sm font-bold text-gray-800 leading-snug">
                                        {{ pair.qA.subpertanyaan }}
                                    </p>

                                    <!-- Indikator 5 Opsi Skala Rating -->
                                    <div class="pt-2 border-t border-emerald-100/80 flex items-center justify-between text-[11px] text-gray-500">
                                        <span class="font-medium">Pilihan Skala Rating:</span>
                                        <div class="flex items-center gap-1 font-mono font-bold text-emerald-800">
                                            <span v-for="opt in (pair.qA.detils || pair.qA.options || [])" :key="opt.id" class="px-1.5 py-0.5 rounded bg-white border border-emerald-100 text-[10px]" :title="opt.option_text">
                                                {{ opt.option_text?.substring(0, 1) || '•' }}
                                            </span>
                                            <span v-if="(!pair.qA.detils && !pair.qA.options) || (pair.qA.detils?.length === 0 && pair.qA.options?.length === 0)" class="text-amber-600 text-xs">
                                                Belum ada opsi
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Jika Pasangan A belum ada -->
                                <div v-else class="p-4 rounded-xl bg-amber-50/50 border border-dashed border-amber-300 flex flex-col items-center justify-center text-center">
                                    <span class="text-xs text-amber-700 font-bold">⚠️ Pasangan Kolom A ({{ pair.expectedCodeA }}) Belum Dibuat</span>
                                    <p class="text-[11px] text-gray-500 mt-1">Buat soal bernomor ganjil untuk melengkapi aspek ini.</p>
                                </div>

                                <!-- Kartu Soal Kolom B (Kontribusi PT) -->
                                <div v-if="pair.qB" class="p-4 rounded-xl bg-teal-50/30 border border-teal-100 space-y-2.5 relative">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-extrabold bg-teal-100 text-teal-800 font-mono">
                                            Kolom B • {{ pair.qB.kode_pertanyaan }}
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <button
                                                type="button"
                                                @click="openEditQuestionModal(pair.qB)"
                                                class="px-2 py-1 text-xs font-bold text-gray-600 hover:text-teal-700 hover:bg-white rounded-lg transition-colors cursor-pointer"
                                                title="Edit Soal B"
                                            >
                                                ✎ Edit
                                            </button>
                                            <button
                                                type="button"
                                                @click="handleDeleteQuestion(pair.qB)"
                                                class="px-2 py-1 text-xs font-bold text-gray-400 hover:text-red-600 hover:bg-white rounded-lg transition-colors cursor-pointer"
                                                title="Hapus Soal B"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                    </div>

                                    <p class="text-xs sm:text-sm font-bold text-gray-800 leading-snug">
                                        {{ pair.qB.subpertanyaan }}
                                    </p>

                                    <!-- Indikator 5 Opsi Skala Rating -->
                                    <div class="pt-2 border-t border-teal-100/80 flex items-center justify-between text-[11px] text-gray-500">
                                        <span class="font-medium">Pilihan Skala Rating:</span>
                                        <div class="flex items-center gap-1 font-mono font-bold text-teal-800">
                                            <span v-for="opt in (pair.qB.detils || pair.qB.options || [])" :key="opt.id" class="px-1.5 py-0.5 rounded bg-white border border-teal-100 text-[10px]" :title="opt.option_text">
                                                {{ opt.option_text?.substring(0, 1) || '•' }}
                                            </span>
                                            <span v-if="(!pair.qB.detils && !pair.qB.options) || (pair.qB.detils?.length === 0 && pair.qB.options?.length === 0)" class="text-amber-600 text-xs">
                                                Belum ada opsi
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Jika Pasangan B belum ada -->
                                <div v-else class="p-4 rounded-xl bg-amber-50/50 border border-dashed border-amber-300 flex flex-col items-center justify-center text-center">
                                    <span class="text-xs text-amber-700 font-bold">⚠️ Pasangan Kolom B ({{ pair.expectedCodeB }}) Belum Dibuat</span>
                                    <p class="text-[11px] text-gray-500 mt-1">Buat soal bernomor genap untuk melengkapi aspek ini.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAMPILAN STANDAR: DAFTAR KARTU PERTANYAAN INDIVIDUAL -->
                    <div v-else-if="activeSectionQuestions.length > 0" class="space-y-4">
                        <QuestionCard
                            v-for="(q, index) in activeSectionQuestions"
                            :key="q.id"
                            :subpertanyaan="q"
                            :index="index"
                            :totalInActiveSection="activeSectionQuestions.length"
                            :targetQuestionMap="targetQuestionMap"
                            :isReordering="isReordering"
                            @editQuestion="openEditQuestionModal"
                            @deleteQuestion="handleDeleteQuestion"
                            @moveQuestion="handleMoveQuestion"
                            @addOption="openAddOptionModal"
                            @editOption="openEditOptionModal"
                            @deleteOption="handleDeleteOption"
                        />
                    </div>

                    <!-- Empty State Bersih & Minimal -->
                    <div 
                        v-else 
                        class="p-12 text-center border border-dashed border-gray-200 rounded-2xl bg-gray-50/50 space-y-3"
                    >
                        <div class="w-12 h-12 rounded-full bg-emerald-50 text-[#005B3C] mx-auto flex items-center justify-center text-xl font-bold">
                            ?
                        </div>
                        <h3 class="text-base font-bold text-gray-800">
                            {{ searchQuery ? 'Pertanyaan Tidak Ditemukan' : 'Belum Ada Pertanyaan di Bagian Ini' }}
                        </h3>
                        <p class="text-xs text-gray-500 max-w-sm mx-auto">
                            {{ searchQuery ? `Tidak ada hasil pencarian untuk "${searchQuery}".` : 'Bagian ini belum memiliki butir pertanyaan. Tambahkan butir pertanyaan baru untuk memulai.' }}
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Modal Tambah / Edit Pertanyaan -->
        <QuestionModal
            :show="showQuestionModal"
            :isEdit="isEditingQuestion"
            :subpertanyaan="selectedQuestion"
            :sections="sections"
            :defaultSectionId="activeSectionId"
            :nextOrder="calculatedNextOrder"
            @close="closeQuestionModal"
            @saved="closeQuestionModal"
        />

        <!-- Modal Tambah / Edit Opsi Jawaban & Jump Logic -->
        <OptionModal
            :show="showOptionModal"
            :isEdit="isEditingOption"
            :subpertanyaan="selectedQuestionForOption"
            :option="selectedOption"
            :availableJumpTargets="availableJumpTargets"
            @close="closeOptionModal"
            @saved="closeOptionModal"
        />
    </div>
</template>
