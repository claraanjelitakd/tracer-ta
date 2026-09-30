<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    token: String,
    email: String,
});

const form = useForm({
    token: props.token,
    email: props.email || '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);

const submit = () => {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Reset Kata Sandi - Tracer Study UKDW" />

    <div class="min-h-screen bg-[#002813] flex items-center justify-center p-3 sm:p-6 lg:p-8 font-sans selection:bg-[#FFC700] selection:text-slate-950">
        
        <div class="max-w-md w-full bg-[#00381a] rounded-xl p-6 sm:p-8 shadow-2xl border border-white/15 relative overflow-hidden text-white">
            
            <!-- Glow background -->
            <div class="absolute -top-24 -left-24 w-60 h-60 bg-[#03542B] rounded-full blur-3xl pointer-events-none opacity-60"></div>
            <div class="absolute -bottom-24 -right-24 w-60 h-60 bg-[#FFC700]/10 rounded-full blur-3xl pointer-events-none opacity-60"></div>

            <!-- Header Branding -->
            <div class="flex items-center gap-3.5 mb-6">
                <Link href="/" class="w-12 h-12 rounded-lg bg-white p-2 flex items-center justify-center shadow-md shrink-0">
                    <img src="/uploads/logo/logo-ukdw.png" alt="Logo UKDW" class="w-full h-full object-contain" />
                </Link>
                <div>
                    <span class="text-base font-black tracking-tight text-white block leading-tight">TRACER STUDY UKDW</span>
                    <span class="text-[11px] text-white/75 font-semibold tracking-wide">Universitas Kristen Duta Wacana</span>
                </div>
            </div>

            <div class="mb-6">
                <span class="text-[11px] font-black uppercase tracking-widest text-[#FFC700] block mb-1">
                    Pemulihan Akun
                </span>
                <h1 class="text-2xl font-black text-white tracking-tight">
                    Reset Kata Sandi
                </h1>
                <p class="text-xs text-white/75 mt-1 font-medium">
                    Silakan masukkan alamat email dan kata sandi baru untuk akun Anda.
                </p>
            </div>

            <!-- Error Banner -->
            <div v-if="form.errors.email || form.errors.password || form.errors.token" class="mb-5 p-3.5 bg-red-950/80 border border-red-500/50 rounded-lg text-red-200 text-xs font-medium space-y-1">
                <div v-if="form.errors.email">{{ form.errors.email }}</div>
                <div v-if="form.errors.password">{{ form.errors.password }}</div>
                <div v-if="form.errors.token">{{ form.errors.token }}</div>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                
                <!-- Input Email -->
                <div>
                    <label for="email" class="block text-xs font-bold text-white/90 uppercase tracking-wider mb-1.5">
                        Alamat Email
                    </label>
                    <input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        placeholder="nama@gmail.com"
                        class="block w-full px-4 py-3 bg-black/30 border border-white/20 rounded-lg text-sm text-white placeholder-white/40 focus:outline-none focus:border-[#FFC700] focus:ring-1 focus:ring-[#FFC700] transition-all font-medium"
                    />
                </div>

                <!-- Input Kata Sandi Baru -->
                <div>
                    <label for="password" class="block text-xs font-bold text-white/90 uppercase tracking-wider mb-1.5">
                        Kata Sandi Baru
                    </label>
                    <div class="relative">
                        <input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            v-model="form.password"
                            required
                            placeholder="Minimal 6 karakter"
                            class="block w-full pl-4 pr-11 py-3 bg-black/30 border border-white/20 rounded-lg text-sm text-white placeholder-white/40 focus:outline-none focus:border-[#FFC700] focus:ring-1 focus:ring-[#FFC700] transition-all font-medium"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-white/40 hover:text-white cursor-pointer"
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
                    <input
                        id="password_confirmation"
                        :type="showPassword ? 'text' : 'password'"
                        v-model="form.password_confirmation"
                        required
                        placeholder="Ulangi kata sandi baru"
                        class="block w-full px-4 py-3 bg-black/30 border border-white/20 rounded-lg text-sm text-white placeholder-white/40 focus:outline-none focus:border-[#FFC700] focus:ring-1 focus:ring-[#FFC700] transition-all font-medium"
                    />
                </div>

                <!-- Tombol Submit -->
                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex justify-center items-center py-3.5 px-6 rounded-lg shadow-lg text-sm font-black text-slate-950 bg-[#FFC700] hover:bg-[#FBBF24] transition-all disabled:opacity-60 cursor-pointer"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-slate-950" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ form.processing ? 'Menyimpan Kata Sandi...' : 'Simpan Kata Sandi Baru' }}</span>
                    </button>
                </div>

            </form>

            <div class="mt-6 text-center">
                <Link href="/login" class="text-xs text-[#FFC700] hover:underline font-bold">
                    &larr; Kembali ke Halaman Login
                </Link>
            </div>

        </div>

    </div>
</template>
