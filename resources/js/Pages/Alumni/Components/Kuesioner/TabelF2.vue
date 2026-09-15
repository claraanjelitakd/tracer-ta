<!--
  Komponen: Tabel Penekanan Metode Pembelajaran F2 Tracer Study Alumni
  File: resources/js/Pages/Alumni/Components/Kuesioner/TabelF2.vue
  Fungsi: Menampilkan matriks penilaian penekanan metode pembelajaran (F21 s/d F27)
          dengan skala 1 s/d 5 (Sangat Besar s/d Tidak Sama Sekali).
-->
<script setup>
import { computed } from 'vue';

const props = defineProps({
    headerQuestion: {
        type: Object,
        default: null,
    },
    questions: {
        type: Array,
        required: true,
    },
    form: {
        type: Object,
        required: true,
    },
});

// Daftar opsi skala 1-5 untuk Metode Pembelajaran
const scaleOptions = [
    { score: 1, label: 'Sangat Besar', short: 'SB' },
    { score: 2, label: 'Besar', short: 'B' },
    { score: 3, label: 'Cukup Besar', short: 'CB' },
    { score: 4, label: 'Kurang', short: 'K' },
    { score: 5, label: 'Tidak Sama Sekali', short: 'TSS' },
];

// Menghitung berapa metode yang sudah dinilai
const completedCount = computed(() => {
    return props.questions.filter(q => {
        const val = props.form.answers[q.id];
        return val !== undefined && val !== null && val !== '';
    }).length;
});

const handleSelect = (qId, optionText) => {
    props.form.answers[qId] = optionText;
};
</script>

<template>
    <div class="space-y-4 sm:space-y-6">
        <!-- Header Banner F2 -->
        <div class="bg-white p-4 sm:p-6 md:p-8 rounded-2xl sm:rounded-3xl shadow-xs border border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-block bg-[#005B3C] text-white text-[10px] sm:text-[11px] font-black uppercase px-2 py-0.5 rounded-md tracking-wider">
                            Instrumen F2
                        </span>
                        <span class="text-[11px] sm:text-xs text-gray-500 font-bold">Metode Pembelajaran</span>
                    </div>
                    <h2 class="text-base sm:text-xl md:text-2xl font-black text-gray-900 leading-snug">
                        {{ headerQuestion?.question_text || 'Menurut anda seberapa besar penekanan pada metode pembelajaran di bawah ini dilaksanakan di program studi anda?' }}
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-600 mt-1 font-medium">
                        Pilihlah tingkat penekanan dari 1 (Sangat Besar) hingga 5 (Tidak Sama Sekali) untuk setiap metode.
                    </p>
                </div>

                <!-- Counter Selesai -->
                <div class="flex items-center gap-2.5 sm:gap-3 bg-emerald-50/60 px-3 py-2 sm:px-4 sm:py-2.5 rounded-xl shrink-0 border border-emerald-200/70 self-start sm:self-auto">
                    <div class="text-left sm:text-right">
                        <div class="text-[10px] sm:text-[11px] font-bold text-emerald-800">Metode Terisi</div>
                        <div class="text-sm sm:text-base font-black text-[#005B3C]">
                            {{ completedCount }} / {{ questions.length }}
                        </div>
                    </div>
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-[#005B3C] text-white flex items-center justify-center font-black text-xs shadow-2xs">
                        {{ Math.round((completedCount / (questions.length || 1)) * 100) }}%
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Matriks Penilaian -->
        <div class="bg-white rounded-2xl sm:rounded-3xl shadow-xs border border-gray-200/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[620px]">
                    <thead>
                        <tr class="bg-gray-50/90 border-b border-gray-200/80 text-gray-700 text-xs sm:text-sm">
                            <th class="py-3.5 px-4 font-black uppercase text-gray-600 text-center w-12">No</th>
                            <th class="py-3.5 px-4 font-black text-gray-800 min-w-[220px]">Metode Pembelajaran</th>
                            <th 
                                v-for="opt in scaleOptions" 
                                :key="opt.score"
                                class="py-3.5 px-2 text-center font-black text-xs min-w-[85px]"
                            >
                                <div class="font-extrabold text-gray-900">{{ opt.score }}</div>
                                <div class="text-[10px] sm:text-[11px] font-medium text-gray-500 leading-tight mt-0.5">
                                    {{ opt.label }}
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr 
                            v-for="(q, idx) in questions" 
                            :key="q.id" 
                            class="transition-colors hover:bg-emerald-50/20"
                            :class="{'bg-emerald-50/30': !!form.answers[q.id]}"
                        >
                            <!-- Nomor Urut -->
                            <td class="py-3.5 px-4 text-center font-black text-xs sm:text-sm text-gray-500">
                                {{ idx + 1 }}
                            </td>

                            <!-- Nama Metode -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-[11px] font-black px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 border border-gray-200/60 shrink-0">
                                        {{ q.code }}
                                    </span>
                                    <span class="font-bold text-gray-900 text-xs sm:text-sm md:text-base">
                                        {{ q.question_text }}
                                    </span>
                                </div>
                            </td>

                            <!-- 5 Pilihan Skala Radio -->
                            <td 
                                v-for="opt in scaleOptions" 
                                :key="opt.score"
                                class="py-3.5 px-2 text-center"
                            >
                                <label class="cursor-pointer inline-flex items-center justify-center p-1 group">
                                    <input 
                                        type="radio" 
                                        :name="'f2_' + q.id" 
                                        :value="opt.label" 
                                        :checked="form.answers[q.id] === opt.label || form.answers[q.id] == opt.score"
                                        @change="handleSelect(q.id, opt.label)" 
                                        class="sr-only"
                                    >
                                    <div 
                                        class="w-8 h-8 sm:w-9 sm:h-9 rounded-full flex items-center justify-center font-bold text-xs sm:text-sm transition-all"
                                        :class="[
                                            (form.answers[q.id] === opt.label || form.answers[q.id] == opt.score)
                                                ? 'bg-[#005B3C] text-white ring-2 ring-offset-1 ring-[#005B3C] shadow-sm scale-105 font-black'
                                                : 'bg-white text-gray-700 border border-gray-300 hover:bg-emerald-50 hover:border-emerald-500'
                                        ]"
                                    >
                                        {{ opt.score }}
                                    </div>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
