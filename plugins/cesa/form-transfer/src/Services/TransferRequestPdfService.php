<?php

namespace Cesa\FormTransfer\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Cesa\FormTransfer\Models\TransferRequest;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class TransferRequestPdfService
{
    public function download(TransferRequest $record): StreamedResponse
    {
        $pdfOutput = $this->renderPdf($record);

        return response()->streamDownload(function () use ($pdfOutput): void {
            echo $pdfOutput;
        }, $this->getPdfFileName($record));
    }

    public function downloadBulkArchive(Collection $records): BinaryFileResponse
    {
        if ($records->isEmpty()) {
            throw new RuntimeException('No transfer requests were selected for PDF download.');
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'transfer_request_pdf_');

        if ($zipPath === false) {
            throw new RuntimeException('Unable to create a temporary archive for transfer request PDFs.');
        }

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create the transfer request PDF archive.');
        }

        $usedEntryNames = [];

        foreach ($records as $record) {
            if (! $record instanceof TransferRequest) {
                continue;
            }

            $entryName = $this->getUniqueZipEntryName($this->getPdfFileName($record), $usedEntryNames);

            $zip->addFromString($entryName, $this->renderPdf($record));
        }

        $zip->close();

        return response()
            ->download($zipPath, $this->getBulkArchiveFileName(), ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    public function getPdfFileName(TransferRequest $record): string
    {
        return $this->getFilenamePrefix().'-'.($record->uid ?: $record->id).'.pdf';
    }

    public function getBulkArchiveFileName(): string
    {
        return $this->getFilenamePrefix().'-bulk.zip';
    }

    protected function renderPdf(TransferRequest $record): string
    {
        $record->loadMissing(['bank', 'division', 'company', 'approvalWorkflow', 'formTransfer']);

        return Pdf::loadView('form-transfer::pdf.transfer-request', [
            'record' => $record,
        ])->setPaper('a4', 'portrait')->output();
    }

    /**
     * @param  array<string, int>  $usedEntryNames
     */
    protected function getUniqueZipEntryName(string $entryName, array &$usedEntryNames): string
    {
        if (! array_key_exists($entryName, $usedEntryNames)) {
            $usedEntryNames[$entryName] = 1;

            return $entryName;
        }

        $usedEntryNames[$entryName]++;

        $extension = pathinfo($entryName, PATHINFO_EXTENSION);
        $baseName = pathinfo($entryName, PATHINFO_FILENAME);
        $suffix = '-'.$usedEntryNames[$entryName];

        if ($extension === '') {
            return $baseName.$suffix;
        }

        return $baseName.$suffix.'.'.$extension;
    }

    protected function getFilenamePrefix(): string
    {
        return (string) __('form-transfer::filament/resources/transfer-request/view.transfer_request.actions.download_pdf_filename_prefix');
    }
}
