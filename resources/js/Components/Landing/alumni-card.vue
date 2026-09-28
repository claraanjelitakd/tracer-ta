<script setup>
defineProps({
    alumni: {
        type: Object,
        required: true
    }
});
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl hover:-translate-y-2 hover:border-green-200 transition-all duration-300 group flex flex-col">
        <!-- Banner Bagian Atas Card -->
        <div class="h-24 bg-gradient-to-r from-green-600 to-green-400 relative">
            <!-- Icon Linkedin di pojok -->
            <a :href="alumni.linkedin" target="_blank" class="absolute top-4 right-4 text-white hover:text-[#FFC700] transition-colors">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                </svg>
            </a>
        </div>
        
        <div class="p-6 pt-0 relative flex-1 flex flex-col">
            <!-- Foto Profil (Naik ke atas banner) -->
            <div class="w-20 h-20 rounded-full border-4 border-white overflow-hidden bg-gray-200 -mt-10 mb-4 shadow-md">
                <img :src="alumni.image" :alt="alumni.name" class="w-full h-full object-cover">
            </div>
            
            <div class="mb-4">
                <h3 class="text-xl font-bold text-gray-900 group-hover:text-green-700 transition-colors">{{ alumni.name }}</h3>
                <p class="text-sm text-gray-500 font-medium">{{ alumni.prodi }} - Angkatan {{ alumni.angkatan }}</p>
            </div>
            
            <div class="mt-auto space-y-3">
                <!-- Job Info -->
                <div class="flex items-start space-x-3 text-sm text-gray-600">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    <div>
                        <div class="font-semibold text-gray-800">{{ alumni.job }}</div>
                        <div>{{ alumni.company }}</div>
                    </div>
                </div>
                
                <!-- Location Info -->
                <div class="flex items-center space-x-3 text-sm text-gray-600">
                    <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span>{{ alumni.province }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
