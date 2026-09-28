<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <!-- Tambahkan pt-40 untuk memberi ruang bagi card yang overlap dari atas -->
    <section class="py-24 pt-40 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 gsap-fade-up">
            <div class="flex flex-col lg:flex-row gap-12 lg:gap-16 items-start">
                
                <!-- Kiri: Thumbnails Video -->
                <div class="w-full lg:w-5/12 space-y-6">
                    <!-- Thumbnail 1 -->
                    <div class="relative rounded-xl overflow-hidden shadow-lg group cursor-pointer border border-gray-200 bg-gray-100 aspect-video flex items-center justify-center">
                        <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&q=80&w=800" alt="Video Thumbnail" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                        <!-- Overlay Title Box (Style UB) -->
                        <div class="absolute top-4 left-0 bg-yellow-400 text-green-900 font-bold py-2 px-4 shadow-md max-w-[80%] uppercase transform -skew-x-12 -ml-2">
                            <div class="skew-x-12 px-2">Alur Pengisian Tracer Study</div>
                        </div>
                        <!-- Play Button -->
                        <div class="absolute z-10 w-16 h-12 bg-white rounded flex items-center justify-center shadow-lg group-hover:bg-green-600 group-hover:text-white transition-colors duration-300">
                            <svg class="w-8 h-8 ml-1 text-green-900 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>

                    <!-- Thumbnail 2 -->
                    <div class="relative rounded-xl overflow-hidden shadow-lg group cursor-pointer border border-gray-200 bg-gray-100 aspect-video flex items-center justify-center">
                        <img src="https://images.unsplash.com/photo-1571260899304-42507011ec70?auto=format&fit=crop&q=80&w=800" alt="Video Thumbnail" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                        <div class="absolute top-4 left-0 bg-[#004D25] text-white font-bold py-2 px-4 shadow-md max-w-[80%] uppercase transform -skew-x-12 -ml-2">
                            <div class="skew-x-12 px-2">Serba-Serbi Tracer Study UKDW</div>
                        </div>
                        <div class="absolute z-10 w-16 h-12 bg-white rounded flex items-center justify-center shadow-lg group-hover:bg-green-600 group-hover:text-white transition-colors duration-300">
                            <svg class="w-8 h-8 ml-1 text-green-900 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Kanan: Konten Teks & Stats -->
                <div class="w-full lg:w-7/12">
                    <h2 class="text-3xl font-black text-green-900 uppercase tracking-tight mb-2">TRACER STUDY</h2>
                    <h3 class="text-2xl font-medium text-gray-800 mb-6">Definisi dan Responden</h3>
                    
                    <p class="text-gray-600 leading-relaxed mb-8 text-justify">
                        Tracer Study UKDW adalah studi pelacakan jejak alumni Universitas Kristen Duta Wacana yang dilakukan beberapa bulan/tahun setelah lulus dalam rangka memperoleh umpan balik dari alumni guna mengevaluasi proses pelayanan dan pemberian masukan kepada universitas. Tracer Study UKDW menggunakan target responden seluruh populasi lulusan tahun tertentu dari berbagai fakultas. Simak video edukasi di samping untuk lebih memahami Tracer Study UKDW.
                    </p>

                    <!-- Checklist Program Studi -->
                    <div class="grid grid-cols-2 gap-4 mb-10 text-gray-700">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Program Diploma</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Program Magister</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Program Sarjana</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Program Doktor</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Program Profesi</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Program Spesialis</span>
                        </div>
                    </div>

                    <!-- Stats & CTA -->
                    <div class="flex flex-wrap gap-12 items-end">
                        <div>
                            <div class="text-5xl font-black text-gray-900">14</div>
                            <div class="text-sm font-bold text-gray-500 tracking-widest uppercase mt-1">Program Studi</div>
                        </div>
                        <div>
                            <div class="text-5xl font-black text-gray-900">7</div>
                            <div class="text-sm font-bold text-gray-500 tracking-widest uppercase mt-1">Fakultas</div>
                        </div>
                        <div class="ml-auto mt-6 md:mt-0">
                            <Link href="/login" class="inline-flex items-center px-8 py-3 bg-yellow-400 text-green-900 font-bold rounded shadow-md hover:bg-yellow-500 hover:shadow-lg transition-all duration-300">
                                Isi Kuesioner
                                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                            </Link>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
</template>
