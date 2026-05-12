<?php

namespace Services;

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

    // =========================================================================
    // Public API
    // =========================================================================

    public function generateContract(int $contractId, array $data): string
    {
        $filePath = $this->storageDir . DIRECTORY_SEPARATOR . "contract_{$contractId}.docx";

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

    // =========================================================================
    // Utilities
    // =========================================================================

    private function x(?string $text): string
    {
        if ($text === null || $text === '') return '';
        $text = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $text);
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }

    private function validateDocx(string $filePath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException('Generated DOCX failed validation: ' . $filePath);
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