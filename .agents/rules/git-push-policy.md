# Aturan Git & Kebijakan Push (Git Push Policy)

## ATURAN MUTLAK: DILARANG MELAKUKAN GIT PUSH OTOMATIS
Agent / AI **DILARANG KERAS** menjalankan perintah `git push` ke remote repository (GitHub) tanpa perintah eksplisit dan langsung dari pengguna.

### Prosedur Alur Kerja:
1. **Lokal Saja:** Setiap perbaikan bug, penambahan fitur, atau modifikasi kode hanya boleh dilakukan di lingkungan lokal.
2. **Uji & Validasi:** Pastikan semua pengujian (`php artisan test`, `npm run build`, dan Pint formatting) berjalan sukses di lokal.
3. **Lapor ke Pengguna:** Laporkan hasil perubahan secara jelas dan transparan kepada pengguna agar pengguna dapat memeriksa dan menguji hasilnya langsung di browser terlebih dahulu.
4. **Tunggu Izin Eksplisit:** Hanya jalankan `git push` apabila pengguna mengirimkan instruksi eksplisit seperti *"push"*, *"push aja"*, atau *"tolong push ke github"*.
5. **Jangan Mengasumsikan:** Izin push sebelumnya TIDAK berlaku untuk perubahan berikutnya. Setiap perubahan baru HARUS menunggu perintah push baru dari pengguna.
