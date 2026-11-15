<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Pembelajaran - {{ $siteName }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1E293B; margin: 20px; }
        .header { border-bottom: 2px solid #1B2CC1; padding-bottom: 10px; margin-bottom: 14px; }
        .brand { font-size: 16px; font-weight: bold; color: #1B2CC1; }
        .sub { font-size: 11px; color: #475569; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #1B2CC1; color: #ffffff; text-align: left; padding: 6px 8px; font-size: 9px; }
        td { border-bottom: 1px solid #CBD5E1; padding: 6px 8px; }
        tr:nth-child(even) td { background-color: #F8FAFC; }
        .num { text-align: right; }
        .footer { margin-top: 16px; font-size: 9px; color: #475569; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">{{ $siteName }} - {{ $institutionName }}</div>
        <div class="sub">
            Laporan Rekapitulasi Pembelajaran
            @if($course)
                | Kelas: {{ $course->code }} - {{ $course->title }}
            @endif
            | Dicetak: {{ $generatedAt }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIM / ID</th>
                <th>Nama Peserta</th>
                <th>Kode</th>
                <th>Kelas</th>
                <th>Pengajar</th>
                <th>Status</th>
                <th class="num">Progres</th>
                <th class="num">Tugas</th>
                <th class="num">Kuis</th>
                <th class="num">Ujian</th>
                <th class="num">Akhir</th>
                <th>Mutu</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['identity_number'] }}</td>
                    <td>{{ $row['student_name'] }}</td>
                    <td>{{ $row['course_code'] }}</td>
                    <td>{{ $row['course_title'] }}</td>
                    <td>{{ $row['instructor'] }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td class="num">{{ number_format($row['progress'], 1) }}%</td>
                    <td class="num">{{ number_format($row['assignment_avg'], 1) }}</td>
                    <td class="num">{{ number_format($row['quiz_avg'], 1) }}</td>
                    <td class="num">{{ number_format($row['exam_avg'], 1) }}</td>
                    <td class="num"><strong>{{ number_format($row['final_score'], 1) }}</strong></td>
                    <td><strong>{{ $row['letter_grade'] }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" style="text-align: center; padding: 16px;">Belum ada data peserta untuk filter yang dipilih.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dokumen resmi dihasilkan oleh {{ $siteName }}. Total baris: {{ count($rows) }}.
    </div>
</body>
</html>
