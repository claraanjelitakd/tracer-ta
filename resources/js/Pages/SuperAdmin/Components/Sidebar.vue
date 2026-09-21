<!--
  Komponen Navigasi Sidebar Terpadu Super Admin (Sidebar.vue)
  File: resources/js/Pages/SuperAdmin/Components/Sidebar.vue
  
  Desain Mengikuti Estetika Resmi UKDW:
  - Hijau: #0D542B / #005B3C (Solid, Bersih)
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
});

const page = usePage();
const isMobileOpen = ref(false);

const currentUser = computed(() => {
    return props.user?.name ? props.user : (page.props.auth?.user || { name: 'Super Administrator', email: 'admin@ukdw.ac.id' });
});

const userInitials = computed(() => {
    const name = currentUser.value?.name || 'SA';
    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
});

const isDashboardActive = computed(() => {
    return page.url === '/superadmin' || page.url === '/superadmin/' || page.url.startsWith('/superadmin/dashboard');
});

const isPertanyaanActive = computed(() => {
    return page.url.startsWith('/superadmin/pertanyaan');
});

const isSectionsActive = computed(() => {
    return page.url.startsWith('/superadmin/sections');
});

const isAlumniActive = computed(() => {
    return page.url.startsWith('/superadmin/alumni');
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
        text: 'Apakah Anda yakin ingin keluar dari sesi Super Admin?',
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
        <!-- TOPBAR KHUSUS MOBILE / TABLET (Tampil hanya di layar < lg) -->
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
                <Link href="/superadmin/dashboard" class="flex items-center gap-2">
                    <img src="/uploads/landing/2.png" alt="Logo UKDW" class="h-8 w-8 object-contain" onerror="this.style.display='none'" />
                    <div>
                        <span class="font-extrabold text-sm text-gray-900 tracking-tight block leading-tight">Tracer Study</span>
                        <span class="text-[10px] font-bold text-[#0D542B] uppercase tracking-wider">Super Admin</span>
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

        <!-- SIDEBAR UTAMA (Desktop: Fixed di Kiri | Mobile: Slide-over Drawer) -->
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
                    <Link href="/superadmin/dashboard" class="flex items-center gap-3.5 group">
                        <div class="w-11 h-11 rounded-2xl bg-white border border-gray-100 p-1.5 shadow-2xs flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                            <img src="/uploads/landing/2.png" alt="Logo UKDW" class="w-full h-full object-contain" onerror="this.style.display='none'" />
                        </div>
                        <div>
                            <span class="font-black text-gray-900 text-base tracking-tight block leading-tight group-hover:text-[#0D542B] transition-colors">
                                Tracer Study UKDW
                            </span>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="inline-block w-2 h-2 rounded-full bg-[#0D542B]"></span>
                                <span class="text-[11px] font-extrabold text-[#0D542B] uppercase tracking-wider">
                                    Super Admin
                                </span>
                            </div>
                        </div>
                    </Link>

                    <!-- Tombol Tutup Mobile -->
                    <button
                        type="button"
                        @click="closeMobileMenu"
                        class="lg:hidden p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- NAVIGASI MENU UTAMA -->
                <div class="px-4 py-6 space-y-6 overflow-y-auto max-h-[calc(100vh-220px)]">
                    <div>
                        <div class="px-3 mb-2 text-[10px] font-black uppercase tracking-widest text-gray-400">
                            Menu Utama
                        </div>
                        <nav class="space-y-1.5">
                            
                            <!-- 1. Dashboard -->
                            <Link
                                href="/superadmin/dashboard"
                                @click="closeMobileMenu"
                                class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl text-sm font-bold transition-all group"
                                :class="[
                                    isDashboardActive
                                        ? 'bg-[#0D542B] text-white shadow-xs'
                                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                                ]"
                            >
                                <svg
                                    class="w-5 h-5 shrink-0 transition-colors"
                                    :class="isDashboardActive ? 'text-[#FDC700]' : 'text-gray-400 group-hover:text-gray-700'"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                <span>Dashboard</span>
                            </Link>

                            <!-- 2. Kelola Kuesioner -->
                            <Link
                                href="/superadmin/pertanyaan"
                                @click="closeMobileMenu"
                                class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl text-sm font-bold transition-all group"
                                :class="[
                                    isPertanyaanActive
                                        ? 'bg-[#0D542B] text-white shadow-xs'
                                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                                ]"
                            >
                                <svg
                                    class="w-5 h-5 shrink-0 transition-colors"
                                    :class="isPertanyaanActive ? 'text-[#FDC700]' : 'text-gray-400 group-hover:text-gray-700'"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Kelola Kuesioner</span>
                            </Link>

                            <!-- 3. Kelola Section -->
                            <Link
                                href="/superadmin/sections"
                                @click="closeMobileMenu"
                                class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl text-sm font-bold transition-all group"
                                :class="[
                                    isSectionsActive
                                        ? 'bg-[#0D542B] text-white shadow-xs'
                                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                                ]"
                            >
                                <svg
                                    class="w-5 h-5 shrink-0 transition-colors"
                                    :class="isSectionsActive ? 'text-[#FDC700]' : 'text-gray-400 group-hover:text-gray-700'"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                <span>Kelola Section</span>
                            </Link>

                            <!-- 4. Data Alumni -->
                            <Link
                                href="/superadmin/alumni"
                                @click="closeMobileMenu"
                                class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl text-sm font-bold transition-all group"
                                :class="[
                                    isAlumniActive
                                        ? 'bg-[#0D542B] text-white shadow-xs'
                                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                                ]"
                            >
                                <svg
                                    class="w-5 h-5 shrink-0 transition-colors"
                                    :class="isAlumniActive ? 'text-[#FDC700]' : 'text-gray-400 group-hover:text-gray-700'"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span>Data Alumni</span>
                            </Link>
                        </nav>
                    </div>

                    <!-- TAUTAN LAINNYA -->
                    <div>
                        <div class="px-3 mb-2 text-[10px] font-black uppercase tracking-widest text-gray-400">
                            Pintasan Publik
                        </div>
                        <nav class="space-y-1">
                            <Link
                                href="/"
                                class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold text-gray-600 hover:text-[#0D542B] hover:bg-emerald-50/50 transition-colors group"
                            >
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-[#0D542B]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                    </svg>
                                    <span>Lihat Beranda Publik</span>
                                </span>
                                <svg class="w-3.5 h-3.5 text-gray-400 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </Link>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- BAGIAN BAWAH: KARTU PROFIL USER & LOGOUT -->
            <div class="p-4 border-t border-gray-100 bg-gray-50/70">
                <div class="p-3 bg-white rounded-2xl border border-gray-100 shadow-2xs space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#0D542B] text-[#FDC700] font-black text-sm flex items-center justify-center shadow-xs shrink-0">
                            {{ userInitials }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="font-extrabold text-xs text-gray-900 break-words leading-snug block">
                                {{ currentUser.name }}
                            </span>
                            <span class="text-[11px] text-gray-500 break-words leading-tight block mt-0.5">
                                {{ currentUser.email || 'admin@ukdw.ac.id' }}
                            </span>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="handleLogout"
                        class="w-full py-2 px-3 bg-red-50 hover:bg-red-100 text-red-700 hover:text-red-800 font-bold text-xs rounded-xl transition-all flex items-center justify-center gap-2 cursor-pointer border border-red-100"
                    >
                        <!-- Ikon Power Off untuk Keluar Sesi -->
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 11-12.728 0M12 3v9" />
                        </svg>
                        <span>Keluar Sesi</span>
                    </button>
                </div>
            </div>
        </aside>
    </div>
</template>
