<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

defineProps({
    status: String,
});

// Form state untuk menangani login
const form = useForm({
    username: '',
    password: '',
    remember: false,
});

// Form state untuk menangani lupa kata sandi
const forgotForm = useForm({
    username_atau_email: '',
});

// State visibilitas kata sandi
const showPassword = ref(false);

// State modal lupa kata sandi
const showForgotModal = ref(false);
const showContactInfo = ref(false);

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};

const submitForgot = () => {
    forgotForm.post('/forgot-password', {
        onSuccess: () => {
            forgotForm.reset('username_atau_email');
        },
    });
};
</script>

<template>
    <Head title="Masuk - Tracer Study UKDW" />

    <div class="min-h-screen bg-[#002813] flex items-center justify-center p-3 sm:p-6 lg:p-8 font-sans selection:bg-[#FFC700] selection:text-slate-950">
        
        <!-- Master Card Split 2 Kolom (Bentuk Tegas & Elegan, Tidak Tumpul Berlebihan) -->
        <div class="max-w-5xl w-full bg-[#ffff] rounded-xl p-3 sm:p-5 shadow-2xl border border-white/15 grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-6 items-stretch relative overflow-hidden">
            
            <!-- Glow Latar Belakang Hijau Botol -->
            <div class="absolute -top-32 -left-32 w-80 h-80 bg-[#03542B] rounded-full blur-3xl pointer-events-none opacity-50"></div>
            <div class="absolute -bottom-32 -right-32 w-80 h-80 bg-[#FFC700]/10 rounded-full blur-3xl pointer-events-none opacity-50"></div>

            <!-- 
              ========================================================================
              KOLOM KIRI: INFORMASI PETUNJUK PENGISIAN AKUN
              ========================================================================
            -->
            <div class="lg:col-span-6 flex flex-col justify-between p-6 sm:p-8 rounded-lg bg-gradient-to-b from-[#00381a] via-[#004D25] to-[#022813] border border-white/10 relative overflow-hidden text-white shadow-inner">
                
                <div>
                    <!-- Top Branding Logo & Title -->
                    <div class="flex items-center gap-3.5 mb-6">
                        <Link href="/" class="w-12 h-12 rounded-lg bg-white p-2 flex items-center justify-center shadow-md shrink-0 hover:scale-105 transition-transform">
                            <img src="/uploads/logo/logo-ukdw.png" alt="Logo UKDW" class="w-full h-full object-contain" />
                        </Link>
                        <div>
                            <span class="text-base font-black tracking-tight text-white block leading-tight">TRACER STUDY UKDW</span>
                            <span class="text-[11px] text-white/75 font-semibold tracking-wide">Universitas Kristen Duta Wacana</span>
                        </div>
                    </div>

                    <!-- Headline Inspiratif -->
                    <h2 class="text-xl sm:text-2xl lg:text-3xl font-black text-white tracking-tight leading-tight mb-2">
                        Design your future, one milestone at a time.
                    </h2>
                    <p class="text-xs sm:text-sm text-white/80 leading-relaxed font-medium mb-6">
                        Bergabunglah bersama ribuan alumni Duta Wacana dalam membangun almamater yang unggul dan melayani dunia.
                    </p>

                    <!-- Petunjuk Akun Pengisian Box -->
                    <div class="space-y-3.5 bg-black/25 backdrop-blur-xl p-4 sm:p-5 rounded-lg border border-white/15 shadow-sm">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-2 h-2 rounded-full bg-[#FFC700]"></span>
                            <h3 class="text-xs font-black uppercase tracking-wider text-[#FFC700]">
                                Petunjuk Akun Pengisian
                            </h3>
                        </div>

                        <!-- Alumni UKDW -->
                        <div class="text-xs space-y-1 pl-3 border-l-2 border-[#FFC700]">
                            <div class="font-bold text-white flex items-center justify-between">
                                <span>Alumni UKDW</span>
                                <span class="text-[10px] text-[#FFC700] font-semibold bg-[#FFC700]/10 px-2 py-0.5 rounded">Akun Terdaftar</span>
                            </div>
                            <p class="text-white/85 leading-relaxed">
                                <strong>Username:</strong> Menggunakan <span class="font-bold text-[#FFC700]">NIM</span> saat kuliah.
                            </p>
                            <p class="text-white/85 leading-relaxed">
                                <strong>Password:</strong> Tanggal lahir format <code class="bg-black/40 px-1.5 py-0.5 rounded font-bold text-[#FFC700] border border-white/10">ddmmyyyy</code>.
                            </p>
                            <p class="text-[11px] text-white/60 italic pt-0.5">
                                Contoh: 01011990 (kelahiran 1 Januari 1990). Wajib ubah kata sandi saat pertama kali masuk.
                            </p>
                        </div>

                        <!-- Pengguna Lulusan / Perusahaan -->
                        <div class="text-xs space-y-1 pl-3 border-l-2 border-white/30 pt-2 border-t border-white/10">
                            <div class="font-bold text-white flex items-center justify-between">
                                <span>Pengguna Lulusan / Perusahaan</span>
                                <span class="text-[10px] text-white/70 font-semibold bg-white/10 px-2 py-0.5 rounded">Mitra</span>
                            </div>
                            <p class="text-white/85 leading-relaxed">
                                <strong>Akses Kuesioner Mitra:</strong> Menggunakan kredensial khusus yang telah dikirimkan secara resmi melalui email penanggung jawab instansi.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 
              ========================================================================
              KOLOM KANAN: FORM LOGIN OTENTIKASI
              ========================================================================
            -->
            <div class="lg:col-span-6 bg-[#023319] sm:bg-[#00381a] p-6 sm:p-9 lg:p-10 rounded-lg border border-white/10 flex flex-col justify-center text-white relative">
                
                <div class="mb-7">
                    <span class="text-[11px] font-black uppercase tracking-widest text-[#FFC700] block mb-1">
                        Autentikasi Pengguna
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight leading-snug">
                        Masuk ke Akun
                    </h1>
                    <p class="text-xs sm:text-sm text-white/75 mt-1 font-medium">
                        Masukkan NIM/Username dan kata sandi Anda untuk melanjutkan.
                    </p>
                </div>

                <!-- Status / Success Banner -->
                <div v-if="status" class="mb-5 p-3.5 bg-emerald-950/90 border border-emerald-500/50 rounded-lg flex items-start gap-2.5 text-emerald-200 text-xs sm:text-sm animate-fade-in">
                    <svg class="w-5 h-5 shrink-0 text-emerald-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ status }}</span>
                </div>

                <!-- Error Banner -->
                <div v-if="form.errors.username || form.errors.password" class="mb-5 p-3.5 bg-red-950/80 border border-red-500/50 rounded-lg flex items-start gap-2.5 text-red-200 text-xs sm:text-sm animate-fade-in">
                    <svg class="w-5 h-5 shrink-0 text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>{{ form.errors.username || form.errors.password }}</span>
                </div>

                <!-- Form Login -->
                <form @submit.prevent="submit" class="space-y-4">
                    
                    <!-- Input Username / NIM -->
                    <div>
                        <label for="username" class="block text-xs font-bold text-white/90 uppercase tracking-wider mb-1.5">
                            Username / NIM Alumni
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-white/40">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <input
                                id="username"
                                type="text"
                                v-model="form.username"
                                required
                                autofocus
                                placeholder="Masukkan NIM atau Username"
                                class="block w-full pl-11 pr-4 py-3 bg-black/30 border border-white/20 rounded-lg text-sm text-white placeholder-white/40 focus:outline-none focus:border-[#FFC700] focus:ring-1 focus:ring-[#FFC700] transition-all font-medium"
                            />
                        </div>
                    </div>

                    <!-- Input Kata Sandi -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-bold text-white/90 uppercase tracking-wider">
                                Kata Sandi
                            </label>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-white/40">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                required
                                placeholder="Masukkan kata sandi"
                                class="block w-full pl-11 pr-11 py-3 bg-black/30 border border-white/20 rounded-lg text-sm text-white placeholder-white/40 focus:outline-none focus:border-[#FFC700] focus:ring-1 focus:ring-[#FFC700] transition-all font-medium"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-white/40 hover:text-white focus:outline-none cursor-pointer"
                                tabindex="-1"
                            >
                                <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Ingat Saya & Lupa Kata Sandi -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center cursor-pointer select-none">
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="w-4 h-4 rounded text-[#FFC700] bg-black/40 border-white/30 focus:ring-[#FFC700] cursor-pointer"
                            />
                            <span class="ml-2 text-xs font-semibold text-white/80">Ingat Saya</span>
                        </label>
                        <button
                            type="button"
                            @click="showForgotModal = true"
                            class="text-xs font-bold text-[#FFC700] hover:text-[#FBBF24] hover:underline transition-colors focus:outline-none cursor-pointer"
                        >
                            Lupa Kata Sandi?
                        </button>
                    </div>

                    <!-- Tombol Submit Masuk (Tegas, Solid, rounded-lg) -->
                    <div class="pt-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full flex justify-center items-center py-3.5 px-6 rounded-lg shadow-lg text-sm font-black text-slate-950 bg-[#FFC700] hover:bg-[#FBBF24] focus:outline-none focus:ring-2 focus:ring-[#FFC700] transition-all duration-200 active:scale-[0.99] disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-slate-950" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ form.processing ? 'Memproses...' : 'Masuk ke Sistem' }}</span>
                        </button>
                    </div>

                </form>

                <p class="text-[11px] text-white/50 text-center mt-6">
                    © {{ new Date().getFullYear() }} Universitas Kristen Duta Wacana
                </p>

            </div>

        </div>

        <!-- Modal Form Lupa Kata Sandi (Tegas & Ramping, rounded-xl) -->
        <div v-if="showForgotModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fade-in">
            <div class="bg-[#00381A] text-white rounded-xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-white/20 relative text-left">
                
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/10">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-sm bg-[#FFC700]"></span>
                        <h3 class="text-base font-black text-white">Lupa Kata Sandi</h3>
                    </div>
                    <button 
                        @click="showForgotModal = false"
                        class="text-white/60 hover:text-white rounded-lg p-1 transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Notifikasi Status / Error di dalam Modal -->
                <div v-if="$page.props.flash?.status" class="mb-4 p-3 bg-emerald-950/90 border border-emerald-500/50 rounded-lg text-xs text-emerald-200">
                    {{ $page.props.flash.status }}
                </div>
                
                <div v-if="forgotForm.errors.username_atau_email" class="mb-4 p-3 bg-red-950/80 border border-red-500/50 rounded-lg text-xs text-red-200">
                    {{ forgotForm.errors.username_atau_email }}
                </div>

                <div v-if="!showContactInfo">
                    <p class="text-xs text-white/85 mb-4 leading-relaxed">
                        Masukkan <strong>NIM / Username</strong> atau <strong>Email terdaftar</strong> Anda. Tautan pemulihan kata sandi akan otomatis dikirimkan ke Gmail/Email akun Anda.
                    </p>

                    <form @submit.prevent="submitForgot" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-white/90 uppercase tracking-wider mb-1.5">
                                NIM / Username / Email
                            </label>
                            <input
                                type="text"
                                v-model="forgotForm.username_atau_email"
                                required
                                placeholder="Contoh: 71200101 atau nama@gmail.com"
                                class="block w-full px-4 py-3 bg-black/30 border border-white/20 rounded-lg text-sm text-white placeholder-white/40 focus:outline-none focus:border-[#FFC700] focus:ring-1 focus:ring-[#FFC700] transition-all font-medium"
                            />
                        </div>

                        <button
                            type="submit"
                            :disabled="forgotForm.processing"
                            class="w-full flex justify-center items-center py-3 px-5 rounded-lg shadow-lg text-xs font-black text-slate-950 bg-[#FFC700] hover:bg-[#FBBF24] transition-all disabled:opacity-60 cursor-pointer"
                        >
                            <svg v-if="forgotForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-slate-950" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ forgotForm.processing ? 'Mengirim Email...' : 'Kirim Tautan Reset Password' }}</span>
                        </button>
                    </form>

                    <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-[11px]">
                        <button 
                            @click="showContactInfo = true" 
                            class="text-[#FFC700] hover:underline font-medium cursor-pointer"
                        >
                            Tidak tahu email terdaftar?
                        </button>
                        <button 
                            @click="showForgotModal = false" 
                            class="text-white/60 hover:text-white cursor-pointer"
                        >
                            Batal
                        </button>
                    </div>
                </div>

                <!-- Kontak Bantuan Manual Jika Tidak Tahu Email -->
                <div v-else class="text-xs text-white/85 space-y-3 leading-relaxed">
                    <p>
                        Silakan hubungi PIC resmi Biro 3 UKDW jika email Anda belum terdaftar di sistem:
                    </p>
                    <div class="bg-black/35 p-3.5 rounded-lg border border-white/10 space-y-1.5 font-medium text-white/90">
                        <div class="flex items-center gap-2">
                            <span class="text-white/50">Unit:</span>
                            <span class="font-bold">Biro 3 UKDW (Alumni &amp; Karier)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-white/50">Email:</span>
                            <a href="mailto:biro3@staff.ukdw.ac.id" class="text-[#FFC700] font-bold underline">biro3@staff.ukdw.ac.id</a>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-white/50">Telp:</span>
                            <span>(0274) 563929 ext. 103</span>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button
                            type="button"
                            @click="showContactInfo = false"
                            class="w-full py-2 bg-white/10 hover:bg-white/20 text-white text-xs font-bold rounded-lg transition-all cursor-pointer"
                        >
                            &larr; Kembali ke Form Reset
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </div>
</template>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.98); }
    to { opacity: 1; transform: scale(1); }
}
.animate-fade-in {
    animation: fadeIn 0.15s ease-out;
}
</style>
