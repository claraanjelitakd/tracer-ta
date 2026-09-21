<!--
  Komponen Modal Tambah / Edit Pertanyaan (QuestionModal.vue)
  
  Fungsi:
  Formulir modal dialog terpadu untuk membuat butir pertanyaan baru atau menyunting pertanyaan yang ada.
  Mendukung pemilihan section kuesioner, penentuan kode unik, tipe input, lokasi tampil (Kuesioner/Profil),
  status wajib diisi, nomor urutan, serta feedback SweetAlert2.
-->
<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    isEdit: {
        type: Boolean,
        default: false,
    },
    subpertanyaan: {
        type: Object,
        default: null,
    },
    sections: {
        type: Array,
        default: () => [],
    },
    defaultSectionId: {
        type: [Number, String],
        default: '',
    },
    nextOrder: {
        type: Number,
        default: 1,
    },
});

const emit = defineEmits(['close', 'saved']);

// Formulir reaktif Inertia
const form = useForm({
    id: null,
    kelompok_pertanyaan_id: '',
    kode_pertanyaan: '',
    subpertanyaan: '',
    type: 'single_choice',
    tampil_di: 'kuesioner',
    wajib: true,
    order: null,
});

// Daftar kelompok jenis pertanyaan
const questionTypeGroups = [
    {
        group: 'Teks & Isian',
        types: [
            { value: 'text', label: 'Jawaban Singkat (Text)', hint: 'Isian teks singkat 1 baris.' },
            { value: 'textarea', label: 'Paragraf (Textarea)', hint: 'Uraian teks panjang multibaris.' },
            { value: 'number', label: 'Isian Angka (Number)', hint: 'Isian berupa nilai angka saja.' },
        ],
    },
    {
        group: 'Pilihan & Opsi',
        types: [
            { value: 'single_choice', label: 'Pilihan Ganda (Radio)', hint: 'Memilih satu opsi jawaban.' },
            { value: 'radio_input', label: 'Pilihan Ganda + Isian Angka (seperti F3 & F5)', hint: 'Opsi radio dengan isian angka.' },
            { value: 'radio_text', label: 'Pilihan Ganda + Isian Teks / Lainnya', hint: 'Opsi radio dengan isian teks khusus.' },
            { value: 'multiple_choice', label: 'Kotak Centang (Checkbox)', hint: 'Dapat memilih lebih dari satu jawaban.' },
            { value: 'dropdown', label: 'Menu Dropdown', hint: 'Pilihan opsi dalam bentuk menu tarik-turun.' },
        ],
    },
    {
        group: 'Skala & Penilaian',
        types: [
            { value: 'rating_5', label: 'Skala Rating (1-5 / Likert)', hint: 'Skala penilaian 1 s/d 5 otomatis.' },
            { value: 'multiple_number', label: 'Isian Gaji / Angka Ganda (F13)', hint: 'Isian kolom angka gaji.' },
            { value: 'matrix', label: 'Matriks Pilihan Ganda', hint: 'Tabel matriks evaluasi skala rating.' },
            { value: 'matrix_dual', label: 'Dual Matrix (A vs B)', hint: 'Tabel evaluasi ganda kompetensi vs kontribusi PT.' },
        ],
    },
    {
        group: 'Lainnya',
        types: [
            { value: 'date', label: 'Tanggal', hint: 'Pemilihan tanggal.' },
            { value: 'time', label: 'Waktu', hint: 'Pemilihan waktu.' },
            { value: 'file', label: 'Upload Berkas', hint: 'Unggah dokumen berkas bukti.' },
        ],
    },
];

const getSelectedTypeHint = computed(() => {
    for (const group of questionTypeGroups) {
        const found = group.types.find((t) => t.value === form.type);
        if (found) return found.hint;
    }
    return '';
});

// Pantau pembukaan modal dan inisialisasi form data
watch(() => props.show, (isOpen) => {
    if (isOpen) {
        form.clearErrors();
        if (props.isEdit && props.subpertanyaan) {
            form.id = props.subpertanyaan.id;
            form.kelompok_pertanyaan_id = props.subpertanyaan.kelompok_pertanyaan_id;
            form.kode_pertanyaan = props.subpertanyaan.kode_pertanyaan;
            form.subpertanyaan = props.subpertanyaan.subpertanyaan;
            form.type = props.subpertanyaan.type;
            form.tampil_di = props.subpertanyaan.tampil_di || 'kuesioner';
            form.wajib = !!props.subpertanyaan.wajib;
            form.order = props.subpertanyaan.order;
        } else {
            form.reset();
            form.id = null;
            form.kelompok_pertanyaan_id = props.defaultSectionId || (props.sections[0]?.id || '');
            form.kode_pertanyaan = '';
            form.subpertanyaan = '';
            form.type = 'single_choice';
            form.tampil_di = 'kuesioner';
            form.wajib = true;
            form.order = props.nextOrder || 1;
        }
    }
});

const handleClose = () => {
    form.reset();
    form.clearErrors();
    emit('close');
};

const handleSubmit = () => {
    if (props.isEdit && form.id) {
        form.put(`/superadmin/pertanyaan/${form.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    title: 'Berhasil',
                    text: 'Data pertanyaan berhasil diperbarui.',
                    icon: 'success',
                    confirmButtonColor: '#0D542B',
                    confirmButtonText: 'Selesai',
                });
                emit('saved');
                emit('close');
            },
            onError: (errors) => {
                let errorList = '<ul class="text-left text-xs list-disc list-inside space-y-1 mt-2">';
                for (let key in errors) {
                    errorList += `<li>${errors[key]}</li>`;
                }
                errorList += '</ul>';

                Swal.fire({
                    title: 'Gagal Menyimpan',
                    html: '<p class="text-sm">Ada data yang belum valid:</p>' + errorList,
                    icon: 'error',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Perbaiki',
                });
            },
        });
    } else {
        form.post('/superadmin/pertanyaan', {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    title: 'Berhasil',
                    text: 'Pertanyaan baru berhasil ditambahkan.',
                    icon: 'success',
                    confirmButtonColor: '#0D542B',
                    confirmButtonText: 'Selesai',
                });
                emit('saved');
                emit('close');
            },
            onError: (errors) => {
                let errorList = '<ul class="text-left text-xs list-disc list-inside space-y-1 mt-2">';
                for (let key in errors) {
                    errorList += `<li>${errors[key]}</li>`;
                }
                errorList += '</ul>';

                Swal.fire({
                    title: 'Gagal Menyimpan',
                    html: '<p class="text-sm">Ada data yang belum valid:</p>' + errorList,
                    icon: 'error',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Perbaiki',
                });
            },
        });
    }
};
</script>

<template>
    <div 
        v-if="show" 
        class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 sm:p-6 bg-slate-900/60 backdrop-blur-xs"
        @click.self="handleClose"
    >
        <!-- Modal Dialog Card -->
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full border border-gray-100 overflow-hidden z-10 transform transition-all">
            
            <!-- Header Modal -->
            <div class="px-6 py-4 bg-gray-50/90 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-[#0D542B] text-white flex items-center justify-center font-bold text-xs">
                        {{ isEdit ? 'Edit' : '+' }}
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-gray-900">
                            {{ isEdit ? 'Sunting Butir Pertanyaan' : 'Tambah Pertanyaan Baru' }}
                        </h2>
                        <p class="text-xs text-gray-500">
                            {{ isEdit ? 'Perbarui informasi butir pertanyaan dan konfigurasi tampilan.' : 'Lengkapi isian untuk menambahkan butir pertanyaan baru.' }}
                        </p>
                    </div>
                </div>

                <!-- Tombol Close -->
                <button 
                    type="button" 
                    @click="handleClose"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="handleSubmit" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                
                <!-- Section Kuesioner -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Bagian Kuesioner (Section) <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="form.kelompok_pertanyaan_id"
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[#0D542B] focus:border-[#0D542B] text-xs sm:text-sm bg-gray-50/50"
                    >
                        <option value="" disabled>Pilih Bagian Kuesioner</option>
                        <option v-for="sec in sections" :key="sec.id" :value="sec.id">
                            Bagian {{ sec.order }}: {{ sec.title }}
                        </option>
                    </select>
                    <p v-if="form.errors.kelompok_pertanyaan_id" class="text-xs text-red-500 mt-1 font-medium">
                        {{ form.errors.kelompok_pertanyaan_id }}
                    </p>
                </div>

                <!-- Grid: Kode, Nomor Urut, Lokasi Tampil -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Kode Pertanyaan -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Kode Pertanyaan <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="form.kode_pertanyaan"
                            required
                            placeholder="Contoh: F301, F13"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[#0D542B] focus:border-[#0D542B] text-xs sm:text-sm font-mono uppercase bg-gray-50/50"
                        />
                        <p v-if="form.errors.kode_pertanyaan" class="text-xs text-red-500 mt-1 font-medium">
                            {{ form.errors.kode_pertanyaan }}
                        </p>
                    </div>

                    <!-- Nomor Urut -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Nomor Urut
                        </label>
                        <input
                            type="number"
                            v-model.number="form.order"
                            min="1"
                            placeholder="Urutan ke..."
                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[#0D542B] focus:border-[#0D542B] text-xs sm:text-sm bg-gray-50/50"
                        />
                        <p v-if="form.errors.order" class="text-xs text-red-500 mt-1 font-medium">
                            {{ form.errors.order }}
                        </p>
                    </div>

                    <!-- Lokasi Tampil (Kuesioner vs Profile vs Both) -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Lokasi Tampil <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.tampil_di"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[#0D542B] focus:border-[#0D542B] text-xs sm:text-sm bg-gray-50/50"
                        >
                            <option value="kuesioner">Kuesioner Universitas</option>
                            <option value="profile">Profil Alumni</option>
                            <option value="both">Kuesioner & Profil</option>
                        </select>
                        <p v-if="form.errors.tampil_di" class="text-xs text-red-500 mt-1 font-medium">
                            {{ form.errors.tampil_di }}
                        </p>
                    </div>
                </div>

                <!-- Tipe Pertanyaan -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Tipe Pertanyaan <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="form.type"
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[#0D542B] focus:border-[#0D542B] text-xs sm:text-sm bg-gray-50/50"
                    >
                        <optgroup v-for="group in questionTypeGroups" :key="group.group" :label="group.group">
                            <option v-for="t in group.types" :key="t.value" :value="t.value">
                                {{ t.label }}
                            </option>
                        </optgroup>
                    </select>
                    <p v-if="form.errors.type" class="text-xs text-red-500 mt-1 font-medium">
                        {{ form.errors.type }}
                    </p>
                    <p v-else-if="getSelectedTypeHint" class="text-[11px] text-gray-500 mt-1.5 leading-relaxed font-medium">
                        {{ getSelectedTypeHint }}
                    </p>
                </div>

                <!-- Teks Lengkap Pertanyaan -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Teks Rumusan Pertanyaan <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        v-model="form.subpertanyaan"
                        required
                        rows="3"
                        placeholder="Ketikkan bunyi kalimat pertanyaan kuesioner..."
                        class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[#0D542B] focus:border-[#0D542B] text-xs sm:text-sm bg-gray-50/50 leading-relaxed"
                    ></textarea>
                    <p v-if="form.errors.subpertanyaan" class="text-xs text-red-500 mt-1 font-medium">
                        {{ form.errors.subpertanyaan }}
                    </p>
                </div>

                <!-- Status Wajib Diisi -->
                <div class="flex items-center gap-2.5 p-3 rounded-xl bg-gray-50 border border-gray-200">
                    <input
                        type="checkbox"
                        id="is_required_check"
                        v-model="form.wajib"
                        class="w-4 h-4 text-[#0D542B] rounded border-gray-300 focus:ring-[#0D542B] cursor-pointer"
                    />
                    <label for="is_required_check" class="text-xs font-semibold text-gray-800 cursor-pointer select-none">
                        Pertanyaan ini wajib diisi oleh responden (Required)
                    </label>
                </div>

                <!-- Footer Tombol Aksi -->
                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-2.5">
                    <button
                        type="button"
                        @click="handleClose"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-100 border border-gray-300 transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 rounded-xl text-xs font-semibold text-white bg-[#0D542B] hover:bg-[#08381c] disabled:opacity-50 transition-all cursor-pointer flex items-center gap-1.5"
                    >
                        <span v-if="form.processing">Menyimpan...</span>
                        <span v-else>{{ isEdit ? 'Simpan Perubahan' : 'Tambah Pertanyaan' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
