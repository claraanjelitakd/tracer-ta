<script setup>
defineProps({
    activeSectionIndex: {
        type: Number,
        required: true,
    },
    totalSections: {
        type: Number,
        required: true,
    },
    isLastVisibleSection: {
        type: Boolean,
        default: false,
    },
    isProcessing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['prev']);
</script>

<template>
    <div>
        <!-- 1. TOMBOL NAVIGASI DESKTOP / PC -->
        <!-- Tombol Kembali (<) Desktop -->
        <button 
            type="button"
            @click="emit('prev')"
            :disabled="activeSectionIndex === 0"
            class="hidden md:flex fixed left-8 bottom-8 z-40 w-14 h-14 rounded-full justify-center items-center bg-[#0D542B] text-white shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 transition-all disabled:opacity-0 disabled:pointer-events-none group border-0 cursor-pointer"
            title="Kembali ke Bagian Sebelumnya"
        >
            <svg class="w-6 h-6 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        <!-- Tombol Lanjut (>) / Selesai (✓) Desktop -->
        <button 
            type="submit"
            :disabled="isProcessing"
            class="hidden md:flex fixed right-8 bottom-8 z-40 w-14 h-14 rounded-full justify-center items-center shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 transition-all group border-0 cursor-pointer disabled:opacity-50"
            :class="[
                isLastVisibleSection 
                    ? 'bg-[#0D542B] text-white' 
                    : 'bg-[#0D542B] text-white'
            ]"
            :title="isLastVisibleSection ? 'Selesai & Kirim Formulir' : 'Lanjut ke Bagian Berikutnya'"
        >
            <!-- Ikon Loading jika form sedang disubmit -->
            <svg v-if="isProcessing" class="animate-spin w-6 h-6" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>

            <!-- Ikon Centang (Selesai) -->
            <svg v-else-if="isLastVisibleSection" class="w-6 h-6 transform group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>

            <!-- Ikon Panah Kanan (Lanjut) -->
            <svg v-else class="w-6 h-6 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
            </svg>
        </button>

        <!-- 2. TOMBOL NAVIGASI MOBILE / SMARTPHONE -->
        <div class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-gray-200 px-4 py-3 flex items-center justify-between shadow-lg">
            <!-- Tombol Kembali Mobile -->
            <button 
                type="button"
                @click="emit('prev')"
                :disabled="activeSectionIndex === 0"
                class="flex items-center gap-1.5 px-4 py-2.5 rounded-xl font-bold text-xs bg-slate-100 text-slate-700 hover:bg-slate-200 active:scale-95 transition-all disabled:opacity-0 disabled:pointer-events-none"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali
            </button>

            <!-- Indikator Bagian Mobile -->
            <span class="text-xs font-bold text-slate-500">
                {{ activeSectionIndex + 1 }} / {{ totalSections }}
            </span>

            <!-- Tombol Lanjut / Selesai Mobile -->
            <button 
                type="submit"
                :disabled="isProcessing"
                class="flex items-center gap-1.5 px-5 py-2.5 rounded-xl font-bold text-xs shadow-xs active:scale-95 transition-all bg-[#0D542B] text-white"
            >
                <span v-if="isProcessing">Menyimpan...</span>
                <span v-else-if="isLastVisibleSection">Kirim</span>
                <span v-else>Lanjut</span>
                
                <svg v-if="!isProcessing && !isLastVisibleSection" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                </svg>
                <svg v-else-if="!isProcessing && isLastVisibleSection" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </button>
        </div>
    </div>
</template>
