<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use Throwable;

class ImportService
{
    // ── Template ──────────────────────────────────────────────────────────────

    public function generateTemplate(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Transactions');

        // Headers
        $headers = ['Date', 'Montant', 'Type', 'Description', 'Tags'];
        foreach ($headers as $col => $label) {
            $sheet->getCell([$col + 1, 1])->setValue($label);
        }

        // Header style
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '3730A3']]],
        ]);

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(35);
        $sheet->getColumnDimension('E')->setWidth(25);

        // Example rows
        $examples = [
            ['2026-04-01', 1800.00, 'income',  'Salaire',             'salaire travail'],
            ['2026-04-03', 45.50,   'expense', 'Courses supermarché', 'alimentation courses'],
            ['2026-04-05', 12.99,   'expense', 'Netflix',             'abonnement streaming'],
        ];

        foreach ($examples as $rowIdx => $row) {
            $excelRow = $rowIdx + 2;
            foreach ($row as $colIdx => $value) {
                $sheet->getCell([$colIdx + 1, $excelRow])->setValue($value);
            }

            // Date format for column A
            $sheet->getStyle('A'.$excelRow)->getNumberFormat()->setFormatCode('YYYY-MM-DD');

            // Alternating row background
            $bgColor = $rowIdx % 2 === 0 ? 'F8F8FF' : 'FFFFFF';
            $sheet->getStyle(sprintf('A%s:E%s', $excelRow, $excelRow))
                ->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($bgColor);
        }

        // Data validation for Type column (C): dropdown expense/income
        $validation = $sheet->getCell('C2')->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(true);
        $validation->setShowDropDown(false);
        $validation->setFormula1('"expense,income"');
        $validation->setSqref('C2:C10000');

        // Freeze header row
        $sheet->freezePane('A2');

        // Instructions sheet
        $info = $spreadsheet->createSheet();
        $info->setTitle('Instructions');
        $this->fillInstructionsSheet($info);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function fillInstructionsSheet(Worksheet $sheet): void
    {
        $lines = [
            ['Colonne', 'Format attendu', 'Exemple'],
            ['Date',        'YYYY-MM-DD ou JJ/MM/AAAA', '2026-04-01'],
            ['Montant',     'Nombre positif (décimal avec .)', '45.50'],
            ['Type',        '"expense" ou "income"', 'expense'],
            ['Description', 'Texte libre', 'Courses supermarché'],
            ['Tags',        'Mots séparés par des espaces', 'alimentation courses'],
        ];

        foreach ($lines as $rowIdx => $row) {
            foreach ($row as $colIdx => $value) {
                $sheet->getCell([$colIdx + 1, $rowIdx + 1])->setValue($value);
            }
        }

        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
        ]);

        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(25);
    }

    // ── Template columns (fixed) ──────────────────────────────────────────────

    public const TEMPLATE_HEADERS = ['Date', 'Montant', 'Type', 'Description', 'Tags'];

    private const int COL_DATE = 0;

    private const int COL_AMOUNT = 1;

    private const int COL_TYPE = 2;

    private const int COL_DESC = 3;

    private const int COL_TAGS = 4;

    // ── Preview ───────────────────────────────────────────────────────────────
    /**
     * Parse the file, validate headers, return all rows as objects, then delete the temp file.
     *
     * @return array{rows: array, total: int}
     *
     * @throws RuntimeException if headers don't match the template
     */
    public function preview(string $storedPath): array
    {
        $full = Storage::path($storedPath);
        $raw = $this->readXlsxRows($full);

        $this->validateHeaders($raw[0] ?? []);

        // Convert raw rows to named objects for the frontend
        $rows = [];
        foreach (array_slice($raw, 1) as $row) {
            $date = trim((string) ($row[self::COL_DATE] ?? ''));
            $amount = trim((string) ($row[self::COL_AMOUNT] ?? ''));
            $type = trim((string) ($row[self::COL_TYPE] ?? ''));
            $desc = trim((string) ($row[self::COL_DESC] ?? ''));
            $tags = trim((string) ($row[self::COL_TAGS] ?? ''));

            if ($date === '' && $amount === '') {
                continue; // skip fully empty rows
            }

            $rows[] = [
                'date' => $date,
                'amount' => $amount,
                'type' => in_array($type, TransactionType::values(), true) ? $type : TransactionType::Expense->value,
                'description' => $desc,
                'tags' => $tags,
            ];
        }

        // File is no longer needed — delete immediately
        Storage::delete($storedPath);

        return ['rows' => $rows, 'total' => count($rows)];
    }

    private function validateHeaders(array $headers): void
    {
        $normalized = array_map(trim(...), $headers);

        if ($normalized !== self::TEMPLATE_HEADERS) {
            throw new RuntimeException(
                'Format de fichier invalide. Utilisez le modèle fourni (colonnes attendues : '.
                implode(', ', self::TEMPLATE_HEADERS).').'
            );
        }
    }

    // ── Process ───────────────────────────────────────────────────────────────

    /**
     * @param  array{rows: array, wallet_id: int}  $data
     * @return array{created: int, errors: int, month: string}
     */
    public function process(array $data, User $user, Wallet $wallet): array
    {
        $created = 0;
        $errors = 0;
        $months = [];

        foreach ($data['rows'] as $row) {
            try {
                $date = $this->parseDate((string) ($row['date'] ?? ''));
                if (! $date) {
                    $errors++;

                    continue;
                }

                $amount = $this->parseAmount((string) ($row['amount'] ?? ''));
                if ($amount === 0.0) {
                    $errors++;

                    continue;
                }

                $type = in_array($row['type'] ?? '', TransactionType::values()) ? $row['type'] : TransactionType::Expense->value;
                $rawDesc = trim((string) ($row['description'] ?? ''));
                $rawTags = trim((string) ($row['tags'] ?? ''));
                $tags = $rawTags !== ''
                    ? array_values(array_filter(array_map(trim(...), explode(' ', $rawTags))))
                    : null;

                Transaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'category_id' => $row['category_id'],
                    'type' => $type,
                    'amount' => abs($amount),
                    'description' => $rawDesc !== '' ? $rawDesc : null,
                    'date' => $date,
                    'tags' => $tags,
                ]);

                $months[] = substr($date, 0, 7); // YYYY-MM
                $created++;
            } catch (Throwable) {
                $errors++;
            }
        }

        // Most frequent month among imported transactions
        $month = $months !== []
            ? array_search(max(array_count_values($months)), array_count_values($months), true)
            : now()->format('Y-m');

        return ['created' => $created, 'errors' => $errors, 'month' => $month];
    }

    // ── XLSX helpers ──────────────────────────────────────────────────────────

    private function readXlsxRows(string $full): array
    {
        $spreadsheet = IOFactory::load($full);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];

        foreach ($sheet->getRowIterator() as $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $val = $cell->getFormattedValue();
                // For numeric date cells, PhpSpreadsheet formats them — keep as-is
                $cells[] = trim($val);
            }

            // Skip fully empty rows
            if (array_filter($cells, fn ($c) => $c !== '') === []) {
                continue;
            }

            $rows[] = $cells;
        }

        return $rows;
    }

    // ── Parsers ───────────────────────────────────────────────────────────────

    private function parseDate(string $raw): ?string
    {
        $ts = strtotime($raw);

        if ($ts === false) {
            return null;
        }

        $date = date('Y-m-d', $ts);

        return $date !== '1970-01-01' ? $date : null;
    }

    private function parseAmount(string $raw): float
    {
        $clean = preg_replace('/[^\d.,\-]/', '', $raw);
        $clean = str_replace(',', '.', $clean);

        $parts = explode('.', $clean);
        if (count($parts) > 2) {
            $clean = implode('', array_slice($parts, 0, -1)).'.'.end($parts);
        }

        return (float) $clean;
    }
}
