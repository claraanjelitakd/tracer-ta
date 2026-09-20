<!--
  Halaman Utama (Parent Component): Kelola Profil Alumni
  File: resources/js/Pages/Alumni/Profil/Index.vue
  
  DIRELOAD OLEH BACKEND DARI:
  - Controller Tampil : App\Http\Controllers\Alumni\Profil\ProfilController.php (method tampilkanHalamanProfil)
  - Route URL (GET)   : /alumni/profile
  
  DISIMPAN KE BACKEND OLEH:
  - Controller Simpan : App\Http\Controllers\Alumni\Profil\SimpanProfilController.php (method simpanProfil)
  - Route URL (POST)  : /alumni/profile
-->
<script setup>
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Swal from 'sweetalert2';

import FormPribadi from './Components/FormPribadi.vue';
import FormAkademik from './Components/FormAkademik.vue';
import FormOrangTua from './Components/FormOrangTua.vue';
import FormKarier from './Components/FormKarier.vue';
import Navbar from '../Components/Navbar.vue';

/**
 * ====================================================================
 * MENERIMA DATA (PROPS) DARI CONTROLLER (ProfilController.php)
 * ====================================================================
 * - alumniData : Objek detail model Alumni dari database
 * - formData   : Objek data awal gabungan (data akademik, pribadi, orang tua, pekerjaan)
 * - provinces  : Array master data provinsi dari tabel 'provinces'
 * - kabupatens : Array master data kabupaten dari tabel 'kabupatens'
 * - companies  : Array master data perusahaan dari tabel 'companies'
 */
const props = defineProps({
    alumniData: Object,
    formData: Object,
    provinces: Array,
    kabupatens: Array,
    negaras: Array,
    companies: Array,
    refOptions: Object,
});

const page = usePage();
const user = computed(() => page.props.auth.user);

// Storage key untuk mengingat tab aktif saat refresh
const PROFILE_TAB_STORAGE_KEY = 'tracerstudy_alumni_profile_tab';

// Inisialisasi activeTab dari URL param atau localStorage
const getInitialTab = () => {
    if (typeof window !== 'undefined') {
        // Hapus cache draft profil lama jika pernah tersimpan di browser
        localStorage.removeItem('tracerstudy_alumni_profile_draft');

        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if (tabParam && ['pribadi', 'akademik', 'orangtua', 'karier'].includes(tabParam)) {
            return tabParam;
        }
        const cached = localStorage.getItem(PROFILE_TAB_STORAGE_KEY);
        if (cached && ['pribadi', 'akademik', 'orangtua', 'karier'].includes(cached)) {
            return cached;
        }
    }
    return 'pribadi';
};

/**
 * ====================================================================
 * MEMBUAT OBJEK FORM DENGAN useForm DARI INERTIA
 * ====================================================================
 * Di sinilah 'form' lahir!
 * Nilai awalnya diambil dari props.formData yang dikirim oleh ProfilController.php.
 * 
 * Objek 'form' inilah yang nanti dioper ke 4 komponen anak:
 * <FormPribadi  :form="form" />
 * <FormAkademik :form="form" />
 * <FormOrangTua :form="form" />
 * <FormKarier   :form="form" />
 */
const form = useForm(JSON.parse(JSON.stringify(props.formData || {})));

// Sinkronkan data form jika props berubah dari backend (misal setelah simpan)
watch(() => props.formData, (newData) => {
    if (newData) {
        Object.assign(form, JSON.parse(JSON.stringify(newData)));
    }
}, { deep: true });

// State untuk tab yang sedang aktif ('pribadi', 'akademik', 'orangtua', 'karier')
const activeTab = ref(getInitialTab());

const tabs = [
    { id: 'pribadi', name: 'Identitas & Alamat', subtitle: 'Biodata & Kontak', step: '1' },
    { id: 'karier', name: 'Karier & Jejaring', subtitle: 'Pekerjaan & Medsos', step: '2' },
    { id: 'akademik', name: 'Akademik & Yudisium', subtitle: 'Riwayat Studi', step: '3' },
    { id: 'orangtua', name: 'Data Orang Tua', subtitle: 'Kontak Keluarga', step: '4' },
];

// Helper validasi NPWP (15 atau 16 digit per UU HPP & PMK 112/PMK.03/2022)
const isValidNpwp = (val) => {
    if (!val) return false;
    const clean = String(val).replace(/[^0-9]/g, '');
    return clean.length === 15 || clean.length === 16;
};

// Helper validasi email
const isValidEmail = (val) => {
    if (!val) return false;
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(val).trim());
};

// Helper validasi telepon
const isValidPhone = (val) => {
    if (!val) return false;
    const clean = String(val).replace(/[^0-9+]/g, '');
    return clean.length >= 10 && clean.length <= 15;
};

// Evaluasi kelengkapan data tiap tab secara realtime
const tabCompleteness = computed(() => {
    // 1. Tab Pribadi
    const missingPribadi = [];
    if (!form.nama || !String(form.nama).trim()) missingPribadi.push('Nama Lengkap');
    if (!form.npwp || !isValidNpwp(form.npwp)) missingPribadi.push('NPWP (15/16 Digit)');
    if (!form.tempat_lahir || !String(form.tempat_lahir).trim()) missingPribadi.push('Tempat Lahir');
    if (!form.tanggal_lahir) missingPribadi.push('Tanggal Lahir');
    if (!form.jenis_kelamin) missingPribadi.push('Jenis Kelamin');
    if (!form.agama) missingPribadi.push('Agama');
    if (!form.nomor_telepon || !isValidPhone(form.nomor_telepon)) missingPribadi.push('Nomor Telepon / WhatsApp');
    if ((!form.email_pribadi && !form.email) || !isValidEmail(form.email_pribadi || form.email)) missingPribadi.push('Email Pribadi');
    if ((!form.alamat_saat_ini || !String(form.alamat_saat_ini).trim()) && (!form.alamat || !String(form.alamat).trim())) missingPribadi.push('Alamat Domisili');
    if (!form.provinsi_id && !form.propinsi_id) missingPribadi.push('Provinsi Domisili');
    if (!form.kabupaten_id) missingPribadi.push('Kabupaten/Kota Domisili');

    // 2. Tab Karier
    const missingKarier = [];
    const kategori = (form.kategori_pekerjaan || '').trim();
    if (!kategori) {
        missingKarier.push('Status Pekerjaan / Aktivitas Utama');
    } else if (kategori === 'Pekerja') {
        if (!form.nama_perusahaan || !String(form.nama_perusahaan).trim()) missingKarier.push('Nama Perusahaan / Instansi');
        if ((!form.perusahaan_alamat || !String(form.perusahaan_alamat).trim()) && (!form.company_alamat || !String(form.company_alamat).trim())) missingKarier.push('Alamat Perusahaan');
        if (!form.perusahaan_skala && !form.company_skala) missingKarier.push('Skala Perusahaan');
        if (!form.perusahaan_jenis_perusahaan && !form.company_jenis_perusahaan) missingKarier.push('Jenis Perusahaan');
        if (!form.posisi_jabatan || !String(form.posisi_jabatan).trim()) missingKarier.push('Posisi Jabatan');
        if (!form.nama_atasan || !String(form.nama_atasan).trim()) missingKarier.push('Nama Atasan Langsung');
        if ((!form.email_atasan || !isValidEmail(form.email_atasan)) && (!form.telepon_atasan || !isValidPhone(form.telepon_atasan))) missingKarier.push('Kontak Atasan (Email / No HP)');
    } else if (kategori === 'Wiraswasta') {
        if (!form.nama_perusahaan || !String(form.nama_perusahaan).trim()) missingKarier.push('Nama Usaha / Bisnis');
        if (!form.posisi_wiraswasta) missingKarier.push('Posisi / Jabatan dalam Usaha');
    }

    // Validasi opsional studi lanjut jika alumni mengaktifkan studi lanjut
    if (form.pendidikan_tingkat || form.perguruan_tinggi || form.pendidikan_prodi) {
        if (!form.pendidikan_tingkat || !String(form.pendidikan_tingkat).trim()) missingKarier.push('Jenjang Pendidikan');
        if (!form.perguruan_tinggi || !String(form.perguruan_tinggi).trim()) missingKarier.push('Perguruan Tinggi Studi Lanjut');
        if (!form.pendidikan_prodi || !String(form.pendidikan_prodi).trim()) missingKarier.push('Program Studi Lanjut');
    }

    // 3. Tab Akademik
    const missingAkademik = [];
    if (!form.nim) missingAkademik.push('NIM');

    // 4. Tab Orang Tua
    const missingOrangTua = [];
    if (!form.nama_orang_tua || !String(form.nama_orang_tua).trim()) missingOrangTua.push('Nama Orang Tua / Wali');
    if (!form.pekerjaan_orang_tua || !String(form.pekerjaan_orang_tua).trim()) missingOrangTua.push('Pekerjaan Orang Tua');
    if (!form.nomor_telepon_orang_tua || !isValidPhone(form.nomor_telepon_orang_tua)) missingOrangTua.push('Nomor Telepon Orang Tua');
    if (!form.alamat_orang_tua || !String(form.alamat_orang_tua).trim()) missingOrangTua.push('Alamat Orang Tua');
    if (!form.provinsi_id_orang_tua) missingOrangTua.push('Provinsi Orang Tua');
    if (!form.kabupaten_id_orang_tua) missingOrangTua.push('Kabupaten Orang Tua');

    return {
        pribadi: {
            isComplete: missingPribadi.length === 0,
            missing: missingPribadi,
        },
        karier: {
            isComplete: missingKarier.length === 0,
            missing: missingKarier,
        },
        akademik: {
            isComplete: missingAkademik.length === 0,
            missing: missingAkademik,
        },
        orangtua: {
            isComplete: missingOrangTua.length === 0,
            missing: missingOrangTua,
        },
    };
});

const currentTabIndex = computed(() => tabs.findIndex(t => t.id === activeTab.value));
const prevTab = computed(() => currentTabIndex.value > 0 ? tabs[currentTabIndex.value - 1] : null);
const nextTab = computed(() => currentTabIndex.value < tabs.length - 1 ? tabs[currentTabIndex.value + 1] : null);

const switchTab = (tabName) => {
    activeTab.value = tabName;
    if (typeof window !== 'undefined') {
        const container = document.getElementById('profile-form-container');
        if (container) {
            const topOffset = -90;
            const targetY = container.getBoundingClientRect().top + window.pageYOffset + topOffset;
            window.scrollTo({ top: targetY, behavior: 'smooth' });
        }
    }
};

// Sinkronkan activeTab ke URL dan localStorage agar tidak reset saat refresh
watch(activeTab, (newTab) => {
    if (typeof window !== 'undefined') {
        localStorage.setItem(PROFILE_TAB_STORAGE_KEY, newTab);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', newTab);
        window.history.replaceState({}, '', url.toString());
    }
});

/**
 * ====================================================================
 * FUNGSI SUBMIT (SIMPAN SELURUH PROFIL KE BACKEND)
 * ====================================================================
 */
const submit = () => {
    // Validasi nominal gaji sebelum submit
    if (form.gaji && Number(form.gaji) > 0 && Number(form.gaji) < 1000) {
        activeTab.value = 'karier';
        Swal.fire({
            title: 'Nominal Gaji Tidak Valid!',
            text: 'Nominal rata-rata pendapatan minimal ribuan (minimal Rp 1.000) atau kosongkan kolom pendapatan jika tidak berkenan membagikan nominal.',
            icon: 'warning',
            confirmButtonColor: '#005B3C',
            confirmButtonText: 'Perbaiki'
        });
        return;
    }

    form.post('/alumni/profile', {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Profil dan Data Anda berhasil diperbarui.',
                icon: 'success',
                confirmButtonColor: '#005B3C',
                confirmButtonText: 'Tutup'
            });
            form.clearErrors();
        },
        onError: (errors) => {
            let errorHtml = '<ul class="text-left list-disc list-inside text-xs">';
            for (let key in errors) {
                errorHtml += `<li>${errors[key]}</li>`;
            }
            errorHtml += '</ul>';

            Swal.fire({
                title: 'Gagal Menyimpan!',
                html: '<p class="mb-2 text-sm">Ada data yang belum lengkap atau salah:</p>' + errorHtml,
                icon: 'error',
                confirmButtonColor: '#d33',
                confirmButtonText: 'Perbaiki'
            });
        }
    });
};
</script>

<template>
    <Head title="Profil Alumni - Tracer Study" />

    <div class="min-h-screen bg-[#f8fafc] pb-24">
        <!-- Navigasi Utama Terpadu Alumni -->
        <Navbar :user="user" />

        <!-- Header Profil -->
        <header class="bg-gradient-to-r from-[#005B3C] to-[#007b55] pt-12 pb-24 relative overflow-hidden">
            <!-- Decorative circle -->
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="w-full max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center md:items-start justify-between relative z-10">
                <div class="flex flex-col md:flex-row items-center md:items-center space-y-4 md:space-y-0 md:space-x-8">
                    <div class="w-32 h-32 bg-white text-[#005B3C] rounded-3xl flex items-center justify-center text-5xl font-black shadow-2xl transform rotate-3 hover:rotate-0 transition-transform duration-300">
                        {{ user.name.charAt(0) }}
                    </div>
                    <div class="text-center md:text-left mt-2">
                        <h1 class="text-4xl font-extrabold text-white tracking-tight">{{ user.name }}</h1>
                        <p class="text-green-100 font-medium mt-2 flex items-center justify-center md:justify-start">
                            <span class="bg-white/20 px-3 py-1 rounded-full text-sm mr-3 backdrop-blur-sm shadow-sm">{{ alumniData?.nim || formData?.nim }}</span>
                            {{ alumniData?.prodi?.nama_prodi || formData?.program_studi || 'Program Studi' }}
                        </p>
                    </div>
                </div>

                <!-- Petunjuk Ringkas Profil -->
                <div class="mt-6 md:mt-0 bg-white/15 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-white text-xs max-w-sm">
                    <div class="flex items-center gap-2 font-bold mb-1">
                        <svg class="w-4 h-4 text-yellow-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                        <span>Petunjuk Pengisian Profil</span>
                    </div>
                    <p class="text-green-50 leading-relaxed">
                        Lengkapi data pada 4 formulir berikut. Indikator status langsung terlihat pada setiap kolom formulir dan tab navigasi.
                    </p>
                </div>
            </div>
        </header>

        <main id="profile-form-container" class="w-full max-w-[1400px] mx-auto -mt-16 px-4 sm:px-6 lg:px-8 relative z-20">
            <form @submit.prevent="submit" class="bg-white/95 backdrop-blur-xl rounded-[2rem] shadow-xl border border-gray-100 relative transition-all duration-300">
                
                <!-- Sticky Tabs Navigation (Bersih & Profesional) -->
                <div class="sticky top-[72px] lg:top-20 z-40 bg-white/95 backdrop-blur-md border-b border-gray-200/80 shadow-xs rounded-t-[2rem] px-4 sm:px-6 py-2.5">
                    <div class="flex items-center justify-between gap-2 overflow-x-auto no-scrollbar py-1">
                        <button 
                            v-for="tab in tabs" 
                            :key="tab.id"
                            type="button" 
                            @click="switchTab(tab.id)" 
                            :class="[
                                activeTab === tab.id 
                                    ? 'bg-[#005B3C] text-white shadow-md shadow-[#005B3C]/20 font-bold' 
                                    : 'bg-gray-50/80 hover:bg-gray-100 text-gray-600 hover:text-gray-900 border border-gray-200/60 font-semibold'
                            ]"
                            class="whitespace-nowrap py-3 px-4 sm:px-5 rounded-2xl text-xs sm:text-sm transition-all duration-200 flex items-center justify-between gap-3 flex-1 min-w-[190px] cursor-pointer group"
                        >
                            <div class="flex items-center gap-2.5 min-w-0">
                                <!-- Step Number Badge -->
                                <span 
                                    :class="activeTab === tab.id ? 'bg-white text-[#005B3C]' : 'bg-gray-200 text-gray-700 group-hover:bg-gray-300'"
                                    class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-black shrink-0 transition-colors"
                                >
                                    {{ tab.step }}
                                </span>
                                
                                <!-- Tab Title -->
                                <div class="text-left min-w-0">
                                    <span class="block leading-tight truncate">{{ tab.name }}</span>
                                    <span 
                                        :class="activeTab === tab.id ? 'text-green-100' : 'text-gray-400'"
                                        class="hidden sm:block text-[10px] font-medium leading-tight truncate"
                                    >
                                        {{ tab.subtitle }}
                                    </span>
                                </div>
                            </div>

                            <!-- Polished Status Indicator (Visual Langsung Tanpa Teks 'X belum') -->
                            <div class="shrink-0 flex items-center pl-1">
                                <span 
                                    v-if="tabCompleteness[tab.id]?.isComplete" 
                                    class="inline-flex items-center justify-center w-5 h-5 rounded-full transition-all shadow-2xs"
                                    :class="activeTab === tab.id ? 'bg-[#FFD700] text-[#005B3C]' : 'bg-emerald-500 text-white'"
                                    title="Data lengkap"
                                >
                                    <svg class="w-3 h-3 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                </span>
                                <span 
                                    v-else 
                                    class="w-2.5 h-2.5 rounded-full transition-all shrink-0"
                                    :class="activeTab === tab.id ? 'bg-amber-300 ring-2 ring-white/40' : 'bg-rose-400 ring-2 ring-rose-100'"
                                    title="Data belum lengkap"
                                ></span>
                            </div>
                        </button>
                    </div>

                    <!-- Visual Progress Bar -->
                    <div class="w-full bg-gray-100 h-1.5 rounded-full mt-2 overflow-hidden">
                        <div 
                            class="bg-[#005B3C] h-full transition-all duration-300 rounded-full"
                            :style="{ width: `${((currentTabIndex + 1) / tabs.length) * 100}%` }"
                        ></div>
                    </div>
                </div>

                <!-- Notifikasi Peringatan Perubahan Belum Disimpan (Modern Top Notification Banner) -->
                <transition 
                    enter-active-class="transition duration-300 ease-out" 
                    enter-from-class="transform -translate-y-2 opacity-0" 
                    enter-to-class="transform translate-y-0 opacity-100" 
                    leave-active-class="transition duration-200 ease-in" 
                    leave-from-class="transform translate-y-0 opacity-100" 
                    leave-to-class="transform -translate-y-2 opacity-0"
                >
                    <div v-if="form.isDirty" class="mx-6 sm:mx-8 md:mx-12 mt-6 p-4 rounded-2xl bg-amber-50/90 border border-amber-200 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center text-amber-700 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                            <div>
                                <p class="text-xs sm:text-sm font-bold text-amber-900">Ada Perubahan Belum Disimpan</p>
                                <p class="text-[11px] text-amber-700">Anda telah mengubah data profil. Jangan lupa simpan perubahan agar data tersimpan di sistem.</p>
                            </div>
                        </div>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="px-5 py-2.5 bg-[#005B3C] hover:bg-[#00472e] text-white text-xs font-bold rounded-xl shadow-xs transition-all shrink-0 cursor-pointer disabled:opacity-50"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Sekarang' }}
                        </button>
                    </div>
                </transition>

                <!-- ============================================================= -->
                <!-- KONTEN TAB FORM PROFIL -->
                <!-- ============================================================= -->
                <div class="p-6 sm:p-8 md:p-12 min-h-[500px]">
                    <!-- Tab 1: Identitas & Alamat Pribadi -->
                    <div v-show="activeTab === 'pribadi'">
                        <FormPribadi :form="form" :provinces="provinces" :kabupatens="kabupatens" :negaras="negaras" />
                    </div>

                    <!-- Tab 2: Karier, Pekerjaan & Data Atasan -->
                    <div v-show="activeTab === 'karier'">
                        <FormKarier 
                            :form="form" 
                            :provinces="provinces" 
                            :kabupatens="kabupatens" 
                            :negaras="negaras" 
                            :companies="companies" 
                            :alumniData="alumniData" 
                            :refOptions="refOptions" 
                        />
                    </div>

                    <!-- Tab 3: Data Akademik & Yudisium -->
                    <div v-show="activeTab === 'akademik'">
                        <FormAkademik :form="form" />
                    </div>

                    <!-- Tab 4: Data Orang Tua / Wali -->
                    <div v-show="activeTab === 'orangtua'">
                        <FormOrangTua :form="form" :provinces="provinces" :kabupatens="kabupatens" />
                    </div>
                </div>

                <!-- Bottom Sticky / Action Bar: Navigasi Antar Tab & Tombol Simpan -->
                <div class="px-6 sm:px-8 md:px-12 py-5 bg-gray-50/90 backdrop-blur-md rounded-b-[2rem] flex flex-col md:flex-row justify-between items-center gap-4 border-t border-gray-100">
                    <!-- Left: Navigasi Sebelumnya -->
                    <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-start">
                        <button 
                            v-if="prevTab" 
                            type="button" 
                            @click="switchTab(prevTab.id)"
                            class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-xs sm:text-sm font-semibold transition-all flex items-center gap-1.5 shadow-2xs cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                            <span>Sebelumnya: {{ prevTab.name }}</span>
                        </button>
                        <span v-else class="text-xs text-gray-400 font-medium">Langkah 1 dari 4</span>
                    </div>
                    
                    <!-- Right: Tombol Selanjutnya / Simpan -->
                    <div class="flex items-center gap-3 w-full md:w-auto justify-end">
                        <button 
                            v-if="nextTab" 
                            type="button" 
                            @click="switchTab(nextTab.id)"
                            class="px-5 py-3 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-[#005B3C] border border-emerald-200 text-xs sm:text-sm font-bold transition-all flex items-center gap-1.5 shadow-2xs cursor-pointer"
                        >
                            <span>Lanjut: {{ nextTab.name }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>

                        <button 
                            type="submit" 
                            :disabled="form.processing" 
                            class="px-8 py-3 bg-[#005B3C] hover:bg-[#00472e] text-white text-xs sm:text-sm font-extrabold rounded-xl shadow-md hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </div>
</template>
