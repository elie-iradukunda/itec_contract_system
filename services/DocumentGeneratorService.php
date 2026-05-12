<?php

namespace Services;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use ZipArchive;

class DocumentGeneratorService
{
    private $storageDir;
    private $companyInfo;
    private $primaryColor = '80181A';

    public function __construct()
    {
        // Fix path - use realpath to resolve correctly
        $this->storageDir = realpath(__DIR__ . '/../storage/contracts/');
        
        // If realpath fails (directory doesn't exist), construct manually
        if (!$this->storageDir) {
            $this->storageDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'contracts' . DIRECTORY_SEPARATOR;
        }
        
        $this->ensureDirectoryExists();

        $this->companyInfo = [
            'name' => 'ITEC Solutions',
            'address' => 'Head office in Rwanda - Nyarugenge District, Kigali, Rwanda',
            'tin' => '105253130',
            'phone' => '0788276076',
            'tagline' => 'BE SMART, CHOOSE SMART',
            'services' => 'Web hosting & design, software development, web & mobile application development, Graphic design, IT Consultancy, IT supplying & support, office support',
        ];
    }

    private function ensureDirectoryExists()
    {
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0777, true);
        }
    }

    public function generateContract($contractId, $data)
    {
        $fileName = "contract_{$contractId}.docx";
        $filePath = $this->storageDir . DIRECTORY_SEPARATOR . $fileName;
        
        // Debug logging
        error_log("DocumentGeneratorService: Saving to path: " . $filePath);
        
        if (!class_exists('\ZipArchive')) {
            return $this->generateSimpleContract($filePath, $contractId, $data);
        }

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->addTitleStyle(1, ['size' => 16, 'bold' => true, 'color' => $this->primaryColor], ['alignment' => Jc::CENTER]);
        $phpWord->addTitleStyle(2, ['size' => 12, 'bold' => true, 'color' => $this->primaryColor], ['spaceBefore' => 240]);

        $section = $phpWord->addSection([
            'marginTop' => 1200,
            'marginBottom' => 1200,
            'marginLeft' => 1000,
            'marginRight' => 1000,
        ]);

        $header = $section->addHeader();
        $logoPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'logo.png'; 
        if (file_exists($logoPath)) {
            $header->addImage($logoPath, ['width' => 160, 'alignment' => Jc::CENTER]);
        }
        $header->addText($this->companyInfo['tagline'], ['size' => 9, 'bold' => true], ['alignment' => Jc::CENTER]);
        $header->addLine(['width' => 480, 'height' => 0, 'weight' => 2.5, 'color' => '000000', 'spaceAfter' => 200]);

        $section->addTitle($this->sanitizeForXml(strtoupper($data['title'] ?? 'Contract')), 1);
        $section->addTextBreak(1);

        $this->addMetadataTable($section, $contractId, $data);
        $section->addTextBreak(1);
        $this->addDescriptionBlock($section, $data);
        $this->addSanitizedContent($section, $data);
        $this->addSignatureSection($section, $data['client_name'] ?? 'Client');
        $this->addFooter($section);
        
        try {
            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($filePath);
            error_log("DocumentGeneratorService: File saved successfully at: " . $filePath);
        } catch (\Throwable $error) {
            error_log("DocumentGeneratorService: Error saving file: " . $error->getMessage());
            return $this->generateSimpleContract($filePath, $contractId, $data);
        }
        
        // Validate generated file using ZipArchive
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return $this->generateSimpleContract($filePath, $contractId, $data);
        }
        $zip->close();

        return $filePath;
    }

    public function generateContractPdf($contractId, array $data, array $signatures = [])
    {
        $folder = $this->storageDir . (int) $contractId;
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $filePath = $folder . '/contract_review_' . uniqid() . '.pdf';
        $printLogoPath = __DIR__ . '/../public/assets/logo-print.jpg';
        $logoPath = file_exists($printLogoPath) ? $printLogoPath : __DIR__ . '/../public/assets/logo.png';
        $pdf = $this->createPdf($logoPath);

        $pdf->SetCreator('ITEC Contract System');
        $pdf->SetAuthor($this->companyInfo['name']);
        $pdf->SetTitle($data['title'] ?? 'Contract');
        $pdf->SetSubject('Contract execution copy');
        $pdf->SetMargins(20, 48, 20);
        $pdf->SetHeaderMargin(8);
        $pdf->SetFooterMargin(16);
        $pdf->SetAutoPageBreak(true, 30);
        $pdf->AddPage();

        $this->addPdfTitle($pdf, $data);
        $this->addPdfMetadataTable($pdf, (int) $contractId, $data);
        $this->addPdfDescription($pdf, $data);
        $this->addPdfContent($pdf, $data);
        $this->addPdfSignatureSection($pdf, (int) $contractId, $data, $signatures);

        $pdf->Output($filePath, 'F');

        return $filePath;
    }

    private function createPdf($logoPath)
    {
        $companyInfo = $this->companyInfo;
        $primaryColor = $this->primaryColor;

        return new class($logoPath, $companyInfo, $primaryColor) extends \TCPDF {
            private $logoPath;
            private $companyInfo;
            private $primaryColor;

            public function __construct($logoPath, array $companyInfo, $primaryColor)
            {
                parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);
                $this->logoPath = $logoPath;
                $this->companyInfo = $companyInfo;
                $this->primaryColor = $primaryColor;
                $this->setPrintHeader(true);
                $this->setPrintFooter(true);
            }

            public function Header()
            {
                $pageWidth = $this->getPageWidth();
                if (file_exists($this->logoPath)) {
                    $type = strtoupper(pathinfo($this->logoPath, PATHINFO_EXTENSION));
                    $type = $type === 'JPG' ? 'JPEG' : $type;
                    $this->Image($this->logoPath, ($pageWidth - 28) / 2, 7, 28, 0, $type);
                }

                $this->SetY(34);
                $this->SetFont('helvetica', 'B', 7);
                $this->Cell(0, 4, $this->companyInfo['tagline'], 0, 1, 'C');
                $this->SetDrawColor(0, 0, 0);
                $this->SetLineWidth(0.35);
                $this->Line(20, 41, $pageWidth - 20, 41);
            }

            public function Footer()
            {
                $pageWidth = $this->getPageWidth();
                $this->SetY(-26);
                $this->SetDrawColor(119, 119, 119);
                $this->SetLineWidth(0.25);
                $this->Line(20, $this->GetY(), $pageWidth - 20, $this->GetY());
                $this->Ln(2);

                $this->SetFont('helvetica', 'B', 6.8);
                $infoText = "{$this->companyInfo['address']} | TIN: {$this->companyInfo['tin']} | Tel: {$this->companyInfo['phone']}";
                $this->MultiCell(0, 3.5, $infoText, 0, 'C', false, 1);

                $this->SetFont('helvetica', 'I', 6.2);
                $this->MultiCell(0, 3.5, $this->companyInfo['services'], 0, 'C', false, 1);

                $this->SetFont('helvetica', '', 6.5);
                $this->Cell(0, 4, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'C');
            }
        };
    }

    private function addPdfTitle($pdf, array $data)
    {
        [$r, $g, $b] = $this->hexToRgb($this->primaryColor);
        $pdf->SetTextColor($r, $g, $b);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->MultiCell(0, 8, strtoupper((string) ($data['title'] ?? 'Contract')), 0, 'C', false, 1);
        $pdf->Ln(6);
        $pdf->SetTextColor(0, 0, 0);
    }

    private function addPdfMetadataTable($pdf, $contractId, array $data)
    {
        $rows = [
            ['Contract Ref', '#' . (int) $contractId],
            ['Document Type', $data['document_type'] ?? 'Service Agreement'],
            ['Date', date('F d, Y')],
        ];

        foreach (['effective_date' => 'Effective Date', 'start_date' => 'Start Date', 'duration' => 'Duration', 'governing_law' => 'Governing Law'] as $key => $label) {
            if (!empty($data[$key])) {
                $rows[] = [$label, $data[$key]];
            }
        }

        $rows[] = ['Client', $data['client_name'] ?? 'To be confirmed at signing'];
        if (!empty($data['client_email'])) {
            $rows[] = ['Email', $data['client_email']];
        }

        $html = '<table cellpadding="5" cellspacing="0" border="1" style="border-color:#d9d9d9;width:100%;">';
        foreach ($rows as [$label, $value]) {
            $html .= '<tr>'
                . '<td width="28%" style="background-color:#f3f3f3;font-weight:bold;">' . $this->escapeHtml($label) . '</td>'
                . '<td width="72%">' . $this->escapeHtml($value) . '</td>'
                . '</tr>';
        }
        $html .= '</table>';

        $pdf->SetFont('helvetica', '', 9.5);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(4);
    }

    private function addPdfDescription($pdf, array $data)
    {
        $description = trim((string) ($data['description'] ?? ''));
        if ($description === '') {
            return;
        }

        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->SetTextColor(85, 85, 85);
        $pdf->MultiCell(0, 6, $description, 0, 'L', false, 1);
        $pdf->Ln(3);
        $pdf->SetTextColor(0, 0, 0);
    }

    private function addPdfContent($pdf, array $data)
    {
        [$r, $g, $b] = $this->hexToRgb($this->primaryColor);
        $pdf->SetTextColor($r, $g, $b);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 7, 'Agreement Details', 0, 1, 'L');
        $pdf->Ln(1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 10.5);

        $plainText = $this->plainTextFromHtml($data['content'] ?? '');
        if (trim($plainText) === '') {
            $plainText = "This agreement is made between the parties as described above.\nThe parties agree to the terms and conditions outlined in this document.";
        }

        foreach (preg_split('/\R/', $plainText) as $line) {
            $cleanLine = trim($line);
            if ($cleanLine !== '') {
                $pdf->MultiCell(0, 6, $cleanLine, 0, 'L', false, 1);
                $pdf->Ln(2);
            }
        }
    }

    private function addPdfSignatureSection($pdf, $contractId, array $data, array $signatures)
    {
        $this->ensurePdfSpace($pdf, 68);

        [$r, $g, $b] = $this->hexToRgb($this->primaryColor);
        $pdf->SetTextColor($r, $g, $b);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 7, 'Signatures', 0, 1, 'L');
        $pdf->Ln(3);
        $pdf->SetTextColor(0, 0, 0);

        $client = $this->signatureForRole($signatures, 'client');
        $company = $this->signatureForRole($signatures, 'company_rep');

        $leftX = $pdf->GetX();
        $topY = $pdf->GetY();
        $gap = 10;
        $columnWidth = ($pdf->getPageWidth() - 40 - $gap) / 2;
        $boxHeight = 23;

        $this->drawSignatureColumn($pdf, $leftX, $topY, $columnWidth, $boxHeight, [
            'title' => 'Client Signature',
            'name' => $data['client_name'] ?? ($client['signer_id'] ?? 'Client'),
            'email' => $data['client_email'] ?? ($client['signer_id'] ?? ''),
            'date' => $client ? date('M d, Y', strtotime($client['signed_at'])) : '_______________',
            'image' => $client ? $this->visualSignaturePath($contractId, $client) : null,
        ]);

        $rightX = $leftX + $columnWidth + $gap;
        $this->drawSignatureColumn($pdf, $rightX, $topY, $columnWidth, $boxHeight, [
            'title' => 'ITEC Solutions',
            'name' => 'Authorized Signatory',
            'email' => $company['signer_id'] ?? '',
            'date' => $company ? date('M d, Y', strtotime($company['signed_at'])) : '_______________',
            'image' => $company ? $this->visualSignaturePath($contractId, $company) : null,
        ]);

        if ($company) {
            $sealPath = __DIR__ . '/../storage/seals/company_seal.png';
            if (file_exists($sealPath) && $this->isPdfImageSafe($sealPath)) {
                $pdf->Image($sealPath, $rightX + $columnWidth - 26, $topY - 2, 22, 22, 'PNG');
            }
        }

        $pdf->SetY($topY + 56);
    }

    private function drawSignatureColumn($pdf, $x, $y, $width, $boxHeight, array $details)
    {
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.35);
        $pdf->Line($x, $y + $boxHeight, $x + $width, $y + $boxHeight);

        if (!empty($details['image']) && file_exists($details['image']) && $this->isPdfImageSafe($details['image'])) {
            [$imageWidth, $imageHeight] = $this->fittedImageSize($details['image'], $width - 10, $boxHeight - 4);
            $imageX = $x + (($width - $imageWidth) / 2);
            $imageY = $y + (($boxHeight - $imageHeight) / 2);
            $type = strtoupper(pathinfo($details['image'], PATHINFO_EXTENSION));
            $type = $type === 'JPG' ? 'JPEG' : $type;
            $pdf->Image($details['image'], $imageX, $imageY, $imageWidth, $imageHeight, $type);
        }

        $pdf->SetXY($x, $y + $boxHeight + 3);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($width, 5, $details['title'], 0, 1, 'L');
        $pdf->SetX($x);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell($width, 5, $details['name'], 0, 1, 'L');

        if (!empty($details['email'])) {
            $pdf->SetX($x);
            $pdf->SetTextColor(85, 85, 85);
            $pdf->Cell($width, 5, $details['email'], 0, 1, 'L');
            $pdf->SetTextColor(0, 0, 0);
        }

        $pdf->SetX($x);
        $pdf->Cell($width, 5, 'Date: ' . $details['date'], 0, 1, 'L');
    }

    private function ensurePdfSpace($pdf, $height)
    {
        $bottomLimit = $pdf->getPageHeight() - 32;
        if ($pdf->GetY() + $height > $bottomLimit) {
            $pdf->AddPage();
        }
    }

    private function signatureForRole(array $signatures, $role)
    {
        $match = null;
        foreach ($signatures as $signature) {
            if (($signature['signer_role'] ?? '') === $role) {
                $match = $signature;
            }
        }

        return $match;
    }

    private function visualSignaturePath($contractId, array $signature)
    {
        if (!empty($signature['signature_file_path'])) {
            $resolved = $this->resolveProjectPath($signature['signature_file_path']);
            if (is_file($resolved)) {
                return $resolved;
            }
        }

        $basePattern = dirname(__DIR__) . '/storage/signatures/sig_' . (int) $contractId . '_*_' . (int) ($signature['id'] ?? 0);
        $files = array_merge(
            glob($basePattern . '.jpg') ?: [],
            glob($basePattern . '.jpeg') ?: [],
            glob($basePattern . '.png') ?: []
        );
        if (!$files) {
            return null;
        }

        usort($files, function ($left, $right) {
            return filemtime($right) <=> filemtime($left);
        });

        return $files[0];
    }

    private function resolveProjectPath($path)
    {
        $path = str_replace('\\', '/', (string) $path);
        if ($path === '') {
            return '';
        }

        return preg_match('/^[A-Za-z]:\//', $path) || str_starts_with($path, '/')
            ? $path
            : dirname(__DIR__) . '/' . ltrim($path, '/');
    }

    private function fittedImageSize($path, $maxWidth, $maxHeight)
    {
        $size = @getimagesize($path);
        if (!$size || empty($size[0]) || empty($size[1])) {
            return [$maxWidth, $maxHeight];
        }

        [$pixelWidth, $pixelHeight] = $size;
        $width = $maxWidth;
        $height = $width * ($pixelHeight / $pixelWidth);

        if ($height > $maxHeight) {
            $height = $maxHeight;
            $width = $height * ($pixelWidth / $pixelHeight);
        }

        return [$width, $height];
    }

    private function isPdfImageSafe($path)
    {
        $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));
        if ($extension !== 'png') {
            return true;
        }

        if (extension_loaded('gd') || extension_loaded('imagick')) {
            return true;
        }

        $header = @file_get_contents($path, false, null, 0, 26);
        if ($header === false || strlen($header) < 26) {
            return false;
        }

        $colorType = ord($header[25]);
        return !in_array($colorType, [4, 6], true);
    }

    private function hexToRgb($hex)
    {
        $hex = ltrim((string) $hex, '#');
        if (strlen($hex) !== 6) {
            return [0, 0, 0];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function plainTextFromHtml($html)
    {
        $html = preg_replace('/<\/(td|th)>/i', "\t", (string) $html);
        $html = preg_replace('/<(\/p|br|hr|\/div|\/li|\/h[1-6]|\/blockquote|\/tr)>/i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+\n/", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }

    private function escapeHtml($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private function generateSimpleContract($filePath, $contractId, $data)
    {
        $content = strip_tags(str_replace(['</p>', '<br>', '<br/>'], "\n", $data['content'] ?? ''));
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');

        $lines = [
            strtoupper($data['title'] ?? 'Contract'),
            '',
            'Contract Ref: #' . (int) $contractId,
            'Document Type: ' . ($data['document_type'] ?? 'Service Agreement'),
            'Client: ' . ($data['client_name'] ?? 'To be confirmed at signing'),
            '',
            'Agreement Details',
            trim($content) !== '' ? trim($content) : 'Contract details to be completed.',
            '',
            'Client Signature: ____________________________',
            'ITEC Solutions: ____________________________',
        ];

        $this->writeZip($filePath, [
            '[Content_Types].xml' => $this->contentTypesXml(),
            '_rels/.rels' => $this->rootRelsXml(),
            'word/_rels/document.xml.rels' => $this->documentRelsXml(),
            'word/document.xml' => $this->documentXml($lines),
        ]);

        return $filePath;
    }

    private function addMetadataTable($section, $contractId, $data)
    {
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'D9D9D9', 'cellMargin' => 80]);
        $rows = [
            ['Contract Ref', '#' . (int) $contractId],
            ['Document Type', $data['document_type'] ?? 'Service Agreement'],
            ['Date', date('F d, Y')],
        ];

        if (!empty($data['effective_date'])) {
            $rows[] = ['Effective Date', $data['effective_date']];
        }

        if (!empty($data['start_date'])) {
            $rows[] = ['Start Date', $data['start_date']];
        }

        if (!empty($data['duration'])) {
            $rows[] = ['Duration', $data['duration']];
        }

        if (!empty($data['governing_law'])) {
            $rows[] = ['Governing Law', $data['governing_law']];
        }

        $rows[] = ['Client', $data['client_name'] ?? 'To be confirmed at signing'];

        if (!empty($data['client_email'])) {
            $rows[] = ['Email', $data['client_email']];
        }

        foreach ($rows as [$label, $value]) {
            $table->addRow();
            $table->addCell(2500, ['bgColor' => 'F3F3F3'])->addText($label, ['bold' => true]);
            $table->addCell(6500)->addText($this->sanitizeForXml($value));
        }
    }

    private function addSanitizedContent($section, $data)
    {
        if (!empty($data['content'])) {
            $section->addTitle('Agreement Details', 2);
            
            $plainText = strip_tags(str_replace(['</p>', '<br>', '<br/>'], "\n", $data['content']));
            $plainText = html_entity_decode($plainText, ENT_QUOTES, 'UTF-8');
            
            $paragraphs = explode("\n", $plainText);
            foreach ($paragraphs as $p) {
                $cleanLine = $this->sanitizeForXml(trim($p));
                if ($cleanLine !== '') {
                    // Detect numbered items (1., 2., etc.) and style them
                    if (preg_match('/^\d+\./', $cleanLine)) {
                        $section->addText($cleanLine, ['size' => 10.5, 'bold' => true]);
                    } else {
                        $section->addText($cleanLine, ['size' => 10.5]);
                    }
                    $section->addTextBreak(0.5);
                }
            }
        } else {
            $section->addTitle('Agreement Details', 2);
            $section->addText('This agreement is made between the parties as described above.', ['size' => 10.5]);
            $section->addTextBreak(1);
            $section->addText('The parties agree to the terms and conditions outlined in this document.', ['size' => 10.5]);
        }
    }

    private function sanitizeForXml($text) 
    {
        if (empty($text)) return '';
        $text = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $text);
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function addSignatureSection($section, $clientName)
    {
        $section->addTextBreak(2);
        $section->addTitle('Signatures', 2);

        $table = $section->addTable(['borderSize' => 0]);
        $table->addRow(400);
        $table->addCell(4000, ['borderBottomSize' => 6]);
        $table->addCell(1000);
        $table->addCell(4000, ['borderBottomSize' => 6]);

        $table->addRow();
        $table->addCell(4000)->addText('Client Signature', ['size' => 9, 'bold' => true]);
        $table->addCell(1000);
        $table->addCell(4000)->addText('ITEC Solutions', ['size' => 9, 'bold' => true]);

        $table->addRow();
        $table->addCell(4000)->addText($this->sanitizeForXml($clientName), ['size' => 9]);
        $table->addCell(1000);
        $table->addCell(4000)->addText('Authorized Signatory', ['size' => 9]);

        $table->addRow();
        $table->addCell(4000)->addText('Date: _______________', ['size' => 9]);
        $table->addCell(1000);
        $table->addCell(4000)->addText('Date: _______________', ['size' => 9]);
    }

    private function addFooter($section)
    {
        $footer = $section->addFooter();
        $footer->addLine(['width' => 480, 'height' => 0, 'weight' => 1.5, 'color' => '777777']);

        $center = ['alignment' => Jc::CENTER];
        $infoText = "{$this->companyInfo['address']} | TIN: {$this->companyInfo['tin']} | Tel: {$this->companyInfo['phone']}";
        $footer->addText($this->sanitizeForXml($infoText), ['size' => 8, 'bold' => true, 'color' => '333333'], $center);
        $footer->addText($this->sanitizeForXml($this->companyInfo['services']), ['size' => 7, 'italic' => true, 'color' => '666666'], $center);
        $footer->addTextBreak(1);
        $footer->addPreserveText('Page {PAGE} of {NUMPAGES}', ['size' => 8], $center);
    }
    
    public function updateContractContent($contractId, $content)
    {
        $filePath = $this->storageDir . DIRECTORY_SEPARATOR . "contract_{$contractId}.docx";
        
        if (!file_exists($filePath)) {
            return false;
        }
        
        $phpWord = IOFactory::load($filePath);
        
        $sections = $phpWord->getSections();
        if (empty($sections)) {
            return false;
        }
        
        $data = [
            'title' => 'Updated Contract',
            'client_name' => 'Client Name',
            'client_email' => 'client@email.com',
            'content' => $content
        ];
        
        return $this->generateContract($contractId, $data);
    }
    
    // Helper methods for simple contract generation
    private function writeZip($filePath, $files) {
        $zip = new ZipArchive();
        if ($zip->open($filePath, ZipArchive::CREATE) !== true) {
            return;
        }
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
    }
    
    private function contentTypesXml() {
        return '<?xml version="1.0" encoding="UTF-8"?>
        <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
            <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
            <Default Extension="xml" ContentType="application/xml"/>
            <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
        </Types>';
    }
    
    private function rootRelsXml() {
        return '<?xml version="1.0" encoding="UTF-8"?>
        <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
            <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
        </Relationships>';
    }
    
    private function documentRelsXml() {
        return '<?xml version="1.0" encoding="UTF-8"?>
        <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
        </Relationships>';
    }
    
    private function documentXml($lines) {
        $body = '';
        foreach ($lines as $line) {
            if (empty($line)) {
                $body .= '<w:p><w:pPr><w:pStyle w:val="Normal"/></w:pPr></w:p>';
            } else {
                $body .= '<w:p><w:pPr><w:pStyle w:val="Normal"/></w:pPr><w:r><w:t xml:space="preserve">' . htmlspecialchars($line) . '</w:t></w:r></w:p>';
            }
        }
        
        return '<?xml version="1.0" encoding="UTF-8"?>
        <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
            <w:body>' . $body . '<w:sectPr><w:pgSz w:w="12240" w:h="15840"/></w:sectPr></w:body>
        </w:document>';
    }
}