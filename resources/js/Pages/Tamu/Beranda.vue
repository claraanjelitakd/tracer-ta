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

    <div class="min-h-screen bg-[#004D25] font-sans text-white antialiased selection:bg-[#FFC700] selection:text-slate-950">
        <!-- 1. Header & Navigation -->
        <Navbar />
        
        <main>
            <!-- 1. Hero Section dengan YouTube Autoplay Video Kampus & 3 Card Akses One UI Glossy -->
            <HeroSection :stats="stats" @search-alumni="onHeroSearch" />
            
            <!-- 2. Tentang Tracer Study & Sambutan WR III UKDW -->
            <TentangSection />

            <!-- 3. Alur Kuesioner (One UI Glossy Cards) -->
            <UserGuideSection />

            <!-- 4. Metrik Kunci & Transformasi Kurikulum -->
            <StatsTransformationSection :stats="stats" />

            <!-- 5. Peta Sebaran & Direktori Alumni Interaktif (Sebelum Blog) -->
            <AlumniMapSection 
                ref="mapSectionRef"
                :alumni-wilayah="alumniWilayah" 
                :alumni-list="alumniList" 
                :all-provinsi="allProvinsi"
                :all-kabupaten="allKabupaten"
            />

            <!-- 6. Warta Kampus & Berita Terkini -->
            <BlogSection />

            <!-- 7. Unduh Berkas & Informasi Akun Login -->
            <BerkasSection />
        </main>

        <!-- 8. Footer Resmi Biro 3 UKDW -->
        <Footer />
    </div>
</template>

