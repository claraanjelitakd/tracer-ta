<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    subpertanyaan: {
        type: Object,
        default: null,
    },
    targetQuestionMap: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['close']);

const close = () => {
    emit('close');
};

const typeLabels = {
    text: 'Jawaban Singkat (Text)',
    textarea: 'Paragraf (Textarea)',
    number: 'Isian Angka (Number)',
    single_choice: 'Pilihan Ganda (Radio)',
    radio_input: 'Pilihan Ganda + Isian Angka',
    radio_text: 'Pilihan Ganda + Isian Teks',
    multiple_choice: 'Kotak Centang (Checkbox)',
    dropdown: 'Dropdown',
    searchable_select: 'Dropdown Pencarian',
    rating_5: 'Skala Rating (1-5)',
    multiple_number: 'Isian Gaji / Angka Ganda',
    matrix: 'Matriks Skala',
    matrix_dual: 'Matriks Ganda (A vs B)',
    multiple_textbox: 'Multiple Textbox',
    date: 'Tanggal',
    time: 'Waktu',
    file: 'Upload Berkas',
    header: 'Header / Judul Bagian',
};

const getTargetBadge = (target) => {
    switch (target) {
        case 'profile':
            return {
                label: 'Profil Alumni (Biodata)',
                classes: 'bg-amber-50 text-amber-800 border-amber-200',
            };
        case 'both':
            return {
                label: 'Kuesioner & Profil',
                classes: 'bg-blue-50 text-blue-800 border-blue-200',
            };
        case 'kuesioner':
        default:
            return {
                label: 'Kuesioner Universitas',
                classes: 'bg-emerald-50 text-emerald-800 border-emerald-200',
            };
    }
};

const optionsList = computed(() => {
    return props.subpertanyaan?.detils || props.subpertanyaan?.options || [];
});
</script>

<template>
    <div
        v-if="show && subpertanyaan"
        class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
        @click.self="close"
    >
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-gray-100 overflow-hidden transform transition-all">
            <!-- Header Modal -->
            <div class="px-6 py-4 bg-gray-50/80 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="px-2.5 py-1 rounded-lg bg-[#0D542B] text-white font-mono font-bold text-xs">
                        {{ subpertanyaan.kode_pertanyaan }}
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">
                            Detail Butir Pertanyaan
                        </h3>
                        <p class="text-xs text-gray-500">
                            Informasi lengkap butir pertanyaan, opsi jawaban, dan aturan percabangan
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="close"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Konten Modal -->
            <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <!-- Info Utama: Kode, Bagian, Tipe, Lokasi -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
                        <span class="text-[11px] text-gray-500 font-medium block">Nomor Urut</span>
                        <span class="text-xs font-bold text-gray-900 block mt-0.5">#{{ subpertanyaan.order }}</span>
                    </div>
                    <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
                        <span class="text-[11px] text-gray-500 font-medium block">Sifat Pengisian</span>
                        <span
                            class="text-xs font-bold block mt-0.5"
                            :class="subpertanyaan.wajib ? 'text-red-700' : 'text-gray-600'"
                        >
                            {{ subpertanyaan.wajib ? 'Wajib Diisi' : 'Opsional' }}
                        </span>
                    </div>
                    <div class="p-3 rounded-xl bg-gray-50 border border-gray-100 col-span-2">
                        <span class="text-[11px] text-gray-500 font-medium block">Lokasi Tampil</span>
                        <span
                            class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold border mt-0.5"
                            :class="getTargetBadge(subpertanyaan.tampil_di).classes"
                        >
                            {{ getTargetBadge(subpertanyaan.tampil_di).label }}
                        </span>
                    </div>
                </div>

                <!-- Bagian Kuesioner & Tipe Input -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="p-3.5 rounded-xl bg-gray-50 border border-gray-100">
                        <span class="text-[11px] text-gray-500 font-medium block">Bagian Kuesioner (Section)</span>
                        <span class="text-xs font-bold text-gray-900 block mt-1">
                            {{ subpertanyaan.kelompok_pertanyaan?.order ? `Bagian ${subpertanyaan.kelompok_pertanyaan.order}: ` : '' }}
                            {{ subpertanyaan.kelompok_pertanyaan?.title || 'Belum Ditugaskan' }}
                        </span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-gray-50 border border-gray-100">
                        <span class="text-[11px] text-gray-500 font-medium block">Tipe Input</span>
                        <span class="text-xs font-bold text-gray-900 block mt-1">
                            {{ typeLabels[subpertanyaan.type] || subpertanyaan.type }}
                        </span>
                    </div>
                </div>

                <!-- Teks Butir Pertanyaan -->
                <div class="p-4 rounded-xl bg-gray-50 border border-gray-100 space-y-1">
                    <span class="text-[11px] text-gray-500 font-medium block">Bunyi Pertanyaan</span>
                    <p class="text-sm font-semibold text-gray-900 leading-relaxed whitespace-pre-line">
                        {{ subpertanyaan.subpertanyaan }}
                    </p>
                    <p v-if="subpertanyaan.keterangan" class="text-xs text-gray-500 pt-2 border-t border-gray-200/60 mt-2 italic">
                        Keterangan: {{ subpertanyaan.keterangan }}
                    </p>
                </div>

                <!-- Daftar Opsi Jawaban (Jika ada) -->
                <div v-if="optionsList.length > 0" class="p-4 rounded-xl bg-gray-50 border border-gray-100 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-800">
                            Pilihan Opsi Jawaban ({{ optionsList.length }} Opsi)
                        </span>
                    </div>

                    <div class="divide-y divide-gray-200/70 border border-gray-200 rounded-xl bg-white overflow-hidden text-xs">
                        <div
                            v-for="(opt, oIdx) in optionsList"
                            :key="opt.id || oIdx"
                            class="p-3 flex items-center justify-between gap-3 hover:bg-slate-50/50"
                        >
                            <div class="flex items-start gap-2.5 min-w-0">
                                <span class="w-5 h-5 rounded bg-gray-100 font-mono font-bold text-gray-600 flex items-center justify-center text-[10px] shrink-0 mt-0.5">
                                    {{ opt.order ?? (oIdx + 1) }}
                                </span>
                                <div>
                                    <span class="text-gray-900 font-medium block">
                                        {{ opt.option_text || opt.opsi || '-' }}
                                    </span>
                                    <span v-if="opt.value" class="text-[10px] text-gray-400 font-mono">
                                        Value: {{ opt.value }}
                                    </span>
                                </div>
                            </div>

                            <!-- Jump Logic Target jika ada -->
                            <div v-if="opt.jump_to" class="shrink-0 text-right">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                    <span>Lompat ke: {{ opt.jump_to }}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jika tidak ada opsi -->
                <div v-else class="p-3.5 rounded-xl bg-gray-50 border border-gray-100 text-center text-xs text-gray-500">
                    Pertanyaan ini menggunakan input isian langsung tanpa pilihan opsi terpisah.
                </div>
            </div>

            <!-- Footer Modal -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                <button
                    type="button"
                    @click="close"
                    class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors cursor-pointer"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>
