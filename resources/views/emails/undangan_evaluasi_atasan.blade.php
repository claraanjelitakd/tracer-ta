<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Undangan Evaluasi Pengguna Lulusan UKDW</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
            color: #333333;
        }
        .email-container {
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
        }
        .header {
            background-color: #005B3C;
            padding: 28px 32px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.3px;
        }
        .header p {
            margin: 6px 0 0 0;
            font-size: 13px;
            opacity: 0.9;
        }
        .content {
            padding: 32px;
        }
        .greeting {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 12px;
        }
        .text {
            font-size: 14px;
            line-height: 1.6;
            color: #4b5563;
            margin-bottom: 20px;
        }
        .info-card {
            background-color: #f0fdf4;
            border-left: 4px solid #005B3C;
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        .info-card p {
            margin: 4px 0;
            font-size: 13px;
            color: #166534;
        }
        .info-card strong {
            color: #005B3C;
        }
        .cta-button {
            display: block;
            width: fit-content;
            margin: 28px auto;
            padding: 14px 28px;
            background-color: #005B3C;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 91, 60, 0.25);
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px 32px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            border-top: 1px solid #f3f4f6;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>UNIVERSITAS KRISTEN DUTA WACANA</h1>
            <p>Form Evaluasi Kepuasan & Kinerja Lulusan</p>
        </div>
        <div class="content">
            <div class="greeting">Kepada Yth. Bapak/Ibu Atasan / Pimpinan,</div>
            <div class="text">
                Semoga Bapak/Ibu dalam keadaan sehat dan sukses selalu.
                Dalam rangka peningkatan mutu pendidikan dan kurikulum di <strong>Universitas Kristen Duta Wacana (UKDW) Yogyakarta</strong>, kami bermaksud memohon kesediaan Bapak/Ibu untuk memberikan evaluasi singkat terhadap kinerja lulusan kami yang bekerja di perusahaan/instansi Anda.
            </div>

            <div class="info-card">
                <p><strong>Nama Alumni:</strong> {{ $biodata->nama ?? $biodata->dataAkademik?->nama ?? 'Alumni UKDW' }}</p>
                <p><strong>NIM:</strong> {{ $biodata->nim ?? '-' }}</p>
                <p><strong>Program Studi:</strong> {{ $biodata->prodi?->nama_prodi ?? $biodata->dataAkademik?->program_studi ?? '-' }}</p>
                <p><strong>Perusahaan/Instansi:</strong> {{ $biodata->perusahaan?->nama_perusahaan ?? '-' }}</p>
            </div>

            <div class="text">
                Silakan klik tombol di bawah ini untuk mengisi formulir evaluasi secara langsung (tanpa perlu melakukan login):
            </div>

            <a href="{{ $evaluasiUrl }}" class="cta-button" target="_blank">Isi Formulir Evaluasi Kinerja</a>

            <div class="text" style="font-size: 12px; color: #6b7280; text-align: center;">
                Jika tombol di atas tidak dapat diklik, silakan salin tautan berikut ke peramban (browser) Anda:<br>
                <a href="{{ $evaluasiUrl }}" style="color: #005B3C; word-break: break-all;">{{ $evaluasiUrl }}</a>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Pusat Karir & Alumni Universitas Kristen Duta Wacana (UKDW)<br>
            Jl. Dr. Wahidin Sudirohusodo No. 5-19, Yogyakarta 55224 | Telp: 0274-563929
        </div>
    </div>
</body>
</html>
