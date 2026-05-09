<?php

namespace Services;

use Ulid\Ulid;

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
        
        // Create simple text seal if PNG not available
        if (!file_exists($this->sealPath)) {
            $this->createTextSeal();
        }
    }

    private function createTextSeal()
    {
        $sealContent = "╔════════════════╗\n";
        $sealContent .= "║   COMPANY      ║\n";
        $sealContent .= "║     SEAL       ║\n";
        $sealContent .= "║  AUTHORIZED    ║\n";
        $sealContent .= "╚════════════════╝\n";
        file_put_contents($this->sealPath, $sealContent);
    }

    /**
     * Apply company seal and approval stamp to contract
     * 
     * @param int $contractId
     * @param string $approverName
     * @param string $inputFilePath (optional)
     * @return array
     */
    public function applySeal($contractId, $approverName, $inputFilePath = null)
    {
        // Get contract file path
        if (!$inputFilePath) {
            $inputFilePath = $this->storageDir . $contractId . '/contract.docx';
        }

        if (!file_exists($inputFilePath)) {
            return [
                'success' => false, 
                'error' => 'Contract file not found at: ' . $inputFilePath
            ];
        }

        // Generate ULID approval code
        $ulid = new Ulid();
        $approvalCode = 'ULID_' . $ulid->generate();

        // Create output directory
        $outputDir = $this->storageDir . $contractId . '/';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        // Output path for sealed PDF
        $timestamp = time();
        $outputPath = $outputDir . "sealed_{$timestamp}.pdf";

        // Convert DOCX to PDF first using LibreOffice
        $pdfPath = $this->convertToPdf($inputFilePath);
        
        if (!$pdfPath) {
            return [
                'success' => false, 
                'error' => 'Failed to convert document to PDF. Is LibreOffice installed?'
            ];
        }

        // Apply seal and stamp to PDF
        $result = $this->applyStampAndSeal($pdfPath, $outputPath, $approverName, $approvalCode);

        if (!$result) {
            return [
                'success' => false, 
                'error' => 'Failed to apply seal to PDF'
            ];
        }

        return [
            'success' => true,
            'sealed_file' => $outputPath,
            'approval_code' => $approvalCode,
            'original_file' => $inputFilePath,
            'message' => 'Seal and stamp applied successfully'
        ];
    }

    /**
     * Convert DOCX to PDF using LibreOffice headless
     */
    private function convertToPdf($inputPath)
    {
        $extension = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));
        
        // If already PDF, return as is
        if ($extension === 'pdf') {
            return $inputPath;
        }

        // Convert DOCX to PDF
        $outputDir = dirname($inputPath);
        $outputPath = $outputDir . '/temp_' . time() . '.pdf';
        
        $command = sprintf(
            'libreoffice --headless --convert-to pdf --outdir %s %s 2>&1',
            escapeshellarg($outputDir),
            escapeshellarg($inputPath)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            // Find the converted file
            $convertedFile = $outputDir . '/' . pathinfo($inputPath, PATHINFO_FILENAME) . '.pdf';
            if (file_exists($convertedFile)) {
                rename($convertedFile, $outputPath);
                return $outputPath;
            }
        }
        
        return false;
    }

    /**
     * Apply seal and stamp to PDF using TCPDF
     */
    private function applyStampAndSeal($inputPdf, $outputPdf, $approverName, $approvalCode)
    {
        try {
            // Create new TCPDF instance
            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Set document info
            $pdf->SetCreator('ITEC Contract System');
            $pdf->SetAuthor('OSCAR Signature Engine');
            $pdf->SetTitle('Sealed Contract');
            
            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Import existing PDF pages
            $pageCount = $pdf->setSourceFile($inputPdf);
            
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                // Add a new page
                $pdf->AddPage();
                
                // Import the original page
                $templateId = $pdf->importPage($pageNo);
                $pdf->useTemplate($templateId);
                
                // Add company seal (bottom-right corner)
                $this->addSealToPage($pdf);
                
                // Add approval stamp (bottom-left corner)
                $this->addStampToPage($pdf, $approverName, $approvalCode);
            }
            
            // Output flattened PDF (permanent, no layers)
            $pdf->Output($outputPdf, 'F');
            
            return file_exists($outputPdf);
            
        } catch (\Exception $e) {
            error_log("TCPDF Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add company seal to PDF page
     */
    private function addSealToPage($pdf)
    {
        $pageWidth = $pdf->getPageWidth();
        $pageHeight = $pdf->getPageHeight();
        
        // Position: bottom-right corner
        $x = $pageWidth - 55;
        $y = $pageHeight - 55;
        
        if (file_exists($this->sealPath) && mime_content_type($this->sealPath) === 'image/png') {
            // Add image seal
            $pdf->Image($this->sealPath, $x, $y, 45, 45, 'PNG');
        } else {
            // Add text seal as fallback
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetDrawColor(0, 0, 0);
            
            // Draw rectangle border
            $pdf->Rect($x, $y, 45, 45, 'DF');
            
            // Add text
            $pdf->SetXY($x + 5, $y + 15);
            $pdf->Cell(35, 6, 'COMPANY', 0, 1, 'C');
            $pdf->SetXY($x + 5, $y + 23);
            $pdf->Cell(35, 6, 'SEAL', 0, 1, 'C');
            $pdf->SetXY($x + 5, $y + 31);
            $pdf->Cell(35, 4, 'APPROVED', 0, 1, 'C');
        }
    }

    /**
     * Add approval stamp to PDF page
     */
    private function addStampToPage($pdf, $approverName, $approvalCode)
    {
        $pageHeight = $pdf->getPageHeight();
        
        // Position: bottom-left corner
        $x = 15;
        $y = $pageHeight - 50;
        
        // Draw stamp background
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetDrawColor(0, 0, 0);
        
        // Rectangle background
        $pdf->Rect($x, $y, 80, 40, 'DF');
        
        // Add stamp text
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY($x + 5, $y + 5);
        $pdf->Cell(70, 6, 'APPROVED FOR EXECUTION', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY($x + 5, $y + 13);
        $pdf->Cell(70, 4, 'By: ' . $approverName, 0, 1, 'L');
        
        $pdf->SetXY($x + 5, $y + 19);
        $pdf->Cell(70, 4, 'Code: ' . $approvalCode, 0, 1, 'L');
        
        $pdf->SetXY($x + 5, $y + 25);
        $pdf->Cell(70, 4, 'Date: ' . date('Y-m-d H:i:s'), 0, 1, 'L');
    }
}