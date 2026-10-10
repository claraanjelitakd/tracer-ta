<!--
  Halaman Direktori Mahasiswa & Alumni (Biro 3)
  File: resources/js/Pages/AdminBiroTiga/AlumniIndex.vue
  
  Fitur Terpadu Khusus Biro 3:
  - Tampilan Status & Progres Tracer Lengkap (Profil, Kuesioner, Evaluasi Atasan) seragam dengan Super Admin
  - 4 Kartu Statistik dengan indikator kelulusan kuesioner resmi
  - Filter komprehensif termasuk Target Kelulusan & default tahun terbaru
  - Unduh Arsip ZIP Excel per Program Studi & Kirim Email Massal
  - Aksi Terpadu: Detail (Read-Only), WhatsApp, Email Flash, Sinkronisasi LinkedIn, & Riwayat Scraping
-->
<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Swal from 'sweetalert2';
import Sidebar from './Components/Sidebar.vue';

const props = defineProps({
    user: Object,
    alumnis: {
        type: Array,
        default: () => [],
    },
    daftarTahun: {
        type: Array,
        default: () => [],
    },
    daftarTarget: {
        type: Array,
        default: () => [],
    },
    prodis: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({
            total_alumni: 0,
            total_selesai: 0,
            total_belum_selesai: 0,
            persentase_selesai: 0,
        }),
    },
});

// State Filter (Default Tahun: Terplot ke tahun terbaru dari daftarTahun)
const defaultTahun = props.filters.tahun || props.daftarTahun[0] || '';
const search = ref(props.filters.search || '');
const tahun = ref(defaultTahun);
const semester = ref(props.filters.semester || 'all');
const target = ref(props.filters.target || 'all');
const status = ref(props.filters.status || 'all');
const prodiId = ref(props.filters.prodi_id || 'all');

// Sinkronkan state filter jika server mengembalikan props filters baru
watch(() => props.filters, (newFilters) => {
    if (newFilters) {
        if (newFilters.tahun !== undefined) {
            tahun.value = newFilters.tahun;
        }
        if (newFilters.semester !== undefined) {
            semester.value = newFilters.semester || 'all';
        }
        if (newFilters.target !== undefined) {
            target.value = newFilters.target || 'all';
        }
        if (newFilters.status !== undefined) {
            status.value = newFilters.status || 'all';
        }
        if (newFilters.prodi_id !== undefined) {
            prodiId.value = newFilters.prodi_id || 'all';
        }
    }
}, { deep: true });

// Pagination lokal ala DataTables
const perPage = ref(10);
const currentPage = ref(1);

const totalPages = computed(() => {
    return Math.ceil(props.alumnis.length / perPage.value) || 1;
});

const paginatedAlumnis = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return props.alumnis.slice(start, start + perPage.value);
});

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

// Helper inisial avatar
const getInitials = (name) => {
    if (!name) return 'M';
    const clean = name.trim().replace(/[^a-zA-Z\s]/g, '');
    const parts = clean.split(/\s+/).filter(Boolean);
    if (parts.length === 0) return 'M';
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
};

// Terapkan Filter ke URL
let searchTimeout = null;
const applyFilters = () => {
    currentPage.value = 1;
    router.get('/biro3/alumni', {
        search: search.value || undefined,
        tahun: tahun.value || undefined,
        semester: semester.value !== 'all' ? semester.value : undefined,
        target: target.value !== 'all' ? target.value : undefined,
        status: status.value !== 'all' ? status.value : undefined,
        prodi_id: prodiId.value !== 'all' ? prodiId.value : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const handleSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
};

// Reset Filter: Mengembalikan ke tahun kelulusan terbaru
const resetFilters = () => {
    search.value = '';
    tahun.value = props.daftarTahun[0] || '';
    semester.value = 'all';
    target.value = 'all';
    status.value = 'all';
    prodiId.value = 'all';
    currentPage.value = 1;
    router.get('/biro3/alumni', { tahun: tahun.value }, { preserveState: true });
};

// =========================================================================
// FITUR UNDUH ZIP & BLAST EMAIL MASSAL BIRO 3
// =========================================================================

// Unduh Arsip ZIP Excel per Program Studi
const handleDownloadZip = () => {
    const selectedYear = tahun.value || props.daftarTahun[0] || '';

    if (prodiId.value && prodiId.value !== 'all') {
        const url = `/biro3/alumni/export-zip?prodi_id=${prodiId.value}&tahun=${encodeURIComponent(selectedYear)}`;
        window.location.href = url;
    } else {
        const prodiOptions = {};
        props.prodis.forEach((p) => {
            prodiOptions[p.id] = `${p.kode_prodi} - ${p.nama_prodi}`;
        });

        Swal.fire({
            title: 'Unduh Arsip ZIP Excel',
            text: `Pilih Program Studi untuk mengunduh rekap Excel (.xls) seluruh alumni pada Tahun Kelulusan ${selectedYear || 'Terbaru'}:`,
            input: 'select',
            inputOptions: prodiOptions,
            inputPlaceholder: '-- Pilih Program Studi --',
            showCancelButton: true,
            confirmButtonText: 'Unduh ZIP Sekarang',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#0D542B',
            cancelButtonColor: '#94a3b8',
            inputValidator: (val) => {
                if (!val) {
                    return 'Silakan pilih Program Studi terlebih dahulu!';
                }
            },
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                const url = `/biro3/alumni/export-zip?prodi_id=${result.value}&tahun=${encodeURIComponent(selectedYear)}`;
                window.location.href = url;
            }
        });
    }
};

// Kirim Email Massal ke Seluruh Alumni per Tahun Kelulusan
const handleBlastEmail = () => {
    const targetTahun = tahun.value || props.daftarTahun[0] || '';

    if (!targetTahun) {
        Swal.fire({
            icon: 'warning',
            title: 'Pilih Tahun Kelulusan',
            text: 'Silakan tentukan Tahun Kelulusan terlebih dahulu sebelum mengirim email massal.',
            confirmButtonColor: '#0D542B',
        });
        return;
    }

    Swal.fire({
        title: `Kirim Email Massal (${targetTahun})`,
        html: `Apakah Anda yakin ingin mengirim email pengingat kuesioner ke <b>seluruh alumni lulusan tahun ${targetTahun}</b> yang belum menyelesaikan pengisian tracer study?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0D542B',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: `Ya, Kirim Massal (${targetTahun})`,
        cancelButtonText: 'Batal',
    }).then((res) => {
        if (res.isConfirmed) {
            Swal.fire({
                title: 'Memproses Pengiriman Massal...',
                text: 'Sistem sedang mengirim email pengingat ke seluruh antrean alumni.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
            });

            router.post('/biro3/alumni/blast-email', { tahun: targetTahun }, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Pengiriman Massal Selesai',
                        text: `Email pengingat berhasil diproses untuk seluruh alumni angkatan ${targetTahun}.`,
                        confirmButtonColor: '#0D542B',
                    });
                },
                onError: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memproses',
                        text: 'Terjadi kesalahan teknis saat mengirim email massal.',
                        confirmButtonColor: '#0D542B',
                    });
                },
            });
        }
    });
};

// =========================================================================
// FITUR AKSI: WHATSAPP, EMAIL FLASH, & SINKRONISASI LINKEDIN IN-PLACE
// =========================================================================

// 1. WhatsApp Personal Modal
const showWaModal = ref(false);
const activeWaAlumni = ref(null);
const waRecipientPhone = ref('');
const waMessageText = ref('');

const formatPhoneForWA = (rawPhone) => {
    if (!rawPhone) return '';
    let cleaned = String(rawPhone).replace(/\D/g, '');
    if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.slice(1);
    } else if (cleaned.startsWith('8')) {
        cleaned = '62' + cleaned;
    }
    return cleaned;
};

const openWhatsAppModal = (alumni) => {
    activeWaAlumni.value = alumni;
    waRecipientPhone.value = alumni.nomor_telepon || '';
    
    const baseUrl = window.location.origin;
    const loginUrl = `${baseUrl}/login`;

    waMessageText.value = `Halo Sdr/i ${alumni.nama},\n\nSalam hangat dari Biro III Kemahasiswaan & Alumni Universitas Kristen Duta Wacana (UKDW).\n\nKami mengundang Anda untuk melengkapi instrumen kuesioner Tracer Study UKDW (Kemendikbudristek Dikti) melalui portal resmi kami:\n👉 ${loginUrl}\n\n*Panduan Masuk ke Portal:*\n• Username: *${alumni.nim}* (NIM Anda)\n• Password: Kata sandi akun Tracer Study Anda\n\nPartisipasi Anda sangat berarti bagi pengembangan kurikulum dan peningkatan mutu almamater kita tercinta. Jika memerlukan bantuan, silakan balas pesan ini.\n\nTerima kasih banyak atas kerjasamanya!\nSalam,\nBiro 3 UKDW`;

    showWaModal.value = true;
};

const executeSendWa = () => {
    const formatted = formatPhoneForWA(waRecipientPhone.value);
    if (!formatted) {
        Swal.fire({
            icon: 'warning',
            title: 'Nomor WhatsApp Tidak Ditemukan',
            text: 'Nomor telepon/WhatsApp alumni ini belum terdaftar di sistem. Silakan masukkan nomor terlebih dahulu.',
            confirmButtonColor: '#0D542B',
        });
        return;
    }
    const url = `https://wa.me/${formatted}?text=${encodeURIComponent(waMessageText.value)}`;
    window.open(url, '_blank');
    showWaModal.value = false;
};

// 2. Kirim Email Pengingat Satuan (Flash Direct)
const handleSendSingleEmail = (alumni) => {
    if (!alumni.email) {
        Swal.fire({
            icon: 'warning',
            title: 'Email Tidak Terdaftar',
            text: `Alumni ${alumni.nama} (${alumni.nim}) belum memiliki alamat email yang tersimpan di sistem.`,
            confirmButtonColor: '#0D542B',
        });
        return;
    }

    Swal.fire({
        title: 'Kirim Email Pengingat?',
        html: `<p class="text-xs text-slate-600">Kirim email resmi pengingat pengisian tracer study beserta panduan login ke <b>${alumni.nama}</b> (<span class="font-mono text-emerald-800">${alumni.email}</span>)?</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0D542B',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Ya, Kirim Email',
        cancelButtonText: 'Batal',
    }).then((res) => {
        if (res.isConfirmed) {
            Swal.fire({
                title: 'Mengirim Email...',
                text: 'Mohon tunggu sebentar, sistem sedang memproses pengiriman.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
            });

            router.post(`/biro3/alumni/${alumni.id}/send-email`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Email Terkirim',
                        text: `Email pengingat resmi berhasil dikirimkan ke ${alumni.nama} (${alumni.email}).`,
                        confirmButtonColor: '#0D542B',
                    });
                },
                onError: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Mengirim Email',
                        text: 'Terjadi kendala pada server saat memproses pengiriman email.',
                        confirmButtonColor: '#0D542B',
                    });
                },
            });
        }
    });
};
</script>

<template>
    <Head title="Direktori Mahasiswa & Alumni - Biro 3 UKDW" />

    <div class="min-h-screen bg-[#f8fafc] text-slate-800 font-sans flex">
        <!-- Sidebar Resmi Biro 3 -->
        <Sidebar :user="user" />

        <!-- Area Konten Utama -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-72">
            
            <!-- Header Solid Hijau Resmi UKDW #0D542B -->
            <header class="bg-[#0D542B] text-white pt-8 pb-16 px-4 sm:px-6 lg:px-8 shadow-xs border-b border-[#0A4322]">
                <div class="w-full max-w-[1440px] mx-auto flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2 text-xs text-white/80 font-medium mb-2">
                            <Link href="/biro3/dashboard" class="hover:underline">Dashboard</Link>
                            <span>/</span>
                            <span class="text-white font-bold bg-white/10 px-2.5 py-0.5 rounded-md">Data Alumni</span>
                        </div>

                        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white tracking-tight">
                            Direktori Mahasiswa & Alumni
                        </h1>
                        <p class="text-emerald-100/90 text-xs sm:text-sm font-normal mt-1.5 max-w-2xl leading-relaxed">
                            Database seluruh lulusan UKDW, audit keterisian kuesioner tracer study Dikti, dan alat komunikasi langsung ke alumni.
                        </p>

                        <!-- Tombol Aksi Cepat Header: Download ZIP & Kirim Email Massal -->
                        <div class="flex items-center gap-3 mt-4 flex-wrap">
                            <!-- Unduh ZIP -->
                            <button 
                                type="button"
                                @click="handleDownloadZip"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-[#FDC700] hover:bg-[#e6b400] text-slate-900 rounded-xl text-xs font-extrabold transition-all shadow-xs cursor-pointer active:scale-95"
                                title="Unduh arsip ZIP berisi berkas Excel (.xls) per alumni untuk Program Studi pada tahun yang difilter"
                            >
                                <svg class="w-4 h-4 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span>Unduh ZIP Excel Prodi</span>
                            </button>

                            <!-- Kirim Email Massal -->
                            <button 
                                type="button"
                                @click="handleBlastEmail"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer active:scale-95"
                                :title="`Kirim email pengingat massal ke seluruh alumni tahun ${tahun !== 'all' ? tahun : (daftarTahun[0] || '')} yang belum selesai`"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span>Kirim Email Massal ({{ tahun !== 'all' ? tahun : (daftarTahun[0] || 'Terbaru') }})</span>
                            </button>
                        </div>
                    </div>

                    <!-- Ringkasan Cepat di Header -->
                    <div class="bg-black/20 border border-white/20 backdrop-blur-xs px-6 py-4 rounded-2xl text-left md:text-right text-white shadow-xs">
                        <div class="flex items-center md:justify-end gap-1.5 text-xs text-emerald-200 font-bold uppercase tracking-wider mb-0.5">
                            <span class="w-2 h-2 rounded-full bg-[#FDC700] animate-pulse"></span>
                            <span>Tingkat Kelulusan Kuesioner</span>
                        </div>
                        <span class="text-3xl lg:text-4xl font-black text-[#FDC700] tracking-tight block">{{ stats.persentase_selesai }}%</span>
                        <span class="text-xs text-emerald-100/90 font-medium block mt-0.5">
                            {{ stats.total_selesai }} dari {{ stats.total_alumni }} Responden Selesai
                        </span>
                    </div>
                </div>
            </header>

            <!-- Main Content Container -->
            <main class="w-full max-w-[1440px] mx-auto -mt-10 px-4 sm:px-6 lg:px-8 space-y-6 pb-16">
                
                <!-- 4 Kartu Statistik Elegan & Modern (Seragam dengan Super Admin) -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                    <!-- Total Mahasiswa -->
                    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs flex items-center justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Mahasiswa</p>
                            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1.5">{{ stats.total_alumni }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">Tahun Kelulusan {{ tahun }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Kuesioner Selesai -->
                    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs flex items-center justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-bold text-[#0D542B] uppercase tracking-wider">Kuesioner Selesai</p>
                            <p class="text-2xl sm:text-3xl font-extrabold text-[#0D542B] tracking-tight mt-1.5">{{ stats.total_selesai }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">Lengkap 100%</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-[#0D542B] flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Belum Selesai -->
                    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs flex items-center justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Belum Selesai</p>
                            <p class="text-2xl sm:text-3xl font-extrabold text-amber-600 tracking-tight mt-1.5">{{ stats.total_belum_selesai }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">Perlu tindak lanjut</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Rasio Penyelesaian -->
                    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs flex items-center justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Rasio Penyelesaian</p>
                            <p class="text-2xl sm:text-3xl font-extrabold text-[#0D542B] tracking-tight mt-1.5">{{ stats.persentase_selesai }}%</p>
                            <p class="text-xs text-slate-400 mt-0.5">Kepatuhan responden</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-[#0D542B] flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Panel Filter Komprehensif (Default Terplot di Tahun Terbaru) -->
                <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs">
                    <!-- Header Filter Panel -->
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-emerald-50 text-[#0D542B] flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <h2 class="text-xs sm:text-sm font-extrabold text-slate-900 uppercase tracking-wider">
                                Filter & Pencarian Mahasiswa
                            </h2>
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-50 text-[#0D542B] border border-emerald-200/60 font-bold inline-flex items-center gap-1">
                                <svg class="w-3 h-3 text-[#0D542B]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>Tahun: {{ tahun }}</span>
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <button 
                                @click="resetFilters" 
                                class="text-xs text-slate-500 hover:text-slate-800 font-bold transition-colors inline-flex items-center gap-1.5 cursor-pointer px-2.5 py-1.5 rounded-lg hover:bg-slate-100"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span>Reset Filter</span>
                            </button>
                        </div>
                    </div>

                    <!-- Grid Form Controls 6 Kolom -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3.5">
                        <!-- Pencarian Nama / NIM -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wide">
                                Cari Nama / NIM
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input 
                                    type="text" 
                                    v-model="search" 
                                    @input="handleSearchInput" 
                                    placeholder="Ketik nama atau NIM..." 
                                    class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/80 py-2.5 pl-9 pr-3 font-medium text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-[#0D542B] focus:ring-2 focus:ring-[#0D542B]/15 outline-none transition-all"
                                />
                            </div>
                        </div>

                        <!-- Filter Tahun Kelulusan -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wide">
                                Tahun Kelulusan <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <select 
                                    v-model="tahun" 
                                    @change="applyFilters" 
                                    class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/80 py-2.5 px-3 font-bold text-slate-900 focus:bg-white focus:border-[#0D542B] focus:ring-2 focus:ring-[#0D542B]/15 outline-none transition-all cursor-pointer"
                                >
                                    <option v-for="t in daftarTahun" :key="t" :value="t">
                                        Tahun {{ t }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Filter Target Kelulusan -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wide">
                                Target Kelulusan
                            </label>
                            <select 
                                v-model="target" 
                                @change="applyFilters" 
                                class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/80 py-2.5 px-3 font-medium text-slate-900 focus:bg-white focus:border-[#0D542B] focus:ring-2 focus:ring-[#0D542B]/15 outline-none transition-all cursor-pointer"
                            >
                                <option value="all">Semua Target</option>
                                <option v-for="tgt in daftarTarget" :key="tgt" :value="tgt">
                                    {{ tgt }}
                                </option>
                            </select>
                        </div>

                        <!-- Filter Semester Kelulusan -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wide">
                                Semester
                            </label>
                            <select 
                                v-model="semester" 
                                @change="applyFilters" 
                                class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/80 py-2.5 px-3 font-medium text-slate-900 focus:bg-white focus:border-[#0D542B] focus:ring-2 focus:ring-[#0D542B]/15 outline-none transition-all cursor-pointer"
                            >
                                <option value="all">Semua Semester</option>
                                <option value="Gasal">Gasal</option>
                                <option value="Genap">Genap</option>
                            </select>
                        </div>

                        <!-- Filter Status Pengisian -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wide">
                                Status Kuesioner
                            </label>
                            <select 
                                v-model="status" 
                                @change="applyFilters" 
                                class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/80 py-2.5 px-3 font-medium text-slate-900 focus:bg-white focus:border-[#0D542B] focus:ring-2 focus:ring-[#0D542B]/15 outline-none transition-all cursor-pointer"
                            >
                                <option value="all">Semua Status</option>
                                <option value="selesai">Selesai (100%)</option>
                                <option value="belum_selesai">Belum Selesai</option>
                            </select>
                        </div>

                        <!-- Filter Program Studi -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wide">
                                Program Studi
                            </label>
                            <select 
                                v-model="prodiId" 
                                @change="applyFilters" 
                                class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/80 py-2.5 px-3 font-medium text-slate-900 focus:bg-white focus:border-[#0D542B] focus:ring-2 focus:ring-[#0D542B]/15 outline-none transition-all cursor-pointer"
                            >
                                <option value="all">Semua Program Studi</option>
                                <option v-for="p in prodis" :key="p.id" :value="p.id">
                                    {{ p.kode_prodi }} - {{ p.nama_prodi }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Card Tabel Mahasiswa ala DataTables Modern -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <!-- Bar Kontrol DataTables -->
                    <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
                        <div class="flex items-center gap-2 text-xs font-medium text-slate-600">
                            <span>Tampilkan</span>
                            <select v-model="perPage" class="text-xs rounded-lg border border-slate-200 bg-white py-1.5 px-2.5 font-bold text-slate-800 outline-none focus:border-[#0D542B] focus:ring-1 focus:ring-[#0D542B]">
                                <option :value="5">5</option>
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                            </select>
                            <span>data per halaman</span>
                        </div>

                        <div class="text-xs text-slate-500 font-medium">
                            Menampilkan <span class="font-bold text-slate-900">{{ (currentPage - 1) * perPage + (alumnis.length > 0 ? 1 : 0) }}</span> &ndash; 
                            <span class="font-bold text-slate-900">{{ Math.min(currentPage * perPage, alumnis.length) }}</span> dari 
                            <span class="font-bold text-slate-900">{{ alumnis.length }}</span> total mahasiswa (Tahun {{ tahun }})
                        </div>
                    </div>

                    <!-- Tabel Data -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200/70 uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th class="py-3.5 px-4 text-center w-12">No</th>
                                    <th class="py-3.5 px-5">Mahasiswa / Alumni</th>
                                    <th class="py-3.5 px-4">Program Studi</th>
                                    <th class="py-3.5 px-4">Target Kelulusan</th>
                                    <th class="py-3.5 px-4 text-center min-w-[210px]">Status & Progres Tracer</th>
                                    <th class="py-3.5 px-4 text-center min-w-[160px]">Aksi & Kontak</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr 
                                    v-for="(alumni, idx) in paginatedAlumnis" 
                                    :key="alumni.id" 
                                    class="hover:bg-slate-50/70 transition-colors group"
                                >
                                    <!-- No -->
                                    <td class="py-4 px-4 text-center font-mono font-semibold text-slate-400">
                                        {{ (currentPage - 1) * perPage + idx + 1 }}
                                    </td>

                                    <!-- Identitas Mahasiswa (Nama, Avatar Inisial & NIM) -->
                                    <td class="py-4 px-5">
                                        <div class="flex items-center gap-3">
                                            <!-- Avatar Inisial Berkelas Solid UKDW -->
                                            <div class="w-9 h-9 rounded-full bg-emerald-50 border border-emerald-200 text-[#0D542B] flex items-center justify-center font-extrabold text-xs shrink-0 shadow-2xs group-hover:bg-[#0D542B] group-hover:text-white transition-colors">
                                                {{ getInitials(alumni.nama) }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-extrabold text-slate-900 text-xs sm:text-sm group-hover:text-[#0D542B] transition-colors leading-snug truncate">
                                                    {{ alumni.nama }}
                                                </div>
                                                <div class="flex items-center gap-1.5 mt-0.5">
                                                    <span class="font-mono text-[11px] text-slate-500 bg-slate-100 border border-slate-200/50 px-1.5 py-0.2 rounded font-semibold">
                                                        {{ alumni.nim }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Program Studi -->
                                    <td class="py-4 px-4">
                                        <div class="font-semibold text-slate-800 text-xs">
                                            {{ alumni.prodi }}
                                        </div>
                                        <div v-if="alumni.fakultas && alumni.fakultas !== '-'" class="text-[10px] text-slate-400">
                                            {{ alumni.fakultas }}
                                        </div>
                                    </td>

                                    <!-- Periode / Target Kelulusan -->
                                    <td class="py-4 px-4">
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-[#0D542B] border border-emerald-200/70 text-[11px] font-bold">
                                            <svg class="w-3 h-3 text-[#0D542B] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="truncate">{{ alumni.tahun_akademik_lulus }}</span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 mt-1 flex items-center gap-1.5">
                                            <span>Tahun {{ alumni.tahun_lulus }}</span>
                                            <span>&bull;</span>
                                            <span>Status: <strong class="text-slate-600">{{ alumni.status_yudisium }}</strong></span>
                                        </div>
                                    </td>

                                    <!-- Kolom Terpadu: Status & Progres Tracer (Profil, Kuesioner Univ, Evaluasi Atasan) -->
                                    <td class="py-4 px-4">
                                        <div class="flex flex-col gap-1.5 min-w-[190px] max-w-[220px] mx-auto text-[11px]">
                                            <!-- 1. Kelengkapan Profil -->
                                            <div class="flex items-center justify-between px-2 py-1 rounded-md border" :class="alumni.kelengkapan.profile.is_complete ? 'bg-emerald-50/70 border-emerald-200/60 text-emerald-800' : 'bg-rose-50/70 border-rose-200/60 text-rose-700'">
                                                <span class="font-medium flex items-center gap-1.5">
                                                    <span class="w-1.5 h-1.5 rounded-full" :class="alumni.kelengkapan.profile.is_complete ? 'bg-emerald-600' : 'bg-rose-500'"></span>
                                                    Profil
                                                </span>
                                                <span class="font-extrabold font-mono text-[10px]">
                                                    {{ alumni.kelengkapan.profile.percentage }}% {{ alumni.kelengkapan.profile.is_complete ? 'Lengkap' : 'Belum' }}
                                                </span>
                                            </div>

                                            <!-- 2. Kuesioner Universitas -->
                                            <div class="flex items-center justify-between px-2 py-1 rounded-md border" :class="alumni.kelengkapan.questionnaire.is_complete ? 'bg-emerald-50/70 border-emerald-200/60 text-emerald-800' : 'bg-amber-50/70 border-amber-200/60 text-amber-800'">
                                                <span class="font-medium flex items-center gap-1.5">
                                                    <span class="w-1.5 h-1.5 rounded-full" :class="alumni.kelengkapan.questionnaire.is_complete ? 'bg-emerald-600' : 'bg-amber-500'"></span>
                                                    Kuesioner
                                                </span>
                                                <span class="font-extrabold font-mono text-[10px]">
                                                    {{ alumni.kelengkapan.questionnaire.percentage }}% {{ alumni.kelengkapan.questionnaire.is_complete ? 'Selesai' : 'Wajib' }}
                                                </span>
                                            </div>

                                            <!-- 3. Evaluasi Atasan -->
                                            <div class="flex items-center justify-between px-2 py-1 rounded-md border" 
                                                 :class="alumni.evaluasi_atasan?.is_submitted 
                                                     ? 'bg-emerald-50/70 border-emerald-200/60 text-emerald-800' 
                                                     : (alumni.evaluasi_atasan?.has_evaluasi ? 'bg-amber-50/70 border-amber-200/60 text-amber-800' : 'bg-slate-50 border-slate-200/60 text-slate-500')">
                                                <span class="font-medium flex items-center gap-1.5">
                                                    <span class="w-1.5 h-1.5 rounded-full" 
                                                          :class="alumni.evaluasi_atasan?.is_submitted 
                                                              ? 'bg-emerald-600' 
                                                              : (alumni.evaluasi_atasan?.has_evaluasi ? 'bg-amber-500' : 'bg-slate-400')"></span>
                                                    Atasan
                                                </span>
                                                <span class="font-extrabold text-[10px]">
                                                    {{ alumni.evaluasi_atasan?.is_submitted ? 'Sudah Diisi' : (alumni.evaluasi_atasan?.has_evaluasi ? 'Menunggu' : 'Belum Ada') }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Kolom Aksi Terpadu (Detail, WA, Email Flash, LinkedIn Sync & History) -->
                                    <td class="py-4 px-4 text-center">
                                        <div class="inline-flex items-center justify-center gap-1.5">
                                            <!-- 1. Detail Alumni (👁️ Read-Only) -->
                                            <Link 
                                                :href="`/biro3/alumni/${alumni.id}`" 
                                                class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 bg-[#0D542B] hover:bg-[#08381c] text-white transition-all shadow-2xs hover:shadow-xs active:scale-95 cursor-pointer"
                                                title="Lihat Detail Alumni & Audit Kuesioner (Read-Only)"
                                                aria-label="Lihat Detail Alumni"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </Link>

                                            <!-- 2. WhatsApp (💬 Undangan Kuesioner) -->
                                            <button 
                                                type="button"
                                                @click="openWhatsAppModal(alumni)"
                                                class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 bg-[#25D366] hover:bg-[#1faa52] text-white transition-all shadow-2xs hover:shadow-xs active:scale-95 cursor-pointer"
                                                title="Hubungi via WhatsApp (Undangan Kuesioner & Info Login)"
                                                aria-label="Hubungi via WhatsApp"
                                            >
                                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                                                </svg>
                                            </button>

                                            <!-- 3. Kirim Email Pengingat Flash (✉️) -->
                                            <button 
                                                type="button"
                                                @click="handleSendSingleEmail(alumni)"
                                                class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 bg-blue-600 hover:bg-blue-700 text-white transition-all shadow-2xs hover:shadow-xs active:scale-95 cursor-pointer"
                                                title="Kirim Email Pengingat Kuesioner Langsung"
                                                aria-label="Kirim Email Pengingat"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            </button>

                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="alumnis.length === 0">
                                    <td colspan="8" class="py-16 text-center text-slate-500">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                        </div>
                                        <p class="font-bold text-slate-800 text-sm">Tidak Ada Mahasiswa Ditemukan</p>
                                        <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Tidak ada data alumni untuk Tahun Kelulusan {{ tahun }} yang cocok dengan kriteria filter aktif saat ini.</p>
                                        <button 
                                            @click="resetFilters" 
                                            class="mt-4 px-4 py-2 bg-[#0D542B] text-white text-xs font-bold rounded-xl hover:bg-[#08381c] transition-colors shadow-2xs inline-flex items-center gap-1.5 cursor-pointer"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            <span>Kembalikan Semua Filter</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- DataTables Pagination Controls Modern -->
                    <div v-if="alumnis.length > 0" class="p-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
                        <div class="text-xs text-slate-500 font-medium">
                            Halaman <span class="font-bold text-slate-900">{{ currentPage }}</span> dari <span class="font-bold text-slate-900">{{ totalPages }}</span>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <!-- Tombol Sebelumnya -->
                            <button 
                                @click="goToPage(currentPage - 1)" 
                                :disabled="currentPage === 1"
                                class="px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all inline-flex items-center gap-1 shadow-2xs"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                <span>Sebelumnya</span>
                            </button>
                            
                            <!-- Nomor Halaman -->
                            <button 
                                v-for="page in totalPages" 
                                :key="page"
                                v-show="page === 1 || page === totalPages || Math.abs(page - currentPage) <= 1"
                                @click="goToPage(page)"
                                class="w-8 h-8 rounded-xl text-xs font-bold transition-all"
                                :class="currentPage === page ? 'bg-[#0D542B] text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'"
                            >
                                {{ page }}
                            </button>

                            <!-- Tombol Selanjutnya -->
                            <button 
                                @click="goToPage(currentPage + 1)" 
                                :disabled="currentPage === totalPages"
                                class="px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all inline-flex items-center gap-1 shadow-2xs"
                            >
                                <span>Selanjutnya</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

            </main>
        </div>

        <!-- ================================================================= -->
        <!-- MODAL 1: HUBUNGI VIA WHATSAPP PERSONAL                            -->
        <!-- ================================================================= -->
        <div v-if="showWaModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 relative animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                        </span>
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900">Hubungi Alumni via WhatsApp</h3>
                            <p class="text-[11px] text-slate-500">{{ activeWaAlumni?.nama }} ({{ activeWaAlumni?.nim }})</p>
                        </div>
                    </div>
                    <button @click="showWaModal = false" class="text-slate-400 hover:text-slate-600 p-1 text-lg leading-none cursor-pointer">
                        &times;
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nomor Tujuan WhatsApp:</label>
                        <input 
                            v-model="waRecipientPhone" 
                            type="text" 
                            placeholder="Contoh: 08123456789 atau 628123456789" 
                            class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-slate-900 focus:ring-2 focus:ring-[#0D542B] focus:outline-none"
                        />
                        <p class="text-[10px] text-slate-500 mt-1">Nomor otomatis diformat ke kode negara 62 saat membuka WhatsApp.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Pesan WhatsApp (Termasuk Link & Info Login):</label>
                        <textarea 
                            v-model="waMessageText" 
                            rows="9"
                            class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl p-3 text-slate-800 leading-relaxed font-sans focus:ring-2 focus:ring-[#0D542B] focus:outline-none"
                        ></textarea>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2.5 border-t border-slate-100 pt-3">
                    <button 
                        @click="showWaModal = false"
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        @click="executeSendWa"
                        class="px-5 py-2 text-xs font-extrabold bg-[#25D366] hover:bg-[#1faa52] text-white rounded-xl shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                        <span>Buka WhatsApp Web / App</span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>
