<!--
  Halaman: Master Data Wilayah & Upah Minimum Provinsi (UMP)
  File: resources/js/Pages/SuperAdmin/MasterWilayah/Index.vue
  Hak Akses: Super Administrator
-->
<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Swal from 'sweetalert2';
import Sidebar from '../Components/Sidebar.vue';

const props = defineProps({
    user: Object,
    active_tab: {
        type: String,
        default: 'provinsi',
    },
    stats: {
        type: Object,
        default: () => ({
            total_provinsi: 0,
            total_kabupaten: 0,
            total_ump_configured: 0,
            avg_ump: 0,
            min_ump: { besaran: 0, provinsi: '-' },
            max_ump: { besaran: 0, provinsi: '-' },
            selected_year: 2026,
        }),
    },
    available_years: {
        type: Array,
        default: () => [2026],
    },
    all_provinces: {
        type: Array,
        default: () => [],
    },
    provinces: {
        type: Array,
        default: () => [],
    },
    kabupatens: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    umps: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();

// State Tab & Filter
const currentTab = ref(props.active_tab || 'provinsi');
const search = ref(props.filters.search || '');
const selectedYear = ref(props.filters.tahun || 2026);
const selectedProvinsiId = ref(props.filters.propinsi_id || '');
const perPage = ref(props.filters.per_page || 15);

// Format Rupiah Helper
const formatRupiah = (val) => {
    if (val === null || val === undefined || isNaN(val)) return 'Rp 0';
    return 'Rp ' + Number(val).toLocaleString('id-ID');
};

// State Modal Provinsi
const showProvinsiModal = ref(false);
const isEditingProvinsi = ref(false);
const formProvinsi = ref({
    id: null,
    kode_provinsi: '',
    nama_provinsi: '',
    besaran_ump: '',
    tahun_ump: 2026,
    catatan_ump: '',
});

// State Modal Kabupaten
const showKabupatenModal = ref(false);
const isEditingKabupaten = ref(false);
const formKabupaten = ref({
    id: null,
    propinsi_id: '',
    kode_kabupaten: '',
    nama_kabupaten: '',
});

// State Modal UMP
const showUmpModal = ref(false);
const formUmp = ref({
    id: null,
    kode_provinsi: '',
    nama_provinsi_display: '',
    tahun: 2026,
    besaran: '',
    catatan: '',
});

// State Modal Salin UMP Antar Tahun
const showCopyYearModal = ref(false);
const formCopyYear = ref({
    source_year: 2026,
    target_year: 2027,
    percentage_increase: 0,
});

// Loading state
const isSubmitting = ref(false);

// Filter Handler
let searchDebounce = null;
const handleSearch = () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        applyFilters();
    }, 400);
};

const switchTab = (tab) => {
    currentTab.value = tab;
    search.value = '';
    applyFilters();
};

const applyFilters = () => {
    router.get('/superadmin/master-wilayah', {
        tab: currentTab.value,
        search: search.value || undefined,
        tahun: selectedYear.value != 2026 ? selectedYear.value : undefined,
        propinsi_id: currentTab.value === 'kabupaten' && selectedProvinsiId.value ? selectedProvinsiId.value : undefined,
        per_page: currentTab.value === 'kabupaten' && perPage.value != 15 ? perPage.value : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    search.value = '';
    selectedProvinsiId.value = '';
    selectedYear.value = 2026;
    perPage.value = 15;
    router.get('/superadmin/master-wilayah', { tab: currentTab.value }, { preserveState: true });
};

// ==========================================
// ACTIONS PROVINSI
// ==========================================
const openCreateProvinsi = () => {
    isEditingProvinsi.value = false;
    formProvinsi.value = {
        id: null,
        kode_provinsi: '',
        nama_provinsi: '',
        besaran_ump: '',
        tahun_ump: selectedYear.value,
        catatan_ump: '',
    };
    showProvinsiModal.value = true;
};

const openEditProvinsi = (prov) => {
    isEditingProvinsi.value = true;
    formProvinsi.value = {
        id: prov.id,
        kode_provinsi: prov.kode_provinsi,
        nama_provinsi: prov.nama_provinsi,
        besaran_ump: prov.ump ? prov.ump.besaran : '',
        tahun_ump: prov.ump ? prov.ump.tahun : selectedYear.value,
        catatan_ump: prov.ump ? prov.ump.catatan || '' : '',
    };
    showProvinsiModal.value = true;
};

const submitProvinsi = () => {
    if (!formProvinsi.value.kode_provinsi || !formProvinsi.value.nama_provinsi) {
        Swal.fire({
            icon: 'warning',
            title: 'Form Belum Lengkap',
            text: 'Kode Provinsi dan Nama Provinsi wajib diisi.',
            confirmButtonColor: '#0D542B',
        });
        return;
    }

    isSubmitting.value = true;
    if (isEditingProvinsi.value) {
        router.put(`/superadmin/master-wilayah/provinsi/${formProvinsi.value.id}`, {
            kode_provinsi: formProvinsi.value.kode_provinsi,
            nama_provinsi: formProvinsi.value.nama_provinsi,
        }, {
            onSuccess: () => {
                showProvinsiModal.value = false;
                isSubmitting.value = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Data provinsi berhasil diperbarui.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            },
            onError: (err) => {
                isSubmitting.value = false;
                const msg = Object.values(err)[0] || 'Gagal memperbarui data provinsi.';
                Swal.fire({ icon: 'error', title: 'Terjadi Kesalahan', text: msg, confirmButtonColor: '#0D542B' });
            },
        });
    } else {
        router.post('/superadmin/master-wilayah/provinsi', formProvinsi.value, {
            onSuccess: () => {
                showProvinsiModal.value = false;
                isSubmitting.value = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Provinsi baru berhasil ditambahkan.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            },
            onError: (err) => {
                isSubmitting.value = false;
                const msg = Object.values(err)[0] || 'Gagal menambahkan provinsi baru.';
                Swal.fire({ icon: 'error', title: 'Terjadi Kesalahan', text: msg, confirmButtonColor: '#0D542B' });
            },
        });
    }
};

const confirmDeleteProvinsi = (prov) => {
    Swal.fire({
        title: 'Hapus Provinsi?',
        html: `Apakah Anda yakin ingin menghapus data provinsi <b>${prov.nama_provinsi}</b> (Kode: <code>${prov.kode_provinsi}</code>)?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/superadmin/master-wilayah/provinsi/${prov.id}`, {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus',
                        text: 'Provinsi berhasil dihapus dari sistem.',
                        timer: 1800,
                        showConfirmButton: false,
                    });
                },
                onError: (err) => {
                    const msg = err.error || Object.values(err)[0] || 'Gagal menghapus provinsi.';
                    Swal.fire({ icon: 'error', title: 'Tidak Dapat Dihapus', text: msg, confirmButtonColor: '#0D542B' });
                },
            });
        }
    });
};

// ==========================================
// ACTIONS KABUPATEN
// ==========================================
const openCreateKabupaten = () => {
    isEditingKabupaten.value = false;
    formKabupaten.value = {
        id: null,
        propinsi_id: selectedProvinsiId.value || (props.all_provinces[0]?.id || ''),
        kode_kabupaten: '',
        nama_kabupaten: '',
    };
    showKabupatenModal.value = true;
};

const openEditKabupaten = (kab) => {
    isEditingKabupaten.value = true;
    formKabupaten.value = {
        id: kab.id,
        propinsi_id: kab.propinsi_id,
        kode_kabupaten: kab.kode_kabupaten,
        nama_kabupaten: kab.nama_kabupaten,
    };
    showKabupatenModal.value = true;
};

const submitKabupaten = () => {
    if (!formKabupaten.value.propinsi_id || !formKabupaten.value.kode_kabupaten || !formKabupaten.value.nama_kabupaten) {
        Swal.fire({
            icon: 'warning',
            title: 'Form Belum Lengkap',
            text: 'Provinsi, Kode Kabupaten, dan Nama Kabupaten wajib diisi.',
            confirmButtonColor: '#0D542B',
        });
        return;
    }

    isSubmitting.value = true;
    if (isEditingKabupaten.value) {
        router.put(`/superadmin/master-wilayah/kabupaten/${formKabupaten.value.id}`, formKabupaten.value, {
            onSuccess: () => {
                showKabupatenModal.value = false;
                isSubmitting.value = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Data kabupaten/kota berhasil diperbarui.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            },
            onError: (err) => {
                isSubmitting.value = false;
                const msg = Object.values(err)[0] || 'Gagal memperbarui kabupaten/kota.';
                Swal.fire({ icon: 'error', title: 'Terjadi Kesalahan', text: msg, confirmButtonColor: '#0D542B' });
            },
        });
    } else {
        router.post('/superadmin/master-wilayah/kabupaten', formKabupaten.value, {
            onSuccess: () => {
                showKabupatenModal.value = false;
                isSubmitting.value = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Kabupaten/Kota baru berhasil ditambahkan.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            },
            onError: (err) => {
                isSubmitting.value = false;
                const msg = Object.values(err)[0] || 'Gagal menambahkan kabupaten/kota.';
                Swal.fire({ icon: 'error', title: 'Terjadi Kesalahan', text: msg, confirmButtonColor: '#0D542B' });
            },
        });
    }
};

const confirmDeleteKabupaten = (kab) => {
    Swal.fire({
        title: 'Hapus Kabupaten/Kota?',
        html: `Apakah Anda yakin ingin menghapus data <b>${kab.nama_kabupaten}</b> (Kode: <code>${kab.kode_kabupaten}</code>)?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/superadmin/master-wilayah/kabupaten/${kab.id}`, {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus',
                        text: 'Kabupaten/Kota berhasil dihapus.',
                        timer: 1800,
                        showConfirmButton: false,
                    });
                },
                onError: (err) => {
                    const msg = err.error || Object.values(err)[0] || 'Gagal menghapus kabupaten/kota.';
                    Swal.fire({ icon: 'error', title: 'Tidak Dapat Dihapus', text: msg, confirmButtonColor: '#0D542B' });
                },
            });
        }
    });
};

// ==========================================
// ACTIONS UMP
// ==========================================
const openConfigureUmp = (prov) => {
    formUmp.value = {
        id: prov.ump ? prov.ump.id : null,
        kode_provinsi: prov.kode_provinsi,
        nama_provinsi_display: prov.nama_provinsi,
        tahun: selectedYear.value,
        besaran: prov.ump ? prov.ump.besaran : '',
        catatan: prov.ump ? prov.ump.catatan || '' : '',
    };
    showUmpModal.value = true;
};

const submitUmp = () => {
    if (!formUmp.value.kode_provinsi || !formUmp.value.besaran || !formUmp.value.tahun) {
        Swal.fire({
            icon: 'warning',
            title: 'Form Belum Lengkap',
            text: 'Provinsi, Tahun, dan Besaran UMP wajib diisi.',
            confirmButtonColor: '#0D542B',
        });
        return;
    }

    isSubmitting.value = true;
    router.post('/superadmin/master-wilayah/ump', {
        kode_provinsi: formUmp.value.kode_provinsi,
        tahun: formUmp.value.tahun,
        besaran: formUmp.value.besaran,
        catatan: formUmp.value.catatan,
    }, {
        onSuccess: () => {
            showUmpModal.value = false;
            isSubmitting.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Disimpan',
                text: 'Ketetapan UMP berhasil disimpan.',
                timer: 1800,
                showConfirmButton: false,
            });
        },
        onError: (err) => {
            isSubmitting.value = false;
            const msg = Object.values(err)[0] || 'Gagal menyimpan ketetapan UMP.';
            Swal.fire({ icon: 'error', title: 'Terjadi Kesalahan', text: msg, confirmButtonColor: '#0D542B' });
        },
    });
};

const confirmDeleteUmp = (umpItem) => {
    Swal.fire({
        title: 'Hapus Ketetapan UMP?',
        html: `Apakah Anda yakin ingin menghapus data UMP <b>${umpItem.nama_provinsi}</b> tahun <b>${umpItem.tahun}</b>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/superadmin/master-wilayah/ump/${umpItem.id}`, {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus',
                        text: 'Ketetapan UMP berhasil dihapus.',
                        timer: 1800,
                        showConfirmButton: false,
                    });
                },
                onError: (err) => {
                    const msg = Object.values(err)[0] || 'Gagal menghapus UMP.';
                    Swal.fire({ icon: 'error', title: 'Kesalahan', text: msg, confirmButtonColor: '#0D542B' });
                },
            });
        }
    });
};

// Salin Antar Tahun
const openCopyYearModal = () => {
    formCopyYear.value = {
        source_year: selectedYear.value,
        target_year: Number(selectedYear.value) + 1,
        percentage_increase: 0,
    };
    showCopyYearModal.value = true;
};

const submitCopyYear = () => {
    if (formCopyYear.value.source_year === formCopyYear.value.target_year) {
        Swal.fire({
            icon: 'warning',
            title: 'Tahun Sama',
            text: 'Tahun target harus berbeda dengan tahun sumber.',
            confirmButtonColor: '#0D542B',
        });
        return;
    }

    isSubmitting.value = true;
    router.post('/superadmin/master-wilayah/ump/copy-year', formCopyYear.value, {
        onSuccess: () => {
            showCopyYearModal.value = false;
            isSubmitting.value = false;
            selectedYear.value = formCopyYear.value.target_year;
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Disalin',
                text: `Ketetapan UMP tahun ${formCopyYear.value.target_year} berhasil digenerate.`,
                timer: 2000,
                showConfirmButton: false,
            });
        },
        onError: (err) => {
            isSubmitting.value = false;
            const msg = Object.values(err)[0] || 'Gagal menyalin data UMP.';
            Swal.fire({ icon: 'error', title: 'Terjadi Kesalahan', text: msg, confirmButtonColor: '#0D542B' });
        },
    });
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col font-sans text-slate-800">
        <Head title="Master Wilayah & UMP - Super Admin" />

        <!-- SIDEBAR TERPADU SUPERADMIN -->
        <Sidebar :user="user" />

        <!-- KONTEN UTAMA HALAMAN -->
        <main class="flex-1 lg:pl-72 flex flex-col min-h-screen">
            <!-- TOPBAR / HEADER HALAMAN -->
            <header class="bg-white border-b border-slate-200 px-6 py-5 sticky top-0 z-30 shadow-2xs">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
                            <span>Super Admin</span>
                            <span>/</span>
                            <span>Master Data</span>
                            <span>/</span>
                            <span class="text-slate-700 font-semibold">Wilayah & UMP</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                                Master Wilayah & Standar UMP
                            </h1>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                38 Provinsi &bull; 515 Kabupaten/Kota
                            </span>
                        </div>
                    </div>

                    <!-- TOMBOL AKSI CEPAT DI HEADER -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <button
                            v-if="currentTab === 'provinsi'"
                            type="button"
                            @click="openCreateProvinsi"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#0D542B] hover:bg-[#0a4222] text-white rounded-lg text-xs font-semibold shadow-2xs transition-colors cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Tambah Provinsi</span>
                        </button>

                        <button
                            v-if="currentTab === 'kabupaten'"
                            type="button"
                            @click="openCreateKabupaten"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#0D542B] hover:bg-[#0a4222] text-white rounded-lg text-xs font-semibold shadow-2xs transition-colors cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Tambah Kabupaten/Kota</span>
                        </button>

                        <button
                            type="button"
                            @click="openCopyYearModal"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-semibold shadow-2xs transition-colors cursor-pointer"
                            title="Salin ketetapan UMP dari satu tahun ke tahun lainnya"
                        >
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                            </svg>
                            <span>Salin UMP Tahun</span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- ISI KONTEN UTAMA -->
            <div class="p-6 space-y-6">
                <!-- KARTU STATISTIK RINGKASAN -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Kartu 1: Total Provinsi -->
                    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-slate-500">Total Provinsi</span>
                            <span class="w-7 h-7 rounded-md bg-blue-50 text-blue-700 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-bold text-slate-900">{{ stats.total_provinsi }}</span>
                            <span class="text-xs text-slate-500">Provinsi</span>
                        </div>
                        <span class="text-[11px] text-emerald-600 font-medium block mt-1">
                            100% Tercakup Sistem
                        </span>
                    </div>

                    <!-- Kartu 2: Total Kabupaten/Kota -->
                    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-slate-500">Total Kabupaten/Kota</span>
                            <span class="w-7 h-7 rounded-md bg-purple-50 text-purple-700 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-bold text-slate-900">{{ stats.total_kabupaten }}</span>
                            <span class="text-xs text-slate-500">Daerah</span>
                        </div>
                        <span class="text-[11px] text-slate-500 font-medium block mt-1">
                            Terhubung ke masing-masing provinsi
                        </span>
                    </div>

                    <!-- Kartu 3: Regulasi UMP Tahun Ini -->
                    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-slate-500">UMP Tahun {{ stats.selected_year }}</span>
                            <span class="w-7 h-7 rounded-md bg-emerald-50 text-[#0D542B] flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl font-bold text-[#0D542B]">{{ stats.total_ump_configured }}</span>
                            <span class="text-xs text-slate-500">/ {{ stats.total_provinsi }} Provinsi</span>
                        </div>
                        <span class="text-[11px] text-slate-500 font-medium block mt-1 truncate">
                            Rata-rata: {{ formatRupiah(stats.avg_ump) }}
                        </span>
                    </div>

                    <!-- Kartu 4: Rentang UMP Nasional -->
                    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-slate-500">Rentang UMP Terendah & Tertinggi</span>
                            <span class="w-7 h-7 rounded-md bg-amber-50 text-amber-700 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-2 space-y-0.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500 truncate max-w-[110px]" :title="stats.min_ump.provinsi">Min ({{ stats.min_ump.provinsi }}):</span>
                                <span class="font-bold text-slate-800">{{ formatRupiah(stats.min_ump.besaran) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500 truncate max-w-[110px]" :title="stats.max_ump.provinsi">Max ({{ stats.max_ump.provinsi }}):</span>
                                <span class="font-bold text-[#0D542B]">{{ formatRupiah(stats.max_ump.besaran) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TABS NAVIGASI UTAMA -->
                <div class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                    <div class="border-b border-slate-200 px-6 pt-3 flex items-center justify-between flex-wrap gap-4 bg-slate-50/50">
                        <!-- Pilihan Tab -->
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="switchTab('provinsi')"
                                class="px-4 py-2.5 text-xs font-bold border-b-2 transition-all cursor-pointer flex items-center gap-2"
                                :class="[
                                    currentTab === 'provinsi'
                                        ? 'border-[#0D542B] text-[#0D542B] bg-white rounded-t-lg'
                                        : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Provinsi & Standar UMP</span>
                                <span class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 font-mono text-slate-600">
                                    {{ stats.total_provinsi }}
                                </span>
                            </button>

                            <button
                                type="button"
                                @click="switchTab('kabupaten')"
                                class="px-4 py-2.5 text-xs font-bold border-b-2 transition-all cursor-pointer flex items-center gap-2"
                                :class="[
                                    currentTab === 'kabupaten'
                                        ? 'border-[#0D542B] text-[#0D542B] bg-white rounded-t-lg'
                                        : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span>Kabupaten & Kota</span>
                                <span class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 font-mono text-slate-600">
                                    {{ stats.total_kabupaten }}
                                </span>
                            </button>

                            <button
                                type="button"
                                @click="switchTab('ump')"
                                class="px-4 py-2.5 text-xs font-bold border-b-2 transition-all cursor-pointer flex items-center gap-2"
                                :class="[
                                    currentTab === 'ump'
                                        ? 'border-[#0D542B] text-[#0D542B] bg-white rounded-t-lg'
                                        : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                <span>Matriks Regulasi UMP</span>
                                <span class="px-1.5 py-0.5 rounded text-[10px] bg-emerald-100 font-mono text-emerald-800 font-bold">
                                    Tahun {{ selectedYear }}
                                </span>
                            </button>
                        </div>

                        <!-- Pilih Tahun Regulasi UMP -->
                        <div class="flex items-center gap-2 pb-2">
                            <span class="text-xs text-slate-500 font-medium">Tahun UMP:</span>
                            <select
                                v-model="selectedYear"
                                @change="applyFilters"
                                class="text-xs font-semibold bg-white border border-slate-300 rounded-md px-2.5 py-1.5 text-slate-800 focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B]"
                            >
                                <option v-for="yr in available_years" :key="yr" :value="yr">
                                    Tahun {{ yr }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- BAR FILTER & PENCARIAN -->
                    <div class="p-4 border-b border-slate-200 bg-white flex flex-col md:flex-row md:items-center justify-between gap-3">
                        <div class="flex items-center gap-3 flex-1 flex-wrap">
                            <!-- Input Search -->
                            <div class="relative w-full sm:w-72">
                                <input
                                    type="text"
                                    v-model="search"
                                    @input="handleSearch"
                                    :placeholder="currentTab === 'provinsi' ? 'Cari nama / kode provinsi...' : 'Cari nama / kode kabupaten...'"
                                    class="w-full text-xs pl-8 pr-3 py-2 border border-slate-200 rounded-lg bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B] transition-colors"
                                />
                                <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>

                            <!-- Filter Dropdown Provinsi (Khusus Tab Kabupaten) -->
                            <div v-if="currentTab === 'kabupaten'" class="w-full sm:w-60">
                                <select
                                    v-model="selectedProvinsiId"
                                    @change="applyFilters"
                                    class="w-full text-xs px-3 py-2 border border-slate-200 rounded-lg bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B]"
                                >
                                    <option value="">Semua Provinsi (515 Daerah)</option>
                                    <option v-for="p in all_provinces" :key="p.id" :value="p.id">
                                        [{{ p.kode_provinsi }}] {{ p.nama_provinsi }}
                                    </option>
                                </select>
                            </div>

                            <!-- Reset Filter -->
                            <button
                                v-if="search || selectedProvinsiId || selectedYear != 2026"
                                type="button"
                                @click="resetFilters"
                                class="text-xs text-rose-600 hover:text-rose-800 font-medium px-2 py-1 rounded hover:bg-rose-50 transition-colors cursor-pointer"
                            >
                                Reset Filter
                            </button>
                        </div>

                        <!-- Per Page (Khusus Tab Kabupaten) -->
                        <div v-if="currentTab === 'kabupaten'" class="flex items-center gap-2 self-end md:self-auto">
                            <span class="text-xs text-slate-400">Tampilkan:</span>
                            <select
                                v-model="perPage"
                                @change="applyFilters"
                                class="text-xs bg-white border border-slate-200 rounded-md px-2 py-1 text-slate-700"
                            >
                                <option :value="15">15</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>
                    </div>

                    <!-- TAB 1: TABEL PROVINSI & UMP -->
                    <div v-if="currentTab === 'provinsi'" class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3 text-center w-12">No</th>
                                    <th class="px-4 py-3 w-32">Kode Provinsi</th>
                                    <th class="px-4 py-3">Nama Provinsi</th>
                                    <th class="px-4 py-3 text-center w-36">Total Kab/Kota</th>
                                    <th class="px-4 py-3 text-right w-44">Standar UMP {{ selectedYear }}</th>
                                    <th class="px-4 py-3 w-48">Catatan Regulasi</th>
                                    <th class="px-4 py-3 text-center w-36">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(prov, idx) in provinces"
                                    :key="prov.id"
                                    class="hover:bg-slate-50/80 transition-colors"
                                >
                                    <td class="px-4 py-3 text-center text-slate-400 font-medium">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="px-4 py-3 font-mono font-bold text-[#0D542B]">
                                        {{ prov.kode_provinsi }}
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-slate-900">
                                        {{ prov.nama_provinsi }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700">
                                            {{ prov.total_kabupaten }} Daerah
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div v-if="prov.ump" class="font-bold text-slate-900">
                                            {{ formatRupiah(prov.ump.besaran) }}
                                        </div>
                                        <div v-else class="text-rose-600 font-medium text-[11px]">
                                            Belum Diatur
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 truncate max-w-xs" :title="prov.ump?.catatan || '-'">
                                        {{ prov.ump?.catatan || '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <!-- Tombol Atur UMP -->
                                            <button
                                                type="button"
                                                @click="openConfigureUmp(prov)"
                                                class="px-2 py-1 text-[11px] font-semibold rounded bg-emerald-50 text-[#0D542B] hover:bg-emerald-100 border border-emerald-200 transition-colors cursor-pointer"
                                                title="Atur Besaran UMP"
                                            >
                                                Atur UMP
                                            </button>

                                            <!-- Tombol Edit Provinsi -->
                                            <button
                                                type="button"
                                                @click="openEditProvinsi(prov)"
                                                class="p-1 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors cursor-pointer"
                                                title="Edit Provinsi"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>

                                            <!-- Tombol Hapus Provinsi -->
                                            <button
                                                type="button"
                                                @click="confirmDeleteProvinsi(prov)"
                                                class="p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition-colors cursor-pointer"
                                                title="Hapus Provinsi"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="provinces.length === 0">
                                    <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                                        Tidak ada data provinsi yang cocok dengan pencarian.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- TAB 2: TABEL KABUPATEN & KOTA -->
                    <div v-if="currentTab === 'kabupaten'">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3 text-center w-12">No</th>
                                        <th class="px-4 py-3 w-36">Kode Kabupaten</th>
                                        <th class="px-4 py-3">Nama Kabupaten / Kota</th>
                                        <th class="px-4 py-3 w-64">Provinsi Terkait</th>
                                        <th class="px-4 py-3 text-right w-44">Standar UMP Wilayah</th>
                                        <th class="px-4 py-3 text-center w-28">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr
                                        v-for="(kab, idx) in kabupatens.data"
                                        :key="kab.id"
                                        class="hover:bg-slate-50/80 transition-colors"
                                    >
                                        <td class="px-4 py-3 text-center text-slate-400 font-medium">
                                            {{ ((kabupatens.current_page - 1) * kabupatens.per_page) + idx + 1 }}
                                        </td>
                                        <td class="px-4 py-3 font-mono font-bold text-slate-900">
                                            {{ kab.kode_kabupaten || '-' }}
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-slate-900">
                                            {{ kab.nama_kabupaten }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">
                                            <span class="font-mono text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 mr-1.5">
                                                {{ kab.kode_provinsi }}
                                            </span>
                                            <span>{{ kab.nama_provinsi }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium text-slate-800">
                                            <span v-if="kab.ump_provinsi" class="font-bold text-[#0D542B]">
                                                {{ formatRupiah(kab.ump_provinsi) }}
                                            </span>
                                            <span v-else class="text-slate-400 text-[11px]">-</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <!-- Tombol Edit Kabupaten -->
                                                <button
                                                    type="button"
                                                    @click="openEditKabupaten(kab)"
                                                    class="p-1 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors cursor-pointer"
                                                    title="Edit Kabupaten/Kota"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </button>

                                                <!-- Tombol Hapus Kabupaten -->
                                                <button
                                                    type="button"
                                                    @click="confirmDeleteKabupaten(kab)"
                                                    class="p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition-colors cursor-pointer"
                                                    title="Hapus Kabupaten/Kota"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="kabupatens.data.length === 0">
                                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                                            Tidak ada data kabupaten/kota yang sesuai kriteria pencarian.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- PAGINASI KABUPATEN -->
                        <div v-if="kabupatens.links && kabupatens.links.length > 3" class="px-6 py-4 border-t border-slate-200 bg-white flex flex-col sm:flex-row items-center justify-between gap-3">
                            <span class="text-xs text-slate-500">
                                Menampilkan {{ kabupatens.from || 0 }} sampai {{ kabupatens.to || 0 }} dari total {{ kabupatens.total || 0 }} data
                            </span>
                            <div class="flex items-center gap-1 flex-wrap">
                                <button
                                    v-for="(link, i) in kabupatens.links"
                                    :key="i"
                                    type="button"
                                    @click="link.url ? router.get(link.url, {}, { preserveState: true }) : null"
                                    :disabled="!link.url || link.active"
                                    class="px-2.5 py-1 text-xs rounded border transition-colors cursor-pointer disabled:cursor-not-allowed"
                                    :class="[
                                        link.active
                                            ? 'bg-[#0D542B] text-white border-[#0D542B] font-bold'
                                            : link.url
                                                ? 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
                                                : 'bg-slate-100 text-slate-300 border-slate-200'
                                    ]"
                                    v-html="link.label"
                                ></button>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: MATRIKS REGULASI UMP -->
                    <div v-if="currentTab === 'ump'" class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3 text-center w-12">No</th>
                                    <th class="px-4 py-3 w-32">Kode Provinsi</th>
                                    <th class="px-4 py-3">Nama Provinsi</th>
                                    <th class="px-4 py-3 text-center w-28">Tahun</th>
                                    <th class="px-4 py-3 text-right w-44">Besaran UMP</th>
                                    <th class="px-4 py-3">Catatan / Dasar SK</th>
                                    <th class="px-4 py-3 w-36">Diperbarui</th>
                                    <th class="px-4 py-3 text-center w-28">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(u, idx) in umps"
                                    :key="u.id"
                                    class="hover:bg-slate-50/80 transition-colors"
                                >
                                    <td class="px-4 py-3 text-center text-slate-400 font-medium">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="px-4 py-3 font-mono font-bold text-[#0D542B]">
                                        {{ u.kode_provinsi }}
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-slate-900">
                                        {{ u.nama_provinsi }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-slate-800">
                                        {{ u.tahun }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-slate-900">
                                        {{ formatRupiah(u.besaran) }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 truncate max-w-xs" :title="u.catatan || '-'">
                                        {{ u.catatan || '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-400 text-[11px]">
                                        {{ u.updated_at }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="openConfigureUmp({ kode_provinsi: u.kode_provinsi, nama_provinsi: u.nama_provinsi, ump: u })"
                                                class="p-1 text-slate-400 hover:text-emerald-700 hover:bg-emerald-50 rounded transition-colors cursor-pointer"
                                                title="Edit Nilai UMP"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>

                                            <button
                                                type="button"
                                                @click="confirmDeleteUmp(u)"
                                                class="p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition-colors cursor-pointer"
                                                title="Hapus UMP"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="umps.length === 0">
                                    <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                                        Belum ada rekaman UMP untuk tahun {{ selectedYear }}.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <!-- ======================================================== -->
        <!-- MODAL 1: TAMBAH / EDIT PROVINSI                          -->
        <!-- ======================================================== -->
        <div
            v-if="showProvinsiModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-black/40 flex items-center justify-center p-4"
        >
            <div class="bg-white rounded-lg border border-slate-200 shadow-xl max-w-md w-full overflow-hidden transition-all">
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                    <h3 class="text-sm font-bold text-slate-900">
                        {{ isEditingProvinsi ? 'Edit Data Provinsi' : 'Tambah Provinsi Baru' }}
                    </h3>
                    <button
                        type="button"
                        @click="showProvinsiModal = false"
                        class="text-slate-400 hover:text-slate-600 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitProvinsi" class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Kode Provinsi <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="formProvinsi.kode_provinsi"
                            placeholder="Contoh: 34"
                            class="w-full text-xs px-3 py-2 border border-slate-200 rounded-lg bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B] font-mono"
                            required
                        />
                        <span class="text-[11px] text-slate-400 mt-0.5 block">Kode standar wilayah nasional (BPS/Kemendagri)</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Nama Provinsi <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="formProvinsi.nama_provinsi"
                            placeholder="Contoh: DAERAH ISTIMEWA YOGYAKARTA"
                            class="w-full text-xs px-3 py-2 border border-slate-200 rounded-lg bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B]"
                            required
                        />
                    </div>

                    <!-- Besaran UMP Awal (Khusus Tambah Baru) -->
                    <div v-if="!isEditingProvinsi" class="pt-2 border-t border-slate-100 space-y-3">
                        <span class="text-xs font-bold text-slate-800 block">Ketetapan UMP Awal (Opsional)</span>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-medium text-slate-600 mb-1">Tahun UMP</label>
                                <input
                                    type="number"
                                    v-model="formProvinsi.tahun_ump"
                                    class="w-full text-xs px-2.5 py-1.5 border border-slate-200 rounded-md"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-slate-600 mb-1">Besaran (Rp)</label>
                                <input
                                    type="number"
                                    v-model="formProvinsi.besaran_ump"
                                    placeholder="Contoh: 2417495"
                                    class="w-full text-xs px-2.5 py-1.5 border border-slate-200 rounded-md"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="showProvinsiModal = false"
                            class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-md transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="px-4 py-1.5 text-xs font-bold bg-[#0D542B] text-white rounded-md hover:bg-[#0a4222] transition-colors cursor-pointer disabled:opacity-50"
                        >
                            {{ isSubmitting ? 'Menyimpan...' : (isEditingProvinsi ? 'Perbarui Provinsi' : 'Simpan Provinsi') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- MODAL 2: TAMBAH / EDIT KABUPATEN                         -->
        <!-- ======================================================== -->
        <div
            v-if="showKabupatenModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-black/40 flex items-center justify-center p-4"
        >
            <div class="bg-white rounded-lg border border-slate-200 shadow-xl max-w-md w-full overflow-hidden transition-all">
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                    <h3 class="text-sm font-bold text-slate-900">
                        {{ isEditingKabupaten ? 'Edit Kabupaten/Kota' : 'Tambah Kabupaten/Kota Baru' }}
                    </h3>
                    <button
                        type="button"
                        @click="showKabupatenModal = false"
                        class="text-slate-400 hover:text-slate-600 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitKabupaten" class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Provinsi Induk <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="formKabupaten.propinsi_id"
                            class="w-full text-xs px-3 py-2 border border-slate-200 rounded-lg bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B]"
                            required
                        >
                            <option value="" disabled>-- Pilih Provinsi --</option>
                            <option v-for="p in all_provinces" :key="p.id" :value="p.id">
                                [{{ p.kode_provinsi }}] {{ p.nama_provinsi }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Kode Kabupaten/Kota <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="formKabupaten.kode_kabupaten"
                            placeholder="Contoh: 34.71 atau 3404"
                            class="w-full text-xs px-3 py-2 border border-slate-200 rounded-lg bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B] font-mono"
                            required
                        />
                        <span class="text-[11px] text-slate-400 mt-0.5 block">Kode unik kabupaten/kota</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Nama Kabupaten / Kota <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="formKabupaten.nama_kabupaten"
                            placeholder="Contoh: KOTA YOGYAKARTA atau KAB. SLEMAN"
                            class="w-full text-xs px-3 py-2 border border-slate-200 rounded-lg bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B]"
                            required
                        />
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="showKabupatenModal = false"
                            class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-md transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="px-4 py-1.5 text-xs font-bold bg-[#0D542B] text-white rounded-md hover:bg-[#0a4222] transition-colors cursor-pointer disabled:opacity-50"
                        >
                            {{ isSubmitting ? 'Menyimpan...' : (isEditingKabupaten ? 'Perbarui Kabupaten' : 'Simpan Kabupaten') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- MODAL 3: ATUR BESARAN UMP PER DAERAH                     -->
        <!-- ======================================================== -->
        <div
            v-if="showUmpModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-black/40 flex items-center justify-center p-4"
        >
            <div class="bg-white rounded-lg border border-slate-200 shadow-xl max-w-md w-full overflow-hidden transition-all">
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">
                            Atur Standar UMP Daerah
                        </h3>
                        <span class="text-xs text-slate-500 font-semibold">
                            {{ formUmp.nama_provinsi_display }} (Kode: {{ formUmp.kode_provinsi }})
                        </span>
                    </div>
                    <button
                        type="button"
                        @click="showUmpModal = false"
                        class="text-slate-400 hover:text-slate-600 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitUmp" class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Tahun Ketetapan <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="number"
                            v-model="formUmp.tahun"
                            class="w-full text-xs px-3 py-2 border border-slate-200 rounded-lg bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B] font-bold"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Besaran UMP (Nominal Rupiah) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-xs font-bold text-slate-400">Rp</span>
                            <input
                                type="number"
                                v-model="formUmp.besaran"
                                placeholder="Contoh: 2417495"
                                class="w-full text-xs pl-9 pr-3 py-2 border border-slate-200 rounded-lg bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B] font-mono font-bold text-slate-900"
                                required
                            />
                        </div>
                        <div v-if="formUmp.besaran" class="mt-1 text-xs text-emerald-700 font-bold bg-emerald-50 px-2 py-1 rounded border border-emerald-200">
                            Pratinjau: {{ formatRupiah(formUmp.besaran) }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Catatan / Dasar Ketetapan SK Gubernur
                        </label>
                        <input
                            type="text"
                            v-model="formUmp.catatan"
                            placeholder="Contoh: SK Gubernur DIY No. 402/2025"
                            class="w-full text-xs px-3 py-2 border border-slate-200 rounded-lg bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B]"
                        />
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="showUmpModal = false"
                            class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-md transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="px-4 py-1.5 text-xs font-bold bg-[#0D542B] text-white rounded-md hover:bg-[#0a4222] transition-colors cursor-pointer disabled:opacity-50"
                        >
                            {{ isSubmitting ? 'Menyimpan...' : 'Simpan Ketetapan UMP' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- MODAL 4: SALIN UMP ANTAR TAHUN                           -->
        <!-- ======================================================== -->
        <div
            v-if="showCopyYearModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-black/40 flex items-center justify-center p-4"
        >
            <div class="bg-white rounded-lg border border-slate-200 shadow-xl max-w-md w-full overflow-hidden transition-all">
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                    <h3 class="text-sm font-bold text-slate-900">
                        Salin Data UMP Antar Tahun
                    </h3>
                    <button
                        type="button"
                        @click="showCopyYearModal = false"
                        class="text-slate-400 hover:text-slate-600 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitCopyYear" class="p-5 space-y-4">
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Fitur ini akan menyalin seluruh standar UMP dari tahun sumber ke tahun target sekaligus secara otomatis.
                    </p>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Tahun Sumber <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="number"
                                v-model="formCopyYear.source_year"
                                class="w-full text-xs px-3 py-2 border border-slate-200 rounded-lg bg-slate-50"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Tahun Target <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="number"
                                v-model="formCopyYear.target_year"
                                class="w-full text-xs px-3 py-2 border border-slate-200 rounded-lg bg-slate-50 font-bold text-[#0D542B]"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Penyesuaian Kenaikan Rata-rata (%)
                        </label>
                        <div class="relative">
                            <input
                                type="number"
                                step="0.1"
                                v-model="formCopyYear.percentage_increase"
                                placeholder="0"
                                class="w-full text-xs pl-3 pr-8 py-2 border border-slate-200 rounded-lg bg-slate-50"
                            />
                            <span class="absolute right-3 top-2 text-xs font-bold text-slate-400">%</span>
                        </div>
                        <span class="text-[11px] text-slate-400 mt-0.5 block">
                            Isi 0 jika nominal sama persis, atau isi persentase kenaikan (contoh: 5.5).
                        </span>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="showCopyYearModal = false"
                            class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-100 rounded-md transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="px-4 py-1.5 text-xs font-bold bg-[#0D542B] text-white rounded-md hover:bg-[#0a4222] transition-colors cursor-pointer disabled:opacity-50"
                        >
                            {{ isSubmitting ? 'Memproses...' : 'Mulai Salin Data' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
