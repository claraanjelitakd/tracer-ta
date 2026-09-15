<!--
  Komponen Tab Section Kuesioner (SectionTabs.vue)
  
  Fungsi:
  Menyajikan navigasi tab horizontal antar-bagian kuesioner dengan estetika minimalis & elegan
  yang sama persis seperti tab pada halaman Profil Alumni.
-->
<script setup>
import { computed, ref, onMounted, watch, nextTick } from 'vue';

const props = defineProps({
    sections: {
        type: Array,
        default: () => [],
    },
    activeSectionId: {
        type: [Number, String],
        default: null,
    },
    subpertanyaans: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['select']);

const tabsContainer = ref(null);
const activeTabRef = ref(null);

// Hitung jumlah butir pertanyaan per section ID
const getQuestionCountBySection = (sectionId) => {
    return props.subpertanyaans.filter((q) => q.kelompok_pertanyaan_id === sectionId).length;
};

// Urutkan sections berdasarkan properti order
const sortedSections = computed(() => {
    return [...props.sections].sort((a, b) => (a.order || 0) - (b.order || 0));
});

const handleSelect = (sectionId) => {
    emit('select', sectionId);
};

// Pastikan tab aktif otomatis terlihat dan berada di area pandang secara horizontal
const scrollToActiveTab = () => {
    nextTick(() => {
        if (activeTabRef.value && activeTabRef.value[0]) {
            activeTabRef.value[0].scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
                inline: 'center',
            });
        }
    });
};

onMounted(() => {
    scrollToActiveTab();
});

watch(() => props.activeSectionId, () => {
    scrollToActiveTab();
});
</script>

<template>
    <div 
        ref="tabsContainer"
        class="flex overflow-x-auto border-b border-gray-100 bg-white px-4 sm:px-8 custom-tabs-scroll scroll-smooth"
    >
        <button
            v-for="sec in sortedSections"
            :key="sec.id"
            :ref="activeSectionId === sec.id ? (el) => (activeTabRef = [el]) : undefined"
            type="button"
            @click="handleSelect(sec.id)"
            class="whitespace-nowrap py-4 px-6 border-b-2 font-bold text-sm transition-all flex items-center gap-2 cursor-pointer select-none shrink-0"
            :class="[
                activeSectionId === sec.id
                    ? 'border-[#005B3C] text-[#005B3C]'
                    : 'border-transparent text-gray-400 hover:text-gray-700'
            ]"
        >
            <span>Section {{ sec.order }}: {{ sec.title }}</span>
            <span 
                class="px-2 py-0.5 rounded-full text-xs font-semibold transition-colors"
                :class="[
                    activeSectionId === sec.id
                        ? 'bg-emerald-50 text-[#005B3C]'
                        : 'bg-gray-100 text-gray-500'
                ]"
            >
                {{ getQuestionCountBySection(sec.id) }}
            </span>
        </button>
    </div>
</template>

<style scoped>
.custom-tabs-scroll {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}
.custom-tabs-scroll::-webkit-scrollbar {
    height: 4px;
}
.custom-tabs-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.custom-tabs-scroll::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 9999px;
}
.custom-tabs-scroll::-webkit-scrollbar-thumb:hover {
    background-color: #94a3b8;
}
</style>
