<!--
  Halaman Direktori Mahasiswa & Alumni (Fakultas)
  File: resources/js/Pages/AdminFakultas/Alumni/Index.vue
  
  Desain Modern & Profesional Seragam dengan Super Admin UKDW
-->
<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Swal from 'sweetalert2';
import Sidebar from '../Components/Sidebar.vue';

const props = defineProps({
    user: Object,
    fakultas: Object,
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

// State Filter
const defaultTahun = props.filters.tahun || props.daftarTahun[0] || '';
const search = ref(props.filters.search || '');
const tahun = ref(defaultTahun);
const semester = ref(props.filters.semester || 'all');
const target = ref(props.filters.target || 'all');
const status = ref(props.filters.status || 'all');
const prodiId = ref(props.filters.prodi_id || 'all');

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
    router.get('/fakultas/alumni', {
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

const resetFilters = () => {
    search.value = '';
    tahun.value = props.daftarTahun[0] || '';
    semester.value = 'all';
    target.value = 'all';
    status.value = 'all';
    prodiId.value = 'all';
    currentPage.value = 1;
    router.get('/fakultas/alumni', { tahun: tahun.value }, { preserveState: true });
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

    waMessageText.value = `Halo Sdr/i ${alumni.nama},\n\nSalam hangat dari Fakultas Universitas Kristen Duta Wacana (UKDW).\n\nKami mengundang Anda untuk melengkapi instrumen kuesioner Tracer Study UKDW melalui portal resmi kami:\n👉 ${loginUrl}\n\n*Panduan Masuk ke Portal:*\n• Username: *${alumni.nim}* (NIM Anda)\n• Password: Kata sandi akun Tracer Study Anda\n\nPartisipasi Anda sangat berarti bagi pengembangan kurikulum dan peningkatan mutu fakultas & almamater tercinta. Jika memerlukan bantuan, silakan hubungi kami.\n\nTerima kasih banyak atas dukungannya!\nSalam,\nAdmin Fakultas UKDW`;

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

            router.post(`/fakultas/alumni/${alumni.id}/send-email`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Email Terkirim',
                        text: `Email pengingat berhasil dikirimkan ke ${alumni.nama} (${alumni.email}).`,
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

// 3. Sinkronisasi LinkedIn Direct & History Modal
const showLinkedInHistoryModal = ref(false);
const activeLinkedInAlumni = ref(null);
const linkedInHistoryList = ref([]);
const isLoadingHistory = ref(false);

const handleSyncLinkedIn = async (alumni) => {
    if (!alumni.linkedin_url) {
        Swal.fire({
            title: 'URL LinkedIn Belum Diisi',
            html: `<p class="text-xs text-slate-600">Alumni <b>${alumni.nama}</b> (${alumni.nim}) belum memiliki URL profil LinkedIn di database.</p>
                   <p class="text-xs text-slate-500 mt-2">Masukkan URL profil LinkedIn untuk memulai sinkronisasi:</p>`,
            icon: 'info',
            input: 'url',
            inputPlaceholder: 'https://www.linkedin.com/in/username',
            showCancelButton: true,
            confirmButtonText: 'Simpan & Sinkronkan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#0077B5',
            inputValidator: (val) => {
                if (!val || !val.includes('linkedin.com')) {
                    return 'Harap masukkan tautan profil LinkedIn yang valid!';
                }
            }
        }).then(async (res) => {
            if (res.isConfirmed && res.value) {
                try {
                    await fetch(`/fakultas/alumni/${alumni.id}/profile`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        },
                        body: JSON.stringify({ linkedin_url: res.value })
                    });
                    alumni.linkedin_url = res.value;
                    executeLinkedInSync(alumni);
                } catch (e) {
                    executeLinkedInSync(alumni);
                }
            }
        });
        return;
    }

    Swal.fire({
        title: 'Sinkronkan LinkedIn?',
        html: `<p class="text-xs text-slate-600">Jalankan sinkronisasi profil LinkedIn untuk alumni <b>${alumni.nama}</b>?</p>
               <div class="mt-2 text-xs font-mono text-blue-700 bg-blue-50 p-2 rounded-lg break-all border border-blue-200/60">${alumni.linkedin_url}</div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0077B5',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Ya, Sinkronkan Sekarang',
        cancelButtonText: 'Batal',
    }).then((res) => {
        if (res.isConfirmed) {
            executeLinkedInSync(alumni);
        }
    });
};

const executeLinkedInSync = async (alumni) => {
    Swal.fire({
        title: 'Menyinkronkan LinkedIn...',
        html: '<p class="text-xs text-slate-600">Sistem sedang menghubungi LinkedIn untuk memperbarui data karir alumni. Mohon tunggu...</p>',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });

    try {
        const response = await fetch(`/fakultas/linkedin-sync/${alumni.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        const data = await response.json();
        Swal.close();

        if (response.ok && data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Sinkronisasi Berhasil!',
                text: data.message || 'Data LinkedIn alumni berhasil diperbarui.',
                confirmButtonColor: '#0D542B',
            });
            router.reload({ preserveScroll: true });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Sinkronisasi Gagal',
                text: data.message || 'Gagal menyinkronkan profil LinkedIn.',
                confirmButtonColor: '#0D542B',
            });
        }
    } catch (err) {
        Swal.close();
        Swal.fire({
            icon: 'warning',
            title: 'Koneksi Terputus / Waktu Habis',
            text: err.message || 'Koneksi ke server terputus saat sinkronisasi LinkedIn.',
            confirmButtonColor: '#0D542B',
        });
    }
};

const handleOpenLinkedInHistory = async (alumni) => {
    activeLinkedInAlumni.value = alumni;
    showLinkedInHistoryModal.value = true;
    isLoadingHistory.value = true;
    linkedInHistoryList.value = [];

    try {
        const response = await fetch(`/fakultas/linkedin-sync/alumni/${alumni.id}/history`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await response.json();
        if (data.success) {
            linkedInHistoryList.value = data.history || [];
        }
    } catch (err) {
        console.error('Error fetching history:', err);
    } finally {
        isLoadingHistory.value = false;
    }
};
</script>

<template>
    <Head title="Direktori & Audit Alumni Fakultas - Tracer Study UKDW" />

    <div class="flex h-screen bg-[#F8FAFC] font-sans antialiased text-slate-800 overflow-hidden">
        <!-- Sidebar Terpadu Admin Fakultas -->
        <Sidebar :user="user" :fakultas="fakultas" />

        <!-- Area Konten Utama -->
        <main class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden bg-[#F8FAFC] lg:pl-72">
            <div class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

                <!-- Header Banner Fakultas -->
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#0D542B] to-[#147a3f] p-6 sm:p-8 text-white shadow-sm border border-emerald-800/40">
                    <div class="relative z-10 max-w-4xl">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-emerald-100 text-xs font-semibold mb-3 border border-white/15">
                            <span class="w-2 h-2 rounded-full bg-[#FDC700] animate-pulse"></span>
                            <span>Tracer Study Fakultas &bull; {{ fakultas?.nama_fakultas || 'Fakultas' }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                            Direktori Mahasiswa & Rekap Audit Kuesioner
                        </h1>
                        <p class="text-xs sm:text-sm text-emerald-100/90 mt-2 leading-relaxed">
                            Pemantauan audit kelengkapan kuesioner tracer study dan profil responden alumni lingkup {{ fakultas?.nama_fakultas || 'Fakultas' }}.
                        </p>
                    </div>

                    <!-- Watermark Logo Background -->
                    <div class="absolute -right-10 -bottom-10 w-64 h-64 opacity-10 pointer-events-none">
                        <svg class="w-full h-full text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM3.45 9L12 4.34 20.55 9 12 13.66 3.45 9zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/>
                        </svg>
                    </div>
                </div>

                <!-- 4 Kartu Statistik -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <!-- Total Mahasiswa -->
                    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs flex items-center justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Alumni</p>
                            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1.5">{{ stats.total_alumni }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">Tahun lulus {{ tahun }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Responden Selesai -->
                    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs flex items-center justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kuesioner Selesai</p>
                            <p class="text-2xl sm:text-3xl font-extrabold text-emerald-600 tracking-tight mt-1.5">{{ stats.total_selesai }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">Kelengkapan 100%</p>
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
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Belum Selesai</p>
                            <p class="text-2xl sm:text-3xl font-extrabold text-amber-600 tracking-tight mt-1.5">{{ stats.total_belum_selesai }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">Perlu tindak lanjut</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
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

                <!-- Panel Filter Komprehensif -->
                <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs">
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

                    <!-- Grid Form Controls -->
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

                <!-- Card Tabel Mahasiswa Modern -->
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

                    <!-- Tabel Data (Tanpa Kolom Status Akhir) -->
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

                                    <!-- Identitas Mahasiswa -->
                                    <td class="py-4 px-5">
                                        <div class="flex items-center gap-3">
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
                                            <!-- 1. Detail (👁️) -->
                                            <Link 
                                                :href="`/fakultas/alumni/${alumni.id}`" 
                                                class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 bg-[#0D542B] hover:bg-[#08381c] text-white transition-all shadow-2xs hover:shadow-xs active:scale-95 cursor-pointer"
                                                title="Lihat Detail Alumni & Kuesioner"
                                                aria-label="Lihat Detail Alumni"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </Link>

                                            <!-- 2. WhatsApp (💬) -->
                                            <button 
                                                type="button"
                                                @click="openWhatsAppModal(alumni)"
                                                class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 bg-[#25D366] hover:bg-[#1faa52] text-white transition-all shadow-2xs hover:shadow-xs active:scale-95 cursor-pointer"
                                                title="Hubungi via WhatsApp (Undangan Kuesioner)"
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

                                            <!-- 4. Sinkronisasi LinkedIn Direct & History (🔗) -->
                                            <div class="relative inline-flex items-center">
                                                <button 
                                                    type="button"
                                                    @click="handleSyncLinkedIn(alumni)"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 bg-[#0077B5] hover:bg-[#005e93] text-white transition-all shadow-2xs hover:shadow-xs active:scale-95 cursor-pointer"
                                                    :title="alumni.linkedin_url ? `Sinkronkan LinkedIn: ${alumni.linkedin_url}` : 'Sinkronkan LinkedIn Alumni'"
                                                    aria-label="Sinkronkan LinkedIn"
                                                >
                                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                                        <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                                                    </svg>
                                                </button>
                                                <!-- Tombol Mini Riwayat Log LinkedIn -->
                                                <button 
                                                    type="button"
                                                    @click="handleOpenLinkedInHistory(alumni)"
                                                    class="w-4 h-4 rounded-full bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 absolute -top-1.5 -right-1.5 flex items-center justify-center text-[8px] font-bold shadow-2xs cursor-pointer"
                                                    title="Riwayat Scraping LinkedIn"
                                                    aria-label="Riwayat LinkedIn"
                                                >
                                                    H
                                                </button>
                                            </div>
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
                                        <p class="font-extrabold text-slate-900 text-sm">Tidak Ada Mahasiswa Ditemukan</p>
                                        <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                                            Tidak ada catatan data alumni yang cocok dengan kriteria filter aktif untuk Tahun {{ tahun }}.
                                        </p>
                                        <button 
                                            @click="resetFilters" 
                                            class="mt-4 px-4 py-2 bg-slate-900 text-white text-xs font-bold rounded-xl hover:bg-slate-800 transition-colors cursor-pointer"
                                        >
                                            Reset Semua Filter
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer Pagination DataTables -->
                    <div class="p-4 sm:p-5 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/40">
                        <div class="text-xs text-slate-500 font-medium">
                            Halaman <span class="font-bold text-slate-900">{{ currentPage }}</span> dari <span class="font-bold text-slate-900">{{ totalPages }}</span>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button
                                @click="goToPage(currentPage - 1)"
                                :disabled="currentPage === 1"
                                class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-600 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all cursor-pointer shadow-2xs"
                            >
                                Sebelumnya
                            </button>

                            <div class="flex items-center gap-1">
                                <template v-for="p in totalPages" :key="p">
                                    <button
                                        v-if="p === 1 || p === totalPages || (p >= currentPage - 1 && p <= currentPage + 1)"
                                        @click="goToPage(p)"
                                        class="w-7 h-7 rounded-lg text-xs font-extrabold transition-all cursor-pointer"
                                        :class="p === currentPage ? 'bg-[#0D542B] text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100'"
                                    >
                                        {{ p }}
                                    </button>
                                    <span v-else-if="p === currentPage - 2 || p === currentPage + 2" class="text-slate-400 text-xs px-0.5">
                                        ...
                                    </span>
                                </template>
                            </div>

                            <button
                                @click="goToPage(currentPage + 1)"
                                :disabled="currentPage === totalPages"
                                class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-600 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all cursor-pointer shadow-2xs"
                            >
                                Selanjutnya
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </main>
        <!-- ================================================================= -->
        <!-- MODAL HUBUNGI VIA WHATSAPP PERSONAL                               -->
        <!-- ================================================================= -->
        <div v-if="showWaModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-200 relative animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                        </span>
                        <div>
                            <h3 class="text-sm font-extrabold text-gray-900">Hubungi Alumni via WhatsApp</h3>
                            <p class="text-[11px] text-gray-500">{{ activeWaAlumni?.nama }} ({{ activeWaAlumni?.nim }})</p>
                        </div>
                    </div>
                    <button @click="showWaModal = false" class="text-gray-400 hover:text-gray-600 p-1 text-lg leading-none cursor-pointer">
                        &times;
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nomor Tujuan WhatsApp:</label>
                        <input 
                            v-model="waRecipientPhone" 
                            type="text" 
                            placeholder="Contoh: 08123456789 atau 628123456789" 
                            class="w-full text-xs font-mono bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-gray-900 focus:ring-2 focus:ring-[#0D542B] focus:outline-none"
                        />
                        <p class="text-[10px] text-gray-500 mt-1">Nomor otomatis diformat ke kode negara 62 saat membuka WhatsApp.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Pesan WhatsApp (Termasuk Link & Info Login):</label>
                        <textarea 
                            v-model="waMessageText" 
                            rows="9"
                            class="w-full text-xs bg-gray-50 border border-gray-300 rounded-xl p-3 text-gray-800 leading-relaxed font-sans focus:ring-2 focus:ring-[#0D542B] focus:outline-none"
                        ></textarea>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2.5 border-t border-gray-100 pt-3">
                    <button 
                        @click="showWaModal = false"
                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        @click="executeSendWa"
                        class="px-5 py-2 text-xs font-extrabold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                        <span>Buka WhatsApp Web / App</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- MODAL RIWAYAT (HISTORY) SINKRONISASI LINKEDIN                     -->
        <!-- ================================================================= -->
        <div v-if="showLinkedInHistoryModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-gray-200 relative animate-in fade-in zoom-in-95 duration-150 max-h-[85vh] flex flex-col">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-8 rounded-xl bg-blue-100 text-[#0077B5] flex items-center justify-center font-bold">
                            in
                        </span>
                        <div>
                            <h3 class="text-sm font-extrabold text-gray-900">Riwayat Sinkronisasi LinkedIn</h3>
                            <p class="text-[11px] text-gray-500">{{ activeLinkedInAlumni?.nama }} ({{ activeLinkedInAlumni?.nim }})</p>
                        </div>
                    </div>
                    <button @click="showLinkedInHistoryModal = false" class="text-gray-400 hover:text-gray-600 p-1 text-lg leading-none cursor-pointer">
                        &times;
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto pr-1">
                    <div v-if="isLoadingHistory" class="py-12 text-center text-slate-500 text-xs">
                        <div class="inline-block w-6 h-6 border-2 border-slate-300 border-t-[#0077B5] rounded-full animate-spin mb-2"></div>
                        <p>Memuat riwayat sinkronisasi...</p>
                    </div>

                    <div v-else-if="linkedInHistoryList.length === 0" class="py-12 text-center text-slate-400 text-xs">
                        <p class="font-bold text-slate-600">Belum Ada Riwayat Sinkronisasi</p>
                        <p class="mt-1">Alumni ini belum pernah disinkronkan melalui provider LinkedIn.</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div 
                            v-for="item in linkedInHistoryList" 
                            :key="item.id"
                            class="p-4 rounded-xl border bg-slate-50/70 border-slate-200 flex flex-col gap-2"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-mono text-slate-500 font-semibold">{{ item.scraped_at || '-' }}</span>
                                <span 
                                    class="text-[10px] uppercase font-extrabold px-2 py-0.5 rounded-full"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800': item.status === 'approved',
                                        'bg-amber-100 text-amber-800': item.status === 'pending',
                                        'bg-rose-100 text-rose-800': item.status === 'rejected',
                                    }"
                                >
                                    {{ item.status }}
                                </span>
                            </div>

                            <div v-if="item.preview" class="text-xs bg-white p-3 rounded-lg border border-slate-200/80 space-y-1">
                                <div class="font-bold text-slate-800">{{ item.preview.posisi || 'Posisi / Jabatan tidak terdeteksi' }}</div>
                                <div class="text-slate-600 font-medium">{{ item.preview.perusahaan || 'Perusahaan tidak terdeteksi' }}</div>
                                <div v-if="item.preview.lokasi" class="text-[11px] text-slate-400">📍 {{ item.preview.lokasi }}</div>
                            </div>

                            <div v-if="item.reviewed_at" class="text-[10px] text-slate-500 flex items-center justify-between pt-1 border-t border-slate-200/60">
                                <span>Ditinjau oleh: <b>{{ item.reviewer_name }}</b></span>
                                <span>Waktu tinjau: {{ item.reviewed_at }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-gray-100 flex justify-end">
                    <button 
                        @click="showLinkedInHistoryModal = false"
                        class="px-4 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>
