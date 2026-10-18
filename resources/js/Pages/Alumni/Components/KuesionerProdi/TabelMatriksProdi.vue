<script setup>
defineProps({
    block: {
        type: Object,
        required: true,
    },
    form: {
        type: Object,
        required: true,
    },
    isQuestionAnswered: {
        type: Function,
        required: true,
    },
    getMatrixScaleOptions: {
        type: Function,
        required: true,
    },
});
</script>

<template>
    <div class="bg-white rounded-2xl sm:rounded-3xl shadow-xs border border-gray-100 overflow-hidden">
        <div class="p-4 sm:p-6 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="inline-block bg-[#0D542B] text-white text-[10px] sm:text-[11px] font-bold uppercase px-2 py-0.5 rounded tracking-wider shadow-2xs">
                    Skala 1 - 5
                </span>
                <span class="text-xs font-bold text-gray-700">
                    Pilihlah skor 1 s/d 5 untuk setiap butir penilaian di bawah ini:
                </span>
            </div>
        </div>

        <div class="overflow-x-auto md:overflow-x-visible">
            <table class="w-full text-left border-collapse min-w-[620px] md:min-w-0">
                <thead>
                    <tr class="bg-gray-50/90 border-b border-gray-200 text-gray-700 text-xs sm:text-sm">
                        <th class="py-3.5 px-3 sm:px-4 font-bold uppercase text-gray-600 text-center w-12">No</th>
                        <th class="py-3.5 px-3 sm:px-4 font-bold text-gray-800">Butir Pernyataan / Evaluasi</th>
                        <th 
                            v-for="opt in getMatrixScaleOptions(block.questions)" 
                            :key="opt.score"
                            class="py-3.5 px-2 text-center font-bold text-xs w-20 sm:w-24 md:w-28"
                        >
                            <div class="font-bold text-gray-900 text-sm sm:text-base">{{ opt.score }}</div>
                            <div class="text-[10px] sm:text-[11px] font-medium text-gray-500 leading-tight mt-0.5">
                                {{ opt.label }}
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr 
                        v-for="(q, qIdx) in block.questions" 
                        :key="q.id" 
                        class="transition-colors hover:bg-emerald-50/20"
                        :class="{'bg-emerald-50/30': isQuestionAnswered(q)}"
                    >
                        <!-- Nomor Urut -->
                        <td class="py-3.5 px-3 sm:px-4 text-center font-bold text-xs sm:text-sm text-gray-500">
                            {{ qIdx + 1 }}
                        </td>

                        <!-- Teks Pertanyaan & Kode -->
                        <td class="py-3.5 px-3 sm:px-4">
                            <div class="flex items-start gap-2.5">
                                <span class="font-mono text-[11px] font-bold px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 border border-gray-200 shrink-0 mt-0.5">
                                    {{ q.code }}
                                </span>
                                <span class="font-bold text-gray-900 text-xs sm:text-sm md:text-base leading-snug">
                                    {{ q.question_text }}
                                    <span v-if="q.is_required" class="text-rose-500 font-bold ml-1">*</span>
                                </span>
                            </div>
                        </td>

                        <!-- 5 Pilihan Skor Bulat -->
                        <td 
                            v-for="opt in getMatrixScaleOptions(block.questions)" 
                            :key="opt.score"
                            class="py-3.5 px-2 text-center"
                        >
                            <label class="cursor-pointer inline-flex items-center justify-center p-1 group">
                                <input 
                                    type="radio" 
                                    :name="'q_' + q.id" 
                                    :value="opt.score" 
                                    v-model="form.answers[q.id]" 
                                    class="sr-only"
                                >
                                <div 
                                    class="w-8 h-8 sm:w-9 sm:h-9 md:w-10 md:h-10 rounded-full flex items-center justify-center font-bold text-xs sm:text-sm md:text-base transition-all shadow-2xs"
                                    :class="[
                                        Number(form.answers[q.id]) === opt.score
                                            ? 'bg-[#0D542B] text-white ring-2 ring-offset-1 ring-[#0D542B] shadow-sm scale-105'
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
</template>
