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
    private $sealOutputDir;

    public function __construct()
    {
        $this->sealPath = __DIR__ . '/../storage/seals/company_seal.png';
        $this->storageDir = __DIR__ . '/../storage/contracts/';
        $this->sealOutputDir = __DIR__ . '/../storage/contracts/seal/';

        $this->ensureDirectoriesExist();
        $this->ensureSealExists();
    }

    private function ensureDirectoriesExist()
    {
        $dirs = [
            dirname($this->sealPath),
            $this->sealOutputDir
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }
    }

    private function ensureSealExists()
    {
        if (!file_exists($this->sealPath)) {
            $this->createSealImage();
        }
    }

    private function createSealImage()
    {
        if (!function_exists('imagecreatetruecolor')) {
            return;
        }

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
                $inputFilePath = $this->getContractFilePath($contractId);
            }

            if (!file_exists($inputFilePath)) {
                return [
                    'success' => false,
                    'error' => 'Contract file not found: ' . $inputFilePath
                ];
            }

            $approvalCode = 'ULID_' . Ulid::generate();

            $timestamp = time();
            $outputPath = $this->sealOutputDir . 'sealed_' . $contractId . '_' . $timestamp . '.pdf';

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

    private function getContractFilePath($contractId)
    {
        try {
            $db = \Core\Database::getInstance()->getConnection();
            
            // First try to get uploaded hard copy from audit log
            $stmt = $db->prepare("
                SELECT uploaded_file_path 
                FROM doc_signature_audit 
                WHERE contract_id = ? AND event_type = 'hard_copy_uploaded' 
                ORDER BY timestamp DESC 
                LIMIT 1
            ");
            $stmt->execute([(int) $contractId]);
            $filePath = $stmt->fetchColumn();

            if ($filePath) {
                $resolved = $this->resolvePath($filePath);
                if (file_exists($resolved)) {
                    return $resolved;
                }
            }
            
            // If no hard copy, try to get the digitally signed contract file
            $stmt = $db->prepare("
                SELECT dsa.uploaded_file_path 
                FROM doc_signature_audit dsa
                WHERE dsa.contract_id = ? AND dsa.event_type = 'signature_created'
                ORDER BY dsa.timestamp DESC 
                LIMIT 1
            ");
            $stmt->execute([(int) $contractId]);
            $filePath = $stmt->fetchColumn();
            
            if ($filePath) {
                $resolved = $this->resolvePath($filePath);
                if (file_exists($resolved)) {
                    return $resolved;
                }
            }
            
            // Fallback: Get the contract file path from contracts table
            $stmt = $db->prepare("
                SELECT file_path 
                FROM contracts 
                WHERE id = ?
            ");
            $stmt->execute([(int) $contractId]);
            $filePath = $stmt->fetchColumn();
            
            if ($filePath) {
                $resolved = $this->resolvePath($filePath);
                if (file_exists($resolved)) {
                    return $resolved;
                }
            }
            
        } catch (\Throwable $error) {
            error_log('Seal file lookup failed: ' . $error->getMessage());
        }

        // Final fallback - default path
        return $this->storageDir . 'contract_' . $contractId . '.docx';
    }

    private function resolvePath($path)
    {
        $path = str_replace('\\', '/', $path);
        
        if (strpos($path, 'storage/') === 0) {
            $path = __DIR__ . '/../' . $path;
        }
        
        if (strpos($path, 'C:') === 0 || strpos($path, '/') === 0) {
            return $path;
        }
        
        return __DIR__ . '/../' . $path;
    }

    private function convertToPdf($inputPath)
    {
        try {
            $extension = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));

            if ($extension === 'pdf') {
                return $inputPath;
            }

            if (!file_exists($inputPath)) {
                throw new Exception('File not found.');
            }

            if (filesize($inputPath) <= 0) {
                throw new Exception('File is empty.');
            }

            if (!class_exists('\ZipArchive')) {
                return $this->createFallbackPdf($inputPath);
            }

            $zip = new ZipArchive();
            $openResult = $zip->open($inputPath);

            if ($openResult !== true) {
                throw new Exception('Invalid DOCX. Zip open failed with code: ' . $openResult);
            }

            if ($zip->locateName('_rels/.rels') === false) {
                $zip->close();
                throw new Exception('Invalid DOCX structure.');
            }

            $zip->close();

            Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
            Settings::setPdfRendererPath(realpath(__DIR__ . '/../vendor/dompdf/dompdf'));

            $phpWord = IOFactory::load($inputPath);

            $tempPdfPath = dirname($inputPath) . DIRECTORY_SEPARATOR . 'tmp_' . uniqid() . '.pdf';
            $writer = IOFactory::createWriter($phpWord, 'PDF');
            $writer->save($tempPdfPath);

            if (!file_exists($tempPdfPath)) {
                throw new Exception('PDF conversion failed.');
            }

            return $tempPdfPath;

        } catch (\Throwable $e) {
            error_log('Conversion Error: ' . $e->getMessage());
            return $this->createFallbackPdf($inputPath);
        }
    }

    private function createFallbackPdf($inputPath)
    {
        try {
            $tempPdfPath = dirname($inputPath) . DIRECTORY_SEPARATOR . 'tmp_' . uniqid() . '.pdf';
            $pdf = new Fpdi();

            $pdf->SetCreator('ITEC Contract System');
            $pdf->SetAuthor('OSCAR Signature Engine');
            $pdf->SetTitle('Contract Execution Copy');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'Contract Execution Copy', 0, 1);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(0, 7, 'The original contract document is stored at: ' . $inputPath);
            $pdf->Ln(4);
            $pdf->MultiCell(0, 7, 'Document hash: ' . hash_file('sha256', $inputPath));
            $pdf->Output($tempPdfPath, 'F');

            return file_exists($tempPdfPath) ? $tempPdfPath : false;
        } catch (\Throwable $error) {
            error_log('Fallback PDF Error: ' . $error->getMessage());
            return false;
        }
    }

    private function applyStampAndSeal($inputPdf, $outputPdf, $approverName, $approvalCode)
    {
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
                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';

                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                $this->addSealToPage($pdf);
                $this->addStampToPage($pdf, $approverName, $approvalCode);
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

        if (file_exists($this->sealPath) && mime_content_type($this->sealPath) === 'image/png') {
            $pdf->Image($this->sealPath, $x, $y, 40, 40, 'PNG');
        } else {
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetDrawColor(255, 0, 0);
            $pdf->Ellipse($x + 20, $y + 20, 18, 18);
            $pdf->Text($x + 8, $y + 18, 'SEAL');
        }
    }

    private function addStampToPage($pdf, $approverName, $approvalCode)
    {
        $pageHeight = $pdf->getPageHeight();

        $x = 15;
        $y = $pageHeight - 45;

        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Rect($x, $y, 85, 35, 'DF');

        
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetX($x + 4);
        $pdf->Cell(70, 4, 'By: ' . $approverName, 0, 1);
        $pdf->SetX($x + 4);
        $pdf->Cell(70, 4, 'Code: ' . $approvalCode, 0, 1);
        $pdf->SetX($x + 4);
        $pdf->Cell(70, 4, 'Date: ' . date('Y-m-d H:i:s'), 0, 1);
    }
}