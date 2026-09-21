<!--
  Komponen Navigasi Terpadu Admin Program Studi (Navbar.vue)
  File: resources/js/Pages/AdminProdi/Components/Navbar.vue
  
  Desain Mengikuti Standar Super Admin UKDW:
  Bersih, profesional, minimalis, dan elegan tanpa dekorasi berlebihan.
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
    prodi: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();

const currentUser = computed(() => {
    return props.user?.name ? props.user : (page.props.auth?.user || { name: 'Admin Program Studi' });
});

const currentProdi = computed(() => {
    return props.prodi?.nama_prodi ? props.prodi : (page.props.auth?.user?.prodi || {});
});

const isDashboardActive = computed(() => {
    return page.url === '/prodi' || page.url.startsWith('/prodi/dashboard');
});

const isPertanyaanActive = computed(() => {
    return page.url.startsWith('/prodi/pertanyaan');
});

const isSectionActive = computed(() => {
    return page.url.startsWith('/prodi/sections');
});

const isAlumniActive = computed(() => {
    return page.url.startsWith('/prodi/alumni');
});

const handleLogout = () => {
    Swal.fire({
        title: 'Konfirmasi Keluar',
        text: 'Apakah Anda yakin ingin keluar dari sesi Admin Program Studi?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#005B3C',
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
    <nav class="bg-white shadow-xs border-b border-gray-200 sticky top-0 z-50">
        <div class="w-full max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                
                <!-- Kiri: Brand Logo & Navigasi -->
                <div class="flex items-center space-x-6">
                    <Link href="/prodi/dashboard" class="flex items-center space-x-3 group">
                        <img 
                            src="/uploads/logo/logo-ukdw.png" 
                            onerror="this.src='https://www.ukdw.ac.id/wp-content/uploads/2017/10/logo-ukdw.png'" 
                            alt="Logo UKDW" 
                            class="h-8 w-8 object-contain" 
                        />
                        <div>
                            <span class="text-gray-900 font-black text-base sm:text-lg tracking-tight group-hover:text-[#005B3C] transition-colors">
                                Tracer Study UKDW
                            </span>
                            <span v-if="currentProdi?.nama_prodi" class="hidden sm:inline-block ml-2 px-2.5 py-0.5 rounded-md text-xs font-bold bg-emerald-50 text-[#005B3C] border border-emerald-200">
                                {{ currentProdi.nama_prodi }}
                            </span>
                        </div>
                    </Link>

                    <!-- Navigasi Menu Desktop -->
                    <div class="hidden md:flex items-center space-x-1 pl-4 border-l border-gray-200">
                        <Link 
                            href="/prodi/dashboard" 
                            class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all"
                            :class="[
                                isDashboardActive
                                    ? 'text-[#005B3C] bg-emerald-50 font-bold'
                                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                            ]"
                        >
                            Dashboard
                        </Link>
                        <Link 
                            href="/prodi/pertanyaan" 
                            class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all"
                            :class="[
                                isPertanyaanActive
                                    ? 'text-[#005B3C] bg-emerald-50 font-bold'
                                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                            ]"
                        >
                            Kelola Kuesioner
                        </Link>
                        <Link 
                            href="/prodi/sections" 
                            class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all"
                            :class="[
                                isSectionActive
                                    ? 'text-[#005B3C] bg-emerald-50 font-bold'
                                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                            ]"
                        >
                            Kelola Section
                        </Link>
                        <Link 
                            href="/prodi/alumni" 
                            class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all"
                            :class="[
                                isAlumniActive
                                    ? 'text-[#005B3C] bg-emerald-50 font-bold'
                                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                            ]"
                        >
                            Data Alumni
                        </Link>
                    </div>
                </div>

                <!-- Kanan: Info Akun & Keluar -->
                <div class="flex items-center space-x-3">
                    <div class="hidden sm:flex flex-col text-right">
                        <span class="text-gray-900 font-bold text-xs sm:text-sm leading-tight">{{ currentUser.name }}</span>
                        <span class="text-gray-500 text-[11px] font-medium">{{ currentProdi?.nama_prodi || 'Program Studi' }}</span>
                    </div>

                    <button 
                        @click="handleLogout"
                        class="px-3 py-1.5 sm:px-3.5 sm:py-2 text-xs font-bold text-red-600 hover:text-white bg-red-50 hover:bg-red-600 rounded-xl transition-colors cursor-pointer border border-red-200 hover:border-red-600 flex items-center gap-1.5"
                    >
                        <!-- Ikon Power Off untuk Tombol Keluar -->
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 11-12.728 0M12 3v9" />
                        </svg>
                        <span>Keluar</span>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation Bar -->
            <div class="md:hidden flex items-center justify-around py-2 border-t border-gray-100 text-xs font-bold">
                <Link 
                    href="/prodi/dashboard"
                    class="py-1.5 px-2.5 rounded-lg"
                    :class="isDashboardActive ? 'text-[#005B3C] bg-emerald-50' : 'text-gray-600'"
                >
                    Dashboard
                </Link>
                <Link 
                    href="/prodi/pertanyaan"
                    class="py-1.5 px-2.5 rounded-lg"
                    :class="isPertanyaanActive ? 'text-[#005B3C] bg-emerald-50' : 'text-gray-600'"
                >
                    Kuesioner
                </Link>
                <Link 
                    href="/prodi/sections"
                    class="py-1.5 px-2.5 rounded-lg"
                    :class="isSectionActive ? 'text-[#005B3C] bg-emerald-50' : 'text-gray-600'"
                >
                    Section
                </Link>
                <Link 
                    href="/prodi/alumni"
                    class="py-1.5 px-2.5 rounded-lg"
                    :class="isAlumniActive ? 'text-[#005B3C] bg-emerald-50' : 'text-gray-600'"
                >
                    Alumni
                </Link>
            </div>
        </div>
    </nav>
</template>
