<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Sertifikat {{ $certificate->certificate_number }}</title>
    <style>
        @page { margin: 0; }
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            padding: 36px;
            background-color: #F8FAFC;
            color: #1E293B;
        }
        .frame {
            border: 8px solid #1B2CC1;
            padding: 36px 48px;
            background-color: #ffffff;
            position: relative;
        }
        .inner-border {
            border: 2px solid #ABD2FA;
            padding: 32px 40px;
            text-align: center;
        }
        .institution {
            font-size: 13px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #1B2CC1;
            font-weight: bold;
        }
        .title {
            font-size: 32px;
            font-weight: bold;
            color: #1E293B;
            margin: 18px 0 8px;
        }
        .subtitle {
            font-size: 13px;
            color: #475569;
            margin-bottom: 24px;
        }
        .recipient {
            font-size: 28px;
            font-weight: bold;
            color: #1B2CC1;
            border-bottom: 2px solid #7692FF;
            display: inline-block;
            padding: 0 24px 8px;
            margin-bottom: 18px;
        }
        .course-name {
            font-size: 18px;
            font-weight: bold;
            color: #1E293B;
            margin: 10px 0;
        }
        .score-pill {
            display: inline-block;
            background-color: #ABD2FA;
            color: #1E293B;
            padding: 6px 18px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 10px;
        }
        .meta-table {
            width: 100%;
            margin-top: 42px;
            font-size: 11px;
            color: #475569;
        }
        .meta-table td {
            width: 33.3%;
            vertical-align: bottom;
        }
        .sign-line {
            border-top: 1px solid #1B2CC1;
            width: 180px;
            margin: 36px auto 6px;
        }
        .serial {
            font-size: 10px;
            color: #475569;
            margin-top: 18px;
        }
    </style>
</head>
<body>
    <div class="frame">
        <div class="inner-border">
            <div class="institution">{{ $siteName }} - {{ $institutionName }}</div>
            <div class="title">SERTIFIKAT KELULUSAN</div>
            <div class="subtitle">Diberikan sebagai pengakuan atas penyelesaian kelas secara tuntas kepada:</div>

            <div class="recipient">{{ $certificate->student?->name }}</div>

            <div style="font-size: 13px; color: #475569;">Telah memenuhi seluruh capaian pembelajaran pada kelas:</div>
            <div class="course-name">{{ $certificate->course?->title }} ({{ $certificate->course?->code }})</div>

            <div class="score-pill">Nilai Akhir: {{ number_format((float) $certificate->final_score, 2) }} / 100</div>

            <table class="meta-table">
                <tr>
                    <td style="text-align: left;">
                        Tanggal Terbit<br>
                        <strong style="color: #1E293B;">{{ $certificate->issued_at?->format('d F Y') }}</strong>
                    </td>
                    <td style="text-align: center;">
                        Periode Akademik<br>
                        <strong style="color: #1E293B;">{{ $certificate->course?->academicPeriod?->name ?? 'Reguler' }}</strong>
                    </td>
                    <td style="text-align: center;">
                        <div class="sign-line"></div>
                        <strong style="color: #1E293B;">{{ $certificate->course?->instructor?->name ?? 'Pengajar Kelas' }}</strong><br>
                        Pengajar Pengampu
                    </td>
                </tr>
            </table>

            <div class="serial">
                Nomor Sertifikat: {{ $certificate->certificate_number }} | Kode Verifikasi: {{ substr($certificate->verification_hash, 0, 16) }}
            </div>
        </div>
    </div>
</body>
</html>
