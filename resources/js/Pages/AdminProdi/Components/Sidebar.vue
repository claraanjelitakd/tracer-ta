<!--
  Komponen Navigasi Sidebar Terpadu Admin Program Studi (Sidebar.vue)
  File: resources/js/Pages/AdminProdi/Components/Sidebar.vue
  
  Desain Mengikuti Estetika Resmi UKDW:
  - Hijau: #0D542B / #005B3C
  - Kuning: #FDC700 (Kuning Resmi UKDW)
  - Putih & Netral: #FFFFFF / #F8FAFC
  - Tata letak: Fixed Desktop Sidebar + Responsive Mobile Slide-over Drawer
-->
<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
    prodi: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const isMobileOpen = ref(false);

const currentUser = computed(() => {
    return props.user?.name ? props.user : (page.props.auth?.user || { name: 'Admin Program Studi', email: 'prodi@ukdw.ac.id' });
});

const currentProdi = computed(() => {
    return props.prodi?.nama_prodi ? props.prodi : (page.props.auth?.user?.prodi || {});
});

const userInitials = computed(() => {
    const name = currentUser.value?.name || 'PR';
    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
});

const isDashboardActive = computed(() => {
    return page.url === '/prodi' || page.url === '/prodi/' || page.url.startsWith('/prodi/dashboard');
});

const isAlumniActive = computed(() => {
    return page.url.startsWith('/prodi/alumni');
});

const isSectionsActive = computed(() => {
    return page.url.startsWith('/prodi/sections') || page.url.startsWith('/prodi/section');
});

const isPertanyaanActive = computed(() => {
    return page.url.startsWith('/prodi/pertanyaan');
});

const toggleMobileMenu = () => {
    isMobileOpen.value = !isMobileOpen.value;
};

const closeMobileMenu = () => {
    isMobileOpen.value = false;
};

const handleLogout = () => {
    Swal.fire({
        title: 'Konfirmasi Keluar',
        text: 'Apakah Anda yakin ingin keluar dari sesi Admin Program Studi?',
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
    <div>
        <!-- TOPBAR KHUSUS MOBILE / TABLET -->
        <header class="lg:hidden bg-white/95 backdrop-blur-md border-b border-gray-200/80 sticky top-0 z-40 px-4 py-3 flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="toggleMobileMenu"
                    class="p-2 rounded-xl text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#0D542B] transition-colors cursor-pointer"
                    aria-label="Buka Menu"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <Link href="/prodi/dashboard" class="flex items-center gap-2">
                    <img src="/uploads/landing/2.png" alt="Logo UKDW" class="h-8 w-8 object-contain" onerror="this.style.display='none'" />
                    <div>
                        <span class="font-extrabold text-sm text-gray-900 tracking-tight block leading-tight">Tracer Study</span>
                        <span class="text-[10px] font-bold text-[#0D542B] uppercase tracking-wider">{{ currentProdi.nama_prodi || 'Admin Prodi' }}</span>
                    </div>
                </Link>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-gray-700 bg-gray-100 px-2.5 py-1 rounded-full">
                    {{ userInitials }}
                </span>
            </div>
        </header>

        <!-- BACKDROP OVERLAY UNTUK MOBILE DRAWER -->
        <div
            v-if="isMobileOpen"
            class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 lg:hidden transition-opacity duration-300"
            @click="closeMobileMenu"
        ></div>

        <!-- SIDEBAR UTAMA -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-gray-200/80 flex flex-col justify-between transition-transform duration-300 ease-in-out shadow-lg lg:shadow-none"
            :class="[
                isMobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
            ]"
        >
            <!-- BAGIAN ATAS: LOGO & BRANDING -->
            <div>
                <!-- Brand Header -->
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <Link href="/prodi/dashboard" class="flex items-center gap-3.5 group">
                        <div class="w-11 h-11 rounded-2xl bg-white border border-gray-100 p-1.5 shadow-2xs flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                            <img src="/uploads/landing/2.png" alt="Logo UKDW" class="w-full h-full object-contain" onerror="this.style.display='none'" />
                        </div>
                        <div class="min-w-0">
                            <span class="font-black text-gray-900 text-base tracking-tight block leading-tight group-hover:text-[#0D542B] transition-colors truncate">
                                Tracer Study UKDW
                            </span>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="inline-block w-2 h-2 rounded-full bg-[#0D542B] shrink-0"></span>
                                <span class="text-[11px] font-extrabold text-[#0D542B] uppercase tracking-wider truncate">
                                    {{ currentProdi.nama_prodi || 'Program Studi' }}
                                </span>
                            </div>
                        </div>
                    </Link>

                    <!-- Close Button Mobile Drawer -->
                    <button
                        type="button"
                        @click="closeMobileMenu"
                        class="lg:hidden p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                        aria-label="Tutup Menu"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- NAVIGASI MENU UTAMA PRODI -->
                <div class="px-4 py-6 space-y-1">
                    <div class="px-3 pb-2">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-gray-400">
                            Menu Program Studi
                        </span>
                    </div>

                    <!-- 1. Dashboard -->
                    <Link
                        href="/prodi/dashboard"
                        @click="closeMobileMenu"
                        class="flex items-center gap-3.5 px-3.5 py-3 rounded-2xl text-xs font-bold transition-all group"
                        :class="[
                            isDashboardActive
                                ? 'bg-[#0D542B] text-white shadow-xs font-black'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                        ]"
                    >
                        <div
                            class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors shrink-0"
                            :class="[
                                isDashboardActive
                                    ? 'bg-white/20 text-white'
                                    : 'bg-gray-100 text-gray-500 group-hover:bg-[#0D542B]/10 group-hover:text-[#0D542B]'
                            ]"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <span>Dashboard Utama</span>
                        </div>
                    </Link>

                    <!-- 2. Data Alumni Khusus Prodi -->
                    <Link
                        href="/prodi/alumni"
                        @click="closeMobileMenu"
                        class="flex items-center gap-3.5 px-3.5 py-3 rounded-2xl text-xs font-bold transition-all group"
                        :class="[
                            isAlumniActive
                                ? 'bg-[#0D542B] text-white shadow-xs font-black'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                        ]"
                    >
                        <div
                            class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors shrink-0"
                            :class="[
                                isAlumniActive
                                    ? 'bg-white/20 text-white'
                                    : 'bg-gray-100 text-gray-500 group-hover:bg-[#0D542B]/10 group-hover:text-[#0D542B]'
                            ]"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <span>Data Alumni Prodi</span>
                        </div>
                    </Link>

                    <div class="px-3 pt-4 pb-2">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-gray-400">
                            Kuesioner Internal Prodi
                        </span>
                    </div>

                    <!-- 3. Kelola Section Prodi -->
                    <Link
                        href="/prodi/sections"
                        @click="closeMobileMenu"
                        class="flex items-center gap-3.5 px-3.5 py-3 rounded-2xl text-xs font-bold transition-all group"
                        :class="[
                            isSectionsActive
                                ? 'bg-[#0D542B] text-white shadow-xs font-black'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                        ]"
                    >
                        <div
                            class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors shrink-0"
                            :class="[
                                isSectionsActive
                                    ? 'bg-white/20 text-white'
                                    : 'bg-gray-100 text-gray-500 group-hover:bg-[#0D542B]/10 group-hover:text-[#0D542B]'
                            ]"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <span>Kelola Bagian / Seksi</span>
                        </div>
                    </Link>

                    <!-- 4. Kelola Pertanyaan Prodi -->
                    <Link
                        href="/prodi/pertanyaan"
                        @click="closeMobileMenu"
                        class="flex items-center gap-3.5 px-3.5 py-3 rounded-2xl text-xs font-bold transition-all group"
                        :class="[
                            isPertanyaanActive
                                ? 'bg-[#0D542B] text-white shadow-xs font-black'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                        ]"
                    >
                        <div
                            class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors shrink-0"
                            :class="[
                                isPertanyaanActive
                                    ? 'bg-white/20 text-white'
                                    : 'bg-gray-100 text-gray-500 group-hover:bg-[#0D542B]/10 group-hover:text-[#0D542B]'
                            ]"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <span>Kelola Pertanyaan</span>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- BAGIAN BAWAH: INFO PENGGUNA & TOMBOL LOGOUT -->
            <div class="p-4 border-t border-gray-100">
                <div class="p-3 bg-gray-50 rounded-2xl flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-xl bg-[#0D542B] text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs">
                            {{ userInitials }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="block text-xs font-bold text-gray-900 truncate">
                                {{ currentUser.name }}
                            </span>
                            <span class="block text-[10px] text-gray-500 truncate">
                                {{ currentProdi.nama_prodi || currentUser.email }}
                            </span>
                        </div>
                    </div>

                    <!-- Tombol Logout -->
                    <button
                        type="button"
                        @click="handleLogout"
                        class="p-2 text-gray-400 hover:text-rose-600 hover:bg-white rounded-xl transition-all cursor-pointer shrink-0"
                        title="Keluar dari Sistem"
                        aria-label="Logout"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>
    </div>
</template>
