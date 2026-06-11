<?php
/**
 * scripts/resize_banners.php
 *
 * Batch center-crop and resize images to a target 16:9 size using GD.
 * Usage (from project root):
 *   php scripts/resize_banners.php --source=uploads/banners --dest=uploads/banners_resized --width=1451 --height=816
 *
 */

// --- Simple arg parsing ---
$opts = [];
foreach ($argv as $arg) {
    if (strpos($arg, '--') === 0) {
        $pair = explode('=', substr($arg, 2), 2);
        $opts[$pair[0]] = $pair[1] ?? true;
    }
}

$sourceDir = $opts['source'] ?? __DIR__ . '/../uploads/banners';
$destDir   = $opts['dest']   ?? __DIR__ . '/../uploads/banners_resized';
$targetW   = isset($opts['width']) ? (int)$opts['width'] : 1451;
$targetH   = isset($opts['height']) ? (int)$opts['height'] : 816;

if (!extension_loaded('gd')) {
    fwrite(STDERR, "GD extension is required.\n");
    exit(1);
}

if (!is_dir($sourceDir)) {
    fwrite(STDERR, "Source directory not found: $sourceDir\n");
    exit(1);
}

if (!is_dir($destDir)) {
    mkdir($destDir, 0775, true);
}

$files = array_values(array_filter(scandir($sourceDir), function($f) use ($sourceDir) {
    return is_file($sourceDir . DIRECTORY_SEPARATOR . $f) && preg_match('/\.(jpe?g|png|gif|webp)$/i', $f);
}));

if (count($files) === 0) {
    echo "No images found in $sourceDir\n";
    exit(0);
}

echo "Found " . count($files) . " images. Resizing to {$targetW}x{$targetH} (16:9)...\n";

foreach ($files as $file) {
    $srcPath = $sourceDir . DIRECTORY_SEPARATOR . $file;
    $destPath = $destDir . DIRECTORY_SEPARATOR . $file;

    $info = getimagesize($srcPath);
    if (!$info) { echo "Skipping (not an image): $file\n"; continue; }

    [$origW, $origH, $type] = [$info[0], $info[1], $info[2]];

    // Determine crop box to match target aspect ratio
    $targetRatio = $targetW / $targetH;
    $origRatio = $origW / $origH;

    if ($origRatio > $targetRatio) {
        // original is wider -> crop width
        $newH = $origH;
        $newW = (int) round($origH * $targetRatio);
        $srcX = (int) round(($origW - $newW) / 2);
        $srcY = 0;
    } else {
        // original is taller -> crop height
        $newW = $origW;
        $newH = (int) round($origW / $targetRatio);
        $srcX = 0;
        $srcY = (int) round(($origH - $newH) / 2);
    }

    // Create source image depending on type
    switch ($type) {
        case IMAGETYPE_JPEG: $srcImg = imagecreatefromjpeg($srcPath); break;
        case IMAGETYPE_PNG:  $srcImg = imagecreatefrompng($srcPath); break;
        case IMAGETYPE_GIF:  $srcImg = imagecreatefromgif($srcPath); break;
        case IMAGETYPE_WEBP: $srcImg = imagecreatefromwebp($srcPath); break;
        default:
            echo "Unsupported format for $file — skipping\n";
            continue 2;
    }

    $dstImg = imagecreatetruecolor($targetW, $targetH);
    // preserve PNG transparency
    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
        imagecolortransparent($dstImg, imagecolorallocatealpha($dstImg, 0, 0, 0, 127));
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
    }

    imagecopyresampled($dstImg, $srcImg, 0, 0, $srcX, $srcY, $targetW, $targetH, $newW, $newH);

    // Save
    $saved = false;
    switch ($type) {
        case IMAGETYPE_JPEG: $saved = imagejpeg($dstImg, $destPath, 85); break;
        case IMAGETYPE_PNG:  $saved = imagepng($dstImg, $destPath, 6); break;
        case IMAGETYPE_GIF:  $saved = imagegif($dstImg, $destPath); break;
        case IMAGETYPE_WEBP: $saved = imagewebp($dstImg, $destPath, 85); break;
    }

    imagedestroy($srcImg);
    imagedestroy($dstImg);

    if ($saved) echo "Saved: $destPath\n";
    else echo "Failed to save: $destPath\n";
}

echo "Done. Resized images are in: $destDir\n";

?>
