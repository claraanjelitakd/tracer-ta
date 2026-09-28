<script setup>
import { ref } from 'vue';

const blogs = [
    {
        id: 1,
        judul: 'Sosialisasi Tracer Study Nasional Kemendikbudristek 2026',
        kategori: 'Regulasi & Mutu',
        tanggal: '24 Sep 2026',
        penulis: 'Biro 3 UKDW',
        ringkasan: 'Langkah strategis pelaporan data lulusan untuk pemenuhan Indikator Kinerja Utama (IKU-1) perguruan tinggi se-Indonesia.',
        tag: 'IKU-1'
    },
    {
        id: 2,
        judul: 'Pembaruan Kurikulum Berbasis Kebutuhan Industri Era AI',
        kategori: 'Akademik',
        tanggal: '18 Sep 2026',
        penulis: 'LPPM UKDW',
        ringkasan: 'Hasil survei pengguna lulusan menunjukkan tingginya permintaan terhadap keterampilan problem-solving dan rekayasa cerdas.',
        tag: 'Kurikulum'
    },
    {
        id: 3,
        judul: 'Temu Kangen & Bursa Kerja Alumni Lintas Angkatan',
        kategori: 'Kegiatan Alumni',
        tanggal: '10 Sep 2026',
        penulis: 'Ikatan Alumni',
        ringkasan: 'Mempererat persaudaraan alumni Duta Wacana sekaligus membuka peluang karier baru dan mentoring untuk lulusan muda.',
        tag: 'Networking'
    },
    {
        id: 4,
        judul: 'Apresiasi Bagi Responden Kuesioner Tracer Study Cepat',
        kategori: 'Informasi',
        tanggal: '02 Sep 2026',
        penulis: 'Humas Kampus',
        ringkasan: 'Program reward dan merchandise eksklusif bagi alumni yang telah menyelesaikan pengisian kuesioner pada periode pembukaan.',
        tag: 'Merchandise'
    }
];

const blogContainer = ref(null);

const scrollLeft = () => {
    if (blogContainer.value) {
        blogContainer.value.scrollBy({ left: -300, behavior: 'smooth' });
    }
};

const scrollRight = () => {
    if (blogContainer.value) {
        blogContainer.value.scrollBy({ left: 300, behavior: 'smooth' });
    }
};
</script>

<template>
    <section id="blog" class="py-20 lg:py-24 bg-[#004D25] text-white border-b border-white/10 relative overflow-hidden">
        <!-- Glossy Glow Accents -->
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-[#03542B]/80 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-[#FFC700]/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 w-full">
            
            <!-- Section Header -->
            <div class="flex items-end justify-between mb-8">
                <div>
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-[#FFC700] text-slate-950 font-black text-xs uppercase tracking-wider mb-2 shadow-md">
                        Warta Kampus
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight mt-1">
                        Blog &amp; Agenda Terkini
                    </h2>
                    <p class="text-white/80 text-xs sm:text-sm mt-1">
                        Informasi seputar perkembangan almamater, panduan karier, dan agenda temu alumni.
                    </p>
                </div>

                <!-- Right Navigation Arrows Glossy Style -->
                <div class="hidden sm:flex items-center gap-2">
                    <button
                        @click="scrollLeft"
                        class="w-10 h-10 rounded-full bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-white hover:bg-[#FFC700] hover:text-slate-950 transition-all shadow-md cursor-pointer active:scale-95"
                        title="Sebelumnya"
                    >
                        <svg class="w-4 h-4 font-bold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button
                        @click="scrollRight"
                        class="w-10 h-10 rounded-full bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-white hover:bg-[#FFC700] hover:text-slate-950 transition-all shadow-md cursor-pointer active:scale-95"
                        title="Berikutnya"
                    >
                        <svg class="w-4 h-4 font-bold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>

            <!-- 4 Blog Cards Matching Glossy Squircle -->
            <div 
                ref="blogContainer"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 overflow-x-auto pb-2"
            >
                <div 
                    v-for="post in blogs" 
                    :key="post.id"
                    class="bg-white/10 backdrop-blur-2xl rounded-3xl border border-white/20 shadow-2xl hover:shadow-2xl hover:border-[#FFC700]/70 hover:bg-white/15 transition-all flex flex-col justify-between overflow-hidden group cursor-pointer text-white"
                >
                    <!-- Visual Header Placeholder -->
                    <div class="p-3.5 pb-0">
                        <div class="h-32 w-full rounded-2xl bg-black/40 border border-white/15 flex items-center justify-center relative overflow-hidden group-hover:bg-black/50 transition-colors">
                            <div class="w-11 h-11 rounded-2xl bg-[#FFC700] text-slate-950 flex items-center justify-center font-black shadow-md">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                            </div>
                            
                            <span class="absolute top-2.5 right-2.5 bg-black/60 backdrop-blur-md text-[10px] font-bold text-white px-2.5 py-0.5 rounded-full border border-white/20 shadow-xs">
                                {{ post.tanggal }}
                            </span>
                        </div>
                    </div>

                    <!-- Post Body -->
                    <div class="p-4 pt-3 flex-1 flex flex-col">
                        <span class="text-[10px] font-black text-[#FFC700] uppercase tracking-wider block mb-1">
                            {{ post.kategori }}
                        </span>
                        
                        <h3 class="text-sm font-bold text-white group-hover:text-[#FFC700] transition-colors line-clamp-2 leading-snug mb-2">
                            {{ post.judul }}
                        </h3>

                        <p class="text-xs text-white/75 line-clamp-3 leading-relaxed flex-1">
                            {{ post.ringkasan }}
                        </p>
                    </div>

                    <!-- Post Footer -->
                    <div class="p-4 pt-3 border-t border-white/15 flex items-center justify-between bg-black/20">
                        <span class="text-[11px] font-medium text-white/60">
                            {{ post.penulis }}
                        </span>

                        <span class="inline-flex items-center gap-1 text-xs font-black text-[#FFC700] group-hover:translate-x-0.5 transition-transform">
                            <span>Baca</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </section>
</template>
