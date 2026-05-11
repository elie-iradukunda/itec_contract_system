<?php

namespace Services;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

class DocumentGeneratorService
{
    private $storageDir;
    private $companyInfo;
    private $primaryColor = '80181A'; 
    
    public function __construct()
    {
        $this->storageDir = __DIR__ . '/../storage/contracts/';
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
        
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        
        // Define Title Styles
        $phpWord->addTitleStyle(1, ['size' => 16, 'bold' => true, 'color' => $this->primaryColor], ['alignment' => Jc::CENTER]);
        $phpWord->addTitleStyle(2, ['size' => 12, 'bold' => true, 'color' => $this->primaryColor], ['spaceBefore' => 240]);

        $section = $phpWord->addSection([
            'marginTop' => 1200, 'marginBottom' => 1200, 'marginLeft' => 1000, 'marginRight' => 1000,
        ]);
        
        // --- Header Section ---
        $header = $section->addHeader();
        $logoPath = __DIR__ . '/../public/assets/logo.png'; 
        if (file_exists($logoPath)) {
            $header->addImage($logoPath, ['width' => 160, 'alignment' => Jc::CENTER]);
        }
        $header->addText($this->companyInfo['tagline'], ['size' => 9, 'bold' => true], ['alignment' => Jc::CENTER]);
        
        // Bold black line in header (as seen in image)
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
        
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($filePath);
        
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
                }
            }
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
        $table = $section->addTable();
        $table->addRow(400);
        $table->addCell(4000, ['borderBottomSize' => 6]);
        $table->addCell(1000);
        $table->addCell(4000, ['borderBottomSize' => 6]);
        
        $table->addRow();
        $table->addCell(4000)->addText("Client Signature", ['size' => 9, 'bold' => true]);
        $table->addCell(1000);
        $table->addCell(4000)->addText("ITEC Solutions", ['size' => 9, 'bold' => true]);
    }
    
    private function addFooter($section)
    {
        $footer = $section->addFooter();
        
        // Footer Line (Gray as seen in footer image)
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
}