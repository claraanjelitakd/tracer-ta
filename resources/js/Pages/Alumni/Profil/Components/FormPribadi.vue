<!--
  Komponen Anak (Child Component): Form Data Pribadi
  File: resources/js/Pages/Alumni/Profil/Components/FormPribadi.vue
  
  DIPANGGIL OLEH (Parent Component):
  - resources/js/Pages/Alumni/Profil/Index.vue
  
  SUMBER DATA DARI BACKEND:
  - Controller: App\Http\Controllers\Alumni\Profil\ProfilController.php
-->
<script setup>
import { computed } from 'vue';

/**
 * ====================================================================
 * MENERIMA DATA (PROPS) DARI PARENT (Index.vue)
 * ====================================================================
 */
const props = defineProps({
    form: Object,
    provinces: Array,
    kabupatens: Array,
    negaras: Array,
});

// Helper validasi email
const isValidEmail = (val) => {
    if (!val) return false;
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(val).trim());
};

// Helper validasi NPWP (15 atau 16 digit per UU HPP & PMK 112/PMK.03/2022)
const isValidNpwp = (val) => {
    if (!val) return false;
    const clean = String(val).replace(/[^0-9]/g, '');
    return clean.length === 15 || clean.length === 16;
};

// Helper validasi No HP
const isValidPhone = (val) => {
    if (!val) return false;
    const clean = String(val).replace(/[^0-9+]/g, '');
    return clean.length >= 10 && clean.length <= 15;
};

// Handler input NPWP hanya angka max 16 digit
const handleNpwpInput = (e) => {
    const raw = e.target.value.replace(/[^0-9]/g, '').slice(0, 16);
    props.form.npwp = raw;
};

// Salin NIK sebagai NPWP 16 digit
const copyNikToNpwp = () => {
    if (props.form.nik) {
        props.form.npwp = String(props.form.nik).replace(/[^0-9]/g, '').slice(0, 16);
    }
};

// Handler input telepon hanya angka & tanda +
const handlePhoneInput = (e) => {
    props.form.nomor_telepon = e.target.value.replace(/[^0-9+]/g, '').slice(0, 15);
};

const lockedInputClass = "block w-full border border-gray-200 bg-gray-100 text-gray-700 rounded-xl px-4 py-3 text-sm font-medium cursor-not-allowed select-none";
</script>

<template>
    <div class="space-y-8">
        
        <!-- ================================================================= -->
        <!-- BAGIAN 1: IDENTITAS DIRI RESMI (DARI DATA AKADEMIK)              -->
        <!-- ================================================================= -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-xs border border-gray-100">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 flex-wrap gap-2">
                <h2 class="text-lg sm:text-xl font-black text-gray-900 flex items-center gap-2">
                    <span class="w-2.5 h-6 bg-[#005B3C] rounded-full inline-block"></span>
                    Identitas Diri Resmi
                </h2>
                <span class="text-xs font-semibold px-2.5 py-1 bg-gray-100 text-gray-600 rounded-lg border border-gray-200">
                    Data Induk Akademik (Terkunci Otomatis)
                </span>
            </div>

            <p class="text-xs text-gray-500 mb-5 leading-relaxed bg-slate-50 border border-slate-200/80 p-3.5 rounded-xl">
                Identitas kependudukan dan akademik (Nama, Tempat/Tanggal Lahir, Jenis Kelamin, Agama, Golongan Darah, Dokumen) terhubung langsung dari Pangkalan Data Akademik resmi kampus dan dikunci secara permanen untuk menjamin validitas serta mencegah duplikasi data.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- NIM (Terkunci) -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Nomor Induk Mahasiswa (NIM)
                    </label>
                    <input type="text" :value="form.nim" disabled :class="lockedInputClass" />
                </div>

                <!-- Nama Lengkap (Terkunci dari Data Akademik) -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5 flex items-center justify-between">
                        <span>Nama Lengkap (Sesuai Ijazah)</span>
                        <span class="text-[11px] text-gray-400 font-normal">Data Akademik</span>
                    </label>
                    <input 
                        type="text" 
                        :value="form.nama" 
                        disabled 
                        :class="lockedInputClass" 
                        title="Nama lengkap sesuai data akademik resmi kampus" 
                    />
                </div>

                <!-- NIK (Terkunci dari Data Akademik) -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5 flex items-center justify-between">
                        <span>Nomor Induk Kependudukan (NIK)</span>
                        <span class="text-[11px] text-gray-400 font-normal">Data Akademik</span>
                    </label>
                    <input type="text" :value="form.nik" disabled :class="lockedInputClass" placeholder="16 Digit NIK" title="NIK bersifat permanen dan terdaftar resmi di pangkalan data kampus" />
                </div>

                <!-- NPWP (16 Digit NIK / 15 Digit PMK 112) -->
                <div>
                    <div class="flex items-center justify-between mb-1.5 flex-wrap gap-1">
                        <label class="block text-xs sm:text-sm font-bold text-gray-700">
                            Nomor Pokok Wajib Pajak (NPWP) <span class="text-rose-500 font-bold">*</span>
                        </label>
                        <button 
                            v-if="form.nik && form.npwp !== form.nik"
                            type="button" 
                            @click="copyNikToNpwp" 
                            class="text-[11px] font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 px-2.5 py-0.5 rounded-lg border border-gray-300 transition-colors cursor-pointer"
                            title="Gunakan 16 Digit NIK sebagai NPWP sesuai PMK 112/2022"
                        >
                            Gunakan NIK (16 Digit)
                        </button>
                    </div>
                    <input 
                        type="text" 
                        :value="form.npwp" 
                        @input="handleNpwpInput"
                        maxlength="16"
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-mono font-bold transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="isValidNpwp(form.npwp) ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                        placeholder="16 Digit NIK / 15 Digit NPWP" 
                    />
                    <p class="text-[11px] text-gray-400 mt-1 font-medium">
                        Sesuai UU HPP & PMK No. 112/PMK.03/2022, Wajib Pajak Orang Pribadi menggunakan 16 digit NIK sebagai format NPWP terbaru.
                    </p>
                </div>
                
                <!-- Tempat Lahir (Terkunci dari Data Akademik) -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5 flex items-center justify-between">
                        <span>Tempat Lahir</span>
                        <span class="text-[11px] text-gray-400 font-normal">Data Akademik</span>
                    </label>
                    <input 
                        type="text" 
                        :value="form.tempat_lahir" 
                        disabled 
                        :class="lockedInputClass" 
                        placeholder="Kota / Kabupaten Kelahiran" 
                    />
                </div>

                <!-- Tanggal Lahir (Terkunci dari Data Akademik) -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5 flex items-center justify-between">
                        <span>Tanggal Lahir</span>
                        <span class="text-[11px] text-gray-400 font-normal">Data Akademik</span>
                    </label>
                    <input 
                        type="text" 
                        :value="form.tanggal_lahir" 
                        disabled 
                        :class="lockedInputClass" 
                    />
                </div>
                
                <!-- Jenis Kelamin (Terkunci dari Data Akademik) -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5 flex items-center justify-between">
                        <span>Jenis Kelamin</span>
                        <span class="text-[11px] text-gray-400 font-normal">Data Akademik</span>
                    </label>
                    <input 
                        type="text" 
                        :value="form.jenis_kelamin" 
                        disabled 
                        :class="lockedInputClass" 
                    />
                </div>

                <!-- Agama (Terkunci dari Data Akademik) -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5 flex items-center justify-between">
                        <span>Agama</span>
                        <span class="text-[11px] text-gray-400 font-normal">Data Akademik</span>
                    </label>
                    <input 
                        type="text" 
                        :value="form.agama" 
                        disabled 
                        :class="lockedInputClass" 
                    />
                </div>

                <!-- Golongan Darah (Terkunci dari Data Akademik) -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5 flex items-center justify-between">
                        <span>Golongan Darah</span>
                        <span class="text-[11px] text-gray-400 font-normal">Data Akademik</span>
                    </label>
                    <input 
                        type="text" 
                        :value="form.golongan_darah || '-'" 
                        disabled 
                        :class="lockedInputClass" 
                    />
                </div>

                <!-- Kewarganegaraan (Terkunci dari Data Akademik) -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5 flex items-center justify-between">
                        <span>Kewarganegaraan</span>
                        <span class="text-[11px] text-gray-400 font-normal">Data Akademik</span>
                    </label>
                    <input 
                        type="text" 
                        :value="form.warga_negara || 'WNI'" 
                        disabled 
                        :class="lockedInputClass" 
                    />
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- BAGIAN 2: DOKUMEN PENDUKUNG (DARI DATA AKADEMIK)                  -->
        <!-- ================================================================= -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-xs border border-gray-100">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 flex-wrap gap-2">
                <h2 class="text-lg sm:text-xl font-black text-gray-900 flex items-center gap-2">
                    <span class="w-2.5 h-6 bg-[#005B3C] rounded-full inline-block"></span>
                    Dokumen Pendukung
                </h2>
                <span class="text-xs font-semibold px-2.5 py-1 bg-gray-100 text-gray-600 rounded-lg border border-gray-200">
                    Data Induk Akademik (Terkunci Otomatis)
                </span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5 flex items-center justify-between">
                        <span>Nomor Kartu Keluarga (KK)</span>
                        <span class="text-[11px] text-gray-400 font-normal">Data Akademik</span>
                    </label>
                    <input 
                        type="text" 
                        :value="form.no_kk || '-'" 
                        disabled 
                        :class="lockedInputClass" 
                        placeholder="Nomor KK (16 digit)" 
                    />
                </div>
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5 flex items-center justify-between">
                        <span>NISN</span>
                        <span class="text-[11px] text-gray-400 font-normal">Data Akademik</span>
                    </label>
                    <input 
                        type="text" 
                        :value="form.nisn || '-'" 
                        disabled 
                        :class="lockedInputClass" 
                        placeholder="10 Digit NISN" 
                    />
                </div>
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5 flex items-center justify-between">
                        <span>Nomor BPJS Kesehatan</span>
                        <span class="text-[11px] text-gray-400 font-normal">Data Akademik</span>
                    </label>
                    <input 
                        type="text" 
                        :value="form.no_bpjs || '-'" 
                        disabled 
                        :class="lockedInputClass" 
                        placeholder="13 Digit Nomor BPJS" 
                    />
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- BAGIAN 3: KONTAK & ALAMAT DOMISILI                                -->
        <!-- ================================================================= -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-xs border border-gray-100">
            <div class="flex items-center justify-between mb-6 pb-3 border-b border-gray-100">
                <h2 class="text-lg sm:text-xl font-black text-gray-900 flex items-center gap-2">
                    <span class="w-2.5 h-6 bg-[#005B3C] rounded-full inline-block"></span>
                    Kontak & Alamat Domisili
                </h2>
                <span class="text-xs text-gray-400 font-medium">Kolom bertanda <span class="text-rose-500 font-bold">*</span> wajib diisi</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Nomor Telepon / WA -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Nomor Telepon / WhatsApp <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input 
                        type="tel" 
                        :value="form.nomor_telepon" 
                        @input="handlePhoneInput"
                        autocomplete="off"
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-mono font-bold transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="isValidPhone(form.nomor_telepon) ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                        placeholder="Contoh: 081234567890" 
                    />
                </div>

                <!-- Email Pribadi -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Email Pribadi <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input 
                        type="email" 
                        v-model="form.email_pribadi" 
                        autocomplete="off"
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="isValidEmail(form.email_pribadi) ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                        placeholder="contoh: nama.alumni@gmail.com" 
                    />
                </div>

                <!-- Alamat Domisili -->
                <div class="md:col-span-2">
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Alamat Domisili Saat Ini <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <textarea 
                        v-model="form.alamat_saat_ini" 
                        rows="3" 
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="(form.alamat_saat_ini || form.alamat)?.trim() ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                        placeholder="Nama Jalan, RT/RW, Dusun/Kelurahan..."
                    ></textarea>
                </div>

                <!-- Provinsi -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Provinsi <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <select 
                        v-model="form.provinsi_id" 
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="(form.provinsi_id || form.propinsi_id) ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                    >
                        <option value="">-- Pilih Provinsi --</option>
                        <option v-for="prov in provinces" :key="prov.id" :value="prov.id">{{ prov.nama_provinsi }}</option>
                    </select>
                </div>

                <!-- Kabupaten / Kota -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Kabupaten / Kota <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <select 
                        v-model="form.kabupaten_id" 
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="form.kabupaten_id ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                    >
                        <option value="">-- Pilih Kabupaten/Kota --</option>
                        <option v-for="kab in kabupatens" :key="kab.id" :value="kab.id" v-show="!form.provinsi_id || kab.province_id == form.provinsi_id">
                            {{ kab.nama_kabupaten }}
                        </option>
                    </select>
                </div>

                <!-- Kecamatan -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Kecamatan <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input 
                        type="text" 
                        v-model="form.kecamatan" 
                        autocomplete="off"
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="form.kecamatan?.trim() ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                        placeholder="Kecamatan" 
                    />
                </div>

                <!-- Kelurahan / Desa -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Kelurahan / Desa <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input 
                        type="text" 
                        v-model="form.kelurahan" 
                        autocomplete="off"
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="form.kelurahan?.trim() ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                        placeholder="Kelurahan/Desa" 
                    />
                </div>

                <!-- Kode Pos -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Kode Pos <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input 
                        type="text" 
                        v-model="form.kode_pos" 
                        autocomplete="off"
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="form.kode_pos?.trim() ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                        placeholder="Kode Pos (5 digit)" 
                    />
                </div>
            </div>
        </div>
        
    </div>
</template>
