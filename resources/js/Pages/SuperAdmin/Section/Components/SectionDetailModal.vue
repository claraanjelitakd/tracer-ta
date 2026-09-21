<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    section: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const close = () => {
    emit('close');
};
</script>

<template>
    <div
        v-if="show && section"
        class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
        @click.self="close"
    >
        <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-gray-100 overflow-hidden transform transition-all">
            <!-- Header Modal -->
            <div class="px-6 py-4 bg-gray-50/80 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-[#0D542B] text-white flex items-center justify-center font-bold text-xs">
                        {{ section.order }}
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">
                            Detail Bagian Kuesioner
                        </h3>
                        <p class="text-xs text-gray-500">
                            Informasi lengkap struktur kelompok kuesioner
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="close"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Konten Modal -->
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-3.5 rounded-xl bg-gray-50 border border-gray-100">
                        <span class="text-xs text-gray-500 font-medium block">Nomor Urut</span>
                        <span class="text-sm font-bold text-gray-900 block mt-0.5">Bagian {{ section.order }}</span>
                    </div>

                    <div class="p-3.5 rounded-xl bg-gray-50 border border-gray-100">
                        <span class="text-xs text-gray-500 font-medium block">Kuesioner Induk</span>
                        <span class="text-sm font-bold text-gray-900 block mt-0.5">
                            {{ section.kuesioner?.title || section.questionnaire?.title || 'Kuesioner Umum' }}
                            <span v-if="section.kuesioner?.year || section.questionnaire?.year" class="text-xs text-gray-500 font-normal">
                                ({{ section.kuesioner?.year || section.questionnaire?.year }})
                            </span>
                        </span>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-gray-50 border border-gray-100">
                    <span class="text-xs text-gray-500 font-medium block">Judul Bagian (Title)</span>
                    <span class="text-sm font-bold text-gray-900 block mt-0.5">{{ section.title }}</span>
                </div>

                <div class="p-3.5 rounded-xl bg-gray-50 border border-gray-100">
                    <span class="text-xs text-gray-500 font-medium block">Deskripsi / Petunjuk Pengisian</span>
                    <p class="text-xs text-gray-700 mt-1 leading-relaxed whitespace-pre-line">
                        {{ section.description || 'Tidak ada deskripsi tambahan untuk bagian ini.' }}
                    </p>
                </div>

                <div class="p-3.5 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-gray-500 font-medium block">Total Butir Pertanyaan</span>
                        <span class="text-xs text-gray-400 mt-0.5 block">Jumlah pertanyaan yang terdaftar dalam bagian ini</span>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-emerald-50 text-[#0D542B] font-bold text-xs border border-emerald-200/60">
                        {{ section.subpertanyaans_count ?? section.questions_count ?? (section.subpertanyaans?.length ?? 0) }} Butir Soal
                    </span>
                </div>
            </div>

            <!-- Footer Modal -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                <button
                    type="button"
                    @click="close"
                    class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>
