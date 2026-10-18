<script setup>
const props = defineProps({
    block: {
        type: Object,
        required: true,
    },
    form: {
        type: Object,
        required: true,
    },
});

// Helper radio_text & radio_input
const getRadioSelected = (qId) => {
    const ans = props.form.answers[qId];
    if (ans && typeof ans === 'object') return ans.selected || '';
    return ans || '';
};

const getRadioInput = (qId) => {
    const ans = props.form.answers[qId];
    if (ans && typeof ans === 'object') return ans.input || '';
    return '';
};

const handleRadioSelect = (qId, optionText) => {
    if (!props.form.answers[qId] || typeof props.form.answers[qId] !== 'object') {
        props.form.answers[qId] = {
            selected: optionText,
            input: '',
        };
    } else {
        props.form.answers[qId].selected = optionText;
    }
};

const setRadioInput = (qId, val) => {
    if (!props.form.answers[qId] || typeof props.form.answers[qId] !== 'object') {
        props.form.answers[qId] = {
            selected: '',
            input: val,
        };
    } else {
        props.form.answers[qId].input = val;
    }
};

const isCustomOption = (optionText) => {
    if (!optionText) return false;
    const lower = optionText.toLowerCase();
    return lower.includes('lainnya') || lower.includes('tuliskan') || lower.includes('...') || lower.includes('…');
};

const isCheckboxChecked = (qId, optionText) => {
    const ans = props.form.answers[qId];
    if (!Array.isArray(ans)) return false;
    return ans.includes(optionText);
};

const toggleCheckboxOption = (qId, optionText) => {
    if (!Array.isArray(props.form.answers[qId])) {
        props.form.answers[qId] = [];
    }
    const idx = props.form.answers[qId].indexOf(optionText);
    if (idx > -1) {
        props.form.answers[qId].splice(idx, 1);
    } else {
        props.form.answers[qId].push(optionText);
    }
};

const filterNumberInput = (e) => {
    if (['e', 'E', '+', '-', '.'].includes(e.key)) {
        e.preventDefault();
    }
};
</script>

<template>
    <!-- 1. KARTU HEADER PETUNJUK (Jika Tipe Header) -->
    <div 
        v-if="block.question?.type === 'header'"
        class="bg-white p-5 sm:p-7 md:p-8 rounded-2xl sm:rounded-3xl shadow-xs border border-gray-100"
    >
        <div class="flex items-center gap-2 mb-1.5">
            <span class="inline-block bg-[#0D542B] text-white text-[10px] sm:text-[11px] font-bold uppercase px-2 py-0.5 rounded tracking-wider shadow-2xs">
                {{ block.question.code || 'Petunjuk' }}
            </span>
        </div>
        <h3 class="text-base sm:text-lg md:text-xl font-bold text-gray-900 leading-snug">
            {{ block.question.question_text }}
        </h3>
    </div>

    <!-- 2. KARTU PERTANYAAN NON-MATRIX -->
    <div 
        v-else-if="block.type === 'standard_question'" 
        class="bg-white p-5 sm:p-7 md:p-8 rounded-2xl sm:rounded-3xl shadow-xs border border-gray-100"
    >
        <!-- Header Soal -->
        <h3 class="text-base sm:text-lg md:text-xl font-bold text-gray-900 mb-4 leading-snug flex items-start gap-3">
            <span class="shrink-0 bg-[#0D542B] text-white px-2 py-0.5 rounded text-[11px] font-bold shadow-2xs">
                {{ block.question.code }}
            </span>
            <span>
                {{ block.question.question_text }}
                <span v-if="block.question.is_required" class="text-rose-500 font-bold ml-1">*</span>
            </span>
        </h3>

        <!-- Pilihan Tunggal (Radio / Single Choice) -->
        <div v-if="block.question.type === 'single_choice' || block.question.type === 'radio'">
            <div class="inline-block mb-3 px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-[11px] font-medium text-slate-700">
                Pilih satu jawaban
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <label v-for="opt in block.question.options" :key="opt.id" class="cursor-pointer group block select-none">
                    <input 
                        type="radio" 
                        :name="'q_' + block.question.id" 
                        :value="opt.option_text" 
                        v-model="form.answers[block.question.id]" 
                        class="sr-only"
                    >
                    <div 
                        class="p-4 rounded-xl font-bold text-xs sm:text-sm md:text-base transition-all flex items-center justify-center text-center shadow-2xs min-h-[56px]"
                        :class="[
                            form.answers[block.question.id] === opt.option_text
                                ? 'bg-[#0D542B] text-white shadow-md ring-2 ring-[#0D542B]/30'
                                : 'bg-gray-50 text-gray-700 hover:bg-slate-100 hover:text-slate-900 border border-gray-200'
                        ]"
                    >
                        <span>{{ opt.option_text }}</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- Pilihan Ganda + Lainnya (Radio + Teks) -->
        <div v-else-if="block.question.type === 'radio_text'">
            <div class="inline-block mb-3 px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-[11px] font-medium text-slate-700">
                Pilih salah satu jawaban
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <label v-for="opt in block.question.options" :key="opt.id" class="cursor-pointer group block select-none">
                    <input 
                        type="radio" 
                        :name="'q_' + block.question.id" 
                        :value="opt.option_text" 
                        :checked="getRadioSelected(block.question.id) === opt.option_text"
                        @change="handleRadioSelect(block.question.id, opt.option_text)"
                        class="sr-only"
                    >
                    <div 
                        class="p-4 rounded-xl font-bold text-xs sm:text-sm md:text-base transition-all flex flex-col items-center justify-center text-center shadow-2xs min-h-[56px]"
                        :class="[
                            getRadioSelected(block.question.id) === opt.option_text
                                ? 'bg-[#0D542B] text-white shadow-md ring-2 ring-[#0D542B]/30'
                                : 'bg-gray-50 text-gray-700 hover:bg-slate-100 hover:text-slate-900 border border-gray-200'
                        ]"
                    >
                        <span>{{ opt.option_text }}</span>
                        <div 
                            v-if="getRadioSelected(block.question.id) === opt.option_text && isCustomOption(opt.option_text)" 
                            class="w-full mt-2" 
                            @click.stop
                        >
                            <input 
                                type="text" 
                                :value="getRadioInput(block.question.id)"
                                @input="setRadioInput(block.question.id, $event.target.value)"
                                placeholder="Tuliskan jawaban Anda di sini..." 
                                class="w-full rounded-lg bg-white text-gray-900 placeholder-gray-400 p-2 text-xs sm:text-sm font-normal border border-gray-300 focus:ring-2 focus:ring-[#0D542B] focus:outline-none"
                            >
                        </div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Radio + Angka (Bulan) -->
        <div v-else-if="block.question.type === 'radio_input'">
            <div class="inline-block mb-3 px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-[11px] font-medium text-slate-700">
                Pilih salah satu jawaban
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <label v-for="opt in block.question.options" :key="opt.id" class="cursor-pointer group block select-none">
                    <input 
                        type="radio" 
                        :name="'q_' + block.question.id" 
                        :value="opt.option_text" 
                        :checked="getRadioSelected(block.question.id) === opt.option_text"
                        @change="handleRadioSelect(block.question.id, opt.option_text)"
                        class="sr-only"
                    >
                    <div 
                        class="p-4 rounded-xl font-bold text-xs sm:text-sm md:text-base transition-all flex flex-col items-center justify-center text-center shadow-2xs min-h-[56px]"
                        :class="[
                            getRadioSelected(block.question.id) === opt.option_text
                                ? 'bg-[#0D542B] text-white shadow-md ring-2 ring-[#0D542B]/30'
                                : 'bg-gray-50 text-gray-700 hover:bg-slate-100 hover:text-slate-900 border border-gray-200'
                        ]"
                    >
                        <span>{{ opt.option_text }}</span>
                        <div 
                            v-if="getRadioSelected(block.question.id) === opt.option_text && !opt.option_text.toLowerCase().includes('tidak')" 
                            class="w-full mt-2 flex items-center justify-center gap-2" 
                            @click.stop
                        >
                            <input 
                                type="number" 
                                min="0"
                                :value="getRadioInput(block.question.id)"
                                @input="setRadioInput(block.question.id, $event.target.value)"
                                @keydown="filterNumberInput"
                                placeholder="0" 
                                class="w-24 rounded-lg bg-white text-gray-900 placeholder-gray-400 p-2 text-center text-xs sm:text-sm font-bold border border-gray-300 focus:ring-2 focus:ring-[#0D542B] focus:outline-none"
                            >
                            <span class="text-xs font-bold" :class="getRadioSelected(block.question.id) === opt.option_text ? 'text-white' : 'text-gray-600'">Bulan</span>
                        </div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Pilihan Ganda (Checkbox / Multiple Choice) -->
        <div v-else-if="block.question.type === 'multiple_choice' || block.question.type === 'checkbox'">
            <div class="inline-block mb-3 px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-[11px] font-medium text-slate-700">
                Jawaban bisa lebih dari satu
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <label v-for="opt in block.question.options" :key="opt.id" class="cursor-pointer group block select-none">
                    <input 
                        type="checkbox" 
                        :value="opt.option_text" 
                        :checked="isCheckboxChecked(block.question.id, opt.option_text)"
                        @change="toggleCheckboxOption(block.question.id, opt.option_text)"
                        class="sr-only"
                    >
                    <div 
                        class="p-4 rounded-xl font-bold text-xs sm:text-sm md:text-base transition-all flex items-center justify-center text-center shadow-2xs min-h-[56px]"
                        :class="[
                            isCheckboxChecked(block.question.id, opt.option_text)
                                ? 'bg-[#0D542B] text-white shadow-md ring-2 ring-[#0D542B]/30' 
                                : 'bg-gray-50 text-gray-700 hover:bg-slate-100 hover:text-slate-900 border border-gray-200'
                        ]"
                    >
                        <span>{{ opt.option_text }}</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- Dropdown Select & Searchable Select -->
        <div v-else-if="block.question.type === 'dropdown' || block.question.type === 'searchable_select'">
            <select 
                v-model="form.answers[block.question.id]" 
                :required="block.question.is_required"
                class="w-full rounded-xl bg-gray-50 p-3.5 text-gray-900 font-medium focus:bg-white focus:ring-2 focus:ring-[#0D542B] transition-all text-sm sm:text-base border border-gray-200 shadow-2xs cursor-pointer"
            >
                <option value="" disabled>-- Pilih salah satu opsi --</option>
                <option v-for="opt in block.question.options" :key="opt.id" :value="opt.option_text">
                    {{ opt.option_text }}
                </option>
            </select>
        </div>

        <!-- Isian Teks Bebas Singkat -->
        <div v-else-if="block.question.type === 'text'">
            <input 
                type="text"
                v-model="form.answers[block.question.id]" 
                :required="block.question.is_required"
                class="w-full rounded-xl bg-gray-50 p-3.5 text-gray-900 font-normal focus:bg-white focus:ring-2 focus:ring-[#0D542B] transition-all text-sm sm:text-base border border-gray-200 shadow-2xs"
                placeholder="Tuliskan jawaban Anda di sini..."
            >
        </div>

        <!-- Paragraf (Textarea) -->
        <div v-else-if="block.question.type === 'textarea'">
            <textarea 
                v-model="form.answers[block.question.id]" 
                rows="4" 
                :required="block.question.is_required"
                class="w-full rounded-xl bg-gray-50 p-3.5 text-gray-900 font-normal focus:bg-white focus:ring-2 focus:ring-[#0D542B] transition-all text-sm sm:text-base border border-gray-200 shadow-2xs leading-relaxed"
                placeholder="Tuliskan penjelasan atau jawaban lengkap Anda di sini..."
            ></textarea>
        </div>

        <!-- Isian Angka -->
        <div v-else-if="block.question.type === 'number'">
            <input 
                type="number" 
                v-model="form.answers[block.question.id]" 
                :required="block.question.is_required"
                @keydown="filterNumberInput"
                min="0"
                class="w-full sm:max-w-xs rounded-xl bg-gray-50 p-3.5 text-gray-900 font-bold font-mono focus:bg-white focus:ring-2 focus:ring-[#0D542B] transition-all text-lg sm:text-xl text-center border border-gray-200 shadow-2xs"
                placeholder="0"
            >
        </div>

        <!-- Tanggal / Date -->
        <div v-else-if="block.question.type === 'date'">
            <input 
                type="date" 
                v-model="form.answers[block.question.id]" 
                :required="block.question.is_required"
                class="w-full sm:max-w-xs rounded-xl bg-gray-50 p-3.5 text-gray-900 font-medium focus:bg-white focus:ring-2 focus:ring-[#0D542B] transition-all text-sm sm:text-base border border-gray-200 shadow-2xs"
            >
        </div>

        <!-- Waktu / Time -->
        <div v-else-if="block.question.type === 'time'">
            <input 
                type="time" 
                v-model="form.answers[block.question.id]" 
                :required="block.question.is_required"
                class="w-full sm:max-w-xs rounded-xl bg-gray-50 p-3.5 text-gray-900 font-medium focus:bg-white focus:ring-2 focus:ring-[#0D542B] transition-all text-sm sm:text-base border border-gray-200 shadow-2xs"
            >
        </div>
    </div>
</template>
