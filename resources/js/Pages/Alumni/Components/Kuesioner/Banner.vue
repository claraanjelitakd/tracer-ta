<!--
  Komponen: Banner Judul Bagian Kuesioner Tracer Study Alumni
  File: resources/js/Pages/Alumni/Components/Kuesioner/Banner.vue
  Fungsi: Menampilkan kartu banner hijau judul section aktif dengan indikator "Bagian X dari Y" dan instruksi pengisian.
-->
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
        default: 'Kuesioner Universitas',
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
                    <span class="inline-block bg-[#005B3C] text-white text-[10px] sm:text-[11px] font-black uppercase px-2.5 py-0.5 rounded-md tracking-wider shadow-2xs">
                        BAGIAN {{ currentIndex + 1 }} DARI {{ totalSections }}
                    </span>
                    <span class="text-[11px] sm:text-xs text-gray-500 font-bold">
                        {{ badgeText }}
                    </span>
                </div>
                <!-- Judul Nama Bagian -->
                <h1 class="text-xl sm:text-2xl md:text-3xl font-black text-gray-900 tracking-tight leading-snug">
                    {{ title }}
                </h1>
                <!-- Petunjuk Singkat -->
                <p class="text-gray-600 font-medium text-xs sm:text-sm mt-1">
                    <span v-if="description && !description.toLowerCase().includes('wajib diisi')">
                        {{ description }} Kolom bertanda <span class="text-red-500 font-black">*</span> wajib diisi.
                    </span>
                    <span v-else-if="description">
                        {{ description }}
                    </span>
                    <span v-else>
                        Pilih jawaban yang paling sesuai. Kolom bertanda <span class="text-red-500 font-black">*</span> wajib diisi.
                    </span>
                </p>
            </div>

        </div>
    </div>
</template>
