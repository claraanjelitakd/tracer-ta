<!--
  Halaman Detail Mahasiswa & Audit Kuesioner Tracer Study (Admin Fakultas)
  File: resources/js/Pages/fakultas/Alumni/Show.vue
  
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
    fakultas: Object,
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
            kode_pertanyaan: q.kode || q.kode_pertanyaan || '-',
            aspek_penilaian: q.aspek || q.aspek_penilaian || '-',
            keterangan: q.deskripsi || q.keterangan || '',
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
const isRejectingSync = ref(false);

const approveForm = ref({
    tipe_pekerjaan: 'pekerja',
    posisi_jabatan: '',
    posisi_wiraswasta: '',
    nama_perusahaan: '',
    skills: '',
    sync_foto: true,
});

const activeLinkedinTab = ref('penyesuaian');

const applyCareerToForm = (exp) => {
    if (!exp) return;
    if (exp.role) {
        approveForm.value.posisi_jabatan = exp.role;
        approveForm.value.posisi_wiraswasta = exp.role;
    }
    if (exp.company) {
        approveForm.value.nama_perusahaan = exp.company;
    }
    activeLinkedinTab.value = 'penyesuaian';
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: `Jabatan "${exp.role || '-'}" di ${exp.company || '-'} diterapkan ke usulan`,
        showConfirmButton: false,
        timer: 2000,
    });
};

const initApproveForm = () => {
    const preview = props.linkedinSyncResult?.preview || {};
    approveForm.value = {
        tipe_pekerjaan: 'pekerja',
        posisi_jabatan: preview.current_job || props.alumni.posisi_jabatan || '',
        posisi_wiraswasta: '',
        nama_perusahaan: preview.current_company || props.alumni.perusahaan?.nama_perusahaan || '',
        skills: Array.isArray(preview.skills) ? preview.skills.join(', ') : (props.alumni.keahlian || props.alumni.skills || ''),
        sync_foto: !!preview.profile_picture,
    };
};

initApproveForm();

watch(() => props.linkedinSyncResult, () => {
    initApproveForm();
}, { deep: true });

const handleApproveStagingWithForm = async () => {
    if (!props.linkedinSyncResult || props.linkedinSyncResult.status !== 'pending') return;

    const confirm = await Swal.fire({
        title: 'Setujui Hasil Scraping LinkedIn?',
        text: 'Data yang telah disesuaikan akan disimpan ke database utama profil alumni (biodata, riwayat pekerjaan, & keahlian).',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0D542B',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Ya, Setujui & Simpan',
        cancelButtonText: 'Batal',
    });

    if (!confirm.isConfirmed) return;

    isApprovingSync.value = true;
    try {
        const response = await fetch(`/fakultas/linkedin-sync/results/${props.linkedinSyncResult.id}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify(approveForm.value),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Berhasil Disetujui!',
                text: data.message || 'Data profil alumni telah diperbarui dengan data LinkedIn.',
                confirmButtonColor: '#0D542B',
            });
            router.reload({ preserveScroll: true });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyetujui',
                text: data.message || 'Terjadi kesalahan saat menyetujui.',
                confirmButtonColor: '#0D542B',
            });
        }
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'Kesalahan Sistem',
            text: 'Gagal memproses persetujuan hasil staging.',
            confirmButtonColor: '#0D542B',
        });
    } finally {
        isApprovingSync.value = false;
    }
};

const handleRejectStaging = async () => {
    if (!props.linkedinSyncResult || props.linkedinSyncResult.status !== 'pending') return;

    const confirm = await Swal.fire({
        title: 'Tolak Hasil Scraping LinkedIn?',
        text: 'Hasil scraping ini akan ditandai ditolak dan tidak akan diterapkan ke profil alumni.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#b91c1c',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Ya, Tolak',
        cancelButtonText: 'Batal',
    });

    if (!confirm.isConfirmed) return;

    isRejectingSync.value = true;
    try {
        const response = await fetch(`/fakultas/linkedin-sync/results/${props.linkedinSyncResult.id}/reject`, {
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
                icon: 'success',
                title: 'Hasil Ditolak',
                text: data.message || 'Hasil scraping telah ditandai ditolak.',
                confirmButtonColor: '#0D542B',
            });
            router.reload({ preserveScroll: true });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menolak',
                text: data.message || 'Terjadi kesalahan saat menolak data.',
                confirmButtonColor: '#0D542B',
            });
        }
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'Kesalahan Sistem',
            text: 'Gagal menghubungi server.',
            confirmButtonColor: '#0D542B',
        });
    } finally {
        isRejectingSync.value = false;
    }
};

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
            await fetch(`/fakultas/alumni/${props.alumni.id}/profile`, {
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
        const response = await fetch(`/fakultas/linkedin-sync/${props.alumni.id}`, {
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

const handleDirectApproveStaging = async () => {
    if (!props.linkedinSyncResult || props.linkedinSyncResult.status !== 'pending') return;

    const confirm = await Swal.fire({
        title: 'Setujui Hasil Scraping?',
        text: 'Data hasil scraping LinkedIn ini akan diterapkan ke profil utama alumni (biodata & perusahaan).',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0D542B',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Ya, Setujui & Terapkan',
        cancelButtonText: 'Batal',
    });

    if (!confirm.isConfirmed) return;

    isApprovingSync.value = true;
    try {
        const response = await fetch(`/fakultas/linkedin-sync/results/${props.linkedinSyncResult.id}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                tipe_pekerjaan: 'pekerja',
                sync_foto: true,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Berhasil Disetujui!',
                text: 'Data profil alumni telah diperbarui dengan data LinkedIn.',
                confirmButtonColor: '#0D542B',
            });
            router.reload({ preserveScroll: true });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyetujui',
                text: data.message || 'Terjadi kesalahan.',
                confirmButtonColor: '#0D542B',
            });
        }
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'Kesalahan Sistem',
            text: 'Gagal memproses persetujuan hasil staging.',
            confirmButtonColor: '#0D542B',
        });
    } finally {
        isApprovingSync.value = false;
    }
};

// Form data reaktif untuk edit profil oleh Admin Fakultas
const form = useForm(JSON.parse(JSON.stringify(props.formData || {})));

// Simpan perubahan profil oleh Admin Fakultas
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
            form.post(`/fakultas/alumni/${props.alumni.id}/profile`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Disimpan',
                        text: 'Data profil alumni telah berhasil diperbarui oleh Admin Fakultas.',
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
    <Head :title="`Detail Mahasiswa - ${alumni.data_akademik?.nama || alumni.nim} - Admin Fakultas`" />

    <div class="min-h-screen bg-[#f8fafc] text-gray-800 font-sans flex overscroll-none">
        <!-- Sidebar Resmi Admin Fakultas -->
        <Sidebar :user="user" :fakultas="fakultas" />

        <!-- Area Konten Utama -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-72 overscroll-none">
            
            <!-- Header Solid Hijau Resmi UKDW #0D542B -->
            <header class="bg-[#0D542B] text-white py-6 px-4 sm:px-6 lg:px-8 border-b border-[#0A4322]">
                <div class="max-w-[1400px] mx-auto">
                    <div class="flex items-center gap-2 text-xs text-white/80 font-medium mb-3">
                        <Link href="/fakultas/dashboard" class="hover:underline">Dashboard</Link>
                        <span>/</span>
                        <Link href="/fakultas/alumni" class="hover:underline">Data Alumni</Link>
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
                                :href="`/fakultas/alumni/${alumni.id}/export-excel`"
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
                                            ? 'bg-[#0D542B] text-white shadow-xs group-hover:bg-[#093d20] group-hover:scale-105' : 
                                            'bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-slate-600')
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

                        <!-- Tombol Simpan Profil oleh Admin Fakultas (Hijau Solid UKDW #0D542B) -->
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
                        <div v-else class="py-8 px-4 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200 mt-6">
                            <svg class="w-8 h-8 mx-auto text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <h4 class="text-xs font-bold text-slate-800">Belum Ada Informasi Atasan Terdaftar</h4>
                            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                                Alumni ini belum mengisi informasi kontak atasan langsung tempat bekerja atau belum ada penugasan survei evaluasi pengguna lulusan.
                            </p>
                        </div>
                    </div>

                    <!-- ========================================================= -->
                    <!-- TABEL HASIL PENILAIAN ASPEK KOMPETENSI                    -->
                    <!-- ========================================================= -->
                    <div v-if="evaluasiAtasan" class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
                        <div class="p-4 sm:p-5 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">
                                    Hasil Penilaian Aspek Kinerja & Kemampuan Alumni
                                </h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    {{ evaluasiAtasan.is_submitted ? 'Respon penilaian telah diverifikasi dan masuk ke dalam sistem' : 'Survei telah dikirimkan, menunggu atasan mengisi instrumen evaluasi ini' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 bg-[#0D542B] text-white text-[11px] font-bold rounded-full">
                                    {{ evaluasiAtasanQuestionsWithAnswers.length }} Butir Penilaian
                                </span>
                                <span 
                                    class="px-2.5 py-0.5 rounded text-[11px] font-semibold border"
                                    :class="evaluasiAtasan.is_submitted ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-amber-50 text-amber-800 border-amber-200'"
                                >
                                    {{ evaluasiAtasan.is_submitted ? 'Respon Terverifikasi' : 'Menunggu Pengisian Atasan' }}
                                </span>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-700 border-b border-slate-200 text-[11px] uppercase font-bold tracking-wider">
                                        <th class="py-2.5 px-3 text-center w-12 border-r border-slate-200">#</th>
                                        <th class="py-2.5 px-4 border-r border-slate-200 w-28">Kode Butir</th>
                                        <th class="py-2.5 px-4 border-r border-slate-200 min-w-[280px]">Aspek Penilaian & Deskripsi</th>
                                        <th class="py-2.5 px-4 text-center border-r border-slate-200 w-44">Skor / Nilai</th>
                                        <th class="py-2.5 px-4 min-w-[200px]">Catatan / Evaluasi Atasan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-700">
                                    <tr 
                                        v-for="(item, idx) in evaluasiAtasanQuestionsWithAnswers" 
                                        :key="item.id || idx"
                                        class="hover:bg-slate-50/60 transition-colors"
                                        :class="idx % 2 === 0 ? 'bg-white' : 'bg-slate-50/20'"
                                    >
                                        <td class="py-3 px-3 text-center font-mono font-bold text-slate-400 border-r border-slate-100">
                                            {{ idx + 1 }}
                                        </td>
                                        <td class="py-3 px-4 font-mono font-bold text-slate-800 border-r border-slate-100">
                                            <span class="bg-slate-100 px-2 py-0.5 rounded text-[11px] text-slate-800 border border-slate-200">
                                                {{ item.kode_pertanyaan }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 border-r border-slate-100">
                                            <div class="font-bold text-slate-900">{{ item.aspek_penilaian }}</div>
                                            <p v-if="item.keterangan" class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">{{ item.keterangan }}</p>
                                        </td>
                                        <td class="py-3 px-4 text-center border-r border-slate-100">
                                            <div v-if="item.skor !== null && item.skor !== undefined" class="flex flex-col items-center gap-1">
                                                <span 
                                                    class="px-2.5 py-1 rounded text-[11px] font-bold border"
                                                    :class="getSkorBadgeClass(item.skor)"
                                                >
                                                    Skor: {{ item.skor }} - {{ getSkorLabel(item.skor) }}
                                                </span>
                                            </div>
                                            <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-800 border border-amber-200">
                                                Menunggu Respon
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-slate-700">
                                            <span v-if="item.catatan" class="font-medium text-xs">{{ item.catatan }}</span>
                                            <span v-else class="text-slate-400 italic text-[11px]">-</span>
                                        </td>
                                    </tr>

                                    <tr v-if="evaluasiAtasanQuestionsWithAnswers.length === 0">
                                        <td colspan="5" class="py-8 text-center text-slate-400 text-xs italic">
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
                                    :href="'/fakultas/linkedin-sync?alumni_id=' + alumni.id + '&search=' + encodeURIComponent(alumni.nim || alumni.nama || '')" 
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
                                    :href="'/fakultas/linkedin-sync?alumni_id=' + alumni.id + '&search=' + encodeURIComponent(alumni.nim || alumni.nama || '')" 
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
                                    {{ linkedinSyncResult.reviewer?.name || (linkedinSyncResult.status === 'approved' ? 'Admin Fakultas' : 'Menunggu Review') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================================= -->
                    <!-- PANEL TERPADU: PENINJAUAN, RIWAYAT KARIER & LOG SINKRONISASI -->
                    <!-- ========================================================= -->
                    <div v-if="linkedinSyncResult && linkedinSyncResult.status === 'pending'" class="bg-white rounded-2xl shadow-xs border border-slate-200 overflow-hidden">
                        <!-- Header Formal Korporat (Tanpa Kuning AI) -->
                        <div class="p-4 sm:p-5 bg-slate-50 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-200 text-slate-700 flex items-center justify-center shrink-0 mt-0.5 font-bold text-xs">
                                    LI
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-bold text-slate-900 text-sm">Peninjauan Sinkronisasi LinkedIn (Menunggu Persetujuan)</h3>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-200 text-slate-800">Pending Review</span>
                                    </div>
                                    <p class="text-slate-600 text-xs mt-1 leading-relaxed">
                                        Data hasil penelusuran LinkedIn belum diterapkan ke basis data utama alumni. Tinjau usulan, periksa riwayat karier lengkap atau riwayat sinkronisasi sebelum menyetujui.
                                    </p>
                                </div>
                            </div>

                            <!-- Navigasi 3 Sub-Tab Peninjauan -->
                            <div class="flex items-center bg-white p-1 rounded-xl border border-slate-200 shrink-0 text-xs shadow-2xs">
                                <button
                                    type="button"
                                    @click="activeLinkedinTab = 'penyesuaian'"
                                    :class="[activeLinkedinTab === 'penyesuaian' ? 'bg-[#0D542B] text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900']"
                                    class="px-3 py-1.5 rounded-lg transition-all cursor-pointer flex items-center gap-1.5"
                                >
                                    <span>Persetujuan & Usulan</span>
                                </button>
                                <button
                                    type="button"
                                    @click="activeLinkedinTab = 'riwayat_profil'"
                                    :class="[activeLinkedinTab === 'riwayat_profil' ? 'bg-[#0D542B] text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900']"
                                    class="px-3 py-1.5 rounded-lg transition-all cursor-pointer flex items-center gap-1.5"
                                >
                                    <span>Riwayat Karier & Pendidikan</span>
                                    <span v-if="linkedinSyncResult.preview?.experience?.length" class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/20">
                                        {{ linkedinSyncResult.preview.experience.length }}
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    @click="activeLinkedinTab = 'history_log'"
                                    :class="[activeLinkedinTab === 'history_log' ? 'bg-[#0D542B] text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900']"
                                    class="px-3 py-1.5 rounded-lg transition-all cursor-pointer flex items-center gap-1.5"
                                >
                                    <span>Riwayat Sinkronisasi</span>
                                    <span v-if="linkedinHistory?.length" class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-200 text-slate-700">
                                        {{ linkedinHistory.length }}
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    @click="activeLinkedinTab = 'audit_trace'"
                                    :class="[activeLinkedinTab === 'audit_trace' ? 'bg-[#0D542B] text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900']"
                                    class="px-3 py-1.5 rounded-lg transition-all cursor-pointer flex items-center gap-1.5"
                                >
                                    <span>Pemetaan Database</span>
                                    <span v-if="filteredTraceMapping.length" class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-200 text-slate-700">
                                        {{ filteredTraceMapping.length }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- TAB 1: FORM PENYESUAIAN & USULAN -->
                        <div v-show="activeLinkedinTab === 'penyesuaian'" class="p-5 sm:p-6 space-y-4">
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 text-xs">
                                <!-- Data Saat Ini di Sistem -->
                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                                    <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                                        <span class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">
                                            Data Saat Ini di Sistem
                                        </span>
                                        <span class="text-[10px] text-slate-500">Basis Data Kampus</span>
                                    </div>
                                    <div class="space-y-2.5 text-xs">
                                        <div>
                                            <span class="text-slate-500 text-[11px] block">Jabatan / Posisi:</span>
                                            <b class="text-slate-800 block text-xs">{{ alumni.posisi_jabatan || '- Belum diisi -' }}</b>
                                        </div>
                                        <div>
                                            <span class="text-slate-500 text-[11px] block">Perusahaan / Instansi:</span>
                                            <b class="text-slate-800 block text-xs">{{ alumni.perusahaan?.nama_perusahaan || '- Belum diisi -' }}</b>
                                        </div>
                                        <div>
                                            <span class="text-slate-500 text-[11px] block">Keahlian (Skills):</span>
                                            <span class="text-slate-700 block truncate" :title="alumni.keahlian || alumni.skills || ''">{{ alumni.keahlian || alumni.skills || '- Belum diisi -' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-slate-500 text-[11px] block">Kategori Pekerjaan:</span>
                                            <span class="text-slate-700 block">{{ alumni.kategori_pekerjaan || '- Belum diisi -' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Penyesuaian Usulan LinkedIn -->
                                <div class="lg:col-span-2 bg-slate-50/60 p-4 sm:p-5 rounded-xl border border-slate-200 space-y-3.5">
                                    <div class="flex items-center justify-between flex-wrap gap-2 border-b border-slate-200 pb-2.5">
                                        <span class="text-[11px] font-bold text-slate-900 uppercase tracking-wider">
                                            Usulan Data yang Akan Disimpan ke Profil:
                                        </span>
                                        <!-- Toggle Kategori Pekerjaan -->
                                        <div class="inline-flex rounded-lg border border-slate-300 p-0.5 bg-white text-[11px] shadow-2xs">
                                            <button
                                                type="button"
                                                @click="approveForm.tipe_pekerjaan = 'pekerja'"
                                                :class="[approveForm.tipe_pekerjaan === 'pekerja' ? 'bg-[#0D542B] text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900']"
                                                class="px-2.5 py-1 rounded-md transition-all cursor-pointer flex items-center gap-1.5"
                                            >
                                                <span>Karyawan / Pekerja</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="approveForm.tipe_pekerjaan = 'wirausaha'"
                                                :class="[approveForm.tipe_pekerjaan === 'wirausaha' ? 'bg-[#0D542B] text-white font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900']"
                                                class="px-2.5 py-1 rounded-md transition-all cursor-pointer flex items-center gap-1.5"
                                            >
                                                <span>Wirausaha / Bisnis</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                        <!-- Posisi Jabatan -->
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-800 mb-1">
                                                {{ approveForm.tipe_pekerjaan === 'wirausaha' ? 'Posisi Wiraswasta / Usaha:' : 'Posisi / Jabatan Pekerjaan:' }}
                                            </label>
                                            <input
                                                v-if="approveForm.tipe_pekerjaan === 'wirausaha'"
                                                type="text"
                                                v-model="approveForm.posisi_wiraswasta"
                                                :placeholder="approveForm.posisi_jabatan || 'contoh: Founder, Owner, Direktur'"
                                                class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B]"
                                            />
                                            <input
                                                v-else
                                                type="text"
                                                v-model="approveForm.posisi_jabatan"
                                                placeholder="contoh: Software Engineer, Analis Sistem"
                                                class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B]"
                                            />
                                        </div>

                                        <!-- Nama Perusahaan -->
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-800 mb-1">
                                                {{ approveForm.tipe_pekerjaan === 'wirausaha' ? 'Nama Unit Usaha / Bisnis:' : 'Nama Perusahaan / Instansi:' }}
                                            </label>
                                            <input
                                                type="text"
                                                v-model="approveForm.nama_perusahaan"
                                                placeholder="contoh: PT K-24 INDONESIA OFFICIAL"
                                                class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B]"
                                            />
                                        </div>

                                        <!-- Kolom Keahlian (Skills) -->
                                        <div class="sm:col-span-2">
                                            <label class="block text-[11px] font-semibold text-slate-800 mb-1 flex items-center justify-between">
                                                <span>Keahlian / Skills yang Akan Disimpan ke Profil:</span>
                                                <span class="text-[10px] text-slate-500 font-normal">Sinkron ke kolom Keahlian profil alumni (pisahkan dengan koma)</span>
                                            </label>
                                            <input
                                                type="text"
                                                v-model="approveForm.skills"
                                                placeholder="contoh: Professional Expertise in Sistem Informasi, SQL, Analysis..."
                                                class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg focus:ring-1 focus:ring-[#0D542B] focus:border-[#0D542B]"
                                            />
                                        </div>

                                        <!-- Checkbox Foto LinkedIn -->
                                        <div v-if="linkedinSyncResult.preview?.profile_picture" class="sm:col-span-2 p-3 bg-white rounded-xl border border-slate-300 flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-3">
                                                <img :src="linkedinSyncResult.preview.profile_picture" class="w-10 h-10 rounded-lg object-cover border border-slate-200 shrink-0" />
                                                <div>
                                                    <span class="text-xs font-bold text-slate-900 block">Terapkan Foto Profil LinkedIn ke Biodata Alumni</span>
                                                    <span class="text-[10px] text-slate-500">Foto akan disimpan ke sistem /uploads/profile/</span>
                                                </div>
                                            </div>
                                            <label class="inline-flex items-center gap-2 cursor-pointer shrink-0">
                                                <input type="checkbox" v-model="approveForm.sync_foto" class="w-4 h-4 text-[#0D542B] rounded border-slate-300 focus:ring-[#0D542B]" />
                                                <span class="text-xs font-semibold text-slate-700">Gunakan</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: RIWAYAT KARIER & PENDIDIKAN DARI LINKEDIN -->
                        <div v-show="activeLinkedinTab === 'riwayat_profil'" class="p-5 sm:p-6 space-y-5">
                            <!-- Headline & Ringkasan / About -->
                            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-slate-700 uppercase tracking-wider text-[10px]">Headline Profil LinkedIn</span>
                                    <span class="text-slate-500">{{ linkedinSyncResult.preview?.location || '-' }}</span>
                                </div>
                                <p class="text-slate-900 font-medium text-xs">{{ linkedinSyncResult.preview?.headline || '-' }}</p>
                                <div v-if="linkedinSyncResult.preview?.about" class="pt-2 border-t border-slate-200">
                                    <span class="font-bold text-slate-700 uppercase tracking-wider text-[10px] block mb-1">Tentang / Ringkasan (About)</span>
                                    <p class="text-slate-700 text-xs leading-relaxed whitespace-pre-line">{{ linkedinSyncResult.preview.about }}</p>
                                </div>
                                <div class="flex items-center gap-4 pt-1 text-[11px] text-slate-500">
                                    <span>Koneksi: <strong class="text-slate-800">{{ linkedinSyncResult.preview?.connections || '-' }}</strong></span>
                                    <span>Pengikut: <strong class="text-slate-800">{{ linkedinSyncResult.preview?.followers || '-' }}</strong></span>
                                </div>
                            </div>

                            <!-- Daftar Riwayat Pekerjaan (Work Experience) -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                        Riwayat Pengalaman Kerja (Experience)
                                    </h4>
                                    <span class="text-[11px] text-slate-500">
                                        Total {{ linkedinSyncResult.preview?.experience?.length || 0 }} Riwayat Terdeteksi
                                    </span>
                                </div>

                                <div v-if="linkedinSyncResult.preview?.experience?.length" class="space-y-2.5">
                                    <div
                                        v-for="(exp, expIdx) in linkedinSyncResult.preview.experience"
                                        :key="expIdx"
                                        class="bg-white p-3.5 rounded-xl border border-slate-200 hover:border-slate-300 transition flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
                                    >
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-slate-900 text-xs sm:text-sm">{{ exp.role || '-' }}</span>
                                                <span v-if="expIdx === 0" class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#0D542B] text-white">
                                                    Paling Baru
                                                </span>
                                            </div>
                                            <div class="text-slate-700 font-medium">
                                                {{ exp.company || '-' }} &bull; <span class="text-slate-500 font-normal">{{ exp.duration || '-' }}</span>
                                            </div>
                                            <div v-if="exp.location" class="text-slate-500 text-[11px]">
                                                Lokasi: {{ exp.location }}
                                            </div>
                                            <div v-if="exp.description" class="text-slate-600 text-[11px] leading-relaxed pt-1 whitespace-pre-line">
                                                {{ exp.description }}
                                            </div>
                                        </div>

                                        <!-- Tombol Cepat: Terapkan Jabatan Ini ke Usulan -->
                                        <button
                                            type="button"
                                            @click="applyCareerToForm(exp)"
                                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 text-[11px] font-semibold rounded-lg transition shrink-0 self-start sm:self-auto cursor-pointer border border-slate-200"
                                            title="Gunakan posisi dan perusahaan ini untuk disetujui"
                                        >
                                            Gunakan Jabatan Ini
                                        </button>
                                    </div>
                                </div>
                                <div v-else class="text-center py-6 bg-slate-50 rounded-xl border border-slate-200 text-slate-500 text-xs">
                                    Tidak ada riwayat pekerjaan terperinci yang berhasil diekstrak dari profil ini.
                                </div>
                            </div>

                            <!-- Daftar Riwayat Pendidikan (Education) -->
                            <div class="space-y-3 pt-2">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                        Riwayat Pendidikan (Education)
                                    </h4>
                                    <span class="text-[11px] text-slate-500">
                                        Total {{ linkedinSyncResult.preview?.education?.length || 0 }} Riwayat
                                    </span>
                                </div>

                                <div v-if="linkedinSyncResult.preview?.education?.length" class="space-y-2">
                                    <div
                                        v-for="(edu, eduIdx) in linkedinSyncResult.preview.education"
                                        :key="eduIdx"
                                        class="bg-white p-3 rounded-xl border border-slate-200 text-xs"
                                    >
                                        <div class="font-bold text-slate-900">{{ edu.school || '-' }}</div>
                                        <div class="text-slate-700">{{ edu.degree || '-' }} <span v-if="edu.fieldOfStudy">&bull; {{ edu.fieldOfStudy }}</span></div>
                                        <div v-if="edu.period" class="text-slate-500 text-[11px]">{{ edu.period }}</div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-4 bg-slate-50 rounded-xl border border-slate-200 text-slate-500 text-xs">
                                    Tidak ada data riwayat pendidikan.
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: RIWAYAT SINKRONISASI (HISTORY LOG) -->
                        <div v-show="activeLinkedinTab === 'history_log'" class="p-5 sm:p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                    Log Riwayat Sinkronisasi LinkedIn
                                </h4>
                                <span class="text-[11px] text-slate-500">
                                    Menampilkan rekaman aktivitas penelusuran scraping LinkedIn
                                </span>
                            </div>

                            <div v-if="linkedinHistory?.length" class="overflow-x-auto border border-slate-200 rounded-xl">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 border-b border-slate-200 text-[10px] uppercase font-bold text-slate-600 tracking-wider">
                                        <tr>
                                            <th class="px-4 py-2.5">Waktu Scrape</th>
                                            <th class="px-4 py-2.5">Target LinkedIn</th>
                                            <th class="px-4 py-2.5">Pekerjaan & Perusahaan Terdeteksi</th>
                                            <th class="px-4 py-2.5">Status</th>
                                            <th class="px-4 py-2.5">Petugas Peninjau</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-slate-700">
                                        <tr v-for="log in linkedinHistory" :key="log.id" :class="[log.id === linkedinSyncResult.id ? 'bg-slate-50/80 font-medium' : 'hover:bg-slate-50/50']">
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="font-semibold text-slate-900">
                                                    {{ log.scraped_at ? new Date(log.scraped_at).toLocaleString('id-ID') : '-' }}
                                                </div>
                                                <span v-if="log.id === linkedinSyncResult.id" class="text-[10px] text-[#0D542B] font-bold">
                                                    (Aktif Ditinjau)
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 max-w-[200px] truncate">
                                                <a v-if="log.target_url" :href="log.target_url" target="_blank" class="text-blue-600 hover:underline">
                                                    {{ log.target_url }}
                                                </a>
                                                <span v-else class="text-slate-400">-</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-slate-900">
                                                    {{ log.preview?.current_job || '-' }}
                                                </div>
                                                <div class="text-[11px] text-slate-500">
                                                    {{ log.preview?.current_company || '-' }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span
                                                    v-if="log.status === 'approved'"
                                                    class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800"
                                                >
                                                    Approved
                                                </span>
                                                <span
                                                    v-else-if="log.status === 'pending'"
                                                    class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-200 text-slate-800"
                                                >
                                                    Pending
                                                </span>
                                                <span
                                                    v-else-if="log.status === 'rejected'"
                                                    class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-rose-100 text-rose-800"
                                                >
                                                    Rejected
                                                </span>
                                                <span
                                                    v-else
                                                    class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-gray-100 text-gray-700"
                                                >
                                                    {{ log.status }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-slate-600 text-[11px]">
                                                {{ log.reviewer?.name || (log.status === 'approved' ? 'Admin Fakultas' : '-') }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-center py-6 bg-slate-50 rounded-xl border border-slate-200 text-slate-500 text-xs">
                                Belum ada riwayat sinkronisasi terdahulu.
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

                        <!-- Footer Aksi Persetujuan (Formal & Bersih) -->
                        <div class="p-4 sm:p-5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div class="text-xs text-slate-500">
                                Peninjau: <span class="font-semibold text-slate-700">Admin Fakultas</span> &bull; Waktu Scrape: <span class="font-mono">{{ linkedinSyncResult.scraped_at ? new Date(linkedinSyncResult.scraped_at).toLocaleString('id-ID') : '-' }}</span>
                            </div>

                            <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                                <button
                                    type="button"
                                    @click="handleRejectStaging"
                                    :disabled="isRejectingSync || isApprovingSync"
                                    class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold rounded-xl transition cursor-pointer disabled:opacity-50"
                                >
                                    <span v-if="isRejectingSync">Menolak...</span>
                                    <span v-else>Tolak (Reject)</span>
                                </button>

                                <button
                                    type="button"
                                    @click="handleApproveStagingWithForm"
                                    :disabled="isApprovingSync || isRejectingSync"
                                    class="px-5 py-2 bg-[#0D542B] hover:bg-[#093c1f] text-white text-xs font-bold rounded-xl transition shadow-2xs flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                                >
                                    <svg v-if="!isApprovingSync" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>{{ isApprovingSync ? 'Menyimpan & Menerapkan...' : 'Setujui & Terapkan Data' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Banner Konfirmasi Jika Data Sudah Diapprove -->
                    <div v-else-if="linkedinSyncResult && linkedinSyncResult.status === 'approved'" class="bg-emerald-50/70 border border-emerald-200 rounded-2xl p-4 sm:p-5 flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="text-xs">
                            <div class="flex items-center gap-2">
                                <h4 class="font-bold text-emerald-950 text-sm">Hasil Sinkronisasi LinkedIn Telah Disetujui (Approved)</h4>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-200 text-emerald-900">Aktif di Biodata</span>
                            </div>
                            <p class="text-emerald-800 mt-1 leading-relaxed">
                                Data profil LinkedIn alumni ini telah diverifikasi dan diterapkan ke database utama oleh <strong>{{ linkedinSyncResult.reviewer?.name || 'Admin Fakultas' }}</strong> pada {{ linkedinSyncResult.reviewed_at ? new Date(linkedinSyncResult.reviewed_at).toLocaleString('id-ID') : '-' }}. Rincian atribut tersimpan dapat ditinjau pada tabel audit & trace di bawah.
                            </p>
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
                                :href="'/fakultas/linkedin-sync?alumni_id=' + alumni.id"
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

