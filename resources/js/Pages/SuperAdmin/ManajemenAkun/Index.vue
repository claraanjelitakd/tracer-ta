<!--
  Halaman Manajemen Akun Pengguna Terpadu (Super Admin)
  File: resources/js/Pages/SuperAdmin/ManajemenAkun/Index.vue
  
  Fitur:
  1. Sunting/Edit Data Pengguna (Email, Nama, Username, Role, Prodi/Fakultas).
  2. Reset Password 1-Klik Otomatis ke Tanggal Lahir (ddmmyyyy) + Kirim Email Konfirmasi ke Email Terdaftar.
  3. Desain Bersih Enterprise tanpa highlight background aneh pada sidebar.
-->
<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Swal from 'sweetalert2';
import Sidebar from '../Components/Sidebar.vue';

const props = defineProps({
    user: Object,
    users: Object,
    prodis: Array,
    fakultas: Array,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters.search || '');
const selectedRole = ref(props.filters.role || 'all');
const selectedProdi = ref(props.filters.prodi_id || '');
const selectedFakultas = ref(props.filters.fakultas_id || '');

const applyFilters = () => {
    router.get('/superadmin/manajemen-akun', {
        search: search.value || undefined,
        role: selectedRole.value !== 'all' ? selectedRole.value : undefined,
        prodi_id: selectedProdi.value || undefined,
        fakultas_id: selectedFakultas.value || undefined,
    }, { preserveState: true, replace: true });
};

// Modal Edit User Data
const openEditUserModal = (u) => {
    const currentFakultasId = u.fakultas_id || u.prodi?.fakultas_id || u.prodi?.fakultas?.id || '';

    const prodiOptions = props.prodis.map(p => 
        `<option value="${p.id}" ${p.id == u.prodi_id ? 'selected' : ''}>${p.nama_prodi}</option>`
    ).join('');

    const fakultasOptions = props.fakultas.map(f => 
        `<option value="${f.id}" ${f.id == currentFakultasId ? 'selected' : ''}>${f.nama_fakultas}</option>`
    ).join('');

    Swal.fire({
        title: `<span class="text-base font-bold text-slate-900">Sunting Data Akun Pengguna</span>`,
        html: `
            <div class="text-left text-xs space-y-3 pt-1 text-slate-800">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                    <input id="swal-edit-name" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs" value="${(u.name || '').replace(/"/g, '&quot;')}" />
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Email Terdaftar (Pribadi) *</label>
                        <input id="swal-edit-email" type="email" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs" value="${u.email || ''}" />
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Username / NIM *</label>
                        <input id="swal-edit-username" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs" value="${u.username || ''}" />
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Peran (Role)</label>
                        <select id="swal-edit-role" class="w-full px-2 py-2 border border-slate-300 rounded-lg text-xs bg-white">
                            <option value="superadmin" ${u.role === 'superadmin' ? 'selected' : ''}>Super Admin</option>
                            <option value="admin_biro3" ${u.role === 'admin_biro3' ? 'selected' : ''}>Admin Biro 3</option>
                            <option value="admin_fakultas" ${u.role === 'admin_fakultas' ? 'selected' : ''}>Admin Fakultas</option>
                            <option value="admin_prodi" ${u.role === 'admin_prodi' ? 'selected' : ''}>Admin Prodi</option>
                            <option value="alumni" ${u.role === 'alumni' ? 'selected' : ''}>Alumni</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Program Studi</label>
                        <select id="swal-edit-prodi" class="w-full px-2 py-2 border border-slate-300 rounded-lg text-xs bg-white">
                            <option value="">-- Pilih Prodi --</option>
                            ${prodiOptions}
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Fakultas</label>
                        <select id="swal-edit-fakultas" class="w-full px-2 py-2 border border-slate-300 rounded-lg text-xs bg-white">
                            <option value="">-- Pilih Fakultas --</option>
                            ${fakultasOptions}
                        </select>
                    </div>
                </div>
            </div>
        `,
        width: '560px',
        showCancelButton: true,
        confirmButtonText: 'Simpan Perubahan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#0D542B',
        cancelButtonColor: '#6B7280',
        didOpen: () => {
            const prodiSelect = document.getElementById('swal-edit-prodi');
            const fakultasSelect = document.getElementById('swal-edit-fakultas');
            if (prodiSelect && fakultasSelect) {
                prodiSelect.addEventListener('change', (e) => {
                    const selectedProdiId = e.target.value;
                    const foundProdi = props.prodis.find(p => p.id == selectedProdiId);
                    if (foundProdi && foundProdi.fakultas_id) {
                        fakultasSelect.value = foundProdi.fakultas_id;
                    }
                });
            }
        },
        preConfirm: () => {
            const name = document.getElementById('swal-edit-name')?.value?.trim();
            const email = document.getElementById('swal-edit-email')?.value?.trim();
            const username = document.getElementById('swal-edit-username')?.value?.trim();

            if (!name || !email || !username) {
                Swal.showValidationMessage('Nama, Email, dan Username wajib diisi.');
                return false;
            }

            return {
                name,
                email,
                username,
                role: document.getElementById('swal-edit-role')?.value,
                prodi_id: document.getElementById('swal-edit-prodi')?.value || null,
                fakultas_id: document.getElementById('swal-edit-fakultas')?.value || null,
            };
        }
    }).then((res) => {
        if (res.isConfirmed && res.value) {
            router.put(`/superadmin/manajemen-akun/${u.id}`, res.value, {
                onSuccess: () => {
                    Swal.fire('Berhasil!', 'Data akun pengguna telah diperbarui.', 'success');
                }
            });
        }
    });
};

// Reset 1-Tombol: Reset ke Tanggal Lahir (ddmmyyyy) + Kirim Notifikasi Pemulihan ke Email Terdaftar
const handleResetToDefaultAndNotify = (userData) => {
    const passwordHint = userData.default_dob_password ? `Format Tanggal Lahir (${userData.default_dob_password})` : 'Ukdw123456';

    Swal.fire({
        title: 'Reset Password & Kirim Konfirmasi Email?',
        html: `
            <div class="text-xs text-left space-y-2">
                <p>Password akun <strong>${userData.name}</strong> akan otomatis direset ke <strong>${passwordHint}</strong> dan status akun diwajibkan ganti password awal (default change password).</p>
                <p class="pt-1">Notifikasi konfirmasi hasil reset akan otomatis dikirimkan ke email terdaftar pengguna:</p>
                <div class="font-mono font-bold text-[#0D542B] bg-emerald-50 p-2 rounded border border-emerald-200">${userData.email}</div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Reset & Kirim Email',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#0D542B',
        cancelButtonColor: '#6B7280',
    }).then((res) => {
        if (res.isConfirmed) {
            router.post(`/superadmin/manajemen-akun/${userData.id}/reset-default-notify`, {}, {
                onSuccess: () => {
                    Swal.fire('Berhasil Di-reset!', `Password ${userData.name} telah direset ke tanggal lahir dan konfirmasi berhasil dikirim ke ${userData.email}.`, 'success');
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Manajemen Akun - Super Admin UKDW" />

    <div class="min-h-screen bg-slate-50 flex font-sans">
        <Sidebar :user="user" />

        <div class="flex-1 flex flex-col min-w-0 lg:pl-72">
            <!-- Header Top Bar -->
            <div class="bg-white border-b border-gray-200 px-6 py-5">
                <div class="w-full flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 text-xs text-gray-500 font-medium mb-1">
                            <Link href="/superadmin/dashboard" class="hover:underline hover:text-[#0D542B]">Dashboard</Link>
                            <span>/</span>
                            <span class="text-gray-800 font-semibold">Super Admin</span>
                            <span>/</span>
                            <span class="text-[#0D542B] font-bold">Manajemen Akun</span>
                        </div>
                        <h1 class="text-xl font-bold text-gray-900">
                            Manajemen Akun & Pemulihan Password Tanggal Lahir
                        </h1>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Pengelolaan pengguna terpadu, sunting email/biodata, dan 1-Klik reset password ke tanggal lahir dengan konfirmasi email.
                        </p>
                    </div>

                    <!-- Widget Statistik Dual Sesuai Foto Pengguna -->
                    <div class="bg-slate-50/80 border border-slate-200/80 rounded-2xl px-8 py-3.5 flex items-center gap-8 text-center shadow-2xs">
                        <div>
                            <div class="text-2xl font-black text-[#0D542B] leading-none mb-1.5">{{ stats.total_admin_fakultas + stats.total_admin_prodi }}</div>
                            <div class="text-[11px] font-bold text-slate-600 tracking-wide uppercase">ADMIN FAKULTAS & PRODI</div>
                        </div>
                        <div class="w-px h-10 bg-slate-300/80"></div>
                        <div>
                            <div class="text-2xl font-black text-[#D97706] leading-none mb-1.5">{{ stats.total_alumni }}</div>
                            <div class="text-[11px] font-bold text-slate-600 tracking-wide uppercase">MAHASISWA & ALUMNI</div>
                        </div>
                    </div>
                </div>
            </div>

            <main class="w-full p-6 space-y-4">
                <!-- Filter bar -->
                <div class="bg-white p-4 rounded-lg border border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs shadow-2xs">
                    <div class="flex items-center gap-3 flex-wrap flex-1">
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Cari nama, email, atau username..."
                            class="px-3 py-2 border border-gray-300 rounded-lg text-xs w-64 focus:outline-none focus:ring-1 focus:ring-[#0D542B]"
                        />

                        <!-- Filter Role -->
                        <select v-model="selectedRole" @change="applyFilters" class="px-3 py-2 border border-gray-300 rounded-lg text-xs bg-white">
                            <option value="all">Semua Peran (Role)</option>
                            <option value="superadmin">Super Admin</option>
                            <option value="admin_biro3">Admin Biro 3</option>
                            <option value="admin_fakultas">Admin Fakultas</option>
                            <option value="admin_prodi">Admin Prodi</option>
                            <option value="alumni">Alumni</option>
                        </select>

                        <!-- Filter Prodi -->
                        <select v-model="selectedProdi" @change="applyFilters" class="px-3 py-2 border border-gray-300 rounded-lg text-xs bg-white">
                            <option value="">Semua Program Studi</option>
                            <option v-for="p in prodis" :key="p.id" :value="p.id">{{ p.nama_prodi }}</option>
                        </select>
                    </div>
                </div>

                <!-- Table Pengguna -->
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-2xs">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-100 border-b border-gray-200 text-slate-700 font-bold uppercase text-[11px]">
                                <th class="py-3 px-4">Nama Pengguna</th>
                                <th class="py-3 px-4">Email Terdaftar (Pribadi)</th>
                                <th class="py-3 px-4">Role / Peran</th>
                                <th class="py-3 px-4">Program Studi / Fakultas</th>
                                <th class="py-3 px-4 text-center">Aksi Manajemen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="u in users.data" :key="u.id" class="hover:bg-slate-50/80">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ u.name }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono">@{{ u.username }}</div>
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-800">
                                    {{ u.email }}
                                </td>
                                <td class="py-3 px-4">
                                    <span v-if="u.role === 'superadmin'" class="px-2.5 py-1 rounded font-bold text-[11px] text-[#0D542B]">Super Admin</span>
                                    <span v-else-if="u.role === 'admin_fakultas'" class="px-2.5 py-1 rounded font-bold text-[11px] text-[#0D542B]">Admin Fakultas</span>
                                    <span v-else-if="u.role === 'admin_prodi'" class="px-2.5 py-1 rounded font-bold text-[11px] text-[#0D542B]">Admin Prodi</span>
                                    <span v-else-if="u.role === 'admin_biro3'" class="px-2.5 py-1 rounded font-bold text-[11px] text-[#0D542B]">Admin Biro 3</span>
                                    <span v-else-if="u.role === 'alumni'" class="px-2.5 py-1 rounded font-bold text-[11px] text-slate-700">Alumni</span>
                                    <span v-else class="px-2.5 py-1 rounded font-bold text-[11px] text-slate-700">{{ u.role }}</span>
                                </td>
                                <td class="py-3 px-4 text-slate-700">
                                    <template v-if="u.role === 'alumni' || u.role === 'admin_prodi'">
                                        <div class="font-medium">{{ u.prodi?.nama_prodi || '-' }}</div>
                                        <div v-if="u.prodi?.fakultas" class="text-[11px] text-slate-500 mt-0.5">{{ u.prodi.fakultas.nama_fakultas }}</div>
                                    </template>
                                    <template v-else>
                                        {{ u.fakultas?.nama_fakultas || u.prodi?.nama_prodi || '-' }}
                                    </template>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Tombol Edit User -->
                                        <button
                                            @click="openEditUserModal(u)"
                                            class="px-2.5 py-1 bg-slate-100 text-slate-700 font-bold rounded text-[11px] hover:bg-slate-200 border border-slate-300 cursor-pointer"
                                            title="Sunting email, nama, username, atau role pengguna"
                                        >
                                            Sunting Data
                                        </button>
                                        <!-- Tombol Reset 1-Klik ke Tanggal Lahir + Email Konfirmasi -->
                                        <button
                                            @click="handleResetToDefaultAndNotify(u)"
                                            class="px-2.5 py-1 bg-[#0D542B] text-white font-semibold rounded text-[11px] hover:bg-[#08381c] cursor-pointer"
                                            title="Reset password otomatis ke tanggal lahir (ddmmyyyy) & kirim email konfirmasi ke email terdaftar"
                                        >
                                            Reset Password & Email
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
</template>
