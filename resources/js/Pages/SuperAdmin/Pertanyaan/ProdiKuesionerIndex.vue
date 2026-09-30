<!--
  Halaman: Pengaturan Kuesioner Program Studi (Super Admin)
  File: resources/js/Pages/SuperAdmin/Pertanyaan/ProdiKuesionerIndex.vue

  Desain Standar Enterprise UKDW:
  - Dipilih Program Studi terlebih dahulu via Selector Bar.
  - Bebas Emoticon / Emojis (Menggunakan SVG Icons Resmi & Bahasa Formal).
-->
<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import Sidebar from '../Components/Sidebar.vue';

const props = defineProps({
    user: Object,
    prodis: {
        type: Array,
        default: () => [],
    },
    selectedProdi: {
        type: Object,
        default: () => ({}),
    },
    sections: {
        type: Array,
        default: () => [],
    },
    questions: {
        type: Array,
        default: () => [],
    },
    availableJumpTargets: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_sections: 0,
            total_questions: 0,
        }),
    },
});

// Tab Aktif: 'sections' atau 'questions'
const activeTab = ref('sections');

// Selected Prodi State
const selectedProdiId = ref(props.selectedProdi?.id || (props.prodis[0]?.id || ''));

const handleProdiChange = () => {
    if (selectedProdiId.value) {
        router.get('/superadmin/prodi-kuesioner', {
            prodi_id: selectedProdiId.value,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

// ========================================================
// SECTION STATE & FILTERS
// ========================================================
const sectionSearchQuery = ref('');
const filteredSections = computed(() => {
    if (!sectionSearchQuery.value.trim()) return props.sections;
    const q = sectionSearchQuery.value.toLowerCase();
    return props.sections.filter(s =>
        s.title?.toLowerCase().includes(q) ||
        s.description?.toLowerCase().includes(q)
    );
});

// ========================================================
// QUESTION STATE & FILTERS
// ========================================================
const selectedSectionFilter = ref('all');
const selectedRequiredFilter = ref('all');
const questionSearchQuery = ref('');

const typeLabels = {
    single_choice: 'Pilihan Ganda (Radio)',
    radio_input: 'Radio + Angka',
    radio_text: 'Radio + Teks',
    multiple_choice: 'Kotak Centang (Checkbox)',
    dropdown: 'Pilihan Dropdown (Select)',
    searchable_select: 'Pencarian Dropdown',
    rating_5: 'Skala Rating (1-5)',
    multiple_number: 'Angka Ganda',
    matrix: 'Matriks Skala',
    matrix_dual: 'Dual Matrix',
    multiple_textbox: 'Multiple Textbox',
    text: 'Jawaban Singkat (Text)',
    textarea: 'Paragraf (Textarea)',
    number: 'Isian Angka (Number)',
    date: 'Tanggal',
    time: 'Waktu',
    file: 'Unggah Berkas (File)',
    header: 'Header / Judul Bagian',
};

const OPTION_SUPPORTED_TYPES = [
    'single_choice',
    'radio_input',
    'radio_text',
    'multiple_choice',
    'dropdown',
    'searchable_select',
    'rating_5',
    'multiple_number',
    'matrix',
    'matrix_dual',
    'multiple_textbox',
];

const canHaveOptions = (type) => OPTION_SUPPORTED_TYPES.includes(type);

const filteredQuestions = computed(() => {
    return props.questions.filter((q) => {
        if (selectedSectionFilter.value !== 'all') {
            const secId = parseInt(selectedSectionFilter.value, 10);
            if (q.prodi_question_section_id !== secId) return false;
        }
        if (selectedRequiredFilter.value !== 'all') {
            const isReq = selectedRequiredFilter.value === 'wajib';
            if (Boolean(q.is_required) !== isReq) return false;
        }
        if (questionSearchQuery.value.trim()) {
            const query = questionSearchQuery.value.toLowerCase();
            const matchCode = q.code?.toLowerCase().includes(query);
            const matchText = q.question_text?.toLowerCase().includes(query);
            const matchType = (typeLabels[q.type] || q.type)?.toLowerCase().includes(query);
            return matchCode || matchText || matchType;
        }
        return true;
    });
});

// ========================================================
// MODAL HANDLERS: SECTION (TAMBAH / EDIT / REORDER / HAPUS)
// ========================================================
const openSectionModal = (sectionToEdit = null) => {
    const isEdit = !!sectionToEdit;
    const initialTitle = sectionToEdit?.title || '';
    const initialDesc = sectionToEdit?.description || '';
    const initialOrder = sectionToEdit?.order || (props.sections.length + 1);

    Swal.fire({
        title: isEdit ? 'Sunting Bagian Kuesioner Prodi' : 'Tambah Bagian Kuesioner Prodi Baru',
        html: `
            <div class="text-left space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Program Studi Target</label>
                    <div class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg font-bold text-[#0D542B]">
                        ${props.selectedProdi?.nama_prodi || 'Program Studi Target'}
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Judul Bagian (Section Title) *</label>
                    <input id="swal-sec-title" type="text" value="${initialTitle}" placeholder="Contoh: Evaluasi Capaian Pembelajaran Prodi" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs" />
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Deskripsi / Petunjuk Pengisian (Opsional)</label>
                    <textarea id="swal-sec-desc" rows="3" placeholder="Ketikkan petunjuk pengisian bagi responden alumni..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs">${initialDesc}</textarea>
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Nomor Urut Posisi</label>
                    <input id="swal-sec-order" type="number" value="${initialOrder}" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs" />
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: isEdit ? 'Simpan Perubahan' : 'Tambah Bagian',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#0D542B',
        cancelButtonColor: '#6B7280',
        preConfirm: () => {
            const title = document.getElementById('swal-sec-title').value.trim();
            const description = document.getElementById('swal-sec-desc').value.trim();
            const order = parseInt(document.getElementById('swal-sec-order').value, 10) || null;

            if (!title) {
                Swal.showValidationMessage('Judul bagian kuesioner prodi wajib diisi.');
                return false;
            }

            return {
                prodi_id: props.selectedProdi.id,
                title,
                description,
                order,
            };
        },
    }).then((res) => {
        if (res.isConfirmed && res.value) {
            if (isEdit) {
                router.put(`/superadmin/prodi-kuesioner/sections/${sectionToEdit.id}`, res.value, {
                    preserveScroll: true,
                    onSuccess: () => Swal.fire('Berhasil', 'Bagian kuesioner prodi diperbarui.', 'success'),
                });
            } else {
                router.post('/superadmin/prodi-kuesioner/sections', res.value, {
                    preserveScroll: true,
                    onSuccess: () => Swal.fire('Berhasil', 'Bagian kuesioner prodi ditambahkan.', 'success'),
                });
            }
        }
    });
};

const handleReorderSection = (sectionId, direction) => {
    router.post('/superadmin/prodi-kuesioner/sections/reorder', {
        id: sectionId,
        direction,
    }, {
        preserveScroll: true,
    });
};

const handleDeleteSection = (section) => {
    Swal.fire({
        title: 'Hapus Bagian Kuesioner Prodi?',
        text: `Apakah Anda yakin ingin menghapus bagian "${section.title}" beserta seluruh butir pertanyaannya?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus Bagian',
        cancelButtonText: 'Batal',
    }).then((res) => {
        if (res.isConfirmed) {
            router.delete(`/superadmin/prodi-kuesioner/sections/${section.id}`, {
                preserveScroll: true,
                onSuccess: () => Swal.fire('Terhapus', 'Bagian kuesioner prodi berhasil dihapus.', 'success'),
            });
        }
    });
};

// ========================================================
// MODAL HANDLERS: QUESTION (TAMBAH / EDIT / REORDER / HAPUS)
// ========================================================
const openQuestionModal = (questionToEdit = null) => {
    const isEdit = !!questionToEdit;
    const initialCode = questionToEdit?.code || `P${props.questions.length + 1}`;
    const initialText = questionToEdit?.question_text || '';
    const initialType = questionToEdit?.type || 'single_choice';
    const initialRequired = questionToEdit?.is_required !== undefined ? questionToEdit.is_required : true;
    const initialSecId = questionToEdit?.prodi_question_section_id || (props.sections[0]?.id || '');
    const initialOrder = questionToEdit?.order || (props.questions.length + 1);

    const sectionOptions = props.sections.map(s => `<option value="${s.id}" ${initialSecId == s.id ? 'selected' : ''}>${s.title}</option>`).join('');
    const typeOptions = Object.entries(typeLabels).map(([val, label]) => `<option value="${val}" ${initialType === val ? 'selected' : ''}>${label}</option>`).join('');

    Swal.fire({
        title: isEdit ? 'Sunting Pertanyaan Kuesioner Prodi' : 'Tambah Pertanyaan Kuesioner Prodi Baru',
        html: `
            <div class="text-left space-y-3 text-xs max-h-[70vh] overflow-y-auto pr-1">
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Kode Pertanyaan *</label>
                    <input id="swal-q-code" type="text" value="${initialCode}" placeholder="Contoh: P01" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs font-mono font-bold" />
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Bagian / Section *</label>
                    <select id="swal-q-sec" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs">
                        ${sectionOptions || '<option value="">-- Buat Section Baru Dulu --</option>'}
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Pertanyaan / Teks Kuesioner *</label>
                    <textarea id="swal-q-text" rows="3" placeholder="Ketik kalimat pertanyaan..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs">${initialText}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Tipe Pertanyaan *</label>
                        <select id="swal-q-type" class="w-full px-2 py-2 border border-gray-300 rounded-lg text-xs">
                            ${typeOptions}
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Nomor Urut</label>
                        <input id="swal-q-order" type="number" value="${initialOrder}" min="1" class="w-full px-2 py-2 border border-gray-300 rounded-lg text-xs" />
                    </div>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <input id="swal-q-req" type="checkbox" ${initialRequired ? 'checked' : ''} class="rounded text-[#0D542B] focus:ring-[#0D542B]" />
                    <label for="swal-q-req" class="font-bold text-gray-700">Wajib diisi oleh alumni responden</label>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: isEdit ? 'Simpan Perubahan' : 'Tambah Pertanyaan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#0D542B',
        cancelButtonColor: '#6B7280',
        preConfirm: () => {
            const code = document.getElementById('swal-q-code').value.trim();
            const question_text = document.getElementById('swal-q-text').value.trim();
            const type = document.getElementById('swal-q-type').value;
            const prodi_question_section_id = document.getElementById('swal-q-sec').value;
            const order = parseInt(document.getElementById('swal-q-order').value, 10) || null;
            const is_required = document.getElementById('swal-q-req').checked;

            if (!code || !question_text) {
                Swal.showValidationMessage('Kode dan teks pertanyaan wajib diisi.');
                return false;
            }

            return {
                prodi_id: props.selectedProdi.id,
                code,
                question_text,
                type,
                prodi_question_section_id,
                order,
                is_required,
            };
        },
    }).then((res) => {
        if (res.isConfirmed && res.value) {
            if (isEdit) {
                router.put(`/superadmin/prodi-kuesioner/pertanyaan/${questionToEdit.id}`, res.value, {
                    preserveScroll: true,
                    onSuccess: () => Swal.fire('Berhasil', 'Pertanyaan kuesioner prodi diperbarui.', 'success'),
                });
            } else {
                router.post('/superadmin/prodi-kuesioner/pertanyaan', res.value, {
                    preserveScroll: true,
                    onSuccess: () => Swal.fire('Berhasil', 'Pertanyaan kuesioner prodi ditambahkan.', 'success'),
                });
            }
        }
    });
};

const handleReorderQuestion = (questionId, direction) => {
    router.post('/superadmin/prodi-kuesioner/pertanyaan/reorder', {
        id: questionId,
        direction,
    }, {
        preserveScroll: true,
    });
};

const handleDeleteQuestion = (question) => {
    Swal.fire({
        title: 'Hapus Pertanyaan Kuesioner?',
        text: `Apakah Anda yakin ingin menghapus pertanyaan [${question.code}] "${question.question_text}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus Pertanyaan',
        cancelButtonText: 'Batal',
    }).then((res) => {
        if (res.isConfirmed) {
            router.delete(`/superadmin/prodi-kuesioner/pertanyaan/${question.id}`, {
                preserveScroll: true,
                onSuccess: () => Swal.fire('Terhapus', 'Pertanyaan kuesioner prodi berhasil dihapus.', 'success'),
            });
        }
    });
};

// ========================================================
// MODAL HANDLERS: OPSI JAWABAN (TAMBAH / EDIT / HAPUS)
// ========================================================
const openOptionModal = (question, optionToEdit = null) => {
    const isEdit = !!optionToEdit;
    const initialCode = optionToEdit?.code || `${question.code}-${String((question.options?.length || 0) + 1).padStart(2, '0')}`;
    const initialText = optionToEdit?.option_text || '';
    const initialJump = optionToEdit?.jump_to || '';
    const initialOrder = optionToEdit?.order || ((question.options?.length || 0) + 1);

    const jumpOptions = ['<option value="">-- Tanpa Percabangan (Lanjut Normal) --</option>']
        .concat(props.availableJumpTargets.map(code => `<option value="${code}" ${initialJump === code ? 'selected' : ''}>Lompat ke Pertanyaan: [${code}]</option>`))
        .join('');

    Swal.fire({
        title: isEdit ? `Edit Opsi [${question.code}]` : `Tambah Opsi Jawaban [${question.code}]`,
        html: `
            <div class="text-left space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Kode Opsi *</label>
                    <input id="swal-opt-code" type="text" value="${initialCode}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs font-mono" />
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Teks Opsi Jawaban *</label>
                    <input id="swal-opt-text" type="text" value="${initialText}" placeholder="Contoh: Sangat Relevan" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs" />
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Alur Percabangan (Lompat Ke / Jump To)</label>
                    <select id="swal-opt-jump" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs">
                        ${jumpOptions}
                    </select>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: isEdit ? 'Simpan Opsi' : 'Tambah Opsi',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#0D542B',
        cancelButtonColor: '#6B7280',
        preConfirm: () => {
            const code = document.getElementById('swal-opt-code').value.trim();
            const option_text = document.getElementById('swal-opt-text').value.trim();
            const jump_to = document.getElementById('swal-opt-jump').value;

            if (!code || !option_text) {
                Swal.showValidationMessage('Kode opsi dan teks opsi wajib diisi.');
                return false;
            }

            return {
                prodi_question_id: question.id,
                code,
                option_text,
                jump_to: jump_to || null,
                order: initialOrder,
            };
        },
    }).then((res) => {
        if (res.isConfirmed && res.value) {
            if (isEdit) {
                router.put(`/superadmin/prodi-kuesioner/opsi/${optionToEdit.id}`, res.value, {
                    preserveScroll: true,
                    onSuccess: () => Swal.fire('Berhasil', 'Opsi jawaban diperbarui.', 'success'),
                });
            } else {
                router.post('/superadmin/prodi-kuesioner/opsi', res.value, {
                    preserveScroll: true,
                    onSuccess: () => Swal.fire('Berhasil', 'Opsi jawaban ditambahkan.', 'success'),
                });
            }
        }
    });
};

const handleDeleteOption = (opt) => {
    Swal.fire({
        title: 'Hapus Opsi Jawaban?',
        text: `Apakah Anda yakin ingin menghapus opsi "${opt.option_text}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus Opsi',
        cancelButtonText: 'Batal',
    }).then((res) => {
        if (res.isConfirmed) {
            router.delete(`/superadmin/prodi-kuesioner/opsi/${opt.id}`, {
                preserveScroll: true,
                onSuccess: () => Swal.fire('Terhapus', 'Opsi jawaban berhasil dihapus.', 'success'),
            });
        }
    });
};
</script>

<template>
    <Head title="Pengaturan Kuesioner Prodi - Super Admin UKDW" />

    <div class="min-h-screen bg-slate-50 text-gray-800 flex font-sans">
        <Sidebar :user="user" />

        <div class="flex-1 flex flex-col min-w-0 lg:pl-72">
            <!-- Header Halaman -->
            <div class="bg-white border-b border-gray-200 px-6 py-5">
                <div class="w-full flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 text-xs text-gray-500 font-medium mb-1">
                            <Link href="/superadmin/dashboard" class="hover:underline hover:text-[#0D542B]">Dashboard</Link>
                            <span>/</span>
                            <span class="text-gray-800 font-semibold">Super Admin</span>
                            <span>/</span>
                            <span class="text-[#0D542B] font-bold">Kuesioner Per Prodi</span>
                        </div>
                        <h1 class="text-xl font-bold text-gray-900">
                            Pengaturan Kuesioner Program Studi
                        </h1>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Pilih program studi untuk mengelola struktur bagian (section) dan butir pertanyaan kuesioner spesifik prodi.
                        </p>
                    </div>
                </div>
            </div>

            <main class="flex-1 p-6 space-y-6 overflow-x-auto min-w-0">
                <!-- Bar Pemilihan Program Studi -->
                <div class="bg-white rounded-lg p-5 border border-slate-200 shadow-2xs">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Program Studi Target
                            </label>
                            <select
                                v-model="selectedProdiId"
                                @change="handleProdiChange"
                                class="w-full md:w-96 px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm font-bold text-[#0D542B] focus:ring-2 focus:ring-[#0D542B] focus:outline-none"
                            >
                                <option v-for="p in prodis" :key="p.id" :value="p.id">
                                    {{ p.kode_prodi ? `[${p.kode_prodi}] ` : '' }}{{ p.nama_prodi }} {{ p.fakultas ? `(${p.fakultas.nama_fakultas})` : '' }}
                                </option>
                            </select>
                        </div>

                        <div class="flex items-center gap-3 bg-slate-50 p-3 rounded-lg border border-slate-200">
                            <div class="text-center px-2">
                                <span class="block text-lg font-extrabold text-[#0D542B]">{{ stats.total_sections }}</span>
                                <span class="text-[10px] text-gray-500 font-semibold uppercase">Bagian / Section</span>
                            </div>
                            <div class="h-8 w-px bg-slate-300"></div>
                            <div class="text-center px-2">
                                <span class="block text-lg font-extrabold text-amber-600">{{ stats.total_questions }}</span>
                                <span class="text-[10px] text-gray-500 font-semibold uppercase">Butir Kuesioner</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigasi Tab -->
                <div class="flex border-b border-slate-200 space-x-4">
                    <button
                        @click="activeTab = 'sections'"
                        class="pb-3 px-3 text-sm font-bold border-b-2 transition-colors cursor-pointer flex items-center gap-2"
                        :class="[
                            activeTab === 'sections'
                                ? 'border-[#0D542B] text-[#0D542B]'
                                : 'border-transparent text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span>Daftar Bagian (Section)</span>
                    </button>
                    <button
                        @click="activeTab = 'questions'"
                        class="pb-3 px-3 text-sm font-bold border-b-2 transition-colors cursor-pointer flex items-center gap-2"
                        :class="[
                            activeTab === 'questions'
                                ? 'border-[#0D542B] text-[#0D542B]'
                                : 'border-transparent text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Daftar Pertanyaan (Kuesioner)</span>
                    </button>
                </div>

                <!-- TAB 1: SECTION PRODI MANAGEMENT -->
                <div v-if="activeTab === 'sections'" class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="w-full sm:w-72">
                            <input
                                type="text"
                                v-model="sectionSearchQuery"
                                placeholder="Cari judul bagian section..."
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-1 focus:ring-[#0D542B] focus:outline-none bg-white"
                            />
                        </div>
                        <button
                            type="button"
                            @click="openSectionModal(null)"
                            class="px-4 py-2 bg-[#0D542B] hover:bg-[#08381c] text-white text-xs font-bold rounded-lg shadow-2xs transition-colors flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <span>+ Tambah Section Baru</span>
                        </button>
                    </div>

                    <!-- Tabel Section -->
                    <div class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-100 text-gray-700 font-bold border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3 text-center w-20">Urutan</th>
                                    <th class="px-4 py-3">Judul Bagian Section</th>
                                    <th class="px-4 py-3">Deskripsi & Petunjuk Pengisian</th>
                                    <th class="px-4 py-3 text-center">Jumlah Pertanyaan</th>
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <tr v-if="filteredSections.length === 0">
                                    <td colspan="5" class="p-8 text-center text-gray-400 italic">
                                        Belum ada bagian (section) kuesioner yang dibuat untuk {{ selectedProdi.nama_prodi }}.
                                    </td>
                                </tr>
                                <tr v-for="(sec, idx) in filteredSections" :key="sec.id" class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1 font-bold text-gray-700">
                                            <span>#{{ sec.order }}</span>
                                            <div class="flex flex-col gap-0.5 ml-1">
                                                <button
                                                    type="button"
                                                    @click="handleReorderSection(sec.id, 'up')"
                                                    :disabled="idx === 0"
                                                    class="p-0.5 rounded text-gray-400 hover:text-emerald-700 disabled:opacity-30 cursor-pointer"
                                                    title="Naikkan Urutan"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                    </svg>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="handleReorderSection(sec.id, 'down')"
                                                    :disabled="idx === filteredSections.length - 1"
                                                    class="p-0.5 rounded text-gray-400 hover:text-emerald-700 disabled:opacity-30 cursor-pointer"
                                                    title="Turunkan Urutan"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 font-bold text-sm text-gray-900">
                                        {{ sec.title }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 max-w-sm">
                                        {{ sec.description || '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-800 font-bold border border-slate-200">
                                            {{ sec.questions_count ?? 0 }} Pertanyaan
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button
                                                type="button"
                                                @click="openSectionModal(sec)"
                                                class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded shadow-2xs transition-colors cursor-pointer"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                type="button"
                                                @click="handleDeleteSection(sec)"
                                                class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded shadow-2xs transition-colors cursor-pointer"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: PERTANYAAN PRODI MANAGEMENT -->
                <div v-if="activeTab === 'questions'" class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-lg border border-slate-200 shadow-2xs">
                        <div class="flex flex-wrap items-center gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase">Filter Section</label>
                                <select v-model="selectedSectionFilter" class="px-2.5 py-1.5 border border-slate-300 rounded text-xs bg-white">
                                    <option value="all">Semua Section</option>
                                    <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.title }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase">Sifat Pengisian</label>
                                <select v-model="selectedRequiredFilter" class="px-2.5 py-1.5 border border-slate-300 rounded text-xs bg-white">
                                    <option value="all">Semua Sifat</option>
                                    <option value="wajib">Wajib (Required)</option>
                                    <option value="opsional">Opsional</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase">Pencarian Teks</label>
                                <input type="text" v-model="questionSearchQuery" placeholder="Cari kode atau pertanyaan..." class="px-2.5 py-1.5 border border-slate-300 rounded text-xs w-48 bg-white" />
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="openQuestionModal(null)"
                            class="px-4 py-2 bg-[#0D542B] hover:bg-[#08381c] text-white text-xs font-bold rounded-lg shadow-2xs transition-colors flex items-center justify-center gap-1.5 cursor-pointer shrink-0"
                        >
                            <span>+ Tambah Pertanyaan Prodi</span>
                        </button>
                    </div>

                    <!-- Tabel Pertanyaan -->
                    <div class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-100 text-gray-700 font-bold border-b border-slate-200">
                                <tr>
                                    <th class="px-3 py-3 text-center w-20">Urutan</th>
                                    <th class="px-3 py-3 w-20">Kode</th>
                                    <th class="px-4 py-3">Butir Pertanyaan Kuesioner</th>
                                    <th class="px-3 py-3">Tipe & Bagian</th>
                                    <th class="px-4 py-3">Opsi Jawaban & Percabangan</th>
                                    <th class="px-3 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <tr v-if="filteredQuestions.length === 0">
                                    <td colspan="6" class="p-8 text-center text-gray-400 italic">
                                        Belum ada butir pertanyaan kuesioner untuk {{ selectedProdi.nama_prodi }}.
                                    </td>
                                </tr>
                                <tr v-for="(q, idx) in filteredQuestions" :key="q.id" class="hover:bg-slate-50 transition-colors">
                                    <td class="px-3 py-3.5 text-center">
                                        <div class="flex items-center justify-center gap-1 font-bold text-gray-700">
                                            <span>#{{ q.order }}</span>
                                            <div class="flex flex-col gap-0.5 ml-1">
                                                <button
                                                    type="button"
                                                    @click="handleReorderQuestion(q.id, 'up')"
                                                    :disabled="idx === 0"
                                                    class="p-0.5 text-gray-400 hover:text-emerald-700 disabled:opacity-30 cursor-pointer"
                                                    title="Naikkan Urutan"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                    </svg>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="handleReorderQuestion(q.id, 'down')"
                                                    :disabled="idx === filteredQuestions.length - 1"
                                                    class="p-0.5 text-gray-400 hover:text-emerald-700 disabled:opacity-30 cursor-pointer"
                                                    title="Turunkan Urutan"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3.5 font-bold font-mono text-emerald-800">
                                        {{ q.code }}
                                    </td>
                                    <td class="px-4 py-3.5 max-w-sm">
                                        <div class="font-bold text-gray-900 text-xs leading-snug">
                                            {{ q.question_text }}
                                        </div>
                                        <div class="mt-1">
                                            <span v-if="q.is_required" class="text-[10px] font-bold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200">
                                                Wajib
                                            </span>
                                            <span v-else class="text-[10px] text-gray-400">
                                                Opsional
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3.5 max-w-xs">
                                        <div class="font-semibold text-gray-800">
                                            {{ typeLabels[q.type] || q.type }}
                                        </div>
                                        <div class="text-[10px] text-gray-500 mt-0.5">
                                            {{ q.section?.title || 'Biasa' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 max-w-md">
                                        <div v-if="canHaveOptions(q.type)">
                                            <div v-if="q.options && q.options.length > 0" class="space-y-1 mb-2">
                                                <div v-for="opt in q.options" :key="opt.id" class="p-1.5 bg-slate-50 border border-slate-200 rounded flex items-center justify-between gap-2 text-[11px]">
                                                    <div class="min-w-0">
                                                        <span class="font-mono font-bold text-slate-700 me-1">[{{ opt.code }}]</span>
                                                        <span>{{ opt.option_text }}</span>
                                                        <span v-if="opt.jump_to" class="ms-1.5 px-1.5 py-0.5 bg-amber-100 text-amber-800 font-bold rounded text-[9px]">
                                                            Lanjut ke: {{ opt.jump_to }}
                                                        </span>
                                                    </div>
                                                    <div class="flex items-center gap-1 shrink-0">
                                                        <button type="button" @click="openOptionModal(q, opt)" class="text-amber-700 hover:underline cursor-pointer">Edit</button>
                                                        <button type="button" @click="handleDeleteOption(opt)" class="text-rose-600 hover:underline cursor-pointer">Hapus</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <button
                                                type="button"
                                                @click="openOptionModal(q, null)"
                                                class="px-2 py-1 bg-emerald-100 text-[#0D542B] font-bold rounded text-[10px] hover:bg-emerald-200 transition-colors cursor-pointer"
                                            >
                                                + Tambah Opsi Jawaban
                                            </button>
                                        </div>
                                        <div v-else class="text-gray-400 italic text-[10px]">
                                            Tipe isian teks/bebas (tanpa pilihan opsi).
                                        </div>
                                    </td>
                                    <td class="px-3 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button
                                                type="button"
                                                @click="openQuestionModal(q)"
                                                class="px-2 py-1 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded text-[11px] cursor-pointer"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                type="button"
                                                @click="handleDeleteQuestion(q)"
                                                class="px-2 py-1 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded text-[11px] cursor-pointer"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>

            <footer class="text-gray-400 text-xs text-center mt-12 py-6 border-t border-gray-100">
                &copy; {{ new Date().getFullYear() }} Universitas Kristen Duta Wacana. Hak Cipta Dilindungi.
            </footer>
        </div>
    </div>
</template>
