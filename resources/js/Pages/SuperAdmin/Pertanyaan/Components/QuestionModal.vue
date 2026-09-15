<!--
  Komponen Modal Tambah / Edit Pertanyaan (QuestionModal.vue)
  
  Fungsi:
  Formulir modal dialog terpadu untuk membuat butir pertanyaan baru atau menyunting pertanyaan yang ada.
  Mendukung pemilihan section kuesioner, penentuan kode unik, pembatasan target prodi, tipe input,
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
    wajib: true,
    order: null,
});

// Daftar kelompok jenis pertanyaan lengkap menyerupai Google Forms
const questionTypeGroups = [
    {
        group: 'Teks & Isian',
        types: [
            { value: 'text', label: 'Jawaban singkat', icon: '📝', hint: 'Isian teks singkat 1 baris (nama, jabatan, dsb.).' },
            { value: 'textarea', label: 'Paragraf', icon: '📄', hint: 'Uraian teks panjang multibaris untuk saran atau evaluasi.' },
            { value: 'number', label: 'Isian Angka', icon: '🔢', hint: 'Isian berupa nilai angka saja.' },
        ],
    },
    {
        group: 'Pilihan & Opsi',
        types: [
            { value: 'single_choice', label: 'Pilihan ganda (Radio)', icon: '🔘', hint: 'Memilih satu opsi jawaban. Mendukung isian teks tambahan jika opsi memuat kata "Lainnya".' },
            { value: 'radio_input', label: 'Pilihan ganda + Isian Angka (seperti F3 & F5)', icon: '⏳', hint: 'Opsi radio dengan isian angka di tengah kalimat (misal: "Kira-kira ... bulan sebelum lulus"). Masukkan tanda "..." pada teks opsi.' },
            { value: 'radio_text', label: 'Pilihan ganda + Isian Teks / Lainnya', icon: '✏️', hint: 'Opsi radio dengan isian teks khusus pada pilihan tertentu.' },
            { value: 'multiple_choice', label: 'Kotak Centang (Checkbox)', icon: '☑️', hint: 'Dapat memilih lebih dari satu jawaban. Mendukung opsi "Lainnya" dengan isian teks.' },
            { value: 'dropdown', label: 'Drop-down', icon: '🔽', hint: 'Pilihan opsi dalam bentuk menu tarik-turun.' },
        ],
    },
    {
        group: 'Skala & Penilaian',
        types: [
            { value: 'rating_5', label: 'Skala linier / Rating (1-5 / Likert)', icon: '⭐', hint: 'Skala penilaian 1 s/d 5. Menampung nilai angka murni (1-5) dengan panduan minimum dan maksimum tanpa perlu menambah opsi manual.' },
            { value: 'multiple_number', label: 'Isian Gaji / Angka (F13)', icon: '💰', hint: 'Isian kolom angka gaji (Take Home Pay) dalam satuan ribuan rupiah.' },
            { value: 'matrix', label: 'Kisi pilihan ganda (Matriks)', icon: '⊞', hint: 'Tabel matriks evaluasi skala rating 1 s/d 5 per baris aspek.' },
            { value: 'matrix_dual', label: 'Petak evaluasi ganda (Dual Matrix A vs B)', icon: '▦', hint: 'Tabel evaluasi ganda (Kompetensi vs Kontribusi PT).' },
        ],
    },
    {
        group: 'Tanggal & Berkas',
        types: [
            { value: 'date', label: 'Tanggal', icon: '📅', hint: 'Pemilihan tanggal (hari/bulan/tahun).' },
            { value: 'time', label: 'Waktu', icon: '🕒', hint: 'Pemilihan format jam dan menit.' },
            { value: 'file', label: 'Upload file', icon: '☁️', hint: 'Unggah dokumen atau berkas bukti.' },
        ],
    },
];

// Helper untuk mendapatkan teks petunjuk tipe yang sedang dipilih
const getSelectedTypeHint = computed(() => {
    for (const group of questionTypeGroups) {
        const found = group.types.find((t) => t.value === form.type);
        if (found) return found.hint;
    }
    return '';
});

// Deteksi otomatis apakah input sedang dalam konteks Section F17 atau skala rating F17
const isF17Context = computed(() => {
    const selectedSec = props.sections.find((s) => s.id === form.kelompok_pertanyaan_id);
    const secTitle = selectedSec?.title?.toLowerCase() || '';
    const codeMatch = form.kode_pertanyaan?.toUpperCase().startsWith('F17');
    const typeMatch = form.type === 'rating_5';
    return codeMatch || typeMatch || secTitle.includes('f17') || secTitle.includes('kompetensi');
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
            form.wajib = !!props.subpertanyaan.wajib;
            form.order = props.subpertanyaan.order;
        } else {
            form.reset();
            form.id = null;
            form.kelompok_pertanyaan_id = props.defaultSectionId || (props.sections[0]?.id || '');
            form.kode_pertanyaan = '';
            form.subpertanyaan = '';
            form.type = 'single_choice';
            form.wajib = true;
            form.order = props.nextOrder;
        }
    }
});

// Tutup modal
const handleClose = () => {
    emit('close');
};

// Proses submit form ke backend Laravel
const handleSubmit = () => {
    if (props.isEdit) {
        form.put(`/superadmin/pertanyaan/${form.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Data pertanyaan berhasil diperbarui.',
                    icon: 'success',
                    confirmButtonColor: '#005B3C',
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
                    title: 'Gagal Memperbarui!',
                    html: '<p class="text-sm">Periksa kembali inputan Anda:</p>' + errorList,
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
                    title: 'Berhasil!',
                    text: 'Pertanyaan baru berhasil ditambahkan.',
                    icon: 'success',
                    confirmButtonColor: '#005B3C',
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
                    title: 'Gagal Menyimpan!',
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
        class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 sm:p-6"
    >
        <!-- Backdrop Blur -->
        <div 
            class="fixed inset-0 bg-black/50 backdrop-blur-xs transition-opacity" 
            @click="handleClose"
        ></div>

        <!-- Modal Dialog Card -->
        <div class="relative bg-white rounded-3xl shadow-2xl max-w-2xl w-full border border-gray-100 overflow-hidden z-10 transform transition-all">
            
            <!-- Header Modal -->
            <div class="p-6 bg-gradient-to-r from-emerald-800 to-[#005B3C] text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center font-black text-lg text-yellow-300 shadow-xs">
                        {{ isEdit ? '✎' : '+' }}
                    </div>
                    <div>
                        <h2 class="text-lg font-black tracking-tight">
                            {{ isEdit ? 'Edit Butir Pertanyaan' : 'Tambah Pertanyaan Baru' }}
                        </h2>
                        <p class="text-xs text-emerald-100/90 font-medium">
                            {{ isEdit ? 'Perbarui informasi dan konfigurasi pertanyaan kuesioner.' : 'Isi formulir untuk menambahkan butir pertanyaan baru.' }}
                        </p>
                    </div>
                </div>

                <!-- Tombol Close -->
                <button 
                    type="button" 
                    @click="handleClose"
                    class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors cursor-pointer"
                >
                    ✕
                </button>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="handleSubmit" class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                
                <!-- Section Kuesioner -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Bagian Kuesioner (Section) <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="form.kelompok_pertanyaan_id"
                        required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[#005B3C] focus:border-transparent text-sm bg-gray-50/50"
                    >
                        <option value="" disabled>Pilih Bagian Kuesioner</option>
                        <option v-for="sec in sections" :key="sec.id" :value="sec.id">
                            Section {{ sec.order }}: {{ sec.title }}
                        </option>
                    </select>
                    <p v-if="form.errors.kelompok_pertanyaan_id" class="text-xs text-red-500 mt-1 font-semibold">
                        {{ form.errors.kelompok_pertanyaan_id }}
                    </p>
                </div>

                <!-- Kotak Panduan Format Khusus F17 (Dual Matrix) -->
                <div v-if="isF17Context" class="p-4 rounded-2xl bg-emerald-50/90 border border-emerald-200 text-xs space-y-2 text-emerald-950 transition-all">
                    <div class="flex items-center gap-2 font-extrabold text-emerald-900 text-xs sm:text-sm">
                        <span>💡 Aturan Format Penulisan Dual Matrix (Instrumen F17)</span>
                    </div>
                    <p class="leading-relaxed text-gray-700">
                        Pada tampilan pengisian alumni, instrumen F17 digabungkan otomatis menjadi <strong>satu baris tabel matriks berdampingan</strong> (Kolom A vs Kolom B) dengan aturan penomoran:
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                        <div class="p-2.5 rounded-xl bg-white border border-emerald-200/80 shadow-2xs">
                            <span class="font-bold text-emerald-700 block mb-0.5">🔹 Nomor Ganjil = Kolom A</span>
                            <span class="text-gray-600 block">Tingkat kompetensi yang dikuasai alumni.</span>
                            <span class="text-[11px] text-gray-500 block mt-1 font-mono">Kode: <strong>F17-1</strong>, <strong>F17-3</strong>, dst.</span>
                            <span class="text-[11px] text-gray-500 block font-mono">Format: <em>[Nama Aspek] — Kompetensi yang dikuasai</em></span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-white border border-emerald-200/80 shadow-2xs">
                            <span class="font-bold text-teal-700 block mb-0.5">🔸 Nomor Genap = Kolom B</span>
                            <span class="text-gray-600 block">Kontribusi perguruan tinggi dalam kompetensi.</span>
                            <span class="text-[11px] text-gray-500 block mt-1 font-mono">Kode: <strong>F17-2</strong>, <strong>F17-4</strong>, dst.</span>
                            <span class="text-[11px] text-gray-500 block font-mono">Format: <em>[Nama Aspek] — Kontribusi PT dalam Kompetensi</em></span>
                        </div>
                    </div>
                    <div class="text-[11px] text-emerald-800 font-semibold pt-1 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Tipe Pertanyaan yang digunakan: <strong>Pilihan Tunggal (Radio)</strong> atau <strong>Skala Rating (1-5 / F17)</strong> dengan 5 opsi: Sangat Rendah s/d Sangat Tinggi.</span>
                    </div>
                </div>

                <!-- Grid: Kode & Nomor Urut Posisi -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Kode Pertanyaan -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Kode Pertanyaan <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="form.kode_pertanyaan"
                            required
                            placeholder="Contoh: F3, F13, F17-01"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[#005B3C] focus:border-transparent text-sm font-mono uppercase bg-gray-50/50"
                        />
                        <p v-if="form.errors.kode_pertanyaan" class="text-xs text-red-500 mt-1 font-semibold">
                            {{ form.errors.kode_pertanyaan }}
                        </p>
                    </div>

                    <!-- Nomor Urut -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Nomor Urut Posisi
                        </label>
                        <input
                            type="number"
                            v-model.number="form.order"
                            min="1"
                            placeholder="Otomatis urutan berikutnya"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[#005B3C] focus:border-transparent text-sm bg-gray-50/50"
                        />
                        <p v-if="form.errors.order" class="text-xs text-red-500 mt-1 font-semibold">
                            {{ form.errors.order }}
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
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[#005B3C] focus:border-transparent text-sm bg-gray-50/50"
                    >
                        <optgroup v-for="group in questionTypeGroups" :key="group.group" :label="group.group">
                            <option v-for="t in group.types" :key="t.value" :value="t.value">
                                {{ t.icon }} {{ t.label }}
                            </option>
                        </optgroup>
                    </select>
                    <p v-if="form.errors.type" class="text-xs text-red-500 mt-1 font-semibold">
                        {{ form.errors.type }}
                    </p>
                    <p v-else-if="getSelectedTypeHint" class="text-[11px] text-gray-500 mt-1.5 leading-relaxed font-medium">
                        {{ getSelectedTypeHint }}
                    </p>
                </div>

                <!-- Status Wajib Diisi -->
                <div class="flex items-center gap-3 p-3.5 rounded-xl bg-emerald-50/60 border border-emerald-200/80">
                    <input
                        type="checkbox"
                        id="is_required_check"
                        v-model="form.wajib"
                        class="w-4 h-4 text-[#005B3C] rounded border-gray-300 focus:ring-[#005B3C] cursor-pointer"
                    />
                    <label for="is_required_check" class="text-xs font-bold text-emerald-950 cursor-pointer select-none">
                        Pertanyaan ini Wajib Diisi oleh Alumni (Required)
                    </label>
                </div>

                <!-- Teks Lengkap Pertanyaan -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Teks Kalimat Pertanyaan <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        v-model="form.subpertanyaan"
                        required
                        rows="3"
                        placeholder="Ketikkan rumusan pertanyaan kuesioner..."
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[#005B3C] focus:border-transparent text-sm bg-gray-50/50"
                    ></textarea>
                    <p v-if="form.errors.subpertanyaan" class="text-xs text-red-500 mt-1 font-semibold">
                        {{ form.errors.subpertanyaan }}
                    </p>
                </div>

                <!-- Footer Tombol Aksi -->
                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="handleClose"
                        class="px-5 py-2.5 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 border border-gray-300 transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-[#005B3C] hover:bg-emerald-800 shadow-md shadow-emerald-900/20 disabled:opacity-50 transition-all cursor-pointer flex items-center gap-2"
                    >
                        <span v-if="form.processing">Menyimpan...</span>
                        <span v-else>{{ isEdit ? 'Simpan Perubahan' : 'Tambah Pertanyaan' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
