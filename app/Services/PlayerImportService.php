<?php

namespace App\Services;

use App\Models\League;
use App\Models\Player;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PlayerImportService
{
    /**
     * Parse a CSV without inserting. Returns:
     * [
     *   'rows'  => [ ['line'=>2, 'data'=>['full_name'=>..,], 'errors'=>['email'=>'Invalid']], ... ],
     *   'valid' => 18,
     *   'invalid' => 2,
     * ]
     */
    public function preview(UploadedFile $file): array
    {
        $rows = $this->readCsv($file);
        $valid = 0;
        $invalid = 0;

        foreach ($rows as &$row) {
            $row['errors'] = $this->validateRow($row['data']);
            if (empty($row['errors'])) $valid++;
            else $invalid++;
        }

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

                $league->players()->create([
                    'full_name'      => $row['data']['full_name'],
                    'email'          => $row['data']['email'] ?: null,
                    'phone'          => $row['data']['phone'] ?: null,
                    'paid_amount'    => (float) ($row['data']['paid_amount'] ?? 0),
                    'payment_status' => $this->derivePaymentStatus(
                        (float) ($row['data']['paid_amount'] ?? 0),
                        (float) $league->cost
                    ),
                ]);
                $imported++;
            }
        });

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    private function readCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) return [];

        $headers = null;
        $rows = [];
        $lineNo = 0;

        while (($cols = fgetcsv($handle, 0, ',')) !== false) {
            $lineNo++;
            if ($lineNo === 1) {
                $headers = array_map(fn($h) => strtolower(trim($h)), $cols);
                continue;
            }
            if (count(array_filter($cols, fn($v) => $v !== null && $v !== '')) === 0) {
                continue; // skip empty rows
            }
            $data = [];
            foreach ($headers as $i => $key) {
                $data[$key] = isset($cols[$i]) ? trim($cols[$i]) : '';
            }
            $rows[] = ['line' => $lineNo, 'data' => $this->normalizeRow($data)];
        }
        fclose($handle);
        return $rows;
    }

    private function normalizeRow(array $r): array
    {
        // Tolerate Spanish headers too.
        $map = [
            'nombre'    => 'full_name',
            'name'      => 'full_name',
            'full_name' => 'full_name',
            'correo'    => 'email',
            'email'     => 'email',
            'telefono'  => 'phone',
            'teléfono'  => 'phone',
            'phone'     => 'phone',
            'pagado'    => 'paid_amount',
            'pago'      => 'paid_amount',
            'amount'    => 'paid_amount',
            'paid'      => 'paid_amount',
            'paid_amount' => 'paid_amount',
        ];
        $out = [
            'full_name'   => '',
            'email'       => '',
            'phone'       => '',
            'paid_amount' => 0,
        ];
        foreach ($r as $k => $v) {
            $mapped = $map[$k] ?? null;
            if ($mapped) $out[$mapped] = $v;
        }
        return $out;
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
        if ($paid <= 0)            return Player::STATUS_UNPAID;
        if ($cost > 0 && $paid >= $cost) return Player::STATUS_PAID;
        return Player::STATUS_PARTIAL;
    }
}
