<!--
  Komponen: Tabel Evaluasi Kompetensi F17 Tracer Study Alumni
  File: resources/js/Pages/Alumni/Components/Kuesioner/TabelF17.vue
  Fungsi: Menampilkan tabel perbandingan 2 sisi untuk butir F17:
          Kolom A: Kemampuan Diri Saat Lulus
          Tengah: Aspek Kompetensi & Badge Komparasi
          Kolom B: Kebutuhan Saat Ini / Kontribusi Kampus UKDW
-->
<script setup>
import { computed } from 'vue';

const props = defineProps({
    question: {
        type: Object,
        default: null,
    },
    pairs: {
        type: Array,
        default: () => [],
    },
    form: {
        type: Object,
        required: true,
    },
});

// 7 Aspek Kompetensi Utama Tracer Study 2021
const defaultAspects = [
    { key: 'etika', aspectNumber: 1, name: 'Etika' },
    { key: 'bidang_ilmu', aspectNumber: 2, name: 'Keahlian berdasarkan bidang ilmu' },
    { key: 'bahasa_inggris', aspectNumber: 3, name: 'Bahasa Inggris' },
    { key: 'ti', aspectNumber: 4, name: 'Penggunaan teknologi informasi' },
    { key: 'komunikasi', aspectNumber: 5, name: 'Komunikasi' },
    { key: 'kerjasama_tim', aspectNumber: 6, name: 'Kerja sama tim' },
    { key: 'pengembangan_diri', aspectNumber: 7, name: 'Pengembangan diri' },
];

const isDualMatrixMode = computed(() => {
    return !!props.question && (!props.pairs || props.pairs.length === 0);
});

const activeAspectsList = computed(() => {
    if (isDualMatrixMode.value) {
        return defaultAspects;
    }
    return props.pairs;
});

// Helper getter & setter nilai skor A dan B
const getScoreA = (item) => {
    if (isDualMatrixMode.value) {
        return props.form.answers[props.question.id]?.[item.key]?.A ?? null;
    }
    return props.form.answers[item.qA.id] ?? null;
};

const setScoreA = (item, score) => {
    if (isDualMatrixMode.value) {
        if (!props.form.answers[props.question.id] || typeof props.form.answers[props.question.id] !== 'object') {
            props.form.answers[props.question.id] = {};
        }
        if (!props.form.answers[props.question.id][item.key]) {
            props.form.answers[props.question.id][item.key] = {};
        }
        props.form.answers[props.question.id][item.key].A = score;
    } else {
        props.form.answers[item.qA.id] = score;
    }
};

const getScoreB = (item) => {
    if (isDualMatrixMode.value) {
        return props.form.answers[props.question.id]?.[item.key]?.B ?? null;
    }
    return props.form.answers[item.qB.id] ?? null;
};

const setScoreB = (item, score) => {
    if (isDualMatrixMode.value) {
        if (!props.form.answers[props.question.id] || typeof props.form.answers[props.question.id] !== 'object') {
            props.form.answers[props.question.id] = {};
        }
        if (!props.form.answers[props.question.id][item.key]) {
            props.form.answers[props.question.id][item.key] = {};
        }
        props.form.answers[props.question.id][item.key].B = score;
    } else {
        props.form.answers[item.qB.id] = score;
    }
};

// Hitung berapa aspek F17 yang sudah terisi lengkap (A dan B)
const completedCount = computed(() => {
    return activeAspectsList.value.filter(item => {
        const a = getScoreA(item);
        const b = getScoreB(item);
        return a !== null && a !== '' && b !== null && b !== '';
    }).length;
});

const ratingScores = {
    'Sangat Rendah': 1,
    'Rendah': 2,
    'Cukup': 3,
    'Tinggi': 4,
    'Sangat Tinggi': 5,
};

const getRatingScore = (val) => {
    if (val === undefined || val === null || val === '') return null;
    if (ratingScores[val]) return ratingScores[val];
    const n = Number(val);
    if (!isNaN(n) && n >= 1 && n <= 5) return n;
    return null;
};

// Indikator ringkas perbandingan nilai A (Kemampuan Diri) dan B (Kebutuhan/Kontribusi)
const getF17ComparisonBadge = (valA, valB) => {
    const a = getRatingScore(valA);
    const b = getRatingScore(valB);

    if (a !== null && b !== null) {
        if (a > b) {
            return {
                text: `A (${a}) > B (${b})`,
                badgeClass: 'bg-emerald-50 text-emerald-800 border-emerald-200'
            };
        } else if (a === b) {
            return {
                text: `A (${a}) = B (${b})`,
                badgeClass: 'bg-purple-50 text-purple-800 border-purple-200'
            };
        } else {
            return {
                text: `A (${a}) < B (${b})`,
                badgeClass: 'bg-blue-50 text-blue-800 border-blue-200'
            };
        }
    }
    return null;
};
</script>

<template>
    <div class="space-y-4 sm:space-y-6">
        <!-- Header Banner F17 -->
        <div class="bg-white p-4 sm:p-6 md:p-8 rounded-2xl sm:rounded-[2rem] shadow-sm border border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-block bg-[#005B3C] text-white text-[10px] sm:text-[11px] font-bold uppercase px-2 py-0.5 rounded-md tracking-wider">
                            Instrumen F17
                        </span>
                        <span class="text-[11px] sm:text-xs text-gray-500 font-medium">Evaluasi Kompetensi</span>
                    </div>
                    <h2 class="text-lg sm:text-xl md:text-2xl font-black text-gray-900 leading-snug">
                        Penguasaan Saat Lulus vs Tingkat Kebutuhan Saat Ini
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-600 mt-1">
                        Bandingkan tingkat penguasaan Anda saat lulus (Kolom A) dengan tingkat kebutuhan kompetensi pada pekerjaan saat ini (Kolom B).
                    </p>
                </div>
                <!-- Progress Counter -->
                <div class="flex items-center gap-2.5 sm:gap-3 bg-gray-50 px-3 py-2 sm:px-4 sm:py-2.5 rounded-xl shrink-0 border border-gray-200/80 self-start sm:self-auto">
                    <div class="text-left sm:text-right">
                        <div class="text-[10px] sm:text-[11px] font-semibold text-gray-500">Progres Pengisian</div>
                        <div class="text-sm sm:text-base font-black text-[#005B3C]">
                            {{ completedCount }} / {{ activeAspectsList.length }} Aspek
                        </div>
                    </div>
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-[#005B3C] text-white flex items-center justify-center font-black text-xs shadow-xs">
                        {{ Math.round((completedCount / (activeAspectsList.length || 1)) * 100) }}%
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Komparasi Berdampingan -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Petunjuk Geser Horizontal di Mobile -->
            <div class="block md:hidden px-3.5 py-2 bg-emerald-50/80 text-[#005B3C] text-xs font-bold border-b border-emerald-100 flex items-center justify-between">
                <span class="flex items-center gap-1.5">
                    <span>👉</span>
                    <span>Geser tabel ke samping untuk melihat Kolom B</span>
                </span>
                <span class="text-[10px] bg-emerald-200/60 px-2 py-0.5 rounded-full font-mono">Scroll &rarr;</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[840px] border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <!-- Header Kolom A (Penguasaan Saat Lulus) -->
                            <th class="py-4 px-4 w-[330px] bg-emerald-50/70 text-left border-r border-gray-200">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="w-6 h-6 rounded-md bg-[#005B3C] text-white flex items-center justify-center text-xs font-black">A</span>
                                    <span class="font-black text-emerald-950 text-sm">Penguasaan Saat Lulus</span>
                                </div>
                                <div class="text-xs text-emerald-800 font-medium mb-3">
                                    Tingkat kompetensi yang Anda kuasai saat lulus
                                </div>
                                <div class="flex items-center justify-between max-w-[240px] mx-auto px-1 text-xs font-bold text-emerald-900">
                                    <span class="text-[11px] text-emerald-700 font-medium">1 (Sangat Rendah)</span>
                                    <div class="flex gap-4">
                                        <span class="w-6 text-center">2</span>
                                        <span class="w-6 text-center">3</span>
                                        <span class="w-6 text-center">4</span>
                                    </div>
                                    <span class="text-[11px] text-emerald-700 font-medium">5 (Sangat Tinggi)</span>
                                </div>
                            </th>

                            <!-- Header Tengah (Aspek Kompetensi) -->
                            <th class="py-4 px-4 text-center bg-gray-50 text-gray-800 font-black text-xs uppercase tracking-wider">
                                Aspek Kompetensi
                            </th>

                            <!-- Header Kolom B (Tingkat Kebutuhan Saat Ini) -->
                            <th class="py-4 px-4 w-[330px] bg-blue-50/70 text-left border-l border-gray-200">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="w-6 h-6 rounded-md bg-blue-700 text-white flex items-center justify-center text-xs font-black">B</span>
                                    <span class="font-black text-blue-950 text-sm">Kebutuhan Saat Ini</span>
                                </div>
                                <div class="text-xs text-blue-800 font-medium mb-3">
                                    Tingkat kebutuhan pada bidang pekerjaan Anda
                                </div>
                                <div class="flex items-center justify-between max-w-[240px] mx-auto px-1 text-xs font-bold text-blue-900">
                                    <span class="text-[11px] text-blue-700 font-medium">1 (Sangat Rendah)</span>
                                    <div class="flex gap-4">
                                        <span class="w-6 text-center">2</span>
                                        <span class="w-6 text-center">3</span>
                                        <span class="w-6 text-center">4</span>
                                    </div>
                                    <span class="text-[11px] text-blue-700 font-medium">5 (Sangat Tinggi)</span>
                                </div>
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        <tr 
                            v-for="item in activeAspectsList" 
                            :key="'aspect_' + (item.key || item.aspectNumber)" 
                            class="transition-colors hover:bg-gray-50/60"
                        >
                            <!-- Pilihan Kolom A -->
                            <td class="py-4 px-4 bg-emerald-50/20 border-r border-gray-200">
                                <div class="flex items-center justify-between max-w-[240px] mx-auto">
                                    <label 
                                        v-for="score in 5" 
                                        :key="score" 
                                        class="cursor-pointer select-none"
                                        :title="'Kolom A Skor: ' + score"
                                    >
                                        <input 
                                            type="radio" 
                                            :name="'f17_a_' + (item.key || item.qA.id)" 
                                            :value="score" 
                                            :checked="Number(getScoreA(item)) === score"
                                            @change="setScoreA(item, score)"
                                            class="sr-only"
                                        >
                                        <div 
                                            class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-bold text-xs sm:text-sm transition-all"
                                            :class="[
                                                Number(getScoreA(item)) === score
                                                    ? 'bg-[#005B3C] text-white ring-2 ring-offset-1 ring-emerald-500 shadow-sm scale-105 font-black'
                                                    : 'bg-white text-gray-700 border border-emerald-300 hover:bg-emerald-100/70 hover:border-emerald-400'
                                            ]"
                                        >
                                            {{ score }}
                                        </div>
                                    </label>
                                </div>
                            </td>

                            <!-- Aspek Kompetensi (Tengah) -->
                            <td class="py-4 px-5 text-center">
                                <div class="flex flex-col items-center justify-center gap-1.5">
                                    <span class="font-bold text-gray-900 text-xs sm:text-sm md:text-base leading-snug">
                                        {{ item.aspectNumber }}. {{ item.name || item.aspectName }}
                                    </span>
                                    <!-- Badge perbandingan ringkas (A > B, A = B, A < B) jika keduanya terisi -->
                                    <span 
                                        v-if="getF17ComparisonBadge(getScoreA(item), getScoreB(item))"
                                        class="inline-block px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-semibold border"
                                        :class="getF17ComparisonBadge(getScoreA(item), getScoreB(item)).badgeClass"
                                    >
                                        {{ getF17ComparisonBadge(getScoreA(item), getScoreB(item)).text }}
                                    </span>
                                </div>
                            </td>

                            <!-- Pilihan Kolom B -->
                            <td class="py-4 px-4 bg-blue-50/20 border-l border-gray-200">
                                <div class="flex items-center justify-between max-w-[240px] mx-auto">
                                    <label 
                                        v-for="score in 5" 
                                        :key="score" 
                                        class="cursor-pointer select-none"
                                        :title="'Kolom B Skor: ' + score"
                                    >
                                        <input 
                                            type="radio" 
                                            :name="'f17_b_' + (item.key || item.qB.id)" 
                                            :value="score" 
                                            :checked="Number(getScoreB(item)) === score"
                                            @change="setScoreB(item, score)"
                                            class="sr-only"
                                        >
                                        <div 
                                            class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-bold text-xs sm:text-sm transition-all"
                                            :class="[
                                                Number(getScoreB(item)) === score
                                                    ? 'bg-blue-600 text-white ring-2 ring-offset-1 ring-blue-500 shadow-sm scale-105 font-black'
                                                    : 'bg-white text-gray-700 border border-blue-300 hover:bg-blue-100/70 hover:border-blue-400'
                                            ]"
                                        >
                                            {{ score }}
                                        </div>
                                    </label>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
