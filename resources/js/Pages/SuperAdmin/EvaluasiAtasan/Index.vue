<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import Sidebar from '../Components/Sidebar.vue';

const props = defineProps({
    user: Object,
    questions: {
        type: Array,
        default: () => [],
    },
    evaluasis: {
        type: Object,
        default: () => ({ data: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_questions: 0,
            active_questions: 0,
            total_evaluasi: 0,
            submitted_evaluasi: 0,
        }),
    },
});

const activeTab = ref('questions'); // 'questions' atau 'responses'
const searchQuery = ref('');

const filteredQuestions = computed(() => {
    if (!searchQuery.value.trim()) return props.questions;
    const q = searchQuery.value.toLowerCase();
    return props.questions.filter(item =>
        item.aspek?.toLowerCase().includes(q) ||
        item.kode?.toLowerCase().includes(q) ||
        item.deskripsi?.toLowerCase().includes(q)
    );
});

// Modal Tambah / Edit Pertanyaan
const openQuestionModal = (question = null) => {
    const isEdit = !!question;
    const initialAspek = question?.aspek || '';
    const initialKode = question?.kode || '';
    const initialDeskripsi = question?.deskripsi || '';
    const initialKategori = question?.kategori || 'Kinerja';
    const initialOrder = question?.order || (props.questions.length + 1);

    const htmlContent = `
        <div class="text-left space-y-3 text-xs">
            <div>
                <label class="block font-bold text-gray-700 mb-1">Nama Aspek Penilaian *</label>
                <input id="swal-aspek" type="text" value="${initialAspek}" placeholder="Contoh: Integritas (Etika dan Moral)" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-emerald-700" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Kode Aspek</label>
                    <input id="swal-kode" type="text" value="${initialKode}" ${isEdit ? 'disabled' : ''} placeholder="otomatis jika kosong" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs font-mono uppercase focus:outline-none focus:ring-1 focus:ring-emerald-700 ${isEdit ? 'bg-gray-100 text-gray-500' : ''}" />
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Nomor Urut</label>
                    <input id="swal-order" type="number" min="1" value="${initialOrder}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-emerald-700" />
                </div>
            </div>

            <div>
                <label class="block font-bold text-gray-700 mb-1">Kategori</label>
                <input id="swal-kategori" type="text" value="${initialKategori}" placeholder="Kinerja / Kompetensi" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-emerald-700" />
            </div>

            <div>
                <label class="block font-bold text-gray-700 mb-1">Deskripsi / Penjelasan Butir</label>
                <textarea id="swal-deskripsi" rows="2" placeholder="Penjelasan singkat mengenai aspek ini..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-emerald-700">${initialDeskripsi}</textarea>
            </div>
        </div>
    `;

    Swal.fire({
        title: isEdit ? 'Sunting Aspek Evaluasi Atasan' : 'Tambah Aspek Evaluasi Atasan Baru',
        html: htmlContent,
        showCancelButton: true,
        confirmButtonText: isEdit ? 'Simpan Perubahan' : 'Tambah Aspek',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#0D542B',
        cancelButtonColor: '#6B7280',
        width: '520px',
        preConfirm: () => {
            const aspek = document.getElementById('swal-aspek')?.value?.trim();
            const kode = document.getElementById('swal-kode')?.value?.trim();
            const deskripsi = document.getElementById('swal-deskripsi')?.value?.trim() || null;
            const kategori = document.getElementById('swal-kategori')?.value?.trim() || 'Kinerja';
            const order = parseInt(document.getElementById('swal-order')?.value, 10) || 1;

            if (!aspek) {
                Swal.showValidationMessage('Nama aspek penilaian wajib diisi.');
                return false;
            }

            return { aspek, kode, deskripsi, kategori, order };
        },
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            if (isEdit) {
                router.put(`/superadmin/evaluasi-atasan/pertanyaan/${question.id}`, result.value, {
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire('Berhasil', 'Butir aspek evaluasi berhasil diperbarui.', 'success');
                    },
                });
            } else {
                router.post('/superadmin/evaluasi-atasan/pertanyaan', result.value, {
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire('Berhasil', 'Butir aspek evaluasi baru berhasil ditambahkan.', 'success');
                    },
                });
            }
        }
    });
};

const toggleActive = (question) => {
    router.patch(`/superadmin/evaluasi-atasan/pertanyaan/${question.id}/toggle`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire('Status Diperbarui', `Aspek ${question.aspek} kini ${question.is_active ? 'dinonaktifkan' : 'diaktifkan'}.`, 'success');
        },
    });
};

const deleteQuestion = (question) => {
    Swal.fire({
        title: 'Hapus Butir Aspek?',
        text: `Apakah Anda yakin ingin menghapus butir "${question.aspek}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6B7280',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/superadmin/evaluasi-atasan/pertanyaan/${question.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Berhasil', 'Butir aspek berhasil dihapus.', 'success');
                },
            });
        }
    });
};

// Detail Respon Evaluasi Atasan Modal
const viewDetailEvaluasi = (evaluasi) => {
    const alumniName = evaluasi.biodata?.nama || evaluasi.biodata?.data_akademik?.nama || '-';
    const nim = evaluasi.biodata?.nim || '-';
    const prodi = evaluasi.biodata?.prodi?.nama_prodi || '-';
    const namaPerusahaan = evaluasi.perusahaan?.nama_perusahaan || evaluasi.nama_perusahaan || '-';
    const atasanNama = evaluasi.atasan?.nama || '-';
    const atasanEmail = evaluasi.atasan?.email || '-';
    const atasanTelp = evaluasi.atasan?.telepon || '-';

    let responsHtml = '<p class="text-gray-400 italic">Belum ada respon yang diisi.</p>';
    if (evaluasi.respons && evaluasi.respons.length > 0) {
        responsHtml = `
            <table class="w-full text-xs text-left border border-gray-300 mt-2">
                <thead>
                    <tr class="bg-gray-100 text-gray-800">
                        <th class="border border-gray-300 px-2 py-1">No</th>
                        <th class="border border-gray-300 px-2 py-1">Aspek Kinerja</th>
                        <th class="border border-gray-300 px-2 py-1 text-center">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    ${evaluasi.respons.map((r, i) => `
                        <tr>
                            <td class="border border-gray-300 px-2 py-1 text-center">${i + 1}</td>
                            <td class="border border-gray-300 px-2 py-1 font-semibold">${r.pertanyaan?.aspek || '-'}</td>
                            <td class="border border-gray-300 px-2 py-1 text-center font-bold text-[#0D542B]">${r.nilai || '-'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }

    Swal.fire({
        title: `<span class="text-base font-bold text-gray-900">Detail Hasil Evaluasi Pengguna Lulusan</span>`,
        html: `
            <div class="text-left text-xs space-y-3 pt-1 text-gray-800">
                <div class="p-3 bg-gray-50 border border-gray-200 rounded space-y-1">
                    <div><span class="text-gray-500">Nama Alumni:</span> <strong>${alumniName}</strong> (${nim})</div>
                    <div><span class="text-gray-500">Program Studi:</span> <strong>${prodi}</strong></div>
                    <div><span class="text-gray-500">Perusahaan:</span> <strong>${namaPerusahaan}</strong></div>
                    <div><span class="text-gray-500">Nama Atasan:</span> <strong>${atasanNama}</strong> (${atasanEmail} / ${atasanTelp})</div>
                    <div class="pt-1"><span class="text-gray-500">Status:</span> <span class="font-bold ${evaluasi.is_submitted ? 'text-emerald-700' : 'text-amber-600'}">${evaluasi.is_submitted ? '✓ Sudah Mengisi' : 'Menunggu Pengisian'}</span></div>
                </div>

                <div>
                    <span class="font-bold text-gray-700 block">Hasil Penilaian Kinerja Lulusan:</span>
                    ${responsHtml}
                </div>

                ${evaluasi.catatan_lainnya ? `
                    <div class="p-2.5 bg-yellow-50/80 border border-yellow-200 rounded text-xs text-yellow-900">
                        <span class="font-bold block mb-0.5">Catatan / Saran Tambahan:</span>
                        ${evaluasi.catatan_lainnya}
                    </div>
                ` : ''}
            </div>
        `,
        width: '600px',
        confirmButtonText: 'Tutup',
        confirmButtonColor: '#0D542B',
    });
};
</script>

<template>
    <Head title="Pengaturan Kuesioner Evaluasi Atasan - Super Admin" />

    <div class="min-h-screen bg-slate-50 flex font-sans">
        <!-- Sidebar Terpadu Super Admin -->
        <Sidebar :user="user" />

        <!-- Area Konten Utama -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-72">
            <!-- Header Halaman Bersih & Flat -->
            <div class="bg-white border-b border-gray-200 px-6 py-5">
                <div class="w-full flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 text-xs text-gray-500 font-medium mb-1">
                            <Link href="/superadmin/dashboard" class="hover:text-emerald-700">Dashboard</Link>
                            <span>/</span>
                            <span class="text-gray-900 font-semibold">Kuesioner Evaluasi Atasan</span>
                        </div>
                        <h1 class="text-xl font-bold text-gray-900">
                            Pengaturan Kuesioner Evaluasi Pengguna Lulusan (Atasan)
                        </h1>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Konfigurasi butir instrumen aspek kinerja kepuasan pengguna lulusan serta tinjau riwayat respon evaluasi atasan.
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <button
                            v-if="activeTab === 'questions'"
                            type="button"
                            @click="openQuestionModal()"
                            class="px-3.5 py-1.5 bg-[#0D542B] hover:bg-[#08381c] text-white font-semibold text-xs rounded-lg transition-colors flex items-center gap-1.5 cursor-pointer shadow-xs"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Tambah Aspek Penilaian</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tab Navigasi -->
            <div class="px-6 pt-4 bg-white border-b border-gray-200 flex gap-4 text-xs font-bold">
                <button
                    @click="activeTab = 'questions'"
                    class="pb-3 border-b-2 transition-colors cursor-pointer flex items-center gap-2"
                    :class="activeTab === 'questions' ? 'border-[#0D542B] text-[#0D542B]' : 'border-transparent text-gray-500 hover:text-gray-800'"
                >
                    <span>Butir Aspek Evaluasi (Instrumen)</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-100 text-[#0D542B]">{{ stats.active_questions }}</span>
                </button>
                <button
                    @click="activeTab = 'responses'"
                    class="pb-3 border-b-2 transition-colors cursor-pointer flex items-center gap-2"
                    :class="activeTab === 'responses' ? 'border-[#0D542B] text-[#0D542B]' : 'border-transparent text-gray-500 hover:text-gray-800'"
                >
                    <span>Daftar Undangan & Respon Atasan</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-700">{{ stats.submitted_evaluasi }} / {{ stats.total_evaluasi }}</span>
                </button>
            </div>

            <!-- Konten Tab 1: Butir Aspek Evaluasi -->
            <main v-if="activeTab === 'questions'" class="w-full p-6 space-y-4">
                <!-- Bilah Pencarian -->
                <div class="bg-white p-3.5 rounded-lg border border-gray-200 flex justify-between items-center text-xs shadow-2xs">
                    <span class="text-gray-600 font-medium">
                        Menampilkan <strong>{{ filteredQuestions.length }}</strong> aspek evaluasi kepuasan pengguna lulusan
                    </span>
                    <div class="w-64">
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Cari aspek penilaian..."
                            class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-emerald-700"
                        />
                    </div>
                </div>

                <!-- Tabel Butir Aspek -->
                <div class="bg-white border border-gray-300 overflow-x-auto rounded-lg shadow-2xs">
                    <table class="w-full text-left border-collapse border border-gray-300 text-xs bg-white">
                        <thead>
                            <tr class="bg-gray-100 text-gray-800 font-semibold border-b border-gray-300 text-[11px]">
                                <th class="border border-gray-300 px-3 py-2 text-center w-12">No</th>
                                <th class="border border-gray-300 px-3 py-2 w-32">Kode Aspek</th>
                                <th class="border border-gray-300 px-3 py-2 min-w-[240px]">Aspek Penilaian</th>
                                <th class="border border-gray-300 px-3 py-2 w-28 text-center">Status</th>
                                <th class="border border-gray-300 px-3 py-2 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, idx) in filteredQuestions" :key="item.id" class="hover:bg-gray-50 border-b border-gray-200">
                                <td class="border border-gray-300 px-3 py-2.5 text-center font-bold text-gray-500">{{ item.order || (idx + 1) }}</td>
                                <td class="border border-gray-300 px-3 py-2.5 font-mono text-[11px] text-gray-700 font-bold">{{ item.kode }}</td>
                                <td class="border border-gray-300 px-3 py-2.5">
                                    <div class="font-bold text-gray-900 text-sm">{{ item.aspek }}</div>
                                    <div v-if="item.deskripsi" class="text-xs text-gray-500 mt-0.5">{{ item.deskripsi }}</div>
                                </td>
                                <td class="border border-gray-300 px-3 py-2.5 text-center">
                                    <button
                                        type="button"
                                        @click="toggleActive(item)"
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition-colors"
                                        :class="item.is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
                                    >
                                        {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </td>
                                <td class="border border-gray-300 px-3 py-2.5 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="openQuestionModal(item)"
                                            class="p-1 text-amber-600 hover:bg-amber-50 rounded cursor-pointer"
                                            title="Sunting Aspek"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteQuestion(item)"
                                            class="p-1 text-rose-600 hover:bg-rose-50 rounded cursor-pointer"
                                            title="Hapus Aspek"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </main>

            <!-- Konten Tab 2: Daftar Undangan & Respon Atasan -->
            <main v-else class="w-full p-6 space-y-4">
                <div class="bg-white border border-gray-300 overflow-x-auto rounded-lg shadow-2xs">
                    <table class="w-full text-left border-collapse border border-gray-300 text-xs bg-white">
                        <thead>
                            <tr class="bg-gray-100 text-gray-800 font-semibold border-b border-gray-300 text-[11px]">
                                <th class="border border-gray-300 px-3 py-2 text-center w-12">No</th>
                                <th class="border border-gray-300 px-3 py-2 min-w-[200px]">Alumni Dinilai</th>
                                <th class="border border-gray-300 px-3 py-2 min-w-[200px]">Perusahaan / Instansi</th>
                                <th class="border border-gray-300 px-3 py-2 min-w-[200px]">Atasan Langsung</th>
                                <th class="border border-gray-300 px-3 py-2 text-center w-32">Status Pengisian</th>
                                <th class="border border-gray-300 px-3 py-2 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(ev, idx) in evaluasis.data" :key="ev.id" class="hover:bg-gray-50 border-b border-gray-200">
                                <td class="border border-gray-300 px-3 py-2.5 text-center font-bold text-gray-500">{{ idx + 1 }}</td>
                                <td class="border border-gray-300 px-3 py-2.5">
                                    <div class="font-bold text-gray-900">{{ ev.biodata?.nama || ev.biodata?.data_akademik?.nama || '-' }}</div>
                                    <div class="text-[11px] text-gray-500">NIM: {{ ev.biodata?.nim || '-' }} • {{ ev.biodata?.prodi?.nama_prodi || '-' }}</div>
                                </td>
                                <td class="border border-gray-300 px-3 py-2.5">
                                    <div class="font-bold text-gray-900">{{ ev.perusahaan?.nama_perusahaan || ev.nama_perusahaan || '-' }}</div>
                                    <div class="text-[11px] text-gray-500">{{ ev.perusahaan?.alamat || ev.alamat_lengkap || '-' }}</div>
                                </td>
                                <td class="border border-gray-300 px-3 py-2.5">
                                    <div class="font-bold text-gray-900">{{ ev.atasan?.nama || '-' }}</div>
                                    <div class="text-[11px] text-gray-500">{{ ev.atasan?.email || '-' }} • {{ ev.atasan?.telepon || '-' }}</div>
                                </td>
                                <td class="border border-gray-300 px-3 py-2.5 text-center">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="ev.is_submitted ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                    >
                                        {{ ev.is_submitted ? '✓ Sudah Diisi' : 'Menunggu Pengisian' }}
                                    </span>
                                </td>
                                <td class="border border-gray-300 px-3 py-2.5 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button
                                            type="button"
                                            @click="viewDetailEvaluasi(ev)"
                                            class="px-2.5 py-1 bg-[#0D542B] hover:bg-[#08381c] text-white rounded text-[11px] font-semibold cursor-pointer"
                                        >
                                            Lihat Detail
                                        </button>
                                        <a
                                            :href="`/evaluasi-atasan/${ev.token}`"
                                            target="_blank"
                                            class="p-1 text-blue-600 hover:bg-blue-50 rounded"
                                            title="Buka Link Publik"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!evaluasis.data || evaluasis.data.length === 0">
                                <td colspan="6" class="border border-gray-300 px-4 py-8 text-center text-gray-400 italic">
                                    Belum ada data evaluasi atasan yang tersimpan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </main>

            <footer class="text-gray-400 text-xs text-center mt-12 py-6 border-t border-gray-100">
                &copy; {{ new Date().getFullYear() }} Universitas Kristen Duta Wacana. Hak Cipta Dilindungi.
            </footer>
        </div>
    </div>
</template>
