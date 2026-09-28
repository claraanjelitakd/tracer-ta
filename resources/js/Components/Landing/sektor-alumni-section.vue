<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    featuredAlumni: {
        type: Array,
        default: () => []
    },
    sektorKarier: {
        type: Array,
        default: () => []
    }
});

const activeTab = ref('Semua');

const filteredAlumni = computed(() => {
    if (activeTab.value === 'Semua') {
        return props.featuredAlumni;
    }
    if (activeTab.value === 'Teknologi') {
        return props.featuredAlumni.filter(a => 
            (a.sektor && a.sektor.toLowerCase().includes('teknologi')) ||
            (a.posisi_jabatan && (a.posisi_jabatan.toLowerCase().includes('data') || a.posisi_jabatan.toLowerCase().includes('engineer') || a.posisi_jabatan.toLowerCase().includes('associate')))
        );
    }
    if (activeTab.value === 'Pendidikan') {
        return props.featuredAlumni.filter(a => 
            (a.sektor && (a.sektor.toLowerCase().includes('pendidikan') || a.sektor.toLowerCase().includes('riset'))) ||
            (a.posisi_jabatan && a.posisi_jabatan.toLowerCase().includes('beasiswa'))
        );
    }
    return props.featuredAlumni;
});

const scrollContainer = ref(null);

const scrollLeft = () => {
    if (scrollContainer.value) {
        scrollContainer.value.scrollBy({ left: -320, behavior: 'smooth' });
    }
};

const scrollRight = () => {
    if (scrollContainer.value) {
        scrollContainer.value.scrollBy({ left: 320, behavior: 'smooth' });
    }
};
</script>

<template>
    <section class="py-16 bg-slate-50/60 border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Section Header & Controls -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
                <div>
                    <span class="text-xs font-bold text-[#0D542B] tracking-wider uppercase">Rekam Jejak Nyata</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                        Sebaran Karier & Kiprah Lulusan
                    </h2>
                    <p class="text-slate-500 text-sm mt-1 max-w-xl">
                        Data riil alumni UKDW yang telah berkarya di berbagai sektor industri terkemuka nasional dan internasional.
                    </p>
                </div>

                <!-- Tabs & Arrow Controls Matching Wireframe -->
                <div class="flex items-center gap-4">
                    <!-- Segmented Tabs -->
                    <div class="inline-flex p-1 bg-white border border-slate-200 rounded-lg shadow-2xs">
                        <button
                            type="button"
                            @click="activeTab = 'Semua'"
                            class="px-3.5 py-1.5 text-xs font-bold rounded-md transition-all"
                            :class="activeTab === 'Semua' ? 'bg-[#0D542B] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            Semua Sektor
                        </button>
                        <button
                            type="button"
                            @click="activeTab = 'Teknologi'"
                            class="px-3.5 py-1.5 text-xs font-bold rounded-md transition-all"
                            :class="activeTab === 'Teknologi' ? 'bg-[#0D542B] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            Teknologi & IT
                        </button>
                        <button
                            type="button"
                            @click="activeTab = 'Pendidikan'"
                            class="px-3.5 py-1.5 text-xs font-bold rounded-md transition-all"
                            :class="activeTab === 'Pendidikan' ? 'bg-[#0D542B] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            Pendidikan & Riset
                        </button>
                    </div>

                    <!-- Left / Right Arrow Buttons -->
                    <div class="hidden sm:flex items-center gap-1.5">
                        <button
                            @click="scrollLeft"
                            class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors shadow-2xs"
                            title="Sebelumnya"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button
                            @click="scrollRight"
                            class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors shadow-2xs"
                            title="Berikutnya"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Horizontal Cards Scroll / Grid Matching Wireframe -->
            <div 
                ref="scrollContainer"
                class="flex gap-6 overflow-x-auto pb-4 pt-1 snap-x scrollbar-thin scrollbar-thumb-slate-200 scrollbar-track-transparent"
            >
                <div
                    v-for="alumni in filteredAlumni"
                    :key="alumni.id"
                    class="w-[300px] sm:w-[320px] shrink-0 bg-white rounded-xl border border-slate-200 shadow-2xs hover:shadow-md transition-all duration-300 flex flex-col justify-between overflow-hidden group snap-start"
                >
                    <!-- Card Header / Visual Box -->
                    <div class="p-5 pb-3">
                        <div class="h-32 w-full rounded-lg bg-slate-100 flex flex-col items-center justify-center relative overflow-hidden group-hover:bg-slate-200/70 transition-colors">
                            <!-- Background Subtle Pattern -->
                            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#0D542B_1px,transparent_1px)] [background-size:12px_12px]"></div>
                            
                            <!-- Initials Avatar -->
                            <div class="w-14 h-14 rounded-full bg-white border-2 border-emerald-100 text-[#0D542B] font-extrabold text-lg flex items-center justify-center shadow-xs">
                                {{ alumni.nama ? alumni.nama.substring(0, 2).toUpperCase() : 'AL' }}
                            </div>

                            <span class="mt-2 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                                Lulusan {{ alumni.tahun_lulus }}
                            </span>
                        </div>

                        <!-- Card Body -->
                        <div class="mt-4">
                            <span class="inline-block text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100 mb-1.5">
                                {{ alumni.prodi }}
                            </span>
                            <h3 class="text-base font-bold text-slate-900 group-hover:text-[#0D542B] transition-colors line-clamp-1">
                                {{ alumni.nama }}
                            </h3>
                            <p class="text-xs font-semibold text-slate-700 mt-1 line-clamp-1">
                                {{ alumni.posisi_jabatan }}
                            </p>
                            <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">
                                {{ alumni.perusahaan }}
                            </p>
                        </div>
                    </div>

                    <!-- Card Footer Matching Wireframe Price/Detail Row -->
                    <div class="p-5 pt-3 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div>
                            <span class="text-[10px] text-slate-400 font-semibold uppercase block">Sektor</span>
                            <span class="text-xs font-bold text-slate-700 block truncate max-w-[170px]">
                                {{ alumni.sektor || 'Industri Profesional' }}
                            </span>
                        </div>

                        <span class="inline-flex items-center gap-1 text-xs font-bold text-[#0D542B] group-hover:translate-x-0.5 transition-transform">
                            <span>Terdata</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Empty State Fallback -->
            <div v-if="filteredAlumni.length === 0" class="text-center py-12 bg-white rounded-xl border border-slate-200">
                <p class="text-sm font-semibold text-slate-600">Tidak ada data alumni pada sektor ini saat ini.</p>
            </div>

        </div>
    </section>
</template>
