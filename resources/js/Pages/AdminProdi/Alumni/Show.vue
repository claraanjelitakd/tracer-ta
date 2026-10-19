<!--
  Halaman Detail Mahasiswa & Audit Kuesioner Tracer Study (Admin Program Studi)
  File: resources/js/Pages/prodi/Alumni/Show.vue
  
  Format Tampilan:
  1. Detail Profil (Urutan 1)
  2. Kuesioner Universitas (Urutan 2, Tampilan Data Tables ala Excel)
  3. Kuesioner Program Studi: [Nama Prodi] (Urutan 3, Tampilan Data Tables ala Excel)
  
  Aturan Tampilan Data Table Excel:
  - Header Section / Question Header: Background Kuning (#FDC700), tanpa kolom jawaban.
  - Butir Belum Dijawab: Background Merah lembut (#FFF1F2 / rose-50) & status merah tegas.
  - Butir Terjawab: Background Putih/Hijau (#FFFFFF / emerald-50) & status hijau terisi.
-->
<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Swal from 'sweetalert2';
import Sidebar from '../Components/Sidebar.vue';

// Mengimpor 4 Komponen Profil Alumni
import FormPribadi from '../../Alumni/Profil/Components/FormPribadi.vue';
import FormAkademik from '../../Alumni/Profil/Components/FormAkademik.vue';
import FormOrangTua from '../../Alumni/Profil/Components/FormOrangTua.vue';
import FormKarier from '../../Alumni/Profil/Components/FormKarier.vue';

const props = defineProps({
    user: Object,
    prodi: Object,
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
    evaluasiAtasan: {
        type: Object,
        default: null,
    },
    pertanyaanEvaluasiAtasan: {
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

// Sub-Tab Profil: 'pribadi', 'akademik', 'orangtua', 'karier'
const activeProfileTab = ref('pribadi');

// State & Helper Evaluasi Atasan
const copySurveyUrl = () => {
    if (!props.evaluasiAtasan?.survey_url) return;
    navigator.clipboard.writeText(props.evaluasiAtasan.survey_url)
        .then(() => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Tautan survei atasan berhasil disalin!',
                showConfirmButton: false,
                timer: 2000,
            });
        });
};

const getSkorLabel = (skor) => {
    if (!skor) return '-';
    const num = Number(skor);
    if (num >= 4) return 'Sangat Baik';
    if (num === 3) return 'Baik';
    if (num === 2) return 'Cukup';
    if (num === 1) return 'Kurang';
    return String(skor);
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

// State Pencarian & Tampilan Tabel Trace LinkedIn
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

// =========================================================================
// SINKRONISASI LINKEDIN LANGSUNG (IN-PLACE TANPA PINDAH HALAMAN)
// =========================================================================
const isDirectSyncing = ref(false);
const isApprovingSync = ref(false);

const handleDirectLinkedInSync = async () => {
    let targetUrl = props.alumni.linkedin_url || '';

    // Jika URL LinkedIn belum terisi, sediakan dialog input interaktif
    if (!targetUrl || !targetUrl.trim()) {
        const { value: inputUrl } = await Swal.fire({
            title: 'URL LinkedIn Belum Diisi',
            text: 'Masukkan URL profil publik LinkedIn alumni ini untuk memulai sinkronisasi:',
            input: 'url',
            inputPlaceholder: 'https://www.linkedin.com/in/username-alumni',
            showCancelButton: true,
            confirmButtonText: 'Simpan & Sinkronkan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#0D542B',
            cancelButtonColor: '#94a3b8',
            inputValidator: (val) => {
                if (!val || !val.trim()) {
                    return 'URL LinkedIn tidak boleh kosong!';
                }
                if (!val.includes('linkedin.com/')) {
                    return 'URL harus berupa tautan profil LinkedIn (contoh: https://www.linkedin.com/in/username)';
                }
            },
        });

        if (!inputUrl) return;

        // Simpan tautan URL ke biodata terlebih dahulu
        try {
            await fetch(`/prodi/alumni/${props.alumni.id}/profile`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    linkedin_url: inputUrl,
                }),
            });
            props.alumni.linkedin_url = inputUrl;
            targetUrl = inputUrl;
        } catch (e) {
            // Lanjutkan eksekusi
        }
    }

    isDirectSyncing.value = true;

    // Tampilkan dialog loading interaktif saat menunggu proses scraping Apify
    Swal.fire({
        title: 'Menghubungi LinkedIn...',
        html: `
            <div class="space-y-2 text-center py-2">
                <p class="text-xs text-gray-600">Mengambil data profil publik melalui Apify Engine.</p>
                <p class="text-xs text-amber-700 font-semibold bg-amber-50 py-1.5 px-3 rounded-lg border border-amber-200">
                    Proses scraping membutuhkan waktu 10–30 detik. Mohon tidak menutup halaman ini...
                </p>
            </div>
        `,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });

    try {
        const response = await fetch(`/prodi/linkedin-sync/${props.alumni.id}`, {
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
                text: data.message || 'Data LinkedIn berhasil diambil dan disimpan dalam status Pending Review.',
                confirmButtonColor: '#0D542B',
                confirmButtonText: 'OK, Lanjutkan',
            });
            // Reload komponen data secara in-place tanpa perlu meninggalkan halaman
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
        console.error('LinkedIn Direct Sync Error:', err);
        Swal.fire({
            icon: 'warning',
            title: 'Waktu Tunggu Jaringan / Terputus',
            text: err.name === 'AbortError'
                ? 'Waktu tunggu permintaan ke LinkedIn telah habis.'
                : (err.message || 'Koneksi ke server terputus saat menunggu hasil scraping. Jika data sebenarnya sudah masuk di server, silakan muat ulang halaman.'),
            confirmButtonColor: '#0D542B',
            confirmButtonText: 'Muat Ulang Halaman',
            showCancelButton: true,
            cancelButtonText: 'Tutup',
        }).then((result) => {
            if (result.isConfirmed) {
                router.reload({ preserveScroll: true });
            }
        });
    } finally {
        isDirectSyncing.value = false;
    }
};

// State Form Penyesuaian Data Persetujuan LinkedIn (Pending Review)
const approveForm = ref({
    tipe_pekerjaan: 'pekerja', // 'pekerja' | 'wirausaha'
    posisi_jabatan: '',
    posisi_wiraswasta: '',
    nama_perusahaan: '',
    skills: '',
    sync_foto: true,
});

const isRejectingSync = ref(false);

const initApproveForm = () => {
    if (!props.linkedinSyncResult) return;
    const preview = props.linkedinSyncResult.preview || {};
    approveForm.value.tipe_pekerjaan = 'pekerja';
    approveForm.value.posisi_jabatan = preview.current_job || props.alumni?.posisi_jabatan || '';
    approveForm.value.posisi_wiraswasta = '';
    approveForm.value.nama_perusahaan = preview.current_company || props.alumni?.perusahaan?.nama_perusahaan || '';
    approveForm.value.sync_foto = !!preview.profile_picture;

    let initialSkills = '';
    if (Array.isArray(preview.skills) && preview.skills.length > 0) {
        initialSkills = preview.skills.join(', ');
    } else if (typeof preview.skills === 'string' && preview.skills.trim()) {
        initialSkills = preview.skills.trim();
    } else if (Array.isArray(preview.suggested_skills) && preview.suggested_skills.length > 0) {
        initialSkills = preview.suggested_skills.join(', ');
    } else if (props.alumni?.keahlian || props.alumni?.skills) {
        initialSkills = props.alumni.keahlian || props.alumni.skills;
    }
    approveForm.value.skills = initialSkills;
};

// Sub-tab navigasi LinkedIn: 'penyesuaian', 'riwayat_karier', 'riwayat_sync'
const activeLinkedinTab = ref('penyesuaian');

const applyCareerToForm = (exp) => {
    approveForm.value.posisi_jabatan = exp.position || '';
    approveForm.value.nama_perusahaan = exp.company || '';
    activeLinkedinTab.value = 'penyesuaian';
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Jabatan & Perusahaan disalin ke form',
        showConfirmButton: false,
        timer: 1800,
    });
};

// Pantau perubahan linkedinSyncResult untuk inisialisasi form
watch(() => props.linkedinSyncResult, () => {
    initApproveForm();
}, { immediate: true });

const handleApproveStagingWithForm = async () => {
    if (!props.linkedinSyncResult || props.linkedinSyncResult.status !== 'pending' || isApprovingSync.value) return;

    const isWirausaha = approveForm.value.tipe_pekerjaan === 'wirausaha';
    const targetJabatan = isWirausaha
        ? (approveForm.value.posisi_wiraswasta || approveForm.value.posisi_jabatan || '-')
        : (approveForm.value.posisi_jabatan || '-');
    const targetPerusahaan = approveForm.value.nama_perusahaan || '-';
    const targetSkills = approveForm.value.skills?.trim() || '-';

    const confirm = await Swal.fire({
        title: 'Setujui & Terapkan Data LinkedIn?',
        html: `
            <div class="text-left text-xs text-slate-700 space-y-2.5">
                <p>Data berikut akan langsung diterapkan ke profil data utama alumni di basis data kampus:</p>
                <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 space-y-1.5 text-slate-800">
                    <p><span class="text-slate-500 font-medium">Kategori:</span> <b>${isWirausaha ? 'Wiraswasta / Usaha' : 'Karyawan / Pekerja'}</b></p>
                    <p><span class="text-slate-500 font-medium">${isWirausaha ? 'Posisi Usaha' : 'Posisi / Jabatan'}:</span> <b>${targetJabatan}</b></p>
                    <p><span class="text-slate-500 font-medium">Perusahaan / Usaha:</span> <b>${targetPerusahaan}</b></p>
                    <p><span class="text-slate-500 font-medium">Keahlian (Skills):</span> <b>${targetSkills}</b></p>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#0D542B',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Setujui & Terapkan Data',
        cancelButtonText: 'Batal',
    });

    if (!confirm.isConfirmed) return;

    isApprovingSync.value = true;
    try {
        const response = await fetch(`/prodi/linkedin-sync/results/${props.linkedinSyncResult.id}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                tipe_pekerjaan: approveForm.value.tipe_pekerjaan,
                posisi_jabatan: approveForm.value.posisi_jabatan,
                posisi_wiraswasta: approveForm.value.posisi_wiraswasta,
                nama_perusahaan: approveForm.value.nama_perusahaan,
                skills: approveForm.value.skills,
                sync_foto: approveForm.value.sync_foto,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Berhasil Disetujui!',
                text: data.message || 'Data profil alumni telah berhasil diperbarui dari LinkedIn.',
                confirmButtonColor: '#0D542B',
            });
            router.reload({ preserveScroll: true });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyetujui',
                text: data.message || 'Terjadi kesalahan sistem saat menyetujui.',
                confirmButtonColor: '#0D542B',
            });
        }
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'Kesalahan Sistem',
            text: 'Gagal memproses persetujuan hasil staging LinkedIn.',
            confirmButtonColor: '#0D542B',
        });
    } finally {
        isApprovingSync.value = false;
    }
};

const handleRejectStaging = async () => {
    if (!props.linkedinSyncResult || props.linkedinSyncResult.status !== 'pending' || isRejectingSync.value) return;

    const confirm = await Swal.fire({
        title: 'Tolak Hasil Sinkronisasi?',
        text: 'Data utama alumni tidak akan diubah. Hasil scraping akan tetap tersimpan sebagai histori dengan status Ditolak (Rejected).',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Tolak Hasil Ini',
        cancelButtonText: 'Batal',
    });

    if (!confirm.isConfirmed) return;

    isRejectingSync.value = true;
    try {
        const response = await fetch(`/prodi/linkedin-sync/results/${props.linkedinSyncResult.id}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        const data = await response.json();

        if (response.ok && data.success) {
            await Swal.fire({
                icon: 'info',
                title: 'Hasil Ditolak',
                text: data.message || 'Hasil sinkronisasi LinkedIn berhasil ditolak.',
                confirmButtonColor: '#0D542B',
            });
            router.reload({ preserveScroll: true });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menolak',
                text: data.message || 'Terjadi kesalahan sistem saat menolak data.',
                confirmButtonColor: '#0D542B',
            });
        }
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'Kesalahan Sistem',
            text: 'Gagal menolak hasil sinkronisasi LinkedIn.',
            confirmButtonColor: '#0D542B',
        });
    } finally {
        isRejectingSync.value = false;
    }
};

// Form data reaktif untuk edit profil oleh Admin Program Studi
const form = useForm(JSON.parse(JSON.stringify(props.formData || {})));

// Simpan perubahan profil oleh Admin Program Studi
const simpanProfilAlumni = () => {
    Swal.fire({
        title: 'Konfirmasi Simpan Profil',
        text: 'Apakah Anda yakin ingin memperbarui data profil alumni ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0D542B',
        cancelButtonColor: '#9CA3AF',
        confirmButtonText: 'Ya, Simpan',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            form.post(`/prodi/alumni/${props.alumni.id}/profile`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Disimpan',
                        text: 'Data profil alumni telah berhasil diperbarui oleh Admin Program Studi.',
                        confirmButtonColor: '#0D542B',
                    });
                },
                onError: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan',
                        text: 'Periksa kembali data yang dimasukkan.',
                        confirmButtonColor: '#0D542B',
                    });
                },
            });
        }
    });
};

// State Pencarian Teks di Data Table
const searchQueryUniv = ref('');
const searchQueryProdi = ref('');

// Filter section pada tab kuesioner universitas ('all' atau ID section)
const activeSectionId = ref('all');

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

// Filter section pada tab kuesioner prodi
const activeProdiSectionId = ref('all');

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

// Total pertanyaan wajib yang belum dijawab
const totalUnansweredMandatory = computed(() => {
    return props.sections.reduce((acc, s) => acc + (s.unanswered_mandatory_count || 0), 0);
});

// Helper Semester & Yudisium
const semesterKelulusan = computed(() => {
    return props.alumni?.data_akademik?.tahun_akademik_lulus || props.alumni?.tahun_lulus || '-';
});

const statusYudisium = computed(() => {
    return props.alumni?.data_akademik?.yudisium?.proses_yudisium || 'Lulus';
});
</script>

<template>
    <Head :title="`Detail Mahasiswa - ${alumni.data_akademik?.nama || alumni.nim} - Admin Program Studi`" />

    <div class="min-h-screen bg-[#f8fafc] text-gray-800 font-sans flex overscroll-none">
        <!-- Sidebar Resmi Admin Program Studi -->
        <Sidebar :user="user" :prodi="prodi" />

        <!-- Area Konten Utama -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-72 overscroll-none">
            
            <!-- Header Solid Hijau Resmi UKDW #0D542B -->
            <header class="bg-[#0D542B] text-white py-6 px-4 sm:px-6 lg:px-8 border-b border-[#0A4322]">
                <div class="max-w-[1400px] mx-auto">
                    <div class="flex items-center gap-2 text-xs text-white/80 font-medium mb-3">
                        <Link href="/prodi/dashboard" class="hover:underline">Dashboard</Link>
                        <span>/</span>
                        <Link href="/prodi/alumni" class="hover:underline">Data Alumni</Link>
                        <span>/</span>
                        <span class="text-white font-bold">Detail Mahasiswa</span>
                    </div>

                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                        <div class="flex items-center gap-4">
                            <!-- Avatar Foto Profil Alumni -->
                            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden border-2 border-white/30 shadow-md bg-white/10 shrink-0 flex items-center justify-center">
                                <img 
                                    v-if="formData.foto || alumni.foto" 
                                    :src="formData.foto || alumni.foto" 
                                    :alt="alumni.data_akademik?.nama || alumni.user?.name" 
                                    class="w-full h-full object-cover object-top"
                                />
                                <span v-else class="text-white font-black text-2xl">
                                    {{ (alumni.data_akademik?.nama || alumni.user?.name || 'A').charAt(0) }}
                                </span>
                            </div>

                            <div>
                                <!-- Badges Header Solid UKDW -->
                                <div class="flex flex-wrap items-center gap-2.5 mb-2">
                                    <!-- Badge Yudisium: Kuning UKDW #FDC700 murni -->
                                    <span class="px-3.5 py-1 bg-[#FDC700] text-black font-bold text-xs rounded-full">
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
                                    {{ alumni.data_akademik?.nama || alumni.user?.name || 'Mahasiswa UKDW' }}
                                </h1>
                                
                                <p class="text-white/90 text-xs sm:text-sm mt-1 font-medium">
                                    NIM: <span class="font-mono font-bold text-white">{{ alumni.nim }}</span> &bull; 
                                    Program Studi: <span class="font-semibold text-white">{{ alumni.prodi?.nama_prodi || '-' }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 shrink-0 flex-wrap">
                            <a 
                                :href="`/prodi/alumni/${alumni.id}/export-excel`"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#FDC700] hover:bg-[#e5b400] text-black text-xs font-extrabold rounded-xl transition-all shadow-sm cursor-pointer"
                                title="Download seluruh butir pertanyaan & jawaban mahasiswa ini ke file Excel (CSV)"
                            >
                                <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                <span>Download Excel (CSV)</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content (Stay & Solid Layout) -->
            <main class="max-w-[1400px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6 pb-16">

                <!-- ============================================================= -->
                <!-- TAB NAVIGASI UTAMA (URUTAN 1, 2, 3 SESUAI PERMINTAAN)        -->
                <!-- 1. Detail Profil                                              -->
                <!-- 2. Kuesioner Universitas                                      -->
                <!-- 3. Kuesioner Program Studi: [Nama Prodi]                      -->
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
                                            ? 'bg-[#0D542B] text-white shadow-md scale-105 sm:scale-110 ring-2 sm:ring-4 ring-[#0D542B]/20' : 
                                        (tab.isComplete 
                                            ? 'bg-emerald-700 text-white shadow-xs group-hover:bg-emerald-800 group-hover:scale-105' : 
                                            'bg-slate-100 text-slate-500 group-hover:bg-slate-200 group-hover:text-slate-700')
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
                                        class="absolute -top-1 -right-1 w-4 h-4 bg-[#0D542B] text-white rounded-full flex items-center justify-center text-[9px] font-black shadow-xs ring-2 ring-white"
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
                                            ? 'text-[#0D542B] font-black' : 
                                        (tab.isComplete 
                                            ? 'text-[#0D542B] font-bold group-hover:text-[#0A4322]' : 
                                            'text-slate-400 group-hover:text-slate-600 font-medium')
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
                <!-- HALAMAN 1: DETAIL PROFIL MAHASISWA                            -->
                <!-- ============================================================= -->
                <div v-if="activeMainTab === 'profil'" class="space-y-6">
                    
                    <!-- Sub-navigasi Tab Profil & Tombol Simpan -->
                    <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-xs border border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex flex-wrap gap-2">
                            <button 
                                @click="activeProfileTab = 'pribadi'"
                                class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                                :class="activeProfileTab === 'pribadi' ? 'bg-[#0D542B] text-white shadow-xs' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                            >
                                1. Data Pribadi
                            </button>
                            <button 
                                @click="activeProfileTab = 'akademik'"
                                class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                                :class="activeProfileTab === 'akademik' ? 'bg-[#0D542B] text-white shadow-xs' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                            >
                                2. Data Akademik
                            </button>
                            <button 
                                @click="activeProfileTab = 'orangtua'"
                                class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                                :class="activeProfileTab === 'orangtua' ? 'bg-[#0D542B] text-white shadow-xs' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                            >
                                3. Data Orang Tua
                            </button>
                            <button 
                                @click="activeProfileTab = 'karier'"
                                class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                                :class="activeProfileTab === 'karier' ? 'bg-[#0D542B] text-white shadow-xs' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                            >
                                4. Karier & Perusahaan
                            </button>
                        </div>

                        <!-- Tombol Simpan Profil oleh Admin Program Studi (Hijau Solid UKDW #0D542B) -->
                        <button 
                            @click="simpanProfilAlumni"
                            :disabled="form.processing"
                            class="px-6 py-2.5 bg-[#0D542B] hover:bg-[#08381c] text-white rounded-xl text-xs font-extrabold transition-all flex items-center justify-center cursor-pointer shadow-xs disabled:opacity-50"
                        >
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>Simpan Perubahan Profil</span>
                        </button>
                    </div>

                    <!-- Komponen Form Profil Alumni (Ukuran Konsisten Full-Width Card) -->
                    <div class="space-y-6">
                        <FormPribadi 
                            v-show="activeProfileTab === 'pribadi'"
                            :form="form" 
                            :provinces="provinces" 
                            :kabupatens="kabupatens" 
                        />

                        <FormAkademik 
                            v-show="activeProfileTab === 'akademik'"
                            :form="form" 
                        />

                        <FormOrangTua 
                            v-show="activeProfileTab === 'orangtua'"
                            :form="form" 
                            :provinces="provinces" 
                            :kabupatens="kabupatens" 
                        />

                        <FormKarier 
                            v-show="activeProfileTab === 'karier'"
                            :form="form" 
                            :provinces="provinces" 
                            :kabupatens="kabupatens" 
                            :negaras="negaras"
                            :companies="companies" 
                            :alumniData="alumni" 
                            :refOptions="refOptions"
                        />
                    </div>

                    <!-- Tombol Simpan Bawah -->
                    <div class="flex justify-end pt-2">
                        <button 
                            @click="simpanProfilAlumni"
                            :disabled="form.processing"
                            class="px-7 py-3 bg-[#0D542B] hover:bg-[#08381c] text-white rounded-xl text-xs font-extrabold transition-all cursor-pointer shadow-sm disabled:opacity-50"
                        >
                            <span v-if="form.processing">Menyimpan Perubahan...</span>
                            <span v-else>Simpan Perubahan Profil Alumni</span>
                        </button>
                    </div>
                </div>

                <!-- ============================================================= -->
                <!-- HALAMAN 2: KUESIONER UNIVERSITAS (TABLE BERSIH & DROPDOWN)    -->
                <!-- ============================================================= -->
                <div v-else-if="activeMainTab === 'kuesioner'" class="space-y-6">
                    
                    <!-- Heading & Bar Filter / Pencarian -->
                    <div class="bg-white p-5 rounded-2xl shadow-xs border border-gray-200 space-y-4">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-base sm:text-lg font-black text-gray-900 flex items-center gap-2">
                                    <span class="w-2.5 h-5 bg-[#0D542B] rounded-full inline-block"></span>
                                    Kuesioner Tracer Study Universitas
                                </h2>
                                <p class="text-xs text-gray-500 mt-0.5">Tampilan data tables terstruktur untuk seluruh butir instrumen dan jawaban tracer study.</p>
                            </div>
                        </div>

                        <!-- Dropdown Filter Bagian & Input Pencarian -->
                        <div class="pt-3 border-t border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <!-- Dropdown Section Langsung Nama Section -->
                            <div class="flex items-center gap-2.5 w-full md:w-auto">
                                <label class="text-xs font-bold text-gray-600 whitespace-nowrap">Filter Bagian:</label>
                                <select 
                                    v-model="activeSectionId"
                                    class="w-full md:w-96 px-3.5 py-2 bg-gray-50 hover:bg-white border border-gray-300 rounded-xl text-xs font-semibold text-gray-800 focus:ring-2 focus:ring-[#0D542B]/20 focus:border-[#0D542B] transition-all cursor-pointer"
                                >
                                    <option value="all">Semua Bagian ({{ sections.length }} Bagian)</option>
                                    <option 
                                        v-for="s in sections" 
                                        :key="s.id" 
                                        :value="s.id"
                                    >
                                        {{ s.title || s.section || ('Bagian ' + s.order) }} {{ s.unanswered_mandatory_count > 0 ? `(${s.unanswered_mandatory_count} belum)` : '' }}
                                    </option>
                                </select>
                            </div>

                            <!-- Input Pencarian Cepat -->
                            <div class="w-full md:w-80">
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        v-model="searchQueryUniv"
                                        placeholder="Cari kode, pertanyaan, jawaban..."
                                        class="w-full pl-9 pr-4 py-2 bg-gray-50 hover:bg-white border border-gray-300 rounded-xl text-xs font-medium text-gray-800 focus:ring-2 focus:ring-[#0D542B]/20 focus:border-[#0D542B] focus:bg-white transition-all"
                                    />
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DATA TABLE STANDAR (KUESIONER UNIVERSITAS) -->
                    <div class="bg-white rounded-2xl shadow-xs border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-700 border-b border-gray-200">
                                        <th class="w-12 text-center py-3.5 px-3 font-bold text-xs uppercase tracking-wider border-r border-gray-200">No</th>
                                        <th class="w-24 text-center py-3.5 px-3 font-bold text-xs uppercase tracking-wider border-r border-gray-200">Kode</th>
                                        <th class="py-3.5 px-4 font-bold text-xs uppercase tracking-wider border-r border-gray-200 min-w-[280px]">Pertanyaan / Deskripsi Instrumen</th>
                                        <th class="w-24 text-center py-3.5 px-3 font-bold text-xs uppercase tracking-wider border-r border-gray-200">Tipe</th>
                                        <th class="w-20 text-center py-3.5 px-3 font-bold text-xs uppercase tracking-wider border-r border-gray-200">Sifat</th>
                                        <th class="w-32 text-center py-3.5 px-3 font-bold text-xs uppercase tracking-wider border-r border-gray-200">Status</th>
                                        <th class="w-80 py-3.5 px-4 font-bold text-xs uppercase tracking-wider min-w-[240px]">Jawaban Alumni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="section in filteredSections" :key="section.id">
                                        <!-- BARIS HEADER SECTION (KUNING LEMBUT, NAMA SECTION LANGSUNG) -->
                                        <tr class="bg-amber-100/75 text-amber-950 border-y border-amber-200 font-bold">
                                            <td colspan="7" class="py-3 px-4 text-xs tracking-wide">
                                                <div class="flex items-center justify-between">
                                                    <span>{{ section.title || section.section || ('Bagian ' + section.order) }}</span>
                                                    <span v-if="section.unanswered_mandatory_count > 0" class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                                        {{ section.unanswered_mandatory_count }} Wajib Belum Dijawab
                                                    </span>
                                                    <span v-else-if="section.unanswered_optional_count > 0" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                                        Wajib Lengkap ({{ section.unanswered_optional_count }} Opsional Kosong)
                                                    </span>
                                                    <span v-else class="px-2 py-0.5 rounded text-[10px] font-medium bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                        Lengkap (100%)
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- BARIS BUTIR SOAL PER SECTION -->
                                        <tr 
                                            v-for="(q, idx) in section.subpertanyaans" 
                                            :key="q.id"
                                            :class="[
                                                q.is_header 
                                                    ? 'bg-amber-50/60 text-amber-950 font-bold border-b border-gray-200'
                                                    : 'bg-white hover:bg-gray-50/80 border-b border-gray-100'
                                            ]"
                                            class="transition-colors text-xs text-gray-800"
                                        >
                                            <!-- Kolom No -->
                                            <td class="text-center py-2.5 px-3 text-gray-500 font-medium border-r border-gray-100">
                                                {{ idx + 1 }}
                                            </td>

                                            <!-- Kolom Kode -->
                                            <td class="text-center py-2.5 px-3 font-mono font-semibold text-gray-700 border-r border-gray-100">
                                                {{ q.kode_pertanyaan || '-' }}
                                            </td>

                                            <!-- Kolom Pertanyaan (Jika header, span ke kanan) -->
                                            <td v-if="q.is_header" colspan="5" class="py-2.5 px-4 font-bold text-amber-950 text-xs">
                                                {{ q.subpertanyaan }}
                                            </td>

                                            <!-- Kolom Pertanyaan Biasa -->
                                            <td v-else class="py-2.5 px-4 font-medium text-gray-900 border-r border-gray-100 leading-relaxed">
                                                {{ q.subpertanyaan }}
                                            </td>

                                            <!-- Kolom Tipe -->
                                            <td v-if="!q.is_header" class="text-center py-2.5 px-3 capitalize font-normal text-gray-500 border-r border-gray-100 text-[11px]">
                                                {{ q.type || 'text' }}
                                            </td>

                                            <!-- Kolom Sifat -->
                                            <td v-if="!q.is_header" class="text-center py-2.5 px-3 border-r border-gray-100">
                                                <span v-if="q.is_mandatory" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                                    Wajib
                                                </span>
                                                <span v-else class="px-2 py-0.5 rounded text-[10px] font-normal bg-gray-50 text-gray-500">
                                                    Opsional
                                                </span>
                                            </td>

                                            <!-- Kolom Status (v / x) -->
                                            <td v-if="!q.is_header" class="text-center py-2.5 px-3 border-r border-gray-100">
                                                <span v-if="q.is_answered" class="font-black text-sm text-[#0D542B]" title="Terjawab">
                                                    v
                                                </span>
                                                <span v-else class="font-black text-sm text-red-600" title="Belum Dijawab">
                                                    x
                                                </span>
                                            </td>

                                            <!-- Kolom Jawaban Alumni -->
                                            <td v-if="!q.is_header" class="py-2.5 px-4 text-gray-900 leading-relaxed">
                                                <span v-if="q.is_answered" class="text-xs font-semibold text-gray-900 whitespace-pre-line">
                                                    {{ q.answer }}
                                                </span>
                                                <span v-else class="text-xs text-rose-500 font-medium italic">
                                                    -
                                                </span>
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- State Kosong / Pencarian Nihil -->
                                    <tr v-if="filteredSections.length === 0">
                                        <td colspan="7" class="py-12 text-center text-gray-400 font-medium">
                                            Tidak ada butir pertanyaan yang sesuai dengan filter atau pencarian Anda.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ============================================================= -->
                <!-- HALAMAN 3: KUESIONER PROGRAM STUDI: [NAMA PRODI]              -->
                <!-- ============================================================= -->
                <div v-else-if="activeMainTab === 'kuesioner_prodi'" class="space-y-6">
                    
                    <!-- Heading & Bar Filter / Pencarian -->
                    <div class="bg-white p-5 rounded-2xl shadow-xs border border-gray-200 space-y-4">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-base sm:text-lg font-black text-gray-900 flex items-center gap-2">
                                    <span class="w-2.5 h-5 bg-[#0D542B] rounded-full inline-block"></span>
                                    Kuesioner Program Studi: {{ alumni.prodi?.nama_prodi || 'Program Studi' }}
                                </h2>
                                <p class="text-xs text-gray-500 mt-0.5">Tampilan data tables terstruktur untuk butir evaluasi kurikulum dan kepuasan khusus program studi.</p>
                            </div>
                        </div>

                        <!-- Dropdown Filter Bagian Prodi & Input Pencarian -->
                        <div class="pt-3 border-t border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <!-- Dropdown Section Langsung Nama Section -->
                            <div class="flex items-center gap-2.5 w-full md:w-auto">
                                <label class="text-xs font-bold text-gray-600 whitespace-nowrap">Filter Bagian:</label>
                                <select 
                                    v-model="activeProdiSectionId"
                                    class="w-full md:w-96 px-3.5 py-2 bg-gray-50 hover:bg-white border border-gray-300 rounded-xl text-xs font-semibold text-gray-800 focus:ring-2 focus:ring-[#0D542B]/20 focus:border-[#0D542B] transition-all cursor-pointer"
                                >
                                    <option value="all">Semua Bagian ({{ prodiSections.length }} Bagian)</option>
                                    <option 
                                        v-for="s in prodiSections" 
                                        :key="s.id" 
                                        :value="s.id"
                                    >
                                        {{ s.title }} {{ s.unanswered_mandatory_count > 0 ? `(${s.unanswered_mandatory_count} belum)` : '' }}
                                    </option>
                                </select>
                            </div>

                            <!-- Input Pencarian Cepat -->
                            <div class="w-full md:w-80">
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        v-model="searchQueryProdi"
                                        placeholder="Cari kode, instrumen, jawaban..."
                                        class="w-full pl-9 pr-4 py-2 bg-gray-50 hover:bg-white border border-gray-300 rounded-xl text-xs font-medium text-gray-800 focus:ring-2 focus:ring-[#0D542B]/20 focus:border-[#0D542B] focus:bg-white transition-all"
                                    />
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DATA TABLE STANDAR (KUESIONER PROGRAM STUDI) -->
                    <div class="bg-white rounded-2xl shadow-xs border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-700 border-b border-gray-200">
                                        <th class="w-12 text-center py-3.5 px-3 font-bold text-xs uppercase tracking-wider border-r border-gray-200">No</th>
                                        <th class="w-24 text-center py-3.5 px-3 font-bold text-xs uppercase tracking-wider border-r border-gray-200">Kode</th>
                                        <th class="py-3.5 px-4 font-bold text-xs uppercase tracking-wider border-r border-gray-200 min-w-[280px]">Pertanyaan / Deskripsi Instrumen</th>
                                        <th class="w-24 text-center py-3.5 px-3 font-bold text-xs uppercase tracking-wider border-r border-gray-200">Tipe</th>
                                        <th class="w-20 text-center py-3.5 px-3 font-bold text-xs uppercase tracking-wider border-r border-gray-200">Sifat</th>
                                        <th class="w-32 text-center py-3.5 px-3 font-bold text-xs uppercase tracking-wider border-r border-gray-200">Status</th>
                                        <th class="w-80 py-3.5 px-4 font-bold text-xs uppercase tracking-wider min-w-[240px]">Jawaban Alumni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="section in filteredProdiSections" :key="section.id">
                                        <!-- BARIS HEADER SECTION PRODI (KUNING LEMBUT, NAMA SECTION LANGSUNG) -->
                                        <tr class="bg-amber-100/75 text-amber-950 border-y border-amber-200 font-bold">
                                            <td colspan="7" class="py-3 px-4 text-xs tracking-wide">
                                                <span>{{ section.title }}</span>
                                                <span v-if="section.description" class="font-normal text-gray-700 ml-1">
                                                    &mdash; {{ section.description }}
                                                </span>
                                            </td>
                                        </tr>

                                        <!-- BARIS BUTIR SOAL PRODI -->
                                        <tr 
                                            v-for="(q, idx) in section.questions" 
                                            :key="q.id"
                                            :class="[
                                                q.is_header 
                                                    ? 'bg-amber-50/60 text-amber-950 font-bold border-b border-gray-200'
                                                    : 'bg-white hover:bg-gray-50/80 border-b border-gray-100'
                                            ]"
                                            class="transition-colors text-xs text-gray-800"
                                        >
                                            <!-- Kolom No -->
                                            <td class="text-center py-2.5 px-3 text-gray-500 font-medium border-r border-gray-100">
                                                {{ idx + 1 }}
                                            </td>

                                            <!-- Kolom Kode -->
                                            <td class="text-center py-2.5 px-3 font-mono font-semibold text-gray-700 border-r border-gray-100">
                                                {{ q.code || '-' }}
                                            </td>

                                            <!-- Kolom Pertanyaan (Jika header, span ke kanan) -->
                                            <td v-if="q.is_header" colspan="5" class="py-2.5 px-4 font-bold text-amber-950 text-xs">
                                                {{ q.question_text }}
                                            </td>

                                            <!-- Kolom Pertanyaan Biasa -->
                                            <td v-else class="py-2.5 px-4 font-medium text-gray-900 border-r border-gray-100 leading-relaxed">
                                                {{ q.question_text }}
                                            </td>

                                            <!-- Kolom Tipe -->
                                            <td v-if="!q.is_header" class="text-center py-2.5 px-3 capitalize font-normal text-gray-500 border-r border-gray-100 text-[11px]">
                                                {{ q.type || 'text' }}
                                            </td>

                                            <!-- Kolom Sifat -->
                                            <td v-if="!q.is_header" class="text-center py-2.5 px-3 border-r border-gray-100">
                                                <span v-if="q.is_mandatory" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                                    Wajib
                                                </span>
                                                <span v-else class="px-2 py-0.5 rounded text-[10px] font-normal bg-gray-50 text-gray-500">
                                                    Opsional
                                                </span>
                                            </td>

                                            <!-- Kolom Status (v / x) -->
                                            <td v-if="!q.is_header" class="text-center py-2.5 px-3 border-r border-gray-100">
                                                <span v-if="q.is_answered" class="font-black text-sm text-[#0D542B]" title="Terjawab">
                                                    v
                                                </span>
                                                <span v-else class="font-black text-sm text-red-600" title="Belum Dijawab">
                                                    x
                                                </span>
                                            </td>

                                            <!-- Kolom Jawaban Alumni -->
                                            <td v-if="!q.is_header" class="py-2.5 px-4 text-gray-900 leading-relaxed">
                                                <span v-if="q.is_answered" class="text-xs font-semibold text-gray-900 whitespace-pre-line">
                                                    {{ q.answer_text }}
                                                </span>
                                                <span v-else class="text-xs text-rose-500 font-medium italic">
                                                    -
                                                </span>
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- State Kosong -->
                                    <tr v-if="filteredProdiSections.length === 0">
                                        <td colspan="7" class="py-12 text-center text-gray-400 font-medium">
                                            Tidak ada butir kuesioner prodi untuk program studi mahasiswa ini atau tidak sesuai filter pencarian.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ============================================================= -->
                <!-- HALAMAN 4: HASIL EVALUASI ATASAN (PENGGUNA LULUSAN)          -->
                <!-- ============================================================= -->
                <div v-if="activeMainTab === 'evaluasi_atasan'" class="space-y-6">
                    <!-- Card Ringkasan Evaluasi Atasan & Info Pengisian -->
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
                                        <h2 class="text-xl font-black text-gray-900">Hasil Evaluasi Pengguna Lulusan (Atasan)</h2>
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
                <!-- HALAMAN 5: HASIL SCRAPING & AUDIT TRACE LINKEDIN              -->
                <!-- ============================================================= -->
                <div v-if="activeMainTab === 'linkedin'" class="space-y-6">
                    <!-- Card Ringkasan LinkedIn & Status Staging -->
                    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-xs border border-gray-200">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5 pb-6 border-b border-gray-100">
                            <div class="flex items-start gap-4">
                                <Link 
                                    :href="'/prodi/linkedin-sync?alumni_id=' + alumni.id + '&search=' + encodeURIComponent(alumni.nim || alumni.nama || '')" 
                                    class="w-14 h-14 rounded-2xl bg-[#0077B5]/10 text-[#0077B5] hover:bg-[#0077B5]/20 hover:scale-105 transition-all flex items-center justify-center shrink-0 cursor-pointer shadow-xs group"
                                    title="Arahkan langsung ke profil LinkedIn alumni ini di LinkedIn Sync"
                                >
                                    <svg class="w-8 h-8 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                                </Link>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h2 class="text-xl font-black text-gray-900">Audit & Trace Hasil Scraping LinkedIn</h2>
                                        <span 
                                            v-if="linkedinSyncResult" 
                                            class="px-2.5 py-0.5 rounded-full text-xs font-bold capitalize"
                                            :class="linkedinSyncResult.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : (linkedinSyncResult.status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')"
                                        >
                                            Status Staging: {{ linkedinSyncResult.status }}
                                        </span>
                                        <span v-else class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-600">
                                            Belum Ada Data Sinkronisasi
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                                        Seluruh data hasil scraping LinkedIn (Apify Engine) dipetakan secara terstruktur ke tabel basis data Tracer Study (<code class="font-mono bg-slate-100 px-1 py-0.5 rounded text-gray-800">biodata</code>, <code class="font-mono bg-slate-100 px-1 py-0.5 rounded text-gray-800">perusahaan</code>, <code class="font-mono bg-slate-100 px-1 py-0.5 rounded text-gray-800">data_akademik</code>, dan <code class="font-mono bg-slate-100 px-1 py-0.5 rounded text-gray-800">linkedin_sync_results</code>).
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0 flex-wrap">
                                <!-- Tombol Sinkronisasi Langsung In-Place (Tanpa Pindah Halaman) -->
                                <button 
                                    type="button" 
                                    @click="handleDirectLinkedInSync"
                                    :disabled="isDirectSyncing"
                                    class="px-4 py-2 bg-[#0077B5] hover:bg-[#005f93] text-white text-xs font-bold rounded-xl transition flex items-center gap-2 shadow-2xs disabled:opacity-60 cursor-pointer"
                                    title="Sinkronkan data LinkedIn alumni ini secara langsung tanpa perlu meninggalkan halaman ini"
                                >
                                    <svg v-if="!isDirectSyncing" class="w-3.5 h-3.5 fill-current shrink-0" viewBox="0 0 24 24">
                                        <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                                    </svg>
                                    <svg v-else class="animate-spin w-3.5 h-3.5 text-white shrink-0" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>{{ isDirectSyncing ? 'Menyinkronkan...' : 'Sinkronkan LinkedIn Sekarang' }}</span>
                                </button>

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

                                <Link 
                                    :href="'/prodi/linkedin-sync?alumni_id=' + alumni.id + '&search=' + encodeURIComponent(alumni.nim || alumni.nama || '')" 
                                    class="px-4 py-2 bg-[#0D542B] hover:bg-[#0A4322] text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-2xs"
                                    title="Buka direktori lengkap LinkedIn Sync di halaman tersendiri"
                                >
                                    <span>Buka Menu LinkedIn Sync &rarr;</span>
                                </Link>
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
                                <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block">Status Reviewer</span>
                                <span class="text-xs font-semibold text-gray-800 block mt-0.5">
                                    {{ linkedinSyncResult.reviewer?.name || (linkedinSyncResult.status === 'approved' ? 'Admin Program Studi' : 'Menunggu Review') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================================= -->
                    <!-- KARTU TERPADU: PENINJAUAN, RIWAYAT KARIER & LOG LINKEDIN   -->
                    <!-- ========================================================= -->
                    <div v-if="linkedinSyncResult" class="bg-white rounded-2xl shadow-xs border border-slate-200 overflow-hidden">
                        <!-- Header Kartu Formal & Bersih (Slate Theme) -->
                        <div class="p-5 sm:p-6 bg-slate-50 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <h3 class="text-base font-bold text-slate-900">
                                        Hasil Penelusuran LinkedIn Alumni
                                    </h3>
                                    <span 
                                        class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                        :class="linkedinSyncResult.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : (linkedinSyncResult.status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')"
                                    >
                                        {{ linkedinSyncResult.status === 'approved' ? 'Telah Disetujui (Approved)' : (linkedinSyncResult.status === 'pending' ? 'Menunggu Persetujuan (Pending Review)' : 'Ditolak (Rejected)') }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">
                                    Data ditarik dari profil publik LinkedIn oleh Apify Scraper Engine. Periksa kesesuaian data sebelum atau sesudah diterapkan ke database utama.
                                </p>
                            </div>

                            <!-- Sub-Navigasi Tab Internal Rapi -->
                            <div class="inline-flex rounded-xl bg-white p-1 border border-slate-200 shadow-2xs self-start md:self-auto shrink-0 text-xs">
                                <button
                                    type="button"
                                    @click="activeLinkedinTab = 'penyesuaian'"
                                    :class="activeLinkedinTab === 'penyesuaian' ? 'bg-[#0D542B] text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                                    class="px-3.5 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1.5"
                                >
                                    <span>Persetujuan & Usulan</span>
                                </button>
                                <button
                                    type="button"
                                    @click="activeLinkedinTab = 'riwayat_karier'"
                                    :class="activeLinkedinTab === 'riwayat_karier' ? 'bg-[#0D542B] text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                                    class="px-3.5 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1.5"
                                >
                                    <span>Riwayat Karier & Pendidikan</span>
                                    <span 
                                        v-if="(linkedinSyncResult.preview?.experience || []).length" 
                                        class="px-1.5 py-0.2 rounded-full text-[10px]"
                                        :class="activeLinkedinTab === 'riwayat_karier' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'"
                                    >
                                        {{ (linkedinSyncResult.preview?.experience || []).length }}
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    @click="activeLinkedinTab = 'riwayat_sync'"
                                    :class="activeLinkedinTab === 'riwayat_sync' ? 'bg-[#0D542B] text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                                    class="px-3.5 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1.5"
                                >
                                    <span>Riwayat Sinkronisasi (Log)</span>
                                    <span 
                                        v-if="linkedinHistory.length" 
                                        class="px-1.5 py-0.2 rounded-full text-[10px]"
                                        :class="activeLinkedinTab === 'riwayat_sync' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'"
                                    >
                                        {{ linkedinHistory.length }}
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    @click="activeLinkedinTab = 'audit_trace'"
                                    :class="activeLinkedinTab === 'audit_trace' ? 'bg-[#0D542B] text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                                    class="px-3.5 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1.5"
                                >
                                    <span>Pemetaan Database</span>
                                    <span 
                                        v-if="filteredTraceMapping.length" 
                                        class="px-1.5 py-0.2 rounded-full text-[10px]"
                                        :class="activeLinkedinTab === 'audit_trace' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'"
                                    >
                                        {{ filteredTraceMapping.length }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- TAB 1: FORMULIR PENYESUAIAN & PERSETUJUAN  -->
                        <!-- ========================================== -->
                        <div v-show="activeLinkedinTab === 'penyesuaian'" class="p-5 sm:p-6 space-y-6">
                            <!-- Banner Info Bersih (Non-Kuning AI) -->
                            <div v-if="linkedinSyncResult.status === 'pending'" class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 flex items-start gap-3 text-xs text-slate-600">
                                <svg class="w-4 h-4 text-slate-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="leading-relaxed">
                                    Data hasil scraping ini masih dalam tahap peninjauan (pending) dan <strong>belum mengubah data utama alumni</strong>. Silakan sesuaikan data pekerjaan di bawah atau gunakan riwayat karier LinkedIn sebelum menekan tombol persetujuan.
                                </p>
                            </div>
                            <div v-else-if="linkedinSyncResult.status === 'approved'" class="p-3.5 bg-emerald-50/60 rounded-xl border border-emerald-200 flex items-start gap-3 text-xs text-emerald-800">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <p class="leading-relaxed">
                                    Data profil LinkedIn telah <strong>disetujui dan diterapkan</strong> ke profil alumni oleh <b>{{ linkedinSyncResult.reviewer?.name || 'Admin Program Studi' }}</b> pada {{ linkedinSyncResult.reviewed_at ? new Date(linkedinSyncResult.reviewed_at).toLocaleString('id-ID') : '-' }}.
                                </p>
                            </div>

                            <!-- Tabel Perbandingan Komparatif (Sistem Saat Ini vs Usulan LinkedIn) -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Kolom Kiri: Basis Data Kampus Saat Ini -->
                                <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200 space-y-3">
                                    <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Data di Basis Data Kampus</span>
                                        <span class="text-[11px] text-slate-400">Nilai Saat Ini</span>
                                    </div>
                                    <div class="space-y-3 text-xs">
                                        <div>
                                            <span class="text-slate-400 text-[11px] block">Posisi / Jabatan Pekerjaan:</span>
                                            <span class="font-semibold text-slate-800 block mt-0.5">{{ alumni.posisi_jabatan || '- Belum diisi -' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-slate-400 text-[11px] block">Perusahaan / Instansi:</span>
                                            <span class="font-semibold text-slate-800 block mt-0.5">{{ alumni.perusahaan?.nama_perusahaan || '- Belum diisi -' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-slate-400 text-[11px] block">Kategori Karier:</span>
                                            <span class="font-semibold text-slate-800 block mt-0.5">{{ alumni.kategori_pekerjaan || '- Belum diisi -' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-slate-400 text-[11px] block">Keahlian (Skills):</span>
                                            <span class="text-slate-700 block mt-0.5 line-clamp-2" :title="alumni.keahlian || alumni.skills || ''">{{ alumni.keahlian || alumni.skills || '- Belum diisi -' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Kolom Kanan: Usulan Data Baru LinkedIn -->
                                <div class="p-4 bg-white rounded-xl border border-slate-200 space-y-3.5">
                                    <div class="flex items-center justify-between border-b border-slate-200 pb-2 flex-wrap gap-2">
                                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Usulan Data LinkedIn (Bisa Diedit)</span>
                                        <!-- Segmented Button Tipe Pekerjaan -->
                                        <div class="inline-flex rounded-lg border border-slate-200 p-0.5 bg-slate-100 text-[11px]">
                                            <button
                                                type="button"
                                                @click="approveForm.tipe_pekerjaan = 'pekerja'"
                                                :class="approveForm.tipe_pekerjaan === 'pekerja' ? 'bg-[#0D542B] text-white font-bold' : 'text-slate-600'"
                                                class="px-2.5 py-0.5 rounded-md transition cursor-pointer"
                                            >
                                                Karyawan
                                            </button>
                                            <button
                                                type="button"
                                                @click="approveForm.tipe_pekerjaan = 'wirausaha'"
                                                :class="approveForm.tipe_pekerjaan === 'wirausaha' ? 'bg-[#0D542B] text-white font-bold' : 'text-slate-600'"
                                                class="px-2.5 py-0.5 rounded-md transition cursor-pointer"
                                            >
                                                Wirausaha
                                            </button>
                                        </div>
                                    </div>

                                    <div class="space-y-3 text-xs">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                                {{ approveForm.tipe_pekerjaan === 'wirausaha' ? 'Posisi Usaha / Bisnis:' : 'Posisi / Jabatan Pekerjaan:' }}
                                            </label>
                                            <input
                                                v-if="approveForm.tipe_pekerjaan === 'wirausaha'"
                                                type="text"
                                                v-model="approveForm.posisi_wiraswasta"
                                                :placeholder="approveForm.posisi_jabatan || 'contoh: Founder, Direktur'"
                                                class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-300 rounded-lg focus:bg-white focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B]"
                                            />
                                            <input
                                                v-else
                                                type="text"
                                                v-model="approveForm.posisi_jabatan"
                                                placeholder="contoh: Software Engineer"
                                                class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-300 rounded-lg focus:bg-white focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B]"
                                            />
                                        </div>

                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                                {{ approveForm.tipe_pekerjaan === 'wirausaha' ? 'Nama Unit Usaha / Bisnis:' : 'Nama Perusahaan / Instansi:' }}
                                            </label>
                                            <input
                                                type="text"
                                                v-model="approveForm.nama_perusahaan"
                                                placeholder="contoh: PT Bank Mandiri"
                                                class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-300 rounded-lg focus:bg-white focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B]"
                                            />
                                        </div>

                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                                Keahlian / Skills:
                                            </label>
                                            <input
                                                type="text"
                                                v-model="approveForm.skills"
                                                placeholder="contoh: PHP, Laravel, Business Analysis"
                                                class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-300 rounded-lg focus:bg-white focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B]"
                                            />
                                        </div>

                                        <!-- Pilihan Foto Profil LinkedIn -->
                                        <div v-if="linkedinSyncResult.preview?.profile_picture" class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-2.5">
                                                <img :src="linkedinSyncResult.preview.profile_picture" class="w-8 h-8 rounded-lg object-cover border border-slate-300 shrink-0" />
                                                <div>
                                                    <span class="text-xs font-semibold text-slate-800 block">Terapkan Foto LinkedIn</span>
                                                    <span class="text-[10px] text-slate-400">Simpan ke direktori /uploads/profile/</span>
                                                </div>
                                            </div>
                                            <label class="inline-flex items-center gap-1.5 cursor-pointer shrink-0">
                                                <input type="checkbox" v-model="approveForm.sync_foto" class="w-4 h-4 text-[#0D542B] rounded border-slate-300 focus:ring-[#0D542B]" />
                                                <span class="text-xs text-slate-600 font-medium">Gunakan</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tombol Aksi Persetujuan (Hanya muncul jika pending) -->
                            <div v-if="linkedinSyncResult.status === 'pending'" class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                                <span class="text-xs text-slate-500">
                                    Waktu Scrape: <span class="font-mono text-slate-700">{{ linkedinSyncResult.scraped_at ? new Date(linkedinSyncResult.scraped_at).toLocaleString('id-ID') : '-' }}</span>
                                </span>
                                <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                                    <button
                                        type="button"
                                        @click="handleRejectStaging"
                                        :disabled="isRejectingSync || isApprovingSync"
                                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer disabled:opacity-50"
                                    >
                                        <span v-if="isRejectingSync">Menolak...</span>
                                        <span v-else>Tolak (Reject)</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="handleApproveStagingWithForm"
                                        :disabled="isApprovingSync || isRejectingSync"
                                        class="px-5 py-2 bg-[#0D542B] hover:bg-[#0A4322] text-white text-xs font-bold rounded-xl transition shadow-2xs cursor-pointer flex items-center gap-1.5 disabled:opacity-50"
                                    >
                                        <svg v-if="!isApprovingSync" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>{{ isApprovingSync ? 'Menerapkan Data...' : 'Setujui (Approve) & Terapkan Data' }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- ==================================================== -->
                        <!-- TAB 2: RIWAYAT KARIER & PENDIDIKAN LINKEDIN LENGKAP -->
                        <!-- ==================================================== -->
                        <div v-show="activeLinkedinTab === 'riwayat_karier'" class="p-5 sm:p-6 space-y-6">
                            <!-- Bio / Headline / Summary Ringkas -->
                            <div v-if="linkedinSyncResult.preview?.headline || linkedinSyncResult.preview?.about" class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                                <div v-if="linkedinSyncResult.preview.headline" class="text-xs">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Headline LinkedIn</span>
                                    <p class="font-semibold text-slate-800 mt-0.5">{{ linkedinSyncResult.preview.headline }}</p>
                                </div>
                                <div v-if="linkedinSyncResult.preview.about" class="text-xs pt-2 border-t border-slate-200/60">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Tentang (About / Ringkasan Diri)</span>
                                    <p class="text-slate-600 mt-0.5 leading-relaxed whitespace-pre-line">{{ linkedinSyncResult.preview.about }}</p>
                                </div>
                                <div class="flex items-center gap-4 pt-2 text-[11px] text-slate-500">
                                    <span v-if="linkedinSyncResult.preview.location">Lokasi: <b>{{ linkedinSyncResult.preview.location }}</b></span>
                                    <span v-if="linkedinSyncResult.preview.connections">Koneksi: <b>{{ linkedinSyncResult.preview.connections }}</b></span>
                                    <span v-if="linkedinSyncResult.preview.followers">Pengikut: <b>{{ linkedinSyncResult.preview.followers }}</b></span>
                                </div>
                            </div>

                            <!-- Riwayat Pengalaman Kerja (Career History Timeline) -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                        Riwayat Pengalaman Kerja (Work Experience)
                                    </h4>
                                    <span class="text-xs text-slate-500 font-mono">
                                        {{ (linkedinSyncResult.preview?.experience || []).length }} Pengalaman
                                    </span>
                                </div>

                                <div v-if="(linkedinSyncResult.preview?.experience || []).length > 0" class="space-y-3">
                                    <div 
                                        v-for="(exp, idx) in linkedinSyncResult.preview.experience" 
                                        :key="idx"
                                        class="p-4 rounded-xl border border-slate-200 hover:border-slate-300 bg-white transition flex flex-col sm:flex-row sm:items-start justify-between gap-3"
                                    >
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h5 class="text-xs font-bold text-slate-900">{{ exp.position || 'Posisi Tidak Disebutkan' }}</h5>
                                                <span v-if="exp.is_current" class="px-2 py-0.2 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded-full">
                                                    Saat Ini
                                                </span>
                                            </div>
                                            <div class="text-xs text-slate-700 font-medium">
                                                {{ exp.company || 'Perusahaan Tidak Disebutkan' }}
                                                <span v-if="exp.location" class="text-slate-400 font-normal"> &bull; {{ exp.location }}</span>
                                            </div>
                                            <div class="text-[11px] text-slate-400 font-mono">
                                                {{ exp.date || '-' }} <span v-if="exp.time_period">({{ exp.time_period }})</span>
                                            </div>
                                            <p v-if="exp.description" class="text-xs text-slate-600 mt-2 leading-relaxed">
                                                {{ exp.description }}
                                            </p>
                                        </div>

                                        <!-- Tombol Cepat Salin ke Form Persetujuan -->
                                        <button
                                            v-if="linkedinSyncResult.status === 'pending'"
                                            type="button"
                                            @click="applyCareerToForm(exp)"
                                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition shrink-0 cursor-pointer flex items-center gap-1 self-start sm:self-auto"
                                            title="Terapkan jabatan dan perusahaan ini ke formulir persetujuan"
                                        >
                                            <span>Gunakan Jabatan Ini</span>
                                            <span>&rarr;</span>
                                        </button>
                                    </div>
                                </div>
                                <div v-else class="text-xs text-slate-400 italic p-4 bg-slate-50 rounded-xl text-center">
                                    Tidak ada data riwayat pengalaman kerja tersendiri pada profil LinkedIn ini.
                                </div>
                            </div>

                            <!-- Riwayat Pendidikan (Education History) -->
                            <div class="space-y-3 pt-2">
                                <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                        Riwayat Pendidikan (Education)
                                    </h4>
                                    <span class="text-xs text-slate-500 font-mono">
                                        {{ (linkedinSyncResult.preview?.education || []).length }} Institusi
                                    </span>
                                </div>

                                <div v-if="(linkedinSyncResult.preview?.education || []).length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div 
                                        v-for="(edu, idx) in linkedinSyncResult.preview.education" 
                                        :key="idx"
                                        class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 space-y-1 text-xs"
                                    >
                                        <b class="text-slate-800 block">{{ edu.school || 'Nama Institusi' }}</b>
                                        <span v-if="edu.degree || edu.field_of_study" class="text-slate-600 block">
                                            {{ [edu.degree, edu.field_of_study].filter(Boolean).join(' - ') }}
                                        </span>
                                        <span v-if="edu.date" class="text-slate-400 font-mono text-[11px] block">{{ edu.date }}</span>
                                    </div>
                                </div>
                                <div v-else class="text-xs text-slate-400 italic p-4 bg-slate-50 rounded-xl text-center">
                                    Tidak ada data riwayat pendidikan tersendiri pada profil LinkedIn ini.
                                </div>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- TAB 3: RIWAYAT SINKRONISASI (LOG HISTORY)  -->
                        <!-- ========================================== -->
                        <div v-show="activeLinkedinTab === 'riwayat_sync'" class="p-5 sm:p-6 space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                        Riwayat Seluruh Sinkronisasi LinkedIn Alumni Ini
                                    </h4>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        Mencatat seluruh penarikan data scraping LinkedIn, waktu eksekusi, serta keputusan persetujuan.
                                    </p>
                                </div>
                                <span class="text-xs font-mono font-bold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-lg">
                                    {{ linkedinHistory.length }} Riwayat
                                </span>
                            </div>

                            <div v-if="linkedinHistory.length > 0" class="overflow-x-auto">
                                <table class="w-full text-left text-xs text-slate-700 border-collapse">
                                    <thead>
                                        <tr class="border-b border-slate-200 bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 font-semibold">
                                            <th class="py-2.5 px-3">Waktu Scrape</th>
                                            <th class="py-2.5 px-3">Target URL</th>
                                            <th class="py-2.5 px-3">Jabatan & Perusahaan Terdeteksi</th>
                                            <th class="py-2.5 px-3">Status</th>
                                            <th class="py-2.5 px-3">Petugas Peninjau</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="hist in linkedinHistory" :key="hist.id" class="hover:bg-slate-50/60">
                                            <td class="py-3 px-3 font-mono text-[11px] text-slate-800 whitespace-nowrap">
                                                {{ hist.scraped_at ? new Date(hist.scraped_at).toLocaleString('id-ID') : '-' }}
                                            </td>
                                            <td class="py-3 px-3 max-w-[200px] truncate">
                                                <a :href="hist.linkedin_url" target="_blank" class="text-[#0077B5] hover:underline font-mono text-[11px]">
                                                    {{ hist.linkedin_url }}
                                                </a>
                                            </td>
                                            <td class="py-3 px-3">
                                                <b class="text-slate-800 block">{{ hist.preview?.current_job || '-' }}</b>
                                                <span class="text-slate-500 text-[11px]">{{ hist.preview?.current_company || '-' }}</span>
                                            </td>
                                            <td class="py-3 px-3 whitespace-nowrap">
                                                <span 
                                                    class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                                                    :class="hist.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : (hist.status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')"
                                                >
                                                    {{ hist.status }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 text-[11px] text-slate-600 whitespace-nowrap">
                                                {{ hist.reviewer?.name || (hist.status === 'approved' ? 'Admin Program Studi' : '-') }}
                                                <span v-if="hist.reviewed_at" class="text-slate-400 block font-mono text-[10px]">
                                                    {{ new Date(hist.reviewed_at).toLocaleDateString('id-ID') }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-xs text-slate-400 italic p-4 bg-slate-50 rounded-xl text-center">
                                Belum ada riwayat sinkronisasi tersimpan.
                            </div>
                        </div>

                        <!-- ========================================================= -->
                        <!-- TAB 4: PEMETAAN BASIS DATA TRACER STUDY (AUDIT TRACE)     -->
                        <!-- ========================================================= -->
                        <div v-show="activeLinkedinTab === 'audit_trace'" class="p-5 sm:p-6 space-y-4">
                            <!-- Toolbar Filter Pencarian -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200">
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                        Pemetaan Atribut LinkedIn ke Basis Data Kampus
                                    </h4>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        Memverifikasi kolom target basis data tracer study (biodata, perusahaan, data akademik)
                                    </p>
                                </div>

                                <div class="w-full sm:w-72">
                                    <input 
                                        type="text" 
                                        v-model="searchTraceQuery" 
                                        placeholder="Cari atribut atau kolom target..."
                                        class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-300 bg-white focus:outline-hidden focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B]"
                                    />
                                </div>
                            </div>

                            <!-- Data Table Mapping Bersih & Flat -->
                            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50 text-slate-700 border-b border-slate-200 text-[10px] uppercase font-bold tracking-wider">
                                            <th class="py-2.5 px-3 text-center w-10">#</th>
                                            <th class="py-2.5 px-3 min-w-[140px]">Atribut Scraping</th>
                                            <th class="py-2.5 px-3 min-w-[160px]">Deskripsi Field</th>
                                            <th class="py-2.5 px-3 min-w-[200px]">Nilai Hasil LinkedIn</th>
                                            <th class="py-2.5 px-3 min-w-[180px] bg-slate-100/70 text-slate-800">Target Kolom Database</th>
                                            <th class="py-2.5 px-3 min-w-[180px]">Nilai di Database Kampus</th>
                                            <th class="py-2.5 px-3 text-center min-w-[110px]">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-slate-700">
                                        <tr 
                                            v-for="(row, idx) in filteredTraceMapping" 
                                            :key="idx" 
                                            class="hover:bg-slate-50/60 transition-colors"
                                            :class="idx % 2 === 0 ? 'bg-white' : 'bg-slate-50/20'"
                                        >
                                            <td class="py-2.5 px-3 text-center font-mono text-slate-400 font-semibold text-[11px]">
                                                {{ idx + 1 }}
                                            </td>
                                            <td class="py-2.5 px-3 font-mono font-bold text-slate-900">
                                                <span class="bg-slate-100 px-2 py-0.5 rounded text-[11px] text-slate-800 border border-slate-200">
                                                    {{ row.key }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 font-semibold text-slate-800">
                                                {{ row.label }}
                                                <p v-if="row.note" class="text-[10px] text-slate-400 font-normal mt-0.5">{{ row.note }}</p>
                                            </td>
                                            <td class="py-2.5 px-3 break-words max-w-xs">
                                                <div v-if="row.key === 'li_profile_image_url' && row.scraped_value && row.scraped_value.startsWith('http')" class="flex items-center gap-2">
                                                    <img :src="row.scraped_value" class="w-8 h-8 rounded-lg object-cover border border-slate-200 shrink-0" />
                                                    <span class="truncate text-[11px] text-blue-600 font-mono">{{ row.scraped_value }}</span>
                                                </div>
                                                <span v-else class="text-xs text-slate-900 font-medium">
                                                    {{ row.scraped_value }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 bg-slate-50/50">
                                                <div class="flex items-center gap-1 flex-wrap font-mono text-[10px]">
                                                    <span class="px-1.5 py-0.5 bg-[#0D542B] text-white font-bold rounded">
                                                        {{ row.target_table }}
                                                    </span>
                                                    <span class="text-slate-400 font-bold">&rarr;</span>
                                                    <span class="px-1.5 py-0.5 bg-slate-200 text-slate-800 font-bold rounded">
                                                        {{ row.target_column }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="py-2.5 px-3 font-semibold break-words max-w-xs">
                                                <div v-if="row.target_column === 'foto' && row.db_value && row.db_value !== '(Belum tersimpan di biodata)'" class="flex items-center gap-2">
                                                    <img :src="row.db_value" class="w-8 h-8 rounded-lg object-cover border border-slate-200 shrink-0" />
                                                    <code class="text-[10px] font-mono text-[#0D542B] truncate">{{ row.db_value }}</code>
                                                </div>
                                                <span v-else :class="row.db_value && !row.db_value.startsWith('(') ? 'text-slate-900 font-bold' : 'text-slate-400 italic font-normal'">
                                                    {{ row.db_value }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-center">
                                                <span 
                                                    class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider inline-block whitespace-nowrap"
                                                    :class="{
                                                        'bg-emerald-100 text-emerald-800': row.badge === 'emerald',
                                                        'bg-sky-100 text-sky-800': row.badge === 'sky',
                                                        'bg-amber-100 text-amber-800': row.badge === 'amber',
                                                        'bg-rose-100 text-rose-800': row.badge === 'rose',
                                                        'bg-slate-100 text-slate-700': row.badge === 'slate'
                                                    }"
                                                >
                                                    {{ row.status }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr v-if="filteredTraceMapping.length === 0">
                                            <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                                Tidak ada atribut yang cocok dengan kata kunci pencarian.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Panel State Bersih jika Belum Ada Data Sinkronisasi -->
                    <div v-else-if="!linkedinSyncResult" class="bg-white rounded-2xl p-6 border border-gray-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-gray-900 text-sm">Belum Ada Riwayat Scraping LinkedIn untuk Alumni Ini</h3>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-600">Belum Disinkron</span>
                            </div>
                            <p class="text-xs text-gray-500 leading-relaxed max-w-2xl">
                                Data profil LinkedIn belum pernah diambil dari engine Apify. 
                                <span v-if="alumni.linkedin_url">Tautan LinkedIn terdaftar: <a :href="alumni.linkedin_url" target="_blank" class="text-[#0077B5] font-semibold hover:underline">{{ alumni.linkedin_url }}</a></span>
                                <span v-else>Alumni belum memiliki URL profil LinkedIn terdaftar di biodata kampus.</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button
                                v-if="alumni.linkedin_url"
                                type="button"
                                @click="handleDirectLinkedInSync"
                                :disabled="isDirectSyncing"
                                class="px-4 py-2.5 bg-[#0D542B] hover:bg-[#08381c] text-white text-xs font-bold rounded-xl transition flex items-center gap-2 shadow-xs cursor-pointer disabled:opacity-60"
                            >
                                <svg v-if="!isDirectSyncing" class="w-3.5 h-3.5 fill-current shrink-0" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                                <span>{{ isDirectSyncing ? 'Mengambil Data...' : 'Ambil Data LinkedIn Sekarang' }}</span>
                            </button>
                            <Link
                                :href="'/prodi/linkedin-sync?alumni_id=' + alumni.id"
                                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
                            >
                                Buka Direktori LinkedIn Sync
                            </Link>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>
</template>

