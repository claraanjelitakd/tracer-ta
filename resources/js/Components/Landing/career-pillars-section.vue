<script setup>
</script>

<template>
    <section id="pilar" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Section Header Centered matching Wireframe -->
            <div class="text-center max-w-xl mx-auto mb-14">
                <span class="text-xs font-bold text-[#0D542B] tracking-wider uppercase">Pilar Keilmuan</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mt-1">
                    Fokus Bidang & Ekosistem Lulusan
                </h2>
                <p class="text-slate-500 text-sm mt-2">
                    Tiga pilar keunggulan akademik UKDW yang mencetak alumni berintegritas dan berdaya saing global.
                </p>
            </div>

            <!-- 3 Pillars Cards (01 Dark Active, 02 White, 03 White) matching Wireframe -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <!-- Pillar 01 (Dark Active Card) -->
                <div class="bg-slate-900 text-white rounded-2xl p-8 border border-slate-800 shadow-md flex flex-col justify-between relative overflow-hidden group">
                    <div class="absolute -top-12 -right-12 w-40 h-40 bg-emerald-500/10 rounded-full blur-2xl"></div>
                    
                    <div>
                        <!-- Number & Mascot Header -->
                        <div class="flex items-center justify-between mb-6">
                            <span class="text-2xl font-black text-emerald-400">01</span>
                            <div class="w-14 h-14 rounded-xl bg-white/10 p-1 flex items-center justify-center border border-white/10 group-hover:scale-110 transition-transform">
                                <img src="/uploads/pigo/anak-it.png" alt="Anak IT Pigo" class="w-full h-full object-contain" />
                            </div>
                        </div>

                        <h3 class="text-xl font-extrabold text-white mb-3">
                            Teknologi & Rekayasa
                        </h3>

                        <p class="text-slate-300 text-sm leading-relaxed mb-6">
                            Pengembangan sistem perangkat lunak terdistribusi, kecerdasan buatan, arsitektur data cloud, dan game engineering yang beradaptasi dengan disrupsi digital dunia.
                        </p>
                    </div>

                    <div class="pt-6 border-t border-slate-800 flex items-center justify-between text-xs font-semibold text-emerald-300">
                        <span>Fakultas Teknologi Informasi</span>
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </div>
                </div>

                <!-- Pillar 02 (White Card) -->
                <div class="bg-white text-slate-900 rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between group">
                    <div>
                        <!-- Number & Mascot Header -->
                        <div class="flex items-center justify-between mb-6">
                            <span class="text-2xl font-black text-slate-300 group-hover:text-[#0D542B] transition-colors">02</span>
                            <div class="w-14 h-14 rounded-xl bg-emerald-50 p-1 flex items-center justify-center border border-emerald-100 group-hover:scale-110 transition-transform">
                                <img src="/uploads/pigo/pigo-dokter.png" alt="Pigo Dokter" class="w-full h-full object-contain" />
                            </div>
                        </div>

                        <h3 class="text-xl font-extrabold text-slate-900 group-hover:text-[#0D542B] transition-colors mb-3">
                            Kedokteran & Kesehatan
                        </h3>

                        <p class="text-slate-600 text-sm leading-relaxed mb-6">
                            Pendidikan dokter dan tenaga kesehatan profesional berhati melayani, berkomitmen kuat terhadap kesehatan masyarakat, etika medis, dan pelayanan wilayah terpencil.
                        </p>
                    </div>

                    <div class="pt-6 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-[#0D542B]">
                        <span>Fakultas Kedokteran</span>
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </div>
                </div>

                <!-- Pillar 03 (White Card) -->
                <div class="bg-white text-slate-900 rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between group">
                    <div>
                        <!-- Number & Mascot Header -->
                        <div class="flex items-center justify-between mb-6">
                            <span class="text-2xl font-black text-slate-300 group-hover:text-[#0D542B] transition-colors">03</span>
                            <div class="w-14 h-14 rounded-xl bg-amber-50 p-1 flex items-center justify-center border border-amber-100 group-hover:scale-110 transition-transform">
                                <img src="/uploads/pigo/pigo-laptop.png" alt="Pigo Bisnis" class="w-full h-full object-contain" />
                            </div>
                        </div>

                        <h3 class="text-xl font-extrabold text-slate-900 group-hover:text-[#0D542B] transition-colors mb-3">
                            Bisnis, Desain & Humaniora
                        </h3>

                        <p class="text-slate-600 text-sm leading-relaxed mb-6">
                            Kewirausahaan beretika, akuntansi manajerial, arsitektur berkelanjutan, desain produk, teologi inklusif, dan penguasaan bahasa global yang humanis.
                        </p>
                    </div>

                    <div class="pt-6 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-[#0D542B]">
                        <span>Bisnis, Arsitektur & Kependidikan</span>
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </div>
                </div>

            </div>

        </div>
    </section>
</template>
