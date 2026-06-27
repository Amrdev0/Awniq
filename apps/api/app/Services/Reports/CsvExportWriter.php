<?php

namespace App\Services\Reports;

class CsvExportWriter
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<array<string, mixed>>  $rows
     */
    public function write(array $headers, iterable $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn (string $header): mixed => $row[$header] ?? null, $headers));
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv === false ? '' : $csv;
    }
}
