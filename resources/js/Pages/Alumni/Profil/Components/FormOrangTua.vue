<!--
  Komponen Anak (Child Component): Form Data Orang Tua
  File: resources/js/Pages/Alumni/Profil/Components/FormOrangTua.vue
  
  DIPANGGIL OLEH (Parent Component):
  - resources/js/Pages/Alumni/Profil/Index.vue
  
  SUMBER DATA DARI BACKEND:
  - Controller: App\Http\Controllers\Alumni\Profil\ProfilController.php
-->
<script setup>
/**
 * ====================================================================
 * MENERIMA DATA (PROPS) DARI PARENT (Index.vue)
 * ====================================================================
 */
const props = defineProps({
    form: Object,
    provinces: Array,
    kabupatens: Array
});

// Helper validasi telepon
const isValidPhone = (val) => {
    if (!val) return false;
    const clean = String(val).replace(/[^0-9+]/g, '');
    return clean.length >= 10 && clean.length <= 15;
};

// Handler input telepon hanya angka & tanda +
const handlePhoneInput = (e) => {
    props.form.nomor_telepon_orang_tua = e.target.value.replace(/[^0-9+]/g, '').slice(0, 15);
};
</script>

<template>
    <div class="space-y-8">
        
        <!-- Bagian: Identitas Orang Tua / Wali -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-xs border border-gray-100">
            <div class="flex items-center justify-between mb-6 pb-3 border-b border-gray-100">
                <h2 class="text-lg sm:text-xl font-black text-gray-900 flex items-center gap-2">
                    <span class="w-2.5 h-6 bg-[#005B3C] rounded-full inline-block"></span>
                    Identitas Orang Tua / Wali
                </h2>
                <span class="text-xs text-gray-400 font-medium">Kolom bertanda <span class="text-rose-500 font-bold">*</span> wajib diisi</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Nama Lengkap Orang Tua -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Nama Lengkap Orang Tua / Wali <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input 
                        type="text" 
                        v-model="form.nama_orang_tua" 
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="form.nama_orang_tua?.trim() ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                        placeholder="Nama Lengkap Orang Tua / Wali" 
                    />
                </div>

                <!-- Pekerjaan Orang Tua -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Pekerjaan Orang Tua / Wali <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input 
                        type="text" 
                        v-model="form.pekerjaan_orang_tua" 
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="form.pekerjaan_orang_tua?.trim() ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                        placeholder="Contoh: PNS, Wiraswasta, Karyawan Swasta, Petani..." 
                    />
                </div>

                <!-- Nomor Telepon Orang Tua -->
                <div class="md:col-span-2">
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Nomor Telepon / WhatsApp Orang Tua <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input 
                        type="tel" 
                        :value="form.nomor_telepon_orang_tua" 
                        @input="handlePhoneInput"
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-mono font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="isValidPhone(form.nomor_telepon_orang_tua) ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                        placeholder="Contoh: 081234567890" 
                    />
                </div>
            </div>
        </div>

        <!-- Bagian: Alamat Tempat Tinggal Orang Tua -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-xs border border-gray-100">
            <div class="flex items-center justify-between mb-6 pb-3 border-b border-gray-100">
                <h2 class="text-lg sm:text-xl font-black text-gray-900 flex items-center gap-2">
                    <span class="w-2.5 h-6 bg-[#005B3C] rounded-full inline-block"></span>
                    Alamat Domisili Orang Tua / Wali
                </h2>
                <span class="text-xs text-gray-400 font-medium">Kolom bertanda <span class="text-rose-500 font-bold">*</span> wajib diisi</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Alamat Lengkap -->
                <div class="md:col-span-2">
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Alamat Lengkap <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <textarea 
                        v-model="form.alamat_orang_tua" 
                        rows="3" 
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="form.alamat_orang_tua?.trim() ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                        placeholder="Nama Jalan, RT/RW, Dusun/Kompleks, Kelurahan..."
                    ></textarea>
                </div>

                <!-- Provinsi -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">
                        Provinsi <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <select 
                        v-model="form.provinsi_id_orang_tua" 
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="form.provinsi_id_orang_tua ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
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
                        v-model="form.kabupaten_id_orang_tua" 
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20"
                        :class="form.kabupaten_id_orang_tua ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                    >
                        <option value="">-- Pilih Kabupaten/Kota --</option>
                        <option v-for="kab in kabupatens" :key="kab.id" :value="kab.id" v-show="!form.provinsi_id_orang_tua || kab.province_id == form.provinsi_id_orang_tua">
                            {{ kab.nama_kabupaten }}
                        </option>
                    </select>
                </div>

                <!-- Kota / Keterangan Wilayah -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">Kota / Keterangan Wilayah</label>
                    <input 
                        type="text" 
                        v-model="form.kota_orang_tua" 
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20" 
                        :class="form.kota_orang_tua?.trim() ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                        placeholder="Contoh: Sleman, Yogyakarta" 
                    />
                </div>

                <!-- Kode Pos -->
                <div>
                    <label class="block text-xs sm:text-sm font-bold text-gray-700 mb-1.5">Kode Pos</label>
                    <input 
                        type="text" 
                        v-model="form.kode_pos_orang_tua" 
                        class="block w-full border rounded-xl shadow-2xs px-4 py-3 text-sm font-medium transition-all focus:ring-2 focus:ring-[#005B3C]/20" 
                        :class="form.kode_pos_orang_tua?.trim() ? 'border-emerald-300 bg-white text-gray-900 focus:border-[#005B3C]' : 'border-rose-300 bg-rose-50/20 text-gray-900 focus:border-rose-500'"
                        placeholder="Kode Pos (5 digit)" 
                    />
                </div>
            </div>
        </div>
        
    </div>
</template>
