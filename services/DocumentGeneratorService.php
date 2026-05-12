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
        $this->storageDir = dirname(__DIR__) . '/storage/contracts/';
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
        $folder = $this->storageDir . (int) $contractId;
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $filePath = $folder . '/contract.docx';

        if (!class_exists('\ZipArchive') || !class_exists('\PhpOffice\PhpWord\PhpWord')) {
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
        $logoPath = __DIR__ . '/../public/assets/logo.png';
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
            IOFactory::createWriter($phpWord, 'Word2007')->save($filePath);
        } catch (\Throwable $error) {
            return $this->generateSimpleContract($filePath, $contractId, $data);
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return $this->generateSimpleContract($filePath, $contractId, $data);
        }
        $zip->close();

        return $filePath;
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
        $section->addTitle('Agreement Details', 2);
        $plainText = strip_tags(str_replace(['</p>', '<br>', '<br/>'], "\n", $data['content'] ?? ''));
        $plainText = html_entity_decode($plainText, ENT_QUOTES, 'UTF-8');

        if (trim($plainText) === '') {
            $plainText = "This agreement is made between the parties as described above.\nThe parties agree to the terms and conditions outlined in this document.";
        }

        foreach (preg_split('/\R/', $plainText) as $line) {
            $cleanLine = $this->sanitizeForXml(trim($line));
            if ($cleanLine !== '') {
                $section->addText($cleanLine, ['size' => 10.5]);
                $section->addTextBreak(1);
            }
        }
    }

    private function addDescriptionBlock($section, $data)
    {
        $description = trim((string) ($data['description'] ?? ''));
        if ($description === '') {
            return;
        }

        $section->addText($this->sanitizeForXml($description), ['size' => 10.5, 'italic' => true, 'color' => '555555']);
        $section->addTextBreak(1);
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

    private function sanitizeForXml($text)
    {
        $text = (string) $text;
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $text);
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function documentXml(array $lines)
    {
        $paragraphs = array_map(function ($line) {
            return '<w:p><w:r><w:t xml:space="preserve">' . $this->escapeXml($line) . '</w:t></w:r></w:p>';
        }, $lines);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:body>' . implode('', $paragraphs)
            . '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440"/></w:sectPr>'
            . '</w:body></w:document>';
    }

    private function contentTypesXml()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '</Types>';
    }

    private function rootRelsXml()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>';
    }

    private function documentRelsXml()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>';
    }

    private function escapeXml($text)
    {
        return htmlspecialchars((string) $text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function writeZip($path, array $files)
    {
        $localData = '';
        $centralData = '';
        [$dosTime, $dosDate] = $this->dosTimestamp();

        foreach ($files as $name => $contents) {
            $offset = strlen($localData);
            $size = strlen($contents);
            $crc = hexdec(hash('crc32b', $contents));
            $nameLength = strlen($name);

            $localData .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0);
            $localData .= $name . $contents;

            $centralData .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0, 0, 0, 0, 0, $offset);
            $centralData .= $name;
        }

        $centralOffset = strlen($localData);
        $centralSize = strlen($centralData);
        $count = count($files);
        $eocd = pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, $centralSize, $centralOffset, 0);

        if (file_put_contents($path, $localData . $centralData . $eocd) === false) {
            throw new \Exception('The DOCX file could not be created');
        }
    }

    private function dosTimestamp()
    {
        $now = getdate();
        $time = ($now['hours'] << 11) | ($now['minutes'] << 5) | (int) ($now['seconds'] / 2);
        $date = (($now['year'] - 1980) << 9) | ($now['mon'] << 5) | $now['mday'];

        return [$time, $date];
    }
}
