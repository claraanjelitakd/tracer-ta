<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';

import Sidebar from '@/Pages/SuperAdmin/Components/Sidebar.vue';

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
// 1. FILTER & PENCARIAN
// ========================================================
const getInitialSectionId = () => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        const secParam = urlParams.get('sec_id');
        if (secParam) {
            const parsed = parseInt(secParam, 10);
            if (props.sections.some((s) => s.id === parsed)) return parsed.toString();
        }
    }
    return 'all';
};

const selectedSectionFilter = ref(getInitialSectionId());
const selectedTargetFilter = ref('all');
const searchQuery = ref('');
const isReordering = ref(false);

// Daftar tipe pertanyaan yang aktif dan relevan (tanpa duplikasi dan tipe yang tidak dipakai)
const typeLabels = {
    // Tipe yang mendukung Opsi Pilihan Jawaban
    radio: 'Pilihan Ganda (Radio)',
    radio_input: 'Radio + Angka',
    radio_text: 'Radio + Teks',
    multiple_choice: 'Kotak Centang (Checkbox)',
    rating_5: 'Skala Rating (1-5)',
    multiple_number: 'Angka Ganda',
    matrix: 'Matriks Skala',
    matrix_dual: 'Dual Matrix',
    multiple_textbox: 'Multiple Textbox',

    // Tipe Isian Langsung & Header (Tanpa Opsi)
    text: 'Jawaban Singkat (Text)',
    textarea: 'Paragraf (Textarea)',
    number: 'Isian Angka (Number)',
    date: 'Tanggal',
    header: 'Header / Judul Bagian',
};

// Hanya tipe-tipe ini yang boleh memiliki pilihan opsi jawaban
const OPTION_SUPPORTED_TYPES = [
    'radio',
    'radio_input',
    'radio_text',
    'multiple_choice',
    'rating_5',
    'multiple_number',
    'matrix',
    'matrix_dual',
    'multiple_textbox',
];

const canHaveOptions = (type) => {
    return OPTION_SUPPORTED_TYPES.includes(type);
};

const filteredQuestions = computed(() => {
    return props.subpertanyaans.filter((q) => {
        if (selectedSectionFilter.value !== 'all') {
            const secId = parseInt(selectedSectionFilter.value, 10);
            if (q.kelompok_pertanyaan_id !== secId) return false;
        }

        if (selectedTargetFilter.value !== 'all') {
            const targetVal = q.tampil_di || 'kuesioner';
            if (selectedTargetFilter.value === 'kuesioner' && targetVal !== 'kuesioner' && targetVal !== 'both') return false;
            if (selectedTargetFilter.value === 'profile' && targetVal !== 'profile' && targetVal !== 'both') return false;
            if (selectedTargetFilter.value === 'both' && targetVal !== 'both') return false;
        }

        if (searchQuery.value.trim()) {
            const query = searchQuery.value.toLowerCase();
            const matchCode = q.kode_pertanyaan?.toLowerCase().includes(query);
            const matchText = q.subpertanyaan?.toLowerCase().includes(query);
            const matchType = (typeLabels[q.type] || q.type)?.toLowerCase().includes(query);
            if (!matchCode && !matchText && !matchType) return false;
        }

        return true;
    });
});

const getTargetBadge = (target) => {
    switch (target) {
        case 'profile':
            return {
                label: 'Profil Alumni',
                classes: 'bg-amber-50 text-amber-800 border-amber-300',
            };
        case 'both':
            return {
                label: 'Kuesioner & Profil',
                classes: 'bg-blue-50 text-blue-800 border-blue-300',
            };
        case 'kuesioner':
        default:
            return {
                label: 'Kuesioner Univ',
                classes: 'bg-emerald-50 text-emerald-800 border-emerald-300',
            };
    }
};

// ========================================================
// 2. SWEETALERT2: MODAL TAMBAH & EDIT PERTANYAAN
// ========================================================
const openQuestionModal = (questionToEdit = null) => {
    const isEdit = !!questionToEdit;
    const initialSectionId = questionToEdit?.kelompok_pertanyaan_id 
        || (selectedSectionFilter.value !== 'all' ? parseInt(selectedSectionFilter.value, 10) : (props.sections[0]?.id || ''));
    const initialCode = questionToEdit?.kode_pertanyaan || '';
    const initialText = questionToEdit?.subpertanyaan || '';
    const initialKeterangan = questionToEdit?.keterangan || '';
    const rawType = questionToEdit?.type;
    const initialType = (rawType === 'single_choice' ? 'radio' : (rawType === 'checkbox' ? 'multiple_choice' : rawType)) || 'radio';
    const initialTarget = questionToEdit?.tampil_di || 'kuesioner';
    const initialWajib = questionToEdit ? (questionToEdit.wajib ? '1' : '0') : '1';
    const initialOrder = questionToEdit?.order || (props.subpertanyaans.length + 1);

    const sectionOptionsHtml = props.sections
        .map((s) => `<option value="${s.id}" ${s.id == initialSectionId ? 'selected' : ''}>Bagian ${s.order}: ${s.title}</option>`)
        .join('');

    const typeOptionsHtml = Object.entries(typeLabels)
        .map(([val, label]) => `<option value="${val}" ${val === initialType ? 'selected' : ''}>${label}</option>`)
        .join('');

    const htmlContent = `
        <div class="text-left space-y-3 text-xs">
            <div>
                <label class="block font-bold text-gray-700 mb-1">Bagian Kuesioner (Section) *</label>
                <select id="swal-section-id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs bg-white focus:outline-none focus:ring-1 focus:ring-emerald-700">
                    ${sectionOptionsHtml}
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Kode Pertanyaan *</label>
                    <input id="swal-kode-pertanyaan" type="text" placeholder="Contoh: F301, F13" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs font-mono uppercase focus:outline-none focus:ring-1 focus:ring-emerald-700" />
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Lokasi Tampil *</label>
                    <select id="swal-tampil-di" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs bg-white focus:outline-none focus:ring-1 focus:ring-emerald-700">
                        <option value="kuesioner" ${initialTarget === 'kuesioner' ? 'selected' : ''}>Kuesioner Universitas</option>
                        <option value="profile" ${initialTarget === 'profile' ? 'selected' : ''}>Profil Alumni</option>
                        <option value="both" ${initialTarget === 'both' ? 'selected' : ''}>Kuesioner & Profil</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Tipe Pertanyaan *</label>
                    <select id="swal-type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs bg-white focus:outline-none focus:ring-1 focus:ring-emerald-700">
                        ${typeOptionsHtml}
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Sifat Pengisian *</label>
                    <select id="swal-wajib" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs bg-white focus:outline-none focus:ring-1 focus:ring-emerald-700">
                        <option value="1" ${initialWajib === '1' ? 'selected' : ''}>Wajib Diisi</option>
                        <option value="0" ${initialWajib === '0' ? 'selected' : ''}>Opsional</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-gray-700 mb-1">Bunyi Kalimat Pertanyaan *</label>
                <textarea id="swal-subpertanyaan" rows="3" placeholder="Ketikkan rumusan butir pertanyaan..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-emerald-700 leading-relaxed"></textarea>
            </div>

            <div>
                <label class="block font-bold text-gray-700 mb-1">Keterangan / Batasan Nilai (Constraint) (Opsional)</label>
                <input id="swal-keterangan" type="text" placeholder="Contoh: Minimal Rp 1.000, kelipatan ribuan, satuan bulan" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-emerald-700" />
                <p class="text-[11px] text-gray-500 mt-1">Gunakan untuk memberikan batasan angka (misal gaji kelipatan 1000) atau petunjuk khusus responden.</p>
            </div>

            <div>
                <label class="block font-bold text-gray-700 mb-1">Nomor Urut</label>
                <input id="swal-order" type="number" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-emerald-700" />
            </div>
        </div>
    `;

    Swal.fire({
        title: isEdit ? 'Sunting Pertanyaan' : 'Tambah Pertanyaan Baru',
        html: htmlContent,
        showCancelButton: true,
        confirmButtonText: isEdit ? 'Simpan Perubahan' : 'Tambah Pertanyaan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#0D542B',
        cancelButtonColor: '#6B7280',
        width: '540px',
        focusConfirm: false,
        didOpen: () => {
            const elCode = document.getElementById('swal-kode-pertanyaan');
            if (elCode) elCode.value = initialCode;
            const elText = document.getElementById('swal-subpertanyaan');
            if (elText) elText.value = initialText;
            const elKet = document.getElementById('swal-keterangan');
            if (elKet) elKet.value = initialKeterangan;
            const elOrder = document.getElementById('swal-order');
            if (elOrder) elOrder.value = initialOrder;
        },
        preConfirm: () => {
            const kelompok_pertanyaan_id = document.getElementById('swal-section-id').value;
            const kode_pertanyaan = document.getElementById('swal-kode-pertanyaan').value.trim();
            const tampil_di = document.getElementById('swal-tampil-di').value;
            const type = document.getElementById('swal-type').value;
            const wajib = document.getElementById('swal-wajib').value === '1';
            const subpertanyaan = document.getElementById('swal-subpertanyaan').value.trim();
            const keterangan = document.getElementById('swal-keterangan').value.trim() || null;
            const order = parseInt(document.getElementById('swal-order').value, 10) || null;

            if (!kelompok_pertanyaan_id) {
                Swal.showValidationMessage('Pilih Bagian Kuesioner.');
                return false;
            }
            if (!kode_pertanyaan) {
                Swal.showValidationMessage('Kode pertanyaan wajib diisi.');
                return false;
            }
            if (!subpertanyaan) {
                Swal.showValidationMessage('Bunyi pertanyaan wajib diisi.');
                return false;
            }

            return {
                kelompok_pertanyaan_id,
                kode_pertanyaan,
                tampil_di,
                type,
                wajib,
                subpertanyaan,
                keterangan,
                order,
            };
        },
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            if (isEdit) {
                router.put(`/superadmin/pertanyaan/${questionToEdit.id}`, result.value, {
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire('Berhasil', 'Pertanyaan berhasil diperbarui.', 'success');
                    },
                    onError: (errors) => {
                        Swal.fire('Gagal Menyimpan', Object.values(errors).join('<br>'), 'error');
                    },
                });
            } else {
                router.post('/superadmin/pertanyaan', result.value, {
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire('Berhasil', 'Pertanyaan baru berhasil ditambahkan.', 'success');
                    },
                    onError: (errors) => {
                        Swal.fire('Gagal Menyimpan', Object.values(errors).join('<br>'), 'error');
                    },
                });
            }
        }
    });
};

// ========================================================
// 3. SWEETALERT2: MODAL DETAIL PERTANYAAN (IKON MATA)
// ========================================================
const openDetailModal = (q) => {
    const options = q.detils || q.options || [];
    const optionsHtml = options.length > 0
        ? `<div class="mt-2 divide-y divide-gray-100 border border-gray-200 rounded-lg max-h-48 overflow-y-auto">
            ${options.map((opt, idx) => `
                <div class="p-2 flex items-center justify-between text-xs">
                    <span class="text-gray-800 font-medium">${idx + 1}. ${opt.option_text || opt.opsi}</span>
                    ${opt.jump_to ? `<span class="px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 text-[10px] font-semibold border border-purple-200">Lompat ke: ${opt.jump_to}</span>` : ''}
                </div>
            `).join('')}
           </div>`
        : '<p class="text-gray-400 italic mt-1 text-xs">Tidak ada opsi pilihan (input langsung).</p>';

    const contentHtml = `
        <div class="text-left space-y-3 text-xs">
            <div class="p-2.5 rounded-lg bg-gray-50 border border-gray-200">
                <span class="text-gray-500 font-medium block">Bunyi Pertanyaan:</span>
                <p class="text-gray-900 font-bold mt-0.5 whitespace-normal leading-relaxed">${q.subpertanyaan}</p>
                ${q.keterangan ? `<p class="text-gray-600 italic mt-1.5 p-1.5 bg-white border border-gray-200 rounded">Batasan / Keterangan: ${q.keterangan}</p>` : ''}
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div class="p-2 rounded-lg bg-gray-50 border border-gray-200">
                    <span class="text-gray-500 block">Bagian:</span>
                    <span class="font-bold text-gray-800 leading-normal">${q.kelompok_pertanyaan?.order ? `Bagian ${q.kelompok_pertanyaan.order}: ` : ''}${q.kelompok_pertanyaan?.title || '-'}</span>
                </div>
                <div class="p-2 rounded-lg bg-gray-50 border border-gray-200">
                    <span class="text-gray-500 block">Tipe Input:</span>
                    <span class="font-bold text-gray-800">${typeLabels[q.type] || q.type}</span>
                </div>
                <div class="p-2 rounded-lg bg-gray-50 border border-gray-200">
                    <span class="text-gray-500 block">Sifat:</span>
                    <span class="font-bold ${q.wajib ? 'text-red-700' : 'text-gray-700'}">${q.wajib ? 'Wajib Diisi' : 'Opsional'}</span>
                </div>
                <div class="p-2 rounded-lg bg-gray-50 border border-gray-200">
                    <span class="text-gray-500 block">Lokasi Tampil:</span>
                    <span class="font-bold text-gray-800">${getTargetBadge(q.tampil_di).label}</span>
                </div>
            </div>

            <div>
                <span class="font-bold text-gray-700 block">Daftar Pilihan Opsi (${options.length}):</span>
                ${optionsHtml}
            </div>
        </div>
    `;

    Swal.fire({
        title: `Detail Soal: ${q.kode_pertanyaan}`,
        html: contentHtml,
        confirmButtonText: 'Tutup',
        confirmButtonColor: '#0D542B',
        width: '540px',
    });
};

// ========================================================
// 4. SWEETALERT2: MODAL TAMBAH & EDIT OPSI (DROPDOWN BERCABANG)
// ========================================================
const openAddOptionModal = (question, optionToEdit = null) => {
    const isEdit = !!optionToEdit;
    const initialText = optionToEdit?.option_text || optionToEdit?.opsi || '';
    const initialCode = optionToEdit?.kode_opsi || '';
    const initialJumpTo = optionToEdit?.jump_to || '';
    const initialOrder = optionToEdit?.order || ((question.detils?.length || 0) + 1);

    // Cari section ID awal dari target lompatan jika sedang mode edit
    let initialTargetSectionId = '';
    if (initialJumpTo) {
        const foundTarget = props.availableJumpTargets.find((t) => t.kode_pertanyaan === initialJumpTo);
        if (foundTarget) {
            initialTargetSectionId = foundTarget.kelompok_pertanyaan_id;
        }
    }

    const sectionSelectHtml = `
        <option value="">-- Tanpa Lompatan (Lanjut ke nomor berikutnya) --</option>
        ${props.sections.map((s) => `
            <option value="${s.id}" ${s.id == initialTargetSectionId ? 'selected' : ''}>
                Bagian ${s.order}: ${s.title}
            </option>
        `).join('')}
    `;

    const htmlContent = `
        <div class="text-left space-y-3 text-xs">
            <div class="p-2.5 rounded-lg bg-gray-50 border border-gray-200">
                <span class="text-gray-500 block font-medium">Pertanyaan Induk:</span>
                <span class="font-bold text-gray-900 leading-normal block mt-0.5">${question.kode_pertanyaan} — ${question.subpertanyaan}</span>
            </div>

            <div>
                <label class="block font-bold text-gray-700 mb-1">Teks Pilihan Opsi Jawaban *</label>
                <input id="swal-option-text" type="text" placeholder="Contoh: Sangat Besar, Ya, Tidak, dll." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-emerald-700" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Kode Opsi (Opsional)</label>
                    <input id="swal-option-code" type="text" placeholder="Contoh: ${question.kode_pertanyaan}-01" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs font-mono focus:outline-none focus:ring-1 focus:ring-emerald-700" />
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Nomor Urut Opsi</label>
                    <input id="swal-option-order" type="number" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-emerald-700" />
                </div>
            </div>

            <!-- Percabangan Bertingkat / Cascading (Pilih Bagian dulu, baru Pilih Soal) -->
            <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg space-y-2.5">
                <span class="font-bold text-gray-800 block">Alur Percabangan / Lompat ke Soal (Jump Logic)</span>
                
                <div>
                    <label class="block text-[11px] font-semibold text-gray-600 mb-1">Langkah 1: Pilih Bagian / Section Target</label>
                    <select id="swal-option-jump-section" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs bg-white focus:outline-none focus:ring-1 focus:ring-emerald-700">
                        ${sectionSelectHtml}
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-gray-600 mb-1">Langkah 2: Pilih Butir Pertanyaan Target</label>
                    <select id="swal-option-jump-question" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs bg-white focus:outline-none focus:ring-1 focus:ring-emerald-700" ${!initialTargetSectionId ? 'disabled' : ''}>
                        <option value="">-- Pilih Pertanyaan Target --</option>
                    </select>
                </div>
                <p class="text-[11px] text-gray-500">Pilih bagian terlebih dahulu untuk memfilter daftar pertanyaan target agar tidak terlalu panjang.</p>
            </div>
        </div>
    `;

    Swal.fire({
        title: isEdit ? 'Sunting Opsi Jawaban' : 'Tambah Opsi Jawaban',
        html: htmlContent,
        showCancelButton: true,
        confirmButtonText: isEdit ? 'Simpan Opsi' : 'Tambah Opsi',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#0D542B',
        cancelButtonColor: '#6B7280',
        width: '540px',
        focusConfirm: false,
        didOpen: () => {
            const optTextInput = document.getElementById('swal-option-text');
            if (optTextInput) optTextInput.value = initialText;
            const optCodeInput = document.getElementById('swal-option-code');
            if (optCodeInput) optCodeInput.value = initialCode;
            const optOrderInput = document.getElementById('swal-option-order');
            if (optOrderInput) optOrderInput.value = initialOrder;

            const sectionSelect = document.getElementById('swal-option-jump-section');
            const questionSelect = document.getElementById('swal-option-jump-question');

            const populateQuestions = (secId, selectedCode = '') => {
                if (!secId) {
                    questionSelect.innerHTML = '<option value="">-- Tidak Ada Lompatan --</option>';
                    questionSelect.disabled = true;
                    return;
                }

                const filtered = props.availableJumpTargets.filter((t) => t.kelompok_pertanyaan_id == secId);
                if (filtered.length === 0) {
                    questionSelect.innerHTML = '<option value="">-- Tidak Ada Pertanyaan di Bagian Ini --</option>';
                    questionSelect.disabled = true;
                    return;
                }

                questionSelect.disabled = false;
                questionSelect.innerHTML = `
                    <option value="">-- Pilih Pertanyaan Target --</option>
                    ${filtered.map((t) => `
                        <option value="${t.kode_pertanyaan}" ${t.kode_pertanyaan === selectedCode ? 'selected' : ''}>
                            ${t.label}
                        </option>
                    `).join('')}
                `;
            };

            // Inisialisasi awal saat modal dibuka
            if (initialTargetSectionId) {
                populateQuestions(initialTargetSectionId, initialJumpTo);
            }

            // Listener perubahan dropdown section
            sectionSelect.addEventListener('change', (e) => {
                populateQuestions(e.target.value);
            });
        },
        preConfirm: () => {
            const option_text = document.getElementById('swal-option-text').value.trim();
            const kode_opsi = document.getElementById('swal-option-code').value.trim() || null;
            const order = parseInt(document.getElementById('swal-option-order').value, 10) || null;
            const jump_to = document.getElementById('swal-option-jump-question').value.trim() || null;

            if (!option_text) {
                Swal.showValidationMessage('Teks pilihan opsi jawaban wajib diisi.');
                return false;
            }

            return {
                option_text,
                kode_opsi,
                order,
                jump_to,
            };
        },
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            if (isEdit) {
                router.put(`/superadmin/pertanyaan/options/${optionToEdit.id}`, result.value, {
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire('Berhasil', 'Opsi jawaban berhasil diperbarui.', 'success');
                    },
                    onError: (errors) => {
                        Swal.fire('Gagal Menyimpan', Object.values(errors).join('<br>'), 'error');
                    },
                });
            } else {
                router.post(`/superadmin/pertanyaan/${question.id}/options`, result.value, {
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire('Berhasil', 'Opsi jawaban baru berhasil ditambahkan.', 'success');
                    },
                    onError: (errors) => {
                        Swal.fire('Gagal Menyimpan', Object.values(errors).join('<br>'), 'error');
                    },
                });
            }
        }
    });
};

// ========================================================
// 5. SWEETALERT2: HAPUS OPSI & HAPUS PERTANYAAN
// ========================================================
const handleDeleteOption = (option) => {
    Swal.fire({
        title: 'Hapus Opsi Jawaban?',
        text: `Apakah Anda yakin ingin menghapus opsi "${option.option_text || option.opsi}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6B7280',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/superadmin/pertanyaan/options/${option.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Berhasil', 'Opsi jawaban telah dihapus.', 'success');
                },
                onError: () => {
                    Swal.fire('Gagal', 'Terjadi kesalahan saat menghapus opsi.', 'error');
                },
            });
        }
    });
};

const handleDeleteQuestion = (q) => {
    Swal.fire({
        title: 'Hapus Butir Pertanyaan?',
        text: `Hapus butir "${q.kode_pertanyaan} - ${q.subpertanyaan?.substring(0, 40)}..." beserta seluruh opsi jawabannya?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6B7280',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/superadmin/pertanyaan/${q.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Berhasil', `Pertanyaan ${q.kode_pertanyaan} telah dihapus.`, 'success');
                },
                onError: () => {
                    Swal.fire('Gagal', 'Terjadi kesalahan saat menghapus pertanyaan.', 'error');
                },
            });
        }
    });
};

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
</script>

<template>
    <Head title="Kelola Butir Pertanyaan - Super Admin" />

    <div class="min-h-screen bg-slate-50 flex font-sans">
        <!-- Sidebar Terpadu Super Admin -->
        <Sidebar />

        <!-- Area Konten Utama -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-72">
            <!-- Header Halaman Bersih & Flat -->
            <div class="bg-white border-b border-gray-200 px-6 py-5">
                <div class="w-full flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">
                            Kelola Butir Pertanyaan
                        </h1>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Konfigurasi butir instrumen pertanyaan, opsi pilihan jawaban, dan aturan lokasi tampil.
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <Link
                            href="/superadmin/sections"
                            class="px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span>Kelola Bagian (Section)</span>
                        </Link>

                        <button
                            type="button"
                            @click="openQuestionModal()"
                            class="px-3.5 py-1.5 bg-[#0D542B] hover:bg-[#08381c] text-white font-semibold text-xs rounded-lg transition-colors flex items-center gap-1.5 cursor-pointer shadow-xs"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Tambah Pertanyaan</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Konten Tabel Utama -->
            <main class="w-full p-6 space-y-4">
                <!-- Bilah Filter & Pencarian -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-3 flex-wrap">
                        <!-- Dropdown Filter Bagian -->
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-gray-600">Bagian:</span>
                            <select
                                v-model="selectedSectionFilter"
                                class="px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs bg-white focus:outline-none focus:ring-1 focus:ring-emerald-700"
                            >
                                <option value="all">Semua Bagian (All - {{ subpertanyaans.length }} Soal)</option>
                                <option v-for="sec in sections" :key="sec.id" :value="sec.id">
                                    Bagian {{ sec.order }}: {{ sec.title }}
                                </option>
                            </select>
                        </div>

                        <!-- Dropdown Filter Target -->
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-gray-600">Lokasi:</span>
                            <select
                                v-model="selectedTargetFilter"
                                class="px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs bg-white focus:outline-none focus:ring-1 focus:ring-emerald-700"
                            >
                                <option value="all">Semua Lokasi</option>
                                <option value="kuesioner">Kuesioner Univ</option>
                                <option value="profile">Profil Alumni</option>
                                <option value="both">Kuesioner & Profil</option>
                            </select>
                        </div>
                    </div>

                    <!-- Kotak Pencarian -->
                    <div class="relative w-full md:w-64">
                        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Cari kode atau teks..."
                            class="w-full pl-8 pr-3 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-emerald-700"
                        />
                    </div>
                </div>

                <!-- Info Jumlah Data -->
                <div class="flex items-center justify-between text-xs text-gray-500 px-1">
                    <span>Menampilkan <strong>{{ filteredQuestions.length }}</strong> dari total <strong>{{ subpertanyaans.length }}</strong> butir pertanyaan</span>
                    <span v-if="searchQuery || selectedSectionFilter !== 'all' || selectedTargetFilter !== 'all'" class="text-emerald-800 font-semibold cursor-pointer hover:underline" @click="selectedSectionFilter = 'all'; selectedTargetFilter = 'all'; searchQuery = '';">
                        Reset Filter
                    </span>
                </div>

                <!-- Tabel Data Pertanyaan: Lapang, Bebas Truncate, Header Warna Kuning -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-2xs">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 font-bold uppercase tracking-wider text-[11px]">
                                <th class="py-3 px-3 w-16 text-center">Urut</th>
                                <th class="py-3 px-3 w-20">Kode</th>
                                <th class="py-3 px-4">Pertanyaan, Keterangan & Pilihan Opsi Jawaban</th>
                                <th class="py-3 px-3 w-28 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr
                                v-for="(q, index) in filteredQuestions"
                                :key="q.id"
                                class="transition-colors align-top"
                                :class="q.type === 'header' ? 'bg-[#FEF9C3] hover:bg-[#FEF08A] border-l-4 border-l-[#EAB308]' : 'hover:bg-slate-50/70'"
                            >
                                <!-- Urutan -->
                                <td class="py-4 px-2 text-center">
                                    <div class="flex flex-col items-center justify-center gap-0.5">
                                        <span class="font-bold text-gray-700 text-xs">{{ q.order }}</span>
                                        <div class="flex items-center gap-0.5">
                                            <button
                                                type="button"
                                                title="Naik"
                                                :disabled="index === 0 || isReordering"
                                                @click="handleMoveQuestion(q, 'up')"
                                                class="p-0.5 text-gray-400 hover:text-gray-800 disabled:opacity-20 cursor-pointer disabled:cursor-not-allowed"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
                                                </svg>
                                            </button>
                                            <button
                                                type="button"
                                                title="Turun"
                                                :disabled="index === filteredQuestions.length - 1 || isReordering"
                                                @click="handleMoveQuestion(q, 'down')"
                                                class="p-0.5 text-gray-400 hover:text-gray-800 disabled:opacity-20 cursor-pointer disabled:cursor-not-allowed"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kode -->
                                <td class="py-4 px-3">
                                    <span
                                        class="inline-block px-1.5 py-0.5 rounded font-mono font-bold text-xs border"
                                        :class="q.type === 'header' ? 'bg-[#FDE047] text-amber-950 border-amber-300' : 'bg-gray-100 text-gray-800 border-gray-200'"
                                    >
                                        {{ q.kode_pertanyaan }}
                                    </span>
                                </td>

                                <!-- Informasi Pertanyaan, Meta Row, dan Opsi Jawaban -->
                                <td class="py-4 px-4 space-y-2.5">
                                    <!-- Header Pertanyaan & Metadata -->
                                    <div class="space-y-1.5">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <!-- Badge Khusus Header Kuning -->
                                                    <span
                                                        v-if="q.type === 'header'"
                                                        class="px-2 py-0.5 rounded text-[11px] font-bold bg-[#FACC15] text-amber-950 border border-amber-400 shrink-0"
                                                    >
                                                        Header Kuesioner
                                                    </span>

                                                    <span class="font-bold text-sm leading-snug whitespace-normal" :class="q.type === 'header' ? 'text-amber-950 text-base' : 'text-gray-900'">
                                                        {{ q.subpertanyaan }}
                                                    </span>

                                                    <span
                                                        v-if="q.wajib"
                                                        class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-200 shrink-0"
                                                    >
                                                        Wajib
                                                    </span>
                                                </div>

                                                <!-- Keterangan / Constraint / Batasan Nilai -->
                                                <p v-if="q.keterangan" class="text-xs italic leading-normal whitespace-normal" :class="q.type === 'header' ? 'text-amber-800 font-medium' : 'text-gray-600'">
                                                    {{ q.keterangan }}
                                                </p>
                                            </div>

                                            <!-- Badge Lokasi Tampil -->
                                            <div class="shrink-0">
                                                <span
                                                    class="inline-block px-2.5 py-1 rounded text-xs font-semibold border"
                                                    :class="getTargetBadge(q.tampil_di).classes"
                                                >
                                                    {{ getTargetBadge(q.tampil_di).label }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Baris Informasi Tambahan (Bagian & Tipe Input Tampil Utuh Kebawah) -->
                                        <div class="flex items-center gap-4 text-xs text-gray-600 flex-wrap pt-0.5">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-gray-400 font-medium">Bagian:</span>
                                                <span class="font-semibold" :class="q.type === 'header' ? 'text-amber-900' : 'text-gray-800'">
                                                    {{ q.kelompok_pertanyaan?.order ? `Bagian ${q.kelompok_pertanyaan.order}: ` : '' }}{{ q.kelompok_pertanyaan?.title || '-' }}
                                                </span>
                                            </div>
                                            <span class="text-gray-300">•</span>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-gray-400 font-medium">Tipe:</span>
                                                <span class="font-semibold" :class="q.type === 'header' ? 'text-amber-900' : 'text-gray-800'">
                                                    {{ typeLabels[q.type] || q.type }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bagian Opsi Jawaban: Hanya jika tipe mendukung opsi dan ada opsinya -->
                                    <div
                                        v-if="canHaveOptions(q.type) && ((q.detils && q.detils.length > 0) || (q.options && q.options.length > 0))"
                                        class="p-3 rounded-lg bg-white border border-gray-200 space-y-2 mt-2"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-gray-800">
                                                Pilihan Opsi Jawaban ({{ (q.detils || q.options)?.length || 0 }} Opsi):
                                            </span>
                                        </div>

                                        <!-- Daftar Item Opsi (Daftar Vertikal Lebar & Teks Tampil Utuh) -->
                                        <div class="divide-y divide-gray-200 border border-gray-200 rounded-lg bg-white overflow-hidden text-xs">
                                            <div
                                                v-for="(opt, oIdx) in (q.detils || q.options)"
                                                :key="opt.id || oIdx"
                                                class="p-2.5 flex items-center justify-between gap-3 hover:bg-slate-50/70"
                                            >
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <span class="w-5 h-5 rounded bg-gray-100 text-gray-700 font-mono font-bold flex items-center justify-center text-xs shrink-0">
                                                        {{ oIdx + 1 }}
                                                    </span>
                                                    <span class="text-gray-900 font-medium whitespace-normal leading-normal">
                                                        {{ opt.option_text || opt.opsi }}
                                                    </span>
                                                    <span
                                                        v-if="opt.jump_to"
                                                        class="px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 text-[10px] font-semibold border border-purple-200 shrink-0"
                                                        :title="'Lompat ke: ' + opt.jump_to"
                                                    >
                                                        → Lompat ke: {{ opt.jump_to }}
                                                    </span>
                                                </div>

                                                <!-- Aksi Edit / Hapus Opsi -->
                                                <div class="flex items-center gap-1 shrink-0">
                                                    <button
                                                        type="button"
                                                        @click="openAddOptionModal(q, opt)"
                                                        title="Edit Opsi"
                                                        class="p-1 text-gray-400 hover:text-emerald-700 rounded hover:bg-gray-100 cursor-pointer"
                                                    >
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="handleDeleteOption(opt)"
                                                        title="Hapus Opsi"
                                                        class="p-1 text-gray-400 hover:text-red-600 rounded hover:bg-gray-100 cursor-pointer"
                                                    >
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="py-4 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- Tambah Opsi (+) Hanya untuk tipe yang mendukung opsi -->
                                        <button
                                            v-if="canHaveOptions(q.type)"
                                            type="button"
                                            @click="openAddOptionModal(q)"
                                            title="Tambah Opsi Jawaban"
                                            class="p-1.5 text-emerald-700 hover:text-emerald-900 hover:bg-emerald-100/60 rounded-lg cursor-pointer"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>

                                        <!-- Detail (Mata) -->
                                        <button
                                            type="button"
                                            @click="openDetailModal(q)"
                                            title="Detail Lengkap"
                                            class="p-1.5 text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded-lg cursor-pointer"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>

                                        <!-- Edit (Pensil) -->
                                        <button
                                            type="button"
                                            @click="openQuestionModal(q)"
                                            title="Sunting Soal"
                                            class="p-1.5 text-gray-500 hover:text-emerald-800 hover:bg-emerald-50 rounded-lg cursor-pointer"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <!-- Hapus (Sampah) -->
                                        <button
                                            type="button"
                                            @click="handleDeleteQuestion(q)"
                                            title="Hapus Soal"
                                            class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg cursor-pointer"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredQuestions.length === 0">
                                <td colspan="4" class="py-12 text-center text-gray-500">
                                    <p class="font-bold text-gray-700">Tidak ada butir pertanyaan ditemukan</p>
                                    <p class="text-xs text-gray-400 mt-1">Coba sesuaikan filter bagian atau kata kunci pencarian Anda.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
</template>
