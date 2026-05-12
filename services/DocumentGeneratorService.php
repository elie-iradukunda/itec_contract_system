<?php

namespace Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Language;
use ZipArchive;

class DocumentGeneratorService
{
    private string $storageDir;
    private array  $companyInfo;

    private const PRIMARY_COLOR   = '80181A';
    private const COMPANY_COLOR   = '1F3263';
    private const RWANDA_COLOR    = 'C000C0';
    private const SECONDARY_COLOR = 'F3F3F3';
    private const TEXT_DARK       = '1A1A1A';
    private const TEXT_MID        = '444444';
    private const TEXT_LIGHT      = '777777';
    private const BORDER_COLOR    = 'D0D0D0';

    public function __construct()
    {
        $resolved = realpath(__DIR__ . '/../storage/contracts/');
        $this->storageDir = $resolved ?: __DIR__ . '/../storage/contracts/';
        $this->ensureDirectoryExists($this->storageDir);

        $this->companyInfo = [
            'name'       => 'INFORMATION TECHNOLOGY ENGINEERING CONSTRUCTION',
            'short_name' => 'ITEC LTD',
            'address'    => 'Head office in Rwanda –Nyarugenge District, Kigali, Rwanda',
            'tin'        => '105253130',
            'phone'      => '+250 788620612',
            'email'      => 'info@itec.rw',
            'website'    => 'www.itec.rw',
            'country'    => 'Rwanda',
            'tagline'    => 'BE SMART, CHOOSE SMART',
            'services'   => 'Web hosting & design, software development, web & mobile Application development, Graphic design, IT Consultancy, IT supplying & support, office support',
        ];
    }

    public function generateContractPdf($contractId, array $data, array $signatures = [])
    {
        $contractId = (int) $contractId;
        $contractDir = rtrim($this->storageDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $contractId;
        if (!is_dir($contractDir)) {
            mkdir($contractDir, 0777, true);
        }

        $signatureBlock = null;
        $bodyHtml = $this->contractPdfBodyHtml($data['content'] ?? '', $signatureBlock);
        $html = $this->contractPdfHtml($contractId, $data, $bodyHtml, $signatures, $signatureBlock);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->setChroot(dirname(__DIR__));

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfPath = $contractDir . DIRECTORY_SEPARATOR . 'contract_review_' . uniqid('', true) . '.pdf';
        if (file_put_contents($pdfPath, $dompdf->output()) === false) {
            throw new \Exception('Generated contract PDF could not be saved.');
        }

        return $pdfPath;
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
            return $this->generateFallbackContract($filePath, $contractId, $data);
        }

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $phpWord = $this->createPhpWordInstance();

        // ── Section 1: Cover page ──────────────────────────────────────────
        $coverSection = $phpWord->addSection([
            'paperSize'    => 'A4',
            'marginTop'    => 1000,
            'marginBottom' => 800,
            'marginLeft'   => 1200,
            'marginRight'  => 1200,
            'headerHeight' => 1,    // effectively no header space
            'footerHeight' => 700,
            'titlePage'    => true,
        ]);

        // Empty first-page header (logo lives in the body on the cover page)
        $coverSection->addHeader('first');

        // First-page footer — same company info strip as body pages
        $this->addPageFooter($coverSection, 'first');

        $this->addCoverPage($coverSection, $data);

        // ── Section 2: Contract body ───────────────────────────────────────
        $bodySection = $phpWord->addSection([
            'paperSize'    => 'A4',
            'marginTop'    => 1400,
            'marginBottom' => 1400,
            'marginLeft'   => 1200,
            'marginRight'  => 1200,
            'headerHeight' => 900,
            'footerHeight' => 700,
        ]);

        $this->addBodyHeader($bodySection);
        $this->addPageFooter($bodySection);
        $this->addMetadataTable($bodySection, $contractId, $data);
        $this->addBodyContent($bodySection, $data);
        $this->addSignatureSection($bodySection, $data);

        try {
            IOFactory::createWriter($phpWord, 'Word2007')->save($filePath);
        } catch (\Throwable $e) {
            error_log('[DocumentGeneratorService] Save error: ' . $e->getMessage());
            return $this->generateFallbackContract($filePath, $contractId, $data);
        }

        $this->validateDocx($filePath);
        return $filePath;
    }

    public function updateContractContent(int $contractId, array $data): string|false
    {
        $filePath = $this->storageDir . DIRECTORY_SEPARATOR . "contract_{$contractId}.docx";
        if (!file_exists($filePath)) {
            return false;
        }
        return $this->generateContract($contractId, $data);
    }

    // =========================================================================
    // PhpWord bootstrap
    // =========================================================================

    private function createPhpWordInstance(): PhpWord
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(11);

        $settings = $phpWord->getSettings();
        $settings->setThemeFontLang(new Language(Language::EN_US));

        $phpWord->addParagraphStyle('bodyPara', [
            'spaceBefore' => 0,
            'spaceAfter'  => 120,
            'lineHeight'  => 1.15,
        ]);
        $phpWord->addParagraphStyle('center', [
            'alignment'  => Jc::CENTER,
            'spaceAfter' => 0,
            'spaceBefore'=> 0,
        ]);

        $phpWord->addTitleStyle(1,
            ['size' => 18, 'bold' => true, 'color' => self::PRIMARY_COLOR, 'allCaps' => true],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 80]
        );
        $phpWord->addTitleStyle(2,
            ['size' => 12, 'bold' => true, 'color' => self::PRIMARY_COLOR, 'allCaps' => true],
            ['spaceBefore' => 320, 'spaceAfter' => 160]
        );
        $phpWord->addTitleStyle(3,
            ['size' => 11, 'bold' => true, 'color' => self::TEXT_DARK],
            ['spaceBefore' => 200, 'spaceAfter' => 80]
        );

        $phpWord->addFontStyle('bodyFont',     ['size' => 11, 'color' => self::TEXT_DARK]);
        $phpWord->addFontStyle('labelFont',    ['size' => 10, 'bold' => true, 'color' => self::TEXT_MID]);
        $phpWord->addFontStyle('valueFont',    ['size' => 10, 'color' => self::TEXT_DARK]);
        $phpWord->addFontStyle('numberedFont', ['size' => 11, 'bold' => true, 'color' => self::PRIMARY_COLOR]);

        return $phpWord;
    }

    // =========================================================================
    // Cover page
    // =========================================================================

    private function addCoverPage(\PhpOffice\PhpWord\Element\Section $section, array $data): void
    {
        $c = ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0];

        // ── Logo centered in body ────────────────────────────────────────────
        $logoPath = realpath(__DIR__ . '/../public/assets/logo.png');
        if ($logoPath && file_exists($logoPath)) {
            $section->addImage($logoPath, [
                'width'     => 100,
                'alignment' => Jc::CENTER,
            ]);
        } else {
            $section->addText(
                $this->x($this->companyInfo['name']),
                ['size' => 16, 'bold' => true, 'color' => self::COMPANY_COLOR],
                $c
            );
        }

        $section->addTextBreak(2);

        // ── Contract title + thick rule ──────────────────────────────────────
        $titleText = 'CONTRACT FOR ' . $this->x(strtoupper($data['title'] ?? '[CONTRACT NAME]'));
        $section->addText($titleText, ['size' => 12, 'color' => self::TEXT_DARK], $c);

        // Thick black rule directly under the title
        $section->addLine([
            'width'  => 9360,
            'height' => 0,
            'weight' => 3,
            'color'  => '000000',
        ]);

        $section->addTextBreak(1);

        // ── "Between" ────────────────────────────────────────────────────────
        $section->addText(
            'Between',
            ['size' => 12, 'bold' => true, 'smallCaps' => true, 'color' => self::TEXT_DARK],
            $c
        );

        $section->addTextBreak(2);

        // ── Company name (dark navy, bold, all caps) ──────────────────────────
        $section->addText(
            $this->x($this->companyInfo['name']),
            ['size' => 13, 'bold' => true, 'allCaps' => true, 'color' => self::COMPANY_COLOR],
            $c
        );

        // (ITEC LTD) — italic, same navy
        $section->addText(
            '(' . $this->x($this->companyInfo['short_name']) . ')',
            ['size' => 11, 'italic' => true, 'bold' => true, 'color' => self::COMPANY_COLOR],
            $c
        );

        // TIN Number <italic value>
        $tinRun = $section->addTextRun($c);
        $tinRun->addText('TIN Number ', ['size' => 11, 'bold' => true, 'color' => self::TEXT_DARK]);
        $tinRun->addText($this->x($this->companyInfo['tin']), ['size' => 11, 'italic' => true, 'color' => self::TEXT_DARK]);

        // Tel
        $telRun = $section->addTextRun($c);
        $telRun->addText('Tel ', ['size' => 11, 'bold' => true, 'color' => self::TEXT_DARK]);
        $telRun->addText($this->x($this->companyInfo['phone']), ['size' => 11, 'color' => self::TEXT_DARK]);

        // Email
        $emailRun = $section->addTextRun($c);
        $emailRun->addText('Email: ', ['size' => 11, 'bold' => true, 'color' => self::TEXT_DARK]);
        $emailRun->addText($this->x($this->companyInfo['email']), ['size' => 11, 'color' => self::TEXT_DARK]);

        // Website (no bullet — matches the image)
        $webRun = $section->addTextRun($c);
        $webRun->addText('Website: ', ['size' => 11, 'bold' => true, 'color' => self::TEXT_DARK]);
        $webRun->addText($this->x($this->companyInfo['website']), ['size' => 11, 'color' => self::TEXT_DARK]);

        // Rwanda — magenta
        $section->addText(
            $this->x($this->companyInfo['country']),
            ['size' => 12, 'bold' => true, 'color' => self::RWANDA_COLOR],
            $c
        );

        // ── AND ──────────────────────────────────────────────────────────────
        $section->addTextBreak(2);
        $section->addText('AND', ['size' => 12, 'bold' => true, 'color' => self::TEXT_DARK], $c);

        // ── Client name ───────────────────────────────────────────────────────
        $section->addTextBreak(2);
        $clientDisplay = !empty($data['client_name'])
            ? strtoupper($data['client_name'])
            : 'COMPANY NAME';
        $section->addText(
            '(' . $this->x($clientDisplay) . ')',
            ['size' => 12, 'bold' => true, 'color' => self::TEXT_DARK],
            $c
        );

        // ── Month + Year ──────────────────────────────────────────────────────
        $section->addTextBreak(2);
        $section->addText(
            strtoupper(date('F Y')),
            ['size' => 12, 'bold' => true, 'color' => self::TEXT_DARK],
            $c
        );
    }

    // =========================================================================
    // Body header & shared footer
    // =========================================================================
private function addBodyHeader(\PhpOffice\PhpWord\Element\Section $section): void
{
    $header   = $section->addHeader();
    $logoPath = realpath(__DIR__ . '/../public/assets/logo.png');

    if ($logoPath && file_exists($logoPath)) {
        $header->addImage($logoPath, [
            'width'     => 100,
            'alignment' => Jc::CENTER,
        ]);
    } else {
        $header->addText(
            $this->x($this->companyInfo['short_name']),
            ['size' => 13, 'bold' => true, 'color' => self::COMPANY_COLOR],
            ['alignment' => Jc::CENTER]
        );
    }

    $header->addLine([
        'width'  => 9360,
        'height' => 0,
        'weight' => 2,
        'color'  => self::PRIMARY_COLOR,
    ]);
}
    /**
     * Shared footer used on both the cover page and body pages.
     * Red rule + address + services line.
     */
    private function addPageFooter(\PhpOffice\PhpWord\Element\Section $section, string $type = 'default'): void
    {
        $footer = $section->addFooter($type);
        $center = ['alignment' => Jc::CENTER];

        // Red rule
        $footer->addLine([
            'width'  => 9360,
            'height' => 0,
            'weight' => 1.5,
            'color'  => self::PRIMARY_COLOR,
        ]);

        // Address + TIN + Tel on one line
        $footer->addText(
            $this->x(
                $this->companyInfo['address'] .
                ' Tin: ' . $this->companyInfo['tin'] .
                ' | Tel: ' . $this->companyInfo['phone'] . ','
            ),
            ['size' => 7, 'color' => self::TEXT_MID],
            $center
        );

        // Services line
        $footer->addText(
            $this->x($this->companyInfo['services']),
            ['size' => 7, 'color' => self::TEXT_MID],
            $center
        );
    }

    // =========================================================================
    // Body sections
    // =========================================================================

    private function addMetadataTable(\PhpOffice\PhpWord\Element\Section $section, int $contractId, array $data): void
    {
        $section->addTitle('Contract Details', 2);

        $rows = [
            ['Contract Reference', '#' . $contractId],
            ['Date Issued',        date('F d, Y')],
            ['Client Name',        $data['client_name']  ?? '—'],
            ['Client Email',       $data['client_email'] ?? '—'],
            ['Prepared By',        $this->companyInfo['short_name']],
        ];

        $table = $section->addTable([
            'borderSize'  => 6,
            'borderColor' => self::BORDER_COLOR,
            'cellMargin'  => 120,
        ]);

        foreach ($rows as $i => [$label, $value]) {
            $table->addRow(380);
            $table->addCell(2800, ['bgColor' => $i % 2 === 0 ? 'F7F7F7' : self::SECONDARY_COLOR])
                  ->addText($this->x($label), 'labelFont');
            $table->addCell(6500, ['bgColor' => $i % 2 === 0 ? 'FFFFFF' : 'FAFAFA'])
                  ->addText($this->x((string) $value), 'valueFont');
        }

        $section->addTextBreak(1);
        
        $signatureBlock = $this->addSanitizedContent($section, $data);
        $this->addSignatureSection($section, $data['client_name'], $signatureBlock);
        
        // --- Footer Section ---
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
            throw new \Exception('Generated DOCX is invalid.');
        }
        $zip->close();
        
        return $filePath;
    }

    private function addBodyContent(\PhpOffice\PhpWord\Element\Section $section, array $data): void
    {
        $section->addTitle('Agreement Details', 2);

        $rawContent = $data['content'] ?? '';

        if (trim(strip_tags($rawContent)) === '') {
            $section->addText(
                'This agreement is made between the parties as described above. Full terms shall be appended prior to execution.',
                'bodyFont',
                'bodyPara'
            );
            return;
        }

        $text = str_replace(['</p>', '</div>', '<br>', '<br/>', '<br />'], "\n", $rawContent);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        foreach (explode("\n", $text) as $para) {
            $line = trim($para);
            if ($line === '') {
                $section->addTextBreak(1);
                continue;
            }

            $clean = $this->x($line);

            if (preg_match('/^(\d+[\.\)])\s+(.+)/', $line, $m)) {
                $run = $section->addTextRun('bodyPara');
                $run->addText($m[1] . '  ', 'numberedFont');
                $run->addText($this->x($m[2]), 'bodyFont');
            } elseif (preg_match('/^[-–•]\s+(.+)/', $line, $m)) {
                $section->addListItem($this->x($m[1]), 0, 'bodyFont', 'listBullet');
            } elseif ($line === strtoupper($line) && strlen($line) > 4 && strlen($line) < 120) {
                $section->addTitle($clean, 3);
            } else {
                $section->addText($clean, 'bodyFont', 'bodyPara');
            }
        }

        $section->addTextBreak(1);
    }

    private function addSignatureSection(\PhpOffice\PhpWord\Element\Section $section, array $data): void
    {
        $section->addLine(['width' => 9360, 'height' => 0, 'weight' => 1, 'color' => self::BORDER_COLOR]);
        $section->addTextBreak(1);
        $section->addTitle('Signatures', 2);

        $section->addText(
            'By signing below, both parties agree to the terms and conditions outlined in this agreement.',
            ['size' => 9, 'italic' => true, 'color' => self::TEXT_LIGHT],
            'bodyPara'
        );
        $section->addTextBreak(2);

        $sigTable = $section->addTable(['borderSize' => 0, 'cellMargin' => 60]);

        $sigTable->addRow(600);
        $sigTable->addCell(4200, ['borderBottomSize' => 10, 'borderBottomColor' => self::TEXT_DARK]);
        $sigTable->addCell(800);
        $sigTable->addCell(4200, ['borderBottomSize' => 10, 'borderBottomColor' => self::TEXT_DARK]);

        $sigTable->addRow();
        $l1 = $sigTable->addCell(4200); $sigTable->addCell(800); $r1 = $sigTable->addCell(4200);
        $l1->addText('CLIENT SIGNATURE', ['size' => 8, 'bold' => true, 'color' => self::TEXT_LIGHT]);
        $r1->addText('AUTHORISED SIGNATORY', ['size' => 8, 'bold' => true, 'color' => self::TEXT_LIGHT]);

        $sigTable->addRow();
        $l2 = $sigTable->addCell(4200); $sigTable->addCell(800); $r2 = $sigTable->addCell(4200);
        $l2->addText($this->x($data['client_name'] ?? ''), ['size' => 10, 'color' => self::TEXT_DARK]);
        $r2->addText($this->x($this->companyInfo['short_name']), ['size' => 10, 'color' => self::TEXT_DARK]);

        $sigTable->addRow(400);
        $l3 = $sigTable->addCell(4200); $sigTable->addCell(800); $r3 = $sigTable->addCell(4200);
        $l3->addText('Date: ___________________________', ['size' => 9, 'color' => self::TEXT_LIGHT]);
        $r3->addText('Date: ___________________________', ['size' => 9, 'color' => self::TEXT_LIGHT]);

        $section->addTextBreak(2);

        $stampTable = $section->addTable(['borderSize' => 6, 'borderColor' => self::BORDER_COLOR, 'cellMargin' => 160]);
        $stampTable->addRow(800);
        $sc = $stampTable->addCell(9360, ['bgColor' => 'FFF8F8']);
        $sc->addText(
            'OFFICIAL USE ONLY — COMPANY SEAL / STAMP',
            ['size' => 9, 'bold' => true, 'color' => self::PRIMARY_COLOR, 'allCaps' => true],
            ['alignment' => Jc::CENTER]
        );
        $sc->addTextBreak(2);
        $sc->addText(
            'Approval Code: ___________________          Approved By: ___________________          Date: ___________________',
            ['size' => 9, 'color' => self::TEXT_LIGHT],
            ['alignment' => Jc::CENTER]
        );
    }

    // =========================================================================
    // Fallback: raw ZIP DOCX
    // =========================================================================

    private function generateFallbackContract(string $filePath, int $contractId, array $data): string
    {
        $content = strip_tags(str_replace(['</p>', '<br>', '<br/>'], "\n", $data['content'] ?? ''));
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');

        $lines = [
            'CONTRACT FOR ' . strtoupper($data['title'] ?? ''),
            '',
            'Between',
            '',
            $this->companyInfo['name'],
            '(' . $this->companyInfo['short_name'] . ')',
            'TIN: ' . $this->companyInfo['tin'],
            'Tel: ' . $this->companyInfo['phone'],
            'Email: ' . $this->companyInfo['email'],
            'Website: ' . $this->companyInfo['website'],
            $this->companyInfo['country'],
            '',
            'AND',
            '',
            '(' . strtoupper($data['client_name'] ?? 'COMPANY NAME') . ')',
            '',
            strtoupper(date('F Y')),
            '',
            str_repeat('-', 60),
            '',
            'Contract Reference : #' . $contractId,
            'Date               : ' . date('F d, Y'),
            'Client             : ' . ($data['client_name']  ?? ''),
            'Email              : ' . ($data['client_email'] ?? ''),
            '',
            'AGREEMENT DETAILS',
            trim($content) !== '' ? trim($content) : 'Contract details to be completed.',
            '',
            'SIGNATURES',
            'Client Signature  : ____________________________',
            $this->companyInfo['short_name'] . ' : ____________________________',
            'Date              : ____________________________',
        ];

        $this->writeZip($filePath, [
            '[Content_Types].xml'          => $this->contentTypesXml(),
            '_rels/.rels'                  => $this->rootRelsXml(),
            'word/_rels/document.xml.rels' => $this->documentRelsXml(),
            'word/document.xml'            => $this->documentXml($lines),
        ]);

        return $filePath;
    }

    private function contractPdfHtml($contractId, array $data, $bodyHtml, array $signatures, $signatureBlock = null)
    {
        $title = $this->cleanText($data['title'] ?? 'Contract');
        $documentType = $this->cleanText($data['document_type'] ?? $data['type'] ?? 'Contract');
        $clientName = $this->cleanText($data['client_name'] ?? '') ?: 'Client Representative';
        $clientEmail = $this->cleanText($data['client_email'] ?? '') ?: 'Not provided';
        $date = date('F d, Y');
        $logo = $this->imageDataUri($this->projectPath('public/assets/logo-print.jpg'))
            ?: $this->imageDataUri($this->projectPath('public/assets/logo.png'));

        $logoHtml = $logo
            ? '<img class="logo" src="' . $this->e($logo) . '" alt="ITEC Solutions">'
            : '<div class="logo-text">ITEC Solutions</div>';

        return '<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 34px 42px 56px; }
        body { color: #1f2937; font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; line-height: 1.55; }
        .header { text-align: center; padding-bottom: 12px; border-bottom: 3px solid #111827; margin-bottom: 22px; }
        .logo { width: 138px; height: auto; margin-bottom: 5px; }
        .logo-text { color: #80181a; font-size: 21px; font-weight: bold; margin-bottom: 5px; }
        .tagline { color: #42546a; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        h1 { color: #80181a; margin: 0 0 16px; font-size: 20px; text-align: center; text-transform: uppercase; }
        h2 { color: #80181a; margin: 22px 0 10px; font-size: 14px; text-transform: uppercase; }
        h3 { color: #183655; margin: 15px 0 7px; font-size: 12.5px; }
        p { margin: 0 0 8px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .meta td { border: 1px solid #d8dee8; padding: 8px 10px; vertical-align: top; }
        .meta td:first-child { width: 25%; color: #183655; background: #f3f5f8; font-weight: bold; }
        .body-copy ul, .body-copy ol { margin: 6px 0 10px 22px; padding: 0; }
        .body-copy li { margin: 0 0 5px; }
        .body-copy .note { color: #657086; font-style: italic; }
        .date-table { width: 100%; border-collapse: collapse; margin: 8px 0 14px; }
        .date-table td { border: 1px solid #d8dee8; padding: 8px 10px; }
        .date-table td:first-child { width: 30%; background: #f8fafc; font-weight: bold; }
        .signature-table { width: 100%; border-collapse: collapse; margin-top: 12px; page-break-inside: avoid; }
        .signature-table td { width: 50%; padding: 0 18px 0 0; vertical-align: bottom; }
        .signature-box { height: 78px; border-bottom: 2px solid #111827; text-align: center; vertical-align: middle; }
        .signature-box img { max-width: 210px; max-height: 62px; margin-top: 8px; }
        .signature-placeholder { color: #8a94a6; padding-top: 34px; font-size: 10.5px; }
        .signature-label { color: #183655; margin-top: 7px; font-weight: bold; }
        .signature-small { color: #657086; font-size: 10.5px; }
        .footer { position: fixed; left: 0; right: 0; bottom: -34px; color: #657086; text-align: center; font-size: 9px; border-top: 1px solid #9ca3af; padding-top: 7px; }
    </style>
</head>
<body>
    <div class="header">' . $logoHtml . '<div class="tagline">' . $this->e($this->companyInfo['tagline']) . '</div></div>
    <h1>' . $this->e($title) . '</h1>
    <table class="meta">
        <tr><td>Contract Ref</td><td>#' . (int) $contractId . '</td></tr>
        <tr><td>Document Type</td><td>' . $this->e($documentType) . '</td></tr>
        <tr><td>Client</td><td>' . $this->e($clientName) . '</td></tr>
        <tr><td>Email</td><td>' . $this->e($clientEmail) . '</td></tr>
        <tr><td>Date</td><td>' . $this->e($date) . '</td></tr>
    </table>
    <h2>Agreement Details</h2>
    <div class="body-copy">' . $bodyHtml . '</div>
    ' . $this->contractPdfSignatureHtml($data, $signatures, $signatureBlock) . '
    <div class="footer">' . $this->e($this->companyInfo['address']) . ' | TIN: ' . $this->e($this->companyInfo['tin']) . ' | Tel: ' . $this->e($this->companyInfo['phone']) . '<br>' . $this->e($this->companyInfo['services']) . '</div>
</body>
</html>';
    }

    private function contractPdfBodyHtml($content, &$signatureBlock = null)
    {
        $content = (string) $content;
        if ($this->hasDraftSections($content)) {
            $sections = $this->parseDraftSections($content);
            $html = '';

            foreach ($sections as $draftSection) {
                $type = $draftSection['type'] ?? 'paragraph';
                if ($type === 'signature') {
                    $signatureBlock = $draftSection;
                    continue;
                }

                if ($type === 'heading') {
                    $html .= '<h3>' . $this->e($draftSection['title'] ?? 'Contract section') . '</h3>';
                    if (!empty($draftSection['note'])) {
                        $html .= '<p class="note">' . $this->e($draftSection['note']) . '</p>';
                    }
                    continue;
                }

                if ($type === 'checkbox') {
                    $mark = !empty($draftSection['checked']) ? '[x]' : '[ ]';
                    $html .= '<h3>' . $this->e($draftSection['title'] ?? 'Acceptance') . '</h3>';
                    $html .= '<p><strong>' . $this->e($mark . ' ' . ($draftSection['label'] ?? 'Acceptance checkbox')) . '</strong></p>';
                    continue;
                }

                if ($type === 'date') {
                    $html .= '<h3>' . $this->e($draftSection['title'] ?? 'Date') . '</h3>';
                    $html .= '<table class="date-table"><tr><td>' . $this->e($draftSection['label'] ?? 'Date') . '</td><td>' . $this->e($draftSection['value'] ?? '________________') . '</td></tr></table>';
                    continue;
                }

                $title = $this->cleanText($draftSection['title'] ?? '');
                if ($title !== '') {
                    $html .= '<h3>' . $this->e($title) . '</h3>';
                }

                if ($type === 'list') {
                    $html .= $this->contractPdfListHtml($draftSection);
                    continue;
                }

                $html .= $this->contractPdfBlocksHtml($draftSection['blocks'] ?? []);
            }

            return trim($html) !== '' ? $html : '<p>Contract details to be completed.</p>';
        }

        $html = $this->sanitizePdfHtml($content);
        return trim(strip_tags($html)) !== '' ? $html : '<p>Contract details to be completed.</p>';
    }

    private function contractPdfBlocksHtml(array $blocks)
    {
        $html = '';
        foreach ($blocks as $block) {
            $text = $this->cleanText($block['text'] ?? '');
            if ($text === '') {
                continue;
            }

            if (($block['type'] ?? '') === 'heading') {
                $html .= '<h3>' . $this->e($text) . '</h3>';
            } elseif (($block['type'] ?? '') === 'list') {
                $html .= '<p style="margin-left: 16px;">' . $this->e($text) . '</p>';
            } else {
                $html .= '<p>' . $this->e($text) . '</p>';
            }
        }

        return $html;
    }

    private function contractPdfListHtml(array $draftSection)
    {
        $style = $draftSection['list_style'] ?? 'bullet';
        $items = $draftSection['items'] ?? [];
        $tag = $style === 'numbered' ? 'ol' : 'ul';
        $html = '<' . $tag . '>';

        foreach ($items as $item) {
            $text = $this->cleanText($item['text'] ?? '');
            if ($text === '') {
                continue;
            }

            if ($style === 'checklist') {
                $text = (!empty($item['checked']) ? '[x] ' : '[ ] ') . $text;
            }

            $html .= '<li>' . $this->e($text) . '</li>';
        }

        return $html . '</' . $tag . '>';
    }

    private function contractPdfSignatureHtml(array $data, array $signatures, $signatureBlock = null)
    {
        $clientSignature = $this->signatureForRole($signatures, 'client');
        $companySignature = $this->signatureForRole($signatures, 'company_rep');
        $clientImage = $this->signatureImageDataUri($clientSignature);
        $companyImage = $this->signatureImageDataUri($companySignature);

        $leftSigner = $this->cleanText($signatureBlock['left_signer'] ?? '') ?: $this->cleanText($data['client_name'] ?? '') ?: 'Client Representative';
        $rightSigner = $this->cleanText($signatureBlock['right_signer'] ?? '') ?: 'Authorized Signatory';
        $clientSignedAt = $this->signatureDate($clientSignature['signed_at'] ?? null);
        $companySignedAt = $this->signatureDate($companySignature['signed_at'] ?? null);

        $clientBox = $clientImage
            ? '<img src="' . $this->e($clientImage) . '" alt="Client signature">'
            : '<div class="signature-placeholder">Client signature space</div>';
        $companyBox = $companyImage
            ? '<img src="' . $this->e($companyImage) . '" alt="Company signature">'
            : '<div class="signature-placeholder">Reserved for ITEC signatory</div>';

        return '<h2>Signatures</h2>
<table class="signature-table">
    <tr>
        <td>
            <div class="signature-box">' . $clientBox . '</div>
            <div class="signature-label">Client Signature</div>
            <div class="signature-small">' . $this->e($leftSigner) . '</div>
            <div class="signature-small">Date: ' . $this->e($clientSignedAt) . '</div>
        </td>
        <td>
            <div class="signature-box">' . $companyBox . '</div>
            <div class="signature-label">ITEC Solutions</div>
            <div class="signature-small">' . $this->e($rightSigner) . '</div>
            <div class="signature-small">Date: ' . $this->e($companySignedAt) . '</div>
        </td>
    </tr>
</table>';
    }

    private function sanitizePdfHtml($html)
    {
        $allowed = '<p><br><strong><b><em><i><u><s><span><div><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><table><thead><tbody><tfoot><tr><td><th><hr>';
        $html = strip_tags((string) $html, $allowed);
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);
        $html = preg_replace('/\s+(class|id|style|data-[a-z0-9_-]+)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);

        return $html;
    }

    private function signatureForRole(array $signatures, $role)
    {
        $match = null;
        foreach ($signatures as $signature) {
            if (($signature['signer_role'] ?? '') === $role) {
                $match = $signature;
            }
        }

        return $match ?: [];
    }

    private function signatureImageDataUri(array $signature)
    {
        $path = $signature['signature_file_path'] ?? '';
        if ($path === '') {
            return '';
        }

        return $this->imageDataUri($this->resolveProjectPath($path));
    }

    private function imageDataUri($path)
    {
        $path = (string) $path;
        if ($path === '' || !is_file($path)) {
            return '';
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => '',
        };

        if ($mime === '') {
            return '';
        }

        $contents = file_get_contents($path);
        return $contents === false ? '' : 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    private function signatureDate($value)
    {
        return !empty($value) ? date('F d, Y', strtotime($value)) : '________________';
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

    private function projectPath($path)
    {
        return dirname(__DIR__) . '/' . ltrim(str_replace('\\', '/', (string) $path), '/');
    }

    private function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
    
    private function addMetadataTable($section, $contractId, $data)
    {
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'D9D9D9', 'cellMargin' => 80]);
        $table->addRow();
        $table->addCell(2500, ['bgColor' => 'F3F3F3'])->addText('Contract Ref', ['bold' => true]);
        $table->addCell(6500)->addText("#{$contractId}");
        
        $table->addRow();
        $table->addCell(2500, ['bgColor' => 'F3F3F3'])->addText('Client', ['bold' => true]);
        $table->addCell(6500)->addText($this->sanitizeForXml($data['client_name']));
        
        $table->addRow();
        $table->addCell(2500, ['bgColor' => 'F3F3F3'])->addText('Date', ['bold' => true]);
        $table->addCell(6500)->addText(date('F d, Y'));
        
        $table->addRow();
        $table->addCell(2500, ['bgColor' => 'F3F3F3'])->addText('Email', ['bold' => true]);
        $table->addCell(6500)->addText($this->sanitizeForXml($data['client_email']));
    }
    
    private function addSanitizedContent($section, $data)
    {
        $section->addTitle('Agreement Details', 2);

        if (!empty($data['content'])) {
            if ($this->hasDraftSections($data['content'])) {
                return $this->addStructuredDraftContent($section, $data['content']);
            }

            $paragraphs = $this->extractContractParagraphs($data['content']);

            foreach ($paragraphs as $paragraph) {
                $cleanLine = $this->sanitizeForXml($paragraph['text']);
                if ($cleanLine === '') {
                    continue;
                }

                if ($paragraph['is_heading']) {
                    $section->addText(
                        $cleanLine,
                        ['size' => 11, 'bold' => true, 'color' => $this->primaryColor],
                        ['spaceBefore' => 180, 'spaceAfter' => 90]
                    );
                    continue;
                }

                if ($paragraph['is_list']) {
                    $section->addText(
                        $cleanLine,
                        ['size' => 10.5],
                        ['indentation' => ['left' => 360, 'hanging' => 180], 'spaceAfter' => 90]
                    );
                    continue;
                }

                $section->addText($cleanLine, ['size' => 10.5], ['spaceAfter' => 110, 'lineHeight' => 1.18]);
            }
        } else {
            $section->addText('This agreement is made between the parties as described above.', ['size' => 10.5], ['spaceAfter' => 110]);
            $section->addText('The parties agree to the terms and conditions outlined in this document.', ['size' => 10.5], ['spaceAfter' => 110]);
        }

        return null;
    }

    private function hasDraftSections($html)
    {
        return stripos((string) $html, 'data-draft-section') !== false;
    }

    private function addStructuredDraftContent($section, $html)
    {
        $signatureBlock = null;
        $sections = $this->parseDraftSections($html);

        foreach ($sections as $draftSection) {
            $type = $draftSection['type'] ?? 'paragraph';

            if ($type === 'signature') {
                $signatureBlock = $draftSection;
                continue;
            }

            if ($type === 'heading') {
                $this->addDraftTitle($section, $draftSection['title'] ?? 'Contract section', 12);
                $this->addDraftNote($section, $draftSection['note'] ?? '');
                continue;
            }

            if ($type === 'checkbox') {
                $this->addDraftTitle($section, $draftSection['title'] ?? 'Acceptance', 11);
                $mark = !empty($draftSection['checked']) ? '[x]' : '[ ]';
                $section->addText(
                    $mark . ' ' . $this->cleanText($draftSection['label'] ?? 'Acceptance checkbox'),
                    ['size' => 10.5, 'bold' => true],
                    ['spaceAfter' => 110, 'indentation' => ['left' => 240]]
                );
                continue;
            }

            if ($type === 'date') {
                $this->addDraftTitle($section, $draftSection['title'] ?? 'Date', 11);
                $table = $section->addTable(['borderSize' => 4, 'borderColor' => 'D9D9D9', 'cellMargin' => 80]);
                $table->addRow();
                $table->addCell(2500, ['bgColor' => 'F7F7F7'])->addText($this->cleanText($draftSection['label'] ?? 'Date'), ['size' => 10, 'bold' => true]);
                $table->addCell(6500)->addText($this->cleanText($draftSection['value'] ?? '________________'), ['size' => 10.5]);
                $section->addTextBreak(1);
                continue;
            }

            $this->addDraftTitle($section, $draftSection['title'] ?? 'Contract section', 11);

            if ($type === 'list') {
                $this->addDraftList($section, $draftSection);
                continue;
            }

            foreach (($draftSection['blocks'] ?? []) as $block) {
                $this->addDraftContentBlock($section, $block);
            }
        }

        return $signatureBlock;
    }

    private function addDraftTitle($section, $title, $size = 11)
    {
        $text = $this->cleanText($title);
        if ($text === '') {
            return;
        }

        $section->addText(
            $text,
            ['size' => $size, 'bold' => true, 'color' => $this->primaryColor],
            ['spaceBefore' => 180, 'spaceAfter' => 90]
        );
    }

    private function addDraftNote($section, $note)
    {
        $text = $this->cleanText($note);
        if ($text === '') {
            return;
        }

        $section->addText($text, ['size' => 10, 'italic' => true, 'color' => '666666'], ['spaceAfter' => 100]);
    }

    private function addDraftContentBlock($section, array $block)
    {
        $text = $this->cleanText($block['text'] ?? '');
        if ($text === '') {
            return;
        }

        if (($block['type'] ?? '') === 'heading') {
            $this->addDraftTitle($section, $text, 10.5);
            return;
        }

        if (($block['type'] ?? '') === 'list') {
            $section->addText(
                $text,
                ['size' => 10.5],
                ['indentation' => ['left' => 360, 'hanging' => 180], 'spaceAfter' => 90]
            );
            return;
        }

        $section->addText($text, ['size' => 10.5], ['spaceAfter' => 110, 'lineHeight' => 1.18]);
    }

    private function addDraftList($section, array $draftSection)
    {
        $style = $draftSection['list_style'] ?? 'bullet';
        $items = $draftSection['items'] ?? [];

        foreach ($items as $index => $item) {
            $text = $this->cleanText($item['text'] ?? '');
            if ($text === '') {
                continue;
            }

            if ($style === 'numbered') {
                $line = ($index + 1) . '. ' . $text;
            } elseif ($style === 'checklist') {
                $line = (!empty($item['checked']) ? '[x] ' : '[ ] ') . $text;
            } else {
                $line = '- ' . $text;
            }

            $section->addText(
                $line,
                ['size' => 10.5],
                ['indentation' => ['left' => 420, 'hanging' => 180], 'spaceAfter' => 80]
            );
        }
    }

    private function parseDraftSections($html)
    {
        if (!class_exists('\DOMDocument')) {
            return $this->parseDraftSectionsFallback($html);
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="draft-root">' . (string) $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//*[@data-draft-section]');
        $sections = [];

        foreach ($nodes as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            $type = $node->getAttribute('data-draft-section') ?: 'paragraph';
            $title = $this->directParagraphText($xpath, $node) ?: $this->cleanText($node->getAttribute('data-label'));
            $section = [
                'type' => $type,
                'title' => $title,
                'blocks' => [],
            ];

            if ($type === 'heading') {
                $section['note'] = $this->nodeText($xpath->query('.//*[@data-section-note]', $node)->item(0));
            } elseif (in_array($type, ['paragraph', 'text'], true)) {
                $contentNode = $xpath->query('.//*[@data-section-content]', $node)->item(0);
                $section['blocks'] = $this->contentBlocksFromNode($xpath, $contentNode ?: $node, $title);
            } elseif ($type === 'list') {
                $section['list_style'] = $node->getAttribute('data-list-style') ?: 'bullet';
                $section['items'] = $this->listItemsFromNode($xpath, $node);
            } elseif ($type === 'checkbox') {
                $section['label'] = $this->cleanText($node->getAttribute('data-label')) ?: $this->stripCheckboxMarker($this->lastParagraphText($xpath, $node));
                $section['checked'] = $node->getAttribute('data-checked') === '1';
            } elseif ($type === 'date') {
                $section['title'] = preg_replace('/:\s*.*$/', '', $section['title'] ?? '');
                $section['label'] = $this->cleanText($node->getAttribute('data-label')) ?: 'Date';
                $section['value'] = $this->cleanText($node->getAttribute('data-value')) ?: '________________';
            } elseif ($type === 'signature') {
                $section['left_signer'] = $this->cleanText($node->getAttribute('data-left-signer')) ?: 'Client Signature';
                $section['right_signer'] = $this->cleanText($node->getAttribute('data-right-signer')) ?: 'ITEC Solutions';
                $section['note'] = $this->nodeText($xpath->query('.//*[@data-section-note]', $node)->item(0));
            }

            $sections[] = $section;
        }

        return $sections;
    }

    private function parseDraftSectionsFallback($html)
    {
        return [[
            'type' => 'paragraph',
            'title' => 'Contract details',
            'blocks' => array_map(function ($paragraph) {
                return ['type' => 'paragraph', 'text' => $paragraph['text']];
            }, $this->extractContractParagraphs($html)),
        ]];
    }

    private function directParagraphText(\DOMXPath $xpath, \DOMElement $node)
    {
        return $this->nodeText($xpath->query('./p[1]', $node)->item(0));
    }

    private function lastParagraphText(\DOMXPath $xpath, \DOMElement $node)
    {
        $items = $xpath->query('./p', $node);
        return $items->length ? $this->nodeText($items->item($items->length - 1)) : '';
    }

    private function contentBlocksFromNode(\DOMXPath $xpath, \DOMNode $node, $titleToSkip = '')
    {
        $blocks = [];
        $elements = $xpath->query('.//*[self::p or self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6 or self::li]', $node);

        foreach ($elements as $element) {
            $text = $this->nodeText($element);
            if ($text === '' || $text === $this->cleanText($titleToSkip)) {
                continue;
            }

            $tag = strtolower($element->nodeName);
            $type = preg_match('/^h[1-6]$/', $tag) ? 'heading' : ($tag === 'li' ? 'list' : 'paragraph');
            if ($this->isContractListLine($text)) {
                $type = 'list';
            }

            $blocks[] = ['type' => $type, 'text' => $text];
        }

        if (!$blocks) {
            $text = $this->nodeText($node);
            if ($text !== '' && $text !== $this->cleanText($titleToSkip)) {
                $blocks[] = ['type' => 'paragraph', 'text' => $text];
            }
        }

        return $blocks;
    }

    private function listItemsFromNode(\DOMXPath $xpath, \DOMElement $node)
    {
        $items = [];
        $nodes = $xpath->query('.//*[@data-list-item]', $node);

        foreach ($nodes as $itemNode) {
            if (!$itemNode instanceof \DOMElement) {
                continue;
            }

            $text = $this->cleanText($itemNode->getAttribute('data-text')) ?: $this->stripListMarker($this->nodeText($itemNode));
            $items[] = [
                'text' => $text,
                'checked' => $itemNode->getAttribute('data-checked') === '1',
            ];
        }

        return $items;
    }

    private function nodeText($node)
    {
        if (!$node) {
            return '';
        }

        return $this->cleanText($node->textContent ?? '');
    }

    private function extractContractParagraphs($html)
    {
        $html = (string) $html;
        $paragraphs = [];

        if (preg_match_all('/<p\b[^>]*>(.*?)<\/p>/is', $html, $matches)) {
            foreach ($matches[1] as $rawParagraph) {
                $text = $this->htmlToCleanText($rawParagraph);
                if ($text === '') {
                    continue;
                }

                $paragraphs[] = [
                    'text' => $text,
                    'is_heading' => $this->isContractBodyHeading($text, $rawParagraph),
                    'is_list' => $this->isContractListLine($text),
                ];
            }
        }

        if (!$paragraphs) {
            $plainText = strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], "\n", $html));
            $plainText = html_entity_decode($plainText, ENT_QUOTES, 'UTF-8');

            foreach (preg_split('/\R+/', $plainText) as $line) {
                $text = trim($line);
                if ($text === '') {
                    continue;
                }

                $paragraphs[] = [
                    'text' => $text,
                    'is_heading' => $this->isContractBodyHeading($text, ''),
                    'is_list' => $this->isContractListLine($text),
                ];
            }
        }

        return $paragraphs;
    }

    private function htmlToCleanText($html)
    {
        $html = preg_replace('/<br\s*\/?>/i', "\n", (string) $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\s*\R\s*/', ' ', $text);

        return trim($text);
    }

    private function cleanText($value)
    {
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text);
        $text = preg_replace('/\s*\R\s*/', ' ', $text);

        return trim($text);
    }

    private function stripListMarker($value)
    {
        return trim(preg_replace('/^(No\.\s*\d+:|\d+\.|[-*]|\[[x ]\])\s*/i', '', $this->cleanText($value)));
    }

    private function stripCheckboxMarker($value)
    {
        return trim(preg_replace('/^\[[x ]\]\s*/i', '', $this->cleanText($value)));
    }

    private function isContractBodyHeading($text, $rawHtml)
    {
        $text = trim((string) $text);
        if ($text === '') {
            return false;
        }

        $hasStrongTitle = preg_match('/<strong\b[^>]*>.*?<\/strong>/is', (string) $rawHtml);
        return (bool) ($hasStrongTitle && preg_match('/^\d+(\.\d+)*\.?\s+\S/', $text));
    }

    private function isContractListLine($text)
    {
        return (bool) preg_match('/^(-|No\.\s*\d+:|\[[x ]\])\s+/i', trim((string) $text));
    }

    private function sanitizeForXml($text) 
    {
        if (empty($text)) return '';
        $text = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $text);
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
    
    private function addSignatureSection($section, $clientName, $signatureBlock = null)
    {
        $section->addTextBreak(2);
        $section->addTitle('Signatures', 2);

        if (!empty($signatureBlock['note'])) {
            $section->addText(
                $this->cleanText($signatureBlock['note']),
                ['size' => 9.5, 'italic' => true, 'color' => '666666'],
                ['spaceAfter' => 120]
            );
        }

        $leftSigner = $this->cleanText($signatureBlock['left_signer'] ?? '') ?: $this->cleanText($clientName) ?: 'Client Representative';
        $rightSigner = $this->cleanText($signatureBlock['right_signer'] ?? '') ?: 'Authorized Signatory';
        
        $table = $section->addTable(['borderSize' => 0]);
        $table->addRow(400);
        $table->addCell(4000, ['borderBottomSize' => 6]);
        $table->addCell(1000);
        $table->addCell(4000, ['borderBottomSize' => 6]);
        
        $table->addRow();
        $table->addCell(4000)->addText("Client Signature", ['size' => 9, 'bold' => true]);
        $table->addCell(1000);
        $table->addCell(4000)->addText("ITEC Solutions", ['size' => 9, 'bold' => true]);
        
        $table->addRow();
        $table->addCell(4000)->addText($this->sanitizeForXml($leftSigner), ['size' => 9]);
        $table->addCell(1000);
        $table->addCell(4000)->addText($this->sanitizeForXml($rightSigner), ['size' => 9]);
        
        $table->addRow();
        $table->addCell(4000)->addText("Date: _______________", ['size' => 9]);
        $table->addCell(1000);
        $table->addCell(4000)->addText("Date: _______________", ['size' => 9]);
    }
    
    private function addFooter($section)
    {
        $footer = $section->addFooter();
        
        // Footer Line 
        $footer->addLine([
            'width' => 480, 
            'height' => 0, 
            'weight' => 1.5, 
            'color' => '777777'
        ]);
        
        $center = ['alignment' => Jc::CENTER];
        
        // Main address line
        $infoText = "{$this->companyInfo['address']} | TIN: {$this->companyInfo['tin']} | Tel: {$this->companyInfo['phone']}";
        $footer->addText($this->sanitizeForXml($infoText), ['size' => 8, 'bold' => true, 'color' => '333333'], $center);
        
        // Services line (Italic/smaller)
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
        $zip->close();
    }

    private function writeZip(string $filePath, array $files): void
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot create DOCX at: ' . $filePath);
        }
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml"  ContentType="application/xml"/>
    <Override PartName="/word/document.xml"
              ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1"
        Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"
        Target="word/document.xml"/>
</Relationships>';
    }

    private function documentRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
</Relationships>';
    }

    private function documentXml(array $lines): string
    {
        $body = '';
        foreach ($lines as $line) {
            $safe = htmlspecialchars((string) $line, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $body .= $safe === ''
                ? '<w:p/>'
                : '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>'
                  . '<w:r><w:rPr><w:sz w:val="22"/></w:rPr>'
                  . '<w:t xml:space="preserve">' . $safe . '</w:t></w:r></w:p>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>' . $body . '
        <w:sectPr>
            <w:pgSz w:w="11906" w:h="16838"/>
            <w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134"/>
        </w:sectPr>
    </w:body>
</w:document>';
    }
}
