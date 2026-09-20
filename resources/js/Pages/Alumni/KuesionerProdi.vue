<!--
  Halaman: Kuesioner Khusus Program Studi (Frontend Alumni)
  File: resources/js/Pages/Alumni/KuesionerProdi.vue
  Fungsi: Menampilkan kuesioner program studi dengan antarmuka yang setara dengan Kuesioner Universitas:
          - Layout lebar desktop (max-w-6xl / max-w-7xl)
          - Stepper tahapan seksi horizontal di atas
          - Banner judul dan deskripsi seksi
          - Matriks tabel untuk butir skala 1-5 (identik dengan F2 Universitas)
          - Kartu interaktif untuk pilihan tunggal, ganda, teks, angka, dan tanggal
          - Navigasi Kembali dan Lanjut / Selesai
-->
<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import Swal from 'sweetalert2';
import Navbar from './Components/Navbar.vue';
import Stepper from './Components/Kuesioner/Stepper.vue';
import Banner from './Components/Kuesioner/Banner.vue';
import Navigasi from './Components/Kuesioner/Navigasi.vue';

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

const STORAGE_ANSWERS_KEY = 'tracerstudy_alumni_kuesioner_prodi_answers';
const STORAGE_SECTION_KEY = 'tracerstudy_alumni_kuesioner_prodi_sec';

// Inisialisasi formulir jawaban (gabung server + cache lokal jika ada)
const getInitialAnswers = () => {
    const base = { ...(props.initialAnswers || {}) };
    if (typeof window !== 'undefined') {
        try {
            const cached = localStorage.getItem(STORAGE_ANSWERS_KEY);
            if (cached) {
                const parsed = JSON.parse(cached);
                Object.assign(base, parsed);
            }
        } catch (e) {
            console.error('Gagal membaca cache jawaban prodi:', e);
        }
    }
    return base;
};

const getInitialSectionIndex = () => {
    if (typeof window !== 'undefined') {
        try {
            const cached = localStorage.getItem(STORAGE_SECTION_KEY);
            if (cached !== null) {
                const num = parseInt(cached, 10);
                if (!isNaN(num) && num >= 0) return num;
            }
        } catch (e) {
            console.error('Gagal membaca cache section prodi:', e);
        }
    }
    return 0;
};

const form = useForm({
    answers: getInitialAnswers(),
});

const activeSectionIndex = ref(getInitialSectionIndex());
const completedSectionIndices = ref(new Set());

// Mengelompokkan pertanyaan berdasarkan Section
const groupedSections = computed(() => {
    if (props.sections && props.sections.length > 0) {
        return props.sections.map((sec, sIdx) => {
            const secQuestions = (sec.questions && sec.questions.length > 0)
                ? sec.questions
                : props.questions.filter(q => q.prodi_question_section_id === sec.id);
            
            // Urutkan pertanyaan berdasarkan order
            const sorted = [...secQuestions].sort((a, b) => (a.order || 0) - (b.order || 0));

            return {
                ...sec,
                displayOrder: sIdx + 1,
                questions: sorted,
            };
        });
    }

    // Fallback jika belum ada section terpisah
    return [{
        id: 'default',
        order: 1,
        displayOrder: 1,
        title: 'Kuesioner Program Studi',
        description: 'Pertanyaan evaluasi dan pengembangan program studi.',
        questions: props.questions,
    }];
});

const currentSection = computed(() => {
    if (!groupedSections.value || groupedSections.value.length === 0) return null;
    return groupedSections.value[activeSectionIndex.value] || groupedSections.value[0];
});

// Mengelompokkan butir pertanyaan di seksi aktif:
// Butir-butir bertipe rating_5 yang berurutan akan digabung menjadi 1 blok matriks tabel (seperti F2)
const currentSectionBlocks = computed(() => {
    const list = currentSection.value?.questions || [];
    const blocks = [];
    let currentRatingGroup = null;

    for (let i = 0; i < list.length; i++) {
        const q = list[i];
        if (q.type === 'rating_5') {
            if (!currentRatingGroup) {
                currentRatingGroup = {
                    type: 'rating_matrix',
                    questions: [],
                };
                blocks.push(currentRatingGroup);
            }
            currentRatingGroup.questions.push(q);
        } else {
            currentRatingGroup = null;
            blocks.push({
                type: 'standard_question',
                question: q,
            });
        }
    }

    return blocks;
});

// Helper label skala 1-5 untuk matriks tabel
const getMatrixScaleOptions = (questions) => {
    if (!questions || questions.length === 0) {
        return [
            { score: 1, label: 'Sangat Tidak Setuju' },
            { score: 2, label: 'Tidak Setuju' },
            { score: 3, label: 'Netral' },
            { score: 4, label: 'Setuju' },
            { score: 5, label: 'Sangat Setuju' },
        ];
    }

    const firstWithOpts = questions.find(q => q.options && q.options.length >= 5);
    if (firstWithOpts) {
        return firstWithOpts.options.slice(0, 5).map((opt, idx) => ({
            score: idx + 1,
            label: (opt.option_text || '').replace(/^\d+\s*=\s*/, '').trim(),
        }));
    }

    return [
        { score: 1, label: 'Sangat Tidak Setuju' },
        { score: 2, label: 'Tidak Setuju' },
        { score: 3, label: 'Netral' },
        { score: 4, label: 'Setuju' },
        { score: 5, label: 'Sangat Setuju' },
    ];
};

const filterNumberInput = (event) => {
    if (['e', 'E', '+', '-', '.'].includes(event.key)) {
        event.preventDefault();
    }
};

// Validasi kelengkapan per butir pertanyaan
const isQuestionAnswered = (q) => {
    if (!q) return false;
    const ans = form.answers[q.id];
    if (ans === undefined || ans === null || ans === '') return false;
    if (Array.isArray(ans)) return ans.length > 0;
    if (typeof ans === 'object') return !!ans.selected;
    return true;
};

// Validasi kelengkapan satu seksi
const isSectionAnswered = (section) => {
    if (!section || !section.questions || section.questions.length === 0) return true;
    const requiredQuestions = section.questions.filter(q => q.is_required);
    if (requiredQuestions.length === 0) return true;
    return requiredQuestions.every(q => isQuestionAnswered(q));
};

// Status kelengkapan seksi untuk stepper
const isSectionCompleted = (index) => {
    const sec = groupedSections.value[index];
    if (!sec) return false;
    return isSectionAnswered(sec) || completedSectionIndices.value.has(index);
};

const isLineCompleted = (index) => {
    const currentCompleted = isSectionCompleted(index);
    const nextCompleted = isSectionCompleted(index + 1);
    const currentActive = (activeSectionIndex.value === index);
    const nextActive = (activeSectionIndex.value === index + 1);

    return (currentCompleted && (nextCompleted || nextActive)) || 
           (index < activeSectionIndex.value && currentCompleted);
};

// Sinkronisasi status kelengkapan
const syncSectionCompletion = () => {
    const newCompleted = new Set();
    groupedSections.value.forEach((sec, idx) => {
        if (isSectionAnswered(sec)) {
            newCompleted.add(idx);
        }
    });
    completedSectionIndices.value = newCompleted;
};

// Hitung total progress
const progressStats = computed(() => {
    const total = props.questions.length;
    if (total === 0) return { answered: 0, total: 0, percentage: 0 };

    let answered = 0;
    for (const q of props.questions) {
        if (isQuestionAnswered(q)) {
            answered++;
        }
    }

    return {
        answered,
        total,
        percentage: Math.round((answered / total) * 100),
    };
});

// Helper checkbox
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

// Navigasi Section
const setSection = (index) => {
    if (currentSection.value && isSectionAnswered(currentSection.value)) {
        completedSectionIndices.value.add(activeSectionIndex.value);
    }
    activeSectionIndex.value = index;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const prevSection = () => {
    if (activeSectionIndex.value > 0) {
        activeSectionIndex.value--;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const isLastSection = computed(() => {
    return activeSectionIndex.value >= groupedSections.value.length - 1;
});

const handleNextOrSubmit = () => {
    if (isLastSection.value) {
        // Validasi seluruh pertanyaan wajib
        const requiredQuestions = props.questions.filter(q => q.is_required);
        const missing = requiredQuestions.filter(q => !isQuestionAnswered(q));

        if (missing.length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Jawaban Belum Lengkap',
                text: `Masih ada ${missing.length} pertanyaan wajib yang belum diisi. Mohon lengkapi sebelum mengirim.`,
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
                        completedSectionIndices.value.add(activeSectionIndex.value);
                        if (typeof window !== 'undefined') {
                            localStorage.removeItem(STORAGE_ANSWERS_KEY);
                            localStorage.removeItem(STORAGE_SECTION_KEY);
                        }
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
    } else {
        // Lanjut ke section berikutnya
        completedSectionIndices.value.add(activeSectionIndex.value);
        form.post('/alumni/kuesioner-prodi', {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                completedSectionIndices.value.add(activeSectionIndex.value);
                activeSectionIndex.value++;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }
};

// Lifecycle & Watchers
onMounted(() => {
    if (activeSectionIndex.value >= groupedSections.value.length) {
        activeSectionIndex.value = 0;
    }
    syncSectionCompletion();
});

watch(activeSectionIndex, (newIdx) => {
    if (typeof window !== 'undefined') {
        localStorage.setItem(STORAGE_SECTION_KEY, newIdx.toString());
    }
});

let draftTimer = null;
watch(() => form.answers, (newAnswers) => {
    if (typeof window !== 'undefined') {
        clearTimeout(draftTimer);
        draftTimer = setTimeout(() => {
            try {
                localStorage.setItem(STORAGE_ANSWERS_KEY, JSON.stringify(newAnswers));
            } catch (e) {
                console.error('Gagal menyimpan cache jawaban prodi:', e);
            }
        }, 800);
    }
    syncSectionCompletion();
}, { deep: true });
</script>

<<template>
    <Head :title="'Kuesioner Program Studi ' + (prodi?.nama_prodi || '')" />

    <div class="min-h-screen bg-[#E8F5E9] font-sans text-gray-900 antialiased flex flex-col relative pb-20 md:pb-6">
        <!-- Header Atas: Navbar & Stepper Tahapan -->
        <header class="sticky top-0 z-50 w-full shadow-md bg-white">
            <!-- 1. Navbar Atas -->
            <Navbar />

            <!-- 2. Stepper Tahapan (Bulatan Angka 1 s/d N & Judul Bagian) -->
            <Stepper 
                v-if="groupedSections && groupedSections.length > 0"
                :sections="groupedSections"
                :active-index="activeSectionIndex"
                :completed-indices="completedSectionIndices"
                :is-section-completed="isSectionCompleted"
                :is-line-completed="isLineCompleted"
                @select-section="setSection"
            />
        </header>

        <!-- Area Konten Formulir Utama (Ukuran Sama Persis Kuesioner Universitas) -->
        <main class="flex-1 px-3 py-4 sm:px-4 md:px-6 lg:px-8 w-full mx-auto mt-3 sm:mt-6 md:mt-8 max-w-6xl xl:max-w-7xl">
            
            <!-- Pesan jika prodi belum memiliki pertanyaan -->
            <div v-if="questions.length === 0" class="bg-white rounded-3xl p-8 sm:p-12 text-center shadow-xs border border-gray-100">
                <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-[#005B3C] flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path></svg>
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

            <!-- Form Kuesioner Aktif Berdasarkan Seksi -->
            <div v-else-if="currentSection">
                <!-- 3. Banner Judul Bagian -->
                <Banner 
                    :current-index="activeSectionIndex"
                    :total-sections="groupedSections.length"
                    :title="currentSection.title"
                    :description="currentSection.description"
                    :badge-text="'Kuesioner Program Studi ' + (prodi?.nama_prodi || '')"
                />

                <!-- Konten Formulir Seksi Aktif -->
                <form @submit.prevent="handleNextOrSubmit" class="space-y-4 sm:space-y-6">
                    <template v-for="(block, bIdx) in currentSectionBlocks" :key="'block_' + bIdx">
                        
                        <!-- =================================================================================== -->
                        <!-- 1. MATRIKS TABEL SKALA 1-5 (Persis Format TabelF2 Kuesioner Universitas)            -->
                        <!-- =================================================================================== -->
                        <div 
                            v-if="block.type === 'rating_matrix'" 
                            class="bg-white rounded-2xl sm:rounded-3xl shadow-xs border border-gray-100 overflow-hidden"
                        >
                            <div class="p-4 sm:p-6 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="inline-block bg-[#005B3C] text-white text-[10px] sm:text-[11px] font-black uppercase px-2.5 py-0.5 rounded-md tracking-wider shadow-2xs">
                                        Skala 1 - 5
                                    </span>
                                    <span class="text-xs font-bold text-gray-700">
                                        Pilihlah skor 1 s/d 5 untuk setiap butir penilaian di bawah ini:
                                    </span>
                                </div>
                            </div>

                            <div class="overflow-x-auto md:overflow-x-visible">
                                <table class="w-full text-left border-collapse min-w-[620px] md:min-w-0">
                                    <thead>
                                        <tr class="bg-gray-50/90 border-b border-gray-200/80 text-gray-700 text-xs sm:text-sm">
                                            <th class="py-3.5 px-3 sm:px-4 font-black uppercase text-gray-600 text-center w-12">No</th>
                                            <th class="py-3.5 px-3 sm:px-4 font-black text-gray-800">Butir Pernyataan / Evaluasi</th>
                                            <th 
                                                v-for="opt in getMatrixScaleOptions(block.questions)" 
                                                :key="opt.score"
                                                class="py-3.5 px-2 text-center font-black text-xs w-20 sm:w-24 md:w-28"
                                            >
                                                <div class="font-extrabold text-gray-900 text-sm sm:text-base">{{ opt.score }}</div>
                                                <div class="text-[10px] sm:text-[11px] font-medium text-gray-500 leading-tight mt-0.5">
                                                    {{ opt.label }}
                                                </div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr 
                                            v-for="(q, qIdx) in block.questions" 
                                            :key="q.id" 
                                            class="transition-colors hover:bg-emerald-50/20"
                                            :class="{'bg-emerald-50/30': isQuestionAnswered(q)}"
                                        >
                                            <!-- Nomor Urut -->
                                            <td class="py-3.5 px-3 sm:px-4 text-center font-black text-xs sm:text-sm text-gray-500">
                                                {{ qIdx + 1 }}
                                            </td>

                                            <!-- Teks Pertanyaan & Kode -->
                                            <td class="py-3.5 px-3 sm:px-4">
                                                <div class="flex items-start gap-2.5">
                                                    <span class="font-mono text-[11px] font-black px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 border border-gray-200/60 shrink-0 mt-0.5">
                                                        {{ q.code }}
                                                    </span>
                                                    <span class="font-bold text-gray-900 text-xs sm:text-sm md:text-base leading-snug">
                                                        {{ q.question_text }}
                                                        <span v-if="q.is_required" class="text-red-500 font-black ml-0.5">*</span>
                                                    </span>
                                                </div>
                                            </td>

                                            <!-- 5 Pilihan Skor Bulat (Persis F2) -->
                                            <td 
                                                v-for="opt in getMatrixScaleOptions(block.questions)" 
                                                :key="opt.score"
                                                class="py-3.5 px-2 text-center"
                                            >
                                                <label class="cursor-pointer inline-flex items-center justify-center p-1 group">
                                                    <input 
                                                        type="radio" 
                                                        :name="'q_' + q.id" 
                                                        :value="opt.score" 
                                                        v-model="form.answers[q.id]" 
                                                        class="sr-only"
                                                    >
                                                    <div 
                                                        class="w-8 h-8 sm:w-9 sm:h-9 md:w-10 md:h-10 rounded-full flex items-center justify-center font-black text-xs sm:text-sm md:text-base transition-all shadow-2xs"
                                                        :class="[
                                                            Number(form.answers[q.id]) === opt.score
                                                                ? 'bg-[#005B3C] text-white ring-2 ring-offset-1 ring-[#005B3C] shadow-sm scale-105 font-black'
                                                                : 'bg-white text-gray-700 border border-gray-300 hover:bg-emerald-50 hover:border-emerald-500'
                                                        ]"
                                                    >
                                                        {{ opt.score }}
                                                    </div>
                                                </label>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- =================================================================================== -->
                        <!-- 2. KARTU HEADER PETUNJUK (Jika Tipe Header)                                         -->
                        <!-- =================================================================================== -->
                        <div 
                            v-else-if="block.question?.type === 'header'"
                            class="bg-white p-5 sm:p-7 md:p-8 rounded-2xl sm:rounded-3xl shadow-xs border border-gray-100"
                        >
                            <div class="flex items-center gap-2 mb-1.5">
                                <span class="inline-block bg-[#005B3C] text-white text-[10px] sm:text-[11px] font-black uppercase px-2.5 py-0.5 rounded-md tracking-wider shadow-2xs">
                                    {{ block.question.code || 'Petunjuk' }}
                                </span>
                            </div>
                            <h3 class="text-base sm:text-lg md:text-xl font-black text-gray-900 leading-snug">
                                {{ block.question.question_text }}
                            </h3>
                        </div>

                        <!-- =================================================================================== -->
                        <!-- 3. KARTU PERTANYAAN NON-MATRIX (Pilihan Tunggal, Ganda, Teks, Angka, Tanggal)       -->
                        <!-- =================================================================================== -->
                        <div 
                            v-else-if="block.type === 'standard_question'" 
                            class="bg-white p-5 sm:p-7 md:p-8 rounded-2xl sm:rounded-3xl shadow-xs border border-gray-100"
                        >
                            <!-- Header Soal -->
                            <h3 class="text-base sm:text-lg md:text-xl font-black text-gray-900 mb-4 leading-snug flex items-start gap-3">
                                <span class="shrink-0 bg-[#FFD700] text-[#005B3C] px-2.5 py-1 rounded-xl text-xs sm:text-sm font-black shadow-2xs">
                                    {{ block.question.code }}
                                </span>
                                <span>
                                    {{ block.question.question_text }}
                                    <span v-if="block.question.is_required" class="text-red-500 font-black ml-0.5">*</span>
                                </span>
                            </h3>

                            <!-- Pilihan Tunggal (Radio / Single Choice) -->
                            <div v-if="block.question.type === 'single_choice' || block.question.type === 'radio'">
                                <div class="inline-block mb-3 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-[11px] font-bold text-emerald-800">
                                    Pilih satu jawaban
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                    <label v-for="opt in block.question.options" :key="opt.id" class="cursor-pointer group block select-none">
                                        <input 
                                            type="radio" 
                                            :name="'q_' + block.question.id" 
                                            :value="opt.option_text" 
                                            v-model="form.answers[block.question.id]" 
                                            class="sr-only"
                                        >
                                        <div 
                                            class="p-4 rounded-2xl font-bold text-xs sm:text-sm md:text-base transition-all flex items-center justify-center text-center shadow-2xs min-h-[56px]"
                                            :class="[
                                                form.answers[block.question.id] === opt.option_text
                                                    ? 'bg-[#005B3C] text-white shadow-md ring-2 ring-emerald-500'
                                                    : 'bg-gray-50 text-gray-700 hover:bg-emerald-50 hover:text-[#005B3C] border border-gray-100'
                                            ]"
                                        >
                                            <span>{{ opt.option_text }}</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Pilihan Ganda (Checkbox / Multiple Choice) -->
                            <div v-else-if="block.question.type === 'multiple_choice' || block.question.type === 'checkbox'">
                                <div class="inline-block mb-3 px-3 py-1 rounded-full bg-emerald-100/70 border border-emerald-300 text-[11px] font-bold text-[#005B3C]">
                                    Jawaban bisa lebih dari satu
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                    <label v-for="opt in block.question.options" :key="opt.id" class="cursor-pointer group block select-none">
                                        <input 
                                            type="checkbox" 
                                            :value="opt.option_text" 
                                            :checked="isCheckboxChecked(block.question.id, opt.option_text)"
                                            @change="toggleCheckboxOption(block.question.id, opt.option_text)"
                                            class="sr-only"
                                        >
                                        <div 
                                            class="p-4 rounded-2xl font-bold text-xs sm:text-sm md:text-base transition-all flex items-center justify-center text-center shadow-2xs min-h-[56px]"
                                            :class="[
                                                isCheckboxChecked(block.question.id, opt.option_text)
                                                    ? 'bg-[#005B3C] text-white shadow-md ring-2 ring-emerald-500' 
                                                    : 'bg-gray-50 text-gray-700 hover:bg-emerald-50 hover:text-[#005B3C] border border-gray-100'
                                            ]"
                                        >
                                            <span>{{ opt.option_text }}</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Isian Teks Bebas -->
                            <div v-else-if="block.question.type === 'text'">
                                <textarea 
                                    v-model="form.answers[block.question.id]" 
                                    rows="3" 
                                    :required="block.question.is_required"
                                    class="w-full rounded-2xl bg-gray-50 p-4 text-gray-900 font-medium focus:bg-white focus:ring-2 focus:ring-[#005B3C] transition-all text-sm sm:text-base border border-gray-200 shadow-2xs"
                                    placeholder="Tuliskan jawaban atau saran Anda di sini..."
                                ></textarea>
                            </div>

                            <!-- Isian Angka -->
                            <div v-else-if="block.question.type === 'number'">
                                <input 
                                    type="number" 
                                    v-model="form.answers[block.question.id]" 
                                    :required="block.question.is_required"
                                    @keydown="filterNumberInput"
                                    min="0"
                                    class="w-full sm:max-w-xs rounded-2xl bg-gray-50 p-4 text-gray-900 font-black font-mono focus:bg-white focus:ring-2 focus:ring-[#005B3C] transition-all text-lg sm:text-xl text-center border border-gray-200 shadow-2xs"
                                    placeholder="0"
                                >
                            </div>

                            <!-- Tanggal / Date -->
                            <div v-else-if="block.question.type === 'date'">
                                <input 
                                    type="date" 
                                    v-model="form.answers[block.question.id]" 
                                    :required="block.question.is_required"
                                    class="w-full sm:max-w-xs rounded-2xl bg-gray-50 p-4 text-gray-900 font-bold focus:bg-white focus:ring-2 focus:ring-[#005B3C] transition-all text-sm sm:text-base border border-gray-200 shadow-2xs"
                                >
                            </div>
                        </div>
                    </template>

                    <!-- Tombol Navigasi Desktop & Mobile (< Kembali & > Lanjut/Selesai) -->
                    <Navigasi 
                        :active-section-index="activeSectionIndex"
                        :total-sections="groupedSections.length"
                        :is-last-visible-section="isLastSection"
                        :is-processing="form.processing"
                        @prev="prevSection"
                    />
                </form>
            </div>
        </main>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    display: none;
}
.custom-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
