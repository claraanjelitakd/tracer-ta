<!--
  Halaman Utama Kelola Bagian Kuesioner (Section) - SuperAdmin (Index.vue)
  
  Fungsi:
  Pusat manajemen struktur dan pengelompokan kuesioner Tracer Study.
  Superadmin dapat membuat, mengubah nama/judul, menghapus bagian kuesioner,
  serta mengubah urutan posisi bagian (naik/turun) secara dinamis.
-->
<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';

import Navbar from '@/Pages/SuperAdmin/Components/Navbar.vue';
import SectionModal from './Components/SectionModal.vue';

// Properti yang dikirimkan oleh SuperAdmin\KelolaSection\KelolaSectionController
const props = defineProps({
    // Daftar seluruh section kuesioner beserta relasi kuesioner dan jumlah pertanyaan
    sections: {
        type: Array,
        default: () => [],
    },
    // Daftar seluruh instrumen kuesioner aktif
    kuesioners: {
        type: Array,
        default: () => [],
    },
    questionnaires: {
        type: Array,
        default: () => [],
    },
});

// ========================================================
// 1. STATE PENCARIAN & FILTER
// ========================================================
// String input teks pencarian judul atau nomor bagian
const searchQuery = ref('');

// State indikator saat proses pemindahan urutan sedang berlangsung
const isReordering = ref(false);

/**
 * Komputasi daftar section yang lolos filter pencarian teks judul atau nomor urut.
 */
const filteredSections = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.sections;
    }
    const query = searchQuery.value.toLowerCase();
    return props.sections.filter((sec) => {
        const matchTitle = sec.title?.toLowerCase().includes(query);
        const matchOrder = sec.order?.toString().includes(query);
        const matchKuesioner = (sec.kuesioner?.title || sec.questionnaire?.title)?.toLowerCase().includes(query);
        return matchTitle || matchOrder || matchKuesioner;
    });
});

/**
 * Menghitung total keseluruhan pertanyaan dari seluruh section yang ada.
 */
const totalQuestionsCount = computed(() => {
    return props.sections.reduce((sum, sec) => sum + (sec.subpertanyaans_count ?? sec.questions_count ?? 0), 0);
});

// ========================================================
// 2. MODAL TAMBAH & EDIT SECTION
// ========================================================
// Penanda visibilitas modal form section
const showSectionModal = ref(false);

// Penanda apakah modal dalam mode edit (true) atau tambah baru (false)
const isEditSection = ref(false);

// Menyimpan data section yang sedang dipilih untuk diedit
const selectedSection = ref(null);

/**
 * Menghitung rekomendasi nomor urut section berikutnya jika menambah baru.
 */
const nextAvailableOrder = computed(() => {
    if (props.sections.length === 0) return 1;
    const maxOrder = Math.max(...props.sections.map((s) => s.order || 0));
    return maxOrder + 1;
});

/**
 * Membuka modal dialog untuk menambah section baru.
 */
const openAddModal = () => {
    isEditSection.value = false;
    selectedSection.value = null;
    showSectionModal.value = true;
};

/**
 * Membuka modal dialog untuk menyunting section yang dipilih.
 *
 * @param {Object} sec - Objek data section yang akan diedit
 */
const openEditModal = (sec) => {
    isEditSection.value = true;
    selectedSection.value = sec;
    showSectionModal.value = true;
};

/**
 * Menutup modal dialog form section.
 */
const closeSectionModal = () => {
    showSectionModal.value = false;
    selectedSection.value = null;
};

// ========================================================
// 3. OPERASI PENGURUTAN (REORDER NAIK / TURUN)
// ========================================================
/**
 * Memindahkan urutan section satu langkah ke atas ('up') atau ke bawah ('down').
 *
 * @param {Object} sec - Section yang dipindahkan posisinya
 * @param {string} direction - Arah pergerakan ('up' atau 'down')
 */
const handleMoveSection = (sec, direction) => {
    isReordering.value = true;
    router.post(
        '/superadmin/sections/reorder',
        {
            id: sec.id,
            direction: direction,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isReordering.value = false;
            },
        }
    );
};

// ========================================================
// 4. OPERASI PENGHAPUSAN SECTION (DELETE DENGAN SWEETALERT2)
// ========================================================
/**
 * Menghapus section kuesioner dengan konfirmasi peringatan SweetAlert2.
 * Jika section memiliki pertanyaan, pengguna diberikan peringatan tegas.
 *
 * @param {Object} sec - Section yang akan dihapus
 */
const handleDeleteSection = (sec) => {
    const questionCount = sec.subpertanyaans_count ?? sec.questions_count ?? 0;
    
    // Susun pesan peringatan jika terdapat pertanyaan di dalam section
    const warningHtml = questionCount > 0 
        ? `
            <div class="text-center space-y-3">
                <p class="text-sm text-gray-600">Apakah Anda yakin ingin menghapus bagian:</p>
                <div class="inline-block px-4 py-2 rounded-xl bg-red-50 text-red-700 font-extrabold text-sm border border-red-200">
                    Section ${sec.order}: ${sec.title}
                </div>
                <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-amber-800 text-xs font-semibold">
                    ⚠️ Peringatan Kritis: Bagian ini memuat <span class="font-extrabold">${questionCount} butir pertanyaan</span>. Menghapus bagian ini juga akan menghapus seluruh pertanyaan dan opsi jawaban di dalamnya secara permanen!
                </div>
            </div>
        `
        : `
            <div class="text-center space-y-2">
                <p class="text-sm text-gray-600">Apakah Anda yakin ingin menghapus bagian:</p>
                <div class="inline-block px-4 py-2 rounded-xl bg-gray-100 text-gray-800 font-extrabold text-sm">
                    Section ${sec.order}: ${sec.title}
                </div>
                <p class="text-xs text-gray-400">Bagian ini belum memiliki butir pertanyaan.</p>
            </div>
        `;

    Swal.fire({
        title: 'Hapus Bagian Kuesioner?',
        html: warningHtml,
        icon: questionCount > 0 ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus Sekarang',
        cancelButtonText: 'Batal',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/superadmin/sections/${sec.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Berhasil Dihapus!',
                        text: `Section ${sec.order} telah dihapus dari sistem.`,
                        icon: 'success',
                        confirmButtonColor: '#005B3C',
                        confirmButtonText: 'Tutup',
                    });
                },
                onError: () => {
                    Swal.fire({
                        title: 'Gagal Menghapus!',
                        text: 'Terjadi kendala pada sistem saat menghapus data bagian kuesioner.',
                        icon: 'error',
                        confirmButtonColor: '#d33',
                    });
                },
            });
        }
    });
};
</script>

<template>
    <Head title="Kelola Bagian Kuesioner - Super Admin" />

    <div class="min-h-screen bg-[#f8fafc] flex flex-col font-sans pb-24">
        
        <!-- Navbar Terpadu Superadmin -->
        <Navbar />

        <!-- Header Solid Hijau Resmi UKDW #0D542B -->
        <header class="bg-[#0D542B] text-white pt-10 pb-20">
            <div class="w-full max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <Link 
                            href="/superadmin/pertanyaan" 
                            class="text-xs font-semibold text-white/80 hover:text-white flex items-center gap-1 transition-colors"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            <span>Kembali ke Kelola Pertanyaan</span>
                        </Link>
                    </div>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                        Kelola Bagian Kuesioner (Section)
                    </h1>
                    <p class="text-white/90 font-medium mt-1 max-w-2xl text-sm sm:text-base leading-relaxed">
                        Atur struktur bab/bagian kuesioner Tracer Study, urutan penomoran alur pengisian alumni, dan keterhubungan kuesioner.
                    </p>
                </div>

                <!-- Tombol Pintas ke Halaman Pertanyaan -->
                <div class="flex items-center gap-3">
                    <Link
                        href="/superadmin/pertanyaan"
                        class="px-5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white font-bold text-xs sm:text-sm transition-all flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Kelola Butir Pertanyaan</span>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Card Container -->
        <main class="w-full max-w-[1400px] mx-auto -mt-16 px-4 sm:px-6 lg:px-8 relative z-20">
            <div class="bg-white rounded-[2rem] shadow-xl border border-gray-100 overflow-hidden relative transition-all duration-300">
                
                <!-- Bar Statistik & Filter Bagian Atas -->
                <div class="p-6 md:p-8 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    
                    <!-- Kiri: Statistik Ringkas -->
                    <div class="flex items-center gap-4 sm:gap-6 flex-wrap">
                        <div class="flex items-center gap-3 bg-emerald-50/60 border border-emerald-100 px-4 py-2.5 rounded-2xl">
                            <div class="w-9 h-9 rounded-xl bg-[#005B3C] text-white flex items-center justify-center font-extrabold text-sm">
                                {{ sections.length }}
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 font-semibold">Total Bagian</div>
                                <div class="text-sm font-extrabold text-[#005B3C]">Section Kuesioner</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 bg-gray-50 border border-gray-200/80 px-4 py-2.5 rounded-2xl">
                            <div class="w-9 h-9 rounded-xl bg-gray-700 text-white flex items-center justify-center font-extrabold text-sm">
                                {{ totalQuestionsCount }}
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 font-semibold">Total Pertanyaan</div>
                                <div class="text-sm font-extrabold text-gray-800">Butir Tersebar</div>
                            </div>
                        </div>
                    </div>

                    <!-- Kanan: Pencarian & Tombol Tambah Section -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <!-- Kotak Pencarian -->
                        <div class="relative min-w-[260px]">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </span>
                            <input
                                type="text"
                                v-model="searchQuery"
                                placeholder="Cari nama bagian atau kuesioner..."
                                class="w-full pl-9 pr-4 py-2.5 rounded-xl text-xs sm:text-sm border border-gray-200 focus:ring-2 focus:ring-[#005B3C] focus:border-transparent bg-gray-50/50"
                            />
                        </div>

                        <!-- Tombol Tambah Section Baru -->
                        <button
                            type="button"
                            @click="openAddModal"
                            class="px-6 py-2.5 bg-[#0D542B] hover:bg-[#08381c] text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs transition-all flex items-center justify-center gap-2 cursor-pointer shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <span>Tambah Bagian</span>
                        </button>
                    </div>
                </div>

                <!-- Konten Daftar Section -->
                <div class="p-6 md:p-8">
                    
                    <!-- Kondisi Jika Belum Ada Data Section Sama Sekali -->
                    <div v-if="filteredSections.length === 0" class="text-center py-16 px-4 bg-gray-50/50 rounded-2xl border border-dashed border-gray-200">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-50 text-[#0D542B] flex items-center justify-center">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-gray-800">
                            {{ searchQuery ? 'Tidak ada bagian yang cocok dengan pencarian' : 'Belum Ada Bagian Kuesioner' }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
                            {{ searchQuery ? 'Coba gunakan kata kunci pencarian yang lain.' : 'Mulai dengan menambahkan bagian pertama untuk mengelompokkan butir pertanyaan kuesioner Anda.' }}
                        </p>
                        <button
                            v-if="!searchQuery"
                            type="button"
                            @click="openAddModal"
                            class="mt-5 px-5 py-2 rounded-xl bg-[#0D542B] hover:bg-[#08381c] text-white font-bold text-xs shadow-xs transition-all cursor-pointer"
                        >
                            + Tambah Bagian Sekarang
                        </button>
                    </div>

                    <!-- Daftar Kartu Section (Tampilan List Interaktif Bebas Hover Border) -->
                    <div v-else class="space-y-4">
                        <div
                            v-for="(sec, index) in filteredSections"
                            :key="sec.id"
                            class="group bg-white rounded-2xl border border-gray-100 p-5 sm:p-6 shadow-xs hover:shadow-md transition-shadow flex flex-col md:flex-row md:items-center justify-between gap-5 relative overflow-hidden"
                        >
                            <!-- Garis Aksen Kiri Warna Hijau UKDW -->
                            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-[#0D542B]"></div>

                            <!-- Bagian Kiri: Nomor Urut, Judul Section, & Info Kuesioner -->
                            <div class="flex items-start gap-4 sm:gap-5 pl-2">
                                
                                <!-- Badge Urutan Section -->
                                <div class="shrink-0 flex flex-col items-center justify-center w-12 h-12 rounded-2xl bg-[#0D542B] text-white font-extrabold text-base shadow-xs">
                                    <span class="text-[9px] font-bold uppercase tracking-wider text-white/80 -mb-1">Sec</span>
                                    <span>{{ sec.order }}</span>
                                </div>

                                <!-- Judul & Metadata Section -->
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2.5 flex-wrap">
                                        <h3 class="text-base sm:text-lg font-extrabold text-gray-900 group-hover:text-[#005B3C] transition-colors tracking-tight">
                                            {{ sec.title }}
                                        </h3>
                                        <!-- Badge Kuesioner Induk -->
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                                            {{ sec.kuesioner?.title || sec.questionnaire?.title || 'Kuesioner Umum' }} ({{ sec.kuesioner?.year || sec.questionnaire?.year || '-' }})
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-4 text-xs font-medium text-gray-500 flex-wrap">
                                        <!-- Jumlah Butir Pertanyaan -->
                                        <div class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                            <span class="font-bold text-gray-700">{{ sec.subpertanyaans_count ?? sec.questions_count ?? 0 }}</span> Butir Pertanyaan
                                        </div>

                                        <span class="text-gray-300">•</span>

                                        <!-- Tautan Langsung ke Kelola Pertanyaan di Section Ini -->
                                        <Link
                                             :href="`/superadmin/pertanyaan?sec_id=${sec.id}`"
                                             class="text-[#005B3C] hover:underline font-bold inline-flex items-center gap-1"
                                         >
                                             <span>Buka Daftar Soal</span>
                                             <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                         </Link>
                                     </div>
                                 </div>
                             </div>

                             <!-- Bagian Kanan: Kontrol Reorder & Tombol Aksi CRUD -->
                             <div class="flex items-center justify-end gap-2.5 border-t md:border-t-0 pt-4 md:pt-0 border-gray-100 shrink-0">
                                 
                                 <!-- Grup Tombol Reorder Urutan (Naik / Turun) -->
                                 <div class="flex items-center bg-gray-50 p-1 rounded-xl border border-gray-200">
                                     <!-- Tombol Naik (Up) -->
                                     <button
                                         type="button"
                                         title="Pindahkan Posisi ke Atas"
                                         :disabled="index === 0 || isReordering"
                                         @click="handleMoveSection(sec, 'up')"
                                         class="p-1.5 rounded-lg text-gray-600 hover:text-[#005B3C] hover:bg-white disabled:opacity-30 disabled:hover:bg-transparent transition-all cursor-pointer disabled:cursor-not-allowed"
                                     >
                                         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"></path></svg>
                                     </button>

                                     <div class="h-3 w-px bg-gray-200 mx-0.5"></div>

                                     <!-- Tombol Turun (Down) -->
                                     <button
                                         type="button"
                                         title="Pindahkan Posisi ke Bawah"
                                         :disabled="index === filteredSections.length - 1 || isReordering"
                                         @click="handleMoveSection(sec, 'down')"
                                         class="p-1.5 rounded-lg text-gray-600 hover:text-[#005B3C] hover:bg-white disabled:opacity-30 disabled:hover:bg-transparent transition-all cursor-pointer disabled:cursor-not-allowed"
                                     >
                                         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                                     </button>
                                 </div>

                                 <!-- Tombol Sunting (Edit) -->
                                 <button
                                     type="button"
                                     @click="openEditModal(sec)"
                                     title="Sunting Bagian"
                                     class="p-2.5 text-gray-600 hover:text-[#005B3C] hover:bg-emerald-50 rounded-xl border border-gray-200 hover:border-emerald-200 transition-all cursor-pointer"
                                 >
                                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                 </button>

                                 <!-- Tombol Hapus (Delete) -->
                                 <button
                                     type="button"
                                     @click="handleDeleteSection(sec)"
                                     title="Hapus Bagian"
                                     class="p-2.5 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-xl border border-gray-200 hover:border-red-200 transition-all cursor-pointer"
                                 >
                                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                 </button>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>
         </main>

         <!-- Modal Dialog Tambah / Edit Section -->
         <SectionModal
             :show="showSectionModal"
             :isEdit="isEditSection"
             :section="selectedSection"
             :kuesioners="kuesioners && kuesioners.length ? kuesioners : questionnaires"
             :questionnaires="kuesioners && kuesioners.length ? kuesioners : questionnaires"
             :nextOrder="nextAvailableOrder"
             @close="closeSectionModal"
         />
    </div>
</template>
