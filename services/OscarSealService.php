<?php

namespace Services;

require_once __DIR__ . '/../vendor/autoload.php';

use Ulid\Ulid;
use ZipArchive;
use Exception;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\PhpWord;
use setasign\Fpdi\Tcpdf\Fpdi;

class OscarSealService
{
    private $sealPath;
    private $storageDir;

    public function __construct()
    {
        $this->sealPath = __DIR__ . '/../storage/seals/company_seal.png';
        $this->storageDir = __DIR__ . '/../storage/contracts/';

        $this->ensureSealExists();
    }

    private function ensureSealExists()
    {
        $sealDir = dirname($this->sealPath);

        if (!is_dir($sealDir)) {
            mkdir($sealDir, 0777, true);
        }

        if (!file_exists($this->sealPath)) {
            $this->createSealImage();
        }
    }

    private function createSealImage()
    {
        $image = imagecreatetruecolor(300, 300);

        $white = imagecolorallocate($image, 255, 255, 255);
        $red = imagecolorallocate($image, 200, 0, 0);
        $black = imagecolorallocate($image, 0, 0, 0);

        imagefill($image, 0, 0, $white);

        imageellipse($image, 150, 150, 240, 240, $red);
        imageellipse($image, 150, 150, 230, 230, $red);

        imagestring($image, 5, 90, 110, 'COMPANY', $black);
        imagestring($image, 5, 115, 140, 'SEAL', $black);
        imagestring($image, 3, 95, 180, 'AUTHORIZED', $black);

        imagepng($image, $this->sealPath);

        imagedestroy($image);
    }

    public function applySeal($contractId, $approverName, $inputFilePath = null)
    {
        try {

            if (!$inputFilePath) {
                $inputFilePath = $this->storageDir . $contractId . '/contract.docx';
            }

            if (!file_exists($inputFilePath)) {
                return [
                    'success' => false,
                    'error' => 'Contract file not found: ' . $inputFilePath
                ];
            }

            $approvalCode = 'ULID_' . Ulid::generate();

            $outputDir = $this->storageDir . $contractId . '/';

            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            $timestamp = time();

            $outputPath = $outputDir . 'sealed_' . $timestamp . '.pdf';

            $pdfPath = $this->convertToPdf($inputFilePath);

            if (!$pdfPath) {
                return [
                    'success' => false,
                    'error' => 'DOCX to PDF conversion failed.'
                ];
            }

            $sealed = $this->applyStampAndSeal(
                $pdfPath,
                $outputPath,
                $approverName,
                $approvalCode
            );

            if ($pdfPath !== $inputFilePath && file_exists($pdfPath)) {
                unlink($pdfPath);
            }

            if (!$sealed) {
                return [
                    'success' => false,
                    'error' => 'Failed to apply seal.'
                ];
            }

            return [
                'success' => true,
                'sealed_file' => $outputPath,
                'approval_code' => $approvalCode,
                'message' => 'Seal applied successfully.'
            ];

        } catch (\Throwable $e) {

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function convertToPdf($inputPath)
    {
        try {

            $extension = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));

            if ($extension === 'pdf') {
                return $inputPath;
            }

            if (!class_exists('\ZipArchive')) {
                throw new Exception('Zip extension is not enabled.');
            }

            if (!file_exists($inputPath)) {
                throw new Exception('File not found.');
            }

            if (filesize($inputPath) <= 0) {
                throw new Exception('File is empty.');
            }

            $zip = new ZipArchive();

            $openResult = $zip->open($inputPath);

            if ($openResult !== true) {
                throw new Exception(
                    'Invalid DOCX. Zip open failed with code: ' . $openResult
                );
            }

            if ($zip->locateName('_rels/.rels') === false) {
                $zip->close();
                throw new Exception('Invalid DOCX structure.');
            }

            $zip->close();

            Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);

            Settings::setPdfRendererPath(
                realpath(__DIR__ . '/../vendor/dompdf/dompdf')
            );

            $phpWord = IOFactory::load($inputPath);

            $tempPdfPath =
                dirname($inputPath) .
                DIRECTORY_SEPARATOR .
                'tmp_' .
                uniqid() .
                '.pdf';

            $writer = IOFactory::createWriter($phpWord, 'PDF');

            $writer->save($tempPdfPath);

            if (!file_exists($tempPdfPath)) {
                throw new Exception('PDF conversion failed.');
            }

            return $tempPdfPath;

        } catch (\Throwable $e) {

            error_log('Conversion Error: ' . $e->getMessage());

            return false;
        }
    }

    private function applyStampAndSeal(
        $inputPdf,
        $outputPdf,
        $approverName,
        $approvalCode
    ) {
        try {

            $pdf = new Fpdi();

            $pdf->SetCreator('ITEC Contract System');
            $pdf->SetAuthor('OSCAR Signature Engine');
            $pdf->SetTitle('Sealed Contract');

            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            $pageCount = $pdf->setSourceFile($inputPdf);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {

                $templateId = $pdf->importPage($pageNo);

                $size = $pdf->getTemplateSize($templateId);

                $orientation =
                    $size['width'] > $size['height'] ? 'L' : 'P';

                $pdf->AddPage(
                    $orientation,
                    [$size['width'], $size['height']]
                );

                $pdf->useTemplate($templateId);

                $this->addSealToPage($pdf);

                $this->addStampToPage(
                    $pdf,
                    $approverName,
                    $approvalCode
                );
            }

            $pdf->Output($outputPdf, 'F');

            return file_exists($outputPdf);

        } catch (\Throwable $e) {

            error_log('FPDI Error: ' . $e->getMessage());

            return false;
        }
    }

    private function addSealToPage($pdf)
    {
        $pageWidth = $pdf->getPageWidth();
        $pageHeight = $pdf->getPageHeight();

        $x = $pageWidth - 60;
        $y = $pageHeight - 60;

        if (
            file_exists($this->sealPath) &&
            mime_content_type($this->sealPath) === 'image/png'
        ) {

            $pdf->Image(
                $this->sealPath,
                $x,
                $y,
                40,
                40,
                'PNG'
            );

        } else {

            $pdf->SetFont('helvetica', 'B', 8);

            $pdf->SetDrawColor(255, 0, 0);

            $pdf->Ellipse($x + 20, $y + 20, 18, 18);

            $pdf->Text($x + 8, $y + 18, 'SEAL');
        }
    }

    private function addStampToPage(
        $pdf,
        $approverName,
        $approvalCode
    ) {
        $pageHeight = $pdf->getPageHeight();

        $x = 15;
        $y = $pageHeight - 45;

        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetDrawColor(0, 0, 0);

        $pdf->Rect($x, $y, 85, 35, 'DF');

        $pdf->SetFont('helvetica', 'B', 9);

        $pdf->SetXY($x + 4, $y + 4);

        $pdf->Cell(
            70,
            5,
            'APPROVED FOR EXECUTION',
            0,
            1
        );

        $pdf->SetFont('helvetica', '', 7);

        $pdf->SetX($x + 4);
        $pdf->Cell(
            70,
            4,
            'By: ' . $approverName,
            0,
            1
        );

        $pdf->SetX($x + 4);
        $pdf->Cell(
            70,
            4,
            'Code: ' . $approvalCode,
            0,
            1
        );

        $pdf->SetX($x + 4);
        $pdf->Cell(
            70,
            4,
            'Date: ' . date('Y-m-d H:i:s'),
            0,
            1
        );
    }
}