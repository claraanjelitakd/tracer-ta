<!--
  Komponen Navigasi Utama Terpadu Alumni (Navbar.vue)
  File: resources/js/Pages/Alumni/Components/Navbar.vue
  
  Fungsi:
  Menyediakan satu standar navigasi utama yang konsisten di seluruh halaman alumni
  (Dashboard, Profil, Kuesioner Tracer Study Umum, dan Kuesioner Khusus Program Studi).
  
  Warna & Estetika Resmi UKDW:
  - Hijau Resmi: #0D542B / #005B3C
  - Kuning Aksen: #FDC700
  - Bersih, responsif, elegan, tanpa reload layar putih (Inertia Link).
-->
<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();

const currentUser = computed(() => {
    return props.user?.name ? props.user : (page.props.auth?.user || { name: 'Alumni UKDW' });
});

const isDashboardActive = computed(() => {
    return page.url === '/alumni' || page.url.startsWith('/alumni/dashboard');
});

const isProfileActive = computed(() => {
    return page.url.startsWith('/alumni/profile');
});

const isKuesionerActive = computed(() => {
    return page.url === '/alumni/kuesioner' || (page.url.startsWith('/alumni/kuesioner') && !page.url.startsWith('/alumni/kuesioner-prodi'));
});

const isKuesionerProdiActive = computed(() => {
    return page.url.startsWith('/alumni/kuesioner-prodi');
});

const handleLogout = () => {
    Swal.fire({
        title: 'Konfirmasi Keluar',
        text: 'Apakah Anda yakin ingin mengakhiri sesi Tracer Study?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0D542B',
        cancelButtonColor: '#9CA3AF',
        confirmButtonText: 'Ya, Keluar',
        cancelButtonText: 'Batal',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            router.post('/logout');
        }
    });
};
</script>

<template>
    <nav class="bg-white shadow-xs border-b border-gray-100 sticky top-0 z-50 transition-all">
        <div class="w-full max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                
                <!-- Kiri: Brand & Logo Resmi UKDW -->
                <div class="flex items-center space-x-6">
                    <Link href="/alumni/dashboard" class="flex items-center space-x-3 group">
                        <div class="bg-white p-1 rounded-xl shadow-2xs border border-gray-100 flex items-center justify-center">
                            <img 
                                src="/uploads/landing/2.png" 
                                onerror="this.src='/uploads/logo/logo-ukdw.png'" 
                                alt="Logo UKDW" 
                                class="h-8 w-8 object-contain" 
                            />
                        </div>
                        <div>
                            <span class="text-gray-900 font-extrabold text-base sm:text-lg tracking-tight group-hover:text-[#005B3C] transition-colors">
                                Tracer Study UKDW
                            </span>
                            <span class="hidden sm:inline-block ml-2 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-[#005B3C] border border-emerald-100">
                                Alumni
                            </span>
                        </div>
                    </Link>

                    <!-- Navigasi Menu Desktop -->
                    <div class="hidden lg:flex items-center space-x-1 pl-4 border-l border-gray-200">
                        <Link 
                            href="/alumni/dashboard" 
                            class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-1.5"
                            :class="[
                                isDashboardActive
                                    ? 'text-[#005B3C] bg-emerald-50/70 font-bold'
                                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                            ]"
                        >
                            <span>Dashboard</span>
                        </Link>

                        <Link 
                            href="/alumni/profile" 
                            class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-1.5"
                            :class="[
                                isProfileActive
                                    ? 'text-[#005B3C] bg-emerald-50/70 font-bold'
                                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                            ]"
                        >
                            <span>Profil Alumni</span>
                        </Link>

                        <Link 
                            href="/alumni/kuesioner" 
                            class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-1.5"
                            :class="[
                                isKuesionerActive
                                    ? 'text-[#005B3C] bg-emerald-50/70 font-bold'
                                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                            ]"
                        >
                            <span>Kuesioner Universitas</span>
                        </Link>

                        <Link 
                            href="/alumni/kuesioner-prodi" 
                            class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-1.5"
                            :class="[
                                isKuesionerProdiActive
                                    ? 'text-[#005B3C] bg-emerald-50/70 font-bold'
                                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                            ]"
                        >
                            <span>Kuesioner Prodi</span>
                        </Link>
                    </div>
                </div>

                <!-- Kanan: Profil User & Tombol Keluar -->
                <div class="flex items-center space-x-4">
                    <div class="hidden sm:block text-right">
                        <span class="text-gray-900 font-bold text-sm block leading-tight truncate max-w-[200px]">
                            {{ currentUser.name }}
                        </span>
                        <span class="text-[11px] text-gray-400 font-semibold font-mono block">
                            {{ currentUser.username || currentUser.nim || 'Alumni' }}
                        </span>
                    </div>

                    <button 
                        @click="handleLogout" 
                        class="px-4 py-2 text-xs font-semibold text-gray-700 hover:text-red-600 hover:bg-red-50 border border-gray-200 rounded-full transition-all cursor-pointer shadow-2xs flex items-center gap-1.5"
                        title="Keluar dari akun"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        <span>Keluar</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Subnav Mobile / Tablet -->
        <div class="lg:hidden bg-gray-50/95 px-3 py-2 border-t border-gray-100 flex items-center justify-around text-xs gap-1 overflow-x-auto">
            <Link 
                href="/alumni/dashboard" 
                class="px-2.5 py-1.5 font-bold rounded-lg transition-colors whitespace-nowrap"
                :class="isDashboardActive ? 'text-[#005B3C] bg-white shadow-2xs' : 'text-gray-600 hover:text-gray-900'"
            >
                Dashboard
            </Link>
            <Link 
                href="/alumni/profile" 
                class="px-2.5 py-1.5 font-bold rounded-lg transition-colors whitespace-nowrap"
                :class="isProfileActive ? 'text-[#005B3C] bg-white shadow-2xs' : 'text-gray-600 hover:text-gray-900'"
            >
                Profil
            </Link>
            <Link 
                href="/alumni/kuesioner" 
                class="px-2.5 py-1.5 font-bold rounded-lg transition-colors whitespace-nowrap"
                :class="isKuesionerActive ? 'text-[#005B3C] bg-white shadow-2xs' : 'text-gray-600 hover:text-gray-900'"
            >
                Kuesioner
            </Link>
            <Link 
                href="/alumni/kuesioner-prodi" 
                class="px-2.5 py-1.5 font-bold rounded-lg transition-colors whitespace-nowrap"
                :class="isKuesionerProdiActive ? 'text-[#005B3C] bg-white shadow-2xs' : 'text-gray-600 hover:text-gray-900'"
            >
                Kuesioner Prodi
            </Link>
        </div>
    </nav>
</template>
