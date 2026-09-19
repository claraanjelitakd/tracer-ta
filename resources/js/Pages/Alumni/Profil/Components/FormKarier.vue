<!--
  Komponen Anak (Child Component): Form Karier & Riwayat Pekerjaan
  File: resources/js/Pages/Alumni/Profil/Components/FormKarier.vue
  
  DIPANGGIL OLEH (Parent Component):
  👉 resources/js/Pages/Alumni/Profil/Index.vue
  (Pada baris: <FormKarier :form="form" :provinces="provinces" :kabupatens="kabupatens" :companies="companies" :alumniData="alumniData" />)
  
  SUMBER ASLI DATA DARI BACKEND:
  👉 Controller: App\Http\Controllers\Alumni\Profil\ProfilController.php (method index)
  (Menyediakan 'formData', 'provinces', 'kabupatens', 'companies', dan 'alumniData')
-->
<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import Swal from 'sweetalert2';

/**
 * ====================================================================
 * MENERIMA DATA (PROPS) DARI PARENT (Index.vue)
 * ====================================================================
 * - form       : Objek useForm dari Index.vue (berisi status_pekerjaan, nama_perusahaan, jabatan, gaji, linkedin, dsb)
 * - provinces  : Daftar provinsi di Indonesia (untuk lokasi kantor)
 * - kabupatens : Daftar kabupaten/kota (untuk lokasi kantor)
 * - companies  : Daftar perusahaan yang sudah terdaftar di database kampus
 * - alumniData : Data lengkap alumni termasuk relasi prodi (untuk cek kode prodi kusus, misal prodi 31)
 */
const props = defineProps({
    form: Object,
    provinces: Array,
    kabupatens: Array,
    negaras: Array,
    companies: Array,
    alumniData: Object,
});

const inputClass = "block w-full border border-gray-200 bg-white rounded-xl shadow-xs focus:border-[#005B3C] focus:ring focus:ring-[#005B3C]/10 px-4 py-3 text-sm text-gray-800 font-medium transition-colors";
const labelClass = "block text-sm font-semibold text-gray-700 mb-1.5";

// Container refs untuk click-outside
const provinsiContainerRef = ref(null);
const kabupatenContainerRef = ref(null);
const negaraContainerRef = ref(null);
const companyContainerRef = ref(null);

// Cek apakah peran/posisi merupakan Owner / Founder / Wiraswasta
const isOwner = computed(() => {
    if (props.form.kategori_pekerjaan === 'Wiraswasta') return true;
    const pos = (props.form.posisi_jabatan || '').toLowerCase();
    const posWira = (props.form.posisi_wiraswasta || '').toLowerCase();
    return ['owner', 'founder', 'wiraswasta', 'wirausaha', 'wiraswasta / wirausaha', 'owner / founder'].includes(pos) || posWira !== '';
});

// Helper auto-fill atasan jika wiraswasta/owner
const autoFillAtasanOwner = () => {
    props.form.nama_atasan = props.form.nama || '';
    props.form.email_atasan = props.form.email_pribadi || props.form.email || '';
    props.form.telepon_atasan = props.form.nomor_telepon || '';
};

// Watcher: Ketika beralih kategori Wiraswasta vs Pekerja
watch(() => props.form.kategori_pekerjaan, (newVal) => {
    if (newVal === 'Wiraswasta') {
        if (!props.form.posisi_wiraswasta) props.form.posisi_wiraswasta = 'Owner';
        props.form.posisi_jabatan = 'Direksi';
        autoFillAtasanOwner();
    } else if (newVal === 'Pekerja') {
        props.form.posisi_wiraswasta = '';
        if (['owner', 'founder', 'wiraswasta', 'wirausaha', 'wiraswasta / wirausaha', 'owner / founder'].includes((props.form.posisi_jabatan || '').toLowerCase())) {
            props.form.posisi_jabatan = 'Staff';
        }
    }
});

// Watcher: Ketika memilih posisi Owner/Founder/Wiraswasta di input teks
watch(() => props.form.posisi_jabatan, (newVal) => {
    if (!newVal) return;
    const pos = newVal.toLowerCase();
    if (['owner', 'founder', 'wiraswasta', 'wirausaha', 'wiraswasta / wirausaha', 'owner / founder'].includes(pos)) {
        autoFillAtasanOwner();
    }
});

watch(() => props.form.posisi_wiraswasta, (newVal) => {
    if (newVal && props.form.kategori_pekerjaan === 'Wiraswasta') {
        autoFillAtasanOwner();
    }
});

// Helper format Rupiah untuk pratinjau nominal gaji
const formatRupiah = (val) => {
    if (!val && val !== 0) return 'Rp 0';
    let num = Number(val);
    if (isNaN(num) || num <= 0) return 'Rp 0';
    // Jika angka < 1.000.000 dan > 0 (misal diinput ribuan seperti 5000), konversi ke nominal penuh (Rp 5.000.000)
    const actualNominal = num < 1000000 ? num * 1000 : num;
    return 'Rp ' + actualNominal.toLocaleString('id-ID');
};

// Format angka ke format titik ribuan tampilan
const formatNominalDisplay = (val) => {
    if (!val && val !== 0) return '';
    const clean = String(val).replace(/[^0-9]/g, '');
    if (!clean) return '';
    const num = Number(clean);
    return isNaN(num) || num === 0 ? '' : new Intl.NumberFormat('id-ID').format(num);
};

// String tampilan gaji terformat langsung di dalam input box
const formattedGajiString = ref(formatNominalDisplay(props.form.gaji));

// Sinkronkan jika data gaji form diperbarui dari luar
watch(() => props.form.gaji, (newVal) => {
    formattedGajiString.value = formatNominalDisplay(newVal);
});

// Event Handler: Format otomatis dengan titik pemisah ribuan saat user mengetik
const handleGajiInput = (e) => {
    const raw = e.target.value;
    const cleanDigits = raw.replace(/[^0-9]/g, '');
    if (!cleanDigits) {
        props.form.gaji = null;
        formattedGajiString.value = '';
        e.target.value = '';
        return;
    }
    let num = Number(cleanDigits);

    // Jika user mengetik angka satuan kecil (contoh: 5), langsung muncul format ribuan 5.000 di dalam text box dan bisa diedit
    if (num > 0 && num < 1000 && !raw.includes('000')) {
        num = num * 1000;
    }

    props.form.gaji = num;
    formattedGajiString.value = new Intl.NumberFormat('id-ID').format(num);
    e.target.value = formattedGajiString.value;
};

// Salinan lokal daftar perusahaan agar instan ter-update saat tambah perusahaan baru
const localCompanies = ref([...(props.companies || [])]);
watch(() => props.companies, (newVal) => {
    if (newVal) localCompanies.value = [...newVal];
}, { deep: true });

// Inisialisasi jenis lokasi perusahaan jika belum ada (default 'Dalam Negeri')
if (!props.form.company_jenis_lokasi && !props.form.perusahaan_jenis_lokasi) {
    props.form.company_jenis_lokasi = 'Dalam Negeri';
    props.form.perusahaan_jenis_lokasi = 'Dalam Negeri';
}
if (!props.form.company_negara && !props.form.perusahaan_negara) {
    props.form.company_negara = props.form.company_jenis_lokasi === 'Luar Negeri' ? '' : 'Indonesia';
    props.form.perusahaan_negara = props.form.company_negara;
}

// Watcher ketika jenis lokasi perusahaan berganti
watch(() => props.form.company_jenis_lokasi, (newVal) => {
    props.form.perusahaan_jenis_lokasi = newVal;
    if (newVal === 'Luar Negeri') {
        props.form.company_province_id = null;
        props.form.perusahaan_propinsi_id = null;
        props.form.company_kabupaten_id = null;
        props.form.perusahaan_kabupaten_id = null;
        if (!props.form.company_negara || props.form.company_negara === 'Indonesia') {
            props.form.company_negara = '';
            props.form.perusahaan_negara = '';
        }
    } else {
        props.form.company_negara = 'Indonesia';
        props.form.perusahaan_negara = 'Indonesia';
    }
});

watch(() => props.form.company_negara, (newVal) => {
    props.form.perusahaan_negara = newVal;
});

// ============================================================================
// DROPDOWN PROVINSI (SEARCHABLE SEPERTI GAMBAR 2)
// ============================================================================
const showProvinsiDropdown = ref(false);
const searchProvinsiQuery = ref('');

const selectedProvinceName = computed(() => {
    if (!props.form.company_province_id || !props.provinces) return '';
    const prov = props.provinces.find(p => p.id == props.form.company_province_id);
    return prov ? prov.nama_provinsi : '';
});

const filteredProvinces = computed(() => {
    const list = props.provinces || [];
    if (!searchProvinsiQuery.value.trim()) return list;
    const q = searchProvinsiQuery.value.toLowerCase();
    return list.filter(p => p.nama_provinsi.toLowerCase().includes(q));
});

const selectProvinsi = (prov) => {
    props.form.company_province_id = prov.id;
    // Reset kabupaten saat provinsi berganti
    props.form.company_kabupaten_id = '';
    showProvinsiDropdown.value = false;
    searchProvinsiQuery.value = '';
};

// ============================================================================
// DROPDOWN KABUPATEN (SEARCHABLE SEPERTI GAMBAR 2)
// ============================================================================
const showKabupatenDropdown = ref(false);
const searchKabupatenQuery = ref('');

const selectedKabupatenName = computed(() => {
    if (!props.form.company_kabupaten_id || !props.kabupatens) return '';
    const kab = props.kabupatens.find(k => k.id == props.form.company_kabupaten_id);
    return kab ? kab.nama_kabupaten : '';
});

const filteredKabupatens = computed(() => {
    let list = props.kabupatens || [];
    if (props.form.company_province_id) {
        list = list.filter(k => k.province_id == props.form.company_province_id);
    }
    if (!searchKabupatenQuery.value.trim()) return list;
    const q = searchKabupatenQuery.value.toLowerCase();
    return list.filter(k => k.nama_kabupaten.toLowerCase().includes(q));
});

const selectKabupaten = (kab) => {
    props.form.company_kabupaten_id = kab.id;
    showKabupatenDropdown.value = false;
    searchKabupatenQuery.value = '';
};

// ============================================================================
// DROPDOWN NEGARA (SEARCHABLE SEPERTI TEMPLATE PROVINSI - KHUSUS LUAR NEGERI)
// ============================================================================
const showNegaraDropdown = ref(false);
const searchNegaraQuery = ref('');

const selectedNegaraName = computed(() => {
    if (!props.form.company_negara || props.form.company_negara === 'Indonesia') return '';
    return props.form.company_negara;
});

const filteredNegaras = computed(() => {
    // Luar negeri tidak boleh memilih Indonesia (hanya negara di luar negeri)
    const list = (props.negaras || []).filter(n => n.nama_negara.toLowerCase() !== 'indonesia');
    if (!searchNegaraQuery.value.trim()) return list;
    const q = searchNegaraQuery.value.toLowerCase();
    return list.filter(n => 
        n.nama_negara.toLowerCase().includes(q) || 
        (n.benua && n.benua.toLowerCase().includes(q)) ||
        (n.ibu_kota && n.ibu_kota.toLowerCase().includes(q)) ||
        (n.kode_iso2 && n.kode_iso2.toLowerCase().includes(q))
    );
});

const selectNegara = (neg) => {
    props.form.company_negara = neg.nama_negara;
    props.form.perusahaan_negara = neg.nama_negara;
    showNegaraDropdown.value = false;
    searchNegaraQuery.value = '';
};

// ============================================================================
// DROPDOWN PERUSAHAAN (SEARCHABLE SEPERTI GAMBAR 2)
// ============================================================================
const showCompanyDropdown = ref(false);
const searchCompanyQuery = ref('');

const filteredCompanies = computed(() => {
    let list = localCompanies.value || [];

    // Filter berdasarkan jenis lokasi (Dalam Negeri / Luar Negeri)
    if (props.form.company_jenis_lokasi) {
        list = list.filter(c => (c.jenis_lokasi || 'Dalam Negeri') === props.form.company_jenis_lokasi);
    }

    // Jika dalam negeri dan provinsi sudah dipilih
    if (props.form.company_jenis_lokasi === 'Dalam Negeri' && props.form.company_province_id) {
        const inProv = list.filter(c => c.province_id == props.form.company_province_id);
        if (inProv.length > 0) {
            list = inProv;
        }
    }
    // Jika dalam negeri dan kabupaten sudah dipilih
    if (props.form.company_jenis_lokasi === 'Dalam Negeri' && props.form.company_kabupaten_id) {
        const inKab = list.filter(c => c.kabupaten_id == props.form.company_kabupaten_id);
        if (inKab.length > 0) {
            list = inKab;
        }
    }

    if (!searchCompanyQuery.value.trim()) return list;
    const q = searchCompanyQuery.value.toLowerCase();
    return list.filter(c => c.nama_perusahaan.toLowerCase().includes(q) || (c.negara && c.negara.toLowerCase().includes(q)));
});

const selectCompany = (company) => {
    props.form.nama_perusahaan = company.nama_perusahaan;
    
    // Otomatis isi detail lokasi, negara, provinsi, kabupaten, alamat, skala jika ada di database
    if (company.jenis_lokasi) {
        props.form.company_jenis_lokasi = company.jenis_lokasi;
        props.form.perusahaan_jenis_lokasi = company.jenis_lokasi;
    }
    if (company.negara) {
        props.form.company_negara = company.negara;
        props.form.perusahaan_negara = company.negara;
    }
    if (company.province_id) props.form.company_province_id = company.province_id;
    if (company.kabupaten_id) props.form.company_kabupaten_id = company.kabupaten_id;
    if (company.alamat) props.form.company_alamat = company.alamat;
    if (company.skala) props.form.company_skala = company.skala;
    if (company.kode_pos) props.form.zipcode = company.kode_pos;
    if (company.jenis_perusahaan) {
        props.form.company_jenis_perusahaan = company.jenis_perusahaan;
        props.form.perusahaan_jenis_perusahaan = company.jenis_perusahaan;
    }
    if (company.jenis_perusahaan_lainnya) {
        props.form.company_jenis_perusahaan_lainnya = company.jenis_perusahaan_lainnya;
        props.form.perusahaan_jenis_perusahaan_lainnya = company.jenis_perusahaan_lainnya;
    }
    
    showCompanyDropdown.value = false;
    searchCompanyQuery.value = '';
};

// ============================================================================
// MODAL POP-UP TAMBAH PERUSAHAAN BARU (MENGGUNAKAN SWEETALERT2)
// ============================================================================
const openAddCompanyModal = () => {
    showCompanyDropdown.value = false;

    // Persiapkan daftar option provinsi
    const provinceOptions = (props.provinces || [])
        .map(p => `<option value="${p.id}" ${p.id == props.form.company_province_id ? 'selected' : ''}>${p.nama_provinsi}</option>`)
        .join('');

    const initialProvId = props.form.company_province_id || '';
    const initialKabId = props.form.company_kabupaten_id || '';
    const initialKodePos = props.form.zipcode || '';
    const initialJenisLokasi = props.form.company_jenis_lokasi || 'Dalam Negeri';
    const initialNegara = props.form.company_negara || '';
    const initialJenisPerusahaan = props.form.company_jenis_perusahaan || 'Perusahaan swasta';

    // Persiapkan daftar option negara untuk modal (Kecualikan Indonesia)
    const foreignCountries = (props.negaras || []).filter(n => n.nama_negara.toLowerCase() !== 'indonesia');
    const negaraSelectOptions = foreignCountries
        .map(n => `<option value="${n.nama_negara}" ${n.nama_negara === initialNegara ? 'selected' : ''}>${n.nama_negara} (${n.benua || 'Dunia'})</option>`)
        .join('');

    const htmlContent = `
        <div style="text-align: left; font-size: 13px; color: #374151;" class="space-y-3.5 pt-1">
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                    Jenis Lokasi Perusahaan <span style="color: #ef4444;">*</span>
                </label>
                <div style="display: flex; gap: 16px; margin-top: 4px;">
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13px; font-weight: 600; color: #1f2937;">
                        <input type="radio" id="swal-lokasi-dalam" name="swal-jenis-lokasi" value="Dalam Negeri" ${initialJenisLokasi === 'Dalam Negeri' ? 'checked' : ''} style="accent-color: #005B3C;" />
                        🇮🇩 Dalam Negeri (Indonesia)
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13px; font-weight: 600; color: #1f2937;">
                        <input type="radio" id="swal-lokasi-luar" name="swal-jenis-lokasi" value="Luar Negeri" ${initialJenisLokasi === 'Luar Negeri' ? 'checked' : ''} style="accent-color: #005B3C;" />
                        ✈️ Luar Negeri (Abroad)
                    </label>
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">
                    Nama Perusahaan / Instansi <span style="color: #ef4444;">*</span>
                </label>
                <input id="swal-company-name" type="text" style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 9px 12px; font-size: 14px; box-sizing: border-box; outline: none;" placeholder="Contoh: PT Teknologi Nusantara / Google Singapore" />
            </div>

            <!-- Jenis Instansi / Perusahaan (F11) -->
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">
                    Jenis Perusahaan / Instansi (F11)
                </label>
                <select id="swal-company-jenis" style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 9px 12px; font-size: 14px; box-sizing: border-box; outline: none; background: #fff;">
                    <option value="Instansi pemerintah" ${initialJenisPerusahaan === 'Instansi pemerintah' ? 'selected' : ''}>1 - Instansi pemerintah</option>
                    <option value="Organisasi non-profit/Lembaga Swadaya Masyarakat" ${initialJenisPerusahaan === 'Organisasi non-profit/Lembaga Swadaya Masyarakat' ? 'selected' : ''}>2 - Organisasi non-profit / LSM</option>
                    <option value="Perusahaan swasta" ${initialJenisPerusahaan === 'Perusahaan swasta' || !initialJenisPerusahaan ? 'selected' : ''}>3 - Perusahaan swasta</option>
                    <option value="Wiraswasta/perusahaan sendiri" ${initialJenisPerusahaan === 'Wiraswasta/perusahaan sendiri' ? 'selected' : ''}>4 - Wiraswasta / Perusahaan sendiri</option>
                    <option value="BUMN/BUMD" ${initialJenisPerusahaan === 'BUMN/BUMD' ? 'selected' : ''}>6 - BUMN / BUMD</option>
                    <option value="Institusi/Organisasi Multilateral" ${initialJenisPerusahaan === 'Institusi/Organisasi Multilateral' ? 'selected' : ''}>7 - Institusi / Organisasi Multilateral</option>
                    <option value="Lainnya" ${initialJenisPerusahaan === 'Lainnya' ? 'selected' : ''}>5 - Lainnya</option>
                </select>
            </div>

            <div id="swal-section-jenis-lainnya" style="display: ${initialJenisPerusahaan === 'Lainnya' ? 'block' : 'none'};">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">
                    Sebutkan Jenis Instansi Lainnya
                </label>
                <input id="swal-company-jenis-lainnya" type="text" style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 9px 12px; font-size: 14px; box-sizing: border-box; outline: none;" placeholder="Contoh: Startup Komunitas, Lembaga Riset..." />
            </div>

            <!-- Bagian Luar Negeri: Dropdown Select Negara Resmi (Kecualikan Indonesia) -->
            <div id="swal-section-luar-negeri" style="${initialJenisLokasi === 'Luar Negeri' ? 'display: block;' : 'display: none;'}">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">
                    Negara Tempat Bekerja <span style="color: #ef4444;">*</span>
                </label>
                <select id="swal-company-negara" style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 9px 12px; font-size: 14px; box-sizing: border-box; outline: none; background: #fff;">
                    <option value="">-- Pilih Negara di Luar Negeri --</option>
                    ${negaraSelectOptions}
                </select>
                <div style="font-size: 11px; color: #6b7280; margin-top: 4px;">
                    * Pilihan negara bersumber dari daftar master negara resmi dunia (Indonesia tidak termasuk kategori luar negeri).
                </div>
            </div>

            <!-- Bagian Dalam Negeri: Provinsi & Kabupaten -->
            <div id="swal-section-dalam-negeri" style="${initialJenisLokasi === 'Dalam Negeri' ? 'display: grid;' : 'display: none;'} grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">
                        Provinsi <span style="color: #ef4444;">*</span>
                    </label>
                    <select id="swal-company-province" style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 9px 12px; font-size: 14px; box-sizing: border-box; outline: none; background: #fff;">
                        <option value="">-- Pilih Provinsi --</option>
                        ${provinceOptions}
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">
                        Kabupaten / Kota <span style="color: #ef4444;">*</span>
                    </label>
                    <select id="swal-company-kabupaten" style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 9px 12px; font-size: 14px; box-sizing: border-box; outline: none; background: #fff;">
                        <option value="">-- Pilih Kabupaten --</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">
                        Kode Pos Perusahaan
                    </label>
                    <input id="swal-company-kodepos" type="text" value="${initialKodePos}" style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 9px 12px; font-size: 14px; box-sizing: border-box; outline: none;" placeholder="Contoh: 55281 / 117371" />
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">
                        Skala Perusahaan
                    </label>
                    <select id="swal-company-skala" style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 9px 12px; font-size: 14px; box-sizing: border-box; outline: none; background: #fff;">
                        <option value="Lokal">Lokal</option>
                        <option value="Nasional" selected>Nasional</option>
                        <option value="Internasional">Internasional</option>
                    </select>
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">
                    Alamat Jalan / Gedung Perusahaan
                </label>
                <textarea id="swal-company-alamat" rows="2" style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 9px 12px; font-size: 14px; box-sizing: border-box; outline: none; resize: vertical;" placeholder="Nama Jalan, Gedung, Nomor..."></textarea>
            </div>

            <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 10px 12px; font-size: 12px; color: #92400e; display: flex; align-items: center; gap: 8px;">
                <span>ℹ️</span>
                <span>Status verifikasi awal: <strong>Menunggu Verifikasi</strong>.</span>
            </div>
        </div>
    `;

    Swal.fire({
        title: '<div style="font-size: 18px; font-weight: 700; color: #111827;">Tambah Perusahaan Baru</div>',
        html: htmlContent,
        showCancelButton: true,
        confirmButtonText: 'Simpan Perusahaan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#005B3C',
        cancelButtonColor: '#9CA3AF',
        focusConfirm: false,
        width: '34rem',
        customClass: {
            popup: 'rounded-2xl shadow-xl'
        },
        didOpen: () => {
            const radioDalam = document.getElementById('swal-lokasi-dalam');
            const radioLuar = document.getElementById('swal-lokasi-luar');
            const sectionDalam = document.getElementById('swal-section-dalam-negeri');
            const sectionLuar = document.getElementById('swal-section-luar-negeri');
            const provSelect = document.getElementById('swal-company-province');
            const kabSelect = document.getElementById('swal-company-kabupaten');
            const jenisSelect = document.getElementById('swal-company-jenis');
            const sectionJenisLainnya = document.getElementById('swal-section-jenis-lainnya');

            if (jenisSelect && sectionJenisLainnya) {
                jenisSelect.addEventListener('change', (e) => {
                    sectionJenisLainnya.style.display = (e.target.value === 'Lainnya') ? 'block' : 'none';
                });
            }

            const toggleLocationSections = () => {
                if (radioLuar.checked) {
                    sectionLuar.style.display = 'block';
                    sectionDalam.style.display = 'none';
                } else {
                    sectionLuar.style.display = 'none';
                    sectionDalam.style.display = 'grid';
                }
            };

            radioDalam.addEventListener('change', toggleLocationSections);
            radioLuar.addEventListener('change', toggleLocationSections);

            const updateKabupatenOptions = (provId, selectedKabId = '') => {
                if (!provId) {
                    kabSelect.innerHTML = '<option value="">-- Pilih Kabupaten --</option>';
                    kabSelect.disabled = true;
                    kabSelect.style.backgroundColor = '#f3f4f6';
                    return;
                }
                const filtered = (props.kabupatens || []).filter(k => k.province_id == provId);
                let options = '<option value="">-- Pilih Kabupaten --</option>';
                filtered.forEach(k => {
                    const sel = k.id == selectedKabId ? 'selected' : '';
                    options += `<option value="${k.id}" ${sel}>${k.nama_kabupaten}</option>`;
                });
                kabSelect.innerHTML = options;
                kabSelect.disabled = false;
                kabSelect.style.backgroundColor = '#fff';
            };

            // Inisialisasi kabupaten jika sudah ada provinsi terpilih
            if (initialProvId) {
                updateKabupatenOptions(initialProvId, initialKabId);
            } else {
                kabSelect.disabled = true;
                kabSelect.style.backgroundColor = '#f3f4f6';
            }

            // Event listener change provinsi
            provSelect.addEventListener('change', (e) => {
                updateKabupatenOptions(e.target.value);
            });

            // Fokus ke nama perusahaan
            const nameInput = document.getElementById('swal-company-name');
            if (nameInput) nameInput.focus();
        },
        preConfirm: async () => {
            const isLuar = document.getElementById('swal-lokasi-luar')?.checked;
            const jenisLokasi = isLuar ? 'Luar Negeri' : 'Dalam Negeri';
            const nama = document.getElementById('swal-company-name')?.value?.trim();
            const negara = document.getElementById('swal-company-negara')?.value?.trim();
            const provId = document.getElementById('swal-company-province')?.value;
            const kabId = document.getElementById('swal-company-kabupaten')?.value;
            const kodepos = document.getElementById('swal-company-kodepos')?.value?.trim();
            const skala = document.getElementById('swal-company-skala')?.value || (isLuar ? 'Internasional' : 'Lokal');
            const alamat = document.getElementById('swal-company-alamat')?.value?.trim() || '';
            const jenisPerusahaan = document.getElementById('swal-company-jenis')?.value || 'Perusahaan swasta';
            const jenisPerusahaanLainnya = document.getElementById('swal-company-jenis-lainnya')?.value?.trim() || null;

            if (!nama) {
                Swal.showValidationMessage('Nama Perusahaan / Instansi wajib diisi.');
                return false;
            }

            if (isLuar) {
                if (!negara || negara === 'Indonesia' || !foreignCountries.some(n => n.nama_negara === negara)) {
                    Swal.showValidationMessage('Silakan pilih salah satu negara di luar negeri dari daftar master resmi.');
                    return false;
                }
            } else {
                if (!provId) {
                    Swal.showValidationMessage('Provinsi Perusahaan wajib dipilih.');
                    return false;
                }
                if (!kabId) {
                    Swal.showValidationMessage('Kabupaten / Kota Perusahaan wajib dipilih.');
                    return false;
                }
            }

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch('/alumni/company', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        nama_perusahaan: nama,
                        jenis_lokasi: jenisLokasi,
                        negara: isLuar ? negara : 'Indonesia',
                        province_id: isLuar ? null : provId,
                        kabupaten_id: isLuar ? null : kabId,
                        kode_pos: kodepos,
                        skala: skala,
                        jenis_perusahaan: jenisPerusahaan,
                        jenis_perusahaan_lainnya: jenisPerusahaanLainnya,
                        alamat: alamat
                    })
                });

                const data = await res.json();
                if (!res.ok || !data.success || !data.company) {
                    Swal.showValidationMessage(data.message || 'Gagal menyimpan data perusahaan.');
                    return false;
                }
                return data.company;
            } catch (error) {
                Swal.showValidationMessage('Gagal menghubungi server. Silakan coba lagi.');
                return false;
            }
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            const newCompany = result.value;
            // Masukkan ke daftar options
            localCompanies.value.unshift(newCompany);

            // Set ke form profil
            props.form.nama_perusahaan = newCompany.nama_perusahaan;
            props.form.company_jenis_lokasi = newCompany.jenis_lokasi || (newCompany.negara && newCompany.negara !== 'Indonesia' ? 'Luar Negeri' : 'Dalam Negeri');
            props.form.perusahaan_jenis_lokasi = props.form.company_jenis_lokasi;
            props.form.company_negara = newCompany.negara || (props.form.company_jenis_lokasi === 'Luar Negeri' ? '' : 'Indonesia');
            props.form.perusahaan_negara = props.form.company_negara;
            props.form.company_province_id = newCompany.province_id;
            props.form.company_kabupaten_id = newCompany.kabupaten_id;
            props.form.company_alamat = newCompany.alamat || '';
            props.form.company_skala = newCompany.skala || 'Lokal';
            props.form.zipcode = newCompany.kode_pos || '';
            props.form.company_jenis_perusahaan = newCompany.jenis_perusahaan || '';
            props.form.perusahaan_jenis_perusahaan = props.form.company_jenis_perusahaan;
            props.form.company_jenis_perusahaan_lainnya = newCompany.jenis_perusahaan_lainnya || '';
            props.form.perusahaan_jenis_perusahaan_lainnya = props.form.company_jenis_perusahaan_lainnya;
            props.form.company_status_verifikasi = 'Menunggu Verifikasi';

            Swal.fire({
                icon: 'success',
                title: 'Berhasil Ditambahkan!',
                text: `Perusahaan "${newCompany.nama_perusahaan}" berhasil ditambahkan dan dipilih.`,
                confirmButtonColor: '#005B3C',
                timer: 2500,
                timerProgressBar: true
            });
        }
    });
};

// ============================================================================
// CLICK OUTSIDE HANDLER
// ============================================================================
const handleClickOutside = (event) => {
    if (provinsiContainerRef.value && !provinsiContainerRef.value.contains(event.target)) {
        showProvinsiDropdown.value = false;
    }
    if (kabupatenContainerRef.value && !kabupatenContainerRef.value.contains(event.target)) {
        showKabupatenDropdown.value = false;
    }
    if (negaraContainerRef.value && !negaraContainerRef.value.contains(event.target)) {
        showNegaraDropdown.value = false;
    }
    if (companyContainerRef.value && !companyContainerRef.value.contains(event.target)) {
        showCompanyDropdown.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="space-y-8">
        
        <!-- Bagian: Sosial Media & Profesional -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-gray-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">
                Media Sosial & Profesional
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label :class="labelClass">LinkedIn Profil URL</label>
                    <input type="url" v-model="form.linkedin_url" :class="inputClass" placeholder="https://linkedin.com/in/..." />
                </div>
                <div>
                    <label :class="labelClass">LinkedIn Username</label>
                    <input type="text" v-model="form.linkedin_username" :class="inputClass" placeholder="username_linkedin" />
                </div>

                <div>
                    <label :class="labelClass">Instagram Profil URL</label>
                    <input type="url" v-model="form.instagram_url" :class="inputClass" placeholder="https://instagram.com/..." />
                </div>
                <div>
                    <label :class="labelClass">Facebook Profil URL</label>
                    <input type="url" v-model="form.facebook_url" :class="inputClass" placeholder="https://facebook.com/..." />
                </div>
                
                <div class="md:col-span-2">
                    <label :class="labelClass">Bidang Keahlian (Expertise)</label>
                    <input type="text" v-model="form.expert" :class="inputClass" placeholder="Contoh: Software Engineering, Data Science..." />
                </div>
                <div class="md:col-span-2">
                    <label :class="labelClass">Minat & Ketertarikan</label>
                    <input type="text" v-model="form.minat" :class="inputClass" placeholder="Contoh: Artificial Intelligence, Cloud Computing..." />
                </div>

                <!-- Pilihan Kategori Peran Kerja (Pekerja vs Wiraswasta/Founder) -->
                <div class="md:col-span-2">
                    <label :class="labelClass">Kategori Peran Kerja Saat Ini</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-2">
                        <div 
                            @click="form.kategori_pekerjaan = 'Pekerja'"
                            class="p-4 rounded-xl border-2 cursor-pointer transition-all flex items-start gap-3"
                            :class="form.kategori_pekerjaan !== 'Wiraswasta' ? 'border-[#005B3C] bg-emerald-50/50 text-gray-900 shadow-xs ring-1 ring-[#005B3C]/20' : 'border-gray-200 bg-white hover:border-gray-300 text-gray-600'"
                        >
                            <span class="text-2xl shrink-0">🏢</span>
                            <div>
                                <div class="font-bold text-sm" :class="form.kategori_pekerjaan !== 'Wiraswasta' ? 'text-[#005B3C]' : 'text-gray-800'">Pekerja / Karyawan / Profesional</div>
                                <div class="text-xs text-gray-500 mt-0.5">Bekerja di instansi pemerintah, swasta, BUMN, LSM, atau institusi lain</div>
                            </div>
                        </div>

                        <div 
                            @click="form.kategori_pekerjaan = 'Wiraswasta'"
                            class="p-4 rounded-xl border-2 cursor-pointer transition-all flex items-start gap-3"
                            :class="form.kategori_pekerjaan === 'Wiraswasta' ? 'border-[#005B3C] bg-emerald-50/50 text-gray-900 shadow-xs ring-1 ring-[#005B3C]/20' : 'border-gray-200 bg-white hover:border-gray-300 text-gray-600'"
                        >
                            <span class="text-2xl shrink-0">🚀</span>
                            <div>
                                <div class="font-bold text-sm" :class="form.kategori_pekerjaan === 'Wiraswasta' ? 'text-[#005B3C]' : 'text-gray-800'">Wirausaha / Founder / Startup</div>
                                <div class="text-xs text-gray-500 mt-0.5">Mendirikan bisnis sendiri, founder/co-founder startup, pengelola usaha mandiri, atau freelance</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jika Karyawan/Pekerja: Posisi Jabatan Struktural (F2G) -->
                <div v-if="form.kategori_pekerjaan !== 'Wiraswasta'" class="md:col-span-2">
                    <label :class="labelClass">Posisi Jabatan Struktural (F2G)</label>
                    <select v-model="form.posisi_jabatan" :class="inputClass">
                        <option value="">-- Pilih Posisi Jabatan --</option>
                        <option value="Direksi">1 - Direksi</option>
                        <option value="Top Manager">2 - Top Manager</option>
                        <option value="Middle Manager">3 - Middle Manager</option>
                        <option value="Low Manager">4 - Low Manager</option>
                        <option value="Supervisor">5 - Supervisor</option>
                        <option value="Staff">6 - Staff</option>
                    </select>
                </div>

                <!-- Jika Wiraswasta: Posisi / Jabatan Wiraswasta (F5C) -->
                <div v-else class="md:col-span-2 space-y-3">
                    <div>
                        <label :class="labelClass">Posisi / Jabatan Wiraswasta & Startup (F5C)</label>
                        <select v-model="form.posisi_wiraswasta" :class="inputClass">
                            <option value="">-- Pilih Posisi / Jabatan Wiraswasta --</option>
                            <option value="Owner">Owner</option>
                            <option value="Founder">Founder</option>
                            <option value="Co-Founder">Co-Founder</option>
                            <option value="Direktur Utama">Direktur Utama</option>
                            <option value="Pengelola Usaha">Pengelola Usaha</option>
                            <option value="Freelancer / Konsultan Mandiri">Freelancer / Konsultan Mandiri</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <!-- Input Tambahan jika memilih Lainnya -->
                    <div v-if="form.posisi_wiraswasta === 'Lainnya'">
                        <label :class="labelClass">Sebutkan Jabatan Wiraswasta Lainnya</label>
                        <input 
                            type="text" 
                            v-model="form.posisi_wiraswasta_lainnya" 
                            :class="inputClass" 
                            placeholder="Contoh: Managing Partner, Content Creator..." 
                        />
                    </div>
                </div>

                <!-- Input Penghasilan / Take Home Pay (Format Langsung di dalam Text Box) -->
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between mb-1">
                        <label :class="labelClass" class="!mb-0">
                            Rata-rata Pendapatan per Bulan (Take Home Pay)
                        </label>
                        <span v-if="form.gaji" class="text-xs text-[#005B3C] font-black inline-flex items-center gap-1">
                            Terbaca: <strong>{{ formatRupiah(form.gaji) }}</strong> / bulan
                        </span>
                    </div>
                    <div class="relative flex items-center rounded-xl bg-white border border-gray-200 shadow-xs focus-within:ring-2 focus-within:ring-[#005B3C] focus-within:border-transparent transition-all overflow-hidden">
                        <span class="pl-4 pr-1 font-bold text-gray-400 select-none text-base">Rp</span>
                        <input 
                            type="text" 
                            :value="formattedGajiString"
                            @input="handleGajiInput"
                            placeholder="Contoh: 5.000.000" 
                            class="w-full py-3.5 px-3 bg-transparent font-black font-mono border-0 focus:ring-0 text-base sm:text-lg text-gray-900 placeholder-gray-300 tracking-wide" 
                        />
                        <span class="pr-4 text-xs font-semibold text-gray-400 select-none">/ bulan</span>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">
                        * Ketik angka depan (misal ketik 5 untuk 5.000) atau nominal lengkap (5.000.000). Format titik ribuan tertata otomatis dan angka 000 dapat diedit langsung.
                    </p>
                </div>

                <!-- Khusus Alumni Teologi / Filsafat Keilahian (Kode 31) -->
                <div v-if="alumniData?.prodi?.kode_prodi === '31' || alumniData?.nim?.startsWith('31')" class="md:col-span-2 bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <label :class="labelClass">Jenis Pekerjaan (Khusus Alumni Filsafat Keilahian)</label>
                    <div class="flex gap-6 mt-2">
                        <label class="flex items-center space-x-2 cursor-pointer text-sm font-medium text-gray-700">
                            <input type="radio" v-model="form.jenis_pekerjaan" value="Gerejawi" class="text-[#005B3C] focus:ring-[#005B3C]">
                            <span>Gerejawi</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer text-sm font-medium text-gray-700">
                            <input type="radio" v-model="form.jenis_pekerjaan" value="Non Gerejawi" class="text-[#005B3C] focus:ring-[#005B3C]">
                            <span>Non Gerejawi</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bagian: Data Perusahaan Saat Ini -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 mb-6 border-b border-gray-100 gap-3">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">
                        Data Perusahaan / Tempat Bekerja
                    </h2>
                    <p class="text-xs text-gray-400 mt-0.5">Informasi tempat kerja saat ini (Dalam Negeri atau Luar Negeri)</p>
                </div>

                <!-- Pilihan Lokasi Perusahaan: Dalam Negeri / Luar Negeri -->
                <div class="flex items-center gap-2 bg-gray-100/80 p-1.5 rounded-xl border border-gray-200">
                    <button 
                        type="button" 
                        @click="form.company_jenis_lokasi = 'Dalam Negeri'" 
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5"
                        :class="form.company_jenis_lokasi === 'Dalam Negeri' ? 'bg-[#005B3C] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-white/60'"
                    >
                        <span>🇮🇩 Dalam Negeri</span>
                    </button>
                    <button 
                        type="button" 
                        @click="form.company_jenis_lokasi = 'Luar Negeri'" 
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5"
                        :class="form.company_jenis_lokasi === 'Luar Negeri' ? 'bg-[#005B3C] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-white/60'"
                    >
                        <span>✈️ Luar Negeri</span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                
                <!-- Jika Luar Negeri: Searchable Dropdown Negara (Sesuai Template Search Provinsi/Kabupaten) -->
                <div v-if="form.company_jenis_lokasi === 'Luar Negeri'" class="md:col-span-2 relative" ref="negaraContainerRef">
                    <label :class="labelClass">
                        Negara Tempat Bekerja <span class="text-red-500">*</span>
                    </label>
                    
                    <div 
                        @click="showNegaraDropdown = !showNegaraDropdown; if (showNegaraDropdown) searchNegaraQuery = '';"
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium cursor-pointer flex justify-between items-center shadow-xs hover:border-gray-300 transition-colors"
                    >
                        <span v-if="selectedNegaraName" class="text-gray-900 font-semibold flex items-center gap-2">
                            <span>✈️</span>
                            <span>{{ selectedNegaraName }}</span>
                        </span>
                        <span v-else class="text-gray-400">Pilih Negara di Luar Negeri...</span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0 ml-2 transition-transform duration-200" :class="{'rotate-180': showNegaraDropdown}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>

                    <!-- Dropdown Menu Searchable Negara (Gaya Template Provinsi) -->
                    <div v-if="showNegaraDropdown" class="absolute z-50 w-full mt-1.5 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                        <div class="p-2.5 border-b border-gray-100 bg-gray-50/80">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <input 
                                    type="text" 
                                    v-model="searchNegaraQuery" 
                                    class="w-full pl-9 pr-3 py-1.5 border border-gray-200 rounded-lg text-sm bg-white focus:ring-1 focus:ring-[#005B3C] focus:border-[#005B3C] outline-none"
                                    placeholder="Ketik untuk mencari negara..."
                                    autofocus
                                >
                            </div>
                        </div>
                        <ul class="max-h-56 overflow-y-auto divide-y divide-gray-50">
                            <li 
                                v-for="neg in filteredNegaras" 
                                :key="neg.id" 
                                @click="selectNegara(neg)" 
                                class="px-4 py-2.5 hover:bg-green-50 hover:text-[#005B3C] cursor-pointer text-sm font-medium transition-colors flex items-center justify-between"
                                :class="{'bg-green-50 text-[#005B3C] font-semibold': form.company_negara === neg.nama_negara}"
                            >
                                <div class="flex items-center gap-2">
                                    <span>{{ neg.nama_negara }}</span>
                                    <span v-if="neg.kode_iso2" class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 uppercase">{{ neg.kode_iso2 }}</span>
                                </div>
                                <div class="text-xs text-gray-400 font-normal">
                                    {{ neg.benua || 'Luar Negeri' }} <span v-if="neg.ibu_kota">• {{ neg.ibu_kota }}</span>
                                </div>
                            </li>
                            <li v-if="filteredNegaras.length === 0" class="px-4 py-6 text-center text-sm text-gray-400">
                                <div>Negara tidak ditemukan dalam daftar master resmi negara dunia.</div>
                                <div class="text-xs text-gray-400 mt-1">Hanya negara yang terdaftar dalam master data yang dapat dipilih.</div>
                            </li>
                        </ul>
                    </div>

                    <p class="text-[11px] text-gray-400 mt-1">
                        * Pilihan negara dibatasi pada daftar master negara resmi dunia (Indonesia tidak termasuk kategori Luar Negeri).
                    </p>
                </div>

                <!-- Jika Dalam Negeri: Dropdown Provinsi & Kabupaten/Kota -->
                <template v-else>
                    <!-- Searchable Dropdown: Provinsi Perusahaan -->
                    <div class="relative" ref="provinsiContainerRef">
                        <label :class="labelClass">Provinsi Perusahaan</label>
                        
                        <div 
                            @click="showProvinsiDropdown = !showProvinsiDropdown; if (showProvinsiDropdown) searchProvinsiQuery = '';"
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium cursor-pointer flex justify-between items-center shadow-xs hover:border-gray-300 transition-colors"
                        >
                            <span v-if="selectedProvinceName" class="text-gray-900 font-medium">{{ selectedProvinceName }}</span>
                            <span v-else class="text-gray-400">Pilih Provinsi...</span>
                            <svg class="w-4 h-4 text-gray-400 shrink-0 ml-2 transition-transform duration-200" :class="{'rotate-180': showProvinsiDropdown}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>

                        <!-- Dropdown Menu (Gaya Gambar 2) -->
                        <div v-if="showProvinsiDropdown" class="absolute z-50 w-full mt-1.5 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                            <div class="p-2.5 border-b border-gray-100 bg-gray-50/80">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    </div>
                                    <input 
                                        type="text" 
                                        v-model="searchProvinsiQuery" 
                                        class="w-full pl-9 pr-3 py-1.5 border border-gray-200 rounded-lg text-sm bg-white focus:ring-1 focus:ring-[#005B3C] focus:border-[#005B3C] outline-none"
                                        placeholder="Ketik untuk mencari..."
                                        autofocus
                                    >
                                </div>
                            </div>
                            <ul class="max-h-52 overflow-y-auto">
                                <li 
                                    v-for="prov in filteredProvinces" 
                                    :key="prov.id" 
                                    @click="selectProvinsi(prov)" 
                                    class="px-4 py-2.5 hover:bg-green-50 hover:text-[#005B3C] cursor-pointer text-sm font-medium transition-colors border-b border-gray-50 last:border-b-0"
                                    :class="{'bg-green-50 text-[#005B3C] font-semibold': form.company_province_id == prov.id}"
                                >
                                    {{ prov.nama_provinsi }}
                                </li>
                                <li v-if="filteredProvinces.length === 0" class="px-4 py-4 text-center text-sm text-gray-400">
                                    Tidak ada hasil yang cocok.
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Searchable Dropdown: Kabupaten / Kota Perusahaan -->
                    <div class="relative" ref="kabupatenContainerRef">
                        <label :class="labelClass">Kabupaten / Kota Perusahaan</label>
                        
                        <div 
                            @click="if (form.company_province_id) { showKabupatenDropdown = !showKabupatenDropdown; if (showKabupatenDropdown) searchKabupatenQuery = ''; }"
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium cursor-pointer flex justify-between items-center shadow-xs hover:border-gray-300 transition-colors"
                            :class="{'opacity-60 cursor-not-allowed bg-gray-50': !form.company_province_id}"
                        >
                            <span v-if="selectedKabupatenName" class="text-gray-900 font-medium">{{ selectedKabupatenName }}</span>
                            <span v-else class="text-gray-400">
                                {{ form.company_province_id ? 'Pilih Kabupaten/Kota...' : 'Pilih Provinsi terlebih dahulu' }}
                            </span>
                            <svg class="w-4 h-4 text-gray-400 shrink-0 ml-2 transition-transform duration-200" :class="{'rotate-180': showKabupatenDropdown}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>

                        <!-- Dropdown Menu (Gaya Gambar 2) -->
                        <div v-if="showKabupatenDropdown && form.company_province_id" class="absolute z-50 w-full mt-1.5 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                            <div class="p-2.5 border-b border-gray-100 bg-gray-50/80">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    </div>
                                    <input 
                                        type="text" 
                                        v-model="searchKabupatenQuery" 
                                        class="w-full pl-9 pr-3 py-1.5 border border-gray-200 rounded-lg text-sm bg-white focus:ring-1 focus:ring-[#005B3C] focus:border-[#005B3C] outline-none"
                                        placeholder="Ketik untuk mencari..."
                                        autofocus
                                    >
                                </div>
                            </div>
                            <ul class="max-h-52 overflow-y-auto">
                                <li 
                                    v-for="kab in filteredKabupatens" 
                                    :key="kab.id" 
                                    @click="selectKabupaten(kab)" 
                                    class="px-4 py-2.5 hover:bg-green-50 hover:text-[#005B3C] cursor-pointer text-sm font-medium transition-colors border-b border-gray-50 last:border-b-0"
                                    :class="{'bg-green-50 text-[#005B3C] font-semibold': form.company_kabupaten_id == kab.id}"
                                >
                                    {{ kab.nama_kabupaten }}
                                </li>
                                <li v-if="filteredKabupatens.length === 0" class="px-4 py-4 text-center text-sm text-gray-400">
                                    Tidak ada hasil yang cocok.
                                </li>
                            </ul>
                        </div>
                    </div>
                </template>

                <!-- Searchable Dropdown: Nama Perusahaan / Instansi (PERSIS GAMBAR 2) -->
                <div class="md:col-span-2 relative" ref="companyContainerRef">
                    <label :class="labelClass">Nama Perusahaan / Instansi</label>
                    <div class="flex items-center gap-2">
                        <!-- Trigger Box bergaya Dropdown seperti Gambar 2 -->
                        <div 
                            @click="showCompanyDropdown = !showCompanyDropdown; if (showCompanyDropdown) searchCompanyQuery = '';"
                            class="flex-1 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium cursor-pointer flex justify-between items-center shadow-xs hover:border-gray-300 transition-colors"
                        >
                            <span v-if="form.nama_perusahaan" class="text-gray-900 font-semibold truncate">{{ form.nama_perusahaan }}</span>
                            <span v-else class="text-gray-400">Pilih nama perusahaan / instansi...</span>
                            <svg class="w-4 h-4 text-gray-400 shrink-0 ml-2 transition-transform duration-200" :class="{'rotate-180': showCompanyDropdown}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>

                        <!-- Tombol Tambah Perusahaan Baru -->
                        <button 
                            type="button" 
                            @click="openAddCompanyModal" 
                            class="px-4 py-3 bg-gray-900 text-white rounded-xl hover:bg-black transition-colors shrink-0 text-xs font-semibold flex items-center gap-1 shadow-xs" 
                            title="Tambah Perusahaan Baru"
                        >
                            <span>+ Tambah</span>
                        </button>
                    </div>

                    <!-- Dropdown Menu (Persis Gambar 2: Search Bar di atas + List Opsi) -->
                    <div v-if="showCompanyDropdown" class="absolute z-50 w-full mt-1.5 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                        <!-- Search Bar -->
                        <div class="p-2.5 border-b border-gray-100 bg-gray-50/80">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <input 
                                    type="text" 
                                    v-model="searchCompanyQuery" 
                                    class="w-full pl-9 pr-3 py-1.5 border border-gray-200 rounded-lg text-sm bg-white focus:ring-1 focus:ring-[#005B3C] focus:border-[#005B3C] outline-none"
                                    placeholder="Ketik untuk mencari..."
                                    autofocus
                                >
                            </div>
                        </div>

                        <!-- Options List -->
                        <ul class="max-h-56 overflow-y-auto">
                            <li 
                                v-for="company in filteredCompanies" 
                                :key="company.id" 
                                @click="selectCompany(company)" 
                                class="px-4 py-3 hover:bg-green-50 hover:text-[#005B3C] cursor-pointer text-sm font-medium transition-colors border-b border-gray-50 last:border-b-0 flex justify-between items-center"
                                :class="{'bg-green-50 text-[#005B3C] font-semibold': form.nama_perusahaan === company.nama_perusahaan}"
                            >
                                <div>
                                    <span class="block text-gray-800" :class="{'text-[#005B3C] font-bold': form.nama_perusahaan === company.nama_perusahaan}">
                                        {{ company.nama_perusahaan }}
                                    </span>
                                    <span class="text-xs text-gray-400" v-if="company.jenis_lokasi === 'Luar Negeri' || (company.negara && company.negara !== 'Indonesia')">
                                        ✈️ {{ company.negara || 'Luar Negeri' }}
                                    </span>
                                    <span class="text-xs text-gray-400" v-else-if="company.province_id">
                                        🇮🇩 {{ provinces.find(p => p.id == company.province_id)?.nama_provinsi || '' }}
                                        {{ company.kabupaten_id ? ' - ' + (kabupatens.find(k => k.id == company.kabupaten_id)?.nama_kabupaten || '') : '' }}
                                    </span>
                                </div>
                                <span v-if="company.status_verifikasi === 'Terverifikasi'" class="text-xs text-emerald-600 font-semibold shrink-0 ml-2">
                                    Terverifikasi
                                </span>
                                <span v-else class="text-xs text-amber-600 font-semibold shrink-0 ml-2">
                                    Menunggu Verifikasi
                                </span>
                            </li>
                            <li v-if="filteredCompanies.length === 0" class="px-4 py-5 text-center text-sm text-gray-500">
                                <div>Tidak ada perusahaan yang cocok.</div>
                                <button type="button" @click="openAddCompanyModal" class="mt-2 text-xs font-semibold text-[#005B3C] underline hover:text-green-800">
                                    + Tambah Perusahaan Baru
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Jenis Perusahaan / Instansi (F11 Standar Dikti) -->
                <div class="md:col-span-2">
                    <label :class="labelClass">Jenis Perusahaan / Instansi (F11)</label>
                    <select 
                        v-model="form.company_jenis_perusahaan" 
                        @change="form.perusahaan_jenis_perusahaan = form.company_jenis_perusahaan" 
                        :class="inputClass"
                    >
                        <option value="">-- Pilih Jenis Instansi / Perusahaan --</option>
                        <option value="Instansi pemerintah">1 - Instansi pemerintah</option>
                        <option value="Organisasi non-profit/Lembaga Swadaya Masyarakat">2 - Organisasi non-profit / Lembaga Swadaya Masyarakat (LSM)</option>
                        <option value="Perusahaan swasta">3 - Perusahaan swasta</option>
                        <option value="Wiraswasta/perusahaan sendiri">4 - Wiraswasta / Perusahaan sendiri</option>
                        <option value="BUMN/BUMD">6 - BUMN / BUMD</option>
                        <option value="Institusi/Organisasi Multilateral">7 - Institusi / Organisasi Multilateral</option>
                        <option value="Lainnya">5 - Lainnya</option>
                    </select>
                </div>

                <!-- Input Isian Bebas jika memilih Lainnya -->
                <div v-if="form.company_jenis_perusahaan === 'Lainnya'" class="md:col-span-2">
                    <label :class="labelClass">Sebutkan Jenis Instansi Lainnya</label>
                    <input 
                        type="text" 
                        v-model="form.company_jenis_perusahaan_lainnya" 
                        @input="form.perusahaan_jenis_perusahaan_lainnya = form.company_jenis_perusahaan_lainnya"
                        :class="inputClass" 
                        placeholder="Contoh: Lembaga Riset Independen, Startup Komunitas..." 
                    />
                </div>

                <div class="md:col-span-2">
                    <label :class="labelClass">Skala Perusahaan / Instansi (F2H / F5D)</label>
                    <select v-model="form.company_skala" :class="inputClass">
                        <option value="">-- Pilih Skala Perusahaan --</option>
                        <option value="Lokal">Lokal</option>
                        <option value="Nasional">Nasional</option>
                        <option value="Internasional">Internasional</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label :class="labelClass">Alamat Perusahaan</label>
                    <input type="text" v-model="form.company_alamat" :class="inputClass" placeholder="Contoh: Pacific Building, Jl. Laksda Adisucipto No. 157, Sleman" />
                </div>

                <div class="md:col-span-2">
                    <label :class="labelClass">Kode Pos Perusahaan (Zipcode)</label>
                    <input type="text" v-model="form.zipcode" :class="inputClass" placeholder="Kode Pos (Cth: 55281)" />
                </div>
            </div>
        </div>

        <!-- Bagian: Data Atasan -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-gray-100">
            <h2 class="text-xl font-bold text-gray-900 mb-2">
                Data Atasan Langsung
            </h2>
            <p class="text-xs text-gray-500 mb-6">
                Data pimpinan/atasan digunakan untuk keperluan survei evaluasi kepuasan pengguna lulusan oleh universitas.
            </p>

            <!-- Banner Auto-fill Owner / Founder / Wiraswasta -->
            <div v-if="isOwner" class="bg-emerald-50 border border-emerald-200 text-[#005B3C] rounded-xl p-3.5 text-xs flex items-center gap-2.5 mb-5 font-medium shadow-2xs">
                <span class="text-base">💡</span>
                <span>Karena Anda memilih posisi <strong>Owner / Wiraswasta</strong>, kolom Data Atasan Langsung di bawah ini otomatis diisi dengan data diri Anda.</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label :class="labelClass">Nama Lengkap Atasan</label>
                    <input type="text" v-model="form.nama_atasan" :class="inputClass" placeholder="Contoh: Ir. Bambang Trihatmojo" />
                </div>
                
                <div>
                    <label :class="labelClass">Email Atasan</label>
                    <input type="email" v-model="form.email_atasan" :class="inputClass" placeholder="atasan@perusahaan.co.id" />
                </div>

                <div>
                    <label :class="labelClass">Nomor Telepon Atasan</label>
                    <input type="text" v-model="form.telepon_atasan" :class="inputClass" placeholder="081234567890" />
                </div>
            </div>
        </div>

    </div>
</template>
