<!--
  Halaman Utama (Parent Component): Kelola Profil Alumni
  File: resources/js/Pages/Alumni/Profil/Index.vue
  
  DIRELOAD OLEH BACKEND DARI:
  👉 Controller Tampil : App\Http\Controllers\Alumni\Profil\ProfilController.php (method index)
  👉 Route URL (GET)   : /alumni/profile
  
  DISIMPAN KE BACKEND OLEH:
  👉 Controller Simpan : App\Http\Controllers\Alumni\Profil\SimpanProfilController.php (method simpanProfil)
  👉 Route URL (POST)  : /alumni/profile
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
 * - Dijalankan saat tombol hijau "Simpan Perubahan" diklik (@click="submit")
 * - Mengirim seluruh isi objek 'form' via POST ke URL '/alumni/profile'
 * - Ditangkap di Backend oleh:
 *   App\Http\Controllers\Alumni\Profil\SimpanProfilController.php (method simpanProfil)
 */
const submit = () => {
    form.post('/alumni/profile', {
        preserveScroll: true, // Layar tidak akan loncat ke paling atas setelah simpan
        preserveState: true,  // Data yang sudah diketik tidak akan hilang
        onSuccess: () => {
            // Muncul popup berhasil SweetAlert2
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
            // Susun daftar error validasi dari Laravel
            let errorHtml = '<ul class="text-left list-disc list-inside">';
            for (let key in errors) {
                errorHtml += `<li>${errors[key]}</li>`;
            }
            errorHtml += '</ul>';

            // Muncul popup gagal SweetAlert2
            Swal.fire({
                title: 'Gagal Menyimpan!',
                html: '<p class="mb-2">Ada data yang belum lengkap atau salah:</p>' + errorHtml,
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
                            <span class="bg-white/20 px-3 py-1 rounded-full text-sm mr-3 backdrop-blur-sm shadow-sm">{{ alumniData.nim }}</span>
                            {{ alumniData.prodi?.nama_prodi || 'Program Studi' }}
                        </p>
                    </div>
                </div>
            </div>
        </header>

        <main class="w-full max-w-[1400px] mx-auto -mt-16 px-4 sm:px-6 lg:px-8 relative z-20">
            <form @submit.prevent="submit" class="bg-white/90 backdrop-blur-xl rounded-[2rem] shadow-xl border border-white/40 overflow-hidden relative transition-all duration-300">
                
                <!-- Tabs Navigation -->
                <div class="flex overflow-x-auto border-b border-gray-100 sticky top-20 z-10 bg-white/80 backdrop-blur-md px-4">
                    <button type="button" @click="activeTab = 'pribadi'" :class="activeTab === 'pribadi' ? 'border-[#005B3C] text-[#005B3C]' : 'border-transparent text-gray-400 hover:text-gray-700'" class="whitespace-nowrap py-5 px-8 border-b-2 font-bold text-sm transition-all flex-1 text-center">
                        Identitas & Alamat
                    </button>
                    <button type="button" @click="activeTab = 'karier'" :class="activeTab === 'karier' ? 'border-[#005B3C] text-[#005B3C]' : 'border-transparent text-gray-400 hover:text-gray-700'" class="whitespace-nowrap py-5 px-8 border-b-2 font-bold text-sm transition-all flex-1 text-center">
                        Karier & Jejaring
                    </button>
                    <button type="button" @click="activeTab = 'akademik'" :class="activeTab === 'akademik' ? 'border-[#005B3C] text-[#005B3C]' : 'border-transparent text-gray-400 hover:text-gray-700'" class="whitespace-nowrap py-5 px-8 border-b-2 font-bold text-sm transition-all flex-1 text-center">
                        Akademik & Yudisium
                    </button>
                    <button type="button" @click="activeTab = 'orangtua'" :class="activeTab === 'orangtua' ? 'border-[#005B3C] text-[#005B3C]' : 'border-transparent text-gray-400 hover:text-gray-700'" class="whitespace-nowrap py-5 px-8 border-b-2 font-bold text-sm transition-all flex-1 text-center">
                        Data Orang Tua
                    </button>
                </div>

                <!-- Tab Contents -->
                <div class="p-8 md:p-12 min-h-[500px]">
                    <div v-show="activeTab === 'pribadi'">
                        <FormPribadi :form="form" :provinces="provinces" :kabupatens="kabupatens" :negaras="negaras" />
                    </div>

                    <div v-show="activeTab === 'karier'">
                        <FormKarier :form="form" :provinces="provinces" :kabupatens="kabupatens" :negaras="negaras" :companies="companies" :alumniData="alumniData" />
                    </div>

                    <div v-show="activeTab === 'akademik'">
                        <FormAkademik :form="form" />
                    </div>

                    <div v-show="activeTab === 'orangtua'">
                        <FormOrangTua :form="form" :provinces="provinces" :kabupatens="kabupatens" />
                    </div>
                </div>

                <div class="px-8 md:px-12 py-6 bg-white flex flex-col md:flex-row justify-between items-center border-t border-gray-100/50">
                    <span v-if="form.isDirty" class="text-sm font-semibold text-orange-500 mb-4 md:mb-0 flex items-center animate-pulse">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Jangan lupa untuk menyimpan perubahan!
                    </span>
                    <span v-else></span>
                    
                    <button type="submit" :disabled="form.processing" class="w-full md:w-auto px-10 py-4 bg-gray-900 text-white font-bold rounded-2xl shadow-xl hover:-translate-y-1 hover:shadow-2xl hover:bg-[#005B3C] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center">
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Semua Perubahan' }}</span>
                    </button>
                </div>
            </form>
        </main>
    </div>
</template>
