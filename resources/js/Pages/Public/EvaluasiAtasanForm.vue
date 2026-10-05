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
            confirmButtonColor: '#005B3C',
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
                confirmButtonColor: '#005B3C',
            });
        },
    });
};

const skalaNilaiOptions = ['Sangat Tinggi', 'Tinggi', 'Cukup', 'Kurang', 'Sangat Kurang'];
</script>

<template>
    <Head title="Form Evaluasi Kepuasan & Kinerja Lulusan - UKDW" />

    <!-- Full Page Container (Enterprise Portal Design) -->
    <div class="min-h-screen bg-slate-50 flex flex-col font-sans text-slate-900 antialiased selection:bg-emerald-100 selection:text-emerald-900">
        
        <!-- ========================================================= -->
        <!-- TOPBAR FULL WIDTH: IDENTITAS RESMI UNIVERSITAS             -->
        <!-- ========================================================= -->
        <header class="bg-[#005B3C] text-white shadow-md border-b-4 border-[#F5A623] w-full">
            <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6 text-center md:text-left">
                    <div class="flex flex-col sm:flex-row items-center gap-4 sm:gap-6">
                        <div class="bg-white p-2.5 rounded-2xl shadow-sm shrink-0">
                            <img 
                                src="/uploads/logo/logo-ukdw.png" 
                                alt="Logo UKDW" 
                                class="h-14 sm:h-16 w-auto object-contain" 
                                onerror="this.style.display='none'"
                            />
                        </div>
                        <div>
                            <div class="inline-block px-3 py-1 bg-white/15 backdrop-blur-xs rounded-full text-[10px] sm:text-[11px] font-extrabold uppercase tracking-widest text-emerald-100 mb-1.5">
                                Pusat Karir & Alumni UKDW
                            </div>
                            <h1 class="text-xl sm:text-2xl lg:text-3xl font-black tracking-tight leading-tight uppercase text-white">
                                Form Evaluasi Tingkat Kepuasan & Kinerja Lulusan
                            </h1>
                            <p class="text-xs sm:text-sm font-bold text-emerald-100 mt-1">
                                UNIVERSITAS KRISTEN DUTA WACANA
                            </p>
                            <p class="text-[11px] sm:text-xs text-emerald-100/80 mt-0.5">
                                Jl. Dr. Wahidin S. No. 5-19, Yogyakarta 55224 | Telp: 0274-563929 | Fax: 0274-513235
                            </p>
                        </div>
                    </div>

                    <!-- Badge Dokumen Resmi Akreditasi -->
                    <div class="hidden lg:flex flex-col items-end text-right text-xs bg-white/10 backdrop-blur-xs px-4 py-3 rounded-xl border border-white/15">
                        <span class="font-extrabold uppercase tracking-wider text-emerald-200 text-[10px]">Survei Pengguna Lulusan</span>
                        <span class="text-white font-medium mt-0.5">Standar Akreditasi BAN-PT & Tracer Study</span>
                        <span class="text-emerald-100/70 text-[11px] mt-0.5">Pusat Karir & Alumni (PKA) UKDW</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- ========================================================= -->
        <!-- KONTEN FORMULIR UTAMA (FULL PAGE WIDE LAYOUT)              -->
        <!-- ========================================================= -->
        <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- KARTU ALUMNI YANG DINILAI & STATUS RESPON (PROFESIONAL & ELEGAN) -->
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-5 sm:p-7">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-1.5">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-[#005B3C] bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-200/70 inline-block">
                            Alumni Yang Dinilai
                        </span>
                        <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                            {{ alumniInfo.nama }}
                        </h2>
                        <div class="flex items-center flex-wrap gap-x-3 gap-y-1 text-xs sm:text-sm text-slate-600 font-medium">
                            <span>Program Studi: <strong class="text-slate-900 font-semibold">{{ alumniInfo.prodi }}</strong></span>
                            <span class="text-slate-300 hidden sm:inline">•</span>
                            <span>Nomor Induk Mahasiswa (NIM): <strong class="text-slate-900 font-mono font-semibold">{{ alumniInfo.nim }}</strong></span>
                        </div>
                    </div>

                    <!-- Badge Status Pengisian Profesional Elegan (Harmonis dengan Brand UKDW) -->
                    <div class="shrink-0 flex items-center md:flex-col md:items-end justify-between md:justify-center border-t md:border-t-0 md:border-l border-slate-100 pt-4 md:pt-0 md:pl-8">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">
                            Status Respon
                        </span>
                        <div 
                            v-if="submitSuccess || isSubmitted" 
                            class="inline-flex items-center gap-2.5 px-4 py-2 rounded-xl text-xs font-bold bg-emerald-50 text-[#005B3C] border border-emerald-200 shadow-2xs"
                        >
                            <span class="w-2.5 h-2.5 rounded-full bg-[#005B3C]"></span>
                            <span>Sudah Selesai Diisi</span>
                        </div>
                        <div 
                            v-else 
                            class="inline-flex items-center gap-2.5 px-4 py-2 rounded-xl text-xs font-bold bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs"
                        >
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
                            </span>
                            <span class="text-slate-800">Menunggu Pengisian</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAMPILAN JIKA FORMULIR SUDAH PERNAH DISUBMIT -->
            <div v-if="submitSuccess && isSubmitted" class="bg-white rounded-2xl border border-slate-200 p-8 sm:p-12 text-center space-y-4 shadow-sm">
                <div class="w-16 h-16 bg-emerald-100 text-[#005B3C] rounded-2xl flex items-center justify-center mx-auto text-3xl font-black shadow-inner">
                    ✓
                </div>
                <h3 class="text-2xl font-black text-slate-900">Evaluasi Berhasil Diterima</h3>
                <p class="text-slate-600 text-sm max-w-2xl mx-auto leading-relaxed">
                    Terima kasih yang sebesar-besarnya kami sampaikan kepada Bapak/Ibu pimpinan di <strong>{{ form.nama_perusahaan }}</strong> atas evaluasi obyektif dan masukan konstruktif yang telah diberikan terhadap alumni kami, <strong>{{ alumniInfo.nama }}</strong>. Data respon Anda telah tersimpan secara resmi di sistem Tracer Study UKDW.
                </p>
                <div class="pt-4 flex flex-wrap items-center justify-center gap-4">
                    <a href="https://www.ukdw.ac.id" target="_blank" class="inline-flex items-center px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors">
                        Kunjungi Situs Resmi UKDW →
                    </a>
                </div>
            </div>

            <!-- FORMULIR INPUT EVALUASI (FULL PAGE GRID) -->
            <form v-else @submit.prevent="submitForm" class="space-y-6">

                <!-- ========================================================= -->
                <!-- BAGIAN I: INFORMASI PERUSAHAAN / LEMBAGA / INSTITUSI      -->
                <!-- ========================================================= -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs space-y-6">
                    <div class="border-b border-slate-200 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-[#005B3C] text-white text-sm font-black">
                                I
                            </span>
                            <div>
                                <h2 class="text-base sm:text-lg font-bold text-slate-900 uppercase tracking-tight">
                                    Informasi tentang Perusahaan / Lembaga / Institusi
                                </h2>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Lengkapi identitas organisasi dan karakteristik tempat alumni bekerja saat ini.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Grid Form Data Instansi -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Nama Perusahaan -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Nama Perusahaan / Lembaga / Institusi <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                v-model="form.nama_perusahaan" 
                                required
                                class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-[#005B3C]/20 focus:border-[#005B3C] transition-all bg-white"
                                placeholder="Nama resmi instansi tempat alumni bekerja" 
                            />
                        </div>

                        <!-- Alamat Lengkap -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Alamat Lengkap Perusahaan <span class="text-rose-500">*</span>
                            </label>
                            <textarea 
                                v-model="form.alamat_lengkap" 
                                rows="2"
                                required
                                class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-[#005B3C]/20 focus:border-[#005B3C] transition-all bg-white"
                                placeholder="Jalan, Gedung, Kota/Kabupaten, Kode Pos..."
                            ></textarea>
                        </div>

                        <!-- No Telp / Fax -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">No. Telp / Fax Perusahaan</label>
                            <input 
                                type="text" 
                                v-model="form.no_telp_fax" 
                                class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-[#005B3C]/20 focus:border-[#005B3C] transition-all bg-white"
                                placeholder="Contoh: 0274-563929" 
                            />
                        </div>

                        <!-- Homepage (Website) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Website / Homepage Perusahaan</label>
                            <input 
                                type="text" 
                                v-model="form.homepage" 
                                class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-[#005B3C]/20 focus:border-[#005B3C] transition-all bg-white"
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
                                class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-[#005B3C]/20 focus:border-[#005B3C] transition-all bg-white"
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
                                class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-[#005B3C]/20 focus:border-[#005B3C] transition-all bg-white"
                                placeholder="atasan@perusahaan.com" 
                            />
                        </div>

                        <!-- Nomor Telepon Atasan -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Nomor Telepon Atasan</label>
                            <input 
                                type="tel" 
                                v-model="form.telepon_atasan" 
                                class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-[#005B3C]/20 focus:border-[#005B3C] transition-all bg-white"
                                placeholder="081234567890" 
                            />
                        </div>
                    </div>

                    <!-- ========================================================= -->
                    <!-- 5 PERTANYAAN PROFIL ORGANISASI & ALUMNI DI PERUSAHAAN    -->
                    <!-- ========================================================= -->
                    <div class="space-y-6 pt-6 border-t border-slate-100">
                        
                        <!-- 1. Bentuk Perusahaan / Institusi -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-2.5">
                                1. Bentuk Perusahaan / Institusi:
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                                <label 
                                    v-for="opt in ['BUMN', 'Perusahaan Terbatas', 'Koperasi', 'CV', 'Firma']" 
                                    :key="opt"
                                    class="flex items-center gap-3 p-3.5 border rounded-xl cursor-pointer text-xs font-semibold transition-all select-none"
                                    :class="form.bentuk_perusahaan === opt ? 'border-[#005B3C] bg-emerald-50 text-[#005B3C] ring-1 ring-[#005B3C] font-bold shadow-2xs' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                                >
                                    <input type="radio" v-model="form.bentuk_perusahaan" :value="opt" class="accent-[#005B3C] w-4 h-4 cursor-pointer" />
                                    <span>{{ opt }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- 2. Skala Perusahaan (Tersimpan di tabel Perusahaan) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-2.5">
                                2. Skala Perusahaan:
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <label 
                                    v-for="opt in ['Lokal', 'Nasional', 'Internasional']" 
                                    :key="opt"
                                    class="flex items-center gap-3 p-3.5 border rounded-xl cursor-pointer text-xs font-semibold transition-all select-none"
                                    :class="form.skala_perusahaan === opt ? 'border-[#005B3C] bg-emerald-50 text-[#005B3C] ring-1 ring-[#005B3C] font-bold shadow-2xs' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                                >
                                    <input type="radio" v-model="form.skala_perusahaan" :value="opt" class="accent-[#005B3C] w-4 h-4 cursor-pointer" />
                                    <span>{{ opt }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- 3. Jumlah Pegawai Keseluruhan -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-2.5">
                                3. Jumlah Pegawai keseluruhan yang bekerja di perusahaan ini:
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <label 
                                    v-for="opt in ['< 50 Orang', '51 - 100 Orang', '101 – 150 Orang', '151 – 300 Orang', '301 – 500 Orang', '> 500 Orang']" 
                                    :key="opt"
                                    class="flex items-center gap-3 p-3.5 border rounded-xl cursor-pointer text-xs font-semibold transition-all select-none"
                                    :class="form.jumlah_pegawai === opt ? 'border-[#005B3C] bg-emerald-50 text-[#005B3C] ring-1 ring-[#005B3C] font-bold shadow-2xs' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                                >
                                    <input type="radio" v-model="form.jumlah_pegawai" :value="opt" class="accent-[#005B3C] w-4 h-4 cursor-pointer" />
                                    <span>{{ opt }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- 4. Jumlah Alumni UKDW yang Bekerja -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-2.5">
                                4. Jumlah alumni UKDW yang bekerja di Perusahaan ini:
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <label 
                                    v-for="opt in ['< 5 Orang', '6 - 10 Orang', '11 – 20 Orang', '> 21 Orang']" 
                                    :key="opt"
                                    class="flex items-center gap-3 p-3.5 border rounded-xl cursor-pointer text-xs font-semibold transition-all select-none"
                                    :class="form.jumlah_alumni_ukdw === opt ? 'border-[#005B3C] bg-emerald-50 text-[#005B3C] ring-1 ring-[#005B3C] font-bold shadow-2xs' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                                >
                                    <input type="radio" v-model="form.jumlah_alumni_ukdw" :value="opt" class="accent-[#005B3C] w-4 h-4 cursor-pointer" />
                                    <span>{{ opt }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- 5. Standar Gaji Pertama -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-2.5">
                                5. Standar gaji pertama (per bulan dalam rupiah) yang diberikan Perusahaan kepada alumni UKDW:
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <label 
                                    v-for="opt in ['< 1.000.000', '1.000.000 – 1.500.000', '1.500.000 – 2.000.000', '2.000.000 – 3.000.000', '3.000.000 – 4.000.000', '> 5.000.000']" 
                                    :key="opt"
                                    class="flex items-center gap-3 p-3.5 border rounded-xl cursor-pointer text-xs font-semibold transition-all select-none"
                                    :class="form.standar_gaji_pertama === opt ? 'border-[#005B3C] bg-emerald-50 text-[#005B3C] ring-1 ring-[#005B3C] font-bold shadow-2xs' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                                >
                                    <input type="radio" v-model="form.standar_gaji_pertama" :value="opt" class="accent-[#005B3C] w-4 h-4 cursor-pointer" />
                                    <span>{{ opt }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- BAGIAN II: PENILAIAN KINERJA LULUSAN (12 ASPEK DINAMIS)   -->
                <!-- ========================================================= -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs space-y-6">
                    <div class="border-b border-slate-200 pb-4">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-[#005B3C] text-white text-sm font-black shrink-0">
                                    II
                                </span>
                                <div>
                                    <h2 class="text-base sm:text-lg font-bold text-slate-900 uppercase tracking-tight">
                                        Informasi Penilaian Kesiapan & Kinerja Lulusan (Alumni) UKDW
                                    </h2>
                                    <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">
                                        Evaluasi kesiapan kerja dan penilaian obyektif terhadap kinerja lulusan UKDW atas nama <strong class="text-slate-900 font-bold">{{ alumniInfo.nama }}</strong> yang bekerja pada perusahaan Anda.
                                    </p>
                                </div>
                            </div>

                            <!-- Tag Info Lulusan yang Dinilai (Jelas & Terpampang Nyata) -->
                            <div class="flex items-center gap-2.5 px-4 py-2 rounded-xl bg-emerald-50 border border-emerald-200/80 text-[#005B3C] shrink-0 self-start md:self-auto shadow-2xs">
                                <span class="text-base">🎓</span>
                                <div class="text-left">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-800 block leading-tight">Alumni yang Dinilai:</span>
                                    <span class="text-xs sm:text-sm font-black text-slate-900 tracking-tight">{{ alumniInfo.nama }} ({{ alumniInfo.prodi }})</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BUTIR EVALUASI KESIAPAN KERJA (DINAMIS DARI MASTER PERTANYAAN) -->
                    <div v-if="kesiapanPertanyaan" class="p-4 sm:p-5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
                        <label class="block text-xs font-bold text-slate-900 leading-snug">
                            1. Tingkat kesiapan alumni UKDW dalam bekerja di Perusahaan ini:
                            <span class="block text-[11px] font-normal text-slate-500 mt-0.5">
                                {{ kesiapanPertanyaan.deskripsi || 'Evaluasi kesiapan alumni dalam mengemban tugas dan adaptasi di tempat kerja.' }}
                            </span>
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <label 
                                v-for="opt in ['Sangat siap', 'Cukup Siap', 'Kurang siap', 'Tidak siap']" 
                                :key="opt"
                                class="flex items-center gap-3 p-3.5 border rounded-xl cursor-pointer text-xs font-semibold transition-all select-none bg-white"
                                :class="form.jawaban[kesiapanPertanyaan.kode] === opt ? 'border-[#005B3C] bg-emerald-50 text-[#005B3C] ring-1 ring-[#005B3C] font-bold shadow-2xs' : 'border-slate-200 text-slate-700 hover:bg-slate-100/60'"
                            >
                                <input 
                                    type="radio" 
                                    :name="`jawaban_${kesiapanPertanyaan.kode}`"
                                    v-model="form.jawaban[kesiapanPertanyaan.kode]" 
                                    :value="opt" 
                                    class="accent-[#005B3C] w-4 h-4 cursor-pointer" 
                                />
                                <span>{{ opt }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- SUB-HEADER PENILAIAN 12 ASPEK KINERJA -->
                    <div class="pt-2">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            2. Aspek Kinerja Lulusan (Alumni) UKDW
                        </h3>
                        <p class="text-xs text-slate-500 mb-3">
                            Seberapa tinggikah penilaian Bapak/Ibu terhadap aspek kinerja lulusan UKDW yang bekerja pada perusahaan Anda?
                        </p>
                    </div>

                    <!-- Tabel Matrix Likert Aspek Kinerja Dinamis (Sticky Header & Responsive Scroll) -->
                    <div class="overflow-auto max-h-[620px] border border-slate-200 rounded-xl shadow-2xs bg-white relative scroll-smooth">
                        <table class="w-full text-left text-xs sm:text-sm border-collapse">
                            <thead class="sticky top-0 z-20 shadow-md">
                                <tr class="bg-slate-900 text-white font-bold text-center border-b border-slate-950 text-xs">
                                    <th class="sticky top-0 z-20 bg-slate-900 p-3.5 w-12 border-r border-slate-700/60 shadow-xs">No.</th>
                                    <th class="sticky top-0 z-20 bg-slate-900 p-3.5 border-r border-slate-700/60 text-left min-w-[280px] shadow-xs">
                                        <div class="flex items-center justify-between gap-2">
                                            <span>Aspek Kinerja</span>
                                            <span class="text-[10px] font-bold text-emerald-400 bg-emerald-950/80 px-2 py-0.5 rounded border border-emerald-600/40">
                                                Lulusan: {{ alumniInfo.nama }}
                                            </span>
                                        </div>
                                    </th>
                                    <th v-for="skala in skalaNilaiOptions" :key="skala" class="sticky top-0 z-20 bg-slate-900 p-3.5 w-24 sm:w-28 text-center border-r border-slate-700/60 last:border-r-0 shadow-xs">
                                        {{ skala }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <tr 
                                    v-for="(pertanyaan, idx) in likertPertanyaanList" 
                                    :key="pertanyaan.id" 
                                    class="hover:bg-slate-50/90 transition-colors"
                                    :class="idx % 2 === 1 ? 'bg-slate-50/40' : 'bg-white'"
                                >
                                    <td class="p-3.5 text-center font-bold text-slate-400 border-r border-slate-100">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="p-3.5 border-r border-slate-100">
                                        <div class="font-bold text-slate-900 text-sm leading-snug">{{ pertanyaan.aspek }}</div>
                                        <div v-if="pertanyaan.deskripsi" class="text-xs text-slate-500 mt-1 leading-relaxed">{{ pertanyaan.deskripsi }}</div>
                                    </td>
                                    <td 
                                        v-for="skala in skalaNilaiOptions" 
                                        :key="skala" 
                                        class="p-3.5 text-center border-r border-slate-100 last:border-r-0 cursor-pointer select-none transition-colors"
                                        :class="form.jawaban[pertanyaan.kode] === skala ? 'bg-emerald-50 text-[#005B3C] font-semibold' : 'hover:bg-slate-100/60'"
                                        @click="form.jawaban[pertanyaan.kode] = skala"
                                    >
                                        <div class="flex items-center justify-center">
                                            <input 
                                                type="radio" 
                                                :name="`jawaban_${pertanyaan.kode}`"
                                                v-model="form.jawaban[pertanyaan.kode]" 
                                                :value="skala" 
                                                class="w-4 h-4 accent-[#005B3C] cursor-pointer"
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
                            class="w-full border border-slate-300 rounded-xl px-4 py-3 text-xs sm:text-sm font-medium focus:ring-2 focus:ring-[#005B3C]/20 focus:border-[#005B3C] transition-all bg-white"
                            placeholder="Saran, kritik, atau catatan khusus mengenai kinerja dan pengembangan lulusan UKDW..."
                        ></textarea>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- TOMBOL KONFIRMASI SUBMIT RESMI                            -->
                <!-- ========================================================= -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 text-center shadow-xs">
                    <p class="text-xs text-slate-500 mb-4 max-w-lg mx-auto">
                        Dengan menekan tombol di bawah, Bapak/Ibu menyatakan bahwa data penilaian dan masukan yang diberikan adalah obyektif demi kemajuan kualitas pendidikan dan kurikulum Universitas Kristen Duta Wacana.
                    </p>
                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="w-full sm:w-auto px-10 py-4 bg-[#005B3C] hover:bg-[#00472e] text-white font-extrabold text-sm sm:text-base rounded-xl transition-all shadow-md hover:shadow-lg disabled:opacity-50 cursor-pointer inline-flex items-center justify-center gap-2"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ form.processing ? 'Menyimpan Jawaban...' : 'Kirim Respon Evaluasi Kinerja' }}</span>
                    </button>
                </div>
            </form>
        </main>

        <!-- ========================================================= -->
        <!-- FOOTER RESMI UNIVERSITAS (FULL WIDTH)                      -->
        <!-- ========================================================= -->
        <footer class="bg-white border-t border-slate-200 mt-12 py-8 text-xs text-slate-500 w-full">
            <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-center sm:text-left">
                    <span class="font-bold text-slate-800 block text-sm">Pusat Karir & Alumni Universitas Kristen Duta Wacana</span>
                    <span class="text-[11px] text-slate-400">Jl. Dr. Wahidin Sudirohusodo No. 5-19, Yogyakarta 55224 | Telp: 0274-563929 | Fax: 0274-513235</span>
                </div>
                <div class="text-[11px] text-slate-400">
                    &copy; {{ new Date().getFullYear() }} UKDW. Hak Cipta Dilindungi.
                </div>
            </div>
        </footer>
    </div>
</template>
