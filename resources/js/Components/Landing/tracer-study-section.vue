<template>
    <section id="tentang" class="py-24 bg-green-900 relative overflow-hidden">
        <!-- Decorative Background Elements -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0">
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-green-800 rounded-full mix-blend-multiply filter blur-3xl opacity-70"></div>
            <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-yellow-900 rounded-full mix-blend-multiply filter blur-3xl opacity-50"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 gsap-fade-up">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-black text-white sm:text-4xl uppercase tracking-wider drop-shadow-md">Mengapa Tracer Study Penting?</h2>
                <p class="mt-4 text-lg text-green-100 max-w-2xl mx-auto font-medium">
                    Partisipasi Anda memberikan dampak nyata bagi pengembangan kurikulum dan almamater.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Card 1 (Up) - Yellow Accent -->
                <div class="gsap-fade-up relative bg-white/10 backdrop-blur-xl rounded-2xl p-8 border border-white/20 text-white shadow-[0_8px_32px_0_rgba(31,38,135,0.2)] hover:shadow-[0_8px_32px_0_rgba(250,204,21,0.3)] hover:-translate-y-3 transition-all duration-500 group overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-yellow-400/20 rounded-full blur-3xl group-hover:bg-yellow-400/40 transition-colors duration-500"></div>
                    <div class="relative z-10">
                        <div class="w-14 h-14 bg-yellow-400/20 border border-yellow-400/30 rounded-xl flex items-center justify-center mb-6 text-yellow-400 group-hover:bg-yellow-400 group-hover:text-green-900 transition-colors duration-300 shadow-lg">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3 group-hover:text-yellow-300 transition-colors">Jejak Karier</h3>
                        <p class="text-green-50 text-sm leading-relaxed">Memahami perjalanan alumni setelah menyelesaikan pendidikan di UKDW dalam dunia profesional.</p>
                    </div>
                </div>

                <!-- Card 2 (Down) - Emerald/Gold Accent -->
                <div class="gsap-fade-down relative bg-white/10 backdrop-blur-xl rounded-2xl p-8 border border-white/20 text-white shadow-[0_8px_32px_0_rgba(0,56,26,0.2)] hover:shadow-[0_8px_32px_0_rgba(255,199,0,0.3)] hover:-translate-y-3 transition-all duration-500 group overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-[#FFC700]/20 rounded-full blur-3xl group-hover:bg-[#FFC700]/40 transition-colors duration-500"></div>
                    <div class="relative z-10">
                        <div class="w-14 h-14 bg-[#FFC700]/20 border border-[#FFC700]/30 rounded-xl flex items-center justify-center mb-6 text-[#FFC700] group-hover:bg-[#FFC700] group-hover:text-slate-950 transition-colors duration-300 shadow-lg">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3 group-hover:text-[#FFC700] transition-colors">Evaluasi Pendidikan</h3>
                        <p class="text-green-50 text-sm leading-relaxed">Menjadi salah satu sumber informasi untuk mengevaluasi relevansi pendidikan dengan kebutuhan dunia kerja.</p>
                    </div>
                </div>

                <!-- Card 3 (Up) - Rose Accent -->
                <div class="gsap-fade-up relative bg-white/10 backdrop-blur-xl rounded-2xl p-8 border border-white/20 text-white shadow-[0_8px_32px_0_rgba(31,38,135,0.2)] hover:shadow-[0_8px_32px_0_rgba(244,63,94,0.3)] hover:-translate-y-3 transition-all duration-500 group overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-rose-400/20 rounded-full blur-3xl group-hover:bg-rose-400/40 transition-colors duration-500"></div>
                    <div class="relative z-10">
                        <div class="w-14 h-14 bg-rose-400/20 border border-rose-400/30 rounded-xl flex items-center justify-center mb-6 text-rose-400 group-hover:bg-rose-400 group-hover:text-white transition-colors duration-300 shadow-lg">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3 group-hover:text-rose-300 transition-colors">Hubungan Alumni</h3>
                        <p class="text-green-50 text-sm leading-relaxed">Memperkuat hubungan, jaringan, serta kolaborasi antara alumni dan almamater tercinta.</p>
                    </div>
                </div>

                <!-- Card 4 (Down) - Purple Accent -->
                <div class="gsap-fade-down relative bg-white/10 backdrop-blur-xl rounded-2xl p-8 border border-white/20 text-white shadow-[0_8px_32px_0_rgba(31,38,135,0.2)] hover:shadow-[0_8px_32px_0_rgba(168,85,247,0.3)] hover:-translate-y-3 transition-all duration-500 group overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-purple-400/20 rounded-full blur-3xl group-hover:bg-purple-400/40 transition-colors duration-500"></div>
                    <div class="relative z-10">
                        <div class="w-14 h-14 bg-purple-400/20 border border-purple-400/30 rounded-xl flex items-center justify-center mb-6 text-purple-400 group-hover:bg-purple-400 group-hover:text-white transition-colors duration-300 shadow-lg">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3 group-hover:text-purple-300 transition-colors">Masa Depan UKDW</h3>
                        <p class="text-green-50 text-sm leading-relaxed">Mendukung pengembangan institusi dan fasilitas pendidikan berdasarkan data riil pengalaman alumni.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
