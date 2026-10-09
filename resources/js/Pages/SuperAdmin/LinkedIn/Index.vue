<!--
  Halaman Direktori & Sinkronisasi LinkedIn Alumni
  File: resources/js/Pages/SuperAdmin/LinkedIn/Index.vue

  Mendukung Akses Terpadu Multi-Peran:
  1. Super Admin   : Akses lintas universitas (seluruh fakultas & prodi)
  2. Admin Fakultas: Akses terikat pada prodi-prodi di fakultas yang dinaungi
  3. Admin Prodi   : Akses khusus program studi yang bersangkutan

  Fitur Utama:
  - Tampilan selaras & serasi dengan halaman Direktori Alumni (/superadmin/alumni)
  - Solid Hijau UKDW (#0D542B) dan Kuning UKDW (#FDC700)
  - Filter lengkap: Pencarian Nama/NIM, Tahun Kelulusan, Target Periode, Semester, Status Sinkronisasi, dan Prodi
  - Modal Peninjauan (Review Approval) bersih tanpa emoticon anak kecil (ikon profesional SVG)
  - Sinkronisasi instan per-alumni (in-place) & sinkronisasi massal (bulk)
  - Modal histori snapshot lengkap hasil sinkronisasi
-->
<script setup>
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import Swal from 'sweetalert2';

// Import komponen Sidebar sesuai peran masing-masing
import SidebarSuperAdmin from '../Components/Sidebar.vue';
import SidebarFakultas from '../../AdminFakultas/Components/Sidebar.vue';
import SidebarProdi from '../../AdminProdi/Components/Sidebar.vue';

const props = defineProps({
    alumnis: {
        type: Array,
        default: () => [],
    },
    daftarTahun: {
        type: Array,
        default: () => [],
    },
    tahunTerpilih: {
        type: String,
        default: '',
    },
    daftarTarget: {
        type: Array,
        default: () => [],
    },
    prodis: {
        type: Array,
        default: () => [],
    },
    provider: {
        type: String,
        default: 'official',
    },
    targetAlumniId: {
        type: [Number, String],
        default: null,
    },
    role: {
        type: String,
        default: 'superadmin',
    },
    baseRoute: {
        type: String,
        default: '/superadmin/linkedin-sync',
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({
            total_alumni: 0,
            dengan_linkedin: 0,
            berhasil_sinkron: 0,
            gagal_sinkron: 0,
            dilewati: 0,
            pending_review: 0,
            belum_sinkron: 0,
            persentase_sinkron: 0,
        }),
    },
});

// =========================================================================
// STATE FILTER & PENCARIAN (SELARAS DENGAN HALAMAN DATA ALUMNI)
// =========================================================================
const defaultTahun = props.filters.tahun || props.tahunTerpilih || props.daftarTahun[0] || '';
const search = ref(props.filters.search || '');
const selectedTahun = ref(defaultTahun);
const selectedSemester = ref(props.filters.semester || 'all');
const selectedTarget = ref(props.filters.target || 'all');
const selectedStatus = ref(props.filters.status || 'all');
const selectedProdiId = ref(props.filters.prodi_id || 'all');
const highlightedAlumniId = ref(props.targetAlumniId ? Number(props.targetAlumniId) : null);

// Data Alumni Lokal (Reaktif untuk update status sinkronisasi tanpa reload halaman)
const alumniList = ref(props.alumnis.map(item => ({
    ...item,
    isSyncing: false,
})));

// Metrik Statistik Lokal
const localStats = ref({ ...props.stats });

// Sinkronkan data reaktif lokal jika server mengembalikan props baru (misal setelah filter tahun/prodi diterapkan)
watch(() => props.alumnis, (newAlumnis) => {
    alumniList.value = (newAlumnis || []).map(item => ({
        ...item,
        isSyncing: false,
    }));
}, { deep: true });

watch(() => props.stats, (newStats) => {
    localStats.value = { ...newStats };
}, { deep: true });

watch(() => props.filters, (newFilters) => {
    if (newFilters) {
        if (newFilters.tahun !== undefined) {
            selectedTahun.value = newFilters.tahun;
        }
        if (newFilters.semester !== undefined) {
            selectedSemester.value = newFilters.semester || 'all';
        }
        if (newFilters.target !== undefined) {
            selectedTarget.value = newFilters.target || 'all';
        }
        if (newFilters.status !== undefined) {
            selectedStatus.value = newFilters.status || 'all';
        }
        if (newFilters.prodi_id !== undefined && props.role !== 'admin_prodi') {
            selectedProdiId.value = newFilters.prodi_id || 'all';
        }
    }
}, { deep: true });

// State Bulk Sync
const isBulkSyncing = ref(false);
const bulkProgress = ref({ current: 0, total: 0, percentage: 0 });

// State Modal Peninjauan (Review Approval Apify)
const isReviewModalOpen = ref(false);
const isReviewLoading = ref(false);
const isActionLoading = ref(false);
const activeReview = ref(null);
const activeAlumni = ref(null);

// Form Approval Bersih (Bisa disesuaikan oleh admin sebelum disimpan)
const approveForm = ref({
    tipe_pekerjaan: 'pekerja', // 'pekerja' | 'wirausaha'
    posisi_jabatan: '',
    posisi_wiraswasta: '',
    nama_perusahaan: '',
    sync_foto: true,
});

// State Modal Histori Sinkronisasi
const isHistoryModalOpen = ref(false);
const isHistoryLoading = ref(false);
const historyList = ref([]);
const historyAlumni = ref(null);

// Pagination Lokal ala DataTables
const perPage = ref(10);
const currentPage = ref(1);

// Filter data alumni di frontend berdasarkan input pencarian jika ada
const filteredAlumni = computed(() => {
    let result = alumniList.value;

    if (search.value && search.value.trim()) {
        const query = search.value.toLowerCase().trim();
        result = result.filter(item =>
            (item.nim && item.nim.toLowerCase().includes(query)) ||
            (item.nama && item.nama.toLowerCase().includes(query)) ||
            (item.linkedin_username && item.linkedin_username.toLowerCase().includes(query)) ||
            (item.linkedin_url && item.linkedin_url.toLowerCase().includes(query)) ||
            (item.perusahaan && item.perusahaan.toLowerCase().includes(query)) ||
            (item.posisi && item.posisi.toLowerCase().includes(query))
        );
    }

    return result;
});

const totalPages = computed(() => {
    return Math.ceil(filteredAlumni.value.length / perPage.value) || 1;
});

const paginatedAlumni = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredAlumni.value.slice(start, start + perPage.value);
});

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

// =========================================================================
// TERAPKAN FILTER KE SERVER (SINKRON KE URL)
// =========================================================================
let searchDebounceTimeout = null;

const applyFilters = () => {
    currentPage.value = 1;
    highlightedAlumniId.value = null;

    router.get(props.baseRoute, {
        search: search.value || undefined,
        tahun: selectedTahun.value || undefined,
        semester: selectedSemester.value !== 'all' ? selectedSemester.value : undefined,
        target: selectedTarget.value !== 'all' ? selectedTarget.value : undefined,
        status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
        prodi_id: (props.role === 'admin_prodi' ? undefined : (selectedProdiId.value !== 'all' ? selectedProdiId.value : undefined)),
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const handleSearchInput = () => {
    clearTimeout(searchDebounceTimeout);
    searchDebounceTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
};

const resetFilters = () => {
    search.value = '';
    selectedTahun.value = props.daftarTahun[0] || '';
    selectedSemester.value = 'all';
    selectedTarget.value = 'all';
    selectedStatus.value = 'all';
    selectedProdiId.value = props.role === 'admin_prodi' ? (props.filters.prodi_id || 'all') : 'all';
    currentPage.value = 1;

    router.get(props.baseRoute, {
        tahun: selectedTahun.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Auto scroll ke alumni tertentu jika dialihkan dari detail alumni
onMounted(() => {
    if (highlightedAlumniId.value) {
        const targetIndex = filteredAlumni.value.findIndex(a => a.id === highlightedAlumniId.value);
        if (targetIndex !== -1) {
            currentPage.value = Math.floor(targetIndex / perPage.value) + 1;
        }

        nextTick(() => {
            const el = document.getElementById(`alumni-row-${highlightedAlumniId.value}`);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }
});

// Token CSRF untuk request fetch
const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
};

const showToast = (icon, title) => {
    Swal.fire({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        icon,
        title,
    });
};

// Hitung ulang metrik lokal setelah sinkronisasi/approval
const recalculateLocalStats = () => {
    let dengan = 0;
    let berhasil = 0;
    let gagal = 0;
    let pending = 0;

    alumniList.value.forEach(item => {
        if (item.linkedin_url || item.linkedin_username) dengan++;
        if (item.status_sync === 'Approved' || item.status_sync === 'Berhasil') berhasil++;
        if (item.status_sync === 'Rejected' || item.status_sync === 'Gagal') gagal++;
        if (item.status_sync === 'Pending Review') pending++;
    });

    localStats.value.dengan_linkedin = dengan;
    localStats.value.berhasil_sinkron = berhasil;
    localStats.value.gagal_sinkron = gagal;
    localStats.value.pending_review = pending;
    localStats.value.persentase_sinkron = localStats.value.total_alumni > 0
        ? Math.round((berhasil / localStats.value.total_alumni) * 100)
        : 0;
};

// =========================================================================
// SINKRONISASI INDIVIDUAL (ASYNC TANPA RELOAD HALAMAN)
// =========================================================================
const syncSingle = async (alumni) => {
    if (alumni.isSyncing) return;

    if (props.provider === 'apify') {
        if (!alumni.linkedin_url) {
            showToast('warning', 'URL LinkedIn belum diisi pada profil alumni ini.');
            return;
        }
    } else {
        if (!alumni.linkedin_username) {
            showToast('warning', 'Username LinkedIn belum tersedia pada profil alumni ini.');
            return;
        }
    }

    alumni.isSyncing = true;
    alumni.status_sync = 'Menyinkronkan...';
    alumni.status_color = 'indigo';

    try {
        const response = await fetch(`${props.baseRoute}/${alumni.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
        });

        const data = await response.json();

        if (response.ok && data.success) {
            if (props.provider === 'apify') {
                alumni.status_sync = 'Pending Review';
                alumni.status_color = 'amber';
                alumni.terakhir_sync = data.alumni.terakhir_sync;
                alumni.terakhir_sync_human = data.alumni.terakhir_sync_human;
                alumni.latest_sync_result = data.alumni.latest_sync_result;

                showToast('success', `${alumni.nama}: ${data.message}`);
                recalculateLocalStats();

                if (data.sync_result_id) {
                    openReviewModal(data.sync_result_id, alumni);
                }
            } else {
                alumni.perusahaan = data.alumni.perusahaan;
                alumni.posisi = data.alumni.posisi;
                alumni.status_sync = data.alumni.status_sync;
                alumni.status_color = data.alumni.status_color;
                alumni.terakhir_sync = data.alumni.terakhir_sync;
                alumni.terakhir_sync_human = data.alumni.terakhir_sync_human;

                showToast('success', `${alumni.nama}: ${data.message}`);
                recalculateLocalStats();
            }
        } else {
            alumni.status_sync = 'Gagal';
            alumni.status_color = 'rose';
            showToast('error', `${alumni.nama}: ${data.message || 'Sinkronisasi gagal.'}`);
            recalculateLocalStats();
        }
    } catch (err) {
        alumni.status_sync = 'Gagal';
        alumni.status_color = 'rose';
        showToast('error', `${alumni.nama}: Terjadi gangguan jaringan.`);
        recalculateLocalStats();
    } finally {
        alumni.isSyncing = false;
    }
};

// =========================================================================
// PENINJAUAN & APPROVAL STAGING (PROVIDER APIFY) - BEBAS EMOTICON
// =========================================================================
const openReviewModal = async (syncResultId, alumni) => {
    isReviewModalOpen.value = true;
    isReviewLoading.value = true;
    activeReview.value = null;
    activeAlumni.value = alumni;

    try {
        const response = await fetch(`${props.baseRoute}/results/${syncResultId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await response.json();
        if (response.ok && data.success) {
            activeReview.value = data.result;
            approveForm.value.tipe_pekerjaan = 'pekerja';
            approveForm.value.posisi_jabatan = data.result.preview?.current_job || '';
            approveForm.value.posisi_wiraswasta = '';
            approveForm.value.nama_perusahaan = data.result.preview?.current_company || '';
            approveForm.value.sync_foto = !!data.result.preview?.profile_picture;
        } else {
            showToast('error', data.message || 'Gagal memuat rincian hasil sinkronisasi.');
            isReviewModalOpen.value = false;
        }
    } catch (err) {
        showToast('error', 'Terjadi kesalahan saat memuat data peninjauan.');
        isReviewModalOpen.value = false;
    } finally {
        isReviewLoading.value = false;
    }
};

const handleApprove = async () => {
    if (!activeReview.value || isActionLoading.value) return;

    const isWirausaha = approveForm.value.tipe_pekerjaan === 'wirausaha';
    const targetJabatan = isWirausaha
        ? (approveForm.value.posisi_wiraswasta || approveForm.value.posisi_jabatan || '-')
        : (approveForm.value.posisi_jabatan || '-');
    const targetPerusahaan = approveForm.value.nama_perusahaan || '-';

    const result = await Swal.fire({
        title: 'Setujui & Terapkan Data?',
        html: `
            <div class="text-left text-xs text-slate-700 space-y-2.5">
                <p>Data berikut akan langsung diterapkan ke profil data utama alumni:</p>
                <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 space-y-1.5 text-slate-800">
                    <p><span class="text-slate-500 font-medium">Kategori:</span> <b>${isWirausaha ? 'Wiraswasta / Usaha' : 'Karyawan / Pekerja'}</b></p>
                    <p><span class="text-slate-500 font-medium">${isWirausaha ? 'Posisi Usaha' : 'Posisi / Jabatan'}:</span> <b>${targetJabatan}</b></p>
                    <p><span class="text-slate-500 font-medium">Perusahaan / Usaha:</span> <b>${targetPerusahaan}</b></p>
                </div>
                <div class="flex items-start gap-2 bg-emerald-50 text-emerald-900 p-2.5 rounded-lg border border-emerald-200">
                    <span class="font-bold shrink-0">Catatan:</span>
                    <span>Perusahaan ini akan otomatis didaftarkan ke Master Perusahaan berstatus Menunggu Verifikasi.</span>
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0D542B',
        cancelButtonColor: '#9CA3AF',
        confirmButtonText: 'Ya, Setujui & Simpan',
        cancelButtonText: 'Batal',
        reverseButtons: true,
    });

    if (!result.isConfirmed) return;

    isActionLoading.value = true;
    try {
        const response = await fetch(`${props.baseRoute}/results/${activeReview.value.id}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                tipe_pekerjaan: approveForm.value.tipe_pekerjaan,
                posisi_jabatan: approveForm.value.posisi_jabatan,
                posisi_wiraswasta: approveForm.value.posisi_wiraswasta,
                nama_perusahaan: approveForm.value.nama_perusahaan,
                sync_foto: approveForm.value.sync_foto,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            if (activeAlumni.value) {
                activeAlumni.value.perusahaan = data.alumni.perusahaan;
                activeAlumni.value.posisi = data.alumni.posisi;
                activeAlumni.value.status_sync = 'Approved';
                activeAlumni.value.status_color = 'emerald';
                activeAlumni.value.latest_sync_result = data.alumni.latest_sync_result;
            }

            isReviewModalOpen.value = false;
            recalculateLocalStats();
            Swal.fire({
                title: 'Berhasil Disetujui',
                text: data.message,
                icon: 'success',
                confirmButtonColor: '#0D542B',
            });
        } else {
            showToast('error', data.message || 'Gagal menyetujui data.');
        }
    } catch (err) {
        showToast('error', 'Terjadi kesalahan sistem saat menyetujui data.');
    } finally {
        isActionLoading.value = false;
    }
};

const handleReject = async () => {
    if (!activeReview.value || isActionLoading.value) return;

    const result = await Swal.fire({
        title: 'Tolak Hasil Sinkronisasi?',
        text: 'Data utama alumni tidak akan diubah. Hasil scraping akan tetap tersimpan sebagai histori dengan status Ditolak.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#9CA3AF',
        confirmButtonText: 'Ya, Tolak Hasil Ini',
        cancelButtonText: 'Batal',
        reverseButtons: true,
    });

    if (!result.isConfirmed) return;

    isActionLoading.value = true;
    try {
        const response = await fetch(`${props.baseRoute}/results/${activeReview.value.id}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
        });

        const data = await response.json();

        if (response.ok && data.success) {
            if (activeAlumni.value) {
                activeAlumni.value.status_sync = 'Rejected';
                activeAlumni.value.status_color = 'rose';
                activeAlumni.value.latest_sync_result = data.alumni.latest_sync_result;
            }

            isReviewModalOpen.value = false;
            recalculateLocalStats();
            Swal.fire({
                title: 'Hasil Ditolak',
                text: data.message,
                icon: 'info',
                confirmButtonColor: '#0D542B',
            });
        } else {
            showToast('error', data.message || 'Gagal menolak hasil.');
        }
    } catch (err) {
        showToast('error', 'Terjadi gangguan sistem saat menolak hasil.');
    } finally {
        isActionLoading.value = false;
    }
};

// =========================================================================
// HISTORI SINKRONISASI
// =========================================================================
const openHistoryModal = async (alumni) => {
    isHistoryModalOpen.value = true;
    isHistoryLoading.value = true;
    historyAlumni.value = alumni;
    historyList.value = [];

    try {
        const response = await fetch(`${props.baseRoute}/alumni/${alumni.id}/history`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await response.json();
        if (response.ok && data.success) {
            historyList.value = data.history || [];
        } else {
            showToast('error', data.message || 'Gagal memuat histori sinkronisasi.');
        }
    } catch (err) {
        showToast('error', 'Terjadi kesalahan sistem saat mengambil histori.');
    } finally {
        isHistoryLoading.value = false;
    }
};

// Helper teks role untuk breadcrumb & header
const roleTitle = computed(() => {
    if (props.role === 'admin_prodi') return 'Program Studi';
    if (props.role === 'admin_fakultas') return 'Fakultas';
    return 'Super Admin';
});

const dashboardRoute = computed(() => {
    if (props.role === 'admin_prodi') return '/prodi/dashboard';
    if (props.role === 'admin_fakultas') return '/fakultas/dashboard';
    return '/superadmin/dashboard';
});
</script>

<template>
    <Head :title="`Sinkronisasi LinkedIn - ${roleTitle}`" />

    <div class="min-h-screen bg-[#f8fafc] text-gray-800 font-sans flex">
        <!-- Sidebar Adaptif Sesuai Role Pengguna -->
        <SidebarSuperAdmin v-if="role === 'superadmin' || !role" activeMenu="linkedin-sync" />
        <SidebarFakultas v-else-if="role === 'admin_fakultas'" />
        <SidebarProdi v-else-if="role === 'admin_prodi'" />

        <!-- Area Konten Utama -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-72">

            <!-- Header Solid Hijau Resmi UKDW #0D542B (Serasi dengan superadmin/alumni) -->
            <header class="bg-[#0D542B] text-white pt-8 pb-16 px-4 sm:px-6 lg:px-8">
                <div class="w-full max-w-[1400px] mx-auto flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2 text-xs text-white/80 font-medium mb-2">
                            <Link :href="dashboardRoute" class="hover:underline">Dashboard</Link>
                            <span>/</span>
                            <span>{{ roleTitle }}</span>
                            <span>/</span>
                            <span class="text-white font-bold">Sinkronisasi LinkedIn</span>
                        </div>

                        <div class="flex items-center gap-3 flex-wrap">
                            <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                                Sinkronisasi Data LinkedIn Alumni
                            </h1>

                            <!-- Badge Mode Provider -->
                            <span v-if="provider === 'apify'" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white/15 text-white border border-white/20">
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                Apify Scraper (Staging & Review)
                            </span>
                            <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white/15 text-white border border-white/20">
                                <span class="w-2 h-2 rounded-full bg-[#FDC700]"></span>
                                Official LinkedIn Provider
                            </span>
                        </div>

                        <p class="text-white/90 text-sm sm:text-base font-normal mt-1 max-w-2xl leading-relaxed">
                            Pemetaan dan penelusuran riwayat karier alumni melalui profil publik LinkedIn secara terstruktur ke dalam database tracer study UKDW.
                        </p>
                    </div>

                    <!-- Ringkasan Cepat di Header (Aksen Kuning UKDW #FDC700) -->
                    <div class="bg-black/15 border border-white/20 px-6 py-4 rounded-2xl text-left md:text-right text-white">
                        <span class="text-xs text-white/80 font-bold uppercase tracking-wider block">Tingkat Sinkronisasi LinkedIn</span>
                        <span class="text-3xl font-black text-[#FDC700] block">{{ localStats.persentase_sinkron }}%</span>
                        <span class="text-xs text-white/90 font-medium">{{ localStats.berhasil_sinkron }} dari {{ localStats.total_alumni }} Alumni Terhubung</span>
                    </div>
                </div>
            </header>

            <!-- Main Content Container -->
            <main class="w-full max-w-[1400px] mx-auto -mt-10 px-4 sm:px-6 lg:px-8 space-y-6 pb-16">

                <!-- 5 Kartu Statistik Ringkas & Profesional (-mt-10) -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                    <!-- 1. Total Alumni -->
                    <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-100">
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Alumni</p>
                        <p class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight mt-2">{{ localStats.total_alumni }}</p>
                        <p class="text-xs text-gray-400 mt-1">Lulusan {{ selectedTahun }}</p>
                    </div>

                    <!-- 2. Terhubung LinkedIn -->
                    <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-100">
                        <p class="text-xs font-bold text-sky-700 uppercase tracking-wider">Punya LinkedIn</p>
                        <p class="text-2xl sm:text-3xl font-extrabold text-sky-900 tracking-tight mt-2">{{ localStats.dengan_linkedin }}</p>
                        <p class="text-xs text-sky-600 mt-1">{{ provider === 'apify' ? 'Memiliki URL Profil' : 'Memiliki Username' }}</p>
                    </div>

                    <!-- 3. Berhasil / Approved -->
                    <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-100">
                        <p class="text-xs font-bold text-[#0D542B] uppercase tracking-wider">Disinkronkan</p>
                        <p class="text-2xl sm:text-3xl font-extrabold text-[#0D542B] tracking-tight mt-2">{{ localStats.berhasil_sinkron }}</p>
                        <p class="text-xs text-gray-400 mt-1">Data valid & approved</p>
                    </div>

                    <!-- 4. Menunggu Review (Khusus Apify) -->
                    <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-100">
                        <p class="text-xs font-bold text-amber-600 uppercase tracking-wider">Pending Review</p>
                        <p class="text-2xl sm:text-3xl font-extrabold text-amber-600 tracking-tight mt-2">{{ localStats.pending_review }}</p>
                        <p class="text-xs text-amber-600 mt-1">Menunggu persetujuan</p>
                    </div>

                    <!-- 5. Belum / Gagal Sinkron -->
                    <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-100 col-span-2 sm:col-span-1">
                        <p class="text-xs font-bold text-rose-600 uppercase tracking-wider">Belum / Gagal</p>
                        <p class="text-2xl sm:text-3xl font-extrabold text-rose-600 tracking-tight mt-2">{{ localStats.gagal_sinkron + localStats.dilewati }}</p>
                        <p class="text-xs text-gray-400 mt-1">Perlu ditindaklanjuti</p>
                    </div>
                </div>

                <!-- Panel Filter Komprehensif (Sama Persis dengan superadmin/alumni) -->
                <div class="bg-white rounded-2xl p-6 shadow-xs border border-gray-100">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-5 flex-wrap gap-3">
                        <div class="flex items-center gap-3">
                            <h2 class="text-sm font-extrabold text-gray-900 uppercase tracking-wider">
                                Filter & Pencarian Alumni LinkedIn
                            </h2>
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-50 text-[#0D542B] font-bold">
                                Tahun Kelulusan: {{ selectedTahun }}
                            </span>
                        </div>
                        <div class="flex items-center gap-4">
                            <button 
                                type="button"
                                @click="resetFilters" 
                                class="text-xs text-gray-500 hover:text-gray-800 font-bold transition-colors cursor-pointer"
                            >
                                Reset Filter
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                        <!-- 1. Pencarian Nama / NIM / Akun -->
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Cari Nama / NIM / LinkedIn</label>
                            <input 
                                type="text" 
                                v-model="search" 
                                @input="handleSearchInput" 
                                placeholder="Ketik nama, NIM, atau URL..." 
                                class="w-full text-xs rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 font-medium text-gray-900 focus:bg-white focus:border-[#0D542B] focus:ring-1 focus:ring-[#0D542B] outline-none"
                            />
                        </div>

                        <!-- 2. Filter Tahun Kelulusan -->
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Tahun Kelulusan *</label>
                            <select 
                                v-model="selectedTahun" 
                                @change="applyFilters" 
                                class="w-full text-xs rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 font-bold text-gray-900 focus:bg-white focus:border-[#0D542B] focus:ring-1 focus:ring-[#0D542B] outline-none cursor-pointer"
                            >
                                <option v-for="t in daftarTahun" :key="t" :value="t">
                                    {{ t }}
                                </option>
                            </select>
                        </div>

                        <!-- 3. Filter Target Kelulusan (Semester + Tahun) -->
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Target Kelulusan</label>
                            <select 
                                v-model="selectedTarget" 
                                @change="applyFilters" 
                                class="w-full text-xs rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 font-medium text-gray-900 focus:bg-white focus:border-[#0D542B] focus:ring-1 focus:ring-[#0D542B] outline-none cursor-pointer"
                            >
                                <option value="all">Semua Target</option>
                                <option v-for="tgt in daftarTarget" :key="tgt" :value="tgt">
                                    Target {{ tgt }}
                                </option>
                            </select>
                        </div>

                        <!-- 4. Filter Semester Kelulusan -->
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Semester</label>
                            <select 
                                v-model="selectedSemester" 
                                @change="applyFilters" 
                                class="w-full text-xs rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 font-medium text-gray-900 focus:bg-white focus:border-[#0D542B] focus:ring-1 focus:ring-[#0D542B] outline-none cursor-pointer"
                            >
                                <option value="all">Semua Semester</option>
                                <option value="Gasal">Gasal</option>
                                <option value="Genap">Genap</option>
                            </select>
                        </div>

                        <!-- 5. Filter Status Sinkronisasi -->
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Status Sinkronisasi</label>
                            <select 
                                v-model="selectedStatus" 
                                @change="applyFilters" 
                                class="w-full text-xs rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 font-medium text-gray-900 focus:bg-white focus:border-[#0D542B] focus:ring-1 focus:ring-[#0D542B] outline-none cursor-pointer"
                            >
                                <option value="all">Semua Status</option>
                                <option value="approved">Approved / Berhasil</option>
                                <option value="pending">Pending Review</option>
                                <option value="belum_sync">Belum Disinkronkan</option>
                                <option value="rejected">Ditolak / Gagal</option>
                                <option value="ada_linkedin">Memiliki Akun LinkedIn</option>
                            </select>
                        </div>

                        <!-- 6. Filter Program Studi (Disesuaikan Peran) -->
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Program Studi</label>
                            <select 
                                v-model="selectedProdiId" 
                                @change="applyFilters" 
                                :disabled="role === 'admin_prodi'"
                                class="w-full text-xs rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 font-medium text-gray-900 focus:bg-white focus:border-[#0D542B] focus:ring-1 focus:ring-[#0D542B] outline-none disabled:opacity-75 disabled:cursor-not-allowed cursor-pointer"
                            >
                                <option v-if="role !== 'admin_prodi'" value="all">Semua Program Studi</option>
                                <option v-for="p in prodis" :key="p.id" :value="p.id">
                                    {{ p.kode_prodi ? `${p.kode_prodi} - ` : '' }}{{ p.nama_prodi }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tabel Data Alumni & Status Sinkronisasi LinkedIn -->
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-gray-700">Daftar Alumni:</span>
                            <span class="text-xs font-extrabold text-[#0D542B] bg-emerald-50 px-2 py-0.5 rounded-full">
                                {{ filteredAlumni.length }} Data Ditemukan
                            </span>
                        </div>
                        <div class="text-xs text-gray-500 font-medium">
                            Menampilkan halaman {{ currentPage }} dari {{ totalPages }}
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-gray-600">
                            <thead class="bg-gray-50/80 text-gray-700 font-bold uppercase tracking-wider text-[11px] border-b border-gray-100">
                                <tr>
                                    <th class="py-3.5 px-6">Alumni / Mahasiswa</th>
                                    <th class="py-3.5 px-4">Program Studi</th>
                                    <th class="py-3.5 px-4">Akun LinkedIn</th>
                                    <th class="py-3.5 px-4">Karier Terkini</th>
                                    <th class="py-3.5 px-4 text-center">Status Sinkronisasi</th>
                                    <th class="py-3.5 px-6 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="paginatedAlumni.length === 0">
                                    <td colspan="6" class="py-12 text-center text-gray-400">
                                        Tidak ada data alumni yang cocok dengan filter yang dipilih.
                                    </td>
                                </tr>

                                <tr 
                                    v-else 
                                    v-for="alumni in paginatedAlumni" 
                                    :key="alumni.id"
                                    :id="`alumni-row-${alumni.id}`"
                                    class="hover:bg-gray-50/60 transition-colors"
                                    :class="highlightedAlumniId === alumni.id ? 'bg-amber-50/50' : ''"
                                >
                                    <!-- 1. Alumni Info -->
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-slate-100 text-[#0D542B] font-bold text-xs flex items-center justify-center shrink-0 border border-slate-200">
                                                {{ (alumni.nama || 'A').charAt(0) }}
                                            </div>
                                            <div>
                                                <Link 
                                                    :href="role === 'superadmin' ? `/superadmin/alumni/${alumni.id}` : (role === 'admin_fakultas' ? `/fakultas/alumni/${alumni.id}` : `/prodi/alumni/${alumni.id}`)"
                                                    class="font-bold text-gray-900 hover:text-[#0D542B] hover:underline block leading-tight text-xs sm:text-sm"
                                                >
                                                    {{ alumni.nama }}
                                                </Link>
                                                <span class="text-[11px] font-mono text-gray-500 block mt-0.5">
                                                    NIM: {{ alumni.nim }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 2. Program Studi -->
                                    <td class="py-4 px-4">
                                        <span class="font-semibold text-gray-800 block">{{ alumni.prodi }}</span>
                                        <span class="text-[10px] text-gray-400 block">{{ alumni.fakultas }}</span>
                                    </td>

                                    <!-- 3. Akun LinkedIn -->
                                    <td class="py-4 px-4">
                                        <div v-if="alumni.linkedin_url">
                                            <a 
                                                :href="alumni.linkedin_url" 
                                                target="_blank" 
                                                class="text-[#0077B5] hover:underline font-mono text-xs inline-flex items-center gap-1 max-w-[200px] truncate"
                                                title="Buka profil LinkedIn asli"
                                            >
                                                <svg class="w-3.5 h-3.5 shrink-0 fill-current" viewBox="0 0 24 24">
                                                    <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                                                </svg>
                                                <span class="truncate">{{ alumni.linkedin_url.replace(/^https?:\/\/(www\.)?linkedin\.com\/in\//, '') || 'Profil LinkedIn' }}</span>
                                            </a>
                                        </div>
                                        <div v-else-if="alumni.linkedin_username">
                                            <span class="font-mono text-gray-700 text-xs">@{{ alumni.linkedin_username }}</span>
                                        </div>
                                        <div v-else class="text-gray-400 text-[11px] italic">
                                            Belum diisi
                                        </div>
                                    </td>

                                    <!-- 4. Karier Terkini -->
                                    <td class="py-4 px-4">
                                        <span class="font-semibold text-gray-800 block truncate max-w-[200px]">{{ alumni.posisi || '-' }}</span>
                                        <span class="text-[11px] text-gray-500 block truncate max-w-[200px]">{{ alumni.perusahaan || '-' }}</span>
                                    </td>

                                    <!-- 5. Status Sinkronisasi -->
                                    <td class="py-4 px-4 text-center">
                                        <span 
                                            class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                            :class="{
                                                'bg-emerald-50 text-emerald-800 border border-emerald-200': alumni.status_sync === 'Approved' || alumni.status_sync === 'Berhasil',
                                                'bg-amber-50 text-amber-800 border border-amber-200': alumni.status_sync === 'Pending Review',
                                                'bg-rose-50 text-rose-800 border border-rose-200': alumni.status_sync === 'Rejected' || alumni.status_sync === 'Gagal',
                                                'bg-gray-100 text-gray-600': alumni.status_sync === 'Belum Disinkronkan' || alumni.status_sync === 'Belum Ada URL LinkedIn',
                                            }"
                                        >
                                            {{ alumni.status_sync }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 block mt-1 font-mono">
                                            {{ alumni.terakhir_sync || '-' }}
                                        </span>
                                    </td>

                                    <!-- 6. Aksi Cepat (Tombol Ikon Berukuran Pasti) -->
                                    <td class="py-4 px-6 text-center">
                                        <div class="inline-flex items-center justify-center gap-1.5">
                                            <!-- Tombol Sync Single (Icon Berukuran Pasti 32x32) -->
                                            <button
                                                type="button"
                                                @click="syncSingle(alumni)"
                                                :disabled="alumni.isSyncing"
                                                class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 text-white bg-[#0D542B] hover:bg-[#0A4322] active:scale-95 transition-all shadow-2xs disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                                                :title="alumni.isSyncing ? 'Sedang memproses sinkronisasi...' : 'Sinkronkan Profil LinkedIn'"
                                                aria-label="Sinkronkan Profil LinkedIn"
                                            >
                                                <svg v-if="!alumni.isSyncing" class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                                    <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                                                </svg>
                                                <svg v-else class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                </svg>
                                            </button>

                                            <!-- Tombol Review (Jika status Pending Review, Icon Berukuran Pasti 32x32) -->
                                            <button
                                                v-if="alumni.latest_sync_result && alumni.latest_sync_result.status === 'pending'"
                                                type="button"
                                                @click="openReviewModal(alumni.latest_sync_result.id, alumni)"
                                                class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 text-amber-800 bg-amber-100 hover:bg-amber-200 border border-amber-200/80 active:scale-95 transition-all shadow-2xs cursor-pointer"
                                                title="Tinjau hasil scraping Apify"
                                                aria-label="Tinjau hasil sinkronisasi"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                                </svg>
                                            </button>

                                            <!-- Tombol Histori (Icon Berukuran Pasti 32x32) -->
                                            <button
                                                type="button"
                                                @click="openHistoryModal(alumni)"
                                                class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200/60 active:scale-95 transition-all shadow-2xs cursor-pointer"
                                                title="Lihat riwayat sinkronisasi alumni ini"
                                                aria-label="Riwayat sinkronisasi"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer Pagination ala DataTables -->
                    <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between flex-wrap gap-4">
                        <div class="text-xs text-gray-500">
                            Menampilkan {{ filteredAlumni.length > 0 ? (currentPage - 1) * perPage + 1 : 0 }} sampai 
                            {{ Math.min(currentPage * perPage, filteredAlumni.length) }} dari 
                            {{ filteredAlumni.length }} data alumni
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                @click="goToPage(currentPage - 1)"
                                :disabled="currentPage === 1"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                            >
                                Sebelumnya
                            </button>

                            <button
                                v-for="p in Math.min(totalPages, 5)"
                                :key="p"
                                type="button"
                                @click="goToPage(p)"
                                class="w-8 h-8 rounded-lg text-xs font-bold transition-colors cursor-pointer"
                                :class="currentPage === p ? 'bg-[#0D542B] text-white shadow-2xs' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'"
                            >
                                {{ p }}
                            </button>

                            <button
                                type="button"
                                @click="goToPage(currentPage + 1)"
                                :disabled="currentPage === totalPages"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                            >
                                Selanjutnya
                            </button>
                        </div>
                    </div>
                </div>

            </main>
        </div>

        <!-- ================================================================= -->
        <!-- MODAL PENINJAUAN & APPROVAL (APIFY) - BERSIH TANPA EMOTICON       -->
        <!-- ================================================================= -->
        <div
            v-if="isReviewModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
        >
            <div class="bg-white rounded-2xl max-w-3xl w-full shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
                <!-- Header Modal -->
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-slate-900">
                                Peninjauan Hasil Sinkronisasi LinkedIn
                            </h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800">
                                Pending Review
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Alumni: <span class="font-semibold text-slate-800">{{ activeReview?.alumni_nama || activeAlumni?.nama }}</span>
                            ({{ activeReview?.alumni_nim || activeAlumni?.nim }}) &bull; Diambil: {{ activeReview?.scraped_at || 'Baru saja' }}
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="isReviewModalOpen = false"
                        class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-200/60 transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Body Modal -->
                <div class="p-6 overflow-y-auto space-y-5 flex-1">
                    <div v-if="isReviewLoading" class="py-16 text-center space-y-3">
                        <div class="inline-block w-8 h-8 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
                        <p class="text-xs text-slate-500">Memuat rincian hasil scraping Apify...</p>
                    </div>

                    <template v-else-if="activeReview">
                        <!-- Banner Peringatan Bersih -->
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3.5 flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="text-xs">
                                <p class="font-bold text-amber-900">
                                    Hasil sinkronisasi LinkedIn dari provider Apify dan belum diterapkan ke data utama.
                                </p>
                                <p class="text-amber-700 mt-0.5 leading-relaxed">
                                    Data profil utama alumni di database kampus <b>tidak akan berubah</b> sampai Anda menekan tombol <b>Setujui (Approve)</b>.
                                </p>
                            </div>
                        </div>

                        <!-- Form Penyesuaian Usulan Sebelum Disetujui -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                                    Penyesuaian Data Profil Sebelum Disetujui
                                </h4>
                                <span class="text-[11px] text-slate-500">
                                    Data di bawah dapat disesuaikan sebelum diterapkan ke database utama
                                </span>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 text-xs">
                                <!-- Data Saat Ini -->
                                <div class="bg-white p-3 rounded-lg border border-slate-200">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">
                                        Data Saat Ini di Sistem:
                                    </span>
                                    <div class="space-y-1">
                                        <p><span class="text-slate-500">Jabatan:</span> <b class="text-slate-800">{{ activeReview.current_data?.posisi_jabatan || '- Belum diisi -' }}</b></p>
                                        <p><span class="text-slate-500">Perusahaan:</span> <b class="text-slate-800">{{ activeReview.current_data?.nama_perusahaan || '- Belum diisi -' }}</b></p>
                                    </div>
                                </div>

                                <!-- Form Penyesuaian Usulan (Bebas Emoticon) -->
                                <div class="lg:col-span-2 bg-emerald-50/60 p-3.5 rounded-lg border border-emerald-200 space-y-2.5">
                                    <div class="flex items-center justify-between flex-wrap gap-2">
                                        <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider">
                                            Data yang Akan Disimpan ke Profil:
                                        </span>
                                        <!-- Toggle Kategori Bersih Tanpa Emoji -->
                                        <div class="inline-flex rounded-lg border border-emerald-300 p-0.5 bg-white text-[11px] shadow-2xs">
                                            <button
                                                type="button"
                                                @click="approveForm.tipe_pekerjaan = 'pekerja'"
                                                :class="[approveForm.tipe_pekerjaan === 'pekerja' ? 'bg-[#0D542B] text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900']"
                                                class="px-2.5 py-1 rounded-md transition-all cursor-pointer flex items-center gap-1.5"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                </svg>
                                                <span>Karyawan / Pekerja</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="approveForm.tipe_pekerjaan = 'wirausaha'"
                                                :class="[approveForm.tipe_pekerjaan === 'wirausaha' ? 'bg-[#0D542B] text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900']"
                                                class="px-2.5 py-1 rounded-md transition-all cursor-pointer flex items-center gap-1.5"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                </svg>
                                                <span>Wirausaha / Bisnis</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                        <!-- Posisi Jabatan -->
                                        <div>
                                            <label class="block text-[11px] font-semibold text-emerald-950 mb-1">
                                                {{ approveForm.tipe_pekerjaan === 'wirausaha' ? 'Posisi Wiraswasta / Usaha:' : 'Posisi / Jabatan Pekerjaan:' }}
                                            </label>
                                            <input
                                                v-if="approveForm.tipe_pekerjaan === 'wirausaha'"
                                                type="text"
                                                v-model="approveForm.posisi_wiraswasta"
                                                :placeholder="approveForm.posisi_jabatan || 'contoh: Founder, Owner, Direktur'"
                                                class="w-full px-2.5 py-1.5 text-xs bg-white border border-emerald-300 rounded-md focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B]"
                                            />
                                            <input
                                                v-else
                                                type="text"
                                                v-model="approveForm.posisi_jabatan"
                                                placeholder="contoh: Software Engineer, Analis Sistem"
                                                class="w-full px-2.5 py-1.5 text-xs bg-white border border-emerald-300 rounded-md focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B]"
                                            />
                                        </div>

                                        <!-- Nama Perusahaan -->
                                        <div>
                                            <label class="block text-[11px] font-semibold text-emerald-950 mb-1">
                                                {{ approveForm.tipe_pekerjaan === 'wirausaha' ? 'Nama Unit Usaha / Bisnis:' : 'Nama Perusahaan / Instansi:' }}
                                            </label>
                                            <input
                                                type="text"
                                                v-model="approveForm.nama_perusahaan"
                                                placeholder="contoh: PT Teknologi Nusantara"
                                                class="w-full px-2.5 py-1.5 text-xs bg-white border border-emerald-300 rounded-md focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B]"
                                            />
                                        </div>
                                    </div>

                                    <!-- Opsi Foto Profil -->
                                    <div
                                        v-if="activeReview.preview?.profile_picture"
                                        class="p-2.5 bg-white/95 rounded-md border border-emerald-300 flex items-center justify-between gap-3 shadow-2xs"
                                    >
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <img
                                                :src="activeReview.preview.profile_picture"
                                                alt="Foto LinkedIn"
                                                class="w-7 h-7 rounded-full object-cover border border-slate-200 shrink-0"
                                            />
                                            <div class="min-w-0 text-left">
                                                <span class="text-[11px] font-semibold text-slate-800 block">Terapkan Foto Profil LinkedIn ke Biodata Alumni</span>
                                                <span class="text-[10px] text-slate-500 block truncate">Foto akan disimpan ke sistem /uploads/profile/</span>
                                            </div>
                                        </div>
                                        <label class="flex items-center gap-1.5 cursor-pointer shrink-0 bg-emerald-50 px-2 py-1 rounded border border-emerald-200">
                                            <input
                                                type="checkbox"
                                                v-model="approveForm.sync_foto"
                                                class="rounded text-[#0D542B] focus:ring-[#0D542B] w-4 h-4 cursor-pointer"
                                            />
                                            <span class="text-xs font-semibold text-emerald-900">Gunakan</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rincian Profil Hasil Scraping -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                                Rincian Profil Hasil Scraping
                            </h4>

                            <div class="bg-white p-4 rounded-xl border border-slate-200 space-y-3 text-xs">
                                <div class="flex items-start gap-3 pb-3 border-b border-slate-100">
                                    <img
                                        v-if="activeReview.preview?.profile_picture"
                                        :src="activeReview.preview.profile_picture"
                                        alt="Foto Profil LinkedIn"
                                        class="w-12 h-12 rounded-full object-cover border border-slate-200 shrink-0"
                                    />
                                    <div v-else class="w-12 h-12 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 font-bold shrink-0">
                                        {{ (activeReview.preview?.name || 'A').charAt(0) }}
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <h3 class="font-bold text-slate-900 text-sm">
                                            {{ activeReview.preview?.name || '-' }}
                                        </h3>
                                        <p class="text-slate-600 text-xs mt-0.5">{{ activeReview.preview?.headline || '-' }}</p>
                                        <p class="text-slate-400 text-[11px] mt-0.5">{{ activeReview.preview?.location || '-' }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <div>
                                        <span class="text-slate-400 block text-[11px]">URL Profil:</span>
                                        <a
                                            :href="activeReview.linkedin_url"
                                            target="_blank"
                                            class="text-sky-600 hover:underline font-mono truncate block text-xs"
                                        >
                                            {{ activeReview.linkedin_url }}
                                        </a>
                                    </div>
                                    <div v-if="activeReview.preview?.current_company_url">
                                        <span class="text-slate-400 block text-[11px]">URL Perusahaan:</span>
                                        <a
                                            :href="activeReview.preview.current_company_url"
                                            target="_blank"
                                            class="text-sky-600 hover:underline font-mono truncate block text-xs"
                                        >
                                            {{ activeReview.preview.current_company_url }}
                                        </a>
                                    </div>
                                </div>

                                <div v-if="activeReview.preview?.about" class="pt-2 border-t border-slate-100">
                                    <span class="text-slate-400 block text-[11px] mb-1">Ringkasan (About):</span>
                                    <p class="text-slate-700 leading-relaxed bg-slate-50 p-2.5 rounded-lg border border-slate-100 font-sans">
                                        {{ activeReview.preview.about }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Riwayat Pengalaman Kerja -->
                        <div v-if="activeReview.preview?.experience && activeReview.preview.experience.length > 0" class="space-y-2.5">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                                Riwayat Pengalaman Kerja ({{ activeReview.preview.experience.length }})
                            </h4>

                            <div class="space-y-2">
                                <div
                                    v-for="(exp, idx) in activeReview.preview.experience"
                                    :key="idx"
                                    class="bg-white p-3 rounded-xl border border-slate-200 text-xs flex items-start justify-between gap-3"
                                >
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-900">{{ exp.position || 'Posisi tidak disebutkan' }}</span>
                                            <span
                                                v-if="exp.is_current"
                                                class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                            >
                                                Pekerjaan Saat Ini
                                            </span>
                                        </div>
                                        <p class="text-slate-600 font-medium mt-0.5">
                                            {{ exp.company || 'Perusahaan tidak disebutkan' }}
                                            <span v-if="exp.time_period" class="text-slate-400 font-normal">({{ exp.time_period }})</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Footer Aksi Modal (Bebas Emoticon) -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                    <div>
                        <span v-if="activeReview && activeReview.status !== 'pending'" class="text-xs text-slate-500 italic">
                            Catatan: Data ini telah di-review ({{ activeReview.status }}) pada {{ activeReview.reviewed_at }}.
                        </span>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <button
                            type="button"
                            @click="isReviewModalOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white hover:bg-slate-100 border border-slate-300 transition-colors cursor-pointer"
                        >
                            Tutup
                        </button>

                        <template v-if="activeReview && activeReview.status === 'pending'">
                            <button
                                type="button"
                                @click="handleReject"
                                :disabled="isActionLoading"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors disabled:opacity-50 cursor-pointer"
                            >
                                Tolak (Reject)
                            </button>

                            <button
                                type="button"
                                @click="handleApprove"
                                :disabled="isActionLoading"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-[#0D542B] hover:bg-[#093c1f] transition-colors shadow-2xs disabled:opacity-50 cursor-pointer"
                            >
                                <svg v-if="!isActionLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Setujui (Approve)</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- MODAL HISTORI SINKRONISASI                                        -->
        <!-- ================================================================= -->
        <div
            v-if="isHistoryModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
        >
            <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[85vh]">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">
                            Histori Sinkronisasi LinkedIn
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Alumni: <span class="font-semibold text-slate-800">{{ historyAlumni?.nama }}</span> ({{ historyAlumni?.nim }})
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="isHistoryModalOpen = false"
                        class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-200/60 transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto space-y-3 flex-1">
                    <div v-if="isHistoryLoading" class="py-12 text-center text-xs text-slate-500">
                        Memuat data histori...
                    </div>

                    <div v-else-if="historyList.length === 0" class="py-12 text-center text-xs text-slate-400">
                        Belum ada riwayat sinkronisasi untuk alumni ini.
                    </div>

                    <div
                        v-else
                        v-for="item in historyList"
                        :key="item.id"
                        class="p-4 rounded-xl border border-slate-200 hover:border-slate-300 transition-colors bg-white flex items-start justify-between gap-3 text-xs"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-slate-400 text-[10px]">#{{ item.id }}</span>
                                <span
                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                    :class="[
                                        item.status === 'pending'
                                            ? 'bg-amber-100 text-amber-800'
                                            : (item.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800')
                                    ]"
                                >
                                    {{ item.status }}
                                </span>
                                <span class="text-slate-400 text-[11px] font-mono">{{ item.scraped_at }}</span>
                            </div>

                            <p class="text-slate-800">
                                <b>Jabatan:</b> {{ item.preview?.current_job || '-' }} &bull;
                                <b>Perusahaan:</b> {{ item.preview?.current_company || '-' }}
                            </p>

                            <p v-if="item.reviewed_at" class="text-[11px] text-slate-500">
                                Ditinjau oleh: <b>{{ item.reviewer_name }}</b> pada {{ item.reviewed_at }}
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="isHistoryModalOpen = false; openReviewModal(item.id, historyAlumni)"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors shrink-0 cursor-pointer"
                        >
                            Detail Snapshot
                        </button>
                    </div>
                </div>

                <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 text-right">
                    <button
                        type="button"
                        @click="isHistoryModalOpen = false"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white hover:bg-slate-100 border border-slate-300 transition-colors cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>
