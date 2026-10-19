<!--
  Komponen Anak: Tab Profil & Biodata Alumni (Admin View)
  File: resources/js/Pages/SuperAdmin/Alumni/Components/TabProfil.vue
  
  Menyelaraskan tampilan profil alumni di sisi admin dengan halaman /alumni/profil
  menggunakan kembali komponen FormPribadi, FormKarier, FormAkademik, dan FormOrangTua.
-->
<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

import FormPribadi from '@/Pages/Alumni/Profil/Components/FormPribadi.vue';
import FormAkademik from '@/Pages/Alumni/Profil/Components/FormAkademik.vue';
import FormOrangTua from '@/Pages/Alumni/Profil/Components/FormOrangTua.vue';
import FormKarier from '@/Pages/Alumni/Profil/Components/FormKarier.vue';

const props = defineProps({
    alumni: {
        type: Object,
        required: true,
    },
    formData: {
        type: Object,
        default: () => ({}),
    },
    provinces: {
        type: Array,
        default: () => [],
    },
    kabupatens: {
        type: Array,
        default: () => [],
    },
    negaras: {
        type: Array,
        default: () => [],
    },
    companies: {
        type: Array,
        default: () => [],
    },
    refOptions: {
        type: Object,
        default: () => ({}),
    },
    evaluasiAtasan: {
        type: Object,
        default: null,
    },
    updateRoute: {
        type: String,
        required: true,
    },
});

const form = useForm({ ...(props.formData || {}) });

watch(() => props.formData, (newData) => {
    if (newData) {
        Object.assign(form, JSON.parse(JSON.stringify(newData)));
    }
}, { deep: true });

const activeProfileTab = ref('pribadi');

const profileTabs = [
    { id: 'pribadi', name: '1. Data Pribadi & Kontak', subtitle: 'Identitas, NIK, NPWP & Domisili' },
    { id: 'akademik', name: '2. Data Akademik', subtitle: 'NIM, Prodi, IPK & Kelulusan' },
    { id: 'karier', name: '3. Pekerjaan & Karier', subtitle: 'Instansi, Jabatan & Atasan' },
    { id: 'orangtua', name: '4. Data Orang Tua', subtitle: 'Informasi Kontak Keluarga' },
];

const submitForm = () => {
    form.post(props.updateRoute, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Data Tersimpan!',
                text: 'Profil alumni berhasil diperbarui.',
                confirmButtonColor: '#0D542B',
                timer: 2000,
            });
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] || 'Terjadi kesalahan saat menyimpan data.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: firstError,
                confirmButtonColor: '#0D542B',
            });
        },
    });
};
</script>

<template>
    <div class="space-y-5">
        <!-- Sub-Navigasi Tab Profil & Tombol Aksi Simpan -->
        <div class="bg-white p-4 sm:p-5 rounded-xl shadow-xs border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex flex-wrap gap-1.5 p-1 bg-slate-100 rounded-lg text-xs">
                <button 
                    v-for="t in profileTabs"
                    :key="t.id"
                    type="button"
                    @click="activeProfileTab = t.id"
                    class="px-3.5 py-1.5 rounded-md font-semibold transition-all cursor-pointer"
                    :class="activeProfileTab === t.id ? 'bg-[#0D542B] text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                >
                    {{ t.name }}
                </button>
            </div>

            <button 
                type="button" 
                @click="submitForm"
                :disabled="form.processing"
                class="px-4 py-2 bg-[#0D542B] hover:bg-[#08381c] text-white text-xs font-bold rounded-lg transition-all shadow-2xs flex items-center gap-1.5 self-start sm:self-auto cursor-pointer disabled:opacity-50"
            >
                <svg v-if="!form.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <svg v-else class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Profil Alumni' }}</span>
            </button>
        </div>

        <!-- Banner Perubahan Belum Disimpan -->
        <div v-if="form.isDirty" class="p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-center justify-between text-xs text-amber-900">
            <span>Ada perubahan formulir yang belum disimpan. Pastikan menekan tombol <b>Simpan Profil Alumni</b> untuk memperbarui database.</span>
            <button 
                type="button" 
                @click="submitForm"
                :disabled="form.processing"
                class="px-3 py-1 bg-[#0D542B] hover:bg-[#08381c] text-white rounded text-xs font-bold cursor-pointer disabled:opacity-50 ml-2 shrink-0"
            >
                Simpan
            </button>
        </div>

        <!-- Konten 4 Sub-Tab Form Profil Sesuai Halaman /alumni -->
        <form @submit.prevent="submitForm">
            <!-- 1. Data Pribadi & Kontak -->
            <div v-show="activeProfileTab === 'pribadi'">
                <FormPribadi 
                    :form="form" 
                    :provinces="provinces" 
                    :kabupatens="kabupatens" 
                    :negaras="negaras" 
                />
            </div>

            <!-- 2. Data Akademik -->
            <div v-show="activeProfileTab === 'akademik'">
                <FormAkademik :form="form" />
            </div>

            <!-- 3. Pekerjaan & Karier -->
            <div v-show="activeProfileTab === 'karier'">
                <FormKarier 
                    :form="form" 
                    :provinces="provinces" 
                    :kabupatens="kabupatens" 
                    :negaras="negaras" 
                    :companies="companies" 
                    :alumniData="alumni" 
                    :refOptions="refOptions" 
                    :evaluasiAtasan="evaluasiAtasan" 
                />
            </div>

            <!-- 4. Data Orang Tua -->
            <div v-show="activeProfileTab === 'orangtua'">
                <FormOrangTua 
                    :form="form" 
                    :provinces="provinces" 
                    :kabupatens="kabupatens" 
                />
            </div>
        </form>
    </div>
</template>
