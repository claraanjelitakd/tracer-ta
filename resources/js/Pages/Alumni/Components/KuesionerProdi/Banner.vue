<script setup>
defineProps({
    currentIndex: {
        type: Number,
        required: true,
    },
    totalSections: {
        type: Number,
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: 'Pilih jawaban yang paling sesuai. Kolom bertanda * wajib diisi.',
    },
    badgeText: {
        type: String,
        default: 'Kuesioner Program Studi',
    },
    percentage: {
        type: Number,
        default: null,
    },
    progressLabel: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <div class="mb-4 sm:mb-6 bg-white p-5 sm:p-7 md:p-8 rounded-2xl sm:rounded-3xl shadow-xs border border-gray-100 w-full">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="inline-block bg-[#0D542B] text-white text-[10px] sm:text-[11px] font-bold uppercase px-2 py-0.5 rounded tracking-wider shadow-2xs">
                        BAGIAN {{ currentIndex + 1 }} DARI {{ totalSections }}
                    </span>
                    <span class="text-[11px] sm:text-xs text-gray-500 font-bold">
                        {{ badgeText }}
                    </span>
                </div>
                <!-- Judul Nama Bagian -->
                <h1 class="text-xl sm:text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight leading-snug">
                    {{ title }}
                </h1>
                <!-- Petunjuk Singkat -->
                <p class="text-gray-600 font-medium text-xs sm:text-sm mt-1">
                    <span v-if="description && !description.toLowerCase().includes('wajib diisi')">
                        {{ description }} Kolom bertanda <span class="text-rose-500 font-bold">*</span> wajib diisi.
                    </span>
                    <span v-else-if="description">
                        {{ description }}
                    </span>
                    <span v-else>
                        Silakan jawab seluruh butir pertanyaan berikut dengan objektif. Kolom bertanda <span class="text-rose-500 font-bold">*</span> wajib diisi.
                    </span>
                </p>
            </div>

            <!-- Progress Indikator Persentase Bagian Kanan -->
            <div v-if="percentage !== null" class="flex flex-col sm:items-end justify-center shrink-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-bold text-gray-400">Progres Pengisian:</span>
                    <span class="text-base sm:text-lg font-black text-[#0D542B]">{{ percentage }}%</span>
                </div>
                <div class="w-32 sm:w-40 bg-gray-100 h-2 rounded-full overflow-hidden">
                    <div 
                        class="bg-[#0D542B] h-full rounded-full transition-all duration-300" 
                        :style="{ width: percentage + '%' }"
                    ></div>
                </div>
                <span v-if="progressLabel" class="text-[10px] text-gray-400 font-semibold mt-1">
                    {{ progressLabel }}
                </span>
            </div>
        </div>
    </div>
</template>
