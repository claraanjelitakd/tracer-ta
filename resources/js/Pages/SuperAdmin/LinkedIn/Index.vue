<!--
  Halaman Sinkronisasi LinkedIn (Super Admin)
  File: resources/js/Pages/SuperAdmin/LinkedIn/Index.vue

  Mendukung 2 Mode Provider:
  1. Official LinkedIn Provider (Driver: Mock / API Server-to-Server)
  2. Apify Third-Party Provider (Actor: data_forge_org~linkedin-scraper)
     - Staging & Review per-alumni (Status: Pending Review)
     - Modal Peninjauan (Approve / Reject)
     - Histori lengkap sinkronisasi
-->
<script setup>
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, nextTick } from 'vue';
import Swal from 'sweetalert2';
import Sidebar from '../Components/Sidebar.vue';

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
    provider: {
        type: String,
        default: 'official',
    },
    targetAlumniId: {
        type: [Number, String],
        default: null,
    },
    initialSearch: {
        type: String,
        default: '',
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
        }),
    },
});

// State Filter & Pencarian
const selectedTahun = ref(props.tahunTerpilih || (props.daftarTahun[0] || ''));
const search = ref(props.initialSearch || '');
const highlightedAlumniId = ref(props.targetAlumniId ? Number(props.targetAlumniId) : null);

// State Data Alumni Lokal (Reaktif untuk update tanpa reload halaman)
const alumniList = ref(props.alumnis.map(item => ({
    ...item,
    isSyncing: false,
})));

// State Metrik Lokal
const localStats = ref({ ...props.stats });

// State Bulk Sync
const isBulkSyncing = ref(false);
const bulkProgress = ref({ current: 0, total: 0, percentage: 0 });

// State Review Modal (Apify)
const isReviewModalOpen = ref(false);
const isReviewLoading = ref(false);
const isActionLoading = ref(false);
const activeReview = ref(null);
const activeAlumni = ref(null);

// State Form Kustom Approval (bisa diedit langsung oleh Super Admin)
const approveForm = ref({
    tipe_pekerjaan: 'pekerja', // 'pekerja' | 'wirausaha'
    posisi_jabatan: '',
    posisi_wiraswasta: '',
    nama_perusahaan: '',
    sync_foto: true,
});

// State History Modal
const isHistoryModalOpen = ref(false);
const isHistoryLoading = ref(false);
const historyList = ref([]);
const historyAlumni = ref(null);

// Pagination
const perPage = ref(10);
const currentPage = ref(1);

// Filter Data Alumni berdasarkan Pencarian
const filteredAlumni = computed(() => {
    let result = alumniList.value;

    if (search.value.trim()) {
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

// Pagination computed
const totalPages = computed(() => {
    return Math.ceil(filteredAlumni.value.length / perPage.value) || 1;
});

const paginatedAlumni = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredAlumni.value.slice(start, start + perPage.value);
});

// Aksi Terapkan Filter Tahun Kelulusan
const applyTahunFilter = () => {
    highlightedAlumniId.value = null;
    router.get('/superadmin/linkedin-sync', {
        tahun: selectedTahun.value,
    }, {
        preserveState: false,
        preserveScroll: true,
    });
};

// Auto fokus dan scroll ke target alumni jika diarahkan dari detail alumni
onMounted(() => {
    if (highlightedAlumniId.value) {
        // Cari posisi alumni di dalam data yang difilter
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

// Ambil Token CSRF
const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
};

// Toast Notifikasi Sederhana
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

// =========================================================================
// SINKRONISASI INDIVIDUAL (ASYNC TANPA RELOAD HALAMAN)
// =========================================================================
const syncSingle = async (alumni) => {
    if (alumni.isSyncing) return;

    // Validasi input khusus Apify: wajib ada linkedin_url
    if (props.provider === 'apify') {
        if (!alumni.linkedin_url) {
            showToast('warning', 'URL LinkedIn alumni belum diisi. Provider Apify memerlukan field linkedin_url.');
            return;
        }
    } else {
        if (!alumni.linkedin_username) {
            showToast('warning', 'LinkedIn username belum tersedia.');
            return;
        }
    }

    alumni.isSyncing = true;
    alumni.status_sync = 'Menyinkronkan...';
    alumni.status_color = 'indigo';

    try {
        const response = await fetch(`/superadmin/linkedin-sync/${alumni.id}`, {
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
                // Update baris alumni menjadi Pending Review
                alumni.status_sync = 'Pending Review';
                alumni.status_color = 'amber';
                alumni.terakhir_sync = data.alumni.terakhir_sync;
                alumni.terakhir_sync_human = data.alumni.terakhir_sync_human;
                alumni.latest_sync_result = data.alumni.latest_sync_result;

                showToast('success', `${alumni.nama}: ${data.message}`);
                recalculateLocalStats();

                // Buka otomatis modal peninjauan untuk mempermudah Super Admin
                if (data.sync_result_id) {
                    openReviewModal(data.sync_result_id, alumni);
                }
            } else {
                // Official Provider Update
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
        showToast('error', `${alumni.nama}: Terjadi kesalahan jaringan.`);
        recalculateLocalStats();
    } finally {
        alumni.isSyncing = false;
    }
};

// =========================================================================
// REVIEW & APPROVE / REJECT MODAL (APIFY)
// =========================================================================
const openReviewModal = async (syncResultId, alumni) => {
    isReviewModalOpen.value = true;
    isReviewLoading.value = true;
    activeReview.value = null;
    activeAlumni.value = alumni;

    try {
        const response = await fetch(`/superadmin/linkedin-sync/results/${syncResultId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await response.json();
        if (response.ok && data.success) {
            activeReview.value = data.result;
            // Inisialisasi form approval dengan usulan dari LinkedIn
            approveForm.value.tipe_pekerjaan = 'pekerja';
            approveForm.value.posisi_jabatan = data.result.preview?.current_job || '';
            approveForm.value.posisi_wiraswasta = '';
            approveForm.value.nama_perusahaan = data.result.preview?.current_company || '';
            approveForm.value.sync_foto = !!data.result.preview?.profile_picture;
        } else {
            showToast('error', data.message || 'Gagal memuat detail hasil sinkronisasi.');
            isReviewModalOpen.value = false;
        }
    } catch (err) {
        showToast('error', 'Terjadi kesalahan saat mengambil data hasil sinkronisasi.');
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
            <div class="text-left text-xs text-slate-600 space-y-2.5">
                <p>Data berikut akan <b>langsung diterapkan</b> ke profil utama alumni:</p>
                <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200 space-y-1 text-slate-800">
                    <p><span class="text-slate-500">Kategori:</span> <b>${isWirausaha ? 'Wiraswasta' : 'Bekerja (Karyawan)'}</b></p>
                    <p><span class="text-slate-500">${isWirausaha ? 'Posisi Usaha' : 'Posisi / Jabatan'}:</span> <b>${targetJabatan}</b></p>
                    <p><span class="text-slate-500">Perusahaan / Usaha:</span> <b>${targetPerusahaan}</b></p>
                    <p v-if="approveForm.sync_foto"><span class="text-slate-500">Foto Profil:</span> <b>Sinkronkan ke /uploads/profile/</b></p>
                </div>
                <p class="text-amber-800 bg-amber-50 p-2 rounded border border-amber-200">
                    ✨ Perusahaan ini otomatis masuk ke Master Data Perusahaan dengan status <b>Menunggu Verifikasi</b> agar dapat diverifikasi lebih lanjut oleh Admin.
                </p>
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
        const response = await fetch(`/superadmin/linkedin-sync/results/${activeReview.value.id}/approve`, {
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
            // Update baris alumni
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
        text: 'Data utama alumni TIDAK akan diubah. Hasil scraping akan tetap tersimpan sebagai histori dengan status Ditolak.',
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
        const response = await fetch(`/superadmin/linkedin-sync/results/${activeReview.value.id}/reject`, {
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
            showToast('info', data.message);
        } else {
            showToast('error', data.message || 'Gagal menolak data.');
        }
    } catch (err) {
        showToast('error', 'Terjadi kesalahan sistem saat menolak data.');
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
        const response = await fetch(`/superadmin/linkedin-sync/alumni/${alumni.id}/history`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await response.json();
        if (response.ok && data.success) {
            historyList.value = data.history;
        } else {
            showToast('error', 'Gagal memuat histori.');
        }
    } catch (err) {
        showToast('error', 'Terjadi kesalahan jaringan.');
    } finally {
        isHistoryLoading.value = false;
    }
};

// Hitung Ulang Metrik Lokal setelah Sync
const recalculateLocalStats = () => {
    let berhasil = 0;
    let gagal = 0;
    let dilewati = 0;
    let pending = 0;

    alumniList.value.forEach(item => {
        if (props.provider === 'apify') {
            if (!item.linkedin_url) {
                dilewati++;
            } else if (item.status_sync === 'Approved') {
                berhasil++;
            } else if (item.status_sync === 'Rejected' || item.status_sync === 'Gagal') {
                gagal++;
            } else if (item.status_sync === 'Pending Review') {
                pending++;
            }
        } else {
            if (!item.linkedin_username) {
                dilewati++;
            } else if (item.status_sync === 'Berhasil') {
                berhasil++;
            } else if (item.status_sync === 'Gagal') {
                gagal++;
            }
        }
    });

    localStats.value.berhasil_sinkron = berhasil;
    localStats.value.gagal_sinkron = gagal;
    localStats.value.dilewati = dilewati;
    localStats.value.pending_review = pending;
};

// =========================================================================
// SINKRONISASI MASSAL (BULK SYNC DENGAN VISUAL PROGRESS) - OFFICIAL ONLY
// =========================================================================
const handleBulkSync = () => {
    if (props.provider === 'apify') {
        Swal.fire({
            title: 'Cost Safety: Bulk Sync Dinonaktifkan',
            text: 'Untuk keamanan kuota & biaya pada provider Apify, sinkronisasi massal dinonaktifkan. Silakan lakukan sinkronisasi per alumni.',
            icon: 'info',
            confirmButtonColor: '#0D542B',
        });
        return;
    }

    const candidates = alumniList.value.filter(a => !!a.linkedin_username);

    if (candidates.length === 0) {
        Swal.fire({
            title: 'Tidak Ada Akun LinkedIn',
            text: `Tidak ada alumni pada tahun ${selectedTahun.value} yang memiliki username LinkedIn.`,
            icon: 'info',
            confirmButtonColor: '#0D542B',
        });
        return;
    }

    Swal.fire({
        title: `Sinkronkan Tahun ${selectedTahun.value}?`,
        html: `
            <div class="text-left text-xs text-slate-600 space-y-2">
                <p>Sistem akan menyinkronkan profil LinkedIn untuk <b>${candidates.length} alumni</b> yang memiliki username.</p>
                <p class="text-amber-700 bg-amber-50 p-2.5 rounded-lg border border-amber-200">
                    Proses berjalan secara independen per-alumni menggunakan Official LinkedIn Provider.
                </p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0D542B',
        cancelButtonColor: '#9CA3AF',
        confirmButtonText: 'Ya, Jalankan Sinkronisasi',
        cancelButtonText: 'Batal',
        reverseButtons: true,
    }).then(async (result) => {
        if (result.isConfirmed) {
            await executeBulkSync(candidates);
        }
    });
};

const executeBulkSync = async (candidates) => {
    isBulkSyncing.value = true;
    bulkProgress.value = {
        current: 0,
        total: candidates.length,
        percentage: 0,
    };

    let countBerhasil = 0;
    let countGagal = 0;
    const totalAlumniTahun = alumniList.value.length;
    const countDilewati = totalAlumniTahun - candidates.length;

    for (let i = 0; i < candidates.length; i++) {
        const alumni = candidates[i];
        bulkProgress.value.current = i + 1;
        bulkProgress.value.percentage = Math.round(((i + 1) / candidates.length) * 100);

        alumni.isSyncing = true;
        alumni.status_sync = 'Menyinkronkan...';
        alumni.status_color = 'indigo';

        try {
            const response = await fetch(`/superadmin/linkedin-sync/${alumni.id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
            });

            const data = await response.json();

            if (response.ok && data.success) {
                countBerhasil++;
                alumni.perusahaan = data.alumni.perusahaan;
                alumni.posisi = data.alumni.posisi;
                alumni.status_sync = data.alumni.status_sync;
                alumni.status_color = data.alumni.status_color;
                alumni.terakhir_sync = data.alumni.terakhir_sync;
                alumni.terakhir_sync_human = data.alumni.terakhir_sync_human;
            } else {
                countGagal++;
                alumni.status_sync = 'Gagal';
                alumni.status_color = 'rose';
            }
        } catch (err) {
            countGagal++;
            alumni.status_sync = 'Gagal';
            alumni.status_color = 'rose';
        } finally {
            alumni.isSyncing = false;
        }

        localStats.value.berhasil_sinkron = countBerhasil;
        localStats.value.gagal_sinkron = countGagal;
        localStats.value.dilewati = countDilewati;
    }

    isBulkSyncing.value = false;

    Swal.fire({
        title: 'Sinkronisasi Massal Selesai!',
        html: `
            <div class="text-left text-xs text-slate-700 space-y-2">
                <p>Ringkasan pemrosesan angkatan <b>${selectedTahun.value}</b>:</p>
                <div class="grid grid-cols-3 gap-2 text-center py-2">
                    <div class="bg-emerald-50 border border-emerald-200 p-2 rounded-lg">
                        <span class="text-lg font-bold text-emerald-800 block">${countBerhasil}</span>
                        <span class="text-[10px] text-emerald-600 font-semibold uppercase">Berhasil</span>
                    </div>
                    <div class="bg-rose-50 border border-rose-200 p-2 rounded-lg">
                        <span class="text-lg font-bold text-rose-800 block">${countGagal}</span>
                        <span class="text-[10px] text-rose-600 font-semibold uppercase">Gagal</span>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 p-2 rounded-lg">
                        <span class="text-lg font-bold text-amber-800 block">${countDilewati}</span>
                        <span class="text-[10px] text-amber-600 font-semibold uppercase">Dilewati</span>
                    </div>
                </div>
            </div>
        `,
        icon: 'success',
        confirmButtonColor: '#0D542B',
        confirmButtonText: 'Tutup',
    });
};
</script>

<template>
    <Head title="Sinkronisasi LinkedIn - Super Admin" />

    <div class="min-h-screen bg-slate-50 flex">
        <!-- Sidebar Super Admin -->
        <Sidebar activeMenu="linkedin-sync" />

        <!-- Konten Utama -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-72">
            <!-- Header Atas -->
            <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between sticky top-0 z-20">
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-bold text-slate-800">
                        Sinkronisasi Profil LinkedIn
                    </h2>
                    <span class="text-xs px-2.5 py-0.5 rounded-full font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                        Super Admin
                    </span>
                </div>
            </header>

            <main class="p-6 space-y-6">

                <!-- BANNER UTAMA -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-4 lg:p-5 rounded-xl border border-slate-200 shadow-2xs">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-lg lg:text-xl font-bold text-slate-900">
                                Sinkronisasi LinkedIn
                            </h1>

                            <!-- Badge Mode Provider -->
                            <span v-if="provider === 'apify'" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-800 border border-indigo-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                                Apify Provider (Staging & Review)
                            </span>
                            <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                Official LinkedIn Provider
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            {{ provider === 'apify'
                                ? 'Sinkronisasi profil publik LinkedIn via Apify Actor (per alumni) dengan alur peninjauan (staging & review) sebelum diterapkan ke data utama.'
                                : 'Simulasi sinkronisasi profil karier alumni UKDW mengacu pada standar resmi LinkedIn Profile & Position Fields.' }}
                        </p>
                    </div>

                    <!-- TOMBOL AKSI CEPAT BULK SYNC (HANYA AKTIF UNTUK OFFICIAL PROVIDER) -->
                    <div v-if="provider !== 'apify'" class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="handleBulkSync"
                            :disabled="isBulkSyncing"
                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold text-white bg-[#0D542B] hover:bg-[#093c1f] focus:ring-2 focus:ring-emerald-700 focus:outline-none transition-colors shadow-2xs disabled:opacity-50 cursor-pointer"
                        >
                            <svg v-if="!isBulkSyncing" class="w-4 h-4 shrink-0 fill-current" viewBox="0 0 24 24">
                                <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                            </svg>
                            <svg v-else class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>{{ isBulkSyncing ? `Menyinkronkan... (${bulkProgress.percentage}%)` : `Sinkronkan Tahun ${selectedTahun}` }}</span>
                        </button>
                    </div>
                </div>

                <!-- PROGRESS BAR BULK SYNC (JIKA SEDANG BERJALAN) -->
                <div v-if="isBulkSyncing" class="bg-white p-3.5 rounded-xl border border-emerald-200 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between text-xs font-semibold text-slate-700">
                        <span class="flex items-center gap-1.5 text-emerald-800">
                            <span class="w-2 h-2 rounded-full bg-emerald-600 animate-ping"></span>
                            Memproses sinkronisasi massal angkatan {{ selectedTahun }}...
                        </span>
                        <span class="text-slate-600 font-mono">{{ bulkProgress.current }} / {{ bulkProgress.total }} Alumni ({{ bulkProgress.percentage }}%)</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div
                            class="bg-[#0D542B] h-2 rounded-full transition-all duration-300"
                            :style="{ width: `${bulkProgress.percentage}%` }"
                        ></div>
                    </div>
                </div>

                <!-- 5 KARTU METRIK SUMMARY KPI -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    <!-- 1. Total Alumni -->
                    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">
                            Total Alumni
                        </span>
                        <div class="flex items-baseline justify-between mt-1.5">
                            <span class="text-xl font-bold text-slate-900 font-mono">{{ localStats.total_alumni }}</span>
                            <span class="text-[10px] text-slate-400">Lulus {{ selectedTahun }}</span>
                        </div>
                    </div>

                    <!-- 2. Dengan LinkedIn -->
                    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
                        <span class="text-[11px] font-semibold text-sky-700 uppercase tracking-wider block">
                            Dengan LinkedIn
                        </span>
                        <div class="flex items-baseline justify-between mt-1.5">
                            <span class="text-xl font-bold text-sky-900 font-mono">{{ localStats.dengan_linkedin }}</span>
                            <span class="text-[10px] text-sky-600">{{ provider === 'apify' ? 'Punya URL' : 'Punya username' }}</span>
                        </div>
                    </div>

                    <!-- 3. Berhasil / Approved -->
                    <div class="bg-white p-3.5 rounded-xl border border-emerald-100 bg-emerald-50/20 shadow-2xs">
                        <span class="text-[11px] font-semibold text-emerald-800 uppercase tracking-wider block">
                            {{ provider === 'apify' ? 'Approved' : 'Berhasil Sinkron' }}
                        </span>
                        <div class="flex items-baseline justify-between mt-1.5">
                            <span class="text-xl font-bold text-emerald-900 font-mono">{{ localStats.berhasil_sinkron }}</span>
                            <span class="text-[10px] text-emerald-700 font-medium">Data terpetakan</span>
                        </div>
                    </div>

                    <!-- 4. Gagal / Ditolak -->
                    <div class="bg-white p-3.5 rounded-xl border border-rose-100 bg-rose-50/20 shadow-2xs">
                        <span class="text-[11px] font-semibold text-rose-800 uppercase tracking-wider block">
                            {{ provider === 'apify' ? 'Rejected / Gagal' : 'Gagal' }}
                        </span>
                        <div class="flex items-baseline justify-between mt-1.5">
                            <span class="text-xl font-bold text-rose-900 font-mono">{{ localStats.gagal_sinkron }}</span>
                            <span class="text-[10px] text-rose-600">Error / Ditolak</span>
                        </div>
                    </div>

                    <!-- 5. Dilewati / Pending Review -->
                    <div class="bg-white p-3.5 rounded-xl border border-amber-100 bg-amber-50/20 shadow-2xs col-span-2 sm:col-span-1">
                        <span class="text-[11px] font-semibold text-amber-800 uppercase tracking-wider block">
                            {{ provider === 'apify' ? 'Pending Review' : 'Dilewati' }}
                        </span>
                        <div class="flex items-baseline justify-between mt-1.5">
                            <span class="text-xl font-bold text-amber-900 font-mono">
                                {{ provider === 'apify' ? (localStats.pending_review || 0) : localStats.dilewati }}
                            </span>
                            <span class="text-[10px] text-amber-600">
                                {{ provider === 'apify' ? 'Menunggu Super Admin' : 'Tanpa username' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- FILTER TAHUN KELULUSAN & PENCARIAN ALUMNI -->
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <div class="flex items-center gap-2">
                            <label for="filter-tahun" class="text-xs font-semibold text-slate-700 shrink-0">
                                Tahun Kelulusan:
                            </label>
                            <select
                                id="filter-tahun"
                                v-model="selectedTahun"
                                class="text-xs font-medium text-slate-900 bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-600 focus:outline-none"
                            >
                                <option v-for="th in daftarTahun" :key="th" :value="th">
                                    Tahun {{ th }}
                                </option>
                            </select>
                        </div>

                        <button
                            type="button"
                            @click="applyTahunFilter"
                            class="px-3.5 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300 transition-colors cursor-pointer"
                        >
                            Tampilkan
                        </button>
                    </div>

                    <!-- Pencarian Nama / NIM Cepat -->
                    <div class="relative w-full md:w-72">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari NIM, nama, perusahaan..."
                            class="w-full pl-8 pr-3 py-2 text-xs bg-slate-50 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-600 focus:outline-none"
                        />
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- TABEL DATA ALUMNI -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-700">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[10px] tracking-wider">
                                <tr>
                                    <th class="px-4 py-3">NIM</th>
                                    <th class="px-4 py-3">Nama Alumni</th>
                                    <th class="px-3 py-3 text-center">Tahun Lulus</th>
                                    <th class="px-4 py-3">{{ provider === 'apify' ? 'LinkedIn URL' : 'LinkedIn Username' }}</th>
                                    <th class="px-4 py-3">Perusahaan</th>
                                    <th class="px-4 py-3">Posisi / Jabatan</th>
                                    <th class="px-3 py-3 text-center">Status Sync</th>
                                    <th class="px-3 py-3 text-center">Terakhir Sync</th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-if="paginatedAlumni.length === 0">
                                    <td colspan="9" class="px-4 py-8 text-center text-slate-400">
                                        Tidak ada data alumni yang cocok dengan kriteria filter.
                                    </td>
                                </tr>

                                <tr
                                    v-for="alumni in paginatedAlumni"
                                    :key="alumni.id"
                                    :id="'alumni-row-' + alumni.id"
                                    :class="[
                                        alumni.id === highlightedAlumniId
                                            ? 'bg-emerald-50/80 ring-2 ring-emerald-500 transition-all duration-300'
                                            : 'hover:bg-slate-50/75 transition-colors'
                                    ]"
                                >
                                    <!-- NIM -->
                                    <td class="px-4 py-3 font-mono font-medium text-slate-900">
                                        {{ alumni.nim }}
                                    </td>

                                    <!-- Nama -->
                                    <td class="px-4 py-3 font-semibold text-slate-900">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span>{{ alumni.nama }}</span>
                                            <span 
                                                v-if="alumni.id === highlightedAlumniId" 
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-[#0D542B] text-white shadow-xs animate-pulse"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Profil Dipilih
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Tahun Kelulusan -->
                                    <td class="px-3 py-3 text-center font-mono text-slate-600">
                                        {{ alumni.tahun_lulus }}
                                    </td>

                                    <!-- LinkedIn Field -->
                                    <td class="px-4 py-3">
                                        <div v-if="alumni.linkedin_url" class="flex items-center gap-1.5 max-w-[200px]">
                                            <a
                                                :href="alumni.linkedin_url"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-sky-700 hover:text-sky-900 hover:underline truncate text-[11px] font-mono flex items-center gap-1"
                                                :title="alumni.linkedin_url"
                                            >
                                                <span>{{ alumni.linkedin_username ? `@${alumni.linkedin_username}` : alumni.linkedin_url }}</span>
                                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                </svg>
                                            </a>
                                        </div>
                                        <span v-else-if="alumni.linkedin_username" class="font-mono text-sky-700">
                                            @{{ alumni.linkedin_username }}
                                        </span>
                                        <span v-else class="text-slate-400 italic text-[11px]">- Belum ada -</span>
                                    </td>

                                    <!-- Perusahaan -->
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ alumni.perusahaan }}
                                    </td>

                                    <!-- Posisi -->
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ alumni.posisi }}
                                    </td>

                                    <!-- Status Sync (Badge Warna) -->
                                    <td class="px-3 py-3 text-center whitespace-nowrap">
                                        <!-- Menyinkronkan -->
                                        <span
                                            v-if="alumni.isSyncing"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 animate-pulse"
                                        >
                                            <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                            </svg>
                                            Menyinkronkan...
                                        </span>

                                        <!-- Pending Review (Apify) -->
                                        <span
                                            v-else-if="alumni.status_sync === 'Pending Review'"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-900 border border-amber-300"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                            Pending Review
                                        </span>

                                        <!-- Berhasil / Approved -->
                                        <span
                                            v-else-if="alumni.status_sync === 'Berhasil' || alumni.status_sync === 'Approved'"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                            {{ alumni.status_sync }}
                                        </span>

                                        <!-- Gagal / Rejected -->
                                        <span
                                            v-else-if="alumni.status_sync === 'Gagal' || alumni.status_sync === 'Rejected'"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-800 border border-rose-200"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                            {{ alumni.status_sync }}
                                        </span>

                                        <!-- Belum Ada URL / Username -->
                                        <span
                                            v-else-if="alumni.status_sync === 'Belum Ada URL LinkedIn' || (!alumni.linkedin_username && provider !== 'apify')"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500 border border-slate-200"
                                        >
                                            {{ provider === 'apify' ? 'Tanpa URL' : 'Tanpa Username' }}
                                        </span>

                                        <!-- Belum Disinkronkan -->
                                        <span
                                            v-else
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200"
                                        >
                                            Belum Disinkronkan
                                        </span>
                                    </td>

                                    <!-- Terakhir Sync -->
                                    <td class="px-3 py-3 text-center whitespace-nowrap font-mono text-[11px] text-slate-500">
                                        <span :title="alumni.terakhir_sync_human">{{ alumni.terakhir_sync }}</span>
                                    </td>

                                    <!-- Aksi Individual -->
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <!-- Mode Apify -->
                                        <div v-if="provider === 'apify'" class="flex items-center justify-center gap-1.5">
                                            <!-- Tombol Detail Alumni (Mata) -->
                                            <Link
                                                :href="'/superadmin/alumni/' + alumni.id"
                                                class="inline-flex items-center justify-center p-1.5 text-slate-600 hover:text-[#0D542B] hover:bg-emerald-50 rounded-lg border border-slate-200 transition-colors cursor-pointer shadow-2xs"
                                                title="Lihat Detail Alumni"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </Link>

                                            <!-- Tombol Tinjau (Review) jika status pending -->
                                            <button
                                                v-if="alumni.latest_sync_result && alumni.latest_sync_result.status === 'pending'"
                                                type="button"
                                                @click="openReviewModal(alumni.latest_sync_result.id, alumni)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[11px] font-bold bg-amber-500 text-white hover:bg-amber-600 shadow-2xs cursor-pointer transition-colors"
                                                title="Tinjau hasil scraping Apify"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                <span>Tinjau</span>
                                            </button>

                                            <!-- Tombol Sinkronisasi LinkedIn -->
                                            <button
                                                type="button"
                                                @click="syncSingle(alumni)"
                                                :disabled="alumni.isSyncing || !alumni.linkedin_url"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[11px] font-semibold transition-colors shadow-2xs disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                                                :class="[
                                                    alumni.linkedin_url
                                                        ? 'bg-[#0D542B] text-white hover:bg-[#093c1f]'
                                                        : 'bg-slate-100 text-slate-400'
                                                ]"
                                                :title="alumni.latest_sync_result ? 'Sinkron Ulang (Snapshot Baru)' : 'Sinkronkan Profil LinkedIn via Apify'"
                                            >
                                                <svg v-if="!alumni.isSyncing" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                                <svg v-else class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                </svg>
                                                <span>{{ alumni.latest_sync_result ? 'Sync Ulang' : 'Sinkronkan' }}</span>
                                            </button>

                                            <!-- Tombol Riwayat -->
                                            <button
                                                v-if="alumni.latest_sync_result"
                                                type="button"
                                                @click="openHistoryModal(alumni)"
                                                class="p-1.5 text-slate-500 hover:text-slate-800 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
                                                title="Lihat Histori Sinkronisasi"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Mode Official Provider (Existing) -->
                                        <div v-else class="flex items-center justify-center gap-1.5">
                                            <!-- Tombol Detail Alumni (Mata) -->
                                            <Link
                                                :href="'/superadmin/alumni/' + alumni.id"
                                                class="inline-flex items-center justify-center p-1.5 text-slate-600 hover:text-[#0D542B] hover:bg-emerald-50 rounded-lg border border-slate-200 transition-colors cursor-pointer shadow-2xs"
                                                title="Lihat Detail Alumni"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </Link>

                                            <button
                                                type="button"
                                                @click="syncSingle(alumni)"
                                                :disabled="alumni.isSyncing || !alumni.linkedin_username || isBulkSyncing"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[11px] font-semibold transition-colors shadow-2xs disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                                                :class="[
                                                    alumni.linkedin_username
                                                        ? 'bg-[#0D542B] text-white hover:bg-[#093c1f]'
                                                        : 'bg-slate-100 text-slate-400'
                                                ]"
                                            >
                                                <svg v-if="!alumni.isSyncing" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                                <svg v-else class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                </svg>
                                                <span>Sinkronkan</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- FOOTER & PAGINASI LOKAL -->
                    <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600">
                        <div>
                            Menampilkan <b>{{ paginatedAlumni.length }}</b> dari <b>{{ filteredAlumni.length }}</b> alumni
                            <span v-if="filteredAlumni.length !== alumniList.length" class="text-slate-400">
                                (difilter dari total {{ alumniList.length }})
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="text-slate-500">Per halaman:</span>
                            <select
                                v-model="perPage"
                                class="text-xs bg-white border border-slate-300 rounded px-2 py-1 focus:ring-1 focus:ring-emerald-600 focus:outline-none"
                            >
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                            </select>

                            <div class="flex items-center gap-1 ml-2">
                                <button
                                    type="button"
                                    @click="currentPage = Math.max(1, currentPage - 1)"
                                    :disabled="currentPage === 1"
                                    class="px-2 py-1 rounded border border-slate-300 bg-white hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                                >
                                    &laquo;
                                </button>
                                <span class="px-2 font-mono">{{ currentPage }} / {{ totalPages }}</span>
                                <button
                                    type="button"
                                    @click="currentPage = Math.min(totalPages, currentPage + 1)"
                                    :disabled="currentPage === totalPages"
                                    class="px-2 py-1 rounded border border-slate-300 bg-white hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                                >
                                    &raquo;
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>

        <!-- ================================================================= -->
        <!-- MODAL PENINJAUAN (REVIEW STAGING MODAL)                           -->
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
                                Tinjau Hasil Sinkronisasi LinkedIn (Apify)
                            </h3>
                            <span
                                v-if="activeReview"
                                class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                :class="[
                                    activeReview.status === 'pending'
                                        ? 'bg-amber-100 text-amber-800'
                                        : (activeReview.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800')
                                ]"
                            >
                                Status: {{ activeReview.status }}
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
                    <!-- Loading State -->
                    <div v-if="isReviewLoading" class="py-16 text-center space-y-3">
                        <div class="inline-block w-8 h-8 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
                        <p class="text-xs text-slate-500">Memuat rincian hasil scraping Apify...</p>
                    </div>

                    <template v-else-if="activeReview">
                        <!-- BANNER PERINGATAN WAJIB -->
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3.5 flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div class="text-xs">
                                <p class="font-bold text-amber-900">
                                    Hasil sinkronisasi LinkedIn dari provider Apify dan belum menjadi data utama.
                                </p>
                                <p class="text-amber-700 mt-0.5 leading-relaxed">
                                    Data ini tersimpan sementara di tabel staging (<code class="font-mono bg-amber-100/70 px-1 py-0.5 rounded">linkedin_sync_results</code>).
                                    Data profil utama alumni di database kampus <b>tidak akan berubah</b> sampai Anda menekan tombol <b>Setujui (Approve)</b>.
                                </p>
                            </div>
                        </div>

                        <!-- KOMPARASI CEPAT DATA SAAT INI VS FORM PENYESUAIAN USULAN -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                                    Penyesuaian Data Profil Sebelum Disetujui
                                </h4>
                                <span class="text-[11px] text-slate-500">
                                    Data di bawah dapat Anda sesuaikan sebelum disimpan ke profil utama alumni
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

                                <!-- Form Penyesuaian Usulan (Dapat Diedit / Custom) -->
                                <div class="lg:col-span-2 bg-emerald-50/60 p-3.5 rounded-lg border border-emerald-200 space-y-2.5">
                                    <div class="flex items-center justify-between flex-wrap gap-2">
                                        <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider">
                                            Data yang Akan Disimpan ke Profil (Bisa Diedit / Custom):
                                        </span>
                                        <!-- Toggle Kategori Pekerja vs Wirausaha -->
                                        <div class="inline-flex rounded-lg border border-emerald-300 p-0.5 bg-white text-[11px] shadow-2xs">
                                            <button
                                                type="button"
                                                @click="approveForm.tipe_pekerjaan = 'pekerja'"
                                                :class="[approveForm.tipe_pekerjaan === 'pekerja' ? 'bg-[#0D542B] text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900']"
                                                class="px-2.5 py-1 rounded-md transition-all cursor-pointer"
                                            >
                                                💼 Karyawan / Pekerja
                                            </button>
                                            <button
                                                type="button"
                                                @click="approveForm.tipe_pekerjaan = 'wirausaha'"
                                                :class="[approveForm.tipe_pekerjaan === 'wirausaha' ? 'bg-[#0D542B] text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900']"
                                                class="px-2.5 py-1 rounded-md transition-all cursor-pointer"
                                            >
                                                🚀 Wirausaha / Bisnis
                                            </button>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                        <!-- Posisi Jabatan / Posisi Wiraswasta -->
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
                                                placeholder="contoh: Software Engineer, Wakil Dekan I"
                                                class="w-full px-2.5 py-1.5 text-xs bg-white border border-emerald-300 rounded-md focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B]"
                                            />
                                        </div>

                                        <!-- Nama Perusahaan / Bisnis -->
                                        <div>
                                            <label class="block text-[11px] font-semibold text-emerald-950 mb-1">
                                                {{ approveForm.tipe_pekerjaan === 'wirausaha' ? 'Nama Unit Usaha / Bisnis:' : 'Nama Perusahaan / Instansi:' }}
                                            </label>
                                            <input
                                                type="text"
                                                v-model="approveForm.nama_perusahaan"
                                                placeholder="contoh: Universitas Kristen Duta Wacana"
                                                class="w-full px-2.5 py-1.5 text-xs bg-white border border-emerald-300 rounded-md focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B]"
                                            />
                                        </div>
                                    </div>

                                    <!-- Opsi Sinkronisasi Foto Profil LinkedIn ke Biodata -->
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
                                                <span class="text-[10px] text-slate-500 block truncate">Foto akan disimpan permanen ke <code class="font-mono bg-slate-100 px-1 rounded">/uploads/profile/</code></span>
                                            </div>
                                        </div>
                                        <label class="flex items-center gap-1.5 cursor-pointer shrink-0 bg-emerald-50 px-2 py-1 rounded border border-emerald-200 hover:bg-emerald-100/60 transition-colors">
                                            <input
                                                type="checkbox"
                                                v-model="approveForm.sync_foto"
                                                class="rounded text-[#0D542B] focus:ring-[#0D542B] w-4 h-4 cursor-pointer"
                                            />
                                            <span class="text-xs font-semibold text-emerald-900">Gunakan</span>
                                        </label>
                                    </div>

                                    <p class="text-[10px] text-amber-800 bg-amber-50/90 border border-amber-200 px-2 py-1.5 rounded-md flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Perusahaan ini akan <b>otomatis masuk ke tabel master perusahaan</b> dengan status <b>Menunggu Verifikasi</b> agar dapat diverifikasi lebih lanjut oleh Admin.</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- RINCIAN PROFIL SCRAPED AKTUAL -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                                Rincian Profil Hasil Scraping
                            </h4>

                            <div class="bg-white p-4 rounded-xl border border-slate-200 space-y-3 text-xs">
                                <div class="flex items-start gap-3 pb-3 border-b border-slate-100">
                                    <img
                                        v-if="activeReview.preview.profile_picture"
                                        :src="activeReview.preview.profile_picture"
                                        alt="Foto Profil LinkedIn"
                                        class="w-14 h-14 rounded-full object-cover border border-slate-200 shrink-0 shadow-2xs"
                                    />
                                    <div v-else class="w-14 h-14 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 font-bold shrink-0">
                                        {{ (activeReview.preview.name || 'A').charAt(0) }}
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h3 class="font-bold text-slate-900 text-sm">
                                                {{ activeReview.preview.name || '-' }}
                                            </h3>
                                            <span v-if="activeReview.preview.followers" class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-medium">
                                                {{ activeReview.preview.followers }} Pengikut
                                            </span>
                                            <span v-if="activeReview.preview.connections" class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-medium">
                                                {{ activeReview.preview.connections }} Koneksi
                                            </span>
                                        </div>
                                        <p class="text-slate-600 text-xs mt-0.5">{{ activeReview.preview.headline || '-' }}</p>
                                        <p class="text-slate-400 text-[11px] mt-0.5">{{ activeReview.preview.location || '-' }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <div>
                                        <span class="text-slate-400 block text-[11px]">URL LinkedIn:</span>
                                        <a
                                            :href="activeReview.linkedin_url"
                                            target="_blank"
                                            class="text-sky-600 hover:underline font-mono truncate block text-xs"
                                        >
                                            {{ activeReview.linkedin_url }}
                                        </a>
                                    </div>
                                    <div v-if="activeReview.preview.current_company_url">
                                        <span class="text-slate-400 block text-[11px]">URL Perusahaan / Kampus:</span>
                                        <a
                                            :href="activeReview.preview.current_company_url"
                                            target="_blank"
                                            class="text-sky-600 hover:underline font-mono truncate block text-xs"
                                        >
                                            {{ activeReview.preview.current_company_url }}
                                        </a>
                                    </div>
                                </div>

                                <div v-if="activeReview.preview.about" class="pt-2 border-t border-slate-100">
                                    <span class="text-slate-400 block text-[11px] mb-1">About / Ringkasan:</span>
                                    <p class="text-slate-700 leading-relaxed bg-slate-50 p-2.5 rounded-lg border border-slate-100 font-sans">
                                        {{ activeReview.preview.about }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- PENGALAMAN KERJA (EXPERIENCE) -->
                        <div v-if="activeReview.preview.experience && activeReview.preview.experience.length > 0" class="space-y-2.5">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center justify-between">
                                <span>Riwayat Pengalaman Kerja ({{ activeReview.preview.experience.length }})</span>
                            </h4>

                            <div class="space-y-2">
                                <div
                                    v-for="(exp, idx) in activeReview.preview.experience"
                                    :key="idx"
                                    class="bg-white p-3 rounded-xl border border-slate-200 text-xs flex items-start justify-between gap-3"
                                >
                                    <div class="flex items-start gap-3 min-w-0">
                                        <img
                                            v-if="exp.company_logo"
                                            :src="exp.company_logo"
                                            alt="Logo Perusahaan"
                                            class="w-9 h-9 rounded-md object-contain border border-slate-100 shrink-0 mt-0.5"
                                        />
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
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
                                            <p v-if="exp.location" class="text-slate-400 text-[11px] mt-0.5">{{ exp.location }}</p>
                                            <p v-if="exp.description" class="text-slate-500 text-[11px] mt-1 bg-slate-50 p-2 rounded">
                                                {{ exp.description }}
                                            </p>
                                        </div>
                                    </div>
                                    <span class="text-slate-400 text-[11px] font-mono shrink-0">
                                        {{ exp.date || (exp.start_date ? `${exp.start_date} - ${exp.end_date || 'Sekarang'}` : (exp.is_current ? 'Sekarang' : '-')) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- PENDIDIKAN (EDUCATION) -->
                        <div v-if="activeReview.preview.education && activeReview.preview.education.length > 0" class="space-y-2.5">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                                Riwayat Pendidikan ({{ activeReview.preview.education.length }})
                            </h4>

                            <div class="space-y-2">
                                <div
                                    v-for="(edu, idx) in activeReview.preview.education"
                                    :key="idx"
                                    class="bg-white p-3 rounded-xl border border-slate-200 text-xs flex items-start justify-between gap-3"
                                >
                                    <div>
                                        <p class="font-bold text-slate-900">{{ edu.school || 'Institusi Pendidikan' }}</p>
                                        <p class="text-slate-600 text-[11px] mt-0.5">
                                            {{ [edu.degree, edu.field_of_study].filter(Boolean).join(' - ') || '-' }}
                                        </p>
                                    </div>
                                    <span v-if="edu.date || edu.start_date || edu.end_date" class="text-slate-400 text-[11px] font-mono shrink-0">
                                        {{ edu.date || `${edu.start_date || '?'} - ${edu.end_date || '?'}` }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- KEAHLIAN (SKILLS) -->
                        <div v-if="activeReview.preview.skills && activeReview.preview.skills.length > 0" class="space-y-2">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                                Keahlian / Skills ({{ activeReview.preview.skills.length }})
                            </h4>
                            <div class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="(skill, idx) in activeReview.preview.skills"
                                    :key="idx"
                                    class="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200"
                                >
                                    {{ skill }}
                                </span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Footer Aksi Modal -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                    <div>
                        <span v-if="activeReview && activeReview.status !== 'pending'" class="text-xs text-slate-500 italic">
                            Catatan: Data ini sudah di-review ({{ activeReview.status }}) oleh {{ activeReview.reviewer_name || 'Super Admin' }} pada {{ activeReview.reviewed_at }}.
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

                        <!-- Tombol Tolak & Setujui hanya aktif jika status PENDING -->
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
