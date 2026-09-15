<!--
  Halaman: Kuesioner Tracer Study Alumni (Komponen Induk)
  File: resources/js/Pages/Alumni/Kuesioner.vue
  Fungsi: Mengorkestrasi state kuesioner, validasi per bagian, persistensi sesi,
          dan alur logika lompatan (jump logic), serta merender sub-komponen modular:
          - Navbar: Header atas dan tombol kembali
          - Stepper: Indikator tahapan bulatan 1 s/d N
          - Banner: Judul dan deskripsi seksi aktif
          - TabelF17: Tabel khusus evaluasi kompetensi A vs B
          - KartuPertanyaan: Renderer butir pertanyaan beserta variasi tipe input
          - Navigasi: Kontrol navigasi desktop & mobile
-->
<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import Navbar from './Components/Kuesioner/Navbar.vue';
import Stepper from './Components/Kuesioner/Stepper.vue';
import Banner from './Components/Kuesioner/Banner.vue';
import TabelF17 from './Components/Kuesioner/TabelF17.vue';
import TabelF2 from './Components/Kuesioner/TabelF2.vue';
import KartuPertanyaan from './Components/Kuesioner/KartuPertanyaan.vue';
import Navigasi from './Components/Kuesioner/Navigasi.vue';

// Props dari backend Laravel
const props = defineProps({
    questionnaire: Object,
    initialAnswers: Object,
    error: String,
});

// Kunci penyimpanan sesi lokal (localStorage)
const SECTION_STORAGE_KEY = 'tracerstudy_alumni_kuesioner_section';
const ANSWERS_STORAGE_KEY = 'tracerstudy_alumni_kuesioner_answers';
const COMPLETED_SECTIONS_STORAGE_KEY = 'tracerstudy_alumni_kuesioner_completed_sections';

// Inisialisasi daftar index section yang sudah selesai dikerjakan dari cache
const getInitialCompletedSections = () => {
    const set = new Set();
    if (typeof window !== 'undefined') {
        try {
            const cached = localStorage.getItem(COMPLETED_SECTIONS_STORAGE_KEY);
            if (cached) {
                const parsed = JSON.parse(cached);
                if (Array.isArray(parsed)) {
                    parsed.forEach(idx => set.add(Number(idx)));
                }
            }
        } catch (e) {
            console.error('Gagal membaca cache completed sections:', e);
        }
    }
    return set;
};

// Inisialisasi jawaban formulir: gabungkan data backend dengan draft lokal
const getInitialAnswers = () => {
    let base = JSON.parse(JSON.stringify(props.initialAnswers || {}));
    if (typeof window !== 'undefined') {
        try {
            const cached = localStorage.getItem(ANSWERS_STORAGE_KEY);
            if (cached) {
                const parsed = JSON.parse(cached);
                base = { ...base, ...parsed };
            }
        } catch (e) {
            console.error('Gagal membaca cache jawaban:', e);
        }
    }

    // Normalisasi struktur radio_input & multiple_number
    if (props.questionnaire?.sections) {
        props.questionnaire.sections.forEach(sec => {
            (sec.subpertanyaans || sec.questions)?.forEach(q => {
                const options = q.detils || q.options;
                if (['radio_input', 'radio_text'].includes(q.type)) {
                    if (!base[q.id] || typeof base[q.id] !== 'object') {
                        base[q.id] = { selected: '', input: '', inputs: {} };
                    } else {
                        if (!base[q.id].inputs || typeof base[q.id].inputs !== 'object') {
                            base[q.id].inputs = {};
                        }
                        if (base[q.id].selected && base[q.id].input !== undefined && options) {
                            const matchedOpt = options.find(o => o.option_text === base[q.id].selected);
                            if (matchedOpt && !base[q.id].inputs[matchedOpt.id]) {
                                base[q.id].inputs[matchedOpt.id] = base[q.id].input;
                            }
                        }
                        if (options) {
                            options.forEach(o => {
                                if (base[q.id].inputs[o.id] === undefined) {
                                    base[q.id].inputs[o.id] = '';
                                }
                            });
                        }
                    }
                }

                if (q.type === 'multiple_number') {
                    if (!base[q.id] || typeof base[q.id] !== 'object') {
                        base[q.id] = {};
                    }
                    if (options) {
                        options.forEach(o => {
                            const optCode = o.kode_opsi || o.code;
                            const val = base[q.id][optCode];
                            if (val !== undefined && val !== null && val !== '' && !isNaN(val)) {
                                const num = parseInt(val, 10);
                                base[q.id][optCode] = (num >= 1000 && num % 1000 === 0) ? (num / 1000) : num;
                            } else {
                                if (base[q.id][optCode] === undefined) {
                                    base[q.id][optCode] = '';
                                }
                            }
                        });
                    }
                }
            });
        });
    }

    return base;
};

// Inisialisasi section aktif
const getInitialSectionIndex = () => {
    if (typeof window !== 'undefined') {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const secParam = urlParams.get('sec');
            if (secParam !== null) {
                const parsed = parseInt(secParam, 10);
                if (!isNaN(parsed) && parsed >= 0) return parsed;
            }
            const cachedSec = localStorage.getItem(SECTION_STORAGE_KEY);
            if (cachedSec !== null) {
                const parsed = parseInt(cachedSec, 10);
                if (!isNaN(parsed) && parsed >= 0) return parsed;
            }
        } catch (e) {
            console.error('Gagal membaca cache section:', e);
        }
    }
    return 0;
};

// State Reaktif Kuesioner
const activeSectionIndex = ref(getInitialSectionIndex());
const completedSectionIndices = ref(getInitialCompletedSections());
const form = useForm({ answers: getInitialAnswers() });

// Simpan daftar section selesai ke localStorage
const saveCompletedSections = () => {
    if (typeof window !== 'undefined') {
        try {
            localStorage.setItem(
                COMPLETED_SECTIONS_STORAGE_KEY,
                JSON.stringify(Array.from(completedSectionIndices.value))
            );
        } catch (e) {
            console.error('Gagal menyimpan cache completed sections:', e);
        }
    }
};

// Section aktif saat ini
const currentSection = computed(() => {
    if (!props.questionnaire?.sections) return null;
    return props.questionnaire.sections[activeSectionIndex.value] || null;
});

// Deteksi khusus Section F2 (Penekanan Metode Pembelajaran)
const isF2Section = computed(() => {
    const list = currentSection.value?.subpertanyaans || currentSection.value?.questions;
    return list?.some(q => (q.kode_pertanyaan || q.code) === 'F2' || ((q.kode_pertanyaan || q.code) && (q.kode_pertanyaan || q.code).startsWith('F21'))) || false;
});

const f2HeaderQuestion = computed(() => {
    const list = currentSection.value?.subpertanyaans || currentSection.value?.questions;
    return list?.find(q => (q.kode_pertanyaan || q.code) === 'F2') || null;
});

const f2QuestionsList = computed(() => {
    const list = currentSection.value?.subpertanyaans || currentSection.value?.questions;
    return list?.filter(q => (q.kode_pertanyaan || q.code) !== 'F2' && q.type !== 'header') || [];
});

// Deteksi khusus Section F17 (Evaluasi Kompetensi Dual Matrix A vs B)
const isF17Section = computed(() => {
    const list = currentSection.value?.subpertanyaans || currentSection.value?.questions;
    return list?.some(q => {
        const c = q.kode_pertanyaan || q.code;
        return c === 'F17' || 
            (c && (c.toLowerCase().startsWith('f17a') || c.toLowerCase().startsWith('f17b') || c.startsWith('F17-'))) || 
            q.type === 'matrix_dual';
    }) || false;
});

const f17Question = computed(() => {
    const list = currentSection.value?.subpertanyaans || currentSection.value?.questions;
    return list?.find(q => (q.kode_pertanyaan || q.code) === 'F17' || q.type === 'matrix_dual') || null;
});

// Helper pembersih nama aspek kompetensi
const getCleanAspectName = (text) => {
    if (!text) return '';
    if (text.includes('—')) return text.split('—')[0].trim();
    if (text.includes('-')) return text.split('-')[0].trim();
    return text;
};

// Pasangan pertanyaan F17 berdampingan (A: Kompetensi yang dikuasai vs B: Kebutuhan saat ini)
const f17AspectPairs = computed(() => {
    if (!isF17Section.value || !currentSection.value) return [];
    const list = currentSection.value.subpertanyaans || currentSection.value.questions || [];
    const questions = list.filter(q => (q.kode_pertanyaan || q.code) !== 'F17' && q.type !== 'header');
    const pairs = [];

    for (let i = 0; i < questions.length; i += 2) {
        const qA = questions[i];
        const qB = questions[i + 1];
        if (!qA || !qB) continue;

        pairs.push({
            aspectNumber: Math.floor(i / 2) + 1,
            aspectName: getCleanAspectName(qA.subpertanyaan || qA.question_text),
            qA,
            qB,
        });
    }
    return pairs;
});

// Hitung berapa aspek F17 yang sudah terisi lengkap (A dan B)
const f17CompletedCount = computed(() => {
    if (!isF17Section.value) return 0;
    return f17AspectPairs.value.filter(pair => {
        const valA = form.answers[pair.qA.id];
        const valB = form.answers[pair.qB.id];
        return valA !== undefined && valA !== null && valA !== '' &&
               valB !== undefined && valB !== null && valB !== '';
    }).length;
});

// Kelompokkan pertanyaan dalam section: buat berpasangan kanan-kiri khusus F6/F7 dan F18
const groupedQuestions = computed(() => {
    const list = currentSection.value?.subpertanyaans || currentSection.value?.questions;
    if (!list) return [];
    const questions = list;
    const groups = [];
    let i = 0;

    while (i < questions.length) {
        const q = questions[i];
        const nextQ = questions[i + 1];
        const qCode = q.kode_pertanyaan || q.code;
        const nextQCode = nextQ ? (nextQ.kode_pertanyaan || nextQ.code) : '';

        // Khusus F6 dan F7: buat berdampingan kanan-kiri (grid 2 kolom)
        if (qCode === 'F6' && nextQ && nextQCode === 'F7') {
            groups.push({
                type: 'pair',
                items: [q, nextQ]
            });
            i += 2;
        } else if (qCode === 'F18a' && nextQ && nextQCode === 'F18b') {
            // Pasangan Studi Lanjut F18A dan F18B
            groups.push({
                type: 'pair',
                items: [q, nextQ]
            });
            i += 2;
        } else if (qCode === 'F18c' && nextQ && nextQCode === 'F18d') {
            // Pasangan Studi Lanjut F18C dan F18D
            groups.push({
                type: 'pair',
                items: [q, nextQ]
            });
            i += 2;
        } else {
            groups.push({
                type: 'single',
                items: [q]
            });
            i += 1;
        }
    }
    return groups;
});

// Evaluasi Logika Percabangan (Branching / Jump Logic)
const questionOptionMap = computed(() => {
    const map = {};
    props.questionnaire?.sections?.forEach(sec => {
        (sec.subpertanyaans || sec.questions)?.forEach(q => {
            (q.detils || q.options)?.forEach(opt => {
                if (opt.jump_to) {
                    map[q.id + '_' + opt.option_text] = opt.jump_to;
                }
            });
        });
    });
    return map;
});

// Menentukan apakah suatu butir pertanyaan terlihat (tidak dilewati oleh alur percabangan)
const isQuestionVisible = (qId) => {
    if (!props.questionnaire?.sections) return true;

    // Aturan khusus F504 -> F502/F505/F505A (jika Ya) vs F506 (jika Tidak)
    let currentQ = null;
    let f504Q = null;
    for (const s of props.questionnaire.sections) {
        for (const q of (s.subpertanyaans || s.questions || [])) {
            if (q.id === qId) currentQ = q;
            if ((q.kode_pertanyaan || q.code) === 'F504') f504Q = q;
        }
    }

    if (currentQ && f504Q) {
        const curCode = currentQ.kode_pertanyaan || currentQ.code;
        if (['F502', 'F505', 'F505A'].includes(curCode)) {
            const ansF504 = form.answers[f504Q.id];
            if (ansF504 !== 'Ya' && ansF504 !== '1') return false;
        } else if (curCode === 'F506') {
            const ansF504 = form.answers[f504Q.id];
            if (ansF504 !== 'Tidak' && ansF504 !== '2') return false;
        }
    }

    for (const sec of props.questionnaire.sections) {
        const questions = sec.subpertanyaans || sec.questions;
        if (!questions) continue;
        for (const q of questions) {
            if (q.id === qId) return true;

            const selectedAnswer = form.answers[q.id];
            if (selectedAnswer) {
                let jumpTarget = null;

                if (typeof selectedAnswer === 'string') {
                    jumpTarget = questionOptionMap.value[q.id + '_' + selectedAnswer];
                } else if (typeof selectedAnswer === 'object' && selectedAnswer.selected) {
                    jumpTarget = questionOptionMap.value[q.id + '_' + selectedAnswer.selected];
                }

                if (jumpTarget) {
                    let isSkipped = false;
                    let foundTarget = false;
                    let scanning = false;

                    for (const s of props.questionnaire.sections) {
                        for (const targetQ of (s.subpertanyaans || s.questions || [])) {
                            if (targetQ.id === q.id) {
                                scanning = true;
                                continue;
                            }
                            if (scanning) {
                                if ((targetQ.kode_pertanyaan || targetQ.code) === jumpTarget) {
                                    foundTarget = true;
                                    break;
                                }
                                if (targetQ.id === qId) {
                                    isSkipped = true;
                                    break;
                                }
                            }
                        }
                        if (foundTarget || isSkipped) break;
                    }

                    if (isSkipped) return false;
                }
            }
        }
    }
    return true;
};

// Menentukan apakah suatu section terlihat (memiliki setidaknya satu pertanyaan yang terlihat)
const isSectionVisible = (section) => {
    const list = section?.subpertanyaans || section?.questions;
    if (!section || !list || list.length === 0) return true;
    return list.some(q => isQuestionVisible(q.id));
};

// Menentukan apakah section saat ini adalah section terlihat terakhir
const isLastVisibleSection = computed(() => {
    if (!props.questionnaire?.sections) return true;
    const total = props.questionnaire.sections.length;
    for (let i = activeSectionIndex.value + 1; i < total; i++) {
        if (isSectionVisible(props.questionnaire.sections[i])) {
            return false;
        }
    }
    return true;
});

// Menghitung total salary untuk multiple_number (F13)
const getMultipleNumberTotal = (q) => {
    if (!form.answers[q.id] || typeof form.answers[q.id] !== 'object') return 0;
    let total = 0;
    (q.detils || q.options)?.forEach(opt => {
        const optCode = opt.kode_opsi || opt.code;
        const val = form.answers[q.id][optCode];
        if (val !== null && val !== '' && !isNaN(val)) {
            const num = parseInt(val, 10);
            if (num > 0) {
                total += (num < 1000000 ? num * 1000 : num);
            }
        }
    });
    return total;
};

// Helper getter nilai input untuk radio_input
const getRadioInputVal = (qId, optId) => {
    if (!form.answers[qId] || typeof form.answers[qId] !== 'object') return '';
    if (!form.answers[qId].inputs) return '';
    return form.answers[qId].inputs[optId] ?? '';
};

// Memeriksa apakah satu butir pertanyaan sudah terjawab dengan valid
const isQuestionAnswered = (q) => {
    if (!q) return true;
    if (q.type === 'header') return true;
    if (!isQuestionVisible(q.id)) return true;
    const isReq = q.wajib ?? q.is_required;
    if (!isReq) return true;

    const ans = form.answers[q.id];

    switch (q.type) {
        case 'rating_5': {
            const n = Number(ans);
            return !isNaN(n) && n >= 1 && n <= 5;
        }

        case 'text': {
            return ans !== undefined && ans !== null && ans.toString().trim() !== '';
        }

        case 'number': {
            return ans !== undefined && ans !== null && ans.toString().trim() !== '' && !isNaN(ans);
        }

        case 'searchable_select': {
            return ans !== undefined && ans !== null && ans.toString().trim() !== '';
        }

        case 'radio':
        case 'single_choice': {
            if (!ans || typeof ans !== 'string' || ans.trim() === '') return false;
            const lower = ans.toLowerCase();
            if (lower.includes('lainnya') || lower.includes('tuliskan') || ans.includes('...')) {
                const customAns = form.answers[q.id + '_custom'];
                return customAns !== undefined && customAns !== null && customAns.toString().trim() !== '';
            }
            return true;
        }

        case 'checkbox':
        case 'multiple_choice': {
            if (!Array.isArray(ans) || ans.length === 0) return false;
            const hasCustom = ans.some(v => {
                const s = (v || '').toString().toLowerCase();
                return s.includes('lainnya') || s.includes('tuliskan') || s.includes('...');
            });
            if (hasCustom) {
                const customAns = form.answers[q.id + '_custom'];
                return customAns !== undefined && customAns !== null && customAns.toString().trim() !== '';
            }
            return true;
        }

        case 'radio_input':
        case 'radio_text': {
            if (!ans || typeof ans !== 'object' || !ans.selected) return false;
            const sel = ans.selected;
            if (sel.includes('...') || sel.includes('…') || sel.toLowerCase().includes('lainnya')) {
                const options = q.detils || q.options;
                const matchedOpt = options?.find(o => o.option_text === sel);
                const inputVal = matchedOpt ? getRadioInputVal(q.id, matchedOpt.id) : (ans.input || '');
                return inputVal !== undefined && inputVal !== null && inputVal.toString().trim() !== '';
            }
            return true;
        }

        case 'multiple_number': {
            return getMultipleNumberTotal(q) > 0;
        }

        default:
            return ans !== undefined && ans !== null && ans !== '';
    }
};

// Memeriksa apakah seluruh pertanyaan wajib di suatu section sudah terjawab
const isSectionAnswered = (section) => {
    const questions = section?.subpertanyaans || section?.questions;
    if (!section || !questions || questions.length === 0) return true;

    const isF2 = questions.some(q => (q.kode_pertanyaan || q.code) === 'F2' || ((q.kode_pertanyaan || q.code) && (q.kode_pertanyaan || q.code).startsWith('F21')));
    if (isF2) {
        return f2QuestionsList.value.length > 0 && f2QuestionsList.value.every(q => isQuestionAnswered(q));
    }

    const isF17 = questions.some(q => {
        const c = q.kode_pertanyaan || q.code;
        return c === 'F17' || (c && (c.toLowerCase().startsWith('f17a') || c.toLowerCase().startsWith('f17b') || c.startsWith('F17-')));
    });
    if (isF17) {
        return f17AspectPairs.value.length > 0 && f17CompletedCount.value === f17AspectPairs.value.length;
    }

    const visibleQuestions = questions.filter(q => q.type !== 'header' && isQuestionVisible(q.id));
    if (visibleQuestions.length === 0) return true;

    const requiredQuestions = visibleQuestions.filter(q => (q.wajib ?? q.is_required));
    if (requiredQuestions.length > 0) {
        return requiredQuestions.every(q => isQuestionAnswered(q));
    }

    return visibleQuestions.some(q => isQuestionAnswered(q));
};

// Menentukan status selesai suatu section
const isSectionCompleted = (index) => {
    const sec = props.questionnaire?.sections?.[index];
    if (!sec) return false;
    return completedSectionIndices.value.has(index) || isSectionAnswered(sec);
};

// Menentukan warna konektor garis antar section
const isLineCompleted = (index) => {
    const currentCompleted = isSectionCompleted(index);
    const nextCompleted = isSectionCompleted(index + 1);
    const currentActive = (activeSectionIndex.value === index);
    const nextActive = (activeSectionIndex.value === index + 1);

    return (currentCompleted && (nextCompleted || nextActive)) || 
           (currentActive && nextCompleted) || 
           (index < activeSectionIndex.value && currentCompleted);
};

// Sinkronisasi status kelengkapan seluruh section berdasarkan data jawaban
const syncSectionCompletion = () => {
    if (!props.questionnaire?.sections) return;
    props.questionnaire.sections.forEach((sec, idx) => {
        if (isSectionAnswered(sec)) {
            completedSectionIndices.value.add(idx);
        } else if (idx === activeSectionIndex.value) {
            completedSectionIndices.value.delete(idx);
        }
    });
    saveCompletedSections();
};

// Navigasi Section: Berpindah ke section tertentu via stepper
const setSection = (index) => {
    if (currentSection.value && isSectionAnswered(currentSection.value)) {
        completedSectionIndices.value.add(activeSectionIndex.value);
        saveCompletedSections();
    }
    activeSectionIndex.value = index;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

// Navigasi Section: Kembali ke section sebelumnya
const prevSection = () => {
    if (activeSectionIndex.value > 0) {
        if (currentSection.value && isSectionAnswered(currentSection.value)) {
            completedSectionIndices.value.add(activeSectionIndex.value);
            saveCompletedSections();
        }
        let prevIdx = activeSectionIndex.value - 1;
        while (prevIdx > 0 && !isSectionVisible(props.questionnaire.sections[prevIdx])) {
            prevIdx--;
        }
        activeSectionIndex.value = prevIdx;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

// Navigasi Section: Lanjut atau Simpan Form
const handleNextOrSubmit = () => {
    if (isLastVisibleSection.value) {
        // Halaman terakhir: Simpan seluruh jawaban
        form.post('/alumni/kuesioner', {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                completedSectionIndices.value.add(activeSectionIndex.value);
                saveCompletedSections();
                if (typeof window !== 'undefined') {
                    localStorage.removeItem(ANSWERS_STORAGE_KEY);
                    localStorage.removeItem(SECTION_STORAGE_KEY);
                    localStorage.removeItem(COMPLETED_SECTIONS_STORAGE_KEY);
                }
            }
        });
    } else {
        // Tandai section saat ini selesai
        completedSectionIndices.value.add(activeSectionIndex.value);
        saveCompletedSections();

        // Simpan progress parsial ke backend dan lompat ke section berikutnya yang terlihat
        form.post('/alumni/kuesioner', {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                completedSectionIndices.value.add(activeSectionIndex.value);
                saveCompletedSections();

                let nextIdx = activeSectionIndex.value + 1;
                while (nextIdx < props.questionnaire.sections.length && !isSectionVisible(props.questionnaire.sections[nextIdx])) {
                    nextIdx++;
                }

                if (nextIdx < props.questionnaire.sections.length) {
                    activeSectionIndex.value = nextIdx;
                }
                
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }
};

// Lifecycle Hooks & Watchers
onMounted(() => {
    if (props.questionnaire?.sections?.length) {
        if (activeSectionIndex.value >= props.questionnaire.sections.length) {
            activeSectionIndex.value = 0;
        }
        syncSectionCompletion();
    }
});

watch(activeSectionIndex, (newIdx) => {
    if (typeof window !== 'undefined') {
        localStorage.setItem(SECTION_STORAGE_KEY, newIdx.toString());
        const url = new URL(window.location.href);
        url.searchParams.set('sec', newIdx.toString());
        window.history.replaceState({}, '', url.toString());
    }
});

let draftTimer = null;
watch(() => form.answers, (newAnswers) => {
    if (typeof window !== 'undefined') {
        clearTimeout(draftTimer);
        draftTimer = setTimeout(() => {
            try {
                localStorage.setItem(ANSWERS_STORAGE_KEY, JSON.stringify(newAnswers));
            } catch (e) {
                console.error('Gagal menyimpan cache jawaban:', e);
            }
        }, 800);
    }
    syncSectionCompletion();
}, { deep: true });
</script>

<template>
    <Head title="Kuesioner Tracer Study" />

    <div class="min-h-screen bg-[#E8F5E9] font-sans text-gray-900 antialiased flex flex-col relative pb-28 md:pb-32">
        <!-- Header Atas: Navbar & Stepper Tahapan -->
        <header class="sticky top-0 z-50 w-full shadow-md bg-white">
            <!-- 1. Navbar Atas (Logo UKDW & Tombol Kembali) -->
            <Navbar />

            <!-- 2. Stepper Tahapan (Bulatan Angka 1 s/d N & Judul Bagian) -->
            <Stepper 
                v-if="questionnaire?.sections"
                :sections="questionnaire.sections"
                :active-index="activeSectionIndex"
                :completed-indices="completedSectionIndices"
                :is-section-completed="isSectionCompleted"
                :is-line-completed="isLineCompleted"
                @select-section="setSection"
            />
        </header>

        <!-- Area Konten Formulir Utama -->
        <main 
            class="flex-1 px-3 py-4 sm:px-4 md:px-6 w-full mx-auto mt-3 sm:mt-6 md:mt-8" 
            :class="(isF17Section || isF2Section) ? 'max-w-6xl' : 'max-w-5xl'"
        >
            <!-- Pesan Error jika kuesioner tidak aktif -->
            <div 
                v-if="error" 
                class="bg-red-50 text-red-700 p-4 sm:p-6 rounded-2xl sm:rounded-3xl font-bold mb-4 sm:mb-6 shadow-xs text-sm sm:text-base"
            >
                {{ error }}
            </div>

            <!-- Formulir Kuesioner Aktif -->
            <div v-else-if="questionnaire && currentSection">
                <!-- 3. Banner Hijau Judul Bagian -->
                <Banner 
                    :current-index="activeSectionIndex"
                    :total-sections="questionnaire.sections.length"
                    :title="currentSection.title"
                />

                <form @submit.prevent="handleNextOrSubmit" class="space-y-4 sm:space-y-8">
                    <!-- 4. Khusus Instrumen F2: Penekanan Metode Pembelajaran Matriks -->
                    <TabelF2 
                        v-if="isF2Section"
                        :header-question="f2HeaderQuestion"
                        :questions="f2QuestionsList"
                        :form="form"
                    />

                    <!-- 5. Khusus Instrumen F17: Evaluasi Kompetensi Dual Matrix (A vs B) -->
                    <TabelF17 
                        v-else-if="isF17Section"
                        :question="f17Question"
                        :pairs="f17AspectPairs"
                        :form="form"
                    />

                    <!-- 6. Daftar Pertanyaan Kuesioner Standar (Non-F2 & Non-F17) -->
                    <div v-else class="space-y-4 sm:space-y-8">
                        <template v-for="(group, gIdx) in groupedQuestions" :key="'grp_' + gIdx">
                            <!-- Pertanyaan 2 Kolom Berdampingan (Khusus F6 dan F7) -->
                            <div v-if="group.type === 'pair'" class="grid grid-cols-1 md:grid-cols-2 gap-3.5 sm:gap-6">
                                <KartuPertanyaan 
                                    v-for="q in group.items"
                                    :key="q.id"
                                    :subpertanyaan="q"
                                    :form="form"
                                    :is-visible="isQuestionVisible(q.id)"
                                    :is-paired="true"
                                />
                            </div>

                            <!-- Pertanyaan Tunggal Ukuran Penuh -->
                            <template v-else>
                                <KartuPertanyaan 
                                    v-for="q in group.items"
                                    :key="q.id"
                                    :subpertanyaan="q"
                                    :form="form"
                                    :is-visible="isQuestionVisible(q.id)"
                                    :is-paired="false"
                                />
                            </template>
                        </template>
                    </div>

                    <!-- 6. Tombol Navigasi Desktop & Mobile (< Kembali & > Lanjut/Selesai) -->
                    <Navigasi 
                        :active-section-index="activeSectionIndex"
                        :total-sections="questionnaire.sections.length"
                        :is-last-visible-section="isLastVisibleSection"
                        :is-processing="form.processing"
                        @prev="prevSection"
                    />
                </form>
            </div>
        </main>
    </div>
</template>
