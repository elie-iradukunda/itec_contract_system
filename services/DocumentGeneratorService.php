<?php

namespace Services;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use ZipArchive;

class DocumentGeneratorService
{
    private $storageDir;
    private $companyInfo;
    private $primaryColor = '80181A'; 
    
    public function __construct()
    {
        $this->storageDir = '/../storage/contracts/';
        $this->ensureDirectoryExists();
        
        $this->companyInfo = [
            'name'     => 'ITEC Solutions',
            'address'  => 'Head office in Rwanda – Nyarugenge District, Kigali, Rwanda',
            'tin'      => '105253130',
            'phone'    => '0788276076',
            'tagline'  => 'BE SMART, CHOOSE SMART',
            'services' => 'Web hosting & design, software development, web & mobile Application development, Graphic design, IT Consultancy, IT supplying & support, office support'
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
        $filePath = $this->storageDir . $fileName;

        if (!class_exists('\ZipArchive')) {
            return $this->generateSimpleContract($filePath, $contractId, $data);
        }
        
        // Remove old file if exists
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        
        // Define Title Styles
        $phpWord->addTitleStyle(1, ['size' => 16, 'bold' => true, 'color' => $this->primaryColor], ['alignment' => Jc::CENTER]);
        $phpWord->addTitleStyle(2, ['size' => 12, 'bold' => true, 'color' => $this->primaryColor], ['spaceBefore' => 240]);

        $section = $phpWord->addSection([
            'marginTop' => 1200, 
            'marginBottom' => 1200, 
            'marginLeft' => 1000, 
            'marginRight' => 1000,
        ]);
        
        // --- Header Section ---
        $header = $section->addHeader();
        $logoPath = __DIR__ . '/../public/assets/logo.png'; 
        if (file_exists($logoPath)) {
            $header->addImage($logoPath, ['width' => 160, 'alignment' => Jc::CENTER]);
        }
        $header->addText($this->companyInfo['tagline'], ['size' => 9, 'bold' => true], ['alignment' => Jc::CENTER]);
        
        // Bold black line in header
        $header->addLine([
            'width' => 480, 
            'height' => 0, 
            'weight' => 2.5, 
            'color' => '000000',
            'spaceAfter' => 200
        ]);

        // --- Document Body ---
        $section->addTitle($this->sanitizeForXml(strtoupper($data['title'])), 1);
        $section->addTextBreak(1);
        
        $this->addMetadataTable($section, $contractId, $data);
        $section->addTextBreak(1);
        
        $this->addSanitizedContent($section, $data);
        $this->addSignatureSection($section, $data['client_name']);
        
        // --- Footer Section ---
        $this->addFooter($section);
        
        // Save the document
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($filePath);
        
        // Validate generated file using ZipArchive
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \Exception('Generated DOCX is invalid.');
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
            'Contract Ref: #' . $contractId,
            'Client: ' . ($data['client_name'] ?? ''),
            'Client Email: ' . ($data['client_email'] ?? ''),
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
        if (!empty($data['content'])) {
            $section->addTitle('Agreement Details', 2);
            
            $plainText = strip_tags(str_replace(['</p>', '<br>', '<br/>'], "\n", $data['content']));
            $plainText = html_entity_decode($plainText, ENT_QUOTES, 'UTF-8');
            
            $paragraphs = explode("\n", $plainText);
            foreach ($paragraphs as $p) {
                $cleanLine = $this->sanitizeForXml(trim($p));
                if ($cleanLine !== '') {
                    $section->addText($cleanLine, ['size' => 10.5]);
                    $section->addTextBreak(1);
                }
            }
        } else {
            // Default content if none provided
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
        $table->addCell(4000)->addText("Client Signature", ['size' => 9, 'bold' => true]);
        $table->addCell(1000);
        $table->addCell(4000)->addText("ITEC Solutions", ['size' => 9, 'bold' => true]);
        
        $table->addRow();
        $table->addCell(4000)->addText($this->sanitizeForXml($clientName), ['size' => 9]);
        $table->addCell(1000);
        $table->addCell(4000)->addText("Authorized Signatory", ['size' => 9]);
        
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
        $filePath = $this->storageDir . "contract_{$contractId}.docx";
        
        if (!file_exists($filePath)) {
            return false;
        }
        
        // Load existing document
        $phpWord = IOFactory::load($filePath);
        
        // Get the first section
        $sections = $phpWord->getSections();
        if (empty($sections)) {
            return false;
        }
        
        // For simplicity, regenerate the document
        $data = [
            'title' => 'Updated Contract',
            'client_name' => 'Client Name',
            'client_email' => 'client@email.com',
            'content' => $content
        ];
        
        return $this->generateContract($contractId, $data);
    }
}
