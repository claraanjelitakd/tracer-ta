<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const form = useForm({
    password: '',
    password_confirmation: '',
});

// State visibilitas kata sandi
const showPassword = ref(false);
const showConfirmPassword = ref(false);

const submit = () => {
    form.post('/change-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Ubah Kata Sandi - Tracer Study UKDW" />

    <div class="min-h-screen bg-[#002813] flex items-center justify-center p-3 sm:p-6 lg:p-8 font-sans selection:bg-[#FFC700] selection:text-slate-950">
        
        <!-- Master Card Split 2 Kolom (Bentuk Tegas & Elegan, Tidak Tumpul Berlebihan) -->
        <div class="max-w-5xl w-full bg-[#004D25] rounded-xl p-3 sm:p-5 shadow-2xl border border-white/15 grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-6 items-stretch relative overflow-hidden">
            
            <!-- Glow Latar Belakang Hijau Botol -->
            <div class="absolute -top-32 -left-32 w-80 h-80 bg-[#03542B] rounded-full blur-3xl pointer-events-none opacity-50"></div>
            <div class="absolute -bottom-32 -right-32 w-80 h-80 bg-[#FFC700]/10 rounded-full blur-3xl pointer-events-none opacity-50"></div>

            <!-- 
              ========================================================================
              KOLOM KIRI: INFORMASI PETUNJUK KEAMANAN & AKUN
              ========================================================================
            -->
            <div class="lg:col-span-6 flex flex-col justify-between p-6 sm:p-8 rounded-lg bg-gradient-to-b from-[#00381a] via-[#004D25] to-[#022813] border border-white/10 relative overflow-hidden text-white shadow-inner">
                
                <div>
                    <!-- Top Branding Logo & Title -->
                    <div class="flex items-center gap-3.5 mb-6">
                        <div class="w-12 h-12 rounded-lg bg-white p-2 flex items-center justify-center shadow-md shrink-0">
                            <img src="/uploads/logo/logo-ukdw.png" alt="Logo UKDW" class="w-full h-full object-contain" />
                        </div>
                        <div>
                            <span class="text-base font-black tracking-tight text-white block leading-tight">TRACER STUDY UKDW</span>
                            <span class="text-[11px] text-white/75 font-semibold tracking-wide">Pembaruan Keamanan Wajib</span>
                        </div>
                    </div>

                    <!-- Headline -->
                    <h2 class="text-xl sm:text-2xl lg:text-3xl font-black text-white tracking-tight leading-tight mb-2">
                        Amankan akun Anda sebelum melanjutkan.
                    </h2>
                    <p class="text-xs sm:text-sm text-white/80 leading-relaxed font-medium mb-6">
                        Demi perlindungan data pribadi dan riwayat karier Anda, sistem mewajibkan pembaruan kata sandi dari kata sandi default sistem.
                    </p>

                    <!-- Ketentuan Kata Sandi Box -->
                    <div class="space-y-3 bg-black/25 backdrop-blur-xl p-4 sm:p-5 rounded-lg border border-white/15 shadow-sm text-xs">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-2 h-2 rounded-full bg-[#FFC700]"></span>
                            <h3 class="text-xs font-black uppercase tracking-wider text-[#FFC700]">
                                Kriteria Kata Sandi Baru
                            </h3>
                        </div>

                        <ul class="space-y-2 text-white/85 pl-2 leading-relaxed">
                            <li class="flex items-start gap-2">
                                <span class="text-[#FFC700] font-bold">✓</span>
                                <span>Panjang minimal <strong>8 karakter</strong>.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-[#FFC700] font-bold">✓</span>
                                <span>Kombinasikan huruf, angka, atau simbol unik untuk keamanan maksimal.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-[#FFC700] font-bold">✓</span>
                                <span>Hindari penggunaan tanggal lahir atau nomor telepon yang mudah ditebak.</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Footer Slogan & Logout -->
                <div class="pt-6 mt-6 border-t border-white/10 flex items-center justify-between gap-3 text-xs">
                    <p class="text-white/60 text-[11px] italic">
                        Biro 3 UKDW Kemahasiswaan &amp; Alumni
                    </p>
                    <Link 
                        href="/logout" 
                        method="post"
                        as="button"
                        class="shrink-0 inline-flex items-center gap-1.5 text-white/90 hover:text-[#FFC700] font-bold transition-colors py-1 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Keluar Akun</span>
                    </Link>
                </div>

            </div>

            <!-- 
              ========================================================================
              KOLOM KANAN: FORM UBAH KATA SANDI
              ========================================================================
            -->
            <div class="lg:col-span-6 bg-[#023319] sm:bg-[#00381a] p-6 sm:p-9 lg:p-10 rounded-lg border border-white/10 flex flex-col justify-center text-white relative">
                
                <div class="mb-7">
                    <span class="text-[11px] font-black uppercase tracking-widest text-[#FFC700] block mb-1">
                        Proteksi Akun
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight leading-snug">
                        Ubah Kata Sandi
                    </h1>
                    <p class="text-xs sm:text-sm text-white/75 mt-1 font-medium">
                        Masukkan kata sandi baru Anda di bawah ini.
                    </p>
                </div>

                <!-- Error Banner -->
                <div v-if="form.errors.password" class="mb-5 p-3.5 bg-red-950/80 border border-red-500/50 rounded-lg flex items-start gap-2.5 text-red-200 text-xs sm:text-sm animate-fade-in">
                    <svg class="w-5 h-5 shrink-0 text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>{{ form.errors.password }}</span>
                </div>

                <!-- Form Ubah Password -->
                <form @submit.prevent="submit" class="space-y-4">
                    
                    <!-- Input Kata Sandi Baru -->
                    <div>
                        <label for="password" class="block text-xs font-bold text-white/90 uppercase tracking-wider mb-1.5">
                            Kata Sandi Baru
                        </label>
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
                                autofocus
                                placeholder="Minimal 8 karakter"
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

                    <!-- Input Konfirmasi Kata Sandi Baru -->
                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-white/90 uppercase tracking-wider mb-1.5">
                            Konfirmasi Kata Sandi Baru
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-white/40">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input
                                id="password_confirmation"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                v-model="form.password_confirmation"
                                required
                                placeholder="Ulangi kata sandi baru"
                                class="block w-full pl-11 pr-11 py-3 bg-black/30 border border-white/20 rounded-lg text-sm text-white placeholder-white/40 focus:outline-none focus:border-[#FFC700] focus:ring-1 focus:ring-[#FFC700] transition-all font-medium"
                            />
                            <button
                                type="button"
                                @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-white/40 hover:text-white focus:outline-none cursor-pointer"
                                tabindex="-1"
                            >
                                <svg v-if="!showConfirmPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Tombol Simpan (rounded-lg) -->
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
                            <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Kata Sandi Baru' }}</span>
                        </button>
                    </div>

                </form>

                <p class="text-[11px] text-white/50 text-center mt-6">
                    © {{ new Date().getFullYear() }} Universitas Kristen Duta Wacana
                </p>

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
