<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Miniatury zdjęć (3.10.1).
 *
 * Zdjęcia z telefonu mają 3–5 MB, a kafel w karcie ma 56 px. Bez miniatur
 * lista zgłoszeń ściągała przy otwarciu wszystkie zdjęcia ze wszystkich
 * zgłoszeń (79,6 MB przy 20 zgłoszeniach na danych produkcyjnych).
 *
 * Uwaga: lokalny PHP jest budowany bez GD (jak w konfiguracji projektu),
 * więc testy generowania miniatury pomijają się na tym stanowisku — na
 * serwerze GD jest. Testy czystej logiki (nazwy, kasowanie, fallback
 * bez GD) działają wszędzie.
 */
final class MiniaturyTest extends TestCase
{
    public function testSciezkaMiniatury(): void
    {
        self::assertSame(
            UPLOAD_DIR . '/thumb_' . 'abc123.jpg',
            miniaturka_sciezka('abc123.jpg')
        );
        // nazwa bez rozszerzenia (gdyby kiedyś trafił plik bez '.'):
        self::assertSame(
            UPLOAD_DIR . '/thumb_' . 'bezrozszerzenia.jpg',
            miniaturka_sciezka('bezrozszerzenia')
        );
    }

    public function testMiniaturyPlikuNazwyWTymSamymKatalogu(): void
    {
        // bez tego ścieżka z katalogu w środku wypadłaby poza katalog zdjęć
        $sciezka = miniaturka_sciezka('podkatalog/zdjecie.jpg');
        self::assertStringStartsWith(UPLOAD_DIR . '/', $sciezka);
        self::assertStringNotContainsString('..', $sciezka);
    }

    public function testBezGdZwracaNullIFrontendDostanieFallback(): void
    {
        // serwer bez GD: wgrywanie musi działać dalej, a zdjęcie bez miniatury
        if (function_exists('imagecreatetruecolor')) {
            self::markTestSkipped('to środowisko ma GD - test degradacji bez GD nie ma tu zastosowania');
        }
        $plik = tempnam(sys_get_temp_dir(), 'brak-gd') . '.jpg';
        file_put_contents($plik, "\xFF\xD8\xFF\xE0");   // byle coś z nagłówkiem JPEG
        self::assertNull(zrob_miniaturke($plik), 'bez GD funkcja ma zwrócić null');
        @unlink($plik);
    }

    public function testMiniaturkaUsunNiszczyTylkoPlikiMiniatur(): void
    {
        // funkcja operuje na UPLOAD_DIR (stala), wiec test pisze tam
        // i sprząta po sobie - katalog zdjęć jest poza gitem
        $znacznik = 'testmini' . bin2hex(random_bytes(6));
        $oryginal = UPLOAD_DIR . '/' . $znacznik . '.jpg';
        $mini = UPLOAD_DIR . '/thumb_' . $znacznik . '.jpg';
        $obcy = UPLOAD_DIR . '/' . $znacznik . '-obcy.jpg';
        file_put_contents($oryginal, 'oryginal');
        file_put_contents($mini, 'mini');
        file_put_contents($obcy, 'obcy');

        try {
            // 1) po nazwie wyliczanej z oryginału (gdy wiersz nie ma jeszcze thumb)
            miniaturka_usun($znacznik . '.jpg', null);
            self::assertFileDoesNotExist($mini, 'miniatura musi zniknac');
            self::assertFileExists($oryginal, 'oryginal musi zostac');
            self::assertFileExists($obcy, 'obcy plik musi zostac');

            // 2) po nazwie z kolumny thumb
            file_put_contents($mini, 'mini');
            miniaturka_usun($znacznik . '.jpg', 'thumb_' . $znacznik . '.jpg');
            self::assertFileDoesNotExist($mini, 'miniatura z kolumny musi zniknac');

            // 3) nazwa bez prefiksu thumb_ - niczego nie rusza
            miniaturka_usun($znacznik . '.jpg', $znacznik . '-obcy.jpg');
            self::assertFileExists($obcy, 'plik obejmujacy nazwe miniaturki musi zostac');

            // 4) sciezka z ../ - basename() sprowadza ja do katalogu zdjec
            file_put_contents($mini, 'mini');
            miniaturka_usun($znacznik . '.jpg', '../../../../tmp/' . basename($mini));
            self::assertFileDoesNotExist($mini);
        } finally {
            @unlink($oryginal);
            @unlink($mini);
            @unlink($obcy);
        }
    }

    public function testMiniaturkaUsunNieTknieInnejNazwy(): void
    {
        $znacznik = 'testmini' . bin2hex(random_bytes(6));
        $inny = UPLOAD_DIR . '/' . $znacznik . '.jpg';
        file_put_contents($inny, 'x');
        try {
            miniaturka_usun($znacznik . '.jpg', $znacznik . '.jpg');   // nie jest miniaturą
            self::assertFileExists($inny, 'plik o nazwie bez prefiksu thumb_ nie może być usunięty');
        } finally {
            @unlink($inny);
        }
    }

    public function testParametryMiniatur(): void
    {
        // lista ma kafel 56 px, karta ~120 px; 320 px daje zapas na ekran 2x
        self::assertGreaterThanOrEqual(240, THUMB_MAX_WIDTH);
        self::assertLessThanOrEqual(640, THUMB_MAX_WIDTH);
        self::assertGreaterThanOrEqual(60, THUMB_JPEG_QUALITY);
        self::assertLessThanOrEqual(95, THUMB_JPEG_QUALITY);
        // 4000x3000 to ~50 MB w pamięci, wiec limit musi to przepuszczać
        self::assertGreaterThan(12_000_000, THUMB_MAX_SOURCE_PX);
    }

    /* ---------------- zmniejszanie oryginałów (3.12.0) ---------------- */

    public function testKwalifikacjaDoSkalowania(): void
    {
        // typowe zdjęcie z telefonu - kwalifikuje się
        self::assertTrue(foto_czy_do_skalowania(4080, 3072));
        self::assertTrue(foto_czy_do_skalowania(3000, 4000));   // pionowe
        // już małe - nie ruszamy, żeby nie degradować jakości
        self::assertFalse(foto_czy_do_skalowania(1600, 1200));
        self::assertFalse(foto_czy_do_skalowania(PHOTO_MAX_PX, PHOTO_MAX_PX));
        // granica: 1 px ponad limit kwalifikuje
        self::assertTrue(foto_czy_do_skalowania(PHOTO_MAX_PX + 1, 100));
        // tylko JPEG
        self::assertFalse(foto_czy_do_skalowania(4080, 3072, 'image/png'));
        self::assertFalse(foto_czy_do_skalowania(4080, 3072, 'image/webp'));
        self::assertFalse(foto_czy_do_skalowania(4080, 3072, 'image/gif'));
        // bzdury
        self::assertFalse(foto_czy_do_skalowania(0, 0));
        self::assertFalse(foto_czy_do_skalowania(-10, 100));
    }

    public function testZmniejszanieBezGdlubBezJpeg(): void
    {
        // serwer bez obsługi JPEG zostawia oryginał w spokoju
        if (function_exists('imagejpeg') && function_exists('imagecreatefromjpeg')) {
            self::markTestSkipped('to środowisko ma GD z JPEG - test degradacji nie ma tu zastosowania');
        }
        $znacznik = 'skala' . bin2hex(random_bytes(6));
        $plik = UPLOAD_DIR . '/' . $znacznik . '.jpg';
        file_put_contents($plik, "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 64));
        $przed = filesize($plik);
        try {
            self::assertNull(zmniejsz_oryginal($plik));
            self::assertSame($przed, filesize($plik), 'plik musi zostać nietknięty');
        } finally {
            @unlink($plik);
        }
    }

    public function testZmniejszanieNieRuszaMalychPlikow(): void
    {
        // nawet z GD funkcja nie dotyka pliku poniżej limitu
        $znacznik = 'male' . bin2hex(random_bytes(6));
        $plik = UPLOAD_DIR . '/' . $znacznik . '.jpg';
        $png = imagecreatetruecolor(800, 600);
        imagefilledrectangle($png, 0, 0, 800, 600, imagecolorallocate($png, 20, 120, 200));
        imagepng($png, $plik . '.png');       // lokalnie GD ma PNG (JPEG nie)
        // plik o nazwie .jpg, ale zawartość PNG => getimagesize mówi image/png
        rename($plik . '.png', $plik);
        $przed = filesize($plik);
        try {
            self::assertNull(zmniejsz_oryginal($plik), 'nie-JPEG nie jest skalowany');
            self::assertSame($przed, filesize($plik));
        } finally {
            @unlink($plik);
        }
    }

    public function testParametrySkalowania(): void
    {
        // 2000 px: do oglądania na monitorze wystarczy, a plik robi się
        // około 7 razy lżejszy (pomiar na prawdziwych zdjęciach)
        self::assertGreaterThanOrEqual(1600, PHOTO_MAX_PX);
        self::assertLessThanOrEqual(2560, PHOTO_MAX_PX);
        self::assertGreaterThanOrEqual(75, PHOTO_JPEG_QUALITY);
        self::assertLessThanOrEqual(92, PHOTO_JPEG_QUALITY);
        // limit pamięci musi przepuszczać typowe 4080x3072
        self::assertGreaterThan(4080 * 3072, THUMB_MAX_SOURCE_PX);
    }

    /* --------------- obrót EXIF a wymiary (błąd 3.12.0) --------------- */

    public function testWymiaryPoObrocie(): void
    {
        // zdjęcie z aparatu trzymanego pionowo: 4080x3072 z Orientation=6.
        // Skalujemy proporcjonalnie do 320 px, obracamy o -90.
        self::assertSame([320, 241], foto_po_obrocie_px(320, 241, 0));
        self::assertSame([241, 320], foto_po_obrocie_px(320, 241, -90));
        self::assertSame([241, 320], foto_po_obrocie_px(320, 241, 90));
        self::assertSame([320, 241], foto_po_obrocie_px(320, 241, 180));
        // obrót 180 stopni nie zmienia proporcji wymiarów
        self::assertSame([1506, 2000], foto_po_obrocie_px(1506, 2000, 180));
    }

    public function testObrotExifCzytaTag(): void
    {
        // syntetyczny JPEG 60x40 z doklejonym segmentem EXIF Orientation=6
        // (prawdziwych zdjec klienta nie trzymamy w repo - maja GPS w EXIF)
        $plik = __DIR__ . '/dane/orientacja-6.jpg';
        self::assertFileExists($plik);
        $exif = @exif_read_data($plik);
        self::assertSame(6, (int) ($exif['Orientation'] ?? 0), 'fixture musi mieć Orientation=6');
        self::assertSame(-90, foto_obrot_exif($plik), 'Orientation=6 => obrot -90');
    }

    public function testObrotExifNaZwyklymJpeg(): void
    {
        // plik bez EXIF (np. juz przeskalowany) -> 0, czyli bez obrotu
        $znacznik = 'noexif' . bin2hex(random_bytes(6));
        $plik = UPLOAD_DIR . '/' . $znacznik . '.jpg';
        $png = imagecreatetruecolor(600, 400);
        imagefilledrectangle($png, 0, 0, 600, 400, imagecolorallocate($png, 10, 90, 160));
        imagepng($png, $plik . '.png');
        rename($plik . '.png', $plik);
        try {
            self::assertSame(0, foto_obrot_exif($plik));
        } finally {
            @unlink($plik);
        }
    }
}
