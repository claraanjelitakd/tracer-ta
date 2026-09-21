<!--
  =================================================================================================
  KOMPONEN: Stepper Kuesioner Tracer Study Alumni
  FILE: resources/js/Pages/Alumni/Components/Kuesioner/Stepper.vue
  =================================================================================================
  Fungsi Utama:
  Menampilkan indikator tahapan (progress steps) kuesioner secara horizontal:
  - Bulatan angka 1..N untuk setiap seksi kuesioner.
  - State aktif: berwarna emas (#FFD700) dengan border hijau.
  - State selesai: berwarna hijau (#005B3C) dengan ikon centang putih (✓).
  - State belum diisi: berwarna abu-abu.
  - Auto-scroll otomatis menjaga bulatan seksi aktif tetap berada di tengah layar saat berganti halaman.
-->
<script setup>
import { ref, watch, onMounted } from 'vue';

/**
 * Properti Komponen (Props)
 * - sections: Daftar array seksi/kelompok pertanyaan kuesioner.
 * - activeIndex: Indeks seksi yang saat ini aktif dibuka alumni (0-indexed).
 * - completedIndices: Set yang berisi kumpulan indeks seksi yang berstatus selesai.
 * - isSectionCompleted: Fungsi validator untuk mengecek apakah seksi di index tertentu sudah selesai.
 * - isLineCompleted: Fungsi penentu warna garis penghubung antar tahapan.
 */
const props = defineProps({
    sections: {
        type: Array,
        default: () => [],
    },
    activeIndex: {
        type: Number,
        default: 0,
    },
    activeOriginalIndex: {
        type: Number,
        default: null,
    },
    completedIndices: {
        type: Object, // Set
        default: () => new Set(),
    },
    isSectionCompleted: {
        type: Function,
        required: true,
    },
    isLineCompleted: {
        type: Function,
        required: true,
    },
});

/**
 * Event Emiter
 * - select-section: Memicu perpindahan langsung ke seksi yang diklik oleh alumni.
 */
const emit = defineEmits(['select-section']);

// Referensi DOM container stepper untuk pengaturan auto-scroll horizontal
const stepperContainerRef = ref(null);

// Koleksi referensi elemen DOM untuk tiap bulatan tahapan
const stepRefs = ref({});

/**
 * Mendaftarkan referensi elemen DOM per indeks langkah
 */
const setStepRef = (el, index) => {
    if (el) stepRefs.value[index] = el;
};

/**
 * Menggulirkan (auto-scroll) tampilan stepper agar langkah aktif terlihat di tengah
 * 
 * @param {Number} index Indeks langkah aktif
 */
const scrollActiveStepIntoView = (index) => {
    if (typeof window === 'undefined') return;
    setTimeout(() => {
        const el = stepRefs.value[index];
        if (el && stepperContainerRef.value) {
            el.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    }, 50);
};

// Pantau perubahan activeIndex agar stepper otomatis bergeser
watch([() => props.activeIndex, () => props.activeOriginalIndex], () => {
    scrollActiveStepIntoView(props.activeIndex);
});

// Jalankan scroll saat komponen pertama kali dimuat
onMounted(() => {
    scrollActiveStepIntoView(props.activeIndex);
});
</script>

<template>
    <!-- Container Baris Stepper Horizontal -->
    <div 
        v-if="sections && sections.length > 0" 
        ref="stepperContainerRef"
        class="w-full bg-white/95 backdrop-blur-md border-b border-gray-200 overflow-x-auto py-2 sm:py-3 custom-scrollbar"
    >
        <div class="flex items-center justify-between px-3 sm:px-4 md:px-8 min-w-max max-w-6xl xl:max-w-7xl mx-auto">
            <template v-for="(section, index) in sections" :key="section.id">
                
                <!-- =================================================================================== -->
                <!-- 1. BULATAN & LABEL TAHAPAN SEKSI                                                    -->
                <!-- =================================================================================== -->
                <div 
                    :ref="el => setStepRef(el, index)"
                    class="flex flex-col relative items-center justify-center cursor-pointer group px-1 sm:px-2 md:px-3 py-0.5 sm:py-1 transition-all duration-200"
                    @click="emit('select-section', section.originalIndex !== undefined ? section.originalIndex : index)"
                    :title="'Bagian ' + (index + 1) + ': ' + section.title"
                >
                    <!-- Lingkaran Angka / Centang Selesai -->
                    <div 
                        class="flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 rounded-full font-black text-xs sm:text-sm md:text-lg transition-all duration-200 z-10 shadow-xs relative"
                        :class="[
                            ((activeOriginalIndex !== null && activeOriginalIndex !== undefined && section.originalIndex !== undefined) ? activeOriginalIndex === section.originalIndex : activeIndex === index)
                                ? 'bg-[#FFD700] text-[#005B3C] shadow-md scale-105 sm:scale-110 ring-2 sm:ring-4 ring-[#005B3C]/20' : 
                            (isSectionCompleted(section.originalIndex !== undefined ? section.originalIndex : index) 
                                ? 'bg-[#005B3C] text-white shadow-xs group-hover:bg-[#00482f] group-hover:scale-105' : 
                                'bg-gray-100 text-gray-400 group-hover:bg-gray-200 group-hover:text-gray-600')
                        ]"
                    >
                        <!-- 1.1 Tanda centang putih jika seksi sudah lengkap terisi dan tidak sedang dibuka -->
                        <svg 
                            v-if="isSectionCompleted(section.originalIndex !== undefined ? section.originalIndex : index) && !((activeOriginalIndex !== null && activeOriginalIndex !== undefined && section.originalIndex !== undefined) ? activeOriginalIndex === section.originalIndex : activeIndex === index)" 
                            class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6 text-white" 
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"></path>
                        </svg>
                        
                        <!-- 1.2 Angka urutan tahapan (1, 2, 3..) jika sedang aktif atau belum selesai -->
                        <span v-else>{{ index + 1 }}</span>

                        <!-- 1.3 Badge mini centang di sudut kanan atas jika tahapan aktif ini sudah lengkap -->
                        <span 
                            v-if="((activeOriginalIndex !== null && activeOriginalIndex !== undefined && section.originalIndex !== undefined) ? activeOriginalIndex === section.originalIndex : activeIndex === index) && isSectionCompleted(section.originalIndex !== undefined ? section.originalIndex : index)" 
                            class="absolute -top-1 -right-1 w-3.5 h-3.5 sm:w-4 sm:h-4 bg-[#005B3C] text-white rounded-full flex items-center justify-center text-[8px] sm:text-[10px] font-black shadow-xs ring-1.5 sm:ring-2 ring-white"
                            title="Bagian ini sudah lengkap"
                        >
                            ✓
                        </span>
                    </div>
                    
                    <!-- Judul Nama Seksi di Bawah Lingkaran -->
                    <span 
                        class="text-[9px] sm:text-[11px] md:text-xs mt-1 sm:mt-2 text-center w-20 sm:w-24 md:w-28 leading-tight transition-colors line-clamp-2"
                        :class="[
                            ((activeOriginalIndex !== null && activeOriginalIndex !== undefined && section.originalIndex !== undefined) ? activeOriginalIndex === section.originalIndex : activeIndex === index)
                                ? 'text-[#005B3C] font-black' : 
                            (isSectionCompleted(section.originalIndex !== undefined ? section.originalIndex : index) 
                                ? 'text-[#005B3C] font-bold group-hover:text-[#00482f]' : 
                                'text-gray-400 group-hover:text-gray-600 font-medium')
                        ]"
                    >
                        {{ section.title }}
                    </span>
                </div>
                
                <!-- =================================================================================== -->
                <!-- 2. GARIS PENGHUBUNG ANTAR LINGKARAN STEPPER                                         -->
                <!-- =================================================================================== -->
                <div 
                    v-if="index < sections.length - 1" 
                    class="flex-1 h-1 sm:h-1.5 md:h-2 rounded-full transition-colors duration-300 mx-1 md:mx-2 min-w-[14px] sm:min-w-[20px]" 
                    :class="isLineCompleted(index) ? 'bg-[#005B3C]' : 'bg-gray-200'"
                ></div>
            </template>
        </div>
    </div>
</template>

<style scoped>
/* Menyembunyikan scrollbar bawaan browser agar tampilan horizontal stepper tetap bersih */
.custom-scrollbar::-webkit-scrollbar {
    display: none;
}
.custom-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
