<!--
  Komponen Modal Tambah / Edit Opsi Jawaban (OptionModal.vue)
  
  Fungsi:
  Modal dialog untuk menambah pilihan opsi baru atau mengedit opsi pada butir pertanyaan kuesioner.
  Mendukung pengaturan teks jawaban, kode opsi, dan logika percabangan (jump_to ke pertanyaan lain).
-->
<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch, ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    // Status visibilitas modal
    show: {
        type: Boolean,
        default: false,
    },
    // Menentukan apakah mode edit atau tambah baru
    isEdit: {
        type: Boolean,
        default: false,
    },
    // Objek pertanyaan induk dari opsi ini
    subpertanyaan: {
        type: Object,
        default: null,
    },
    // Objek opsi yang sedang diedit (bila isEdit = true)
    option: {
        type: Object,
        default: null,
    },
    // Daftar semua pertanyaan tujuan lompatan yang tersedia dari backend
    availableJumpTargets: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'saved']);

// Formulir reaktif Inertia untuk opsi
const form = useForm({
    id: null,
    code: '',
    option_text: '',
    jump_to: '',
});

// ========================================================
// STATE & VARIABEL UNTUK POP-UP JUMP LOGIC (BERTINGKAT & SEARCHABLE)
// ========================================================

/**
 * State visibilitas menu pop-up dropdown alur lompatan
 * @type {import('vue').Ref<boolean>}
 */
const isOpenJumpDropdown = ref(false);

/**
 * Kata kunci pencarian pertanyaan target lompatan
 * @type {import('vue').Ref<string>}
 */
const searchJumpQuery = ref('');

/**
 * Judul section yang saat ini aktif dipilih di kolom kiri dropdown bertingkat
 * @type {import('vue').Ref<string>}
 */
const selectedSectionKey = ref('');

/**
 * Referensi elemen container dropdown untuk mendeteksi klik di luar komponen (click-outside)
 * @type {import('vue').Ref<HTMLElement|null>}
 */
const jumpDropdownRef = ref(null);

/**
 * Referensi input text pencarian agar dapat auto-focus saat dropdown dibuka
 * @type {import('vue').Ref<HTMLInputElement|null>}
 */
const searchInputRef = ref(null);

// Pantau pembukaan modal dan inisialisasi data
watch(() => props.show, (isOpen) => {
    if (isOpen) {
        form.clearErrors();
        isOpenJumpDropdown.value = false;
        searchJumpQuery.value = '';

        if (props.isEdit && props.option) {
            form.id = props.option.id;
            form.code = props.option.kode_opsi || props.option.code || '';
            form.option_text = props.option.option_text;
            form.jump_to = props.option.jump_to || '';
        } else {
            form.reset();
            form.id = null;
            form.code = '';
            form.option_text = '';
            form.jump_to = '';
        }

        // Inisialisasi section aktif sesuai target pertanyaan yang tersimpan
        initSelectedSection();
    } else {
        isOpenJumpDropdown.value = false;
    }
});

/**
 * Menginisialisasi section yang aktif di kolom kiri
 * Jika ada jump_to yang sudah dipilih, langsung buka section tempat pertanyaan tersebut berada.
 */
const initSelectedSection = () => {
    if (form.jump_to) {
        const found = props.availableJumpTargets.find((t) => t.code === form.jump_to);
        if (found && found.section_title) {
            selectedSectionKey.value = found.section_title;
            return;
        }
    }
    // Jika tidak ada target aktif, arahkan ke section pertama yang tersedia
    if (props.availableJumpTargets.length > 0) {
        selectedSectionKey.value = props.availableJumpTargets[0].section_title || 'Pertanyaan Lainnya';
    }
};

// ========================================================
// COMPUTED LOGIC UNTUK PENGELOMPOKAN SECTION & PENCARIAN
// ========================================================

/**
 * Menemukan objek pertanyaan yang saat ini dipilih sebagai target lompatan
 */
const selectedTargetObject = computed(() => {
    if (!form.jump_to) return null;
    return props.availableJumpTargets.find((target) => target.code === form.jump_to) || null;
});

/**
 * Mengelompokkan daftar target lompatan berdasarkan section dan menyaring berdasarkan kata kunci pencarian
 */
const groupedJumpTargets = computed(() => {
    const query = searchJumpQuery.value.trim().toLowerCase();

    // 1. Saring data berdasarkan kode pertanyaan, isi pertanyaan, atau judul section
    const filtered = props.availableJumpTargets.filter((item) => {
        if (!query) return true;
        const matchCode = (item.code || '').toLowerCase().includes(query);
        const matchText = (item.text || '').toLowerCase().includes(query);
        const matchSection = (item.section_title || '').toLowerCase().includes(query);
        return matchCode || matchText || matchSection;
    });

    // 2. Kelompokkan item ke dalam struktur Section
    const groups = {};
    filtered.forEach((item) => {
        const key = item.section_title || 'Pertanyaan Lainnya';
        if (!groups[key]) {
            groups[key] = {
                title: key,
                order: item.section_order ?? 999,
                items: [],
            };
        }
        groups[key].items.push(item);
    });

    // 3. Kembalikan array terurut berdasarkan urutan nomor section
    return Object.values(groups).sort((a, b) => a.order - b.order);
});

/**
 * Objek section yang sedang aktif dipilih di kolom kiri
 */
const activeGroup = computed(() => {
    if (!groupedJumpTargets.value.length) return null;
    const found = groupedJumpTargets.value.find((g) => g.title === selectedSectionKey.value);
    return found || groupedJumpTargets.value[0];
});

/**
 * Daftar pertanyaan yang masuk dalam section yang sedang aktif dipilih di kolom kiri
 */
const activeQuestions = computed(() => {
    return activeGroup.value ? activeGroup.value.items : [];
});

/**
 * Menghitung total pertanyaan hasil filter yang cocok di seluruh section
 */
const totalFilteredTargetsCount = computed(() => {
    return groupedJumpTargets.value.reduce((total, group) => total + group.items.length, 0);
});

// Pantau perubahan hasil filter pencarian agar kolom kanan tidak kosong jika section lama tidak punya hasil
watch(groupedJumpTargets, (newGroups) => {
    if (newGroups.length > 0) {
        const stillExists = newGroups.some((g) => g.title === selectedSectionKey.value);
        if (!stillExists) {
            selectedSectionKey.value = newGroups[0].title;
        }
    }
});

// ========================================================
// FUNGSI INTERAKSI KOMPONEN JUMP LOGIC
// ========================================================

/**
 * Membuka atau menutup menu pop-up dropdown target lompatan
 */
const toggleJumpDropdown = () => {
    isOpenJumpDropdown.value = !isOpenJumpDropdown.value;
    if (isOpenJumpDropdown.value) {
        searchJumpQuery.value = '';
        initSelectedSection();
        nextTick(() => {
            searchInputRef.value?.focus();
        });
    }
};

/**
 * Memilih section di kolom kiri untuk menampilkan pertanyaannya di kolom kanan
 * @param {string} sectionTitle - Judul section yang dipilih
 */
const selectSection = (sectionTitle) => {
    selectedSectionKey.value = sectionTitle;
};

/**
 * Memilih salah satu pertanyaan target lompatan di kolom kanan
 * @param {string} targetCode - Kode pertanyaan (misal: 'F8', 'F17-1')
 */
const selectJumpTarget = (targetCode) => {
    form.jump_to = targetCode;
    isOpenJumpDropdown.value = false;
};

/**
 * Mereset lompatan kembali ke alur normal (Lanjut ke pertanyaan berikutnya)
 */
const clearJumpTarget = () => {
    form.jump_to = '';
    isOpenJumpDropdown.value = false;
};

/**
 * Handler penutupan pop-up otomatis ketika pengguna mengklik di luar area dropdown
 * @param {MouseEvent} event
 */
const handleClickOutside = (event) => {
    if (jumpDropdownRef.value && !jumpDropdownRef.value.contains(event.target)) {
        isOpenJumpDropdown.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});

const handleClose = () => {
    emit('close');
};

const handleSubmit = () => {
    if (props.isEdit) {
        form.put(`/superadmin/pertanyaan/options/${form.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Pilihan opsi jawaban berhasil diperbarui.',
                    icon: 'success',
                    confirmButtonColor: '#005B3C',
                    confirmButtonText: 'Selesai',
                });
                emit('saved');
                emit('close');
            },
            onError: (errors) => {
                let errorList = '<ul class="text-left text-xs list-disc list-inside space-y-1 mt-2">';
                for (let key in errors) {
                    errorList += `<li>${errors[key]}</li>`;
                }
                errorList += '</ul>';

                Swal.fire({
                    title: 'Gagal Memperbarui!',
                    html: '<p class="text-sm">Periksa kembali data opsi:</p>' + errorList,
                    icon: 'error',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Perbaiki',
                });
            },
        });
    } else {
        form.post(`/superadmin/pertanyaan/${props.subpertanyaan.id}/options`, {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Pilihan opsi jawaban berhasil ditambahkan.',
                    icon: 'success',
                    confirmButtonColor: '#005B3C',
                    confirmButtonText: 'Selesai',
                });
                emit('saved');
                emit('close');
            },
            onError: (errors) => {
                let errorList = '<ul class="text-left text-xs list-disc list-inside space-y-1 mt-2">';
                for (let key in errors) {
                    errorList += `<li>${errors[key]}</li>`;
                }
                errorList += '</ul>';

                Swal.fire({
                    title: 'Gagal Menyimpan!',
                    html: '<p class="text-sm">Ada kesalahan pada input:</p>' + errorList,
                    icon: 'error',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Perbaiki',
                });
            },
        });
    }
};
</script>

<template>
    <div 
        v-if="show" 
        class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 sm:p-6"
    >
        <!-- Backdrop Blur -->
        <div 
            class="fixed inset-0 bg-black/50 backdrop-blur-xs transition-opacity" 
            @click="handleClose"
        ></div>

        <!-- Modal Card -->
        <div class="relative bg-white rounded-3xl shadow-2xl max-w-2xl w-full border border-gray-100 z-10 transform transition-all">
            
            <!-- Header Modal -->
            <div class="p-5 bg-gradient-to-r from-emerald-800 to-[#005B3C] text-white flex items-center justify-between rounded-t-3xl">
                <div>
                    <h3 class="text-base font-black tracking-tight">
                        {{ isEdit ? 'Edit Pilihan Opsi' : 'Tambah Opsi Jawaban' }}
                    </h3>
                    <p class="text-xs text-emerald-100/90 font-medium mt-0.5">
                        Pertanyaan: <strong class="text-yellow-300 font-mono">{{ subpertanyaan?.kode_pertanyaan }}</strong>
                    </p>
                </div>
                <button 
                    type="button" 
                    @click="handleClose"
                    class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors cursor-pointer"
                >
                    ✕
                </button>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
                <!-- Teks Opsi Jawaban -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Teks Pilihan Jawaban <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        v-model="form.option_text"
                        required
                        placeholder="Contoh: Ya, Tidak, Sangat Sesuai, dsb."
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[#005B3C] focus:border-transparent text-sm bg-gray-50/50"
                    />
                    <p v-if="form.errors.option_text" class="text-xs text-red-500 mt-1 font-semibold">
                        {{ form.errors.option_text }}
                    </p>
                </div>

                <!-- Kode Opsi Jawaban (Opsional) -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Kode Opsi (Opsional)
                    </label>
                    <input
                        type="text"
                        v-model="form.code"
                        placeholder="Kosongkan untuk otomatis (misal: F3-01)"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[#005B3C] focus:border-transparent text-sm font-mono bg-gray-50/50"
                    />
                    <p class="text-[11px] text-gray-400 mt-1">
                        * Jika dikosongkan, sistem otomatis memberikan kode urutan sesuai kode pertanyaan.
                    </p>
                </div>

                <!-- Logika Lompatan Alur (Jump Logic / Branching) - Dropdown Bertingkat (Cascading 2 Kolom) & Searchable -->
                <div class="relative" ref="jumpDropdownRef">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                            Alur Lompatan / Percabangan (Jump Logic)
                        </label>
                        <span v-if="form.jump_to" class="text-[11px] font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                            Aktif: Melompat ke <strong class="font-mono font-bold">{{ form.jump_to }}</strong>
                        </span>
                    </div>

                    <!-- Trigger Button Dropdown -->
                    <div 
                        @click="toggleJumpDropdown"
                        class="w-full min-h-[46px] px-3.5 py-2 rounded-xl border border-gray-300 bg-gray-50/50 hover:bg-gray-100/70 focus-within:ring-2 focus-within:ring-[#005B3C] focus-within:border-transparent transition-all cursor-pointer flex items-center justify-between gap-2 shadow-xs"
                    >
                        <!-- Tampilan Jika Memilih Default -->
                        <div v-if="!form.jump_to" class="flex items-center gap-2 text-sm text-gray-600 truncate">
                            <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                            <span class="font-medium">Lanjut ke pertanyaan berikutnya (Default)</span>
                        </div>

                        <!-- Tampilan Jika Memilih Target Lompatan Khusus -->
                        <div v-else class="flex items-center gap-2.5 min-w-0 truncate">
                            <span class="px-2 py-0.5 text-xs font-mono font-bold rounded-md bg-[#005B3C] text-white shadow-xs shrink-0">
                                {{ selectedTargetObject?.code || form.jump_to }}
                            </span>
                            <span class="text-xs text-gray-800 font-medium truncate">
                                {{ selectedTargetObject?.text || 'Pertanyaan Terpilih' }}
                            </span>
                        </div>

                        <!-- Ikon Aksi (Clear dan Chevron) -->
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button
                                v-if="form.jump_to"
                                type="button"
                                @click.stop="clearJumpTarget"
                                title="Kembalikan ke alur default"
                                class="w-6 h-6 rounded-full hover:bg-gray-200 text-gray-400 hover:text-gray-700 flex items-center justify-center transition-colors text-xs cursor-pointer"
                            >
                                ✕
                            </button>
                            <svg 
                                class="w-4 h-4 text-gray-400 transition-transform duration-200"
                                :class="{ 'rotate-180 text-[#005B3C]': isOpenJumpDropdown }"
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Pop-up Menu Dropdown Bertingkat (Cascading 2 Kolom: Kiri = Section, Kanan = Pertanyaan) -->
                    <div 
                        v-if="isOpenJumpDropdown"
                        class="absolute left-0 right-0 z-30 mt-1.5 bg-white border border-gray-200 rounded-2xl shadow-2xl overflow-hidden animate-in fade-in slide-in-from-top-2 duration-150"
                    >
                        <!-- Search Box & Default Action Header -->
                        <div class="p-2.5 bg-gray-50/90 border-b border-gray-200/90 flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            <!-- Input Pencarian -->
                            <div class="relative flex-1">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input
                                    ref="searchInputRef"
                                    type="text"
                                    v-model="searchJumpQuery"
                                    placeholder="Cari kode (misal F8) atau kata kunci pertanyaan..."
                                    class="w-full pl-9 pr-8 py-2 text-xs rounded-xl border border-gray-300 focus:ring-2 focus:ring-[#005B3C] focus:border-transparent bg-white shadow-inner"
                                />
                                <button
                                    v-if="searchJumpQuery"
                                    type="button"
                                    @click="searchJumpQuery = ''"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs"
                                >
                                    ✕
                                </button>
                            </div>

                            <!-- Tombol Pintas Alur Default -->
                            <button
                                type="button"
                                @click="clearJumpTarget"
                                class="px-3 py-1.5 rounded-xl border text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors shrink-0 cursor-pointer"
                                :class="!form.jump_to 
                                    ? 'bg-[#005B3C] text-white border-[#005B3C] shadow-xs' 
                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'"
                            >
                                <span class="w-2 h-2 rounded-full" :class="!form.jump_to ? 'bg-white' : 'bg-gray-400'"></span>
                                <span>Alur Normal (Default)</span>
                                <span v-if="!form.jump_to" class="text-[10px] bg-emerald-900/40 px-1 py-0.2 rounded font-mono">✓</span>
                            </button>
                        </div>

                        <!-- Konten Bertingkat 2 Kolom: Kolom Kiri = Section, Kolom Kanan = Pertanyaan -->
                        <div class="flex flex-col sm:flex-row border-b border-gray-200">
                            
                            <!-- ============================================== -->
                            <!-- KOLOM KIRI: DAFTAR SECTION                    -->
                            <!-- ============================================== -->
                            <div class="w-full sm:w-2/5 border-b sm:border-b-0 sm:border-r border-gray-200 bg-gray-50/70 flex flex-col">
                                <!-- Header Kolom Kiri -->
                                <div class="px-3 py-2 bg-gray-100/80 text-gray-500 font-bold text-[10px] tracking-wider uppercase border-b border-gray-200 flex items-center justify-between">
                                    <span>1. PILIH SECTION</span>
                                    <span class="text-[10px] font-semibold text-gray-500 bg-white px-1.5 py-0.5 rounded border border-gray-200">
                                        {{ groupedJumpTargets.length }} section
                                    </span>
                                </div>

                                <!-- List Item Section -->
                                <div class="max-h-[260px] overflow-y-auto divide-y divide-gray-100 text-xs">
                                    <template v-if="groupedJumpTargets.length > 0">
                                        <div
                                            v-for="group in groupedJumpTargets"
                                            :key="group.title"
                                            @click="selectSection(group.title)"
                                            class="px-3 py-2.5 cursor-pointer flex items-center justify-between gap-2 transition-all group"
                                            :class="selectedSectionKey === group.title
                                                ? 'bg-[#005B3C] text-white font-semibold shadow-inner'
                                                : 'text-gray-700 hover:bg-emerald-50/80 hover:text-emerald-900'"
                                        >
                                            <div class="min-w-0 flex-1 truncate">
                                                <div class="truncate text-xs leading-tight">
                                                    {{ group.title }}
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-1.5 shrink-0">
                                                <span 
                                                    class="text-[10px] px-1.5 py-0.5 rounded-full font-mono font-medium"
                                                    :class="selectedSectionKey === group.title
                                                        ? 'bg-emerald-900/60 text-emerald-100'
                                                        : 'bg-gray-200 text-gray-600'"
                                                >
                                                    {{ group.items.length }}
                                                </span>
                                                <!-- Panah Indikator Bertingkat -->
                                                <svg 
                                                    class="w-3.5 h-3.5"
                                                    :class="selectedSectionKey === group.title ? 'text-white' : 'text-gray-400 group-hover:text-emerald-700'"
                                                    fill="none" 
                                                    stroke="currentColor" 
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </div>
                                        </div>
                                    </template>

                                    <div v-else class="p-6 text-center text-gray-400 text-xs">
                                        Tidak ada section yang cocok.
                                    </div>
                                </div>
                            </div>

                            <!-- ============================================== -->
                            <!-- KOLOM KANAN: DAFTAR PERTANYAAN (MAKS 5 LIHAT, SCROLLABLE) -->
                            <!-- ============================================== -->
                            <div class="w-full sm:w-3/5 bg-white flex flex-col">
                                <!-- Header Kolom Kanan -->
                                <div class="px-3 py-2 bg-gray-50 text-gray-600 font-bold text-[10px] tracking-wider uppercase border-b border-gray-200 flex items-center justify-between">
                                    <div class="flex items-center gap-1 truncate">
                                        <span>2. PERTANYAAN:</span>
                                        <span class="text-[#005B3C] truncate font-extrabold">{{ activeGroup?.title || 'Pilih Section' }}</span>
                                    </div>
                                    <span class="text-[10px] text-gray-400 font-medium shrink-0">
                                        {{ activeQuestions.length }} butir
                                    </span>
                                </div>

                                <!-- Container Pertanyaan: Dibatasi dalam 1 tampilan terlihat 5 pertanyaan, discroll ada lagi -->
                                <div class="h-[260px] max-h-[260px] overflow-y-auto divide-y divide-gray-100 text-xs">
                                    <template v-if="activeQuestions.length > 0">
                                        <div
                                            v-for="target in activeQuestions"
                                            :key="target.code"
                                            @click="selectJumpTarget(target.code)"
                                            class="min-h-[52px] px-3.5 py-2 hover:bg-emerald-50/70 transition-colors cursor-pointer flex items-center justify-between gap-2.5 group"
                                            :class="{ 'bg-emerald-50 font-bold': form.jump_to === target.code }"
                                        >
                                            <!-- Kolom Kode & Teks Pertanyaan -->
                                            <div class="flex items-start gap-2 min-w-0 flex-1">
                                                <span 
                                                    class="px-2 py-0.5 text-xs font-mono font-bold rounded bg-emerald-50 text-[#005B3C] border border-emerald-200 shrink-0 group-hover:bg-[#005B3C] group-hover:text-white transition-colors"
                                                    :class="{ '!bg-[#005B3C] !text-white': form.jump_to === target.code }"
                                                >
                                                    {{ target.code }}
                                                </span>
                                                <span class="text-xs text-gray-700 group-hover:text-gray-900 leading-snug line-clamp-2">
                                                    {{ target.text }}
                                                </span>
                                            </div>

                                            <!-- Indikator Terpilih -->
                                            <div class="shrink-0 pl-1">
                                                <span 
                                                    v-if="form.jump_to === target.code" 
                                                    class="inline-flex items-center text-[11px] font-bold text-[#005B3C]"
                                                >
                                                    ✓ Terpilih
                                                </span>
                                                <span 
                                                    v-else 
                                                    class="opacity-0 group-hover:opacity-100 text-[11px] text-emerald-700 font-medium transition-opacity"
                                                >
                                                    Pilih
                                                </span>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- State Kosong Jika Tidak Ada Pertanyaan di Section Terpilih -->
                                    <div v-else class="h-full flex flex-col items-center justify-center p-6 text-center text-gray-400">
                                        <svg class="w-7 h-7 mb-1.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.5L20 8.5V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-xs font-medium text-gray-500">Tidak ada pertanyaan pada section ini.</p>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Pop-up Footer Info & Navigasi -->
                        <div class="p-2.5 bg-gray-50 text-[11px] text-gray-500 flex items-center justify-between px-4">
                            <span>* Klik section di kiri, lalu pilih pertanyaan target di kolom kanan.</span>
                            <button 
                                type="button" 
                                @click="isOpenJumpDropdown = false" 
                                class="text-gray-600 hover:text-gray-900 font-semibold px-2 py-1 rounded-md hover:bg-gray-200 transition-colors"
                            >
                                Selesai
                            </button>
                        </div>
                    </div>

                    <p class="text-[11px] text-gray-400 mt-1">
                        * Jika alumni memilih opsi ini, kuesioner akan melompat langsung ke pertanyaan target yang dipilih.
                    </p>
                </div>

                <!-- Footer Tombol Aksi -->
                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-2.5">
                    <button
                        type="button"
                        @click="handleClose"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 border border-gray-300 transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-[#005B3C] hover:bg-emerald-800 shadow-md shadow-emerald-900/20 disabled:opacity-50 transition-all cursor-pointer flex items-center gap-1.5"
                    >
                        <span v-if="form.processing">Menyimpan...</span>
                        <span v-else>{{ isEdit ? 'Simpan Opsi' : 'Tambah Opsi' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
