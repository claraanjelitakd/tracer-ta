<!--
  Halaman Kelola Pertanyaan & Alur Branching Kuesioner (Biro 3)
  Desain: Bersih, Profesional, Formal ala Profil Alumni dengan warna resmi UKDW (Hijau #005B3C & Kuning Landing Page #FACC15).
  Fitur Utama:
  1. Navigasi Section di bagian atas (Tabs horizontal seperti di Profil Alumni).
  2. Pertanyaan ditampilkan terkelompok per section terpilih (tidak menumpuk sekaligus).
  3. Pemindahan urutan kartu pertanyaan (bubble) dibatasi hanya di dalam section yang sama.
  4. Pemisahan tampilan CRUD (Mode Alur vs Mode Formulir Terpisah).
  5. Tabel alur percabangan jelas menampilkan kode target beserta teks pertanyaan tujuan.
  6. Tanpa input manual urutan opsi.
-->
<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    subpertanyaans: Array,
    sections: Array,
    prodis: Array,
    availableJumpTargets: Array,
    targetQuestionMap: Object,
});

// Method proses logout Admin Biro 3 dan kembali ke beranda (Home)
const logout = () => {
    router.post('/logout');
};

// ========================================================
// 1. STATE NAVIGASI PER SECTION & FILTER
// ========================================================
// Storage keys untuk persistensi saat refresh
const BIRO3_SECTION_STORAGE_KEY = 'tracerstudy_biro3_pertanyaan_section';

const getInitialSectionId = () => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        const secParam = urlParams.get('sec_id');
        if (secParam) {
            const parsed = parseInt(secParam, 10);
            if (props.sections.some(s => s.id === parsed)) return parsed;
        }
        const cached = localStorage.getItem(BIRO3_SECTION_STORAGE_KEY);
        if (cached) {
            const parsed = parseInt(cached, 10);
            if (props.sections.some(s => s.id === parsed)) return parsed;
        }
    }
    return props.sections.length > 0 ? props.sections[0].id : null;
};

// Default active section: dari cache atau section pertama
const activeSectionId = ref(getInitialSectionId());

watch(activeSectionId, (newId) => {
    if (typeof window !== 'undefined' && newId) {
        localStorage.setItem(BIRO3_SECTION_STORAGE_KEY, newId.toString());
        const url = new URL(window.location.href);
        url.searchParams.set('sec_id', newId.toString());
        window.history.replaceState({}, '', url.toString());
    }
});

// Mode Tampilan: 'list' (Alur Kuesioner per Section) atau 'form' (Formulir CRUD Terpisah)
const currentView = ref('list');
const isEditingQuestion = ref(false);

const searchQuery = ref('');
const isReordering = ref(false);

// Pertanyaan yang termasuk di dalam section yang sedang aktif
const activeSectionQuestions = computed(() => {
    if (!activeSectionId.value) return [];
    
    return (props.subpertanyaans || [])
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

// Informasi section yang sedang aktif
const currentActiveSection = computed(() => {
    return props.sections.find((s) => s.id === activeSectionId.value) || null;
});

// ========================================================
// 2. FORM PERTANYAAN (CRUD TERPISAH)
// ========================================================
const questionForm = useForm({
    id: null,
    kelompok_pertanyaan_id: '',
    prodi_id: null,
    kode_pertanyaan: '',
    subpertanyaan: '',
    type: 'single_choice',
    wajib: true,
    order: null,
});

const openAddQuestionForm = () => {
    isEditingQuestion.value = false;
    questionForm.reset();
    questionForm.kelompok_pertanyaan_id = activeSectionId.value || (props.sections.length > 0 ? props.sections[0].id : '');
    questionForm.prodi_id = null;
    questionForm.kode_pertanyaan = '';
    questionForm.subpertanyaan = '';
    questionForm.type = 'single_choice';
    questionForm.wajib = true;
    questionForm.order = activeSectionQuestions.value.length + 1;
    currentView.value = 'form';
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const openEditQuestionForm = (q) => {
    isEditingQuestion.value = true;
    questionForm.id = q.id;
    questionForm.kelompok_pertanyaan_id = q.kelompok_pertanyaan_id;
    questionForm.prodi_id = q.prodi_id || null;
    questionForm.kode_pertanyaan = q.kode_pertanyaan;
    questionForm.subpertanyaan = q.subpertanyaan;
    questionForm.type = q.type;
    questionForm.wajib = !!q.wajib;
    questionForm.order = q.order;
    currentView.value = 'form';
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancelQuestionForm = () => {
    questionForm.reset();
    currentView.value = 'list';
};

const submitQuestion = () => {
    if (isEditingQuestion.value) {
        questionForm.put(`/biro3/pertanyaan/${questionForm.id}`, {
            onSuccess: () => {
                currentView.value = 'list';
            },
        });
    } else {
        questionForm.post('/biro3/pertanyaan', {
            onSuccess: () => {
                currentView.value = 'list';
            },
        });
    }
};

const deleteQuestion = (q) => {
    if (confirm(`Hapus pertanyaan ${q.kode_pertanyaan}? Semua opsi jawaban terkait juga akan terhapus.`)) {
        router.delete(`/biro3/pertanyaan/${q.id}`, {
            preserveScroll: true,
        });
    }
};

// ========================================================
// 3. PEMINDAHAN PERTANYAAN (HANYA DALAM SECTION SAMA)
// ========================================================
const moveQuestion = (q, direction) => {
    isReordering.value = true;
    router.post('/biro3/pertanyaan/reorder', {
        id: q.id,
        direction: direction,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isReordering.value = false;
        },
    });
};

// ========================================================
// 4. MODAL OPSI JAWABAN (SEDERHANA TANPA URUTAN MANUAL)
// ========================================================
const showOptionModal = ref(false);
const isEditingOption = ref(false);
const selectedQuestion = ref(null);

const optionForm = useForm({
    id: null,
    code: '',
    option_text: '',
    jump_to: '',
});

const openAddOptionModal = (q) => {
    selectedQuestion.value = q;
    isEditingOption.value = false;
    optionForm.reset();
    optionForm.code = '';
    optionForm.option_text = '';
    optionForm.jump_to = '';
    showOptionModal.value = true;
};

const openEditOptionModal = (q, opt) => {
    selectedQuestion.value = q;
    isEditingOption.value = true;
    optionForm.id = opt.id;
    optionForm.code = opt.code || opt.kode_opsi || '';
    optionForm.option_text = opt.option_text;
    optionForm.jump_to = opt.jump_to || '';
    showOptionModal.value = true;
};

const submitOption = () => {
    if (isEditingOption.value) {
        optionForm.put(`/biro3/pertanyaan/options/${optionForm.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                showOptionModal.value = false;
            },
        });
    } else {
        optionForm.post(`/biro3/pertanyaan/${selectedQuestion.value.id}/options`, {
            preserveScroll: true,
            onSuccess: () => {
                showOptionModal.value = false;
            },
        });
    }
};

const deleteOption = (opt) => {
    if (confirm(`Hapus pilihan opsi "${opt.option_text}"?`)) {
        router.delete(`/biro3/pertanyaan/options/${opt.id}`, {
            preserveScroll: true,
        });
    }
};

const getTargetQuestionTitle = (code) => {
    if (!code) return '';
    return props.targetQuestionMap?.[code] || '';
};

const getTypeLabel = (type) => {
    const labels = {
        single_choice: 'Pilihan Tunggal (Radio)',
        multiple_choice: 'Pilihan Ganda (Checkbox)',
        radio: 'Pilihan Tunggal (Radio)',
        checkbox: 'Pilihan Ganda (Checkbox)',
        text: 'Isian Teks Singkat',
        number: 'Isian Angka (Numerik)',
        radio_input: 'Radio dengan Isian Angka',
        radio_text: 'Radio dengan Isian Teks',
        multiple_number: 'Isian Multi Finansial / Numerik',
        matrix: 'Matriks / Skala Penilaian',
        matrix_dual: 'Matriks Komparasi Ganda',
        searchable_select: 'Dropdown Pencarian',
    };
    return labels[type] || type;
};
</script>

<template>
    <Head title="Kelola Pertanyaan Kuesioner - Biro 3 UKDW" />

    <div class="min-h-screen bg-[#f8fafc] text-slate-800 font-sans pb-24">
        
        <!-- Navbar Minimal Bersih -->
        <nav class="bg-white/90 backdrop-blur-md shadow-sm border-b border-gray-100 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20 items-center">
                    
                    <!-- Logo & Brand -->
                    <div class="flex items-center space-x-3">
                        <img src="/uploads/landing/2.png" alt="Logo UKDW" class="h-10 w-10 object-contain" onerror="this.style.display='none'" />
                        <div class="border-l border-gray-200 pl-3">
                            <span class="text-xs uppercase tracking-wider text-gray-500 font-medium block leading-tight">Tracer Study UKDW</span>
                            <span class="text-base font-bold text-gray-900 leading-tight">Biro Kemahasiswaan, Alumni & Pengembangan Karir</span>
                        </div>
                    </div>

                    <!-- Navigasi Menu Atas -->
                    <div class="hidden md:flex items-center space-x-6">
                        <Link 
                            href="/biro3/dashboard" 
                            class="text-sm font-semibold text-gray-600 hover:text-[#005B3C] transition-colors"
                        >
                            Dashboard
                        </Link>
                        <Link 
                            href="/biro3/alumni" 
                            class="text-sm font-semibold text-gray-600 hover:text-[#005B3C] transition-colors"
                        >
                            Data Alumni
                        </Link>
                        <Link 
                            href="/biro3/pertanyaan" 
                            class="text-sm font-bold text-[#005B3C] border-b-2 border-[#005B3C] pb-1"
                        >
                            Kelola Pertanyaan
                        </Link>
                    </div>

                    <!-- User & Logout -->
                    <div class="flex items-center space-x-4">
                        <span class="text-xs font-semibold text-gray-600 hidden sm:inline-block">Admin Biro 3</span>
                        <button 
                            @click="logout" 
                            class="px-4 py-2 text-xs font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center gap-1.5 cursor-pointer"
                        >
                            <!-- Ikon Power Off untuk Tombol Keluar -->
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 11-12.728 0M12 3v9" />
                            </svg>
                            <span>Keluar</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Bar -->
            <div class="md:hidden px-4 py-2 bg-gray-50 border-t border-gray-100 flex justify-around text-xs font-semibold">
                <Link href="/biro3/dashboard" class="text-gray-600">Dashboard</Link>
                <Link href="/biro3/alumni" class="text-gray-600">Data Alumni</Link>
                <Link href="/biro3/pertanyaan" class="text-[#005B3C] font-bold">Kelola Pertanyaan</Link>
            </div>
        </nav>

        <!-- Header Profil Style Banner (Hijau Elegan & Kuning Landing Page) -->
        <header class="bg-gradient-to-r from-[#005B3C] to-[#007b55] pt-10 pb-20 relative overflow-hidden text-white">
            <div class="absolute -top-20 -right-20 w-80 h-80 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>
            
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <span class="inline-block px-3 py-1 bg-white/20 text-white rounded-full text-xs font-bold tracking-wide backdrop-blur-sm mb-2">
                        Konfigurasi Instrumen Kuesioner
                    </span>
                    <h1 class="text-3xl font-extrabold tracking-tight">Kelola Butir Pertanyaan & Alur Branching</h1>
                    <p class="text-green-100 text-sm mt-1 max-w-2xl">
                        Atur struktur pertanyaan per bagian, alur logika percabangan (*jump logic*), serta sasaran program studi.
                    </p>
                </div>

                <div class="shrink-0 flex items-center gap-3">
                    <button 
                        v-if="currentView === 'form'"
                        @click="cancelQuestionForm"
                        class="px-5 py-2.5 bg-white/15 hover:bg-white/25 text-white font-bold text-xs rounded-xl backdrop-blur-sm transition-all flex items-center gap-1.5"
                    >
                        <span>&larr;</span>
                        <span>Kembali ke Daftar Alur</span>
                    </button>
                    <button 
                        v-if="currentView === 'list'"
                        @click="openAddQuestionForm"
                        class="px-6 py-3 bg-yellow-400 hover:bg-yellow-500 text-green-950 font-bold text-xs uppercase tracking-wider rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5 flex items-center gap-2"
                    >
                        <span>+ Tambah Pertanyaan Baru</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Wrapper Konten -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-10 relative z-20">
            
            <!-- Flash Message -->
            <div v-if="$page.props.flash?.success" class="mb-4 p-4 rounded-xl bg-white border-l-4 border-[#005B3C] shadow-sm flex items-center justify-between text-xs text-green-900 font-semibold">
                <span>{{ $page.props.flash.success }}</span>
            </div>

            <!-- ======================================================== -->
            <!-- TAMPILAN 1: FORMULIR TAMBAH / EDIT PERTANYAAN (TERPISAH) -->
            <!-- ======================================================== -->
            <div v-if="currentView === 'form'" class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                <div class="px-8 py-6 bg-gray-50/70 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold" :class="isEditingQuestion ? 'bg-yellow-100 text-yellow-900' : 'bg-green-100 text-[#005B3C]'">
                            {{ isEditingQuestion ? 'Edit Data Pertanyaan' : 'Tambah Pertanyaan Baru' }}
                        </span>
                        <h2 class="text-xl font-extrabold text-gray-900 mt-1">
                            {{ isEditingQuestion ? `Edit Butir Pertanyaan [ ${questionForm.kode_pertanyaan} ]` : 'Formulir Penambahan Butir Pertanyaan Baru' }}
                        </h2>
                    </div>
                    <button @click="cancelQuestionForm" class="text-gray-400 hover:text-gray-600 text-sm font-semibold">
                        Batal
                    </button>
                </div>

                <form @submit.prevent="submitQuestion" class="p-8 space-y-6 max-w-4xl">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">
                                Bagian Kuesioner (Section) <span class="text-red-500">*</span>
                            </label>
                            <select v-model="questionForm.kelompok_pertanyaan_id" required class="w-full text-sm font-medium rounded-xl border-gray-300 focus:border-[#005B3C] focus:ring-[#005B3C] shadow-sm py-2.5">
                                <option v-for="sec in sections" :key="sec.id" :value="sec.id">
                                    {{ sec.title }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">
                                Target Program Studi
                            </label>
                            <select v-model="questionForm.prodi_id" class="w-full text-sm font-medium rounded-xl border-gray-300 focus:border-[#005B3C] focus:ring-[#005B3C] shadow-sm py-2.5">
                                <option :value="null">Semua Program Studi (Umum)</option>
                                <option v-for="p in prodis" :key="p.id" :value="p.id">
                                    {{ p.kode_prodi }} — {{ p.nama_prodi }}
                                </option>
                            </select>
                            <span class="text-xs text-gray-400 block mt-1">Pilih prodi jika soal ini hanya disajikan khusus untuk prodi tertentu (misal: F2E khusus Teologi).</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">
                                Kode Pertanyaan <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                v-model="questionForm.kode_pertanyaan" 
                                placeholder="Contoh: F3, F8, F11" 
                                required 
                                class="w-full text-sm font-mono font-bold rounded-xl border-gray-300 focus:border-[#005B3C] focus:ring-[#005B3C] shadow-sm py-2.5 uppercase"
                            >
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">
                                Tipe Input Jawaban <span class="text-red-500">*</span>
                            </label>
                            <select v-model="questionForm.type" required class="w-full text-sm font-semibold rounded-xl border-gray-300 focus:border-[#005B3C] focus:ring-[#005B3C] shadow-sm py-2.5">
                                <option value="single_choice">Pilihan Tunggal (Radio)</option>
                                <option value="multiple_choice">Pilihan Ganda (Checkbox)</option>
                                <option value="text">Isian Teks Singkat / Uraian</option>
                                <option value="number">Isian Angka (Numerik)</option>
                                <option value="radio_input">Radio dengan Isian Angka</option>
                                <option value="radio_text">Radio dengan Isian Teks</option>
                                <option value="multiple_number">Isian Multi Finansial / Numerik</option>
                                <option value="searchable_select">Dropdown Pencarian</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">
                            Teks / Bunyi Pertanyaan <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            v-model="questionForm.subpertanyaan" 
                            rows="3" 
                            required 
                            placeholder="Tuliskan isi teks pertanyaan..." 
                            class="w-full text-sm font-medium rounded-xl border-gray-300 focus:border-[#005B3C] focus:ring-[#005B3C] shadow-sm p-3"
                        ></textarea>
                    </div>

                    <div class="pt-3 border-t border-gray-100">
                        <label class="inline-flex items-center cursor-pointer gap-3">
                            <input 
                                type="checkbox" 
                                v-model="questionForm.wajib" 
                                class="rounded border-gray-300 text-[#005B3C] focus:ring-[#005B3C] h-4 w-4"
                            >
                            <div>
                                <span class="text-sm font-bold text-gray-800 block">Pertanyaan Wajib Diisi (Required)</span>
                                <span class="text-xs text-gray-400">Alumni tidak dapat menyelesaikan section jika belum diisi.</span>
                            </div>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100">
                        <button 
                            type="button" 
                            @click="cancelQuestionForm" 
                            class="px-6 py-2.5 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="questionForm.processing" 
                            class="px-8 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-[#005B3C] hover:bg-[#00422c] rounded-xl shadow-md transition-all disabled:opacity-50"
                        >
                            {{ isEditingQuestion ? 'Simpan Perubahan' : 'Simpan Pertanyaan' }}
                        </button>
                    </div>

                </form>
            </div>

            <!-- ======================================================== -->
            <!-- TAMPILAN 2: DAFTAR PERTANYAAN BERDASARKAN SECTION (TABS) -->
            <!-- ======================================================== -->
            <div v-else class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                
                <!-- Navigasi Horizontal Tabs Section (Ala Profil Alumni) -->
                <div class="flex overflow-x-auto border-b border-gray-100 sticky top-20 z-30 bg-white/95 backdrop-blur-md px-2">
                    <button 
                        v-for="sec in sections" 
                        :key="sec.id"
                        type="button" 
                        @click="activeSectionId = sec.id" 
                        :class="activeSectionId === sec.id ? 'border-[#005B3C] text-[#005B3C] font-extrabold bg-green-50/50' : 'border-transparent text-gray-500 hover:text-gray-800 font-semibold'" 
                        class="whitespace-nowrap py-4 px-6 border-b-2 text-sm transition-all flex-shrink-0 text-center flex items-center gap-2"
                    >
                        <span>{{ sec.title }}</span>
                        <span 
                            class="px-2 py-0.5 text-[10px] rounded-full font-mono font-bold"
                            :class="activeSectionId === sec.id ? 'bg-[#005B3C] text-white' : 'bg-gray-100 text-gray-600'"
                        >
                            {{ (subpertanyaans || []).filter(q => q.kelompok_pertanyaan_id === sec.id).length }}
                        </span>
                    </button>
                </div>

                <!-- Kontrol Pencarian & Ringkasan Section -->
                <div class="p-6 bg-gray-50/60 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-extrabold text-gray-900">
                            {{ currentActiveSection?.title || 'Daftar Pertanyaan' }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Menampilkan {{ activeSectionQuestions.length }} butir pertanyaan pada bagian ini.
                        </p>
                    </div>

                    <div class="w-full sm:w-72">
                        <input 
                            type="text" 
                            v-model="searchQuery" 
                            placeholder="Cari kode atau teks di bagian ini..." 
                            class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:border-[#005B3C] focus:ring-[#005B3C]"
                        >
                    </div>
                </div>

                <!-- Daftar Kartu Pertanyaan (Bubble Cards) di Section Aktif -->
                <div class="p-6 space-y-6">
                    
                    <div 
                        v-for="(q, idx) in activeSectionQuestions" 
                        :key="q.id" 
                        class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-all overflow-hidden"
                    >
                        <!-- Header Kartu Pertanyaan -->
                        <div class="p-4 bg-gray-50/80 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            
                            <!-- Kontrol Urutan & Badge -->
                            <div class="flex items-center gap-2.5 flex-wrap">
                                
                                <!-- Tombol Naik / Turun (Hanya dalam Section yang Sama) -->
                                <div class="inline-flex rounded-lg border border-gray-200 bg-white overflow-hidden shadow-xs">
                                    <button 
                                        @click="moveQuestion(q, 'up')" 
                                        :disabled="idx === 0 || isReordering"
                                        title="Pindahkan ke atas di bagian ini"
                                        class="px-2 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed border-r border-gray-100 transition-colors"
                                    >
                                        &uarr;
                                    </button>
                                    <button 
                                        @click="moveQuestion(q, 'down')" 
                                        :disabled="idx === activeSectionQuestions.length - 1 || isReordering"
                                        title="Pindahkan ke bawah di bagian ini"
                                        class="px-2 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                                    >
                                        &darr;
                                    </button>
                                </div>

                                <!-- Kode Pertanyaan (Hijau Resmi UKDW) -->
                                <span class="px-2.5 py-0.5 bg-[#005B3C] text-white font-mono font-bold text-xs rounded-md">
                                    {{ q.kode_pertanyaan }}
                                </span>

                                <!-- Tipe Input -->
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-700 font-semibold text-xs rounded-md">
                                    {{ getTypeLabel(q.type) }}
                                </span>

                                <!-- Wajib / Opsional (Kuning Elegan / Netral) -->
                                <span v-if="q.wajib" class="px-2 py-0.5 bg-yellow-100 text-yellow-900 font-semibold text-xs rounded-md border border-yellow-300">
                                    Wajib
                                </span>
                                <span v-else class="px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-md">
                                    Opsional
                                </span>

                                <!-- Prodi Spesifik -->
                                <span v-if="q.prodi" class="px-2 py-0.5 bg-purple-50 text-purple-800 font-bold text-xs rounded-md border border-purple-200">
                                    Khusus: {{ q.prodi.nama_prodi }}
                                </span>
                            </div>

                            <!-- Tombol Aksi Soal -->
                            <div class="flex items-center gap-3">
                                <button 
                                    @click="openEditQuestionForm(q)" 
                                    class="text-xs font-bold text-[#005B3C] hover:underline"
                                >
                                    Edit Pertanyaan
                                </button>
                                <span class="text-gray-300">|</span>
                                <button 
                                    @click="deleteQuestion(q)" 
                                    class="text-xs font-semibold text-gray-500 hover:text-red-600"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>

                        <!-- Teks Pertanyaan -->
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="text-base font-bold text-gray-900 leading-snug">
                                {{ q.subpertanyaan }}
                            </h3>
                        </div>

                        <!-- Tabel Opsi Jawaban & Alur Percabangan -->
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-600">
                                    Pilihan Opsi Jawaban & Alur Percabangan ({{ (q.detils || q.options)?.length || 0 }})
                                </span>
                                <button 
                                    @click="openAddOptionModal(q)" 
                                    class="px-3 py-1.5 text-xs font-bold text-[#005B3C] bg-green-50 hover:bg-green-100 rounded-lg transition-colors border border-green-200"
                                >
                                    + Tambah Pilihan Opsi
                                </button>
                            </div>

                            <!-- Bila Tanpa Opsi -->
                            <div v-if="!(q.detils || q.options) || (q.detils || q.options).length === 0" class="bg-gray-50 border border-dashed border-gray-200 rounded-lg p-3 text-center text-xs text-gray-400 italic">
                                Pertanyaan tipe ini berupa isian langsung tanpa opsi pilihan.
                            </div>

                            <!-- Tabel Formal Rapi -->
                            <div v-else class="overflow-x-auto border border-gray-100 rounded-xl">
                                <table class="min-w-full divide-y divide-gray-100 text-left text-xs">
                                    <thead class="bg-gray-50 text-gray-500 font-bold uppercase tracking-wider">
                                        <tr>
                                            <th class="py-2.5 px-4 w-12 text-center">No</th>
                                            <th class="py-2.5 px-4 w-28">Kode Opsi</th>
                                            <th class="py-2.5 px-4">Teks Jawaban</th>
                                            <th class="py-2.5 px-4 w-80">Setelah Memilih Opsi (Branching)</th>
                                            <th class="py-2.5 px-4 w-24 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 bg-white">
                                        <tr v-for="(opt, optIdx) in (q.detils || q.options)" :key="opt.id" class="hover:bg-gray-50/60 transition-colors">
                                            <td class="py-3 px-4 text-center font-bold text-gray-400">
                                                {{ optIdx + 1 }}
                                            </td>
                                            <td class="py-3 px-4 font-mono font-bold text-gray-700">
                                                {{ opt.code || opt.kode_opsi || '-' }}
                                            </td>
                                            <td class="py-3 px-4 font-medium text-gray-900">
                                                {{ opt.option_text }}
                                            </td>
                                            <td class="py-3 px-4">
                                                <!-- Jika ada jump_to: tampilkan kode & teks pertanyaan target dengan warna kuning landing page -->
                                                <div v-if="opt.jump_to" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-yellow-50 text-yellow-950 border border-yellow-300 rounded-md font-medium text-xs max-w-md">
                                                    <span class="font-bold text-[#005B3C]">Lompat ke {{ opt.jump_to }}:</span>
                                                    <span class="truncate" :title="getTargetQuestionTitle(opt.jump_to)">
                                                        {{ getTargetQuestionTitle(opt.jump_to) || 'Pertanyaan ' + opt.jump_to }}
                                                    </span>
                                                </div>
                                                <div v-else class="text-gray-400 text-xs">
                                                    Lanjut ke pertanyaan berikutnya (Alur Normal)
                                                </div>
                                            </td>
                                            <td class="py-3 px-4 text-right space-x-2">
                                                <button 
                                                    @click="openEditOptionModal(q, opt)" 
                                                    class="text-[#005B3C] hover:underline font-bold text-xs"
                                                >
                                                    Edit
                                                </button>
                                                <button 
                                                    @click="deleteOption(opt)" 
                                                    class="text-gray-400 hover:text-red-600 font-semibold text-xs"
                                                >
                                                    Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>

                    <!-- Bila Tidak Ada Pertanyaan di Section -->
                    <div v-if="activeSectionQuestions.length === 0" class="p-12 text-center bg-gray-50 rounded-xl border border-dashed border-gray-200">
                        <p class="text-sm font-bold text-gray-700">Belum ada butir pertanyaan pada bagian ini.</p>
                        <p class="text-xs text-gray-400 mt-1">Klik "+ Tambah Pertanyaan Baru" untuk mulai menambahkan pertanyaan ke bagian ini.</p>
                    </div>

                </div>

            </div>

        </main>

        <!-- ======================================================== -->
        <!-- MODAL FOKUS: TAMBAH / EDIT OPSI JAWABAN (SEDERHANA)       -->
        <!-- ======================================================== -->
        <div v-if="showOptionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl border border-gray-100 max-w-lg w-full shadow-2xl overflow-hidden">
                
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">
                            {{ isEditingOption ? 'Edit Pilihan Opsi' : 'Tambah Pilihan Opsi' }}
                        </h3>
                        <p class="text-xs text-gray-500 font-mono mt-0.5">
                            Pertanyaan: [ {{ selectedQuestion?.kode_pertanyaan }} ] {{ selectedQuestion?.subpertanyaan }}
                        </p>
                    </div>
                    <button @click="showOptionModal = false" class="text-gray-400 hover:text-gray-600 font-bold text-lg leading-none">
                        &times;
                    </button>
                </div>

                <form @submit.prevent="submitOption" class="p-6 space-y-4">
                    
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            Teks Pilihan Jawaban <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            v-model="optionForm.option_text" 
                            required 
                            placeholder="Contoh: Bekerja purna waktu / Wirausaha / Belum bekerja" 
                            class="w-full text-xs font-semibold rounded-xl border-gray-300 focus:border-[#005B3C] focus:ring-[#005B3C] shadow-sm py-2.5"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            Kode Opsi (Opsional)
                        </label>
                        <input 
                            type="text" 
                            v-model="optionForm.code" 
                            :placeholder="`Otomatis (misal: ${selectedQuestion?.kode_pertanyaan || 'F3'}-01)`" 
                            class="w-full text-xs font-mono font-bold rounded-xl border-gray-300 focus:border-[#005B3C] focus:ring-[#005B3C] shadow-sm py-2 uppercase"
                        >
                        <span class="text-[10px] text-gray-400 block mt-1">Kosongkan jika ingin nomor kode digenerate otomatis.</span>
                    </div>

                    <!-- Dropdown Branching (Kuning Landing Page Accent) -->
                    <div class="p-4 bg-yellow-50/70 rounded-xl border border-yellow-200 space-y-1.5">
                        <label class="block text-xs font-bold text-yellow-950">
                            Setelah Memilih Opsi Ini (Alur Branching / Lompatan)
                        </label>
                        <select 
                            v-model="optionForm.jump_to" 
                            class="w-full text-xs font-medium rounded-lg border-yellow-300 bg-white focus:border-[#005B3C] focus:ring-[#005B3C] text-gray-800 py-2"
                        >
                            <option value="">Lanjut ke pertanyaan berikutnya (Alur Normal)</option>
                            <option 
                                v-for="target in availableJumpTargets" 
                                :key="target.code" 
                                :value="target.code"
                            >
                                Lompat ke: [ {{ target.code }} ] {{ target.text }}
                            </option>
                        </select>
                        <span class="text-[11px] text-yellow-900 block leading-tight">
                            Pilih kode tujuan jika ingin melewati pertanyaan di antaranya saat opsi ini dipilih.
                        </span>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                        <button 
                            type="button" 
                            @click="showOptionModal = false" 
                            class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="optionForm.processing" 
                            class="px-5 py-2 text-xs font-bold uppercase tracking-wider text-white bg-[#005B3C] hover:bg-[#00422c] rounded-xl shadow-md transition-all disabled:opacity-50"
                        >
                            {{ isEditingOption ? 'Simpan Perubahan' : 'Tambahkan Opsi' }}
                        </button>
                    </div>

                </form>

            </div>
        </div>

    </div>
</template>
