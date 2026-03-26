<?php

namespace Cesa\Document\Services;

use Cesa\Document\Models\Document;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class DocumentExportService
{
    public function __construct(
        public DocxService $docx,
        public PlaceholderService $placeholders,
    ) {}

    /**
     * Detect placeholders from DOCX or HTML content.
     *
     * @return array<int, string>
     */
    public function detectPlaceholders(Document $document): array
    {
        $isDocx = (($document->source_type ?? 'html') === 'docx') && filled($document->docx_path);

        if ($isDocx) {
            if (! Storage::disk('local')->exists((string) $document->docx_path)) {
                throw new RuntimeException(__('document::filament/resources/document.messages.docx_missing'));
            }

            $absPath = Storage::disk('local')->path((string) $document->docx_path);
            $keys = $this->docx->extractPlaceholders($absPath);
            if (! empty($keys)) {
                return $keys;
            }

            // Fallback: convert to HTML then extract placeholders
            $html = $this->docx->toHtml($absPath);

            return $this->placeholders->extract($html);
        }

        return $this->placeholders->extract($document->content ?? '');
    }

    /**
     * Download an Excel template with placeholder keys as headers.
     */
    public function excelTemplate(Document $document): BinaryFileResponse
    {
        $headers = $this->detectPlaceholders($document);

        $export = new class($headers) implements FromArray, WithHeadings
        {
            public function __construct(private array $headers) {}

            public function array(): array
            {
                return [array_fill(0, count($this->headers), '')];
            }

            public function headings(): array
            {
                return $this->headers;
            }
        };

        $name = Str::slug($document->title ?: 'placeholders').'_template.xlsx';

        return Excel::download($export, $name);
    }

    /**
     * Generate and return a download response based on input data.
     * Expects keys: mode ('single'|'bulk'), values (array{key,value}[]), excel (optional path)
     */
    public function download(Document $document, array $data): Response
    {
        $isDocx = (($document->source_type ?? 'html') === 'docx') && filled($document->docx_path);
        $titleSlug = Str::slug($document->title ?? 'Document');
        $mode = $data['mode'] ?? 'single';
        $pattern = isset($data['filename']) && is_string($data['filename'])
            ? (trim($data['filename']) !== '' ? trim($data['filename']) : null)
            : null;

        if ($mode === 'bulk') {
            $excel = $data['excel'] ?? null;
            if (empty($excel)) {
                throw new RuntimeException(__('document::filament/resources/document.messages.bulk_excel_required'));
            }

            $excelPath = is_array($excel) ? collect($excel)->first() : $excel;
            if (! Storage::disk('local')->exists((string) $excelPath)) {
                throw new RuntimeException(__('document::filament/resources/document.messages.excel_missing'));
            }

            $fullPath = Storage::disk('local')->path((string) $excelPath);
            $sheet = Excel::toCollection(new class implements ToCollection
            {
                public function collection(Collection $rows): void
                {
                    // no-op; we only need the facade return value
                }
            }, $fullPath)->first();
            $excelMaps = $this->parseExcelRowsToMaps($sheet);

            if (empty($excelMaps)) {
                throw new RuntimeException(__('document::filament/resources/document.messages.excel_empty'));
            }

            if (count($excelMaps) === 1) {
                return $this->renderSingle($document, $excelMaps[0], $isDocx, $titleSlug, $pattern);
            }

            return $this->renderZip($document, $excelMaps, $isDocx, $titleSlug, $pattern);
        }

        // Single mode
        $map = collect($data['values'] ?? [])
            ->mapWithKeys(fn ($row) => [strtoupper(trim($row['key'] ?? '')) => $row['value'] ?? ''])
            ->filter(fn ($v, $k) => $k !== '')
            ->toArray();

        return $this->renderSingle($document, $map, $isDocx, $titleSlug, $pattern);
    }

    /**
     * @param  array<string,string>  $map
     */
    protected function renderSingle(Document $document, array $map, bool $isDocx, string $titleSlug, ?string $pattern = null): BinaryFileResponse
    {
        if ($isDocx) {
            if (! Storage::disk('local')->exists((string) $document->docx_path)) {
                throw new RuntimeException(__('document::filament/resources/document.messages.docx_missing'));
            }
            $absTpl = Storage::disk('local')->path((string) $document->docx_path);
            $filledDocx = $this->docx->makeFilledDocx($absTpl, $map);

            $base = $this->makeBaseFileName($document, $map, $pattern, null);

            return response()->download($filledDocx, $base.'.docx');
        }

        $content = $document->content ?? '';
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content) ?: 'UTF-8');
        }
        $html = $this->placeholders->replace($content, $map, false);
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');
        $title = $document->title ?? 'Document';
        $title = mb_convert_encoding($title, 'UTF-8', 'UTF-8');

        $template = $this->wrapWordHtml($title, $html);
        $tmp = tempnam(sys_get_temp_dir(), 'doc_').'.doc';
        file_put_contents($tmp, $template);

        $base = $this->makeBaseFileName($document, $map, $pattern, null);

        return response()->download($tmp, $base.'.doc', ['Content-Type' => 'application/msword']);
    }

    /**
     * @param  array<int, array<string,string>>  $maps
     */
    protected function renderZip(Document $document, array $maps, bool $isDocx, string $titleSlug, ?string $pattern = null): BinaryFileResponse
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'docs_zip_').'.zip';
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException(__('document::filament/resources/document.messages.zip_failed'));
        }

        $idx = 1;
        foreach ($maps as $rowMap) {
            $base = $this->makeBaseFileName($document, $rowMap, $pattern, $idx);
            if ($isDocx) {
                if (! Storage::disk('local')->exists((string) $document->docx_path)) {
                    $idx++;

                    continue;
                }
                $absTpl = Storage::disk('local')->path((string) $document->docx_path);
                $filledDocx = $this->docx->makeFilledDocx($absTpl, $rowMap);
                $zip->addFile($filledDocx, $base.'.docx');
            } else {
                $content = $document->content ?? '';
                $html = $this->placeholders->replace($content, $rowMap, false);
                $docHtml = $this->wrapWordHtml($document->title ?? 'Document', $html);
                $docPath = tempnam(sys_get_temp_dir(), 'row_doc_').'.doc';
                file_put_contents($docPath, $docHtml);
                $zip->addFile($docPath, $base.'.doc');
            }
            $idx++;
        }

        $zip->close();

        return response()->download($zipPath, $titleSlug.'-bulk.zip', ['Content-Type' => 'application/zip']);
    }

    /**
     * Build a safe base filename (without extension) using optional pattern and values.
     * Supported tokens:
     *  - {{title}} (document title)
     *  - {{index}} (1-based index in bulk)
     *  - {{$KEY}} or {{KEY}} from placeholder values (case-insensitive)
     */
    protected function makeBaseFileName(Document $document, array $map, ?string $pattern, ?int $index): string
    {
        if ($pattern === null) {
            $titleBase = Str::slug($document->title ?? 'Document') ?: 'document';
            if ($index !== null) {
                // Use the first non-empty value from the map as suffix, fallback to index
                $firstVal = '';
                foreach ($map as $v) {
                    $val = trim((string) ($v ?? ''));
                    if ($val !== '') {
                        $firstVal = $val;
                        break;
                    }
                }
                $suffix = $firstVal !== '' ? Str::slug($firstVal) : (string) $index;
                $suffix = $suffix !== '' ? $suffix : (string) $index;

                return rtrim($titleBase.'-'.$suffix, '-');
            }

            // Single mode (no index): just the title
            return $titleBase;
        }

        $base = $pattern;
        // Built-in replacements
        $builtIns = [
            'title' => (string) ($document->title ?? 'Document'),
            'index' => $index !== null ? (string) $index : '',
        ];
        foreach ($builtIns as $key => $value) {
            $base = preg_replace('/\{\{\s*'.preg_quote($key, '/').'\s*\}\}/i', (string) $value, $base) ?? $base;
        }

        // Placeholder replacements (support {{KEY}} and {{$KEY}}, case-insensitive)
        foreach ($map as $k => $v) {
            $key = (string) $k;
            $val = (string) ($v ?? '');
            $base = preg_replace('/\{\{\s*\$?'.preg_quote($key, '/').'\s*\}\}/i', $val, $base) ?? $base;
        }

        // Cleanup braces for any unresolved tokens
        $base = preg_replace('/\{\{[^}]+\}\}/', '', $base) ?? $base;

        // If user provided a static filename (no row-dynamic tokens) in bulk, auto-append 001/002 suffix
        if ($index !== null && ! $this->patternHasRowDynamics($pattern, $map)) {
            $base = rtrim($base.'-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT), '-');
        }

        // Sanitize filename: ASCII, replace spaces with '-', collapse duplicates, trim
        $base = (string) Str::of($base)
            ->replaceMatches('/[\\\\\/\:\*\?\"\<\>\|]/', '-') // reserved
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->ascii()
            ->replace(' ', '-')
            ->replaceMatches('/-+/', '-')
            ->trim('-');

        if ($base === '') {
            $base = Str::slug($document->title ?? 'Document') ?: 'document';
        }

        return $base;
    }

    /**
     * Determine if the filename pattern contains row-dynamic tokens ({{index}} or any {{KEY}}/{{$KEY}} from the map).
     */
    protected function patternHasRowDynamics(?string $pattern, array $map): bool
    {
        if ($pattern === null || $pattern === '') {
            return false;
        }
        if (preg_match('/\{\{\s*index\s*\}\}/i', $pattern)) {
            return true;
        }
        foreach ($map as $k => $_) {
            $key = (string) $k;
            if ($key === '') {
                continue;
            }
            if (preg_match('/\{\{\s*\$?'.preg_quote($key, '/').'\s*\}\}/i', $pattern)) {
                return true;
            }
        }

        return false;
    }

    protected function wrapWordHtml(string $title, string $bodyHtml): string
    {
        return str_replace(
            ['__TITLE__', '__BODY__'],
            [$title, $bodyHtml],
            <<<'HTML'
<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="ProgId" content="Word.Document" />
    <meta name="Generator" content="PHP" />
    <meta name="Originator" content="PHP" />
    <title>__TITLE__</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12pt; color: #111827; }
        table { border-collapse: collapse; }
        td, th { padding: 4px; }
    </style>
</head>
<body>
__BODY__
</body>
</html>
HTML
        );
    }

    /**
     * @return array<int, array<string,string>>
     */
    protected function parseExcelRowsToMaps(?Collection $sheet): array
    {
        if (! ($sheet instanceof Collection) || $sheet->count() <= 1) {
            return [];
        }

        $headerRow = $sheet->first();
        $headers = collect($headerRow)
            ->map(fn ($h) => strtoupper(trim((string) $h)))
            ->filter(fn ($h) => $h !== '')
            ->values()
            ->all();

        $dataRows = $sheet->slice(1)->values();
        $maps = [];
        foreach ($dataRows as $row) {
            $arr = $row->toArray();
            $rowMap = [];
            foreach ($headers as $i => $key) {
                $rowMap[$key] = (string) ($arr[$i] ?? '');
            }
            $maps[] = $rowMap;
        }

        return $maps;
    }
}
