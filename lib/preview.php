<?php
// Make sure the Imagick class is available
if (!class_exists('Imagick')) {
    throw new Exception('Imagick extension is not installed or enabled.');
}
function generate_thumbnail(string $filepath, string $outpath, int $w = 300, int $h = 200): bool {
    if (!extension_loaded('imagick')) return false;
    try {
        $img = new \Imagick($filepath);
        $img->setImageColorspace(\Imagick::COLORSPACE_RGB);
        $img->setImageFormat('jpeg');
        $img->thumbnailImage($w, $h, true);
        $img->writeImage($outpath);
        $img->clear(); $img->destroy();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>