<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengingat Pengisian Tracer Study UKDW</title>
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
            background-color: #0D542B;
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
            color: #d1fae5;
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
            border-left: 4px solid #0D542B;
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        .info-card h4 {
            margin: 0 0 8px 0;
            color: #0D542B;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-item {
            font-size: 13px;
            margin-bottom: 6px;
            color: #374151;
        }
        .info-item strong {
            color: #111827;
        }
        .btn-container {
            text-align: center;
            margin: 28px 0;
        }
        .btn {
            display: inline-block;
            background-color: #0D542B;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(13, 84, 43, 0.25);
        }
        .btn:hover {
            background-color: #08381c;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px 32px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            border-top: 1px solid #f3f4f6;
        }
        .footer p {
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>TRACER STUDY UKDW</h1>
            <p>Biro III Kemahasiswaan, Alumni, dan Pengembangan Karir</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Halo Sdr/i {{ $namaAlumni }},
            </div>
            
            <div class="text">
                Salam hangat dari almamater tercinta, Universitas Kristen Duta Wacana (UKDW).
            </div>

            <div class="text">
                Kami mengundang Anda untuk berpartisipasi dalam pengisian instrumen <strong>Tracer Study UKDW (Kemendikbudristek Dikti)</strong>. Data yang Anda sampaikan sangat berharga bagi evaluasi kurikulum, peningkatan mutu pendidikan, serta akreditasi program studi dan universitas kita.
            </div>

            <!-- Petunjuk Login -->
            <div class="info-card">
                <h4>Panduan Masuk ke Portal</h4>
                <div class="info-item"><strong>URL Portal:</strong> <a href="{{ $portalUrl }}" style="color: #0D542B;">{{ $portalUrl }}</a></div>
                <div class="info-item"><strong>Username:</strong> {{ $nim }} (NIM Anda)</div>
                <div class="info-item"><strong>Kata Sandi:</strong> Kata sandi akun Tracer Study Anda</div>
            </div>

            <!-- Tombol CTA -->
            <div class="btn-container">
                <a href="{{ $portalUrl }}" class="btn">
                    Masuk & Isi Kuesioner Sekarang &rarr;
                </a>
            </div>

            <div class="text" style="font-size: 13px; color: #6b7280;">
                Waktu yang dibutuhkan untuk pengisian kurang lebih 5&ndash;10 menit. Jika Anda mengalami kendala teknis saat masuk atau mengisi kuesioner, silakan hubungi tim Biro 3 UKDW melalui email resmi atau layanan kemahasiswaan.
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Universitas Kristen Duta Wacana (UKDW)</strong></p>
            <p>Jl. Dr. Wahidin Sudirohusodo No. 5-25, Kotabaru, Yogyakarta 55224</p>
            <p>&copy; {{ date('Y') }} Biro III Kemahasiswaan & Alumni UKDW. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
