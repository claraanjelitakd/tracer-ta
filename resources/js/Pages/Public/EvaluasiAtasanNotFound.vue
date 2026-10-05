<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    message: String,
});
</script>

<template>
    <Head title="Evaluasi Atasan Tidak Ditemukan - UKDW" />

    <div class="min-h-screen bg-slate-50 flex items-center justify-center p-4 font-sans text-gray-900">
        <div class="max-w-md w-full bg-white rounded-3xl p-8 shadow-xl text-center border border-gray-100 space-y-4">
            <div class="w-16 h-16 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto text-2xl font-bold">
                ✕
            </div>
            <h1 class="text-xl font-extrabold text-gray-900">Formulir Tidak Ditemukan</h1>
            <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                {{ message || 'Tautan formulir evaluasi atasan yang Anda buka tidak valid atau sudah kadaluarsa.' }}
            </p>
            <div class="pt-2">
                <Link href="/" class="inline-flex items-center justify-center px-6 py-3 bg-[#005B3C] text-white font-bold text-xs rounded-2xl hover:bg-[#00472f] transition-colors">
                    Kembali ke Halaman Utama
                </Link>
            </div>
        </div>
    </div>
</template>
