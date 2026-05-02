<?php
$sizes = [192, 512];
$dir = __DIR__ . '/assets/icons';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}
foreach ($sizes as $size) {
    $img = imagecreatetruecolor($size, $size);

    $bg = imagecolorallocate($img, 49, 46, 129);
    imagefill($img, 0, 0, $bg);

    $crossColor = imagecolorallocate($img, 255, 255, 255);
    $w = $size;
    $cx = $w / 2;
    $cy = $w / 2;
    $thick = $w / 5;
    $len = $w / 1.5;

    imagefilledrectangle($img, $cx - $thick/2, $cy - $len/2, $cx + $thick/2, $cy + $len/2, $crossColor);

    imagefilledrectangle($img, $cx - $len/2, $cy - $thick/2, $cx + $len/2, $cy + $thick/2, $crossColor);
    imagepng($img, $dir . "/icon-{$size}x{$size}.png");
    imagedestroy($img);
    echo "Generated icon-{$size}x{$size}.png\n";
}
?>
