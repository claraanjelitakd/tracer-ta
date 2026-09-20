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
import Navbar from './Components/Navbar.vue';
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
    const serverAnswers = JSON.parse(JSON.stringify(props.initialAnswers || {}));
    let base = { ...serverAnswers };
    if (typeof window !== 'undefined') {
        try {
            const cached = localStorage.getItem(ANSWERS_STORAGE_KEY);
            if (cached) {
                const parsed = JSON.parse(cached);
                base = { ...parsed, ...serverAnswers };
            }
        } catch (e) {
            console.error('Gagal membaca cache jawaban:', e);
        }
    }

    // Normalisasi struktur radio_input, multiple_number, dan checkbox/multiple_choice
    if (props.questionnaire?.sections) {
        props.questionnaire.sections.forEach(sec => {
            (sec.subpertanyaans || sec.questions)?.forEach(q => {
                const options = q.detils || q.options;

                if (['checkbox', 'multiple_choice'].includes(q.type)) {
                    if (!Array.isArray(base[q.id])) {
                        base[q.id] = (base[q.id] && typeof base[q.id] === 'string') ? [base[q.id]] : [];
                    }
                }

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

// Kelompokkan pertanyaan dalam section: buat berpasangan atau bertiga (F6, F7, F7A dan F18b/F18c)
const groupedQuestions = computed(() => {
    const list = currentSection.value?.subpertanyaans || currentSection.value?.questions;
    if (!list) return [];
    const questions = list;
    const groups = [];
    let i = 0;

    while (i < questions.length) {
        const q = questions[i];
        const nextQ = questions[i + 1];
        const nextQ2 = questions[i + 2];
        const qCode = (q.kode_pertanyaan || q.code || '').toUpperCase();
        const nextQCode = nextQ ? (nextQ.kode_pertanyaan || nextQ.code || '').toUpperCase() : '';
        const nextQ2Code = nextQ2 ? (nextQ2.kode_pertanyaan || nextQ2.code || '').toUpperCase() : '';

        // Khusus F6, F7, dan F7A: kelompokkan jadi 3 kolom berdampingan
        if (qCode === 'F6' && nextQCode === 'F7' && nextQ2Code === 'F7A') {
            groups.push({
                type: 'trio',
                items: [q, nextQ, nextQ2]
            });
            i += 3;
        } else if (qCode === 'F6' && nextQ && nextQCode === 'F7') {
            groups.push({
                type: 'pair',
                items: [q, nextQ]
            });
            i += 2;
        } else if (qCode === 'F18B' && nextQ && nextQCode === 'F18C') {
            // Pasangan Studi Lanjut F18b (Perguruan Tinggi) dan F18c (Program Studi)
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

// Daftar urut seluruh butir pertanyaan dari seluruh seksi (untuk navigasi alur jump_to)
const orderedQuestionsList = computed(() => {
    if (!props.questionnaire?.sections) return [];
    const list = [];
    props.questionnaire.sections.forEach(sec => {
        (sec.subpertanyaans || sec.questions || []).forEach(q => {
            list.push(q);
        });
    });
    return list;
});

// Evaluasi Logika Percabangan (Branching / Jump Logic) Berbasis Data Opsi Database (jump_to)
const hiddenQuestionIds = computed(() => {
    const hidden = new Set();
    const list = orderedQuestionsList.value;
    if (list.length === 0) return hidden;

    // Index mapping untuk pencarian cepat target jump_to berdasarkan kode pertanyaan
    const codeToIndex = {};
    const codeToQuestion = {};
    list.forEach((q, idx) => {
        const code = (q.kode_pertanyaan || q.code || '').toUpperCase();
        if (code) {
            codeToIndex[code] = idx;
            codeToQuestion[code] = q;
        }
    });

    for (let i = 0; i < list.length; i++) {
        const q = list[i];
        if (hidden.has(q.id)) continue;

        const qCode = (q.kode_pertanyaan || q.code || '').toUpperCase();
        const ans = form.answers[q.id];

        // Aturan Berbasis Status Pekerjaan F8 (Standar Tracer Study Kemendikbud Dikti)
        if (qCode === 'F8' && ans !== undefined && ans !== null) {
            const ansStr = (typeof ans === 'string' || typeof ans === 'number') ? ans.toString().trim().toLowerCase() : '';

            // Status 4: Melanjutkan Pendidikan -> lewati pertanyaan pekerjaan/mencari kerja
            if (ansStr.includes('melanjutkan pendidikan') || ansStr === '4') {
                const workCodes = ['F3', 'F4', 'F504', 'F502', 'F505', 'F506', 'F6', 'F7', 'F7A', 'F14', 'F15', 'F16'];
                workCodes.forEach(wc => {
                    if (codeToQuestion[wc]) {
                        hidden.add(codeToQuestion[wc].id);
                    }
                });
            }
            // Status 2: Belum Memungkinkan Bekerja -> lewati studi lanjut dan pertanyaan pekerjaan
            else if (ansStr.includes('belum memungkinkan') || ansStr === '2') {
                const skipCodes = ['F18', 'F18A', 'F18B', 'F18C', 'F18D', 'F3', 'F4', 'F504', 'F502', 'F505', 'F506', 'F6', 'F7', 'F7A', 'F14', 'F15', 'F16'];
                skipCodes.forEach(sc => {
                    if (codeToQuestion[sc]) {
                        hidden.add(codeToQuestion[sc].id);
                    }
                });
            }
            // Status 5: Tidak Kerja tetapi sedang mencari kerja -> lewati studi lanjut & pertanyaan sedang bekerja
            else if (ansStr.includes('sedang mencari kerja') || ansStr === '5') {
                const skipCodes = ['F18', 'F18A', 'F18B', 'F18C', 'F18D', 'F504', 'F502', 'F505', 'F506', 'F14', 'F15', 'F16'];
                skipCodes.forEach(sc => {
                    if (codeToQuestion[sc]) {
                        hidden.add(codeToQuestion[sc].id);
                    }
                });
            }
            // Status 1 & 3: Bekerja / Wiraswasta -> lewati studi lanjut (F18)
            else if (ansStr !== '') {
                ['F18', 'F18A', 'F18B', 'F18C', 'F18D'].forEach(sc => {
                    if (codeToQuestion[sc]) {
                        hidden.add(codeToQuestion[sc].id);
                    }
                });
            }
        }

        // Aturan Khusus F504:
        // Jika Ya -> sembunyikan F506 (pencarian > 6 bulan)
        // Jika Tidak -> sembunyikan F502 & F505 (pencarian <= 6 bulan & gaji)
        if (qCode === 'F504' && ans !== undefined && ans !== null) {
            const ansStr = (typeof ans === 'string' || typeof ans === 'number') ? ans.toString().trim().toLowerCase() : '';
            if (ansStr === 'ya' || ansStr === '1' || ansStr.startsWith('ya')) {
                if (codeToQuestion['F506']) {
                    hidden.add(codeToQuestion['F506'].id);
                }
            } else if (ansStr === 'tidak' || ansStr === '2' || ansStr.startsWith('tidak')) {
                ['F502', 'F505'].forEach(wc => {
                    if (codeToQuestion[wc]) {
                        hidden.add(codeToQuestion[wc].id);
                    }
                });
            }
        }

        const options = q.detils || q.options;
        if (!options || options.length === 0) continue;
        if (ans === undefined || ans === null || ans === '') continue;

        let selectedOpt = null;

        if (typeof ans === 'string' || typeof ans === 'number') {
            const ansStr = ans.toString().trim().toLowerCase();
            selectedOpt = options.find(o => {
                const optText = (o.option_text || '').trim().toLowerCase();
                const optCode = (o.kode_opsi || o.code || '').toString().trim().toLowerCase();
                return optText === ansStr || optCode === ansStr || (ansStr.length > 0 && optText.startsWith(ansStr));
            });
        } else if (typeof ans === 'object' && ans.selected) {
            const selStr = ans.selected.toString().trim().toLowerCase();
            selectedOpt = options.find(o => {
                const optText = (o.option_text || '').trim().toLowerCase();
                const optCode = (o.kode_opsi || o.code || '').toString().trim().toLowerCase();
                return optText === selStr || optCode === selStr;
            });
        }

        // Jika opsi yang dipilih alumni memiliki instruksi jump_to di database
        if (selectedOpt && selectedOpt.jump_to) {
            const targetCode = selectedOpt.jump_to.trim().toUpperCase();
            const targetIdx = codeToIndex[targetCode];

            if (targetIdx !== undefined && targetIdx > i) {
                for (let j = i + 1; j < targetIdx; j++) {
                    hidden.add(list[j].id);
                }
            }
        }
    }

    return hidden;
});

// Menentukan apakah suatu butir pertanyaan terlihat (tidak dilewati oleh alur percabangan jump_to)
const isQuestionVisible = (qId) => {
    return !hiddenQuestionIds.value.has(qId);
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

// ===============================================================================================
// HELPER VALIDASI BUTIR PERTANYAAN & STATUS KELENGKAPAN SEKSI
// ===============================================================================================

/**
 * Memeriksa apakah satu butir pertanyaan sudah terjawab secara valid oleh alumni.
 * Fungsi ini mengecek isi nilai form.answers berdasarkan tipe input butir pertanyaan.
 * 
 * @param {Object} q Objek data subpertanyaan
 * @returns {Boolean} true jika pertanyaan telah terisi jawaban valid
 */
const isQuestionAnswered = (q) => {
    if (!q) return false;
    // Tipe header tidak memerlukan input jawaban
    if (q.type === 'header') return true;
    // Pertanyaan yang disembunyikan oleh jump logic dianggap selesai
    if (!isQuestionVisible(q.id)) return true;

    const ans = form.answers[q.id];

    switch (q.type) {
        // Skala Skor 1 s/d 5 (Likert)
        case 'rating_5': {
            const n = Number(ans);
            return !isNaN(n) && n >= 1 && n <= 5;
        }

        // Isian Teks Bebas
        case 'text': {
            return ans !== undefined && ans !== null && ans.toString().trim() !== '';
        }

        // Isian Angka / Numerik
        case 'number': {
            return ans !== undefined && ans !== null && ans.toString().trim() !== '' && !isNaN(ans);
        }

        // Tanggal, Waktu, Berkas, dan Searchable Dropdown
        case 'date':
        case 'time':
        case 'file':
        case 'searchable_select': {
            return ans !== undefined && ans !== null && ans.toString().trim() !== '';
        }

        // Pilihan Tunggal / Radio
        case 'radio':
        case 'single_choice': {
            if (ans === undefined || ans === null || ans === '') return false;
            const str = ans.toString().trim();
            if (str === '') return false;
            const lower = str.toLowerCase();
            if (lower.includes('lainnya') || lower.includes('tuliskan') || str.includes('...')) {
                const customAns = form.answers[q.id + '_custom'];
                return customAns !== undefined && customAns !== null && customAns.toString().trim() !== '';
            }
            return true;
        }

        // Pilihan Ganda / Checkbox (Bisa memilih lebih dari satu)
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

        // Pilihan Radio dengan Kolom Isian Tambahan (radio_input / radio_text)
        case 'radio_input':
        case 'radio_text': {
            if (!ans || typeof ans !== 'object' || !ans.selected) return false;
            const sel = ans.selected.toString();
            if (sel.includes('...') || sel.includes('…') || sel.toLowerCase().includes('lainnya')) {
                const options = q.detils || q.options;
                const matchedOpt = options?.find(o => o.option_text === sel);
                const inputVal = matchedOpt ? getRadioInputVal(q.id, matchedOpt.id) : (ans.input || '');
                return inputVal !== undefined && inputVal !== null && inputVal.toString().trim() !== '';
            }
            return true;
        }

        // Input Gaji & Nominal Penghasilan (Multiple Number F13)
        case 'multiple_number': {
            return getMultipleNumberTotal(q) > 0;
        }

        // Matriks Tabel & Multiple Textbox
        case 'matrix':
        case 'matrix_dual':
        case 'multiple_textbox': {
            if (!ans || typeof ans !== 'object') return false;
            return Object.values(ans).some(v => v !== undefined && v !== null && v.toString().trim() !== '');
        }

        // Default: Pastikan nilai ada dan tidak string kosong
        default:
            return ans !== undefined && ans !== null && ans.toString().trim() !== '';
    }
};

/**
 * Memeriksa apakah seluruh pertanyaan wajib di suatu section sudah terjawab lengkap.
 * 
 * @param {Object} section Objek seksi kuesioner
 * @returns {Boolean} true jika seksi kuesioner sudah lengkap terisi
 */
const isSectionAnswered = (section) => {
    const questions = section?.subpertanyaans || section?.questions;
    if (!section || !questions || questions.length === 0) return false;

    // Ambil pertanyaan non-header yang terlihat
    const visibleQuestions = questions.filter(q => q.type !== 'header' && isQuestionVisible(q.id));
    if (visibleQuestions.length === 0) return true;

    // Jika ada butir yang secara eksplisit bertanda wajib
    const requiredQuestions = visibleQuestions.filter(q => (q.wajib ?? q.is_required));
    if (requiredQuestions.length > 0) {
        return requiredQuestions.every(q => isQuestionAnswered(q));
    }

    // Jika tidak ada tanda wajib khusus, semua pertanyaan yang terlihat wajib dijawab agar seksi berstatus selesai
    return visibleQuestions.every(q => isQuestionAnswered(q));
};

/**
 * Menentukan status selesai suatu section (digunakan oleh Stepper dan Navigasi).
 * 
 * @param {Number} index Indeks posisi seksi
 * @returns {Boolean} true jika seksi sudah lengkap terisi
 */
const isSectionCompleted = (index) => {
    const sec = props.questionnaire?.sections?.[index];
    if (!sec) return false;
    return isSectionAnswered(sec) || completedSectionIndices.value.has(index);
};

/**
 * Menentukan warna hijau konektor garis antar section pada Stepper.
 * 
 * @param {Number} index Indeks posisi seksi
 * @returns {Boolean} true jika garis penghubung aktif / selesai
 */
const isLineCompleted = (index) => {
    const currentCompleted = isSectionCompleted(index);
    const nextCompleted = isSectionCompleted(index + 1);
    const currentActive = (activeSectionIndex.value === index);
    const nextActive = (activeSectionIndex.value === index + 1);

    return (currentCompleted && (nextCompleted || nextActive)) || 
           (index < activeSectionIndex.value && currentCompleted);
};

/**
 * Sinkronisasi status kelengkapan seluruh section secara berkala.
 */
const syncSectionCompletion = () => {
    if (!props.questionnaire?.sections) return;
    const newCompleted = new Set();
    props.questionnaire.sections.forEach((sec, idx) => {
        if (isSectionAnswered(sec)) {
            newCompleted.add(idx);
        }
    });
    completedSectionIndices.value = newCompleted;
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

    <div class="min-h-screen bg-[#E8F5E9] font-sans text-gray-900 antialiased flex flex-col relative pb-20 md:pb-6">
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
        <main class="flex-1 px-3 py-4 sm:px-4 md:px-6 lg:px-8 w-full mx-auto mt-3 sm:mt-6 md:mt-8 max-w-6xl xl:max-w-7xl">
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

                <form @submit.prevent="handleNextOrSubmit" class="space-y-4 sm:space-y-6">
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
                            <!-- Pertanyaan 3 Kolom Berdampingan (Khusus F6, F7, F7A) -->
                            <div v-if="group.type === 'trio'" class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6 items-stretch">
                                <KartuPertanyaan 
                                    v-for="q in group.items"
                                    :key="q.id"
                                    :subpertanyaan="q"
                                    :form="form"
                                    :is-visible="isQuestionVisible(q.id)"
                                    :is-paired="true"
                                />
                            </div>

                            <!-- Pertanyaan 2 Kolom Berdampingan (Khusus F18b & F18c atau F6 & F7) -->
                            <div v-else-if="group.type === 'pair'" class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 items-stretch">
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
