<!--
  Komponen Modal Tambah / Edit Section Kuesioner (SectionModal.vue)
  
  Fungsi:
  Modal dialog untuk menambah bagian (section) kuesioner baru atau memperbarui judul
  dan urutan section yang sudah ada, lengkap dengan validasi dan notifikasi SweetAlert2.
-->
<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Swal from 'sweetalert2';

// Definisi properti yang diterima dari komponen induk
const props = defineProps({
    // Flag penentu visibilitas modal
    show: {
        type: Boolean,
        default: false,
    },
    // Mode form: true jika sedang mengedit section, false jika menambah baru
    isEdit: {
        type: Boolean,
        default: false,
    },
    // Objek data section yang sedang diedit (null jika mode tambah)
    section: {
        type: Object,
        default: null,
    },
    // Daftar instrumen kuesioner aktif untuk pilihan dropdown
    kuesioners: {
        type: Array,
        default: () => [],
    },
    questionnaires: {
        type: Array,
        default: () => [],
    },
    // Rekomendasi nomor urut otomatis berikutnya jika menambah baru
    nextOrder: {
        type: Number,
        default: 1,
    },
});

// Event yang dipancarkan ke komponen induk
const emit = defineEmits(['close', 'saved']);

const kuesionerList = computed(() => {
    return props.kuesioners && props.kuesioners.length ? props.kuesioners : props.questionnaires;
});

// Inisialisasi state form reaktif menggunakan useForm dari Inertia
const form = useForm({
    id: null,
    kuesioner_id: '',
    questionnaire_id: '',
    title: '',
    order: null,
});

/**
 * Sinkronisasi isi form setiap kali modal dibuka atau data section yang diedit berganti.
 */
watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            form.clearErrors();

            if (props.isEdit && props.section) {
                // Mode Edit: Salin data section terpilih ke dalam formulir
                const kid = props.section.kuesioner_id || props.section.questionnaire_id || '';
                form.id = props.section.id;
                form.kuesioner_id = kid;
                form.questionnaire_id = kid;
                form.title = props.section.title;
                form.order = props.section.order;
            } else {
                // Mode Tambah Baru: Reset form ke nilai awal default
                form.reset();
                form.id = null;
                // Pilih kuesioner pertama sebagai default jika tersedia
                const firstId = kuesionerList.value.length > 0 ? kuesionerList.value[0].id : '';
                form.kuesioner_id = firstId;
                form.questionnaire_id = firstId;
                form.title = '';
                form.order = props.nextOrder;
            }
        }
    }
);

/**
 * Menutup modal dialog dan memancarkan event 'close'.
 */
const closeModal = () => {
    form.clearErrors();
    emit('close');
};

/**
 * Menangani pengiriman formulir (Submit Store atau Update) ke Backend.
 */
const handleSubmit = () => {
    if (props.isEdit && form.id) {
        // Eksekusi pembaruan data section via HTTP PUT
        form.put(`/superadmin/sections/${form.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                emit('saved');
                Swal.fire({
                    title: 'Berhasil Diperbarui!',
                    text: 'Data bagian kuesioner berhasil diperbarui.',
                    icon: 'success',
                    confirmButtonColor: '#005B3C',
                    confirmButtonText: 'Tutup',
                });
            },
            onError: () => {
                Swal.fire({
                    title: 'Gagal Menyimpan!',
                    text: 'Mohon periksa kembali kolom input formulir Anda.',
                    icon: 'error',
                    confirmButtonColor: '#d33',
                });
            },
        });
    } else {
        // Eksekusi penambahan section baru via HTTP POST
        form.post('/superadmin/sections', {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                emit('saved');
                Swal.fire({
                    title: 'Berhasil Ditambahkan!',
                    text: 'Bagian kuesioner baru berhasil dibuat.',
                    icon: 'success',
                    confirmButtonColor: '#005B3C',
                    confirmButtonText: 'Tutup',
                });
            },
            onError: () => {
                Swal.fire({
                    title: 'Gagal Menyimpan!',
                    text: 'Mohon periksa kembali kolom input formulir Anda.',
                    icon: 'error',
                    confirmButtonColor: '#d33',
                });
            },
        });
    }
};
</script>

<template>
    <!-- Overlay Latar Belakang Modal -->
    <div 
        v-if="show" 
        class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4 transition-all duration-300"
    >
        <!-- Wadah Konten Modal Dialog -->
        <div 
            class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-gray-100 transform transition-all duration-300 scale-100"
            role="dialog"
            aria-modal="true"
        >
            <!-- Header Modal -->
            <div class="px-6 py-5 bg-gradient-to-r from-[#005B3C] to-[#007b55] flex items-center justify-between text-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center border border-white/20">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold tracking-tight">
                            {{ isEdit ? 'Sunting Bagian Kuesioner' : 'Tambah Bagian Kuesioner Baru' }}
                        </h3>
                        <p class="text-xs text-emerald-100 font-medium mt-0.5">
                            {{ isEdit ? 'Perbarui judul dan atribut nomor urutan section' : 'Tentukan judul dan urutan penempatan section kuesioner' }}
                        </p>
                    </div>
                </div>

                <!-- Tombol Tutup X -->
                <button 
                    type="button" 
                    @click="closeModal" 
                    class="text-white/80 hover:text-white p-1 rounded-full hover:bg-white/10 transition-colors cursor-pointer"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="handleSubmit" class="p-6 sm:p-8 space-y-6">
                <!-- 1. Pilihan Kuesioner Induk -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                        Kuesioner Induk <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="form.kuesioner_id"
                        @change="form.questionnaire_id = form.kuesioner_id"
                        class="w-full px-4 py-3 rounded-xl border text-sm font-medium focus:ring-2 focus:ring-[#005B3C] focus:border-transparent bg-gray-50/50"
                        :class="(form.errors.kuesioner_id || form.errors.questionnaire_id) ? 'border-red-400 bg-red-50/30' : 'border-gray-200'"
                    >
                        <option value="" disabled>-- Pilih Kuesioner --</option>
                        <option 
                            v-for="q in kuesionerList" 
                            :key="q.id" 
                            :value="q.id"
                        >
                            {{ q.title }} (Tahun {{ q.year }}) {{ q.is_active ? '— [Aktif]' : '' }}
                        </option>
                    </select>
                    <p v-if="form.errors.kuesioner_id || form.errors.questionnaire_id" class="text-xs text-red-500 font-semibold mt-1">
                        {{ form.errors.kuesioner_id || form.errors.questionnaire_id }}
                    </p>
                </div>

                <!-- 2. Judul Section -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                        Judul Bagian (Section Title) <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        v-model="form.title"
                        placeholder="Contoh: Identitas Diri & Riwayat Pendidikan"
                        class="w-full px-4 py-3 rounded-xl border text-sm font-medium focus:ring-2 focus:ring-[#005B3C] focus:border-transparent bg-gray-50/50"
                        :class="form.errors.title ? 'border-red-400 bg-red-50/30' : 'border-gray-200'"
                    />
                    <p v-if="form.errors.title" class="text-xs text-red-500 font-semibold mt-1">
                        {{ form.errors.title }}
                    </p>
                    <p class="text-[11px] text-gray-400 mt-1">
                        Judul bagian akan tampil di tab navigasi pengisian kuesioner alumni.
                    </p>
                </div>

                <!-- 3. Nomor Urutan Section -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                        Nomor Urutan (Order)
                    </label>
                    <input
                        type="number"
                        v-model.number="form.order"
                        min="1"
                        placeholder="Otomatis mengikuti nomor terakhir jika kosong"
                        class="w-full px-4 py-3 rounded-xl border text-sm font-medium focus:ring-2 focus:ring-[#005B3C] focus:border-transparent bg-gray-50/50"
                        :class="form.errors.order ? 'border-red-400 bg-red-50/30' : 'border-gray-200'"
                    />
                    <p v-if="form.errors.order" class="text-xs text-red-500 font-semibold mt-1">
                        {{ form.errors.order }}
                    </p>
                    <p class="text-[11px] text-gray-400 mt-1">
                        Menentukan urutan tampilan Section 1, Section 2, dst. Anda juga dapat mengubah urutannya langsung dengan tombol naik/turun di daftar section.
                    </p>
                </div>

                <!-- Aksi Tombol Footer Modal -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button
                        type="button"
                        @click="closeModal"
                        class="px-5 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 font-bold text-xs sm:text-sm transition-all cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl bg-gray-900 hover:bg-[#005B3C] text-white font-bold text-xs sm:text-sm shadow-md transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50 active:scale-98"
                    >
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ isEdit ? 'Simpan Perubahan' : 'Buat Bagian' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
