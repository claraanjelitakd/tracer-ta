<!--
  Komponen Navigasi Sidebar Terpadu Admin Program Studi (Sidebar.vue)
  Desain Profesional Admin UKDW:
  - Format Bersih, Tipis, Kotak (rounded-lg), Solid #0D542B (Tanpa Gradasi)
  - Fixed Pinned Viewport (h-screen overflow-hidden, flex-1 scrollable)
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

const isPerusahaanActive = computed(() => {
    return page.url.startsWith('/prodi/perusahaan');
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
        <header class="lg:hidden bg-white border-b border-slate-200 sticky top-0 z-40 px-4 py-2.5 flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="toggleMobileMenu"
                    class="p-1.5 rounded-lg text-slate-700 hover:bg-slate-100 hover:text-slate-900 focus:outline-none transition-colors cursor-pointer"
                    aria-label="Buka Menu"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <Link href="/prodi/dashboard" class="flex items-center gap-2">
                    <img src="/uploads/landing/2.png" alt="Logo UKDW" class="h-7 w-7 object-contain" onerror="this.style.display='none'" />
                    <div>
                        <span class="font-bold text-xs text-slate-900 tracking-tight block leading-tight">Tracer Study</span>
                        <span class="text-[10px] font-semibold text-[#0D542B] uppercase tracking-wider">{{ currentProdi.nama_prodi || 'Admin Prodi' }}</span>
                    </div>
                </Link>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-700 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-md">
                    {{ userInitials }}
                </span>
            </div>
        </header>

        <!-- BACKDROP OVERLAY UNTUK MOBILE DRAWER -->
        <div
            v-if="isMobileOpen"
            class="fixed inset-0 bg-black/40 z-50 lg:hidden transition-opacity duration-200"
            @click="closeMobileMenu"
        ></div>

        <!-- SIDEBAR UTAMA ADMIN (BERSIH, TIPIS, KOTAK) -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-slate-200 flex flex-col justify-between transition-transform duration-200 ease-in-out shadow-lg lg:shadow-none h-screen overflow-hidden"
            :class="[
                isMobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
            ]"
        >
            <!-- BAGIAN ATAS: LOGO & BRANDING -->
            <div class="shrink-0">
                <div class="p-4 border-b border-slate-200 flex items-center justify-between bg-white">
                    <Link href="/prodi/dashboard" class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-lg border border-slate-200 p-1 flex items-center justify-center shrink-0 bg-white shadow-2xs">
                            <img src="/uploads/landing/2.png" alt="Logo UKDW" class="w-full h-full object-contain" onerror="this.style.display='none'" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="font-bold text-slate-900 text-sm tracking-tight block leading-tight">
                                Tracer Study UKDW
                            </span>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#0D542B] shrink-0"></span>
                                <span class="text-[11px] font-semibold text-[#0D542B] uppercase tracking-wider block truncate">
                                    {{ currentProdi.nama_prodi || 'Program Studi' }}
                                </span>
                            </div>
                        </div>
                    </Link>

                    <button
                        type="button"
                        @click="closeMobileMenu"
                        class="lg:hidden p-1.5 rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                        aria-label="Tutup Menu"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- NAVIGASI MENU UTAMA PRODI (SLIM, FLAT, KOTAK) -->
            <div class="flex-1 overflow-y-auto min-h-0 px-3 py-3 space-y-1">
                <div class="px-2.5 pb-1 pt-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        Menu Program Studi
                    </span>
                </div>

                <!-- 1. Dashboard -->
                <Link
                    href="/prodi/dashboard"
                    @click="closeMobileMenu"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition-colors"
                    :class="[
                        isDashboardActive
                            ? 'bg-[#0D542B] text-white shadow-2xs'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                    ]"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span class="truncate">Dashboard Utama</span>
                </Link>

                <!-- 2. Data Alumni Khusus Prodi -->
                <Link
                    href="/prodi/alumni"
                    @click="closeMobileMenu"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition-colors"
                    :class="[
                        isAlumniActive
                            ? 'bg-[#0D542B] text-white shadow-2xs'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                    ]"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span class="truncate">Data Alumni Prodi</span>
                </Link>

                <div class="px-2.5 pt-3 pb-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        Kuesioner Internal Prodi
                    </span>
                </div>

                <!-- 3. Kelola Section Prodi -->
                <Link
                    href="/prodi/sections"
                    @click="closeMobileMenu"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition-colors"
                    :class="[
                        isSectionsActive
                            ? 'bg-[#0D542B] text-white shadow-2xs'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                    ]"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span class="truncate">Kelola Bagian / Seksi</span>
                </Link>

                <!-- 4. Kelola Pertanyaan Prodi -->
                <Link
                    href="/prodi/pertanyaan"
                    @click="closeMobileMenu"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition-colors"
                    :class="[
                        isPertanyaanActive
                            ? 'bg-[#0D542B] text-white shadow-2xs'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                    ]"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="truncate">Kelola Pertanyaan</span>
                </Link>

                <div class="px-2.5 pt-3 pb-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        Verifikasi & Master
                    </span>
                </div>

                <!-- 5. Verifikasi Perusahaan -->
                <Link
                    href="/prodi/perusahaan"
                    @click="closeMobileMenu"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition-colors"
                    :class="[
                        isPerusahaanActive
                            ? 'bg-[#0D542B] text-white shadow-2xs'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                    ]"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span class="truncate">Verifikasi Perusahaan</span>
                </Link>
            </div>

            <!-- BAGIAN BAWAH: INFO PENGGUNA & TOMBOL LOGOUT (KOTAK, BERSIH) -->
            <div class="p-3 border-t border-slate-200 shrink-0 bg-white">
                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between gap-2.5">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-lg bg-[#0D542B] text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs">
                            {{ userInitials }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="block text-xs font-semibold text-slate-900 truncate">
                                {{ currentUser.name }}
                            </span>
                            <span class="block text-[10px] text-slate-500 truncate">
                                {{ currentProdi.nama_prodi || currentUser.email }}
                            </span>
                        </div>
                    </div>

                    <!-- Tombol Logout -->
                    <button
                        type="button"
                        @click="handleLogout"
                        class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-white rounded-md transition-colors cursor-pointer shrink-0 border border-transparent hover:border-slate-200"
                        title="Keluar dari Sistem"
                        aria-label="Logout"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 11-12.728 0M12 3v9" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>
    </div>
</template>
