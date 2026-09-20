<!--
  Komponen Anak (Child Component): Form Karier & Riwayat Pekerjaan
  File: resources/js/Pages/Alumni/Profil/Components/FormKarier.vue
  
  Deskripsi:
  Mengelola data karier alumni, mencakup peran/aktivitas saat ini (Karyawan, Wirausaha, Melanjutkan Pendidikan),
  data perusahaan/instansi (lokasi dalam/luar negeri, pencarian master negara/provinsi/kabupaten, dan penambahan perusahaan),
  nominal penghasilan bulanan, serta data kontak atasan langsung.
-->
<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import Swal from 'sweetalert2';

// ============================================================================
// 1. PROPS DARI PARENT (Index.vue)
// ============================================================================
const props = defineProps({
    form: Object,
    provinces: Array,
    kabupatens: Array,
    negaras: Array,
    companies: Array,
    alumniData: Object,
    refOptions: Object,
});

// Class styling reusable untuk form controls
const inputClass = "block w-full border border-gray-200 bg-white rounded-xl shadow-xs focus:border-[#005B3C] focus:ring focus:ring-[#005B3C]/10 px-4 py-3 text-sm text-gray-800 font-medium transition-colors";
const labelClass = "block text-sm font-semibold text-gray-700 mb-1.5";

// Container refs untuk mendeteksi klik di luar area dropdown (click-outside)
const provinsiContainerRef = ref(null);
const kabupatenContainerRef = ref(null);
const negaraContainerRef = ref(null);
const companyContainerRef = ref(null);

// ============================================================================
// 2. OPSI DINAMIS DARI DATABASE (ref_subpertanyaan_detil)
// ============================================================================

// Pilihan Posisi Jabatan Struktural (F2G)
const posisiJabatanOptions = computed(() => {
    if (props.refOptions?.F2G && props.refOptions.F2G.length > 0) {
        return props.refOptions.F2G.map(opt => ({
            value: opt.option_text,
            label: `${opt.kode_opsi} - ${opt.option_text}`
        }));
    }
    return [
        { value: 'Direksi', label: '1 - Direksi' },
        { value: 'Top Manager', label: '2 - Top Manager' },
        { value: 'Middle Manager', label: '3 - Middle Manager' },
        { value: 'Low Manager', label: '4 - Low Manager' },
        { value: 'Supervisor', label: '5 - Supervisor' },
        { value: 'Staff', label: '6 - Staff' },
    ];
});

// Pilihan Jenis Instansi / Perusahaan (F11)
const jenisPerusahaanOptions = computed(() => {
    if (props.refOptions?.F11 && props.refOptions.F11.length > 0) {
        return props.refOptions.F11.map(opt => ({
            value: opt.option_text,
            label: `${opt.kode_opsi} - ${opt.option_text}`
        }));
    }
    return [
        { value: 'Instansi pemerintah', label: '1 - Instansi pemerintah' },
        { value: 'Organisasi non-profit/Lembaga Swadaya Masyarakat', label: '2 - Organisasi non-profit / LSM' },
        { value: 'Perusahaan swasta', label: '3 - Perusahaan swasta' },
        { value: 'Wiraswasta/perusahaan sendiri', label: '4 - Wiraswasta / Perusahaan sendiri' },
        { value: 'BUMN/BUMD', label: '6 - BUMN / BUMD' },
        { value: 'Institusi/Organisasi Multilateral', label: '7 - Institusi / Organisasi Multilateral' },
        { value: 'Lainnya', label: '5 - Lainnya' },
    ];
});

// Pilihan Skala Perusahaan (F2H / F5D)
const skalaOptions = computed(() => {
    if (props.refOptions?.F2H && props.refOptions.F2H.length > 0) {
        return props.refOptions.F2H.map(opt => ({
            value: opt.option_text,
            label: opt.option_text
        }));
    }
    return [
        { value: 'Regional/Lokal', label: 'Regional / Lokal' },
        { value: 'Nasional', label: 'Nasional' },
        { value: 'Internasional', label: 'Internasional' },
    ];
});

// ============================================================================
// 3. LOGIKA PERAN & OTOMATISASI DATA ATASAN
// ============================================================================
const isOwner = computed(() => {
    if (props.form.kategori_pekerjaan === 'Wiraswasta') return true;
    const pos = (props.form.posisi_jabatan || '').toLowerCase();
    const posWira = (props.form.posisi_wiraswasta || '').toLowerCase();
    return ['owner', 'founder', 'wiraswasta', 'wirausaha', 'wiraswasta / wirausaha', 'owner / founder'].includes(pos) || posWira !== '';
});

// Cek apakah perusahaan tempat bekerja sudah berstatus Terverifikasi resmi
const isVerifiedCompany = computed(() => {
    return props.form.company_status_verifikasi === 'Terverifikasi' || props.form.perusahaan_status_verifikasi === 'Terverifikasi';
});

// Alumni hanya boleh mengedit detail perusahaan (alamat, skala, jenis, kode pos) jika belum terverifikasi atau jika bertindak sebagai Owner / Wiraswasta
const canEditCompanyDetails = computed(() => {
    return !isVerifiedCompany.value || isOwner.value;
});

// Watcher sinkronisasi dua arah field perusahaan agar tersimpan sempurna ke backend
watch(() => props.form.company_skala, (newVal) => {
    props.form.perusahaan_skala = newVal;
});
watch(() => props.form.perusahaan_skala, (newVal) => {
    if (newVal && !props.form.company_skala) props.form.company_skala = newVal;
});
watch(() => props.form.company_alamat, (newVal) => {
    props.form.perusahaan_alamat = newVal;
});
watch(() => props.form.perusahaan_alamat, (newVal) => {
    if (newVal && !props.form.company_alamat) props.form.company_alamat = newVal;
});
watch(() => props.form.company_jenis_perusahaan, (newVal) => {
    props.form.perusahaan_jenis_perusahaan = newVal;
});
watch(() => props.form.company_jenis_perusahaan_lainnya, (newVal) => {
    props.form.perusahaan_jenis_perusahaan_lainnya = newVal;
});

// Mengisi data atasan otomatis dengan kontak alumni jika pemilik usaha
const autoFillAtasanOwner = () => {
    props.form.nama_atasan = props.form.nama || '';
    props.form.email_atasan = props.form.email_pribadi || props.form.email || '';
    props.form.telepon_atasan = props.form.nomor_telepon || '';
};

// Pantau perubahan kategori aktivitas alumni
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
    } else if (newVal === 'Melanjutkan Pendidikan') {
        props.form.posisi_wiraswasta = '';
        props.form.posisi_jabatan = '';
    }
});

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

// ============================================================================
// 4. FORMATTING NOMINAL PENGHASILAN (GAJI)
// ============================================================================
const formatRupiah = (val) => {
    if (!val && val !== 0) return 'Rp 0';
    let num = Number(val);
    if (isNaN(num) || num <= 0) return 'Rp 0';
    const actualNominal = num < 1000000 ? num * 1000 : num;
    return 'Rp ' + actualNominal.toLocaleString('id-ID');
};

const formatNominalDisplay = (val) => {
    if (!val && val !== 0) return '';
    const clean = String(val).replace(/[^0-9]/g, '');
    if (!clean) return '';
    const num = Number(clean);
    return isNaN(num) || num === 0 ? '' : new Intl.NumberFormat('id-ID').format(num);
};

const formattedGajiString = ref(formatNominalDisplay(props.form.gaji));

watch(() => props.form.gaji, (newVal) => {
    formattedGajiString.value = formatNominalDisplay(newVal);
});

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
    if (num > 0 && num < 1000 && !raw.includes('000')) {
        num = num * 1000;
    }
    props.form.gaji = num;
    formattedGajiString.value = new Intl.NumberFormat('id-ID').format(num);
    e.target.value = formattedGajiString.value;
};

// ============================================================================
// 5. DATA PERUSAHAAN & LOKASI
// ============================================================================
const localCompanies = ref([...(props.companies || [])]);
watch(() => props.companies, (newVal) => {
    if (newVal) localCompanies.value = [...newVal];
}, { deep: true });

// Inisialisasi default lokasi perusahaan
if (!props.form.company_jenis_lokasi && !props.form.perusahaan_jenis_lokasi) {
    props.form.company_jenis_lokasi = 'Dalam Negeri';
    props.form.perusahaan_jenis_lokasi = 'Dalam Negeri';
}
if (!props.form.company_negara && !props.form.perusahaan_negara) {
    props.form.company_negara = props.form.company_jenis_lokasi === 'Luar Negeri' ? '' : 'Indonesia';
    props.form.perusahaan_negara = props.form.company_negara;
}

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

// Dropdown Provinsi
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
    props.form.company_kabupaten_id = '';
    showProvinsiDropdown.value = false;
    searchProvinsiQuery.value = '';
};

// Dropdown Kabupaten
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

// Dropdown Negara Luar Negeri
const showNegaraDropdown = ref(false);
const searchNegaraQuery = ref('');

const selectedNegaraName = computed(() => {
    if (!props.form.company_negara || props.form.company_negara === 'Indonesia') return '';
    return props.form.company_negara;
});

const filteredNegaras = computed(() => {
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

// Dropdown Perusahaan
const showCompanyDropdown = ref(false);
const searchCompanyQuery = ref('');

const filteredCompanies = computed(() => {
    let list = localCompanies.value || [];

    if (props.form.company_jenis_lokasi) {
        list = list.filter(c => (c.jenis_lokasi || 'Dalam Negeri') === props.form.company_jenis_lokasi);
    }

    if (props.form.company_jenis_lokasi === 'Dalam Negeri' && props.form.company_province_id) {
        const inProv = list.filter(c => c.province_id == props.form.company_province_id);
        if (inProv.length > 0) list = inProv;
    }
    if (props.form.company_jenis_lokasi === 'Dalam Negeri' && props.form.company_kabupaten_id) {
        const inKab = list.filter(c => c.kabupaten_id == props.form.company_kabupaten_id);
        if (inKab.length > 0) list = inKab;
    }

    if (!searchCompanyQuery.value.trim()) return list;
    const q = searchCompanyQuery.value.toLowerCase();
    return list.filter(c => c.nama_perusahaan.toLowerCase().includes(q) || (c.negara && c.negara.toLowerCase().includes(q)));
});

const selectCompany = (company) => {
    props.form.nama_perusahaan = company.nama_perusahaan;
    
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
// 6. MODAL POP-UP TAMBAH PERUSAHAAN BARU (SWEETALERT2)
// ============================================================================
const openAddCompanyModal = () => {
    showCompanyDropdown.value = false;

    const provinceOptions = (props.provinces || [])
        .map(p => `<option value="${p.id}" ${p.id == props.form.company_province_id ? 'selected' : ''}>${p.nama_provinsi}</option>`)
        .join('');

    const initialProvId = props.form.company_province_id || '';
    const initialKabId = props.form.company_kabupaten_id || '';
    const initialKodePos = props.form.zipcode || '';
    const initialJenisLokasi = props.form.company_jenis_lokasi || 'Dalam Negeri';
    const initialNegara = props.form.company_negara || '';
    const initialJenisPerusahaan = props.form.company_jenis_perusahaan || 'Perusahaan swasta';

    const foreignCountries = (props.negaras || []).filter(n => n.nama_negara.toLowerCase() !== 'indonesia');
    const negaraSelectOptions = foreignCountries
        .map(n => `<option value="${n.nama_negara}" ${n.nama_negara === initialNegara ? 'selected' : ''}>${n.nama_negara} (${n.benua || 'Dunia'})</option>`)
        .join('');

    const jenisOptionsHtml = jenisPerusahaanOptions.value
        .map(j => `<option value="${j.value}" ${j.value === initialJenisPerusahaan ? 'selected' : ''}>${j.label}</option>`)
        .join('');

    const skalaOptionsHtml = skalaOptions.value
        .map(s => `<option value="${s.value}" ${s.value === (props.form.company_skala || 'Nasional') ? 'selected' : ''}>${s.label}</option>`)
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
                        Dalam Negeri (Indonesia)
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13px; font-weight: 600; color: #1f2937;">
                        <input type="radio" id="swal-lokasi-luar" name="swal-jenis-lokasi" value="Luar Negeri" ${initialJenisLokasi === 'Luar Negeri' ? 'checked' : ''} style="accent-color: #005B3C;" />
                        Luar Negeri (Abroad)
                    </label>
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">
                    Nama Perusahaan / Instansi <span style="color: #ef4444;">*</span>
                </label>
                <input id="swal-company-name" type="text" style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 9px 12px; font-size: 14px; box-sizing: border-box; outline: none;" placeholder="Contoh: PT Teknologi Nusantara / Google Singapore" />
            </div>

            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">
                    Jenis Perusahaan / Instansi (F11)
                </label>
                <select id="swal-company-jenis" style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 9px 12px; font-size: 14px; box-sizing: border-box; outline: none; background: #fff;">
                    ${jenisOptionsHtml}
                </select>
            </div>

            <div id="swal-section-jenis-lainnya" style="display: ${initialJenisPerusahaan === 'Lainnya' ? 'block' : 'none'};">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">
                    Sebutkan Jenis Instansi Lainnya
                </label>
                <input id="swal-company-jenis-lainnya" type="text" style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 9px 12px; font-size: 14px; box-sizing: border-box; outline: none;" placeholder="Contoh: Startup Komunitas, Lembaga Riset..." />
            </div>

            <div id="swal-section-luar-negeri" style="${initialJenisLokasi === 'Luar Negeri' ? 'display: block;' : 'display: none;'}">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">
                    Negara Tempat Bekerja <span style="color: #ef4444;">*</span>
                </label>
                <select id="swal-company-negara" style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 9px 12px; font-size: 14px; box-sizing: border-box; outline: none; background: #fff;">
                    <option value="">-- Pilih Negara di Luar Negeri --</option>
                    ${negaraSelectOptions}
                </select>
            </div>

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
                        ${skalaOptionsHtml}
                    </select>
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">
                    Alamat Jalan / Gedung Perusahaan
                </label>
                <textarea id="swal-company-alamat" rows="2" style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 9px 12px; font-size: 14px; box-sizing: border-box; outline: none; resize: vertical;" placeholder="Nama Jalan, Gedung, Nomor..."></textarea>
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
        customClass: { popup: 'rounded-2xl shadow-xl' },
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

            if (initialProvId) {
                updateKabupatenOptions(initialProvId, initialKabId);
            } else {
                kabSelect.disabled = true;
                kabSelect.style.backgroundColor = '#f3f4f6';
            }

            provSelect.addEventListener('change', (e) => {
                updateKabupatenOptions(e.target.value);
            });

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
            const skala = document.getElementById('swal-company-skala')?.value || (isLuar ? 'Internasional' : 'Regional/Lokal');
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
            localCompanies.value.unshift(newCompany);

            props.form.nama_perusahaan = newCompany.nama_perusahaan;
            props.form.company_jenis_lokasi = newCompany.jenis_lokasi || (newCompany.negara && newCompany.negara !== 'Indonesia' ? 'Luar Negeri' : 'Dalam Negeri');
            props.form.perusahaan_jenis_lokasi = props.form.company_jenis_lokasi;
            props.form.company_negara = newCompany.negara || (props.form.company_jenis_lokasi === 'Luar Negeri' ? '' : 'Indonesia');
            props.form.perusahaan_negara = props.form.company_negara;
            props.form.company_province_id = newCompany.province_id;
            props.form.company_kabupaten_id = newCompany.kabupaten_id;
            props.form.company_alamat = newCompany.alamat || '';
            props.form.company_skala = newCompany.skala || 'Regional/Lokal';
            props.form.zipcode = newCompany.kode_pos || '';
            props.form.company_jenis_perusahaan = newCompany.jenis_perusahaan || '';
            props.form.perusahaan_jenis_perusahaan = props.form.company_jenis_perusahaan;
            props.form.company_jenis_perusahaan_lainnya = newCompany.jenis_perusahaan_lainnya || '';
            props.form.perusahaan_jenis_perusahaan_lainnya = props.form.company_jenis_perusahaan_lainnya;
            props.form.company_status_verifikasi = 'Menunggu Verifikasi';

            Swal.fire({
                icon: 'success',
                title: 'Berhasil Ditambahkan',
                text: `Perusahaan "${newCompany.nama_perusahaan}" berhasil ditambahkan dan dipilih.`,
                confirmButtonColor: '#005B3C',
                timer: 2500,
                timerProgressBar: true
            });
        }
    });
};

// Click outside handler untuk menutup dropdown
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
        
        <!-- ================================================================= -->
        <!-- CARD 1: MEDIA SOSIAL, KEAHLIAN & STATUS AKTIVITAS SAAT INI       -->
        <!-- ================================================================= -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-gray-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">
                Media Sosial & Profesional
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Input: LinkedIn URL -->
                <div>
                    <label :class="labelClass">LinkedIn Profil URL</label>
                    <input type="url" v-model="form.linkedin_url" :class="inputClass" placeholder="https://linkedin.com/in/..." />
                </div>
                <!-- Input: LinkedIn Username -->
                <div>
                    <label :class="labelClass">LinkedIn Username</label>
                    <input type="text" v-model="form.linkedin_username" :class="inputClass" placeholder="username_linkedin" />
                </div>

                <!-- Input: Instagram URL -->
                <div>
                    <label :class="labelClass">Instagram Profil URL</label>
                    <input type="url" v-model="form.instagram_url" :class="inputClass" placeholder="https://instagram.com/..." />
                </div>
                <!-- Input: Facebook URL -->
                <div>
                    <label :class="labelClass">Facebook Profil URL</label>
                    <input type="url" v-model="form.facebook_url" :class="inputClass" placeholder="https://facebook.com/..." />
                </div>
                
                <!-- Input: Bidang Keahlian -->
                <div class="md:col-span-2">
                    <label :class="labelClass">Bidang Keahlian (Expertise)</label>
                    <input type="text" v-model="form.expert" :class="inputClass" placeholder="Contoh: Software Engineering, Data Science..." />
                </div>
                <!-- Input: Minat / Ketertarikan -->
                <div class="md:col-span-2">
                    <label :class="labelClass">Minat & Ketertarikan</label>
                    <input type="text" v-model="form.minat" :class="inputClass" placeholder="Contoh: Artificial Intelligence, Cloud Computing..." />
                </div>

                <!-- Pilihan Kategori Peran / Aktivitas Saat Ini -->
                <div class="md:col-span-2">
                    <label :class="labelClass">Kategori Peran / Aktivitas Saat Ini</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-2">
                        <!-- Opsi 1: Pekerja / Karyawan -->
                        <div 
                            @click="form.kategori_pekerjaan = 'Pekerja'"
                            class="p-4 rounded-xl border-2 cursor-pointer transition-all flex flex-col justify-between"
                            :class="form.kategori_pekerjaan === 'Pekerja' || (!form.kategori_pekerjaan || (form.kategori_pekerjaan !== 'Wiraswasta' && form.kategori_pekerjaan !== 'Melanjutkan Pendidikan')) ? 'border-[#005B3C] bg-emerald-50/50 text-gray-900 shadow-xs ring-1 ring-[#005B3C]/20' : 'border-gray-200 bg-white hover:border-gray-300 text-gray-600'"
                        >
                            <div class="font-bold text-sm" :class="form.kategori_pekerjaan === 'Pekerja' || (!form.kategori_pekerjaan || (form.kategori_pekerjaan !== 'Wiraswasta' && form.kategori_pekerjaan !== 'Melanjutkan Pendidikan')) ? 'text-[#005B3C]' : 'text-gray-800'">Pekerja / Karyawan</div>
                            <div class="text-xs text-gray-500 mt-1">Bekerja di instansi pemerintah, swasta, BUMN, LSM, dsb.</div>
                        </div>

                        <!-- Opsi 2: Wirausaha / Founder -->
                        <div 
                            @click="form.kategori_pekerjaan = 'Wiraswasta'"
                            class="p-4 rounded-xl border-2 cursor-pointer transition-all flex flex-col justify-between"
                            :class="form.kategori_pekerjaan === 'Wiraswasta' ? 'border-[#005B3C] bg-emerald-50/50 text-gray-900 shadow-xs ring-1 ring-[#005B3C]/20' : 'border-gray-200 bg-white hover:border-gray-300 text-gray-600'"
                        >
                            <div class="font-bold text-sm" :class="form.kategori_pekerjaan === 'Wiraswasta' ? 'text-[#005B3C]' : 'text-gray-800'">Wirausaha / Founder</div>
                            <div class="text-xs text-gray-500 mt-1">Mendirikan bisnis sendiri, startup, atau freelance</div>
                        </div>

                        <!-- Opsi 3: Melanjutkan Pendidikan -->
                        <div 
                            @click="form.kategori_pekerjaan = 'Melanjutkan Pendidikan'"
                            class="p-4 rounded-xl border-2 cursor-pointer transition-all flex flex-col justify-between"
                            :class="form.kategori_pekerjaan === 'Melanjutkan Pendidikan' ? 'border-[#005B3C] bg-emerald-50/50 text-gray-900 shadow-xs ring-1 ring-[#005B3C]/20' : 'border-gray-200 bg-white hover:border-gray-300 text-gray-600'"
                        >
                            <div class="font-bold text-sm" :class="form.kategori_pekerjaan === 'Melanjutkan Pendidikan' ? 'text-[#005B3C]' : 'text-gray-800'">Melanjutkan Pendidikan</div>
                            <div class="text-xs text-gray-500 mt-1">Studi lanjut jenjang S1, S2, S3, profesi, atau spesialis</div>
                        </div>
                    </div>
                </div>

                <!-- Jika Karyawan/Pekerja: Posisi Jabatan Struktural (F2G) Dinamis dari Database -->
                <div v-if="form.kategori_pekerjaan === 'Pekerja' || (!form.kategori_pekerjaan || (form.kategori_pekerjaan !== 'Wiraswasta' && form.kategori_pekerjaan !== 'Melanjutkan Pendidikan'))" class="md:col-span-2">
                    <label :class="labelClass">Posisi Jabatan Struktural (F2G)</label>
                    <select v-model="form.posisi_jabatan" :class="inputClass">
                        <option value="">-- Pilih Posisi Jabatan --</option>
                        <option v-for="opt in posisiJabatanOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
                </div>

                <!-- Jika Wiraswasta: Posisi / Jabatan Wiraswasta (F5C) -->
                <div v-else-if="form.kategori_pekerjaan === 'Wiraswasta'" class="md:col-span-2 space-y-3">
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

                <!-- Jika Melanjutkan Pendidikan: Detail Studi Lanjut (F18 Standar Dikti) -->
                <div v-else-if="form.kategori_pekerjaan === 'Melanjutkan Pendidikan'" class="md:col-span-2 space-y-4 bg-emerald-50/40 p-5 rounded-2xl border border-emerald-100">
                    <div class="pb-2 border-b border-emerald-100/70">
                        <h3 class="font-bold text-gray-900 text-sm">Informasi Studi Lanjut</h3>
                        <p class="text-xs text-gray-500">Lengkapi data jenjang, perguruan tinggi, dan program studi yang sedang Anda tempuh</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label :class="labelClass">Pendidikan Tingkat Apa</label>
                            <select v-model="form.pendidikan_tingkat" :class="inputClass">
                                <option value="">-- Pilih Tingkat / Jenjang --</option>
                                <option value="D3">D3 (Diploma 3)</option>
                                <option value="D4">D4 (Diploma 4 / Sarjana Terapan)</option>
                                <option value="S1">S1 (Sarjana)</option>
                                <option value="S2">S2 (Magister / Master)</option>
                                <option value="S3">S3 (Doktor / Ph.D)</option>
                                <option value="Profesi">Pendidikan Profesi</option>
                                <option value="Spesialis">Pendidikan Spesialis</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label :class="labelClass">Perguruan Tinggi / Universitas</label>
                            <input 
                                type="text" 
                                v-model="form.perguruan_tinggi" 
                                :class="inputClass" 
                                placeholder="Contoh: Universitas Gadjah Mada" 
                            />
                        </div>

                        <div>
                            <label :class="labelClass">Program Studi</label>
                            <input 
                                type="text" 
                                v-model="form.pendidikan_prodi" 
                                :class="inputClass" 
                                placeholder="Contoh: Magister Informatika" 
                            />
                        </div>
                    </div>
                </div>

                <!-- Input Penghasilan / Take Home Pay -->
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

                <!-- Khusus Alumni Teologi / Filsafat Keilahian -->
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

        <!-- ================================================================= -->
        <!-- CARD 2: DATA PERUSAHAAN / INSTANSI TEMPAT BEKERJA                 -->
        <!-- ================================================================= -->
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
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all"
                        :class="form.company_jenis_lokasi === 'Dalam Negeri' ? 'bg-[#005B3C] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-white/60'"
                    >
                        Dalam Negeri
                    </button>
                    <button 
                        type="button" 
                        @click="form.company_jenis_lokasi = 'Luar Negeri'" 
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all"
                        :class="form.company_jenis_lokasi === 'Luar Negeri' ? 'bg-[#005B3C] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-white/60'"
                    >
                        Luar Negeri
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                
                <!-- Jika Luar Negeri: Searchable Dropdown Master Negara Dunia -->
                <div v-if="form.company_jenis_lokasi === 'Luar Negeri'" class="md:col-span-2 relative" ref="negaraContainerRef">
                    <label :class="labelClass">
                        Negara Tempat Bekerja <span class="text-red-500">*</span>
                    </label>
                    
                    <div 
                        @click="showNegaraDropdown = !showNegaraDropdown; if (showNegaraDropdown) searchNegaraQuery = '';"
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium cursor-pointer flex justify-between items-center shadow-xs hover:border-gray-300 transition-colors"
                    >
                        <span v-if="selectedNegaraName" class="text-gray-900 font-semibold">
                            {{ selectedNegaraName }}
                        </span>
                        <span v-else class="text-gray-400">Pilih Negara di Luar Negeri...</span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0 ml-2 transition-transform duration-200" :class="{'rotate-180': showNegaraDropdown}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>

                    <!-- Dropdown List Negara -->
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
                            </li>
                        </ul>
                    </div>

                    <p class="text-[11px] text-gray-400 mt-1">
                        * Pilihan negara dibatasi pada daftar master negara resmi dunia.
                    </p>
                </div>

                <!-- Jika Dalam Negeri: Dropdown Provinsi & Kabupaten/Kota -->
                <template v-else>
                    <!-- Dropdown Provinsi -->
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

                    <!-- Dropdown Kabupaten -->
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

                <!-- Dropdown Nama Perusahaan / Instansi Terdaftar -->
                <div class="md:col-span-2 relative" ref="companyContainerRef">
                    <label :class="labelClass">Nama Perusahaan / Instansi</label>
                    <div class="flex items-center gap-2">
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

                    <!-- Dropdown List Perusahaan -->
                    <div v-if="showCompanyDropdown" class="absolute z-50 w-full mt-1.5 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
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
                                        {{ company.negara || 'Luar Negeri' }}
                                    </span>
                                    <span class="text-xs text-gray-400" v-else-if="company.province_id">
                                        {{ provinces.find(p => p.id == company.province_id)?.nama_provinsi || '' }}
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

                <!-- Banner Notifikasi Status Verifikasi Perusahaan -->
                <div v-if="!canEditCompanyDetails" class="md:col-span-2 bg-gray-50 border border-gray-200 rounded-xl p-3.5 flex items-center gap-3 text-xs text-gray-600 font-medium shadow-2xs">
                    <div class="w-2 h-2 rounded-full bg-emerald-500 shrink-0 animate-pulse"></div>
                    <div>
                        Perusahaan ini telah <strong>Terverifikasi Resmi</strong> oleh universitas. Detail data perusahaan terkunci untuk menjaga konsistensi master data institusi.
                    </div>
                </div>

                <!-- Jenis Perusahaan / Instansi (F11 Standar Dikti Dinamis) -->
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between mb-1.5">
                        <label :class="labelClass" class="mb-0">Jenis Perusahaan / Instansi (F11)</label>
                        <span v-if="!canEditCompanyDetails" class="text-[11px] font-semibold text-gray-400 bg-gray-200/80 px-2 py-0.5 rounded">Terkunci (Terverifikasi)</span>
                    </div>
                    <select 
                        v-model="form.company_jenis_perusahaan" 
                        @change="form.perusahaan_jenis_perusahaan = form.company_jenis_perusahaan" 
                        :disabled="!canEditCompanyDetails"
                        :class="[!canEditCompanyDetails ? 'bg-gray-100/90 text-gray-500 cursor-not-allowed' : '', inputClass]"
                    >
                        <option value="">-- Pilih Jenis Instansi / Perusahaan --</option>
                        <option v-for="opt in jenisPerusahaanOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
                </div>

                <!-- Input Isian Bebas jika memilih Jenis Lainnya -->
                <div v-if="form.company_jenis_perusahaan === 'Lainnya'" class="md:col-span-2">
                    <label :class="labelClass">Sebutkan Jenis Instansi Lainnya</label>
                    <input 
                        type="text" 
                        v-model="form.company_jenis_perusahaan_lainnya" 
                        @input="form.perusahaan_jenis_perusahaan_lainnya = form.company_jenis_perusahaan_lainnya"
                        :disabled="!canEditCompanyDetails"
                        :class="[!canEditCompanyDetails ? 'bg-gray-100/90 text-gray-500 cursor-not-allowed' : '', inputClass]" 
                        placeholder="Contoh: Lembaga Riset Independen, Startup Komunitas..." 
                    />
                </div>

                <!-- Skala Perusahaan / Instansi (F2H / F5D Dinamis) -->
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between mb-1.5">
                        <label :class="labelClass" class="mb-0">Skala Perusahaan / Instansi (F2H / F5D)</label>
                        <span v-if="!canEditCompanyDetails" class="text-[11px] font-semibold text-gray-400 bg-gray-200/80 px-2 py-0.5 rounded">Terkunci (Terverifikasi)</span>
                    </div>
                    <select 
                        v-model="form.company_skala" 
                        @change="form.perusahaan_skala = form.company_skala"
                        :disabled="!canEditCompanyDetails"
                        :class="[!canEditCompanyDetails ? 'bg-gray-100/90 text-gray-500 cursor-not-allowed' : '', inputClass]"
                    >
                        <option value="">-- Pilih Skala Perusahaan --</option>
                        <option v-for="opt in skalaOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
                </div>

                <!-- Alamat Kantor / Perusahaan -->
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between mb-1.5">
                        <label :class="labelClass" class="mb-0">Alamat Perusahaan</label>
                        <span v-if="!canEditCompanyDetails" class="text-[11px] font-semibold text-gray-400 bg-gray-200/80 px-2 py-0.5 rounded">Terkunci (Terverifikasi)</span>
                    </div>
                    <input 
                        type="text" 
                        v-model="form.company_alamat" 
                        @input="form.perusahaan_alamat = form.company_alamat"
                        :disabled="!canEditCompanyDetails"
                        :class="[!canEditCompanyDetails ? 'bg-gray-100/90 text-gray-500 cursor-not-allowed' : '', inputClass]" 
                        placeholder="Contoh: Pacific Building, Jl. Laksda Adisucipto No. 157, Sleman" 
                    />
                </div>

                <!-- Kode Pos Perusahaan -->
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between mb-1.5">
                        <label :class="labelClass" class="mb-0">Kode Pos Perusahaan (Zipcode)</label>
                        <span v-if="!canEditCompanyDetails" class="text-[11px] font-semibold text-gray-400 bg-gray-200/80 px-2 py-0.5 rounded">Terkunci (Terverifikasi)</span>
                    </div>
                    <input 
                        type="text" 
                        v-model="form.zipcode" 
                        :disabled="!canEditCompanyDetails"
                        :class="[!canEditCompanyDetails ? 'bg-gray-100/90 text-gray-500 cursor-not-allowed' : '', inputClass]" 
                        placeholder="Kode Pos (Cth: 55281)" 
                    />
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- CARD 3: DATA ATASAN LANGSUNG / PIMPINAN                           -->
        <!-- ================================================================= -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-gray-100">
            <h2 class="text-xl font-bold text-gray-900 mb-2">
                Data Atasan Langsung
            </h2>
            <p class="text-xs text-gray-500 mb-6">
                Data pimpinan/atasan digunakan untuk keperluan survei evaluasi kepuasan pengguna lulusan oleh universitas.
            </p>

            <!-- Banner Otomatis: Owner / Founder / Wiraswasta -->
            <div v-if="isOwner" class="bg-emerald-50 border border-emerald-200 text-[#005B3C] rounded-xl p-3.5 text-xs flex items-center gap-2.5 mb-5 font-medium shadow-2xs">
                <span>Karena Anda memilih posisi <strong>Owner / Wiraswasta</strong>, kolom Data Atasan Langsung di bawah ini otomatis diisi dengan data diri Anda.</span>
            </div>

            <!-- Banner Otomatis: Melanjutkan Pendidikan -->
            <div v-else-if="form.kategori_pekerjaan === 'Melanjutkan Pendidikan'" class="bg-blue-50 border border-blue-200 text-blue-800 rounded-xl p-3.5 text-xs flex items-center gap-2.5 mb-5 font-medium shadow-2xs">
                <span>Bagi yang sedang <strong>Melanjutkan Pendidikan</strong>, Anda dapat mengisi data Dosen Pembimbing, Ketua Program Studi, atau Pimpinan Akademik terkait pada kolom atasan di bawah ini.</span>
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
