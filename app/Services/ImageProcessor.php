<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class ImageProcessor
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = ImageManager::usingDriver(Driver::class);
    }

    /**
     * Store an uploaded image, resizing to fit within maxWidth × maxHeight,
     * preserving aspect ratio, and re-encoding to webp for size savings.
     * Returns the storage path.
     */
    public function storeResized(
        UploadedFile $file,
        string $folder,
        int $maxWidth = 1600,
        int $maxHeight = 1200,
        int $quality = 82
    ): string {
        $image = $this->manager->decode($file->getRealPath());

        // Only downscale; never upscale.
        if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
            $image->scaleDown(width: $maxWidth, height: $maxHeight);
        }

        $filename = $folder . '/' . bin2hex(random_bytes(8)) . '.webp';
        $encoded = $image->encode(new WebpEncoder(quality: $quality));

        Storage::disk('public')->put($filename, (string) $encoded);
        return $filename;
    }
}
