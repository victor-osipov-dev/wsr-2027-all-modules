<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Сервис обработки изображений рецептов.
 *
 * Для каждого изображения:
 *  - сохраняется оригинал в storage/app/public/recipes/{id}/original_*.ext;
 *  - генерируется миниатюра 350×200 webp (с сохранением пропорций, как CSS contain);
 *  - на миниатюру наносится водяной знак — название рецепта в правом нижнем углу.
 *
 * Используется нативный PHP GD (imagecreatefrom*, imagewebp, imagettftext) —
 * сторонние библиотеки не требуются.
 */
class ImageService
{
    /** Ширина миниатюры (px). */
    public const THUMB_WIDTH = 350;

    /** Высота миниатюры (px). */
    public const THUMB_HEIGHT = 200;

    /** Формат миниатюры. */
    public const THUMB_FORMAT = 'webp';

    /** Отступ водяного знака от края (px). */
    private const WATERMARK_MARGIN = 8;

    /** Допустимые расширения оригиналов. */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /**
     * Сохраняет оригинал изображения и создаёт миниатюру с водяным знаком.
     */
    public function storeRecipeImage(Recipe $recipe, UploadedFile $file, string $watermarkText, bool $isDefault = false): RecipeImage
    {
        $directory = $this->recipeDirectory($recipe);
        $extension = $this->normalizeExtension($file);
        $token = Str::lower(Str::random(12));
        $originalName = 'original_'.$token.'.'.$extension;

        // 1. Сохраняем оригинал (нужен для повторной генерации миниатюр).
        Storage::disk('public')->putFileAs($directory, $file, $originalName);
        $originalRelative = $directory.'/'.$originalName;

        // 2. Генерируем миниатюру с водяным знаком.
        $thumbnailRelative = $this->generateThumbnail($originalRelative, $watermarkText);

        // 3. Регистрируем изображение рецепта.
        return $recipe->images()->create([
            'path' => $originalRelative,
            'thumbnail' => $thumbnailRelative,
            'is_default' => $isDefault,
        ]);
    }

    /**
     * Создаёт миниатюру 350×200 webp из оригинала и возвращает её относительный путь.
     *
     * @throws RuntimeException если исходное изображение отсутствует или формат не поддержан.
     */
    public function generateThumbnail(string $originalRelativePath, string $watermarkText): string
    {
        $disk = Storage::disk('public');
        $sourceAbsolute = $disk->path($originalRelativePath);

        if (! is_file($sourceAbsolute)) {
            throw new RuntimeException("Исходное изображение не найдено: {$originalRelativePath}");
        }

        $source = $this->createGdImage($sourceAbsolute);
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        // Холст миниатюры с белым фоном (letterbox).
        $canvas = imagecreatetruecolor(self::THUMB_WIDTH, self::THUMB_HEIGHT);
        $background = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, self::THUMB_WIDTH, self::THUMB_HEIGHT, $background);

        // Вписываем изображение целиком (аналог object-fit: contain).
        $scale = min(
            self::THUMB_WIDTH / $sourceWidth,
            self::THUMB_HEIGHT / $sourceHeight,
        );
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $offsetX = (int) floor((self::THUMB_WIDTH - $targetWidth) / 2);
        $offsetY = (int) floor((self::THUMB_HEIGHT - $targetHeight) / 2);

        imagecopyresampled(
            $canvas,
            $source,
            $offsetX,
            $offsetY,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight,
        );
        imagedestroy($source);

        // Водяной знак — название рецепта в правом нижнем углу.
        $this->drawWatermark($canvas, $watermarkText);

        // Сохраняем миниатюру (webp, при отсутствии поддержки — png).
        $token = Str::lower(Str::random(12));
        $directory = dirname($originalRelativePath);
        $useWebp = function_exists('imagewebp');

        if ($useWebp) {
            $thumbnailRelative = $directory.'/thumb_350x200_'.$token.'.webp';
            $this->ensureDirectory($disk->path($thumbnailRelative));
            imagewebp($canvas, $disk->path($thumbnailRelative), 85);
        } else {
            $thumbnailRelative = $directory.'/thumb_350x200_'.$token.'.png';
            $this->ensureDirectory($disk->path($thumbnailRelative));
            imagepng($canvas, $disk->path($thumbnailRelative), 7);
        }

        imagedestroy($canvas);

        return $thumbnailRelative;
    }

    /**
     * Перегенерирует миниатюры всех изображений рецепта (например при смене названия).
     */
    public function regenerateThumbnails(Recipe $recipe): void
    {
        $recipe->loadMissing('images');

        foreach ($recipe->images as $image) {
            $this->regenerateThumbnail($image, $recipe->title);
        }
    }

    /**
     * Перегенерирует одну миниатюру, обновляя водяной знак.
     */
    public function regenerateThumbnail(RecipeImage $image, string $watermarkText): void
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($image->path)) {
            return;
        }

        // Удаляем старую миниатюру, чтобы не копить мусор.
        if ($image->thumbnail && $disk->exists($image->thumbnail)) {
            $disk->delete($image->thumbnail);
        }

        $newThumbnail = $this->generateThumbnail($image->path, $watermarkText);
        $image->forceFill(['thumbnail' => $newThumbnail])->save();
    }

    /**
     * Удаляет файлы (оригинал и миниатюру) одного изображения.
     */
    public function deleteImageFiles(RecipeImage $image): void
    {
        $disk = Storage::disk('public');

        foreach ([$image->path, $image->thumbnail] as $relative) {
            if ($relative && $disk->exists($relative)) {
                $disk->delete($relative);
            }
        }
    }

    /**
     * Полностью удаляет каталог с изображениями рецепта.
     */
    public function deleteRecipeDirectory(Recipe $recipe): void
    {
        Storage::disk('public')->deleteDirectory($this->recipeDirectory($recipe));
    }

    /**
     * Каталог рецепта на публичном диске.
     */
    private function recipeDirectory(Recipe $recipe): string
    {
        return 'recipes/'.$recipe->getKey();
    }

    /**
     * Определяет безопасное расширение загруженного файла.
     */
    private function normalizeExtension(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: (string) $file->extension());

        return in_array($extension, self::ALLOWED_EXTENSIONS, true) ? $extension : 'jpg';
    }

    /**
     * Создаёт ресурс GD из файла по его расширению.
     */
    private function createGdImage(string $absolutePath): \GdImage
    {
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        $image = match ($extension) {
            'jpg', 'jpeg' => imagecreatefromjpeg($absolutePath),
            'png' => imagecreatefrompng($absolutePath),
            'webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($absolutePath) : false,
            'gif' => imagecreatefromgif($absolutePath),
            default => false,
        };

        if ($image === false) {
            throw new RuntimeException("Не удалось прочитать изображение: {$absolutePath}");
        }

        // Сохраняем прозрачность PNG.
        imagealphablending($image, true);
        imagesavealpha($image, true);

        return $image;
    }

    /**
     * Наносит водяной знак (название рецепта) в правом нижнем углу.
     */
    private function drawWatermark(\GdImage $canvas, string $text): void
    {
        $text = trim($text);

        if ($text === '') {
            return;
        }

        $font = $this->resolveFont();

        if ($font !== null) {
            $this->drawTrueTypeWatermark($canvas, $text, $font);

            return;
        }

        $this->drawBitmapWatermark($canvas, $text);
    }

    /**
     * Водяной знак с использованием TTF-шрифта.
     */
    private function drawTrueTypeWatermark(\GdImage $canvas, string $text, string $font): void
    {
        $canvasWidth = imagesx($canvas);
        $canvasHeight = imagesy($canvas);

        // Подбираем размер шрифта так, чтобы надпись помещалась в миниатюру.
        $fontSize = 16;
        $box = imagettfbbox($fontSize, 0, $font, $text);
        $textWidth = abs($box[2] - $box[0]);
        $textHeight = abs($box[7] - $box[1]);

        while ($textWidth > $canvasWidth - 2 * self::WATERMARK_MARGIN && $fontSize > 8) {
            $fontSize--;
            $box = imagettfbbox($fontSize, 0, $font, $text);
            $textWidth = abs($box[2] - $box[0]);
            $textHeight = abs($box[7] - $box[1]);
        }

        $x = $canvasWidth - $textWidth - self::WATERMARK_MARGIN;
        $y = $canvasHeight - self::WATERMARK_MARGIN;

        // Полупрозрачная подложка для читаемости.
        $overlay = imagecolorallocatealpha($canvas, 0, 0, 0, 60);
        imagefilledrectangle(
            $canvas,
            max(0, $x - 4),
            max(0, $y - $textHeight - 4),
            $canvasWidth,
            $canvasHeight,
            $overlay,
        );

        $color = imagecolorallocate($canvas, 255, 255, 255);
        imagettftext($canvas, $fontSize, 0, $x, $y, $color, $font, $text);
    }

    /**
     * Резервный водяной знак встроенным растровым шрифтом GD (масштабируется).
     */
    private function drawBitmapWatermark(\GdImage $canvas, string $text): void
    {
        $canvasWidth = imagesx($canvas);
        $canvasHeight = imagesy($canvas);

        $font = 5;
        $charWidth = imagefontwidth($font);
        $charHeight = imagefontheight($font);

        // Рисуем надпись на маленьком изображении, затем масштабируем.
        $smallWidth = max(1, $charWidth * strlen($text));
        $smallHeight = $charHeight;

        $small = imagecreatetruecolor($smallWidth, $smallHeight);
        $black = imagecolorallocate($small, 0, 0, 0);
        imagefilledrectangle($small, 0, 0, $smallWidth, $smallHeight, $black);
        $white = imagecolorallocate($small, 255, 255, 255);
        imagestring($small, $font, 0, 0, $text, $white);

        $maxWidth = $canvasWidth - 2 * self::WATERMARK_MARGIN;
        $scale = max(1, (int) floor($maxWidth / $smallWidth));
        $scale = min($scale, 4);

        if ($scale > 1) {
            $big = imagecreatetruecolor($smallWidth * $scale, $smallHeight * $scale);
            imagecopyresized($big, $small, 0, 0, 0, 0, $smallWidth * $scale, $smallHeight * $scale, $smallWidth, $smallHeight);
            imagedestroy($small);
            $small = $big;
            $smallWidth *= $scale;
            $smallHeight *= $scale;
        }

        $destX = max(0, $canvasWidth - $smallWidth - self::WATERMARK_MARGIN);
        $destY = max(0, $canvasHeight - $smallHeight - self::WATERMARK_MARGIN);

        // Непрозрачная подложка + текст с частичным наложением.
        imagecopymerge($canvas, $small, $destX, $destY, 0, 0, $smallWidth, $smallHeight, 65);
        imagedestroy($small);
    }

    /**
     * Ищет доступный TTF-шрифт для водяного знака.
     */
    private function resolveFont(): ?string
    {
        $candidates = array_filter([
            (string) config('watermark.font'),
            resource_path('fonts/verdana.ttf'),
            resource_path('fonts/arial.ttf'),
            resource_path('fonts/DejaVuSans.ttf'),
            resource_path('fonts/Roboto-Regular.ttf'),
            'C:/Windows/Fonts/arial.ttf',
            'C:/Windows/Fonts/segoeui.ttf',
            'C:/Windows/Fonts/verdana.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            '/usr/share/fonts/truetype/msttcorefonts/Arial.ttf',
            '/System/Library/Fonts/Supplemental/Arial.ttf',
        ]);

        foreach ($candidates as $path) {
            if (is_string($path) && $path !== '' && is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        Log::debug('ImageService: TTF-шрифт не найден, используется встроенный шрифт GD.');

        return null;
    }

    /**
     * Создаёт каталог для файла, если его нет.
     */
    private function ensureDirectory(string $filePath): void
    {
        $directory = dirname($filePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
    }
}
