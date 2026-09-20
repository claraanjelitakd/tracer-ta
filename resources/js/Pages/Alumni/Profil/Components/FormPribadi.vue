<!--
  Komponen Anak (Child Component): Form Data Pribadi
  File: resources/js/Pages/Alumni/Profil/Components/FormPribadi.vue
  
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
 * - form       : Objek useForm yang dioper dari Index.vue (berisi nama, nik, email, hp, alamat, dsb)
 * - provinces  : Daftar provinsi di Indonesia dari tabel 'provinces' (untuk dropdown pilihan)
 * - kabupatens : Daftar kabupaten/kota dari tabel 'kabupatens' (untuk dropdown pilihan)
 */
defineProps({
    form: Object,
    provinces: Array,
    kabupatens: Array,
    negaras: Array,
});

const inputClass = "block w-full border border-gray-200 bg-white rounded-xl shadow-xs focus:border-[#005B3C] focus:ring focus:ring-[#005B3C]/10 px-4 py-3 text-sm text-gray-800 font-medium transition-colors";
const lockedInputClass = "block w-full border border-gray-200 bg-gray-100 text-gray-700 rounded-xl px-4 py-3 text-sm font-medium cursor-not-allowed select-none";
const labelClass = "block text-sm font-semibold text-gray-700 mb-1.5";
</script>

<template>
    <div class="space-y-8">
        
        <!-- Bagian: Identitas Diri -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-gray-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">
                Identitas Diri
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label :class="labelClass">Nomor Induk Mahasiswa (NIM)</label>
                    <input type="text" :value="form.nim" disabled :class="lockedInputClass" />
                </div>
                <div>
                    <label :class="labelClass">Nama Lengkap (Sesuai Ijazah)</label>
                    <input type="text" v-model="form.nama" :class="inputClass" placeholder="Nama Lengkap" />
                </div>
                <div>
                    <label :class="labelClass">Nomor Induk Kependudukan (NIK)</label>
                    <input type="text" :value="form.nik" disabled :class="lockedInputClass" placeholder="16 Digit NIK" title="NIK bersifat permanen dan tidak dapat diubah" />
                </div>
                <div>
                    <label :class="labelClass">Nomor Pokok Wajib Pajak (NPWP)</label>
                    <input type="text" v-model="form.npwp" :class="inputClass" placeholder="15/16 Digit NPWP" />
                </div>
                
                <div>
                    <label :class="labelClass">Tempat Lahir</label>
                    <input type="text" v-model="form.tempat_lahir" :class="inputClass" placeholder="Kota Kelahiran" />
                </div>
                <div>
                    <label :class="labelClass">Tanggal Lahir</label>
                    <input type="date" v-model="form.tanggal_lahir" :class="inputClass" />
                </div>
                
                <div>
                    <label :class="labelClass">Jenis Kelamin</label>
                    <select v-model="form.jenis_kelamin" :class="inputClass">
                        <option value="">-- Pilih Jenis Kelamin --</option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>
                <div>
                    <label :class="labelClass">Agama</label>
                    <select v-model="form.agama" :class="inputClass">
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

                <div>
                    <label :class="labelClass">Golongan Darah</label>
                    <select v-model="form.golongan_darah" :class="inputClass">
                        <option value="">-- Pilih Golongan Darah --</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="AB">AB</option>
                        <option value="O">O</option>
                    </select>
                </div>
                <div>
                    <label :class="labelClass">Kewarganegaraan</label>
                    <input type="text" v-model="form.warga_negara" list="daftar-kewarganegaraan" :class="inputClass" placeholder="WNI / WNA (Pilih/Ketik Negara)" />
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

        <!-- Bagian: Dokumen Kenegaraan -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-gray-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">
                Dokumen Pendukung
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label :class="labelClass">Nomor Kartu Keluarga (KK)</label>
                    <input type="text" v-model="form.no_kk" :class="inputClass" placeholder="Nomor KK" />
                </div>
                <div>
                    <label :class="labelClass">NISN</label>
                    <input type="text" v-model="form.nisn" :class="inputClass" placeholder="NISN" />
                </div>
                <div>
                    <label :class="labelClass">Nomor BPJS Kesehatan</label>
                    <input type="text" v-model="form.no_bpjs" :class="inputClass" placeholder="Nomor BPJS" />
                </div>
            </div>
        </div>

        <!-- Bagian: Kontak & Alamat Domisili -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-gray-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">
                Kontak & Alamat Domisili
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label :class="labelClass">Nomor Telepon / WhatsApp</label>
                    <input type="text" v-model="form.nomor_telepon" :class="inputClass" placeholder="081234567890" />
                </div>
                <div>
                    <label :class="labelClass">Email Pribadi</label>
                    <input type="email" v-model="form.email_pribadi" :class="inputClass" placeholder="email@contoh.com" />
                </div>
                
                <div class="md:col-span-2">
                    <label :class="labelClass">Email Mahasiswa (Students)</label>
                    <input type="email" v-model="form.email_students" :class="inputClass" placeholder="nim@students.ukdw.ac.id" />
                </div>

                <div class="md:col-span-2">
                    <label :class="labelClass">Alamat Domisili Saat Ini</label>
                    <textarea v-model="form.alamat_saat_ini" rows="3" :class="inputClass" placeholder="Nama Jalan, RT/RW, Dusun/Kelurahan..."></textarea>
                </div>

                <div>
                    <label :class="labelClass">Provinsi</label>
                    <select v-model="form.provinsi_id" :class="inputClass">
                        <option value="">-- Pilih Provinsi --</option>
                        <option v-for="prov in provinces" :key="prov.id" :value="prov.id">{{ prov.nama_provinsi }}</option>
                    </select>
                </div>
                <div>
                    <label :class="labelClass">Kabupaten / Kota</label>
                    <select v-model="form.kabupaten_id" :class="inputClass">
                        <option value="">-- Pilih Kabupaten/Kota --</option>
                        <option v-for="kab in kabupatens" :key="kab.id" :value="kab.id" v-show="!form.provinsi_id || kab.province_id == form.provinsi_id">
                            {{ kab.nama_kabupaten }}
                        </option>
                    </select>
                </div>

                <div>
                    <label :class="labelClass">Kecamatan</label>
                    <input type="text" v-model="form.kecamatan" :class="inputClass" placeholder="Kecamatan" />
                </div>
                <div>
                    <label :class="labelClass">Kelurahan / Desa</label>
                    <input type="text" v-model="form.kelurahan" :class="inputClass" placeholder="Kelurahan/Desa" />
                </div>
                <div>
                    <label :class="labelClass">Kode Pos</label>
                    <input type="text" v-model="form.kode_pos" :class="inputClass" placeholder="Kode Pos" />
                </div>
            </div>
        </div>
        
    </div>
</template>
