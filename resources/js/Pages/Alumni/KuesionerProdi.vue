<!--
  Halaman: Kuesioner Khusus Program Studi (Frontend Alumni)
  File: resources/js/Pages/Alumni/KuesionerProdi.vue
-->
<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import Navbar from './Components/Navbar.vue';

const props = defineProps({
    alumni: Object,
    prodi: Object,
    sections: {
        type: Array,
        default: () => [],
    },
    questions: {
        type: Array,
        default: () => [],
    },
    initialAnswers: {
        type: Object,
        default: () => ({}),
    },
    message: String,
});

// Helper pendeteksi pertanyaan identitas yang otomatis diambil dari data akademik
const isAcademicField = (q) => {
    const txt = (q.question_text || '').toLowerCase().trim();
    return q.code === 'PSI-1-01' || q.code === 'PSI-1-02' || q.code === 'PSI-1-03'
        || txt === 'nama' || txt === 'nim' || txt === 'tahun kelulusan' || txt === 'tahun lulus';
};

// Inisialisasi formulir jawaban
const initialMap = { ...props.initialAnswers };
props.questions.forEach(q => {
    const txt = (q.question_text || '').toLowerCase().trim();
    if (q.code === 'PSI-1-01' || txt === 'nama') {
        if (!initialMap[q.id]) {
            initialMap[q.id] = props.alumni?.dataAkademik?.nama || props.alumni?.user?.name || '';
        }
    } else if (q.code === 'PSI-1-02' || txt === 'nim') {
        if (!initialMap[q.id]) {
            initialMap[q.id] = props.alumni?.nim || props.alumni?.dataAkademik?.nim || '';
        }
    } else if (q.code === 'PSI-1-03' || txt === 'tahun kelulusan' || txt === 'tahun lulus') {
        if (!initialMap[q.id]) {
            initialMap[q.id] = props.alumni?.dataAkademik?.tahun_akademik_lulus || props.alumni?.dataAkademik?.tahun_lulus || '';
        }
    }
});

const form = useForm({
    answers: initialMap,
});

// Mengelompokkan pertanyaan berdasarkan Section
const groupedSections = computed(() => {
    if (props.sections && props.sections.length > 0) {
        return props.sections.map(sec => {
            const secQuestions = (sec.questions && sec.questions.length > 0)
                ? sec.questions
                : props.questions.filter(q => q.prodi_question_section_id === sec.id);
            return {
                ...sec,
                questions: secQuestions,
            };
        });
    }

    // Fallback jika belum ada section yang tersimpan
    return [{
        id: 'default',
        order: 1,
        title: 'Kuesioner Program Studi',
        description: 'Pertanyaan evaluasi dan pengembangan program studi.',
        questions: props.questions,
    }];
});

const filterNumberInput = (event) => {
    if (['e', 'E', '+', '-', '.'].includes(event.key)) {
        event.preventDefault();
    }
};

// Validasi apakah seluruh pertanyaan wajib sudah terisi
const isAllRequiredAnswered = () => {
    const requiredQuestions = props.questions.filter(q => q.is_required);
    for (const q of requiredQuestions) {
        const ans = form.answers[q.id];
        if (ans === undefined || ans === null || ans === '') return false;
        if (Array.isArray(ans) && ans.length === 0) return false;
        if (typeof ans === 'object' && !Array.isArray(ans)) {
            if (!ans.selected) return false;
        }
    }
    return true;
};

// Hitung progress pengisian
const progressStats = computed(() => {
    const total = props.questions.length;
    if (total === 0) return { answered: 0, total: 0, percentage: 0 };

    let answered = 0;
    for (const q of props.questions) {
        const ans = form.answers[q.id];
        if (ans !== undefined && ans !== null && ans !== '') {
            if (Array.isArray(ans)) {
                if (ans.length > 0) answered++;
            } else if (typeof ans === 'object') {
                if (ans.selected) answered++;
            } else {
                answered++;
            }
        }
    }

    return {
        answered,
        total,
        percentage: Math.round((answered / total) * 100),
    };
});

// Helper multiple_choice / checkbox
const isCheckboxChecked = (qId, optionText) => {
    return Array.isArray(form.answers[qId]) && form.answers[qId].includes(optionText);
};

const toggleCheckboxOption = (qId, optionText) => {
    if (!Array.isArray(form.answers[qId])) {
        form.answers[qId] = [];
    }
    const idx = form.answers[qId].indexOf(optionText);
    if (idx > -1) {
        form.answers[qId].splice(idx, 1);
    } else {
        form.answers[qId].push(optionText);
    }
};

// Submit jawaban kuesioner prodi
const submitKuesionerProdi = () => {
    if (!isAllRequiredAnswered()) {
        Swal.fire({
            icon: 'warning',
            title: 'Jawaban Belum Lengkap',
            text: 'Mohon lengkapi seluruh pertanyaan bertanda bintang (*) sebelum menyimpan.',
            confirmButtonColor: '#005B3C',
        });
        return;
    }

    Swal.fire({
        title: 'Kirim Jawaban Kuesioner Prodi?',
        text: 'Pastikan seluruh jawaban Anda sudah sesuai. Jawaban Anda sangat bermanfaat bagi evaluasi kurikulum program studi.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#005B3C',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Simpan Jawaban',
        cancelButtonText: 'Periksa Kembali',
    }).then((result) => {
        if (result.isConfirmed) {
            form.post('/alumni/kuesioner-prodi', {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Tersimpan!',
                        text: 'Jawaban kuesioner program studi berhasil disimpan. Terima kasih atas partisipasi Anda!',
                        confirmButtonColor: '#005B3C',
                    });
                },
            });
        }
    });
};
</script>

<template>
    <Head :title="'Kuesioner Program Studi ' + (prodi?.nama_prodi || '')" />

    <div class="min-h-screen bg-[#E8F5E9] font-sans text-gray-900 antialiased flex flex-col relative pb-28 md:pb-32">
        <!-- Navigasi Utama Terpadu Alumni -->
        <Navbar />

        <!-- Main Form Area -->
        <main class="flex-1 px-4 sm:px-6 w-full max-w-4xl mx-auto mt-4 sm:mt-8">
            
            <!-- Banner Judul Kuesioner Prodi -->
            <div class="mb-6 sm:mb-8 text-center bg-[#005B3C] p-5 sm:p-8 rounded-3xl text-white shadow-sm w-full">
                <span class="inline-block bg-[#FFD700] text-[#005B3C] text-[11px] sm:text-xs font-black uppercase px-3.5 py-1 rounded-full mb-2 tracking-wider shadow-xs">
                    Evaluasi Khusus Program Studi
                </span>
                <h1 class="text-2xl sm:text-3xl md:text-4xl font-black tracking-tight mb-2">
                    {{ prodi ? 'Kuesioner Program Studi ' + prodi.nama_prodi : 'Kuesioner Program Studi' }}
                </h1>
                <p class="text-green-100 font-medium text-xs sm:text-sm max-w-xl mx-auto mb-4">
                    Umpan balik Anda sangat berarti untuk akreditasi, pengembangan materi kurikulum, serta fasilitas akademik di program studi Anda. Kolom bertanda <span class="text-[#FFD700] font-black">*</span> wajib diisi.
                </p>

                <!-- Status Pengisian -->
                <div class="max-w-md mx-auto bg-white/10 backdrop-blur-xs p-3 rounded-2xl border border-white/15">
                    <div class="flex items-center justify-between text-xs font-bold text-emerald-100 mb-1.5">
                        <span>Progress Pengisian</span>
                        <span>{{ progressStats.answered }} dari {{ progressStats.total }} Soal ({{ progressStats.percentage }}%)</span>
                    </div>
                    <div class="w-full bg-black/20 rounded-full h-2 overflow-hidden">
                        <div 
                            class="bg-[#FFD700] h-2 rounded-full transition-all duration-500" 
                            :style="{ width: progressStats.percentage + '%' }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Pesan jika prodi belum memiliki pertanyaan -->
            <div v-if="questions.length === 0" class="bg-white rounded-3xl p-8 sm:p-12 text-center shadow-xs border border-gray-100">
                <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-[#005B3C] flex items-center justify-center mx-auto mb-4 text-2xl font-black">
                    🎓
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Belum Ada Kuesioner Khusus Prodi</h3>
                <p class="text-gray-500 text-sm max-w-md mx-auto mb-6">
                    Program Studi <strong>{{ prodi?.nama_prodi || 'Anda' }}</strong> saat ini belum menambahkan butir kuesioner khusus. Anda dapat kembali ke Dashboard atau menyelesaikan kuesioner universitas lainnya.
                </p>
                <Link 
                    href="/alumni/dashboard" 
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#005B3C] hover:bg-[#00482f] text-white font-bold rounded-xl shadow-xs transition-all text-sm"
                >
                    ← Kembali ke Dashboard
                </Link>
            </div>

            <!-- Form Pertanyaan Dikelompokkan per Section -->
            <form v-else @submit.prevent="submitKuesionerProdi" class="space-y-8">
                <div 
                    v-for="sec in groupedSections" 
                    :key="sec.id" 
                    class="space-y-4 sm:space-y-6"
                >
                    <!-- Header Section Card -->
                    <div class="bg-white border-l-4 border-[#005B3C] p-5 sm:p-6 rounded-2xl shadow-xs">
                        <div class="flex items-center gap-2 text-xs font-black text-[#005B3C] uppercase tracking-wider">
                            <span>Bagian {{ sec.order }}</span>
                            <span>•</span>
                            <span>Kuesioner {{ prodi?.nama_prodi }}</span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-black text-gray-900 mt-1">
                            {{ sec.title }}
                        </h2>
                        <p v-if="sec.description" class="text-xs sm:text-sm text-gray-600 mt-1">
                            {{ sec.description }}
                        </p>
                    </div>

                    <!-- Butir-butir Pertanyaan di dalam Section ini -->
                    <div 
                        v-for="q in sec.questions" 
                        :key="q.id" 
                        class="bg-white p-5 sm:p-8 rounded-3xl shadow-sm border border-gray-100"
                    >
                        <!-- Header Soal -->
                        <h3 class="text-base sm:text-xl font-black text-gray-800 mb-4 leading-snug flex items-start gap-3">
                            <span class="shrink-0 bg-[#FFD700] text-[#005B3C] px-2.5 py-1 rounded-xl text-xs sm:text-sm font-black shadow-2xs">
                                {{ q.code }}
                            </span>
                            <span>
                                {{ q.question_text }}
                                <span v-if="q.is_required" class="text-red-500 font-black ml-0.5">*</span>
                            </span>
                        </h3>

                        <!-- TIPE 1: Pilihan Tunggal (Radio / Single Choice) -->
                        <div v-if="q.type === 'single_choice' || q.type === 'radio'">
                            <div class="inline-block mb-3 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-[11px] font-bold text-emerald-800">
                                Pilih satu jawaban
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label v-for="opt in q.options" :key="opt.id" class="cursor-pointer group block select-none">
                                    <input 
                                        type="radio" 
                                        :name="'q_' + q.id" 
                                        :value="opt.option_text" 
                                        v-model="form.answers[q.id]" 
                                        class="sr-only"
                                    >
                                    <div 
                                        class="p-4 rounded-2xl font-bold text-sm sm:text-base transition-all flex flex-col items-center justify-center text-center shadow-xs"
                                        :class="[
                                            form.answers[q.id] === opt.option_text
                                                ? 'bg-[#005B3C] text-white shadow-md'
                                                : 'bg-gray-50 text-gray-700 hover:bg-emerald-50 hover:text-[#005B3C]'
                                        ]"
                                    >
                                        <span>{{ opt.option_text }}</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- TIPE 2: Pilihan Ganda (Checkbox / Multiple Choice) -->
                        <div v-else-if="q.type === 'multiple_choice' || q.type === 'checkbox'">
                            <div class="inline-block mb-3 px-3 py-1 rounded-full bg-emerald-100/70 border border-emerald-300 text-[11px] font-bold text-[#005B3C]">
                                Jawaban bisa lebih dari satu
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label v-for="opt in q.options" :key="opt.id" class="cursor-pointer group block select-none">
                                    <input 
                                        type="checkbox" 
                                        :value="opt.option_text" 
                                        :checked="isCheckboxChecked(q.id, opt.option_text)"
                                        @change="toggleCheckboxOption(q.id, opt.option_text)"
                                        class="sr-only"
                                    >
                                    <div 
                                        class="p-4 rounded-2xl font-bold text-sm sm:text-base transition-all flex flex-col items-center justify-center text-center shadow-xs"
                                        :class="[
                                            isCheckboxChecked(q.id, opt.option_text)
                                                ? 'bg-[#005B3C] text-white shadow-md' 
                                                : 'bg-gray-50 text-gray-700 hover:bg-emerald-50 hover:text-[#005B3C]'
                                        ]"
                                    >
                                        <span>{{ opt.option_text }}</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- TIPE 3: Isian Teks Bebas / Data Akademik Otomatis -->
                        <div v-else-if="q.type === 'text'">
                            <!-- Khusus Data Akademik Otomatis (Nama, NIM, Tahun Kelulusan) -->
                            <div v-if="isAcademicField(q)" class="space-y-2">
                                <div class="relative max-w-xl">
                                    <input 
                                        type="text" 
                                        v-model="form.answers[q.id]" 
                                        readonly 
                                        class="w-full rounded-2xl bg-gray-100/80 text-gray-800 font-semibold p-4 pr-36 border border-gray-200 cursor-not-allowed text-sm sm:text-base select-none shadow-2xs"
                                    />
                                    <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5 text-xs font-bold text-emerald-800 bg-emerald-100/90 px-3 py-1 rounded-full border border-emerald-300/70 select-none pointer-events-none">
                                        <span>✓</span>
                                        <span>Data Akademik</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 font-medium">
                                    Kolom ini diambil secara otomatis dari pangkalan Data Akademik resmi Anda.
                                </p>
                            </div>

                            <!-- Input Teks Bebas Standar -->
                            <textarea 
                                v-else
                                v-model="form.answers[q.id]" 
                                rows="3" 
                                :required="q.is_required"
                                class="w-full rounded-2xl bg-gray-50 p-4 text-gray-900 font-medium focus:bg-white focus:ring-2 focus:ring-[#005B3C] transition-all text-sm sm:text-base border-0 shadow-xs"
                                placeholder="Tuliskan jawaban atau saran Anda di sini..."
                            ></textarea>
                        </div>

                        <!-- TIPE 4: Isian Angka -->
                        <div v-else-if="q.type === 'number'">
                            <input 
                                type="number" 
                                v-model="form.answers[q.id]" 
                                :required="q.is_required"
                                @keydown="filterNumberInput"
                                min="0"
                                class="w-full sm:max-w-xs rounded-2xl bg-gray-50 p-4 text-gray-900 font-black font-mono focus:bg-white focus:ring-2 focus:ring-[#005B3C] transition-all text-lg sm:text-xl text-center border-0 shadow-xs"
                                placeholder="0"
                            >
                        </div>

                        <!-- TIPE: Tanggal / Date -->
                        <div v-else-if="q.type === 'date'">
                            <input 
                                type="date" 
                                v-model="form.answers[q.id]" 
                                :required="q.is_required"
                                class="w-full sm:max-w-xs rounded-2xl bg-gray-50 p-4 text-gray-900 font-bold focus:bg-white focus:ring-2 focus:ring-[#005B3C] transition-all text-sm sm:text-base border-0 shadow-xs"
                            >
                        </div>

                        <!-- TIPE 5: Skala Penilaian Skor 1 s/d 5 -->
                        <div v-else-if="q.type === 'rating_5'" class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-emerald-50 rounded-2xl text-xs sm:text-sm font-bold text-emerald-950">
                                <span>1: Sangat Rendah / Kurang</span>
                                <span>5: Sangat Tinggi / Baik</span>
                            </div>
                            <div class="flex items-center justify-between gap-2 p-3 bg-gray-50 rounded-2xl">
                                <label v-for="score in 5" :key="score" class="cursor-pointer select-none flex-1 flex flex-col items-center">
                                    <input 
                                        type="radio" 
                                        :name="'q_' + q.id" 
                                        :value="score" 
                                        v-model="form.answers[q.id]" 
                                        class="sr-only"
                                    >
                                    <div 
                                        class="w-full h-11 sm:h-12 rounded-xl flex items-center justify-center font-black text-base sm:text-lg transition-all"
                                        :class="[
                                            Number(form.answers[q.id]) === score
                                                ? 'bg-[#005B3C] text-white shadow-sm'
                                                : 'bg-white text-gray-700 hover:bg-emerald-50'
                                        ]"
                                    >
                                        {{ score }}
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- TIPE 6: Radio Input dengan Titik Isian -->
                        <div v-else-if="q.type === 'radio_input'">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label v-for="opt in q.options" :key="opt.id" class="cursor-pointer group block select-none">
                                    <input 
                                        type="radio" 
                                        :name="'q_' + q.id" 
                                        :value="opt.option_text" 
                                        v-model="form.answers[q.id]" 
                                        class="sr-only"
                                    >
                                    <div 
                                        class="p-4 rounded-2xl font-bold text-sm sm:text-base transition-all flex flex-col items-center justify-center text-center shadow-xs"
                                        :class="[
                                            form.answers[q.id] === opt.option_text
                                                ? 'bg-[#005B3C] text-white shadow-md'
                                                : 'bg-gray-50 text-gray-700 hover:bg-emerald-50 hover:text-[#005B3C]'
                                        ]"
                                    >
                                        <span>{{ opt.option_text }}</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tombol Submit Bawah -->
                <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-3xl shadow-sm border border-gray-100">
                    <div class="text-xs sm:text-sm text-gray-500 text-center sm:text-left">
                        Pastikan seluruh pertanyaan wajib telah terisi sebelum menyimpan.
                    </div>
                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="w-full sm:w-auto px-8 py-3.5 bg-[#005B3C] hover:bg-[#00482f] text-white font-black rounded-2xl shadow-md transition-all text-sm sm:text-base cursor-pointer disabled:opacity-50"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Jawaban Kuesioner Prodi ✓' }}
                    </button>
                </div>
            </form>
        </main>
    </div>
</template>
