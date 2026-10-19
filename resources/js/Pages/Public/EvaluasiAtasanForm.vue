<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    token: String,
    evaluasi: Object,
    pertanyaanList: {
        type: Array,
        default: () => [],
    },
    existingResponses: {
        type: Object,
        default: () => ({}),
    },
    existingCatatan: {
        type: String,
        default: '',
    },
    alumniInfo: Object,
    initialCompany: Object,
    isSubmitted: Boolean,
});

// Pisahkan butir pertanyaan: Kesiapan Kerja & 12 Aspek Kinerja Likert
const likertPertanyaanList = computed(() => props.pertanyaanList.filter((q) => q.kode !== 'tingkat_kesiapan_kerja'));
const kesiapanPertanyaan = computed(() => props.pertanyaanList.find((q) => q.kode === 'tingkat_kesiapan_kerja'));

// Siapkan state default jawaban untuk setiap butir pertanyaan
// PENTING: Hanya isi jika sudah ada respon di database, jangan diisi default jika belum pernah dijawab
const initialJawaban = {};
props.pertanyaanList.forEach((q) => {
    initialJawaban[q.kode] = props.existingResponses[q.kode] || '';
});

const form = useForm({
    // Bagian I: Informasi Perusahaan (Tersimpan langsung ke tabel master Perusahaan)
    nama_perusahaan: props.initialCompany?.nama || '',
    alamat_lengkap: props.initialCompany?.alamat || '',
    no_telp_fax: props.initialCompany?.no_telp_fax || '',
    homepage: props.initialCompany?.homepage || '',
    bentuk_perusahaan: props.initialCompany?.bentuk || '',
    skala_perusahaan: props.initialCompany?.skala || '',
    jumlah_pegawai: props.initialCompany?.jumlah_pegawai || '',

    // Karakteristik Keberadaan Alumni UKDW di Perusahaan (Tersimpan ke evaluasi_atasan)
    jumlah_alumni_ukdw: props.evaluasi?.jumlah_alumni_ukdw || '',
    standar_gaji_pertama: props.evaluasi?.standar_gaji_pertama || '',

    // Data Pimpinan / Atasan (Tersimpan ke tabel Atasan)
    nama_atasan: props.initialCompany?.nama_atasan || '',
    email_atasan: props.initialCompany?.email_atasan || '',
    telepon_atasan: props.initialCompany?.telepon_atasan || '',

    // Bagian II: Jawaban Kuesioner Evaluasi (Termasuk Kesiapan Kerja & 12 Aspek Kinerja)
    jawaban: initialJawaban,
    catatan_lainnya: props.existingCatatan || '',
});

const submitSuccess = ref(props.isSubmitted || false);

const submitForm = () => {
    // Validasi singkat apakah semua aspek sudah dinilai
    const missingKeys = props.pertanyaanList.filter((q) => !form.jawaban[q.kode]);
    if (missingKeys.length > 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Penilaian Belum Lengkap',
            text: `Mohon kesediaan Bapak/Ibu untuk melengkapi penilaian pada semua aspek (${missingKeys.length} pertanyaan/aspek belum dinilai).`,
            confirmButtonColor: '#0D542B',
        });
        return;
    }

    form.post(`/evaluasi-atasan/${props.token}`, {
        preserveScroll: true,
        onSuccess: () => {
            submitSuccess.value = true;
            Swal.fire({
                icon: 'success',
                title: 'Evaluasi Berhasil Disimpan',
                text: 'Terima kasih atas waktu dan evaluasi obyektif yang Bapak/Ibu berikan untuk peningkatan mutu lulusan Universitas Kristen Duta Wacana.',
                confirmButtonColor: '#0D542B',
            });
        },
    });
};

const skalaNilaiOptions = ['Sangat Tinggi', 'Tinggi', 'Cukup', 'Kurang', 'Sangat Kurang'];
</script>

<template>
    <Head title="Form Evaluasi Kepuasan & Kinerja Lulusan - UKDW" />

    <!-- Full Page Container (Enterprise Wide Layout) -->
    <div class="min-h-screen bg-slate-50 flex flex-col font-sans text-slate-900 antialiased selection:bg-emerald-100 selection:text-emerald-900">
        
        <!-- ========================================================= -->
        <!-- TOPBAR FULL WIDTH: IDENTITAS RESMI UNIVERSITAS             -->
        <!-- ========================================================= -->
        <header class="bg-[#0D542B] text-white shadow-xs border-b-4 border-[#FDC700] w-full">
            <div class="w-full px-4 sm:px-6 lg:px-10 py-4 sm:py-5">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
                    <div class="flex flex-col sm:flex-row items-center gap-3.5 sm:gap-4">
                        <div class="bg-white p-2 rounded-xl border border-emerald-100 shrink-0 shadow-2xs">
                            <img 
                                src="/uploads/logo/logo-ukdw.png" 
                                alt="Logo UKDW" 
                                class="h-11 sm:h-12 w-auto object-contain" 
                                onerror="this.style.display='none'"
                            />
                        </div>
                        <div>
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-white/10 rounded-full text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-[#FDC700] mb-1">
                                <span>Pusat Karir & Alumni (PKA) UKDW</span>
                            </div>
                            <h1 class="text-base sm:text-lg lg:text-xl font-bold tracking-tight text-white leading-tight">
                                Formulir Evaluasi Pengguna Lulusan (Atasan)
                            </h1>
                            <p class="text-[11px] sm:text-xs text-emerald-100/90 mt-0.5">
                                Universitas Kristen Duta Wacana &bull; Jl. Dr. Wahidin Sudirohusodo No. 5-19, Yogyakarta
                            </p>
                        </div>
                    </div>

                    <!-- Dokumen Resmi Akreditasi Banner -->
                    <div class="hidden md:flex flex-col items-end text-right text-xs bg-white/10 px-4 py-2 rounded-lg border border-white/20 shrink-0">
                        <span class="font-bold text-[#FDC700] text-[11px] uppercase tracking-wide">Instrumen Penilaian Kinerja</span>
                        <span class="text-white text-[11px] mt-0.5">Standar Akreditasi & Tracer Study</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- ========================================================= -->
        <!-- KONTEN FORMULIR UTAMA: FULL PAGE KANAN KIRI                -->
        <!-- ========================================================= -->
        <main class="flex-1 w-full px-4 sm:px-6 lg:px-10 py-6 sm:py-8 space-y-6">

            <!-- KARTU ALUMNI YANG DINILAI & STATUS RESPON -->
            <div class="w-full bg-white rounded-xl border border-slate-200 shadow-2xs p-5 sm:p-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="space-y-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#0D542B] bg-emerald-50 px-2.5 py-0.5 rounded border border-emerald-200 inline-block">
                            Alumni Yang Dinilai
                        </span>
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                            {{ alumniInfo.nama }}
                        </h2>
                        <div class="flex items-center flex-wrap gap-x-3 gap-y-1 text-xs text-slate-600 font-medium">
                            <span>Program Studi: <strong class="text-slate-900 font-semibold">{{ alumniInfo.prodi }}</strong></span>
                            <span class="text-slate-300 hidden sm:inline">&bull;</span>
                            <span>Nomor Induk Mahasiswa (NIM): <strong class="text-slate-900 font-mono font-semibold">{{ alumniInfo.nim }}</strong></span>
                        </div>
                    </div>

                    <!-- Status Pengisian Formal Bersih -->
                    <div class="shrink-0 flex items-center md:flex-col md:items-end justify-between md:justify-center border-t md:border-t-0 md:border-l border-slate-200 pt-3 md:pt-0 md:pl-6">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">
                            Status Pengisian
                        </span>
                        <div 
                            v-if="submitSuccess || isSubmitted" 
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-50 text-[#0D542B] border border-emerald-200"
                        >
                            <span class="w-2 h-2 rounded-full bg-[#0D542B]"></span>
                            <span>Respon Telah Terkirim</span>
                        </div>
                        <div 
                            v-else 
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-900 border border-amber-200"
                        >
                            <span class="w-2 h-2 rounded-full bg-[#FDC700]"></span>
                            <span>Menunggu Pengisian Atasan</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAMPILAN JIKA FORMULIR SUDAH PERNAH DISUBMIT -->
            <div v-if="submitSuccess && isSubmitted" class="w-full bg-white rounded-xl border border-slate-200 p-8 sm:p-12 text-center space-y-4 shadow-2xs">
                <div class="w-14 h-14 bg-emerald-50 text-[#0D542B] rounded-xl flex items-center justify-center mx-auto text-2xl font-bold border border-emerald-200">
                    &check;
                </div>
                <h3 class="text-xl sm:text-2xl font-bold text-slate-900">Evaluasi Berhasil Diterima</h3>
                <p class="text-slate-600 text-xs sm:text-sm max-w-2xl mx-auto leading-relaxed">
                    Terima kasih yang sebesar-besarnya kami sampaikan kepada Bapak/Ibu pimpinan di <strong>{{ form.nama_perusahaan }}</strong> atas waktu, evaluasi obyektif, dan masukan konstruktif yang telah diberikan terhadap alumni kami, <strong>{{ alumniInfo.nama }}</strong>. Data respon Anda telah tersimpan secara resmi di sistem Tracer Study UKDW.
                </p>
                <div class="pt-2 flex flex-wrap items-center justify-center gap-3">
                    <a href="https://www.ukdw.ac.id" target="_blank" class="inline-flex items-center px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg transition-colors">
                        Kunjungi Situs Resmi UKDW &rarr;
                    </a>
                </div>
            </div>

            <!-- FORMULIR INPUT EVALUASI UTAMA -->
            <form v-else @submit.prevent="submitForm" class="space-y-6">

                <!-- ========================================================= -->
                <!-- BAGIAN 1: INFORMASI PERUSAHAAN / INSTITUSI                -->
                <!-- ========================================================= -->
                <div class="w-full bg-white rounded-xl border border-slate-200 p-5 sm:p-7 shadow-2xs space-y-5">
                    <div class="border-b border-slate-200 pb-3 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm sm:text-base font-bold text-slate-900 uppercase tracking-wide">
                                1. Informasi Perusahaan / Lembaga / Institusi
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Identitas resmi instansi dan karakteristik tempat alumni bekerja saat ini.
                            </p>
                        </div>
                    </div>

                    <!-- Grid Form Data Instansi: 1 Kolom di HP, 2 Kolom di Tablet, 3 Kolom di Desktop -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                        <!-- Nama Perusahaan -->
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Nama Perusahaan / Lembaga / Institusi <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                v-model="form.nama_perusahaan" 
                                required
                                class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs sm:text-sm font-medium focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B] transition-colors bg-white"
                                placeholder="Nama resmi instansi tempat alumni bekerja" 
                            />
                        </div>

                        <!-- Alamat Lengkap -->
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Alamat Lengkap Perusahaan <span class="text-rose-500">*</span>
                            </label>
                            <textarea 
                                v-model="form.alamat_lengkap" 
                                rows="2"
                                required
                                class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs sm:text-sm font-medium focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B] transition-colors bg-white leading-relaxed"
                                placeholder="Jalan, Gedung, Kota/Kabupaten, Kode Pos..."
                            ></textarea>
                        </div>

                        <!-- No Telp / Fax -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">No. Telp / Fax Perusahaan</label>
                            <input 
                                type="text" 
                                v-model="form.no_telp_fax" 
                                class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs sm:text-sm font-medium focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B] transition-colors bg-white"
                                placeholder="Contoh: 0274-563929" 
                            />
                        </div>

                        <!-- Homepage (Website) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Website / Homepage Perusahaan</label>
                            <input 
                                type="text" 
                                v-model="form.homepage" 
                                class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs sm:text-sm font-medium focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B] transition-colors bg-white"
                                placeholder="Contoh: https://perusahaan.com" 
                            />
                        </div>

                        <!-- Nama Atasan / Pimpinan -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Nama Atasan Langsung <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                v-model="form.nama_atasan" 
                                required
                                class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs sm:text-sm font-medium focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B] transition-colors bg-white"
                                placeholder="Nama lengkap atasan langsung alumni" 
                            />
                        </div>

                        <!-- Email Atasan (Contact Person) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Email Atasan (Contact Person) <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="email" 
                                v-model="form.email_atasan" 
                                required
                                class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs sm:text-sm font-medium focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B] transition-colors bg-white"
                                placeholder="atasan@perusahaan.com" 
                            />
                        </div>

                        <!-- Nomor Telepon Atasan -->
                        <div class="sm:col-span-2 lg:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Nomor Telepon / WhatsApp Atasan</label>
                            <input 
                                type="tel" 
                                v-model="form.telepon_atasan" 
                                class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs sm:text-sm font-medium focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B] transition-colors bg-white"
                                placeholder="081234567890" 
                            />
                        </div>
                    </div>

                    <!-- 5 Pertanyaan Karakteristik Instansi & Keberadaan Alumni -->
                    <div class="space-y-5 pt-5 border-t border-slate-200">
                        
                        <!-- 1. Bentuk Perusahaan / Institusi -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-2">
                                1. Bentuk Perusahaan / Institusi:
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2.5">
                                <label 
                                    v-for="opt in ['BUMN', 'Perusahaan Terbatas', 'Koperasi', 'CV', 'Firma']" 
                                    :key="opt"
                                    class="flex items-center gap-2.5 p-3 border rounded-lg cursor-pointer text-xs font-semibold transition-colors select-none"
                                    :class="form.bentuk_perusahaan === opt ? 'border-[#0D542B] bg-emerald-50 text-[#0D542B] ring-1 ring-[#0D542B] font-bold' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                                >
                                    <input type="radio" v-model="form.bentuk_perusahaan" :value="opt" class="accent-[#0D542B] w-4 h-4 cursor-pointer shrink-0" />
                                    <span class="truncate">{{ opt }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- 2. Skala Perusahaan -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-2">
                                2. Skala Perusahaan / Organisasi:
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                <label 
                                    v-for="opt in ['Lokal', 'Nasional', 'Internasional']" 
                                    :key="opt"
                                    class="flex items-center gap-2.5 p-3 border rounded-lg cursor-pointer text-xs font-semibold transition-colors select-none"
                                    :class="form.skala_perusahaan === opt ? 'border-[#0D542B] bg-emerald-50 text-[#0D542B] ring-1 ring-[#0D542B] font-bold' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                                >
                                    <input type="radio" v-model="form.skala_perusahaan" :value="opt" class="accent-[#0D542B] w-4 h-4 cursor-pointer shrink-0" />
                                    <span>{{ opt }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- 3. Jumlah Pegawai Keseluruhan -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-2">
                                3. Jumlah Pegawai keseluruhan yang bekerja di perusahaan ini:
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
                                <label 
                                    v-for="opt in ['< 50 Orang', '51 - 100 Orang', '101 – 150 Orang', '151 – 300 Orang', '301 – 500 Orang', '> 500 Orang']" 
                                    :key="opt"
                                    class="flex items-center gap-2.5 p-3 border rounded-lg cursor-pointer text-xs font-semibold transition-colors select-none"
                                    :class="form.jumlah_pegawai === opt ? 'border-[#0D542B] bg-emerald-50 text-[#0D542B] ring-1 ring-[#0D542B] font-bold' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                                >
                                    <input type="radio" v-model="form.jumlah_pegawai" :value="opt" class="accent-[#0D542B] w-4 h-4 cursor-pointer shrink-0" />
                                    <span class="truncate">{{ opt }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- 4. Jumlah Alumni UKDW yang Bekerja -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-2">
                                4. Jumlah alumni UKDW yang bekerja di Perusahaan ini:
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                <label 
                                    v-for="opt in ['< 5 Orang', '6 - 10 Orang', '11 – 20 Orang', '> 21 Orang']" 
                                    :key="opt"
                                    class="flex items-center gap-2.5 p-3 border rounded-lg cursor-pointer text-xs font-semibold transition-colors select-none"
                                    :class="form.jumlah_alumni_ukdw === opt ? 'border-[#0D542B] bg-emerald-50 text-[#0D542B] ring-1 ring-[#0D542B] font-bold' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                                >
                                    <input type="radio" v-model="form.jumlah_alumni_ukdw" :value="opt" class="accent-[#0D542B] w-4 h-4 cursor-pointer shrink-0" />
                                    <span>{{ opt }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- 5. Standar Gaji Pertama -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-2">
                                5. Standar gaji pertama (per bulan dalam rupiah) yang diberikan Perusahaan kepada alumni UKDW:
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
                                <label 
                                    v-for="opt in ['< 1.000.000', '1.000.000 – 1.500.000', '1.500.000 – 2.000.000', '2.000.000 – 3.000.000', '3.000.000 – 4.000.000', '> 5.000.000']" 
                                    :key="opt"
                                    class="flex items-center gap-2.5 p-3 border rounded-lg cursor-pointer text-xs font-semibold transition-colors select-none"
                                    :class="form.standar_gaji_pertama === opt ? 'border-[#0D542B] bg-emerald-50 text-[#0D542B] ring-1 ring-[#0D542B] font-bold' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                                >
                                    <input type="radio" v-model="form.standar_gaji_pertama" :value="opt" class="accent-[#0D542B] w-4 h-4 cursor-pointer shrink-0" />
                                    <span class="truncate">{{ opt }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- BAGIAN 2: PENILAIAN KINERJA LULUSAN                       -->
                <!-- ========================================================= -->
                <div class="w-full bg-white rounded-xl border border-slate-200 p-5 sm:p-7 shadow-2xs space-y-5">
                    <div class="border-b border-slate-200 pb-3 flex flex-col md:flex-row md:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm sm:text-base font-bold text-slate-900 uppercase tracking-wide">
                                2. Informasi Penilaian Kesiapan & Kinerja Lulusan (Alumni) UKDW
                            </h2>
                            <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">
                                Evaluasi obyektif terhadap performa dan kompetensi alumni UKDW atas nama <strong class="text-slate-900 font-bold">{{ alumniInfo.nama }}</strong>.
                            </p>
                        </div>

                        <!-- Tag Alumni Formal Berbingkai Hijau & Emas -->
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-200 text-[#0D542B] shrink-0 self-start md:self-auto">
                            <div class="text-left text-xs">
                                <span class="text-[10px] uppercase font-bold text-emerald-800 block leading-tight">Alumni Dinilai:</span>
                                <span class="font-bold text-slate-900">{{ alumniInfo.nama }} ({{ alumniInfo.prodi }})</span>
                            </div>
                        </div>
                    </div>

                    <!-- Butir Evaluasi Kesiapan Kerja -->
                    <div v-if="kesiapanPertanyaan" class="p-4 sm:p-5 rounded-lg bg-slate-50 border border-slate-200 space-y-3">
                        <label class="block text-xs font-bold text-slate-900 leading-snug">
                            1. Tingkat kesiapan alumni UKDW dalam bekerja di Perusahaan ini:
                            <span class="block text-[11px] font-normal text-slate-500 mt-0.5">
                                {{ kesiapanPertanyaan.deskripsi || 'Evaluasi kesiapan alumni dalam mengemban tugas dan adaptasi di tempat kerja.' }}
                            </span>
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                            <label 
                                v-for="opt in ['Sangat siap', 'Cukup Siap', 'Kurang siap', 'Tidak siap']" 
                                :key="opt"
                                class="flex items-center gap-2.5 p-3.5 border rounded-lg cursor-pointer text-xs font-semibold transition-colors select-none bg-white"
                                :class="form.jawaban[kesiapanPertanyaan.kode] === opt ? 'border-[#0D542B] bg-emerald-50 text-[#0D542B] ring-1 ring-[#0D542B] font-bold' : 'border-slate-200 text-slate-700 hover:bg-slate-100/60'"
                            >
                                <input 
                                    type="radio" 
                                    :name="`jawaban_${kesiapanPertanyaan.kode}`"
                                    v-model="form.jawaban[kesiapanPertanyaan.kode]" 
                                    :value="opt" 
                                    class="accent-[#0D542B] w-4 h-4 cursor-pointer shrink-0" 
                                />
                                <span>{{ opt }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Header Penilaian 12 Aspek Kinerja -->
                    <div class="pt-2">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 mb-1">
                            2. Aspek Kinerja Lulusan (Alumni) UKDW
                        </h3>
                        <p class="text-xs text-slate-500">
                            Seberapa tinggikah penilaian Bapak/Ibu terhadap aspek kinerja lulusan UKDW yang bekerja pada perusahaan Anda?
                        </p>
                    </div>

                    <!-- ========================================================= -->
                    <!-- TAMPILAN MOBILE (HP: < 768px): KARTU PENILAIAN SENTUH MUDAH -->
                    <!-- ========================================================= -->
                    <div class="block md:hidden space-y-4">
                        <div 
                            v-for="(pertanyaan, idx) in likertPertanyaanList" 
                            :key="`m_${pertanyaan.id}`"
                            class="p-4 rounded-xl border transition-colors bg-white shadow-2xs space-y-3"
                            :class="form.jawaban[pertanyaan.kode] ? 'border-emerald-300 bg-emerald-50/20' : 'border-slate-200'"
                        >
                            <div class="flex items-start gap-2.5">
                                <span class="w-6 h-6 rounded-md bg-[#0D542B] text-[#FDC700] text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">
                                    {{ idx + 1 }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-xs font-bold text-slate-900 leading-snug">
                                        {{ pertanyaan.aspek }}
                                    </h4>
                                    <p v-if="pertanyaan.deskripsi" class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                                        {{ pertanyaan.deskripsi }}
                                    </p>
                                </div>
                            </div>

                            <!-- Pilihan Skala Nilai Mobile (Pill Buttons yang Mudah Ditekan Jari) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                                <button
                                    v-for="skala in skalaNilaiOptions"
                                    :key="skala"
                                    type="button"
                                    @click="form.jawaban[pertanyaan.kode] = skala"
                                    class="w-full py-2.5 px-3 text-xs font-semibold rounded-lg border transition-colors flex items-center justify-between cursor-pointer"
                                    :class="form.jawaban[pertanyaan.kode] === skala 
                                        ? 'bg-[#0D542B] text-white border-[#0D542B] shadow-2xs font-bold' 
                                        : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                                >
                                    <span>{{ skala }}</span>
                                    <span v-if="form.jawaban[pertanyaan.kode] === skala" class="text-[#FDC700] text-xs font-bold">&check;</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================================= -->
                    <!-- TAMPILAN TABLET & DESKTOP (>= 768px): TABEL MATRIX RESMI   -->
                    <!-- ========================================================= -->
                    <div class="hidden md:block overflow-x-auto border border-slate-200 rounded-lg shadow-2xs bg-white">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-[#0D542B] text-white font-bold text-center border-b-2 border-[#FDC700] text-xs">
                                    <th class="p-3 w-12 border-r border-emerald-800 text-center text-white">No</th>
                                    <th class="p-3 border-r border-emerald-800 text-left min-w-[280px] text-white">
                                        <div class="flex items-center justify-between gap-2">
                                            <span>Aspek Kinerja & Kemampuan</span>
                                            <span class="text-[10px] font-bold text-[#FDC700] bg-black/20 px-2 py-0.5 rounded">
                                                Lulusan: {{ alumniInfo.nama }}
                                            </span>
                                        </div>
                                    </th>
                                    <th v-for="skala in skalaNilaiOptions" :key="skala" class="p-3 w-28 text-center border-r border-emerald-800 last:border-r-0 text-white">
                                        {{ skala }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <tr 
                                    v-for="(pertanyaan, idx) in likertPertanyaanList" 
                                    :key="`d_${pertanyaan.id}`" 
                                    class="hover:bg-slate-50 transition-colors"
                                    :class="idx % 2 === 1 ? 'bg-slate-50/40' : 'bg-white'"
                                >
                                    <td class="p-3 text-center font-bold text-slate-400 border-r border-slate-100">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="p-3 border-r border-slate-100">
                                        <div class="font-bold text-slate-900 text-xs leading-snug">{{ pertanyaan.aspek }}</div>
                                        <div v-if="pertanyaan.deskripsi" class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">{{ pertanyaan.deskripsi }}</div>
                                    </td>
                                    <td 
                                        v-for="skala in skalaNilaiOptions" 
                                        :key="skala" 
                                        class="p-3 text-center border-r border-slate-100 last:border-r-0 cursor-pointer select-none transition-colors"
                                        :class="form.jawaban[pertanyaan.kode] === skala ? 'bg-emerald-50 text-[#0D542B] font-bold' : 'hover:bg-slate-100/60'"
                                        @click="form.jawaban[pertanyaan.kode] = skala"
                                    >
                                        <div class="flex items-center justify-center">
                                            <input 
                                                type="radio" 
                                                :name="`d_jawaban_${pertanyaan.kode}`"
                                                v-model="form.jawaban[pertanyaan.kode]" 
                                                :value="skala" 
                                                class="w-4 h-4 accent-[#0D542B] cursor-pointer" 
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Catatan Tambahan / Saran Khusus -->
                    <div class="pt-2">
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Catatan Tambahan / Saran Khusus (Sebutkan jika ada):
                        </label>
                        <textarea 
                            v-model="form.catatan_lainnya" 
                            rows="3"
                            class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs sm:text-sm font-medium focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B] transition-colors bg-white leading-relaxed"
                            placeholder="Saran, kritik, atau catatan khusus mengenai kinerja dan pengembangan kurikulum alumni UKDW..."
                        ></textarea>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- TOMBOL KONFIRMASI SUBMIT RESMI                            -->
                <!-- ========================================================= -->
                <div class="w-full bg-white rounded-xl border border-slate-200 p-6 sm:p-8 text-center shadow-2xs space-y-4">
                    <p class="text-xs text-slate-500 max-w-2xl mx-auto leading-relaxed">
                        Dengan menekan tombol di bawah, Bapak/Ibu menyatakan bahwa penilaian ini diberikan secara obyektif demi kemajuan kualitas pendidikan dan kurikulum Universitas Kristen Duta Wacana.
                    </p>
                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="w-full sm:w-auto px-8 py-3.5 bg-[#0D542B] hover:bg-[#08381c] text-white font-bold text-xs sm:text-sm rounded-lg transition-colors shadow-xs disabled:opacity-50 cursor-pointer inline-flex items-center justify-center gap-2 border-b-2 border-[#FDC700]"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ form.processing ? 'Menyimpan Jawaban...' : 'Kirim Respon Evaluasi Kinerja' }}</span>
                    </button>
                </div>
            </form>
        </main>

        <!-- ========================================================= -->
        <!-- FOOTER RESMI UNIVERSITAS: FULL WIDTH                       -->
        <!-- ========================================================= -->
        <footer class="bg-white border-t border-slate-200 mt-10 py-6 text-xs text-slate-500 w-full">
            <div class="w-full px-4 sm:px-6 lg:px-10 flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-left">
                <div>
                    <span class="font-bold text-slate-800 block text-xs sm:text-sm">Pusat Karir & Alumni Universitas Kristen Duta Wacana</span>
                    <span class="text-[11px] text-slate-400">Jl. Dr. Wahidin Sudirohusodo No. 5-19, Yogyakarta 55224 | Telp: 0274-563929</span>
                </div>
                <div class="text-[11px] text-slate-400">
                    &copy; {{ new Date().getFullYear() }} UKDW. Hak Cipta Dilindungi.
                </div>
            </div>
        </footer>
    </div>
</template>
