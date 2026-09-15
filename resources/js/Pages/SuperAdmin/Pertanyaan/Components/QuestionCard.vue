<!--
  Komponen Kartu Pertanyaan Kuesioner (QuestionCard.vue)
  File: resources/js/Pages/SuperAdmin/Pertanyaan/Components/QuestionCard.vue
  
  Desain Profesional & Minimalis Bebas Border Hover:
  - Header: Abu-abu netral bersih (bg-gray-50/70)
  - Opsi Jawaban: Latar bersih (bg-gray-50 hover:bg-gray-100), tanpa hover border
  - Warna Utama: Hijau UKDW #0D542B & Kuning UKDW #FDC700
-->
<script setup>
import { computed } from 'vue';

const props = defineProps({
    subpertanyaan: {
        type: Object,
        required: true,
    },
    index: {
        type: Number,
        required: true,
    },
    totalInActiveSection: {
        type: Number,
        required: true,
    },
    isReordering: {
        type: Boolean,
        default: false,
    },
    targetQuestionMap: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits([
    'editQuestion',
    'deleteQuestion',
    'moveQuestion',
    'addOption',
    'editOption',
    'deleteOption',
]);

const isOptionSupported = computed(() => {
    const supportedTypes = [
        'single_choice',
        'radio',
        'radio_input',
        'radio_text',
        'multiple_choice',
        'checkbox',
        'dropdown',
    ];
    return supportedTypes.includes(props.subpertanyaan.type);
});

const formatQuestionType = (type) => {
    const map = {
        single_choice: 'Pilihan Tunggal (Radio)',
        radio: 'Pilihan Tunggal (Radio)',
        radio_input: 'Pilihan + Isian Angka',
        radio_text: 'Pilihan + Isian Teks',
        multiple_choice: 'Pilihan Ganda (Checkbox)',
        checkbox: 'Pilihan Ganda (Checkbox)',
        dropdown: 'Drop-down Menu',
        text: 'Jawaban Singkat (Text)',
        textarea: 'Paragraf (Textarea)',
        number: 'Angka (Number)',
        multiple_number: 'Banyak Angka',
        rating_5: 'Skala Rating (1-5)',
        rating: 'Skala Rating',
        matrix: 'Matriks / Kisi Pilihan',
        matrix_dual: 'Matriks Ganda (F17)',
        date: 'Tanggal',
        time: 'Waktu',
        file: 'Unggah Berkas',
    };
    return map[type] || type;
};
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 hover:shadow-md transition-shadow overflow-hidden">
        <!-- Header Kartu: Kode, Tipe, Prodi, & Action Buttons -->
        <div class="p-5 sm:p-6 border-b border-gray-100 bg-gray-50/70 flex flex-col md:flex-row md:items-center justify-between gap-4">
            
            <!-- Metadata Pertanyaan -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Badge Nomor & Kode (Hijau Resmi UKDW #0D542B) -->
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-[#0D542B] text-white font-extrabold text-xs">
                    <span>#{{ subpertanyaan.order }}</span>
                    <span class="opacity-60">•</span>
                    <span class="tracking-wide">{{ subpertanyaan.kode_pertanyaan }}</span>
                </span>

                <!-- Badge Tipe Pertanyaan -->
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-gray-200 text-gray-700">
                    {{ formatQuestionType(subpertanyaan.type) }}
                </span>

                <!-- Badge Wajib (Kuning Resmi UKDW #FDC700) / Opsional -->
                <span 
                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold"
                    :class="[
                        subpertanyaan.wajib
                            ? 'bg-[#FDC700] text-black'
                            : 'bg-gray-100 text-gray-600'
                    ]"
                >
                    {{ subpertanyaan.wajib ? 'Wajib Diisi' : 'Opsional' }}
                </span>
            </div>

            <!-- Tombol Pengatur Posisi & Aksi Pertanyaan -->
            <div class="flex items-center gap-1.5 self-end md:self-auto">
                <!-- Pindah Urutan Naik -->
                <button
                    type="button"
                    :disabled="index === 0 || isReordering"
                    @click="emit('moveQuestion', subpertanyaan, 'up')"
                    class="p-2 rounded-lg text-gray-600 hover:text-[#0D542B] hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-gray-200 bg-white"
                    title="Pindahkan ke atas"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                </button>

                <!-- Pindah Urutan Turun -->
                <button
                    type="button"
                    :disabled="index === totalInActiveSection - 1 || isReordering"
                    @click="emit('moveQuestion', subpertanyaan, 'down')"
                    class="p-2 rounded-lg text-gray-600 hover:text-[#0D542B] hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-gray-200 bg-white"
                    title="Pindahkan ke bawah"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>

                <div class="h-4 w-px bg-gray-300 mx-1"></div>

                <!-- Tombol Edit Pertanyaan -->
                <button
                    type="button"
                    @click="emit('editQuestion', subpertanyaan)"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold text-gray-700 hover:text-[#0D542B] hover:bg-gray-100 border border-gray-200 bg-white transition-colors flex items-center gap-1 cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Edit
                </button>

                <!-- Tombol Hapus Pertanyaan -->
                <button
                    type="button"
                    @click="emit('deleteQuestion', subpertanyaan)"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold text-gray-600 hover:text-red-700 hover:bg-gray-100 border border-gray-200 bg-white transition-colors flex items-center gap-1 cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Hapus
                </button>
            </div>
        </div>

        <!-- Isi Teks Pertanyaan -->
        <div class="p-5 sm:p-6">
            <h3 class="text-base sm:text-lg font-extrabold text-gray-900 leading-snug">
                {{ subpertanyaan.subpertanyaan }}
            </h3>

            <!-- Bagian Opsi Pilihan Jawaban -->
            <div v-if="isOptionSupported" class="mt-5 pt-4 border-t border-gray-100">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-extrabold uppercase text-gray-500 tracking-wider">
                        Pilihan Opsi Jawaban ({{ (subpertanyaan.detils || subpertanyaan.options)?.length || 0 }})
                    </span>

                    <!-- Tombol Tambah Opsi (Hijau UKDW #0D542B) -->
                    <button
                        type="button"
                        @click="emit('addOption', subpertanyaan)"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold text-white bg-[#0D542B] hover:bg-[#08381c] transition-colors flex items-center gap-1.5 cursor-pointer shadow-xs"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Tambah Opsi
                    </button>
                </div>

                <!-- Daftar Butir Opsi (Latar Bersih, Tanpa Hover Border) -->
                <div v-if="(subpertanyaan.detils || subpertanyaan.options) && (subpertanyaan.detils || subpertanyaan.options).length > 0" class="space-y-2">
                    <div 
                        v-for="opt in (subpertanyaan.detils || subpertanyaan.options)" 
                        :key="opt.id"
                        class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors group"
                    >
                        <!-- Kiri: Bulatan/Kotak Simbol, Kode Opsi, & Teks Opsi -->
                        <div class="flex items-center gap-3 min-w-0 pr-2">
                            <div 
                                class="w-4 h-4 shrink-0 flex items-center justify-center border-2 border-gray-400 bg-white"
                                :class="subpertanyaan.type === 'multiple_choice' ? 'rounded-md' : 'rounded-full'"
                            ></div>

                            <span class="font-mono text-xs font-bold text-gray-600 bg-white px-2 py-0.5 rounded border border-gray-200 shrink-0">
                                {{ opt.kode_opsi || opt.code || '-' }}
                            </span>

                            <span class="text-sm font-medium text-gray-800 break-words line-clamp-2">
                                {{ opt.option_text }}
                            </span>

                            <!-- Badge Alur Percabangan Jump Logic (Kuning UKDW #FDC700) -->
                            <span 
                                v-if="opt.jump_to" 
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-[#FDC700] text-black shrink-0"
                            >
                                <span>Lompat ke: <strong>{{ opt.jump_to }}</strong></span>
                            </span>
                        </div>

                        <!-- Kanan: Aksi Edit & Hapus Opsi -->
                        <div class="flex items-center gap-1 shrink-0 opacity-80 group-hover:opacity-100 transition-opacity">
                            <button
                                type="button"
                                @click="emit('editOption', subpertanyaan, opt)"
                                class="p-1.5 text-gray-500 hover:text-[#0D542B] hover:bg-white rounded-md transition-colors"
                                title="Edit opsi"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </button>
                            <button
                                type="button"
                                @click="emit('deleteOption', opt)"
                                class="p-1.5 text-gray-500 hover:text-red-700 hover:bg-white rounded-md transition-colors"
                                title="Hapus opsi"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="p-4 rounded-xl bg-gray-50 text-center text-xs text-gray-400">
                    Belum ada opsi jawaban. Klik "Tambah Opsi" untuk memasukkan pilihan.
                </div>
            </div>

            <!-- Catatan khusus tipe rating_5 (Skala Murni Tanpa Opsi Tambahan) -->
            <div v-else-if="subpertanyaan.type === 'rating_5'" class="mt-4 pt-3 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between text-xs text-gray-500 bg-emerald-50/50 p-3 rounded-xl gap-1">
                <span class="font-bold text-emerald-900 flex items-center gap-1.5">
                    <span>⭐</span>
                    <span>Skala Penilaian Range 1 s/d 5 (Jawaban berupa angka murni)</span>
                </span>
                <span class="font-mono text-xs font-semibold text-emerald-800">Skor 1 (Min) ↔ Skor 5 (Maks)</span>
            </div>
        </div>
    </div>
</template>
