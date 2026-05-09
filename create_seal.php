<?php

// Create storage directory if not exists
$sealDir = __DIR__ . '/storage/seals';
if (!is_dir($sealDir)) {
    mkdir($sealDir, 0777, true);
}

// Create a test seal image
$im = imagecreatetruecolor(200, 200);
$white = imagecolorallocate($im, 255, 255, 255);
$black = imagecolorallocate($im, 0, 0, 0);
$red = imagecolorallocate($im, 255, 0, 0);
$blue = imagecolorallocate($im, 0, 0, 255);

// Fill background
imagefilledrectangle($im, 0, 0, 200, 200, $white);

// Draw border
imagerectangle($im, 10, 10, 190, 190, $black);

// Draw circle
imagearc($im, 100, 100, 160, 160, 0, 360, $blue);

// Add text
imagestring($im, 5, 50, 50, "COMPANY", $black);
imagestring($im, 5, 60, 80, "SEAL", $red);
imagestring($im, 3, 35, 120, "AUTHORIZED", $black);
imagestring($im, 3, 55, 140, "SIGNATURE", $black);

// Save the image
$sealPath = $sealDir . '/company_seal.png';
imagepng($im, $sealPath);
imagedestroy($im);

echo "✅ Test seal created successfully!\n";
echo "📍 Location: " . $sealPath . "\n";
echo "📏 Size: " . filesize($sealPath) . " bytes\n";