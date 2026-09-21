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
        <!-- BAGIAN 1: IDENTITAS DIRI                                         -->
        <!-- ================================================================= -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-xs border border-gray-100">
            <div class="flex items-center justify-between mb-6 pb-3 border-b border-gray-100">
                <h2 class="text-lg sm:text-xl font-black text-gray-900 flex items-center gap-2">
                    <span class="w-2.5 h-6 bg-[#005B3C] rounded-full inline-block"></span>
                    Identitas Diri Resmi
                </h2>
                <span class="text-xs text-gray-400 font-medium">Kolom bertanda <span class="text-rose-500 font-bold">*</span> wajib diisi</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- NIM (Terkunci) -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Nomor Induk Mahasiswa (NIM)
                    </label>
                    <input type="text" :value="form.nim" disabled :class="lockedInputClass" />
                </div>

                <!-- Nama Lengkap -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Nama Lengkap (Sesuai Ijazah) <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input 
                        type="text" 
                        v-model="form.nama" 
                        autocomplete="off"
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="form.nama?.trim() ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                        placeholder="Nama Lengkap" 
                    />
                </div>

                <!-- NIK (Terkunci) -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Nomor Induk Kependudukan (NIK) <span class="text-rose-500 font-bold">*</span>
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
                
                <!-- Tempat Lahir -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Tempat Lahir <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input 
                        type="text" 
                        v-model="form.tempat_lahir" 
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="form.tempat_lahir?.trim() ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                        placeholder="Kota / Kabupaten Kelahiran" 
                    />
                </div>

                <!-- Tanggal Lahir -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Tanggal Lahir <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input 
                        type="date" 
                        v-model="form.tanggal_lahir" 
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="form.tanggal_lahir ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                    />
                </div>
                
                <!-- Jenis Kelamin -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Jenis Kelamin <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <select 
                        v-model="form.jenis_kelamin" 
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="form.jenis_kelamin ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                    >
                        <option value="">-- Pilih Jenis Kelamin --</option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>

                <!-- Agama -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Agama <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <select 
                        v-model="form.agama" 
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="form.agama ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                    >
                        <option value="">-- Pilih Agama --</option>
                        <option value="Islam">Islam</option>
                        <option value="Kristen Protestan">Kristen Protestan</option>
                        <option value="Katolik">Katolik</option>
                        <option value="Hindu">Hindu</option>
                        <option value="Buddha">Buddha</option>
                        <option value="Konghucu">Konghucu</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <!-- Golongan Darah -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Golongan Darah <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <select 
                        v-model="form.golongan_darah" 
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="form.golongan_darah ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                    >
                        <option value="">-- Pilih Golongan Darah --</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="AB">AB</option>
                        <option value="O">O</option>
                    </select>
                </div>

                <!-- Kewarganegaraan -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Kewarganegaraan <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input 
                        type="text" 
                        v-model="form.warga_negara" 
                        list="daftar-kewarganegaraan" 
                        autocomplete="off"
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="form.warga_negara?.trim() ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                        placeholder="WNI / WNA (Pilih/Ketik Negara)" 
                    />
                    <datalist id="daftar-kewarganegaraan">
                        <option value="WNI (Indonesia)">WNI (Indonesia)</option>
                        <option value="WNA">WNA</option>
                        <option v-for="neg in negaras" :key="neg.id" :value="`WNA (${neg.nama_negara})`">
                            {{ neg.nama_negara }} ({{ neg.benua || 'Dunia' }})
                        </option>
                    </datalist>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- BAGIAN 2: DOKUMEN PENDUKUNG                                       -->
        <!-- ================================================================= -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-xs border border-gray-100">
            <div class="flex items-center justify-between mb-6 pb-3 border-b border-gray-100">
                <h2 class="text-lg sm:text-xl font-black text-gray-900 flex items-center gap-2">
                    <span class="w-2.5 h-6 bg-[#005B3C] rounded-full inline-block"></span>
                    Dokumen Pendukung
                </h2>
                <span class="text-xs text-gray-400 font-medium">Kolom bertanda <span class="text-rose-500 font-bold">*</span> wajib diisi</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Nomor Kartu Keluarga (KK) <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input 
                        type="text" 
                        v-model="form.no_kk" 
                        autocomplete="off"
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="form.no_kk?.trim() ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                        placeholder="Nomor KK (16 digit)" 
                    />
                </div>
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        NISN <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input 
                        type="text" 
                        v-model="form.nisn" 
                        autocomplete="off"
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="form.nisn?.trim() ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                        placeholder="10 Digit NISN" 
                    />
                </div>
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Nomor BPJS Kesehatan <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input 
                        type="text" 
                        v-model="form.no_bpjs" 
                        autocomplete="off"
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="form.no_bpjs?.trim() ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
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
                        :class="isValidEmail(form.email_pribadi || form.email) ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                        placeholder="contoh: nama.alumni@gmail.com" 
                    />
                </div>
                
                <!-- Email Students -->
                <div class="md:col-span-2">
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Email Mahasiswa (Students UKDW) <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input 
                        type="email" 
                        v-model="form.email_students" 
                        autocomplete="off"
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="isValidEmail(form.email_students) ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                        placeholder="nim@students.ukdw.ac.id" 
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
