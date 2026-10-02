<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

import Navbar from '@/components/landing/navbar.vue';
import HeroSection from '@/components/landing/hero-section.vue';
import AlumniMapSection from '@/components/landing/alumni-map-section.vue';
import TentangSection from '@/components/landing/tentang-section.vue';
import UserGuideSection from '@/components/landing/user-guide-section.vue';
import BerkasSection from '@/components/landing/berkas-section.vue';
import StatsTransformationSection from '@/components/landing/stats-transformation-section.vue';
import BlogSection from '@/components/landing/blog-section.vue';
import Footer from '@/components/landing/footer.vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_alumni: 120,
            total_perusahaan: 32,
            total_prodi: 12,
            total_responden: 88,
            persentase_karier: 94
        })
    },
    featuredAlumni: {
        type: Array,
        default: () => []
    },
    alumniWilayah: {
        type: Object,
        default: () => ({
            domisili: [],
            karier: [],
            perusahaan: {}
        })
    },
    alumniList: {
        type: Array,
        default: () => []
    },
    allProvinsi: {
        type: Array,
        default: () => []
    },
    allKabupaten: {
        type: Array,
        default: () => []
    }
});

// ============================================================================
// PENGATURAN WARNA BACKGROUND SETIAP SECTION (HOMEPAGE / LANDING PAGE)
// ============================================================================
// Anda dapat dengan mudah mengatur / mengubah warna background masing-masing section di sini.
// Contoh pilihan kelas warna Tailwind yang dapat digunakan:
// - 'bg-[#FFC700]'  : Kuning Khas UKDW
// - 'bg-white'      : Putih Bersih
// - 'bg-[#004D25]'  : Hijau Tua Utama UKDW
// - 'bg-[#03542B]'  : Hijau UKDW Sekunder
// - 'bg-slate-950'  : Hitam Elegan
// - Gradasi contoh : 'bg-gradient-to-b from-[#00381A] via-[#004D25] to-[#003318]'
// ============================================================================
const sectionBackgrounds = ref({
    // 1. Hero Section (Banner Video Profil Kampus UKDW)
    hero: 'bg-slate-950',

    // 2. Tentang Tracer Study & Sambutan WR III UKDW (Permintaan: PUTIH)
    tentang: 'bg-white',

    // 3. Alur Partisipasi / Panduan Langkah Kuesioner (Kuning UKDW)
    panduan: 'bg-gradient-to-b from-[#FFC700] via-[#FBBF24] to-[#F59E0B]',

    // 4. Metrik Kunci & Transformasi Kurikulum (Hijau UKDW)
    statistik: 'bg-[#004D25]',

    // 5. PETA SEBARAN & DIREKTORI ALUMNI UKDW (Permintaan: KUNING)
    //    Cakupan Peta: Seluruh Wilayah Indonesia (Nasional)
    peta: 'bg-[#FFC700]',

    // 6. BLOG & AGENDA TERKINI / WARTA KAMPUS (Permintaan: PUTIH)
    blog: 'bg-white',

    // 7. UNDUH BERKAS & DOKUMEN TRACER STUDY (Permintaan: KUNING)
    berkas: 'bg-[#FFC700]',
});

const mapSectionRef = ref(null);

const onHeroSearch = (query) => {
    if (mapSectionRef.value) {
        mapSectionRef.value.searchQuery = query;
    }
};

gsap.registerPlugin(ScrollTrigger);

const initAnimations = () => {
    nextTick(() => {
        // Hero headline animation (Fade show)
        gsap.fromTo('.hero-title', 
            { y: 25, opacity: 0 }, 
            { y: 0, opacity: 1, duration: 0.9, ease: "power2.out" }
        );
        
        gsap.fromTo('.hero-subtitle', 
            { y: 20, opacity: 0 }, 
            { y: 0, opacity: 1, duration: 0.9, delay: 0.2, ease: "power2.out" }
        );

        // Fade show untuk section-section saat di-scroll
        const sections = document.querySelectorAll('section');
        sections.forEach((sec, idx) => {
            if (idx === 0) return; // Lewati hero
            gsap.fromTo(sec, 
                { opacity: 0, y: 20 },
                {
                    opacity: 1,
                    y: 0,
                    duration: 0.7,
                    ease: "power2.out",
                    scrollTrigger: {
                        trigger: sec,
                        start: "top 90%",
                        toggleActions: "play none none reverse"
                    }
                }
            );
        });
    });
};

onMounted(() => {
    setTimeout(() => {
        initAnimations();
    }, 300);
});
</script>

<template>
    <Head title="Portal Tracer Study - Universitas Kristen Duta Wacana" />

    <div class="min-h-screen bg-[#004D25] font-sans antialiased selection:bg-[#FFC700] selection:text-slate-950">
        <!-- Header & Navigasi -->
        <Navbar />
        
        <main>
            <!-- 
              ========================================================================
              1. HERO SECTION (Video Autoplay Kampus & Akses Cepat One UI)
              Pengaturan Background: sectionBackgrounds.hero
              ========================================================================
            -->
            <HeroSection 
                :bg-class="sectionBackgrounds.hero"
                :stats="stats" 
                @search-alumni="onHeroSearch" 
            />
            
            <!-- 
              ========================================================================
              2. TENTANG TRACER STUDY & SAMBUTAN WR III UKDW
              Pengaturan Background: sectionBackgrounds.tentang (Saat ini: PUTIH)
              ========================================================================
            -->
            <TentangSection 
                :bg-class="sectionBackgrounds.tentang"
            />

            <!-- 
              ========================================================================
              3. ALUR PARTISIPASI / PANDUAN LANGKAH BAGI ALUMNI BARU
              Pengaturan Background: sectionBackgrounds.panduan (Default: Kuning UKDW)
              ========================================================================
            -->
            <UserGuideSection 
                :bg-class="sectionBackgrounds.panduan"
            />

            <!-- 
              ========================================================================
              4. METRIK KUNCI & TRANSFORMASI KURIKULUM
              Pengaturan Background: sectionBackgrounds.statistik (Default: Hijau UKDW)
              ========================================================================
            -->
            <StatsTransformationSection 
                :bg-class="sectionBackgrounds.statistik"
                :stats="stats" 
            />

            <!-- 
              ========================================================================
              5. PETA SEBARAN & DIREKTORI ALUMNI UKDW
              - Cakupan Peta: Seluruh Wilayah Indonesia (Nasional)
              - Direktori Karier Dalam & Luar Negeri
              Pengaturan Background: sectionBackgrounds.peta (Saat ini: KUNING UKDW)
              ========================================================================
            -->
            <AlumniMapSection 
                ref="mapSectionRef"
                :bg-class="sectionBackgrounds.peta"
                :alumni-wilayah="alumniWilayah" 
                :alumni-list="alumniList" 
                :all-provinsi="allProvinsi"
                :all-kabupaten="allKabupaten"
            />

            <!-- 
              ========================================================================
              6. BLOG & AGENDA TERKINI (WARTA KAMPUS)
              - Informasi perkembangan almamater, panduan karier & temu alumni
              Pengaturan Background: sectionBackgrounds.blog (Saat ini: PUTIH)
              ========================================================================
            -->
            <BlogSection 
                :bg-class="sectionBackgrounds.blog"
            />

            <!-- 
              ========================================================================
              7. UNDUH BERKAS & DOKUMEN TRACER
              - Surat edaran resmi WR III & instrumen evaluasi kepuasan pengguna
              Pengaturan Background: sectionBackgrounds.berkas (Saat ini: KUNING UKDW)
              ========================================================================
            -->
            <BerkasSection 
                :bg-class="sectionBackgrounds.berkas"
            />
        </main>

        <!-- 8. Footer Resmi Biro 3 UKDW -->
        <Footer />
    </div>
</template>

