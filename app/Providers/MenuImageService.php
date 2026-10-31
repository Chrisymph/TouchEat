<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\MenuItemImage;
use Illuminate\Http\UploadedFile;

class MenuImageService
{
    // Taille maximale (en pixels) du plus grand côté de l'image
    const MAX_SIDE = 700;

    /**
     * Enregistre (ou remplace) l'image d'un article.
     */
    public static function save(MenuItem $item, UploadedFile $file): void
    {
        [$mime, $data] = self::process($file);

        MenuItemImage::updateOrCreate(
            ['menu_item_id' => $item->id],
            ['mime' => $mime, 'data' => $data]
        );

        $item->image_version = (int) (microtime(true) * 1000);
        $item->save();
    }

    /**
     * Supprime l'image d'un article.
     */
    public static function remove(MenuItem $item): void
    {
        MenuItemImage::where('menu_item_id', $item->id)->delete();

        $item->image_version = null;
        $item->save();
    }

    /**
     * Redimensionne et compresse l'image (WebP ou JPEG) pour qu'elle
     * reste légère (environ 30 à 80 Ko) et s'affiche vite.
     *
     * @return array [mime, base64]
     */
    private static function process(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $raw = file_get_contents($path);
        $originalMime = $file->getMimeType() ?: 'image/jpeg';

        // Sans l'extension GD, on garde le fichier tel quel
        if (!function_exists('imagecreatefromstring')) {
            return [$originalMime, base64_encode($raw)];
        }

        $src = @imagecreatefromstring($raw);
        if (!$src) {
            return [$originalMime, base64_encode($raw)];
        }

        // Corriger l'orientation des photos prises avec un téléphone
        if ($originalMime === 'image/jpeg' && function_exists('exif_read_data') && function_exists('imagerotate')) {
            $exif = @exif_read_data($path);
            $orientation = $exif['Orientation'] ?? 1;
            $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;

            if ($angle !== 0) {
                $rotated = imagerotate($src, $angle, 0);
                if ($rotated) {
                    imagedestroy($src);
                    $src = $rotated;
                }
            }
        }

        $w = imagesx($src);
        $h = imagesy($src);

        $ratio = min(1, self::MAX_SIDE / max($w, $h));
        $newW = max(1, (int) round($w * $ratio));
        $newH = max(1, (int) round($h * $ratio));

        // Fond blanc (évite le fond noir des PNG transparents)
        $canvas = imagecreatetruecolor($newW, $newH);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($src);

        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($canvas, null, 80);
            $mime = 'image/webp';
        } else {
            imagejpeg($canvas, null, 82);
            $mime = 'image/jpeg';
        }
        $binary = ob_get_clean();
        imagedestroy($canvas);

        return [$mime, base64_encode($binary)];
    }
}