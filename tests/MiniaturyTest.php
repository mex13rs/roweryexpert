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
}