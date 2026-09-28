<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    featuredAlumni: {
        type: Array,
        default: () => []
    }
});

const defaultItems = [
    {
        id: 1,
        nama: 'Vanessa Stephanie Hartono',
        nim: '72190010',
        tahun_lulus: '2024',
        prodi: 'Sistem Informasi',
        posisi_jabatan: 'UI/UX Designer',
        perusahaan: 'PT Niagahoster',
        sektor: 'Teknologi Informasi & Web Hosting',
        judul_ta: 'Analisis dan Perancangan Antarmuka Pengguna Sistem ERP Berbasis User-Centered Design',
        jenis_publikasi: 'Jurnal Nasional Terakreditasi',
        url_publikasi: 'https://repository.ukdw.ac.id/handle/123456789/72190010'
    },
    {
        id: 2,
        nama: 'Samuel Evan Prasetya',
        nim: '71220005',
        tahun_lulus: '2026',
        prodi: 'Informatika',
        posisi_jabatan: 'Game Software Engineer',
        perusahaan: 'PT Gameloft Indonesia',
        sektor: 'Game Development & Software',
        judul_ta: 'Penerapan Pathfinding Algoritma A* pada Game Engine Multi-Platform',
        jenis_publikasi: 'Prosiding Konferensi Nasional',
        url_publikasi: 'https://repository.ukdw.ac.id/handle/123456789/71220005'
    },
    {
        id: 3,
        nama: 'Angelina Fiona Putri',
        nim: '71200004',
        tahun_lulus: '2024',
        prodi: 'Informatika',
        posisi_jabatan: 'Product Data Analyst',
        perusahaan: 'PT GoTo Gojek Tokopedia Tbk',
        sektor: 'E-Commerce & Digital Platform',
        judul_ta: 'Prediksi Churn Pelanggan E-Commerce Menggunakan Algoritma XGBoost dan Random Forest',
        jenis_publikasi: 'Jurnal Internasional Terindeks',
        url_publikasi: 'https://repository.ukdw.ac.id/handle/123456789/71200004'
    },
    {
        id: 4,
        nama: 'Benedictus Daniel Setiawan',
        nim: '72220001',
        tahun_lulus: '2026',
        prodi: 'Sistem Informasi',
        posisi_jabatan: 'Enterprise Solutions Consultant',
        perusahaan: 'PT Astra Graphia Information Technology',
        sektor: 'Konsultasi IT & Solusi Enterprise',
        judul_ta: 'Analisis dan Perancangan Sistem Informasi Enterprise Resource Planning Terintegrasi',
        jenis_publikasi: 'Jurnal Nasional Terakreditasi',
        url_publikasi: 'https://repository.ukdw.ac.id/handle/123456789/72220001'
    }
];

const items = computed(() => {
    return props.featuredAlumni && props.featuredAlumni.length > 0 
        ? props.featuredAlumni 
        : defaultItems;
});

const page = ref(1);
const perPage = 3;

const totalPages = computed(() => Math.ceil(items.value.length / perPage) || 1);

const visibleItems = computed(() => {
    const start = (page.value - 1) * perPage;
    return items.value.slice(start, start + perPage);
});

const nextPage = () => {
    if (page.value < totalPages.value) page.value++;
    else page.value = 1;
};

const prevPage = () => {
    if (page.value > 1) page.value--;
    else page.value = totalPages.value;
};
</script>

<template>
    <section id="karya-alumni" class="py-16 sm:py-20 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Section Header -->
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
                <div>
                    <span class="text-xs font-bold text-[#0D542B] tracking-wider uppercase">
                        Inovasi & Riset Mahasiswa
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1">
                        KARYA & TUGAS AKHIR ALUMNI UKDW
                    </h2>
                    <p class="text-slate-600 text-xs sm:text-sm mt-1 max-w-xl">
                        Eksplorasi topik skripsi, judul tugas akhir, dan publikasi ilmiah karya lulusan yang kini berkiprah di dunia profesional.
                    </p>
                </div>

                <!-- Navigation Controls -->
                <div v-if="totalPages > 1" class="flex items-center gap-2 self-start sm:self-auto">
                    <button
                        type="button"
                        @click="prevPage"
                        class="w-9 h-9 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 flex items-center justify-center font-bold text-sm transition-colors shadow-2xs cursor-pointer"
                        title="Sebelumnya"
                    >
                        ‹
                    </button>
                    <span class="text-xs font-bold text-slate-600 px-2">
                        {{ page }} / {{ totalPages }}
                    </span>
                    <button
                        type="button"
                        @click="nextPage"
                        class="w-9 h-9 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 flex items-center justify-center font-bold text-sm transition-colors shadow-2xs cursor-pointer"
                        title="Berikutnya"
                    >
                        ›
                    </button>
                </div>
            </div>

            <!-- Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div
                    v-for="item in visibleItems"
                    :key="item.id"
                    class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs hover:shadow-md hover:border-emerald-300 transition-all flex flex-col justify-between group"
                >
                    <div>
                        <!-- Header: Prodi & Tahun Lulus -->
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="px-2.5 py-0.5 rounded-md bg-emerald-50 text-[#0D542B] font-bold text-[11px] border border-emerald-100">
                                {{ item.prodi }}
                            </span>
                            <span class="text-[11px] font-bold text-slate-400">
                                Lulus {{ item.tahun_lulus }}
                            </span>
                        </div>

                        <!-- Judul Tugas Akhir (Highlight Utama) -->
                        <h3 class="text-sm font-extrabold text-slate-900 group-hover:text-[#0D542B] transition-colors leading-snug line-clamp-3 mb-3">
                            "{{ item.judul_ta || 'Pengembangan Sistem Berkelanjutan' }}"
                        </h3>

                        <!-- Penulis & Karier Aktif -->
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs mb-3 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-extrabold text-slate-900">{{ item.nama }}</span>
                            </div>
                            <div class="text-[11px] text-emerald-800 font-bold">
                                {{ item.posisi_jabatan }}
                            </div>
                            <div class="text-[11px] text-slate-600 truncate">
                                @ {{ item.perusahaan }}
                            </div>
                        </div>

                        <!-- Status / Jenis Publikasi -->
                        <div class="text-[11px] text-slate-500 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-yellow-400"></span>
                            <span>{{ item.jenis_publikasi || 'Repository UKDW' }}</span>
                        </div>
                    </div>

                    <!-- Footer Link Publikasi -->
                    <div class="mt-4 pt-3 border-t border-slate-100">
                        <a
                            :href="item.url_publikasi || 'https://repository.ukdw.ac.id'"
                            target="_blank"
                            class="w-full py-2 px-3 rounded-xl bg-[#0D542B] hover:bg-[#083a1d] text-white font-bold text-xs transition-colors flex items-center justify-between shadow-2xs group/btn"
                        >
                            <span>Buka Dokumen TA</span>
                            <svg class="w-3.5 h-3.5 transition-transform group-hover/btn:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </section>
</template>
