<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionSpreadsheetService
{
    private const MAX_ROWS = 500;

    public function downloadTemplate(): StreamedResponse
    {
        $filename = 'template-soal-verdantlms.xlsx';

        return response()->streamDownload(function (): void {
            $options = new Options();
            // Merge title & instruction banners on Sheet 0 (A1:M1, A2:M2) and Sheet 1 (A1:C1)
            $options->mergeCells(0, 1, 12, 1, 0);
            $options->mergeCells(0, 2, 12, 2, 0);
            $options->mergeCells(0, 1, 2, 1, 1);

            $writer = new Writer($options);
            $writer->openToFile('php://output');

            $cellBorder = new Border(
                new BorderPart(Border::TOP, 'CBD5E1', Border::WIDTH_THIN, Border::STYLE_SOLID),
                new BorderPart(Border::BOTTOM, 'CBD5E1', Border::WIDTH_THIN, Border::STYLE_SOLID),
                new BorderPart(Border::LEFT, 'CBD5E1', Border::WIDTH_THIN, Border::STYLE_SOLID),
                new BorderPart(Border::RIGHT, 'CBD5E1', Border::WIDTH_THIN, Border::STYLE_SOLID),
            );

            $headerBorder = new Border(
                new BorderPart(Border::TOP, '142194', Border::WIDTH_THIN, Border::STYLE_SOLID),
                new BorderPart(Border::BOTTOM, '142194', Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                new BorderPart(Border::LEFT, '3B4FD9', Border::WIDTH_THIN, Border::STYLE_SOLID),
                new BorderPart(Border::RIGHT, '3B4FD9', Border::WIDTH_THIN, Border::STYLE_SOLID),
            );

            $titleStyle = (new Style())
                ->setFontBold()
                ->setFontSize(13)
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor('1E293B')
                ->setCellAlignment(CellAlignment::LEFT)
                ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);

            $subtitleStyle = (new Style())
                ->setFontSize(10)
                ->setFontColor('1E3A8A')
                ->setBackgroundColor('EFF6FF')
                ->setCellAlignment(CellAlignment::LEFT)
                ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
                ->setShouldWrapText();

            $headerStyle = (new Style())
                ->setFontBold()
                ->setFontSize(10)
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor('1B2CC1')
                ->setBorder($headerBorder)
                ->setCellAlignment(CellAlignment::LEFT)
                ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
                ->setShouldWrapText();

            $headerCenterStyle = (new Style())
                ->setFontBold()
                ->setFontSize(10)
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor('1B2CC1')
                ->setBorder($headerBorder)
                ->setCellAlignment(CellAlignment::CENTER)
                ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
                ->setShouldWrapText();

            $sampleStyle = (new Style())
                ->setFontSize(10)
                ->setFontColor('713F12')
                ->setBackgroundColor('FEFCE8')
                ->setBorder($cellBorder)
                ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
                ->setShouldWrapText();

            $sampleCenterStyle = (new Style())
                ->setFontBold()
                ->setFontSize(10)
                ->setFontColor('713F12')
                ->setBackgroundColor('FEFCE8')
                ->setBorder($cellBorder)
                ->setCellAlignment(CellAlignment::CENTER)
                ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
                ->setShouldWrapText();

            $evenStyle = (new Style())
                ->setFontSize(10)
                ->setBackgroundColor('FFFFFF')
                ->setBorder($cellBorder)
                ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
                ->setShouldWrapText();

            $evenCenterStyle = (new Style())
                ->setFontSize(10)
                ->setBackgroundColor('FFFFFF')
                ->setBorder($cellBorder)
                ->setCellAlignment(CellAlignment::CENTER)
                ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);

            $oddStyle = (new Style())
                ->setFontSize(10)
                ->setBackgroundColor('F8FAFC')
                ->setBorder($cellBorder)
                ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
                ->setShouldWrapText();

            $oddCenterStyle = (new Style())
                ->setFontSize(10)
                ->setBackgroundColor('F8FAFC')
                ->setBorder($cellBorder)
                ->setCellAlignment(CellAlignment::CENTER)
                ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);

            // Sheet 1: Input Soal (dibaca oleh sistem saat import)
            $sheet1 = $writer->getCurrentSheet();
            $sheet1->setName('Input Soal');
            $sheet1->setColumnWidth(6, 1);         // No
            $sheet1->setColumnWidth(16, 2);        // Tipe Soal *
            $sheet1->setColumnWidth(48, 3);        // Pertanyaan *
            $sheet1->setColumnWidth(9, 4);         // Poin
            $sheet1->setColumnWidth(13, 5);        // Kesulitan
            $sheet1->setColumnWidth(22, 6, 7, 8, 9); // Pilihan A-D
            $sheet1->setColumnWidth(18, 10, 11);   // Pilihan E-F
            $sheet1->setColumnWidth(16, 12);       // Kunci Jawaban
            $sheet1->setColumnWidth(36, 13);       // Pembahasan

            $writer->addRow(Row::fromValues(['  TEMPLATE IMPOR SOAL — VERDANTLMS'], $titleStyle)->setHeight(32));
            $writer->addRow(Row::fromValues([
                '  Petunjuk: Isi mulai baris bernomor 1–20. Baris kuning [CONTOH] dapat dihapus atau ditimpa (lihat tab "Panduan Pengisian" untuk aturan lengkap).',
            ], $subtitleStyle)->setHeight(24));
            $writer->addRow(Row::fromValues([''])->setHeight(8));

            $headers = [
                'No',
                'Tipe Soal *',
                'Pertanyaan *',
                'Poin',
                'Kesulitan',
                'Pilihan A',
                'Pilihan B',
                'Pilihan C',
                'Pilihan D',
                'Pilihan E',
                'Pilihan F',
                'Kunci Jawaban',
                'Pembahasan',
            ];
            $writer->addRow(Row::fromValuesWithStyles($headers, $headerStyle, [
                0 => $headerCenterStyle,
                1 => $headerCenterStyle,
                3 => $headerCenterStyle,
                4 => $headerCenterStyle,
                11 => $headerCenterStyle,
            ])->setHeight(28));

            $sampleRows = [
                [
                    'C1',
                    'PG',
                    '[CONTOH] Protokol standar untuk komunikasi web terenkripsi adalah...',
                    10,
                    'easy',
                    'HTTP',
                    'HTTPS',
                    'FTP',
                    'SMTP',
                    '',
                    '',
                    'B',
                    'HTTPS menambahkan lapisan enkripsi TLS/SSL di atas HTTP.',
                ],
                [
                    'C2',
                    'BS',
                    '[CONTOH] Method HTTP POST bersifat idempotent.',
                    10,
                    'medium',
                    'Benar',
                    'Salah',
                    '',
                    '',
                    '',
                    '',
                    'Salah',
                    'POST digunakan untuk membuat resource baru dan tidak bersifat idempotent.',
                ],
                [
                    'C3',
                    'Essay',
                    '[CONTOH] Jelaskan perbedaan autentikasi dan otorisasi pada aplikasi web.',
                    20,
                    'hard',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Autentikasi memverifikasi identitas, sedangkan otorisasi memeriksa hak akses.',
                ],
            ];

            foreach ($sampleRows as $sample) {
                $writer->addRow(Row::fromValuesWithStyles($sample, $sampleStyle, [
                    0 => $sampleCenterStyle,
                    1 => $sampleCenterStyle,
                    3 => $sampleCenterStyle,
                    4 => $sampleCenterStyle,
                    11 => $sampleCenterStyle,
                ])->setHeight(34));
            }

            // 20 pre-formatted ready-to-fill blank rows
            for ($i = 1; $i <= 20; $i++) {
                $isEven = $i % 2 === 0;
                $rowStyle = $isEven ? $evenStyle : $oddStyle;
                $centerStyle = $isEven ? $evenCenterStyle : $oddCenterStyle;

                $writer->addRow(Row::fromValuesWithStyles(
                    [$i, '', '', '', '', '', '', '', '', '', '', '', ''],
                    $rowStyle,
                    [0 => $centerStyle, 1 => $centerStyle, 3 => $centerStyle, 4 => $centerStyle, 11 => $centerStyle]
                )->setHeight(26));
            }

            // Sheet 2: Panduan Pengisian
            $sheet2 = $writer->addNewSheetAndMakeItCurrent();
            $sheet2->setName('Panduan Pengisian');
            $sheet2->setColumnWidth(22, 1);
            $sheet2->setColumnWidth(50, 2);
            $sheet2->setColumnWidth(42, 3);

            $writer->addRow(Row::fromValues(['  PANDUAN PENGISIAN TEMPLATE SOAL VERDANTLMS'], $titleStyle)->setHeight(32));
            $writer->addRow(Row::fromValues(['Kolom', 'Aturan Pengisian', 'Contoh / Nilai yang Diterima'], $headerStyle)->setHeight(26));

            $guides = [
                ['No', 'Nomor urut bantu untuk guru (tidak wajib diubah; baris yang Pertanyaannya kosong otomatis dilewati).', '1, 2, 3, ...'],
                ['Tipe Soal *', 'Jenis soal. Boleh memakai singkatan atau bahasa Indonesia (kosong = otomatis PG).', 'PG, Pilihan Ganda, BS, Benar Salah, Essay'],
                ['Pertanyaan *', 'Teks pertanyaan utama (wajib diisi untuk setiap baris soal).', 'Apa fungsi middleware di Laravel?'],
                ['Poin', 'Bobot poin angka antara 1 sampai 500 (jika dikosongkan otomatis 10).', '10, 20, 25'],
                ['Kesulitan', 'Tingkat kesulitan soal (jika dikosongkan otomatis medium).', 'easy / mudah, medium / sedang, hard / sulit'],
                ['Pilihan A – F', 'Untuk PG wajib isi minimal 2 pilihan. Untuk BS boleh kosong (otomatis Benar & Salah). Kosongkan untuk Essay.', 'Pilihan A: HTTP, Pilihan B: HTTPS'],
                ['Kunci Jawaban', 'Untuk PG: tulis huruf A–F, angka 1–6, atau teks pilihan. Untuk BS: A/B atau Benar/Salah. Kosongkan untuk Essay.', 'B, 2, Benar, Salah'],
                ['Pembahasan', 'Penjelasan kunci jawaban yang tampil ke siswa setelah ujian selesai (opsional).', 'Middleware menyaring request HTTP sebelum masuk controller.'],
            ];

            foreach ($guides as $idx => $guide) {
                $style = $idx % 2 === 0 ? $evenStyle : $oddStyle;
                $writer->addRow(Row::fromValues($guide, $style)->setHeight(32));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Preview parsed questions & all row-level validation errors without persisting.
     *
     * @return array{
     *   total_rows: int,
     *   valid_count: int,
     *   error_count: int,
     *   rows: array<int, array<string, mixed>>,
     *   errors: array<int, string>
     * }
     */
    public function previewFromUploadedFile(UploadedFile $file): array
    {
        $analysis = $this->analyzeUploadedFile($file);

        return [
            'total_rows' => count($analysis['preview_rows']),
            'valid_count' => count($analysis['parsed']),
            'error_count' => count($analysis['errors']),
            'rows' => $analysis['preview_rows'],
            'errors' => $analysis['errors'],
        ];
    }

    /**
     * Import questions from an uploaded .xlsx file.
     *
     * @return array{imported_count: int, questions: array<int, Question>}
     */
    public function importFromUploadedFile(
        UploadedFile $file,
        User $creator,
        ?int $categoryId,
        ?int $subjectId,
        ?Course $course = null,
        ?Quiz $quiz = null,
        bool $saveToBank = true,
    ): array {
        $analysis = $this->analyzeUploadedFile($file);

        if ($analysis['errors'] !== []) {
            throw ValidationException::withMessages([
                'file' => implode(' | ', $analysis['errors']),
            ]);
        }

        $parsed = $analysis['parsed'];

        if ($parsed === []) {
            throw ValidationException::withMessages([
                'file' => 'Tidak ditemukan baris soal yang terisi pada file Excel.',
            ]);
        }

        $created = DB::transaction(function () use ($parsed, $creator, $categoryId, $subjectId, $course, $quiz, $saveToBank) {
            $questions = [];
            $sortOrder = $quiz ? $quiz->questions()->count() : 0;

            foreach ($parsed as $item) {
                $question = Question::query()->create([
                    'category_id' => $categoryId,
                    'subject_id' => $subjectId,
                    'course_id' => $course?->id,
                    'created_by' => $creator->id,
                    'type' => $item['type'],
                    'question_text' => $item['question_text'],
                    'explanation' => $item['explanation'],
                    'points' => $item['points'],
                    'difficulty' => $item['difficulty'],
                    'is_bank' => $saveToBank,
                ]);

                foreach ($item['options'] as $idx => $opt) {
                    QuestionOption::query()->create([
                        'question_id' => $question->id,
                        'option_text' => $opt['option_text'],
                        'is_correct' => $opt['is_correct'],
                        'sort_order' => $idx + 1,
                    ]);
                }

                if ($quiz) {
                    $sortOrder++;
                    $quiz->questions()->attach($question->id, [
                        'sort_order' => $sortOrder,
                        'points' => $question->points,
                    ]);
                }

                $questions[] = $question;
            }

            return $questions;
        });

        return [
            'imported_count' => count($created),
            'questions' => $created,
        ];
    }

    /**
     * Read and validate all non-empty rows from sheet 1 of an uploaded .xlsx file.
     *
     * @return array{
     *   parsed: array<int, array<string, mixed>>,
     *   preview_rows: array<int, array<string, mixed>>,
     *   errors: array<int, string>
     * }
     */
    private function analyzeUploadedFile(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if ($path === false) {
            throw ValidationException::withMessages(['file' => 'File Excel tidak dapat dibaca.']);
        }

        $reader = new Reader();
        try {
            $reader->open($path);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['file' => 'Format file harus berupa Excel (.xlsx) yang valid.']);
        }

        $rawRows = [];
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    $cells = array_map(fn ($cell) => trim((string) $cell->getValue()), $row->getCells());
                    $rawRows[$rowIndex] = $cells;
                    if (count($rawRows) > self::MAX_ROWS + 5) {
                        throw ValidationException::withMessages([
                            'file' => 'Maksimal ' . self::MAX_ROWS . ' baris soal per sekali unggah.',
                        ]);
                    }
                }
                break; // Only read the first sheet
            }
        } finally {
            $reader->close();
        }

        if ($rawRows === []) {
            throw ValidationException::withMessages([
                'file' => 'File Excel kosong atau tidak memiliki header.',
            ]);
        }

        // Auto-detect header row (supports both row 1 headers and bannered templates where header is around row 4)
        $headerIndex = null;
        foreach ($rawRows as $idx => $cells) {
            $normalizedRow = array_map(
                fn (string $h) => preg_replace('/[\s*]+/', ' ', strtolower(trim($h))) ?? '',
                $cells
            );
            $normalizedRow = array_map('trim', $normalizedRow);
            if (in_array('pertanyaan', $normalizedRow, true) || in_array('question', $normalizedRow, true)) {
                $headerIndex = $idx;
                break;
            }
        }

        if ($headerIndex === null) {
            $headerIndex = array_key_first($rawRows);
        }

        $headerCells = $rawRows[$headerIndex];
        $colMap = $this->resolveColumnMap($headerCells);
        $dataRows = array_filter(
            $rawRows,
            fn (int|string $idx) => (int) $idx > (int) $headerIndex,
            ARRAY_FILTER_USE_KEY
        );

        if ($dataRows === []) {
            throw ValidationException::withMessages([
                'file' => 'File Excel kosong atau belum memiliki baris soal di bawah header.',
            ]);
        }

        $parsed = [];
        $previewRows = [];
        $errors = [];

        foreach ($dataRows as $rowNumber => $cells) {
            // Ignore empty rows and pre-numbered template rows where only the "No" column has a value
            $hasQuestionData = false;
            foreach ($colMap as $key => $colIdx) {
                if ($key === 'no') {
                    continue;
                }
                if (trim((string) ($cells[$colIdx] ?? '')) !== '') {
                    $hasQuestionData = true;
                    break;
                }
            }
            if (! $hasQuestionData) {
                continue;
            }

            try {
                $item = $this->parseRow($cells, $colMap, $rowNumber);
                $parsed[] = $item;

                $correctText = collect($item['options'])
                    ->firstWhere('is_correct', true)['option_text'] ?? ($item['type'] === 'essay' ? 'Penilaian Manual' : '-');

                $previewRows[] = [
                    'row_number' => $rowNumber,
                    'status' => 'valid',
                    'type' => $item['type'],
                    'question_text' => $item['question_text'],
                    'points' => $item['points'],
                    'difficulty' => $item['difficulty'],
                    'options_count' => count($item['options']),
                    'answer_summary' => $correctText,
                    'error' => null,
                ];
            } catch (ValidationException $e) {
                $rowMessages = [];
                foreach ($e->errors() as $msgs) {
                    foreach ($msgs as $msg) {
                        $errors[] = $msg;
                        $rowMessages[] = $msg;
                    }
                }

                $previewRows[] = [
                    'row_number' => $rowNumber,
                    'status' => 'error',
                    'type' => trim((string) ($cells[$colMap['type']] ?? '-')),
                    'question_text' => trim((string) ($cells[$colMap['question_text']] ?? '')) ?: '(Kosong)',
                    'points' => trim((string) ($cells[$colMap['points']] ?? '-')),
                    'difficulty' => trim((string) ($cells[$colMap['difficulty']] ?? '-')),
                    'options_count' => 0,
                    'answer_summary' => '-',
                    'error' => implode(' ', $rowMessages),
                ];
            }

            if (count($previewRows) > self::MAX_ROWS) {
                throw ValidationException::withMessages([
                    'file' => 'Maksimal ' . self::MAX_ROWS . ' soal per sekali impor.',
                ]);
            }
        }

        return [
            'parsed' => $parsed,
            'preview_rows' => $previewRows,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<int, string>  $headerCells
     * @return array<string, int>
     */
    private function resolveColumnMap(array $headerCells): array
    {
        $normalized = array_map(
            fn (string $h) => trim(preg_replace('/\s+/', ' ', str_replace('*', '', strtolower(trim($h)))) ?? ''),
            $headerCells
        );

        $find = function (array $aliases, int $fallback) use ($normalized): int {
            foreach ($normalized as $idx => $label) {
                if (in_array($label, $aliases, true)) {
                    return $idx;
                }
            }

            return $fallback;
        };

        $hasNoColumn = in_array('no', $normalized, true);
        $offset = $hasNoColumn ? 1 : 0;

        $map = [
            'type' => $find(['tipe soal', 'tipe', 'type', 'jenis'], 0 + $offset),
            'question_text' => $find(['pertanyaan', 'question', 'soal', 'question_text'], 1 + $offset),
            'points' => $find(['poin', 'points', 'bobot', 'skor'], 2 + $offset),
            'difficulty' => $find(['kesulitan', 'difficulty', 'tingkat kesulitan'], 3 + $offset),
            'opt_a' => $find(['pilihan a', 'opsi a', 'option a', 'a'], 4 + $offset),
            'opt_b' => $find(['pilihan b', 'opsi b', 'option b', 'b'], 5 + $offset),
            'opt_c' => $find(['pilihan c', 'opsi c', 'option c', 'c'], 6 + $offset),
            'opt_d' => $find(['pilihan d', 'opsi d', 'option d', 'd'], 7 + $offset),
            'opt_e' => $find(['pilihan e', 'opsi e', 'option e', 'e'], 8 + $offset),
            'opt_f' => $find(['pilihan f', 'opsi f', 'option f', 'f'], 9 + $offset),
            'answer_key' => $find(['kunci jawaban', 'kunci', 'jawaban', 'answer'], 10 + $offset),
            'explanation' => $find(['pembahasan', 'explanation', 'penjelasan'], 11 + $offset),
        ];

        if ($hasNoColumn) {
            $map['no'] = $find(['no', 'nomor'], 0);
        }

        return $map;
    }

    /**
     * @param  array<int, string>  $cells
     * @param  array<string, int>  $map
     * @return array{
     *   type: string,
     *   question_text: string,
     *   points: float,
     *   difficulty: string,
     *   explanation: ?string,
     *   options: array<int, array{option_text: string, is_correct: bool}>
     * }
     */
    private function parseRow(array $cells, array $map, int $rowNumber): array
    {
        $get = fn (string $key): string => trim((string) ($cells[$map[$key]] ?? ''));

        $questionText = $get('question_text');
        if ($questionText === '') {
            throw ValidationException::withMessages(['file' => "Baris {$rowNumber}: Kolom Pertanyaan wajib diisi."]);
        }

        $rawType = strtolower($get('type'));
        $type = match (true) {
            in_array($rawType, ['multiple_choice', 'pg', 'pilihan ganda', 'pilihan_ganda', 'mcq', ''], true) => 'multiple_choice',
            in_array($rawType, ['true_false', 'bs', 'benar/salah', 'benar salah', 'true/false', 'tf'], true) => 'true_false',
            in_array($rawType, ['essay', 'esai', 'uraian'], true) => 'essay',
            default => throw ValidationException::withMessages([
                'file' => "Baris {$rowNumber}: Tipe '{$rawType}' tidak dikenali (gunakan PG, BS, atau Essay).",
            ]),
        };

        $rawDiff = strtolower($get('difficulty'));
        $difficulty = match (true) {
            in_array($rawDiff, ['easy', 'mudah', 'rendah'], true) => 'easy',
            in_array($rawDiff, ['medium', 'sedang', ''], true) => 'medium',
            in_array($rawDiff, ['hard', 'sulit', 'tinggi'], true) => 'hard',
            default => throw ValidationException::withMessages([
                'file' => "Baris {$rowNumber}: Kesulitan '{$rawDiff}' tidak valid (gunakan easy/mudah, medium/sedang, atau hard/sulit).",
            ]),
        };

        $rawPoints = $get('points');
        $points = $rawPoints === '' ? 10.0 : (float) str_replace(',', '.', $rawPoints);
        if (! is_numeric($rawPoints === '' ? '10' : str_replace(',', '.', $rawPoints)) || $points < 1 || $points > 500) {
            throw ValidationException::withMessages([
                'file' => "Baris {$rowNumber}: Poin harus angka antara 1 sampai 500.",
            ]);
        }

        $explanation = $get('explanation');
        $options = [];

        if ($type === 'multiple_choice') {
            $slotLetters = ['A' => 'opt_a', 'B' => 'opt_b', 'C' => 'opt_c', 'D' => 'opt_d', 'E' => 'opt_e', 'F' => 'opt_f'];
            $nonEmpty = [];
            foreach ($slotLetters as $letter => $key) {
                $val = $get($key);
                if ($val !== '') {
                    $nonEmpty[$letter] = $val;
                }
            }

            if (count($nonEmpty) < 2) {
                throw ValidationException::withMessages([
                    'file' => "Baris {$rowNumber}: Soal Pilihan Ganda minimal harus memiliki 2 opsi jawaban terisi.",
                ]);
            }

            $keyRaw = $get('answer_key');
            $keyUpper = strtoupper($keyRaw);
            $digitToLetter = ['1' => 'A', '2' => 'B', '3' => 'C', '4' => 'D', '5' => 'E', '6' => 'F'];
            $resolvedLetter = $digitToLetter[$keyUpper] ?? $keyUpper;

            if (! isset($nonEmpty[$resolvedLetter]) && $keyRaw !== '') {
                foreach ($nonEmpty as $letter => $text) {
                    if (mb_strtolower($text) === mb_strtolower($keyRaw)) {
                        $resolvedLetter = $letter;
                        break;
                    }
                }
            }

            if (! isset($nonEmpty[$resolvedLetter])) {
                throw ValidationException::withMessages([
                    'file' => "Baris {$rowNumber}: Kunci jawaban '{$keyRaw}' tidak cocok dengan opsi yang tersedia.",
                ]);
            }

            foreach ($nonEmpty as $letter => $text) {
                $options[] = [
                    'option_text' => $text,
                    'is_correct' => $letter === $resolvedLetter,
                ];
            }
        } elseif ($type === 'true_false') {
            $optA = $get('opt_a') !== '' ? $get('opt_a') : 'Benar';
            $optB = $get('opt_b') !== '' ? $get('opt_b') : 'Salah';
            $keyLower = strtolower($get('answer_key'));

            $isFirstCorrect = match (true) {
                in_array($keyLower, ['a', '1', 'benar', 'true', 'ya', 't', strtolower($optA)], true) => true,
                in_array($keyLower, ['b', '2', 'salah', 'false', 'tidak', 'f', strtolower($optB)], true) => false,
                default => throw ValidationException::withMessages([
                    'file' => "Baris {$rowNumber}: Kunci jawaban Benar/Salah harus A/Benar atau B/Salah.",
                ]),
            };

            $options = [
                ['option_text' => $optA, 'is_correct' => $isFirstCorrect],
                ['option_text' => $optB, 'is_correct' => ! $isFirstCorrect],
            ];
        }

        return [
            'type' => $type,
            'question_text' => $questionText,
            'points' => round($points, 2),
            'difficulty' => $difficulty,
            'explanation' => $explanation !== '' ? $explanation : null,
            'options' => $options,
        ];
    }
}
