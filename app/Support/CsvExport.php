<?php

declare(strict_types=1);

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class CsvExport
{
    /**
     * Semicolon-separated with a UTF-8 BOM so French Excel opens it correctly.
     * Cells starting with a formula character are prefixed to prevent spreadsheet injection.
     *
     * @param  list<string>  $headers
     * @param  iterable<array<array-key, mixed>>  $rows
     */
    public static function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';', escape: '');

            foreach ($rows as $row) {
                fputcsv($out, array_map(self::cell(...), $row), ';', escape: '');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private static function cell(mixed $value): string
    {
        $text = is_scalar($value) ? (string) $value : '';

        return $text !== '' && str_contains('=+-@', $text[0]) && ! is_numeric($text) ? "'{$text}" : $text;
    }
}
