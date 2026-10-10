<!--
  Halaman Detail Mahasiswa & Audit Kuesioner Tracer Study (Admin Biro 3)
  File: resources/js/Pages/AdminBiroTiga/AlumniShow.vue
  
  Format Ringkas & Terpadu Khusus Biro 3:
  - Per Seksi profil disatukan dalam 1 Tabel Kontinu (tanpa sub-tab bertingkat/double)
  - Indikator kelengkapan terintegrasi ringkas di tab/header (tanpa kartu besar berulang)
  - Tombol WhatsApp dilengkapi tautan kuesioner & panduan cara login alumni
  - Tombol Kirim Email langsung memproses flash email otomatis ke email alumni
  - Seluruh data bersifat READ-ONLY (Analisis & Komunikasi)
-->
<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import Sidebar from './Components/Sidebar.vue';

const props = defineProps({
    user: Object,
    alumni: {
        type: Object,
        required: true,
    },
    evaluasi: {
        type: Object,
        required: true,
    },
    sections: {
        type: Array,
        default: () => [],
    },
    prodiSections: {
        type: Array,
        default: () => [],
    },
    prodiEvaluasi: {
        type: Object,
        default: () => ({
            is_complete: false,
            percentage: 0,
            answered_count: 0,
            total_questions: 0,
        }),
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
    companies: {
        type: Array,
        default: () => [],
    },
    negaras: {
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
    pertanyaanEvaluasiAtasan: {
        type: Array,
        default: () => [],
    },
    linkedinSyncResult: {
        type: Object,
        default: null,
    },
    linkedinTraceMapping: {
        type: Array,
        default: () => [],
    },
    linkedinHistory: {
        type: Array,
        default: () => [],
    },
});

// Urutan Tab Utama: 'profil' (1), 'kuesioner' (2), 'kuesioner_prodi' (3), 'evaluasi_atasan' (4), 'linkedin' (5)
const activeMainTab = ref('profil');

// Konfigurasi Navigasi Stepper Persis Seperti Kuesioner Alumni Universitas
const stepperTabs = computed(() => {
    const list = [
        {
            id: 'profil',
            title: 'Detail Profil',
            subtitle: 'Data Pribadi & Akademik',
            isComplete: !!props.evaluasi?.profile?.is_complete,
            statusText: props.evaluasi?.profile?.is_complete 
                ? 'Lengkap (100%)' 
                : `${props.evaluasi?.profile?.percentage || 0}% Terisi`,
        },
        {
            id: 'kuesioner',
            title: 'Kuesioner Universitas',
            subtitle: 'Wajib Dikti',
            isComplete: !!props.evaluasi?.questionnaire?.is_complete,
            statusText: props.evaluasi?.questionnaire?.is_complete 
                ? 'Lengkap (100%)' 
                : `${props.evaluasi?.questionnaire?.mandatory_percentage || 0}% Wajib`,
        },
    ];

    if (props.alumni?.prodi_id) {
        list.push({
            id: 'kuesioner_prodi',
            title: 'Kuesioner Prodi',
            subtitle: props.alumni.prodi?.nama_prodi || 'Program Studi',
            isComplete: !!props.prodiEvaluasi?.is_complete,
            statusText: props.prodiEvaluasi?.is_complete 
                ? 'Lengkap (100%)' 
                : `${props.prodiEvaluasi?.percentage || 0}% Terisi`,
        });
    }

    list.push({
        id: 'evaluasi_atasan',
        title: 'Evaluasi Atasan',
        subtitle: 'Pengguna Lulusan',
        isComplete: !!props.evaluasiAtasan?.is_submitted,
        statusText: props.evaluasiAtasan?.is_submitted 
            ? 'Sudah Diisi' 
            : (props.evaluasiAtasan ? 'Pending' : 'Belum Ada'),
    });

    list.push({
        id: 'linkedin',
        title: 'Hasil LinkedIn',
        subtitle: 'Audit Rekam Karier',
        isComplete: !!props.linkedinSyncResult,
        statusText: props.linkedinSyncResult 
            ? `${props.linkedinTraceMapping?.length || 0} Data Terlacak` 
            : 'Belum Ada Data',
    });

    return list;
});

const isStepperLineCompleted = (index) => {
    return !!stepperTabs.value[index]?.isComplete;
};

// Filter Seksi pada Tabel Profil Tunggal ('all' atau kode seksi: 'pribadi', 'akademik', 'orangtua', 'karier', 'medsos')
const activeProfileSectionFilter = ref('all');

// State Pencarian Teks
const searchQueryProfile = ref('');
const searchQueryUniv = ref('');
const searchQueryProdi = ref('');

// Filter section pada tab kuesioner universitas ('all' atau ID section)
const activeSectionId = ref('all');

// Filter section pada tab kuesioner prodi
const activeProdiSectionId = ref('all');

// Modal Hubungi Alumni via WhatsApp
const showWhatsAppModal = ref(false);
const waRecipientPhone = ref('');
const waMessageText = ref('');

// Format nomor HP ke format internasional WhatsApp (62xxx)
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

// Buka Modal WhatsApp dengan template lengkap (Link kuesioner + panduan cara login)
const openWhatsAppModal = (customPhone = null, customName = null) => {
    const namaAlumni = customName || props.alumni.data_akademik?.nama || props.alumni.nama || props.alumni.user?.name || 'Alumni';
    const targetPhone = customPhone || props.alumni.nomor_telepon || props.alumni.data_akademik?.nomor_telepon || props.formData.nomor_telepon || '';
    const nimAlumni = props.alumni.nim || props.alumni.data_akademik?.nim || '-';
    const baseUrl = window.location.origin;
    const loginUrl = `${baseUrl}/login`;

    waRecipientPhone.value = targetPhone;
    waMessageText.value = `Halo Sdr/i ${namaAlumni},\n\nSalam hangat dari Biro III Kemahasiswaan & Alumni Universitas Kristen Duta Wacana (UKDW).\n\nKami mengundang Anda untuk melengkapi instrumen kuesioner Tracer Study UKDW (Kemendikbudristek Dikti) melalui portal resmi:\n👉 ${loginUrl}\n\n*Panduan Masuk ke Portal:*\n• Username: *${nimAlumni}* (NIM Anda)\n• Password: Kata sandi akun Tracer Study Anda\n\nPartisipasi Anda sangat berharga bagi evaluasi kurikulum dan akreditasi almamater kita. Jika ada kendala teknis saat masuk atau mengisi, silakan balas pesan ini.\n\nTerima kasih banyak atas kontribusi nyata Anda!\nSalam,\nBiro 3 UKDW`;
    
    showWhatsAppModal.value = true;
};

// Eksekusi buka chat WhatsApp Web / App
const kirimPesanWhatsApp = () => {
    const formatted = formatPhoneForWA(waRecipientPhone.value);
    if (!formatted) {
        Swal.fire({
            icon: 'warning',
            title: 'Nomor Tidak Tersedia',
            text: 'Nomor telepon/WhatsApp alumni ini belum terdaftar di sistem.',
            confirmButtonColor: '#0D542B',
        });
        return;
    }
    const url = `https://wa.me/${formatted}?text=${encodeURIComponent(waMessageText.value)}`;
    window.open(url, '_blank');
    showWhatsAppModal.value = false;
};

// Kirim Email Flash Otomatis ke Alumni
const kirimEmailFlash = () => {
    const targetEmail = props.alumni.email_pribadi || props.alumni.data_akademik?.email_pribadi || props.formData.email_pribadi || props.alumni.user?.email;

    if (!targetEmail) {
        Swal.fire({
            icon: 'warning',
            title: 'Email Tidak Terdaftar',
            text: 'Alumni ini belum memiliki alamat email yang tersimpan di sistem.',
            confirmButtonColor: '#0D542B',
        });
        return;
    }

    const namaAlumni = props.alumni.data_akademik?.nama || props.alumni.nama || props.alumni.user?.name || 'Alumni';

    Swal.fire({
        title: 'Kirim Email Pengingat?',
        html: `<p class="text-xs text-gray-600">Kirim email resmi pengingat tracer study beserta petunjuk login langsung ke <b>${namaAlumni}</b> (<span class="font-mono text-emerald-800">${targetEmail}</span>)?</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0D542B',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Ya, Kirim Sekarang',
        cancelButtonText: 'Batal',
    }).then((res) => {
        if (res.isConfirmed) {
            Swal.fire({
                title: 'Mengirim Email...',
                text: 'Sistem sedang memproses pengiriman email.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
            });

            router.post(`/biro3/alumni/${props.alumni.id}/send-email`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Email Berhasil Dikirim',
                        text: `Email pengingat tracer study telah dikirimkan ke ${targetEmail}.`,
                        confirmButtonColor: '#0D542B',
                    });
                },
                onError: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Mengirim Email',
                        text: 'Terjadi kendala pada pengiriman email server.',
                        confirmButtonColor: '#0D542B',
                    });
                },
            });
        }
    });
};

// Salin teks ke papan klip
const salinTeks = (text, label) => {
    if (!text || text === '-') return;
    navigator.clipboard.writeText(text).then(() => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: `${label} disalin`,
            showConfirmButton: false,
            timer: 1800,
        });
    });
};

// =========================================================================
// HELPER EVALUASI ATASAN (PENGGUNA LULUSAN)
// =========================================================================
const copySurveyUrl = () => {
    if (!props.evaluasiAtasan?.survey_url) return;
    navigator.clipboard.writeText(props.evaluasiAtasan.survey_url).then(() => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Tautan kuesioner atasan disalin!',
            showConfirmButton: false,
            timer: 1800,
        });
    });
};

const getSkorLabel = (skor) => {
    const num = Number(skor);
    if (num >= 5) return 'Sangat Baik';
    if (num === 4) return 'Baik';
    if (num === 3) return 'Cukup';
    if (num === 2) return 'Kurang';
    if (num === 1) return 'Sangat Kurang';
    return '-';
};

const getSkorBadgeClass = (skor) => {
    const num = Number(skor);
    if (num >= 4) return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    if (num === 3) return 'bg-blue-100 text-blue-800 border-blue-200';
    if (num === 2) return 'bg-amber-100 text-amber-800 border-amber-200';
    if (num === 1) return 'bg-rose-100 text-rose-800 border-rose-200';
    return 'bg-gray-100 text-gray-700 border-gray-200';
};

const evaluasiAtasanQuestionsWithAnswers = computed(() => {
    if (!props.pertanyaanEvaluasiAtasan || props.pertanyaanEvaluasiAtasan.length === 0) return [];
    
    const responsMap = {};
    if (props.evaluasiAtasan?.respons) {
        props.evaluasiAtasan.respons.forEach(r => {
            responsMap[r.pertanyaan_id] = r;
        });
    }

    return props.pertanyaanEvaluasiAtasan.map(q => {
        const resp = responsMap[q.id];
        return {
            ...q,
            skor: resp ? resp.skor : null,
            catatan: resp ? resp.catatan : null,
            has_response: !!resp,
        };
    });
});

// =========================================================================
// HELPER HASIL SCRAPING & TRACE LINKEDIN (READ-ONLY KHUSUS BIRO 3)
// =========================================================================
const searchTraceQuery = ref('');
const showRawJson = ref(false);

const filteredTraceMapping = computed(() => {
    if (!props.linkedinTraceMapping) return [];
    if (!searchTraceQuery.value.trim()) return props.linkedinTraceMapping;
    const q = searchTraceQuery.value.toLowerCase();
    return props.linkedinTraceMapping.filter(item => {
        return (item.key && item.key.toLowerCase().includes(q)) ||
               (item.label && item.label.toLowerCase().includes(q)) ||
               (item.scraped_value && String(item.scraped_value).toLowerCase().includes(q)) ||
               (item.target_table && item.target_table.toLowerCase().includes(q)) ||
               (item.target_column && item.target_column.toLowerCase().includes(q)) ||
               (item.db_value && String(item.db_value).toLowerCase().includes(q)) ||
               (item.status && item.status.toLowerCase().includes(q));
    });
});

const copyRawJson = () => {
    if (!props.linkedinSyncResult?.scraped_data) return;
    navigator.clipboard.writeText(JSON.stringify(props.linkedinSyncResult.scraped_data, null, 2))
        .then(() => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'JSON scraping berhasil disalin!',
                showConfirmButton: false,
                timer: 2000,
            });
        });
};

const getSyncBadgeClass = (status) => {
    if (status === 'synced') return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    if (status === 'conflict') return 'bg-rose-100 text-rose-800 border-rose-200';
    if (status === 'pending_sync') return 'bg-amber-100 text-amber-800 border-amber-200';
    return 'bg-gray-100 text-gray-700 border-gray-200';
};

const getSyncBadgeLabel = (status) => {
    if (status === 'synced') return 'Tersinkron Sesuai';
    if (status === 'conflict') return 'Perlu Review';
    if (status === 'pending_sync') return 'Belum Diterapkan';
    return status || '-';
};

// Getter Informasi Kelulusan
const semesterKelulusan = computed(() => {
    return props.formData.tahun_akademik_lulus || props.alumni.yudisium?.tahun_akademik_lulus || props.alumni.data_akademik?.tahun_akademik_lulus || '-';
});

const statusYudisium = computed(() => {
    return props.formData.proses_yudisium || props.alumni.yudisium?.proses_yudisium || props.alumni.yudisium?.status_lulus || props.alumni.yudisium?.keterangan_hasil_yudisium || 'Lulus';
});

// Helper Resolusi Nama Provinsi & Kabupaten
const getProvinsiName = (id) => {
    if (!id) return '-';
    const found = props.provinces.find(p => String(p.id) === String(id));
    return found ? found.nama_provinsi : id;
};

const getKabupatenName = (id) => {
    if (!id) return '-';
    const found = props.kabupatens.find(k => String(k.id) === String(id));
    return found ? found.nama_kabupaten : id;
};

// Seluruh Data Profil Alumni Disatukan per Seksi dalam 1 Array Terpadu
const unifiedProfileSections = computed(() => {
    const f = props.formData || {};
    const a = props.alumni || {};
    const da = a.data_akademik || {};
    const ot = a.orang_tua || da.orang_tua || {};
    const p = a.perusahaan || {};
    const at = a.atasan || {};
    const y = a.yudisium || da.yudisium || {};

    return [
        {
            key: 'pribadi',
            order: 1,
            title: 'SEKSI 1: DATA PRIBADI, IDENTITAS & KONTAK',
            rows: [
                { no: 1, param: 'Nama Lengkap (Sesuai Ijazah)', kategori: 'Identitas', value: f.nama || da.nama || a.nama || a.user?.name, rawType: 'text' },
                { no: 2, param: 'Nomor Induk Mahasiswa (NIM)', kategori: 'Identitas', value: f.nim || a.nim || da.nim, rawType: 'code' },
                { no: 3, param: 'Nomor Induk Kependudukan (NIK)', kategori: 'Kependudukan', value: f.nik || a.nik || da.nik, rawType: 'code' },
                { no: 4, param: 'Nomor Kartu Keluarga (No. KK)', kategori: 'Kependudukan', value: f.no_kk || da.no_kk, rawType: 'code' },
                { no: 5, param: 'Tempat Lahir', kategori: 'Identitas', value: f.tempat_lahir || da.tempat_lahir, rawType: 'text' },
                { no: 6, param: 'Tanggal Lahir', kategori: 'Identitas', value: f.tanggal_lahir || da.tanggal_lahir, rawType: 'text' },
                { no: 7, param: 'Jenis Kelamin', kategori: 'Identitas', value: f.jenis_kelamin || da.jenis_kelamin, rawType: 'text' },
                { no: 8, param: 'Agama', kategori: 'Identitas', value: f.agama || da.agama, rawType: 'text' },
                { no: 9, param: 'Golongan Darah', kategori: 'Kesehatan', value: f.golongan_darah || da.golongan_darah, rawType: 'text' },
                { no: 10, param: 'Kewarganegaraan', kategori: 'Kependudukan', value: f.warga_negara || da.warga_negara || 'WNI', rawType: 'text' },
                { no: 11, param: 'Nomor Telepon / WhatsApp', kategori: 'Kontak', value: f.nomor_telepon || a.nomor_telepon || da.nomor_telepon, rawType: 'phone', isContact: true },
                { no: 12, param: 'Email Pribadi', kategori: 'Kontak', value: f.email_pribadi || a.email_pribadi || da.email_pribadi, rawType: 'email', isEmail: true },
                { no: 13, param: 'Email Kampus (Students UKDW)', kategori: 'Kontak', value: f.email_students || da.email_students, rawType: 'email', isEmail: true },
                { no: 14, param: 'Alamat Domisili Saat Ini', kategori: 'Domisili', value: f.alamat_saat_ini || f.alamat || a.alamat || da.alamat_saat_ini, rawType: 'text' },
                { no: 15, param: 'Kelurahan / Kecamatan', kategori: 'Domisili', value: [f.kelurahan || da.kelurahan, f.kecamatan || da.kecamatan].filter(Boolean).join(', ') || null, rawType: 'text' },
                { no: 16, param: 'Kabupaten / Kota Domisili', kategori: 'Domisili', value: getKabupatenName(f.kabupaten_id || da.kabupaten_id), rawType: 'text' },
                { no: 17, param: 'Provinsi Domisili', kategori: 'Domisili', value: getProvinsiName(f.propinsi_id || da.propinsi_id), rawType: 'text' },
                { no: 18, param: 'Kode Pos Domisili', kategori: 'Domisili', value: f.kode_pos || da.kode_pos, rawType: 'code' },
                { no: 19, param: 'Nomor Pokok Wajib Pajak (NPWP)', kategori: 'Perpajakan', value: f.npwp || a.npwp, rawType: 'code' },
                { no: 20, param: 'Nomor BPJS Kesehatan', kategori: 'Jaminan', value: f.no_bpjs || da.no_bpjs, rawType: 'code' },
            ]
        },
        {
            key: 'akademik',
            order: 2,
            title: 'SEKSI 2: DATA AKADEMIK, KELULUSAN & YUDISIUM',
            rows: [
                { no: 1, param: 'Fakultas', kategori: 'Akademik', value: f.fakultas || a.prodi?.fakultas?.nama_fakultas || da.fakultas, rawType: 'text' },
                { no: 2, param: 'Program Studi', kategori: 'Akademik', value: f.program_studi || a.prodi?.nama_prodi || da.program_studi, rawType: 'text' },
                { no: 3, param: 'Kode Program Studi', kategori: 'Akademik', value: f.kode_prodi || a.prodi?.kode_prodi, rawType: 'code' },
                { no: 4, param: 'Jenjang (Strata)', kategori: 'Akademik', value: f.strata || da.strata || 'S1', rawType: 'text' },
                { no: 5, param: 'Tahun Angkatan Masuk', kategori: 'Akademik', value: f.angkatan_masuk || da.angkatan_masuk, rawType: 'text' },
                { no: 6, param: 'Tahun Kelulusan', kategori: 'Akademik', value: f.tahun_lulus || da.tahun_lulus || a.tahun_lulus, rawType: 'text' },
                { no: 7, param: 'Periode Semester Kelulusan', kategori: 'Akademik', value: f.tahun_akademik_lulus || da.tahun_akademik_lulus, rawType: 'text' },
                { no: 8, param: 'IPK Kelulusan (Kumulatif)', kategori: 'Akademik', value: f.ipk || da.ip_kumulatif, rawType: 'text' },
                { no: 9, param: 'Total SKS Ditempuh', kategori: 'Akademik', value: f.total_sks || da.total_sks, rawType: 'text' },
                { no: 10, param: 'Nomor Ijazah Nasional (PIN)', kategori: 'Ijazah', value: f.no_ijazah || da.no_ijazah, rawType: 'code' },
                { no: 11, param: 'Status / Proses Yudisium', kategori: 'Yudisium', value: f.proses_yudisium || y.status_lulus || 'Lulus', rawType: 'text' },
                { no: 12, param: 'Judul Tugas Akhir / Skripsi', kategori: 'Tugas Akhir', value: f.judul_ta || y.judul_ta, rawType: 'text' },
                { no: 13, param: 'Dosen Pembimbing Utama (1)', kategori: 'Pembimbing', value: f.dosen_pembimbing_1 || y.dosen_pembimbing_1, rawType: 'text' },
                { no: 14, param: 'Dosen Pembimbing Pendamping (2)', kategori: 'Pembimbing', value: f.dosen_pembimbing_2 || y.dosen_pembimbing_2, rawType: 'text' },
            ]
        },
        {
            key: 'orangtua',
            order: 3,
            title: 'SEKSI 3: DATA ORANG TUA / KELUARGA',
            rows: [
                { no: 1, param: 'Nama Orang Tua / Wali', kategori: 'Keluarga', value: f.nama_orang_tua || ot.nama_orang_tua, rawType: 'text' },
                { no: 2, param: 'Pekerjaan Orang Tua / Wali', kategori: 'Keluarga', value: f.pekerjaan_orang_tua || ot.pekerjaan, rawType: 'text' },
                { no: 3, param: 'Nomor Telepon Orang Tua', kategori: 'Kontak', value: f.nomor_telepon_orang_tua || ot.nomor_telepon, rawType: 'phone', isContact: true },
                { no: 4, param: 'Alamat Rumah Orang Tua', kategori: 'Alamat', value: f.alamat_orang_tua || ot.alamat, rawType: 'text' },
                { no: 5, param: 'Kabupaten / Kota Orang Tua', kategori: 'Alamat', value: getKabupatenName(f.kabupaten_id_orang_tua || ot.kabupaten_id), rawType: 'text' },
                { no: 6, param: 'Provinsi Orang Tua', kategori: 'Alamat', value: getProvinsiName(f.propinsi_id_orang_tua || ot.propinsi_id), rawType: 'text' },
            ]
        },
        {
            key: 'karier',
            order: 4,
            title: 'SEKSI 4: DATA KARIR, TEMPAT KERJA & ATASAN',
            rows: [
                { no: 1, param: 'Status / Kategori Aktivitas Karir', kategori: 'Status', value: f.kategori_pekerjaan || a.kategori_pekerjaan, rawType: 'badge' },
                { no: 2, param: 'Nama Perusahaan / Instansi / Usaha', kategori: 'Organisasi', value: f.nama_perusahaan || p.nama_perusahaan, rawType: 'text' },
                { no: 3, param: 'Status Verifikasi Perusahaan', kategori: 'Organisasi', value: f.perusahaan_status_verifikasi || p.status_verifikasi, rawType: 'badge' },
                { no: 4, param: 'Posisi / Jabatan Pekerjaan', kategori: 'Jabatan', value: f.posisi_jabatan || a.posisi_jabatan, rawType: 'text' },
                { no: 5, param: 'Skala Perusahaan', kategori: 'Klasifikasi', value: f.perusahaan_skala || p.skala, rawType: 'text' },
                { no: 6, param: 'Jenis / Sektor Industri', kategori: 'Klasifikasi', value: f.perusahaan_jenis_perusahaan || p.jenis_perusahaan, rawType: 'text' },
                { no: 7, param: 'Wilayah Penempatan Kerja', kategori: 'Lokasi', value: f.perusahaan_jenis_lokasi || p.jenis_lokasi || 'Dalam Negeri', rawType: 'text' },
                { no: 8, param: 'Provinsi & Kota Kantor', kategori: 'Lokasi', value: [getProvinsiName(f.perusahaan_propinsi_id || p.propinsi_id), getKabupatenName(f.perusahaan_kabupaten_id || p.kabupaten_id)].filter(v => v !== '-').join(', ') || null, rawType: 'text' },
                { no: 9, param: 'Alamat Kantor Lengkap', kategori: 'Lokasi', value: f.perusahaan_alamat || p.alamat, rawType: 'text' },
                { no: 10, param: 'Estimasi Pendapatan / Gaji Pokok', kategori: 'Finansial', value: f.gaji ? `Rp ${Number(f.gaji).toLocaleString('id-ID')}` : (a.gaji ? `Rp ${Number(a.gaji).toLocaleString('id-ID')}` : null), rawType: 'text' },
                { no: 11, param: 'Nama Atasan Langsung', kategori: 'Atasan', value: f.nama_atasan || at.nama, rawType: 'text' },
                { no: 12, param: 'Email Resmi Atasan', kategori: 'Atasan', value: f.email_atasan || at.email, rawType: 'email', isEmail: true },
                { no: 13, param: 'Nomor Telepon Atasan', kategori: 'Atasan', value: f.telepon_atasan || at.telepon, rawType: 'phone', isContact: true },
                { no: 14, param: 'Perguruan Tinggi Studi Lanjut (Jika Kuliah)', kategori: 'Studi Lanjut', value: f.perguruan_tinggi || a.perguruan_tinggi, rawType: 'text' },
                { no: 15, param: 'Program Studi Lanjut', kategori: 'Studi Lanjut', value: f.pendidikan_prodi || a.pendidikan_prodi, rawType: 'text' },
            ]
        },
        {
            key: 'medsos',
            order: 5,
            title: 'SEKSI 5: PORTOFOLIO PROFESIONAL & MEDIA SOSIAL',
            rows: [
                { no: 1, param: 'Profil URL LinkedIn', kategori: 'Jejaring', value: f.linkedin_url || a.linkedin_url, rawType: 'link' },
                { no: 2, param: 'Username LinkedIn', kategori: 'Jejaring', value: f.linkedin_username || a.linkedin_username, rawType: 'text' },
                { no: 3, param: 'Keahlian Utama (Skills)', kategori: 'Kompetensi', value: f.skills || a.skills || a.expert, rawType: 'text' },
                { no: 4, param: 'Pengalaman Kerja (Experience)', kategori: 'Portofolio', value: f.experience || a.experience || a.minat, rawType: 'text' },
                { no: 5, param: 'Profil URL Instagram', kategori: 'Media Sosial', value: f.instagram_url || a.instagram_url, rawType: 'link' },
            ]
        }
    ];
});

// Filter Seksi Profil Tunggal Berdasarkan Pilihan & Pencarian Teks
const filteredUnifiedProfileSections = computed(() => {
    let sections = unifiedProfileSections.value;

    if (activeProfileSectionFilter.value !== 'all') {
        sections = sections.filter(s => s.key === activeProfileSectionFilter.value);
    }

    if (!searchQueryProfile.value.trim()) {
        return sections;
    }

    const q = searchQueryProfile.value.toLowerCase();
    return sections.map(sec => {
        const matchingRows = sec.rows.filter(r => 
            (r.param && r.param.toLowerCase().includes(q)) ||
            (r.kategori && r.kategori.toLowerCase().includes(q)) ||
            (r.value && String(r.value).toLowerCase().includes(q))
        );
        return {
            ...sec,
            rows: matchingRows,
        };
    }).filter(sec => sec.rows.length > 0);
});

// Filter Sections Kuesioner Universitas
const filteredSections = computed(() => {
    let list = activeSectionId.value === 'all'
        ? props.sections
        : props.sections.filter(s => s.id === activeSectionId.value);

    if (!searchQueryUniv.value.trim()) {
        return list;
    }

    const q = searchQueryUniv.value.toLowerCase();
    return list.map(section => {
        const matchingQuestions = (section.subpertanyaans || []).filter(item => {
            return (item.kode_pertanyaan && item.kode_pertanyaan.toLowerCase().includes(q)) ||
                   (item.subpertanyaan && item.subpertanyaan.toLowerCase().includes(q)) ||
                   (item.answer && String(item.answer).toLowerCase().includes(q)) ||
                   (item.type && item.type.toLowerCase().includes(q));
        });
        return {
            ...section,
            subpertanyaans: matchingQuestions,
        };
    }).filter(s => s.subpertanyaans.length > 0);
});

// Filter Sections Kuesioner Prodi
const filteredProdiSections = computed(() => {
    let list = activeProdiSectionId.value === 'all'
        ? props.prodiSections
        : props.prodiSections.filter(s => s.id === activeProdiSectionId.value);

    if (!searchQueryProdi.value.trim()) {
        return list;
    }

    const q = searchQueryProdi.value.toLowerCase();
    return list.map(section => {
        const matchingQuestions = (section.questions || []).filter(item => {
            return (item.code && item.code.toLowerCase().includes(q)) ||
                   (item.question_text && item.question_text.toLowerCase().includes(q)) ||
                   (item.answer_text && String(item.answer_text).toLowerCase().includes(q)) ||
                   (item.type && item.type.toLowerCase().includes(q));
        });
        return {
            ...section,
            questions: matchingQuestions,
        };
    }).filter(s => s.questions.length > 0);
});
</script>

<template>
    <Head :title="`Detail Mahasiswa - ${alumni.data_akademik?.nama || alumni.nim} - Biro 3 UKDW`" />

    <div class="min-h-screen bg-[#f8fafc] text-gray-800 font-sans flex overscroll-none">
        <!-- Sidebar Resmi Biro 3 -->
        <Sidebar :user="user" />

        <!-- Area Konten Utama -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-72 overscroll-none">
            
            <!-- Header Solid Hijau Resmi UKDW #0D542B -->
            <header class="bg-[#0D542B] text-white py-6 px-4 sm:px-6 lg:px-8 border-b border-[#0A4322]">
                <div class="max-w-[1440px] mx-auto">
                    <!-- Breadcrumbs -->
                    <div class="flex items-center gap-2 text-xs text-white/80 font-medium mb-3">
                        <Link href="/biro3/dashboard" class="hover:underline">Dashboard</Link>
                        <span>/</span>
                        <Link href="/biro3/alumni" class="hover:underline">Data Alumni</Link>
                        <span>/</span>
                        <span class="text-white font-bold bg-white/10 px-2.5 py-0.5 rounded-md">Detail Mahasiswa</span>
                    </div>

                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                        <div>
                            <!-- Badges Header Solid UKDW -->
                            <div class="flex flex-wrap items-center gap-2.5 mb-2.5">
                                <span class="px-3.5 py-1 bg-[#FDC700] text-black font-extrabold text-xs rounded-full">
                                    Yudisium: {{ statusYudisium }}
                                </span>
                                <span class="px-3.5 py-1 bg-black/20 text-white font-medium text-xs rounded-full">
                                    Periode: {{ semesterKelulusan }}
                                </span>
                                <span 
                                    class="px-3.5 py-1 text-xs font-bold rounded-full"
                                    :class="evaluasi.is_complete ? 'bg-white text-[#0D542B]' : 'bg-[#FDC700] text-black'"
                                >
                                    Status Tracer: {{ evaluasi.status }}
                                </span>
                            </div>

                            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                                {{ alumni.data_akademik?.nama || alumni.user?.name || alumni.nama || 'Mahasiswa UKDW' }}
                            </h1>
                            
                            <p class="text-white/90 text-xs sm:text-sm mt-1 font-medium">
                                NIM: <span class="font-mono font-bold text-white">{{ alumni.nim }}</span> &bull; 
                                Program Studi: <span class="font-semibold text-white">{{ alumni.prodi?.nama_prodi || '-' }}</span> &bull;
                                Fakultas: <span class="font-semibold text-white">{{ alumni.prodi?.fakultas?.nama_fakultas || '-' }}</span>
                            </p>
                        </div>

                        <!-- Action Buttons Hubungi & Ekspor -->
                        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
                            <!-- Tombol WhatsApp Cepat (Lengkap Link & Cara Login) -->
                            <button 
                                @click="openWhatsAppModal()"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm cursor-pointer active:scale-95"
                                title="Kirim pesan WhatsApp resmi berisi tautan portal & panduan login"
                            >
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                <span>Hubungi WA</span>
                            </button>

                            <!-- Tombol Email Flash Cepat -->
                            <button 
                                @click="kirimEmailFlash()"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm cursor-pointer active:scale-95"
                                title="Kirim email pengingat tracer study resmi ke email alumni"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                <span>Kirim Email</span>
                            </button>

                            <!-- Tombol Download Excel -->
                            <a 
                                :href="`/biro3/alumni/${alumni.id}/export-excel`"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#FDC700] hover:bg-[#e5b400] text-black text-xs font-extrabold rounded-xl transition-all shadow-sm cursor-pointer"
                                title="Download berkas Excel (.xls) hasil kuesioner alumni ini"
                            >
                                <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                <span>Export Excel</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="max-w-[1440px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5 pb-16">

                <!-- ============================================================= -->
                <!-- NAVIGASI STEPPER PERSIS SEPERTI KUESIONER ALUMNI UNIVERSITAS  -->
                <!-- ============================================================= -->
                <div class="w-full bg-white rounded-2xl shadow-xs border border-gray-200 overflow-x-auto py-3.5 sm:py-4 px-4 sm:px-6">
                    <div class="flex items-center justify-between min-w-max max-w-5xl mx-auto">
                        <template v-for="(tab, index) in stepperTabs" :key="tab.id">
                            <!-- 1. Bulatan & Label Tahapan -->
                            <div 
                                class="flex flex-col relative items-center justify-center cursor-pointer group px-2 sm:px-4 py-1 transition-all duration-200"
                                @click="activeMainTab = tab.id"
                                :title="tab.title"
                            >
                                <!-- Lingkaran Angka / Centang Selesai -->
                                <div 
                                    class="flex items-center justify-center w-10 h-10 sm:w-11 sm:h-11 md:w-12 md:h-12 rounded-full font-black text-xs sm:text-sm md:text-base transition-all duration-200 z-10 shadow-xs relative"
                                    :class="[
                                        activeMainTab === tab.id
                                            ? 'bg-[#FFD700] text-[#005B3C] shadow-md scale-105 sm:scale-110 ring-2 sm:ring-4 ring-[#005B3C]/20' : 
                                        (tab.isComplete 
                                            ? 'bg-[#005B3C] text-white shadow-xs group-hover:bg-[#00482f] group-hover:scale-105' : 
                                            'bg-gray-100 text-gray-400 group-hover:bg-gray-200 group-hover:text-gray-600')
                                    ]"
                                >
                                    <!-- 1.1 Tanda centang putih jika seksi sudah lengkap terisi dan tidak sedang dibuka -->
                                    <svg 
                                        v-if="tab.isComplete && activeMainTab !== tab.id" 
                                        class="w-5 h-5 text-white" 
                                        fill="none" 
                                        stroke="currentColor" 
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    
                                    <!-- 1.2 Angka urutan tahapan (1, 2, 3..) jika sedang aktif atau belum selesai -->
                                    <span v-else>{{ index + 1 }}</span>

                                    <!-- 1.3 Badge mini centang di sudut kanan atas jika tahapan aktif ini sudah lengkap -->
                                    <span 
                                        v-if="activeMainTab === tab.id && tab.isComplete" 
                                        class="absolute -top-1 -right-1 w-4 h-4 bg-[#005B3C] text-white rounded-full flex items-center justify-center text-[9px] font-black shadow-xs ring-2 ring-white"
                                        title="Bagian ini sudah lengkap"
                                    >
                                        ✓
                                    </span>
                                </div>
                                
                                <!-- Judul Nama Seksi di Bawah Lingkaran -->
                                <span 
                                    class="text-[10px] sm:text-[11px] md:text-xs mt-1.5 sm:mt-2 text-center w-24 sm:w-28 leading-tight transition-colors line-clamp-2"
                                    :class="[
                                        activeMainTab === tab.id
                                            ? 'text-[#005B3C] font-black' : 
                                        (tab.isComplete 
                                            ? 'text-[#005B3C] font-bold group-hover:text-[#00482f]' : 
                                            'text-gray-400 group-hover:text-gray-600 font-medium')
                                    ]"
                                >
                                    {{ tab.title }}
                                </span>

                                <!-- Subtitle / Nama Prodi jika ada -->
                                <span 
                                    v-if="tab.subtitle"
                                    class="text-[9px] sm:text-[10px] text-center text-gray-400 truncate max-w-[110px]"
                                >
                                    {{ tab.subtitle }}
                                </span>

                                <!-- Keterangan Status Kelengkapan di Bawah Judul -->
                                <span 
                                    class="text-[9px] sm:text-[10px] mt-0.5 text-center font-bold"
                                    :class="tab.isComplete ? 'text-[#005B3C]' : 'text-gray-400'"
                                >
                                    {{ tab.statusText }}
                                </span>
                            </div>
                            
                            <!-- 2. Garis Penghubung Antar Lingkaran Stepper -->
                            <div 
                                v-if="index < stepperTabs.length - 1" 
                                class="flex-1 h-1 sm:h-1.5 rounded-full transition-colors duration-300 mx-1 sm:mx-2 md:mx-3 min-w-[14px] sm:min-w-[20px]" 
                                :class="isStepperLineCompleted(index) ? 'bg-[#005B3C]' : 'bg-gray-200'"
                            ></div>
                        </template>
                    </div>
                </div>

                <!-- ============================================================= -->
                <!-- HALAMAN 1: DETAIL PROFIL MAHASISWA (1 TABEL KONTINU UTUH)     -->
                <!-- ============================================================= -->
                <div v-if="activeMainTab === 'profil'" class="space-y-4">
                    
                    <!-- Toolbar Filter Seksi & Pencarian -->
                    <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-xs border border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Tampilkan Seksi:</span>
                            <select 
                                v-model="activeProfileSectionFilter"
                                class="text-xs font-bold bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-gray-800 focus:ring-2 focus:ring-[#0D542B] focus:outline-none"
                            >
                                <option value="all">Semua Seksi Profil (Tampilkan Seluruh 5 Bagian)</option>
                                <option value="pribadi">Seksi 1: Data Pribadi, Identitas & Kontak</option>
                                <option value="akademik">Seksi 2: Data Akademik, Kelulusan & Yudisium</option>
                                <option value="orangtua">Seksi 3: Data Orang Tua / Keluarga</option>
                                <option value="karier">Seksi 4: Data Karir, Tempat Kerja & Atasan</option>
                                <option value="medsos">Seksi 5: Portofolio Profesional & Media Sosial</option>
                            </select>
                        </div>

                        <!-- Kotak Pencarian Baris Data Profil -->
                        <div class="relative w-full md:w-80">
                            <input 
                                v-model="searchQueryProfile"
                                type="text" 
                                placeholder="Cari atribut atau isi data mahasiswa..."
                                class="w-full text-xs bg-gray-50 border border-gray-300 rounded-xl pl-9 pr-4 py-2 text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-[#0D542B] focus:outline-none"
                            />
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </div>

                    <!-- TABEL TUNGGAL KONTINU PROFIL MAHASISWA (READ-ONLY) -->
                    <div class="bg-white rounded-2xl shadow-xs border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-100 border-b border-gray-200 text-[11px] font-extrabold text-gray-700 uppercase tracking-wider">
                                        <th class="py-3 px-4 w-12 text-center border-r border-gray-200">No</th>
                                        <th class="py-3 px-4 w-72 border-r border-gray-200">Parameter / Bidang Data</th>
                                        <th class="py-3 px-4 w-28 text-center border-r border-gray-200">Kategori</th>
                                        <th class="py-3 px-4 w-28 text-center border-r border-gray-200">Status</th>
                                        <th class="py-3 px-4 border-r border-gray-200">Nilai / Isi Data Resmi</th>
                                        <th class="py-3 px-4 w-40 text-center">Aksi Cepat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="section in filteredUnifiedProfileSections" :key="section.key">
                                        <!-- Header Seksi Kuning Khas -->
                                        <tr class="bg-[#FDC700] text-black font-extrabold text-xs border-y-2 border-yellow-400">
                                            <td colspan="6" class="py-2.5 px-4">
                                                <div class="flex items-center justify-between">
                                                    <span class="uppercase tracking-wide font-black">
                                                        {{ section.title }}
                                                    </span>
                                                    <span class="text-[11px] font-bold px-2.5 py-0.5 bg-[#0D542B] text-white rounded-full">
                                                        {{ section.rows.length }} Baris Data
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Baris Butir Data per Seksi -->
                                        <template v-for="(row, idx) in section.rows" :key="`${section.key}-${row.no}`">
                                            <tr 
                                                class="border-b border-gray-200 text-xs transition-colors"
                                                :class="row.value ? 'bg-white hover:bg-gray-50' : 'bg-[#FFF1F2] hover:bg-rose-100/60'"
                                            >
                                                <!-- No -->
                                                <td class="py-3 px-4 text-center border-r border-gray-200 text-gray-500 font-mono">{{ idx + 1 }}</td>
                                                
                                                <!-- Parameter -->
                                                <td class="py-3 px-4 border-r border-gray-200 font-bold text-gray-900">
                                                    {{ row.param }}
                                                </td>

                                                <!-- Kategori -->
                                                <td class="py-3 px-4 text-center border-r border-gray-200 text-gray-500 font-medium text-[11px]">
                                                    {{ row.kategori }}
                                                </td>

                                                <!-- Status Terisi / Kosong -->
                                                <td class="py-3 px-4 text-center border-r border-gray-200">
                                                    <span 
                                                        class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider"
                                                        :class="row.value ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700'"
                                                    >
                                                        {{ row.value ? 'TERISI' : 'KOSONG' }}
                                                    </span>
                                                </td>

                                                <!-- Nilai / Data -->
                                                <td class="py-3 px-4 border-r border-gray-200">
                                                    <template v-if="row.value">
                                                        <!-- Format Link -->
                                                        <a 
                                                            v-if="row.rawType === 'link'"
                                                            :href="row.value"
                                                            target="_blank"
                                                            class="text-blue-600 hover:underline font-mono text-[11px] break-all inline-flex items-center gap-1"
                                                        >
                                                            <span>{{ row.value }}</span>
                                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                        </a>

                                                        <!-- Format Code (NIM, NIK, dll) -->
                                                        <span v-else-if="row.rawType === 'code'" class="font-mono font-bold text-[#0D542B] text-xs">
                                                            {{ row.value }}
                                                        </span>

                                                        <!-- Format Badge -->
                                                        <span v-else-if="row.rawType === 'badge'" class="px-2.5 py-1 bg-slate-100 border border-slate-300 rounded-lg font-bold text-slate-800 text-[11px]">
                                                            {{ row.value }}
                                                        </span>

                                                        <!-- Format Teks Biasa -->
                                                        <span v-else class="text-gray-900 font-medium leading-relaxed">
                                                            {{ row.value }}
                                                        </span>
                                                    </template>
                                                    <template v-else>
                                                        <span class="text-rose-500 italic text-[11px] font-normal">&mdash; Belum diisi alumni &mdash;</span>
                                                    </template>
                                                </td>

                                                <!-- Aksi Cepat Biro 3 -->
                                                <td class="py-3 px-4 text-center">
                                                    <div class="flex items-center justify-center gap-1.5">
                                                        <!-- Salin Teks -->
                                                        <button 
                                                            v-if="row.value"
                                                            @click="salinTeks(row.value, row.param)"
                                                            class="p-1.5 text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer"
                                                            title="Salin isi data ini"
                                                        >
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                        </button>

                                                        <!-- Hubungi WhatsApp (Jika Phone) -->
                                                        <button 
                                                            v-if="row.isContact && row.value"
                                                            @click="openWhatsAppModal(row.value, row.param.includes('Orang Tua') ? 'Orang Tua Alumni' : (row.param.includes('Atasan') ? 'Atasan Alumni' : null))"
                                                            class="px-2 py-1 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 text-[11px] font-bold rounded-md transition-colors cursor-pointer flex items-center gap-1"
                                                            title="Hubungi nomor ini via WhatsApp"
                                                        >
                                                            <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                                            <span>Chat</span>
                                                        </button>

                                                        <!-- Kirim Email Flash (Jika Email) -->
                                                        <button 
                                                            v-if="row.isEmail && row.value"
                                                            @click="kirimEmailFlash()"
                                                            class="px-2 py-1 bg-blue-100 hover:bg-blue-200 text-blue-800 text-[11px] font-bold rounded-md transition-colors cursor-pointer flex items-center gap-1"
                                                            title="Kirim email pengingat kuesioner"
                                                        >
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                                            <span>Email</span>
                                                        </button>

                                                        <!-- Buka Tautan (Jika Link) -->
                                                        <a 
                                                            v-if="row.rawType === 'link' && row.value"
                                                            :href="row.value"
                                                            target="_blank"
                                                            class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 text-[11px] font-bold rounded-md transition-colors flex items-center gap-1"
                                                        >
                                                            <span>Buka</span>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ============================================================= -->
                <!-- HALAMAN 2: KUESIONER UNIVERSITAS (TABEL EXCEL READ-ONLY)      -->
                <!-- ============================================================= -->
                <div v-if="activeMainTab === 'kuesioner'" class="space-y-4">
                    <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-xs border border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Pilih Section:</span>
                            <select 
                                v-model="activeSectionId"
                                class="text-xs font-bold bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-gray-800 focus:ring-2 focus:ring-[#0D542B] focus:outline-none"
                            >
                                <option value="all">Semua Section (Tampilkan Seluruh Butir)</option>
                                <option v-for="sec in sections" :key="sec.id" :value="sec.id">
                                    Seksi {{ sec.order }}: {{ sec.title }}
                                </option>
                            </select>
                        </div>

                        <div class="relative w-full md:w-80">
                            <input 
                                v-model="searchQueryUniv"
                                type="text" 
                                placeholder="Cari kode pertanyaan / isi jawaban..."
                                class="w-full text-xs bg-gray-50 border border-gray-300 rounded-xl pl-9 pr-4 py-2 text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-[#0D542B] focus:outline-none"
                            />
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-xs border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-100 border-b border-gray-200 text-[11px] font-extrabold text-gray-700 uppercase tracking-wider">
                                        <th class="py-3 px-4 w-12 text-center border-r border-gray-200">No</th>
                                        <th class="py-3 px-4 w-28 text-center border-r border-gray-200">Kode</th>
                                        <th class="py-3 px-4 border-r border-gray-200">Pertanyaan Instrumen Tracer Study (Kemendikbud Dikti)</th>
                                        <th class="py-3 px-4 w-28 text-center border-r border-gray-200">Tipe</th>
                                        <th class="py-3 px-4 w-24 text-center border-r border-gray-200">Sifat</th>
                                        <th class="py-3 px-4 w-32 text-center border-r border-gray-200">Status</th>
                                        <th class="py-3 px-4 w-72">Jawaban Responden</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="section in filteredSections" :key="section.id">
                                        <!-- Header Seksi Kuesioner dengan Badge Status Akurat -->
                                        <tr class="bg-[#FDC700] text-black font-extrabold text-xs border-y-2 border-yellow-400">
                                            <td colspan="7" class="py-3 px-4">
                                                <div class="flex items-center justify-between">
                                                    <span>SEKSI {{ section.order }}: {{ section.title }}</span>
                                                    <div class="flex items-center gap-2">
                                                        <span v-if="section.unanswered_mandatory_count > 0" class="text-[11px] font-bold px-2.5 py-0.5 bg-red-600 text-white rounded-full">
                                                            {{ section.unanswered_mandatory_count }} Wajib Belum Terisi
                                                        </span>
                                                        <span v-else-if="section.unanswered_optional_count > 0" class="text-[11px] font-bold px-2.5 py-0.5 bg-amber-600 text-white rounded-full">
                                                            Wajib Lengkap &bull; {{ section.unanswered_optional_count }} Opsional Belum Diisi
                                                        </span>
                                                        <span v-else class="text-[11px] font-bold px-2.5 py-0.5 bg-[#0D542B] text-white rounded-full">
                                                            Seksi Lengkap (100%)
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>

                                        <template v-for="(q, idx) in section.subpertanyaans" :key="q.id">
                                            <tr 
                                                v-if="q.is_header"
                                                class="bg-[#FEF08A] text-yellow-950 font-bold text-xs border-b border-yellow-200"
                                            >
                                                <td class="py-2.5 px-4 text-center border-r border-yellow-200 text-gray-500 font-mono">{{ idx + 1 }}</td>
                                                <td class="py-2.5 px-4 text-center border-r border-yellow-200 font-mono font-bold">{{ q.kode_pertanyaan }}</td>
                                                <td colspan="5" class="py-2.5 px-4 uppercase tracking-wide">
                                                    {{ q.subpertanyaan }}
                                                </td>
                                            </tr>

                                            <tr 
                                                v-else
                                                class="border-b border-gray-200 text-xs transition-colors"
                                                :class="q.is_answered ? 'bg-white hover:bg-gray-50' : 'bg-[#FFF1F2] hover:bg-rose-100/60'"
                                            >
                                                <td class="py-3 px-4 text-center border-r border-gray-200 text-gray-500 font-mono">{{ idx + 1 }}</td>
                                                <td class="py-3 px-4 text-center border-r border-gray-200 font-mono font-bold text-[#0D542B]">{{ q.kode_pertanyaan }}</td>
                                                <td class="py-3 px-4 border-r border-gray-200 font-medium text-gray-900 leading-relaxed">{{ q.subpertanyaan }}</td>
                                                <td class="py-3 px-4 text-center border-r border-gray-200 text-gray-500 font-mono text-[11px]">{{ q.type }}</td>
                                                <td class="py-3 px-4 text-center border-r border-gray-200">
                                                    <span 
                                                        class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase"
                                                        :class="q.is_mandatory ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'"
                                                    >
                                                        {{ q.is_mandatory ? 'Wajib' : 'Opsional' }}
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4 text-center border-r border-gray-200">
                                                    <span 
                                                        v-if="q.is_answered" 
                                                        class="font-black text-sm text-[#0D542B]"
                                                        title="Terjawab"
                                                    >
                                                        v
                                                    </span>
                                                    <span 
                                                        v-else 
                                                        class="font-black text-sm text-red-600"
                                                        title="Belum Dijawab"
                                                    >
                                                        x
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4 font-semibold" :class="q.is_answered ? 'text-gray-900' : 'text-rose-500 italic'">
                                                    {{ q.is_answered ? q.answer : '— Belum diisi —' }}
                                                </td>
                                            </tr>
                                        </template>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ============================================================= -->
                <!-- HALAMAN 3: KUESIONER PROGRAM STUDI (TABEL READ-ONLY)          -->
                <!-- ============================================================= -->
                <div v-if="activeMainTab === 'kuesioner_prodi'" class="space-y-4">
                    <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-xs border border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Pilih Section Prodi:</span>
                            <select 
                                v-model="activeProdiSectionId"
                                class="text-xs font-bold bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-gray-800 focus:ring-2 focus:ring-[#0D542B] focus:outline-none"
                            >
                                <option value="all">Semua Section Prodi (Tampilkan Seluruh Butir)</option>
                                <option v-for="sec in prodiSections" :key="sec.id" :value="sec.id">
                                    Seksi {{ sec.order }}: {{ sec.title }}
                                </option>
                            </select>
                        </div>

                        <div class="relative w-full md:w-80">
                            <input 
                                v-model="searchQueryProdi"
                                type="text" 
                                placeholder="Cari kode pertanyaan / isi jawaban prodi..."
                                class="w-full text-xs bg-gray-50 border border-gray-300 rounded-xl pl-9 pr-4 py-2 text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-[#0D542B] focus:outline-none"
                            />
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-xs border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-100 border-b border-gray-200 text-[11px] font-extrabold text-gray-700 uppercase tracking-wider">
                                        <th class="py-3 px-4 w-12 text-center border-r border-gray-200">No</th>
                                        <th class="py-3 px-4 w-28 text-center border-r border-gray-200">Kode</th>
                                        <th class="py-3 px-4 border-r border-gray-200">Pertanyaan Khusus Program Studi</th>
                                        <th class="py-3 px-4 w-28 text-center border-r border-gray-200">Tipe</th>
                                        <th class="py-3 px-4 w-24 text-center border-r border-gray-200">Sifat</th>
                                        <th class="py-3 px-4 w-32 text-center border-r border-gray-200">Status</th>
                                        <th class="py-3 px-4 w-72">Jawaban Responden</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="section in filteredProdiSections" :key="section.id">
                                        <tr class="bg-[#FDC700] text-black font-extrabold text-xs border-y-2 border-yellow-400">
                                            <td colspan="7" class="py-3 px-4">
                                                <div class="flex items-center justify-between">
                                                    <span>SEKSI {{ section.order }}: {{ section.title }}</span>
                                                    <div class="flex items-center gap-2">
                                                        <span v-if="section.unanswered_mandatory_count > 0" class="text-[11px] font-bold px-2.5 py-0.5 bg-red-600 text-white rounded-full">
                                                            {{ section.unanswered_mandatory_count }} Wajib Belum Terisi
                                                        </span>
                                                        <span v-else-if="section.unanswered_optional_count > 0" class="text-[11px] font-bold px-2.5 py-0.5 bg-amber-600 text-white rounded-full">
                                                            Wajib Lengkap &bull; {{ section.unanswered_optional_count }} Opsional Belum Diisi
                                                        </span>
                                                        <span v-else class="text-[11px] font-bold px-2.5 py-0.5 bg-[#0D542B] text-white rounded-full">
                                                            Seksi Lengkap (100%)
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>

                                        <template v-for="(q, idx) in section.questions" :key="q.id">
                                            <tr 
                                                v-if="q.is_header"
                                                class="bg-[#FEF08A] text-yellow-950 font-bold text-xs border-b border-yellow-200"
                                            >
                                                <td class="py-2.5 px-4 text-center border-r border-yellow-200 text-gray-500 font-mono">{{ idx + 1 }}</td>
                                                <td class="py-2.5 px-4 text-center border-r border-yellow-200 font-mono font-bold">{{ q.code || `P${q.id}` }}</td>
                                                <td colspan="5" class="py-2.5 px-4 uppercase tracking-wide">
                                                    {{ q.question_text }}
                                                </td>
                                            </tr>

                                            <tr 
                                                v-else
                                                class="border-b border-gray-200 text-xs transition-colors"
                                                :class="q.is_answered ? 'bg-white hover:bg-gray-50' : 'bg-[#FFF1F2] hover:bg-rose-100/60'"
                                            >
                                                <td class="py-3 px-4 text-center border-r border-gray-200 text-gray-500 font-mono">{{ idx + 1 }}</td>
                                                <td class="py-3 px-4 text-center border-r border-gray-200 font-mono font-bold text-[#0D542B]">{{ q.code || `P${q.id}` }}</td>
                                                <td class="py-3 px-4 border-r border-gray-200 font-medium text-gray-900 leading-relaxed">{{ q.question_text }}</td>
                                                <td class="py-3 px-4 text-center border-r border-gray-200 text-gray-500 font-mono text-[11px]">{{ q.type }}</td>
                                                <td class="py-3 px-4 text-center border-r border-gray-200">
                                                    <span 
                                                        class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase"
                                                        :class="q.is_mandatory ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'"
                                                    >
                                                        {{ q.is_mandatory ? 'Wajib' : 'Opsional' }}
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4 text-center border-r border-gray-200">
                                                    <span 
                                                        v-if="q.is_answered" 
                                                        class="font-black text-sm text-[#0D542B]"
                                                        title="Terjawab"
                                                    >
                                                        v
                                                    </span>
                                                    <span 
                                                        v-else 
                                                        class="font-black text-sm text-red-600"
                                                        title="Belum Dijawab"
                                                    >
                                                        x
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4 font-semibold" :class="q.is_answered ? 'text-gray-900' : 'text-rose-500 italic'">
                                                    {{ q.is_answered ? q.answer_text : '— Belum diisi —' }}
                                                </td>
                                            </tr>
                                        </template>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ============================================================= -->
                <!-- HALAMAN 4: HASIL KUESIONER EVALUASI ATASAN                    -->
                <!-- ============================================================= -->
                <div v-if="activeMainTab === 'evaluasi_atasan'" class="space-y-6">
                    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-xs border border-gray-200">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5 pb-6 border-b border-gray-100">
                            <div class="flex items-start gap-4">
                                <div class="w-14 h-14 rounded-2xl bg-[#0D542B]/10 text-[#0D542B] flex items-center justify-center shrink-0">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h2 class="text-xl font-black text-gray-900">Hasil Kuesioner Evaluasi Pengguna Lulusan (Atasan)</h2>
                                        <span 
                                            v-if="evaluasiAtasan?.is_submitted" 
                                            class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Sudah Diisi ({{ evaluasiAtasan.submitted_at }})
                                        </span>
                                        <span 
                                            v-else-if="evaluasiAtasan" 
                                            class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200 flex items-center gap-1"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            Menunggu Respon Atasan
                                        </span>
                                        <span v-else class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-600">
                                            Belum Ada Data Evaluasi
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                                        Penilaian langsung dari atasan/pimpinan tempat alumni bekerja mengenai performa kerja, etika, dan kompetensi lulusan.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
                                <button 
                                    v-if="evaluasiAtasan?.survey_url"
                                    type="button" 
                                    @click="copySurveyUrl" 
                                    class="px-4 py-2.5 bg-[#0D542B] hover:bg-[#093c1f] text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-2xs cursor-pointer"
                                    title="Salin Tautan Kuesioner Atasan"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                    <span>Salin Link Kuesioner</span>
                                </button>
                            </div>
                        </div>

                        <!-- Info Kontak Atasan & Perusahaan -->
                        <div v-if="evaluasiAtasan" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                            <!-- Card Profil Atasan -->
                            <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/70 space-y-2.5">
                                <div class="flex items-center gap-2 pb-2 border-b border-slate-200 text-xs font-bold text-slate-800">
                                    <svg class="w-4 h-4 text-[#0D542B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7 7z"/></svg>
                                    <span>Data Atasan Langsung</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <span class="text-gray-400 block text-[10px] uppercase font-bold">Nama Atasan</span>
                                        <span class="font-bold text-gray-900">{{ evaluasiAtasan.atasan?.nama || '-' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 block text-[10px] uppercase font-bold">Jabatan</span>
                                        <span class="font-semibold text-gray-800">{{ evaluasiAtasan.atasan?.jabatan || '-' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 block text-[10px] uppercase font-bold">Email</span>
                                        <span class="font-medium text-gray-700 truncate block">{{ evaluasiAtasan.atasan?.email || '-' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 block text-[10px] uppercase font-bold">No. HP / WA</span>
                                        <span class="font-medium text-gray-700">{{ evaluasiAtasan.atasan?.no_hp || '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Data Perusahaan & Token -->
                            <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/70 space-y-2.5">
                                <div class="flex items-center gap-2 pb-2 border-b border-slate-200 text-xs font-bold text-slate-800">
                                    <svg class="w-4 h-4 text-[#0D542B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    <span>Instansi / Perusahaan & Token</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <span class="text-gray-400 block text-[10px] uppercase font-bold">Perusahaan</span>
                                        <span class="font-bold text-gray-900">{{ evaluasiAtasan.perusahaan?.nama || '-' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 block text-[10px] uppercase font-bold">Kota</span>
                                        <span class="font-semibold text-gray-800">{{ evaluasiAtasan.perusahaan?.kota || '-' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 block text-[10px] uppercase font-bold">Token Akses</span>
                                        <code class="font-mono bg-white px-2 py-0.5 rounded border border-gray-200 text-[#0D542B] font-bold text-[11px] inline-block">{{ evaluasiAtasan.token || '-' }}</code>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 block text-[10px] uppercase font-bold">Status Verifikasi</span>
                                        <span class="font-semibold text-emerald-700 capitalize">{{ evaluasiAtasan.status || 'Aktif' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State jika belum ada atasan terdaftar -->
                        <div v-else class="py-8 text-center bg-gray-50/50 rounded-xl border border-dashed border-gray-200 mt-6">
                            <p class="text-xs text-gray-500 font-medium">
                                Alumni ini belum mengisi informasi kontak atasan tempat bekerja atau belum ada penugasan evaluasi atasan.
                            </p>
                        </div>
                    </div>

                    <!-- ========================================================= -->
                    <!-- TABEL HASIL PENILAIAN ASPEK KOMPETENSI                    -->
                    <!-- ========================================================= -->
                    <div class="bg-white rounded-2xl shadow-xs border border-gray-200 overflow-hidden">
                        <div class="p-4 sm:p-5 bg-gray-50/80 border-b border-gray-200 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-extrabold text-gray-800 uppercase tracking-wider">Hasil Penilaian Aspek Kinerja & Kemampuan Alumni</span>
                                <span class="px-2.5 py-0.5 bg-[#0D542B] text-white text-[10px] font-bold rounded-full">
                                    {{ evaluasiAtasanQuestionsWithAnswers.length }} Butir Penilaian
                                </span>
                            </div>
                            <span v-if="evaluasiAtasan?.is_submitted" class="text-xs font-semibold text-emerald-700 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Respon Terverifikasi
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-slate-100 text-slate-700 border-b border-gray-200">
                                        <th class="py-3 px-3 text-center font-bold w-12 border-r border-gray-200">#</th>
                                        <th class="py-3 px-4 font-bold border-r border-gray-200 w-28">Kode Butir</th>
                                        <th class="py-3 px-4 font-bold border-r border-gray-200 min-w-[280px]">Aspek Penilaian & Deskripsi</th>
                                        <th class="py-3 px-4 font-bold text-center border-r border-gray-200 w-36">Skor / Nilai</th>
                                        <th class="py-3 px-4 font-bold min-w-[200px]">Catatan / Evaluasi Atasan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr 
                                        v-for="(item, idx) in evaluasiAtasanQuestionsWithAnswers" 
                                        :key="item.id || idx"
                                        class="hover:bg-slate-50/80 transition-colors"
                                        :class="idx % 2 === 0 ? 'bg-white' : 'bg-slate-50/30'"
                                    >
                                        <td class="py-3 px-3 text-center font-mono font-bold text-gray-400 border-r border-gray-100">
                                            {{ idx + 1 }}
                                        </td>
                                        <td class="py-3 px-4 font-mono font-bold text-gray-800 border-r border-gray-100">
                                            <span class="bg-slate-100 px-2 py-0.5 rounded text-[11px] text-slate-700 border border-slate-200">
                                                {{ item.kode_pertanyaan }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 border-r border-gray-100">
                                            <div class="font-bold text-gray-900">{{ item.aspek_penilaian }}</div>
                                            <p v-if="item.keterangan" class="text-[11px] text-gray-500 mt-0.5">{{ item.keterangan }}</p>
                                        </td>
                                        <td class="py-3 px-4 text-center border-r border-gray-100">
                                            <div v-if="item.skor !== null && item.skor !== undefined" class="flex flex-col items-center gap-1">
                                                <span 
                                                    class="px-2.5 py-1 rounded-full text-[11px] font-black border"
                                                    :class="getSkorBadgeClass(item.skor)"
                                                >
                                                    Skor: {{ item.skor }} - {{ getSkorLabel(item.skor) }}
                                                </span>
                                            </div>
                                            <span v-else class="text-gray-400 italic font-medium">Belum Diisi</span>
                                        </td>
                                        <td class="py-3 px-4 text-gray-800">
                                            <span v-if="item.catatan" class="font-medium text-xs">{{ item.catatan }}</span>
                                            <span v-else class="text-gray-400 italic text-[11px]">-</span>
                                        </td>
                                    </tr>

                                    <tr v-if="evaluasiAtasanQuestionsWithAnswers.length === 0">
                                        <td colspan="5" class="py-12 text-center text-gray-400 font-medium">
                                            Belum ada butir pertanyaan kuesioner evaluasi atasan yang dikonfigurasi.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Saran & Rekomendasi Terbuka dari Atasan -->
                    <div v-if="evaluasiAtasan?.saran" class="bg-white rounded-2xl p-6 shadow-xs border border-gray-200">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-5 h-5 text-[#0D542B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                            <h3 class="text-sm font-extrabold text-gray-900">Saran & Rekomendasi Pengembangan Kurikulum dari Pihak Pengguna Lulusan</h3>
                        </div>
                        <div class="p-4 bg-emerald-50/50 rounded-xl border border-emerald-100 text-xs sm:text-sm text-gray-800 leading-relaxed italic whitespace-pre-line">
                            &ldquo;{{ evaluasiAtasan.saran }}&rdquo;
                        </div>
                    </div>
                </div>

                <!-- ============================================================= -->
                <!-- HALAMAN 5: HASIL SCRAPING LINKEDIN (HASILNYA AJA)             -->
                <!-- ============================================================= -->
                <div v-if="activeMainTab === 'linkedin'" class="space-y-6">
                    <!-- Card Ringkasan Hasil LinkedIn -->
                    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-xs border border-gray-200">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5 pb-6 border-b border-gray-100">
                            <div class="flex items-start gap-4">
                                <div class="w-14 h-14 rounded-2xl bg-[#0077B5]/10 text-[#0077B5] flex items-center justify-center shrink-0 shadow-xs">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h2 class="text-xl font-black text-gray-900">Hasil Scraping Profil LinkedIn</h2>
                                        <span 
                                            v-if="linkedinSyncResult" 
                                            class="px-2.5 py-0.5 rounded-full text-xs font-bold capitalize"
                                            :class="linkedinSyncResult.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : (linkedinSyncResult.status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')"
                                        >
                                            Status: {{ linkedinSyncResult.status }}
                                        </span>
                                        <span v-else class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-600">
                                            Belum Ada Data Scraping
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                                        Informasi hasil penelusuran scraping profil profesional LinkedIn alumni untuk verifikasi dan audit rekam jejak karier.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0 flex-wrap">
                                <a 
                                    v-if="linkedinSyncResult?.linkedin_url || alumni.linkedin_url"
                                    :href="linkedinSyncResult?.linkedin_url || alumni.linkedin_url" 
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="px-4 py-2 bg-[#0077B5] hover:bg-[#005f93] text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-2xs"
                                    title="Buka Halaman Profil LinkedIn Asli"
                                >
                                    <svg class="w-3.5 h-3.5 fill-current shrink-0" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                                    <span>Buka Profil LinkedIn &rarr;</span>
                                </a>

                                <button 
                                    v-if="linkedinSyncResult?.scraped_data"
                                    type="button" 
                                    @click="copyRawJson" 
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition flex items-center gap-1.5 cursor-pointer shadow-2xs"
                                >
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <span>Salin JSON Mentah</span>
                                </button>

                                <button 
                                    v-if="linkedinSyncResult?.scraped_data"
                                    type="button" 
                                    @click="showRawJson = !showRawJson" 
                                    class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5 cursor-pointer shadow-2xs"
                                >
                                    <span>{{ showRawJson ? 'Sembunyikan JSON' : 'Lihat JSON Mentah' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Raw JSON Panel (Expandable) -->
                        <div v-if="showRawJson && linkedinSyncResult?.scraped_data" class="mt-4 p-4 bg-slate-900 text-emerald-400 font-mono text-xs rounded-xl overflow-x-auto max-h-96 border border-slate-700 shadow-inner">
                            <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-800 text-slate-400">
                                <span class="font-bold">Raw Payload JSON Lengkap (Apify LinkedIn Scraper):</span>
                                <span>{{ JSON.stringify(linkedinSyncResult.scraped_data).length }} Karakter</span>
                            </div>
                            <pre class="leading-relaxed whitespace-pre-wrap">{{ JSON.stringify(linkedinSyncResult.scraped_data, null, 2) }}</pre>
                        </div>

                        <!-- Info Metadata Box -->
                        <div v-if="linkedinSyncResult" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6 pt-2">
                            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                                <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block">Target URL LinkedIn</span>
                                <a 
                                    :href="linkedinSyncResult.linkedin_url" 
                                    target="_blank" 
                                    class="text-xs font-bold text-[#0077B5] hover:underline truncate block mt-0.5"
                                    title="Buka Profil LinkedIn Asli"
                                >
                                    {{ linkedinSyncResult.linkedin_url }}
                                </a>
                            </div>

                            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                                <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block">Username / Handle</span>
                                <span class="text-xs font-mono font-bold text-gray-800 block mt-0.5">
                                    {{ linkedinSyncResult.linkedin_username || '-' }}
                                </span>
                            </div>

                            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                                <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block">Waktu Pengambilan (Scraped)</span>
                                <span class="text-xs font-semibold text-gray-800 block mt-0.5">
                                    {{ linkedinSyncResult.scraped_at ? new Date(linkedinSyncResult.scraped_at).toLocaleString('id-ID') : '-' }}
                                </span>
                            </div>

                            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                                <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block">Status Review</span>
                                <span class="text-xs font-semibold text-gray-800 block mt-0.5">
                                    {{ linkedinSyncResult.reviewer?.name || (linkedinSyncResult.status === 'approved' ? 'Terverifikasi' : 'Menunggu Review') }}
                                </span>
                            </div>
                        </div>

                        <!-- Empty State jika belum ada hasil scraping -->
                        <div v-else class="py-10 text-center bg-gray-50/50 rounded-xl border border-dashed border-gray-200 mt-6">
                            <div class="w-12 h-12 mx-auto rounded-full bg-sky-50 text-[#0077B5] flex items-center justify-center mb-2">
                                <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                            </div>
                            <h4 class="text-sm font-bold text-gray-800">Belum Ada Hasil Scraping LinkedIn</h4>
                            <p class="text-xs text-gray-500 mt-1 max-w-md mx-auto leading-relaxed">
                                Alumni ini belum memiliki riwayat data scraping profil LinkedIn pada basis data Tracer Study.
                            </p>
                        </div>
                    </div>

                    <!-- ========================================================= -->
                    <!-- TABEL TRACE & PEMETAAN SELURUH DATA SCRAPING              -->
                    <!-- ========================================================= -->
                    <div v-if="linkedinTraceMapping && linkedinTraceMapping.length > 0" class="bg-white rounded-2xl shadow-xs border border-gray-200 overflow-hidden">
                        <!-- Toolbar Filter Pencarian -->
                        <div class="p-4 sm:p-5 bg-gray-50/80 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-extrabold text-gray-700 uppercase tracking-wider">Tabel Penelusuran Pemetaan Hasil Scraping</span>
                                <span class="px-2.5 py-0.5 bg-[#0077B5] text-white text-[10px] font-bold rounded-full">
                                    {{ filteredTraceMapping.length }} / {{ linkedinTraceMapping.length }} Data
                                </span>
                            </div>

                            <div class="relative w-full sm:w-80">
                                <input 
                                    v-model="searchTraceQuery"
                                    type="text" 
                                    placeholder="Cari atribut, nilai scraped, atau status..."
                                    class="w-full text-xs bg-white border border-gray-300 rounded-xl pl-9 pr-4 py-2 text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-[#0077B5] focus:outline-none"
                                />
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-slate-100 text-slate-700 border-b border-gray-200 font-bold">
                                        <th class="py-3 px-3 text-center w-12 border-r border-gray-200">#</th>
                                        <th class="py-3 px-4 w-48 border-r border-gray-200">Atribut LinkedIn</th>
                                        <th class="py-3 px-4 min-w-[200px] border-r border-gray-200">Nilai Hasil Scraping</th>
                                        <th class="py-3 px-4 w-44 border-r border-gray-200">Target Database</th>
                                        <th class="py-3 px-4 min-w-[180px] border-r border-gray-200">Nilai di Database Saat Ini</th>
                                        <th class="py-3 px-4 w-32 text-center">Status Sinkron</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr 
                                        v-for="(row, idx) in filteredTraceMapping" 
                                        :key="row.key || idx"
                                        class="hover:bg-slate-50/80 transition-colors"
                                        :class="idx % 2 === 0 ? 'bg-white' : 'bg-slate-50/30'"
                                    >
                                        <td class="py-3 px-3 text-center font-mono font-bold text-gray-400 border-r border-gray-100">
                                            {{ idx + 1 }}
                                        </td>
                                        <td class="py-3 px-4 border-r border-gray-100 font-semibold text-gray-800">
                                            <div class="font-bold text-gray-900">{{ row.label || row.key }}</div>
                                            <code class="text-[10px] text-gray-400 font-mono">{{ row.key }}</code>
                                        </td>
                                        <td class="py-3 px-4 border-r border-gray-100 text-gray-900 font-medium">
                                            <div v-if="row.key === 'li_profile_image_url' && row.scraped_value" class="flex items-center gap-2">
                                                <img :src="row.scraped_value" alt="Foto Scraped" class="w-8 h-8 rounded-full object-cover border border-gray-200 shrink-0" />
                                                <span class="truncate text-[11px] text-gray-500 font-mono max-w-[200px]">{{ row.scraped_value }}</span>
                                            </div>
                                            <span v-else>{{ row.scraped_value || '-' }}</span>
                                        </td>
                                        <td class="py-3 px-4 border-r border-gray-100 font-mono text-[11px] text-gray-600">
                                            {{ row.target_table }}.{{ row.target_column }}
                                        </td>
                                        <td class="py-3 px-4 border-r border-gray-100 text-gray-700">
                                            <div v-if="row.key === 'li_profile_image_url' && row.db_value" class="flex items-center gap-2">
                                                <img :src="row.db_value" alt="Foto DB" class="w-8 h-8 rounded-full object-cover border border-gray-200 shrink-0" />
                                                <span class="truncate text-[11px] text-gray-500 font-mono max-w-[200px]">{{ row.db_value }}</span>
                                            </div>
                                            <span v-else>{{ row.db_value || '-' }}</span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span 
                                                class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border"
                                                :class="getSyncBadgeClass(row.status)"
                                            >
                                                {{ getSyncBadgeLabel(row.status) }}
                                            </span>
                                        </td>
                                    </tr>

                                    <tr v-if="filteredTraceMapping.length === 0">
                                        <td colspan="6" class="py-12 text-center text-gray-400 font-medium">
                                            Tidak ditemukan data yang sesuai dengan pencarian &ldquo;{{ searchTraceQuery }}&rdquo;.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
        </div>

        <!-- ================================================================= -->
        <!-- MODAL HUBUNGI VIA WHATSAPP (LENGKAP DENGAN LINK & CARA LOGIN)     -->
        <!-- ================================================================= -->
        <div v-if="showWhatsAppModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-200 relative animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                        </span>
                        <div>
                            <h3 class="text-sm font-extrabold text-gray-900">Hubungi Alumni via WhatsApp</h3>
                            <p class="text-[11px] text-gray-500">{{ alumni.data_akademik?.nama || alumni.nama }} ({{ alumni.nim }})</p>
                        </div>
                    </div>
                    <button @click="showWhatsAppModal = false" class="text-gray-400 hover:text-gray-600 p-1 text-lg leading-none cursor-pointer">
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
                        @click="showWhatsAppModal = false"
                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        @click="kirimPesanWhatsApp"
                        class="px-5 py-2 text-xs font-extrabold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                        <span>Buka WhatsApp Web / App</span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    display: none;
}
.custom-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
