<?php

namespace App\Services;

use App\Models\League;
use App\Models\Player;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PlayerImportService
{
    /**
     * Parse a CSV/XLSX/XLS without inserting. Returns:
     * [
     *   'rows'  => [ ['line'=>2, 'data'=>['full_name'=>..,], 'errors'=>['email'=>'Invalid']], ... ],
     *   'valid' => 18,
     *   'invalid' => 2,
     * ]
     */
    public function preview(UploadedFile $file): array
    {
        $rows = $this->extractRows($file);

        $valid = 0;
        $invalid = 0;

        foreach ($rows as &$row) {
            $row['errors'] = $this->validateRow($row['data']);
            if (empty($row['errors'])) {
                $valid++;
            } else {
                $invalid++;
            }
        }
        unset($row);

        return ['rows' => $rows, 'valid' => $valid, 'invalid' => $invalid];
    }

    public function import(League $league, UploadedFile $file): array
    {
        $preview = $this->preview($file);
        $imported = 0;
        $skipped  = 0;

        DB::transaction(function () use ($league, $preview, &$imported, &$skipped) {
            foreach ($preview['rows'] as $row) {
                if (!empty($row['errors'])) {
                    $skipped++;
                    continue;
                }

                $paid = (float) ($row['data']['paid_amount'] ?? 0);

                $league->players()->create([
                    'full_name'      => $row['data']['full_name'],
                    'email'          => $row['data']['email'] ?: null,
                    'phone'          => $row['data']['phone'] ?: null,
                    'paid_amount'    => $paid,
                    'payment_status' => $this->derivePaymentStatus($paid, (float) $league->cost),
                ]);
                $imported++;
            }
        });

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    private function validateRow(array $data): array
    {
        $errors = [];

        if (empty($data['full_name'])) {
            $errors['full_name'] = 'Nombre requerido';
        } elseif (mb_strlen($data['full_name']) > 120) {
            $errors['full_name'] = 'Máx. 120 caracteres';
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido';
        }

        if (!empty($data['phone']) && mb_strlen($data['phone']) > 40) {
            $errors['phone'] = 'Teléfono demasiado largo';
        }

        if (!empty($data['paid_amount']) && !is_numeric($data['paid_amount'])) {
            $errors['paid_amount'] = 'Monto debe ser numérico';
        }

        return $errors;
    }

    public function derivePaymentStatus(float $paid, float $cost): string
    {
        if ($paid <= 0) {
            return Player::STATUS_UNPAID;
        }
        if ($cost > 0 && $paid >= $cost) {
            return Player::STATUS_PAID;
        }
        return Player::STATUS_PARTIAL;
    }

    // ============================================================
    // File parsing
    // ============================================================

    /**
     * Read an uploaded file (CSV, XLSX, or XLS) into rows shaped for the
     * validation pipeline: [ ['line' => N, 'data' => [...]], ... ].
     */
    private function extractRows(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        $matrix = in_array($ext, ['xlsx', 'xls'], true)
            ? $this->readSpreadsheet($file)
            : $this->readCsv($file);

        if (empty($matrix)) {
            return [];
        }

        // First row is the header. Track how many physical rows we consumed
        // so the reported line numbers match the source file.
        $header = array_map(fn($h) => $this->normalizeHeader((string) $h), array_shift($matrix));

        $rows = [];
        $lineNumber = 1; // header was line 1; data starts at line 2

        foreach ($matrix as $cells) {
            $lineNumber++;

            // Skip fully-empty rows (don't advance "line" expectations for the user;
            // an empty row in the middle is just ignored)
            if (collect($cells)->filter(fn($c) => trim((string) $c) !== '')->isEmpty()) {
                continue;
            }

            $assoc = [];
            foreach ($header as $i => $key) {
                if ($key === null) {
                    continue;
                }
                $assoc[$key] = isset($cells[$i]) ? trim((string) $cells[$i]) : null;
            }

            $rows[] = [
                'line' => $lineNumber,
                'data' => [
                    'full_name'   => $assoc['full_name']   ?? '',
                    'email'       => $assoc['email']       ?? '',
                    'phone'       => $assoc['phone']       ?? '',
                    'paid_amount' => $assoc['paid_amount'] ?? '',
                ],
            ];
        }

        return $rows;
    }

    /** Read an xlsx/xls into a 2D array of raw cell values (first sheet only). */
    private function readSpreadsheet(UploadedFile $file): array
    {
        $reader = IOFactory::createReaderForFile($file->getRealPath());
        $reader->setReadDataOnly(true); // ignore styles/formulas formatting, faster
        $spreadsheet = $reader->load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();

        $matrix = $sheet->toArray(
            nullValue: null,
            calculateFormulas: true,
            formatData: true,
            returnCellRef: false
        );

        // Free memory promptly — spreadsheets can be heavy
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $matrix;
    }

    /** Read a CSV into a 2D array of raw cell values. */
    private function readCsv(UploadedFile $file): array
    {
        $matrix = [];
        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            while (($cells = fgetcsv($handle, 0, ',')) !== false) {
                $matrix[] = $cells;
            }
            fclose($handle);
        }
        return $matrix;
    }

    /**
     * Map a header cell to a canonical key. Accepts Spanish and English aliases,
     * case-insensitive, accent-insensitive. Returns null for unrecognized columns.
     */
    private function normalizeHeader(string $raw): ?string
    {
        $clean = Str::of($raw)
            ->lower()
            ->ascii()   // strip accents: "teléfono" -> "telefono"
            ->trim()
            ->replace(['_', '-'], ' ')
            ->squish()  // collapse multiple spaces
            ->value();

        return match ($clean) {
            'full name', 'nombre', 'nombre completo', 'name', 'jugador' => 'full_name',
            'email', 'correo', 'correo electronico', 'e mail'           => 'email',
            'phone', 'telefono', 'celular', 'tel', 'whatsapp'           => 'phone',
            'paid amount', 'pagado', 'monto', 'pago', 'abonado'         => 'paid_amount',
            default => null,
        };
    }
}
