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
 * - form       : Objek useForm dari Index.vue (berisi nama_orang_tua, telepon_orang_tua, pekerjaan, alamat, dsb)
 * - provinces  : Daftar provinsi dari database (untuk dropdown provinsi orang tua)
 * - kabupatens : Daftar kabupaten dari database (untuk dropdown kabupaten orang tua)
 */
defineProps({
    form: Object,
    provinces: Array,
    kabupatens: Array
});

const inputClass = "block w-full border border-gray-200 bg-white rounded-xl shadow-xs focus:border-[#005B3C] focus:ring focus:ring-[#005B3C]/10 px-4 py-3 text-sm text-gray-800 font-medium transition-colors";
const labelClass = "block text-sm font-semibold text-gray-700 mb-1.5";
</script>

<template>
    <div class="space-y-8">
        
        <!-- Bagian: Identitas Orang Tua / Wali -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-gray-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">
                Identitas Orang Tua / Wali
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label :class="labelClass">Nama Lengkap Orang Tua / Wali</label>
                    <input type="text" v-model="form.nama_orang_tua" :class="inputClass" placeholder="Nama Lengkap Orang Tua / Wali" />
                </div>
                <div>
                    <label :class="labelClass">Pekerjaan Orang Tua / Wali</label>
                    <input type="text" v-model="form.pekerjaan_orang_tua" :class="inputClass" placeholder="Contoh: PNS, Wiraswasta, Karyawan Swasta" />
                </div>
                <div class="md:col-span-2">
                    <label :class="labelClass">Nomor Telepon / WhatsApp Orang Tua</label>
                    <input type="text" v-model="form.nomor_telepon_orang_tua" :class="inputClass" placeholder="Contoh: 081234567890" />
                </div>
            </div>
        </div>

        <!-- Bagian: Alamat Tempat Tinggal Orang Tua -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-gray-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">
                Alamat Domisili Orang Tua / Wali
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label :class="labelClass">Alamat Lengkap</label>
                    <textarea v-model="form.alamat_orang_tua" rows="3" :class="inputClass" placeholder="Nama Jalan, RT/RW, Dusun/Kompleks..."></textarea>
                </div>

                <div>
                    <label :class="labelClass">Provinsi</label>
                    <select v-model="form.provinsi_id_orang_tua" :class="inputClass">
                        <option value="">-- Pilih Provinsi --</option>
                        <option v-for="prov in provinces" :key="prov.id" :value="prov.id">{{ prov.nama_provinsi }}</option>
                    </select>
                </div>
                <div>
                    <label :class="labelClass">Kabupaten / Kota</label>
                    <select v-model="form.kabupaten_id_orang_tua" :class="inputClass">
                        <option value="">-- Pilih Kabupaten/Kota --</option>
                        <option v-for="kab in kabupatens" :key="kab.id" :value="kab.id" v-show="!form.provinsi_id_orang_tua || kab.province_id == form.provinsi_id_orang_tua">
                            {{ kab.nama_kabupaten }}
                        </option>
                    </select>
                </div>

                <div>
                    <label :class="labelClass">Kota / Keterangan Wilayah</label>
                    <input type="text" v-model="form.kota_orang_tua" :class="inputClass" placeholder="Contoh: Sleman, Yogyakarta" />
                </div>
                <div>
                    <label :class="labelClass">Kode Pos</label>
                    <input type="text" v-model="form.kode_pos_orang_tua" :class="inputClass" placeholder="Kode Pos" />
                </div>
            </div>
        </div>
        
    </div>
</template>
