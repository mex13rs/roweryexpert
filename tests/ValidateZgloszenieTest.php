<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * validate_zgloszenie() - walidacja formularza zgłoszenia.
 *
 * json_fail() kończy proces przez exit, dlatego ścieżki błędu lecą
 * w podprocesie (patrz trait RunPhpProcess).
 *
 * Uwaga: od połowy funkcji woła modul('kalendarz') -> db(), więc bez
 * MySQL testujemy tylko wczesne odrzucenia (nazwa, data przyjęcia).
 * Ścieżkę sukcesu i późniejsze błędy (telefon, usterka, status)
 * dorobimy, gdy będzie lokalna baza.
 */
final class ValidateZgloszenieTest extends TestCase
{
    use RunPhpProcess;

    /**
     * Czy lokalna baza jest dostępna? validate_zgloszenie od połowy woła
     * modul('kalendarz') -> db(), więc bez MySQL te ścieżki nie dojdą
     * do json_fail. Bez bazy pomijamy test zamiast zgłaszać fałszywy błąd.
     */
    private function requireDatabase(): void
    {
        static $available = null;
        if ($available === null) {
            try {
                db();
                $available = true;
            } catch (Throwable $e) {
                $available = false;
            }
        }
        if (!$available) {
            self::markTestSkipped(
                'Brak lokalnej bazy MySQL - funkcja przechodzi przez modul() -> db()'
            );
        }
    }

    /** Buduje wywołanie validate_zgloszenie($input) w podprocesie. */
    private function call(array $input): string
    {
        return 'validate_zgloszenie(' . var_export($input, true) . ');';
    }

    private function base(array $overrides = []): array
    {
        return $overrides + [
            'bike_name' => 'Kross Trans Alp',
            'date_in' => '2026-09-29',
            'date_planned' => '2026-10-05',
            'customer_phone' => '501 234 567',
            'fault_description' => 'Hamulec przedni bierze opóźnieniem',
            'status' => 'in_progress',
        ];
    }

    /* ------------------------- wcześniejsze błędy ------------------------ */

    public function testPustaNazwaRoweru(): void
    {
        $this->assertJsonFail(
            $this->call($this->base(['bike_name' => '   '])),
            'Podaj nazwę roweru.'
        );
    }

    public function testBrakNazwyRoweru(): void
    {
        $this->assertJsonFail(
            $this->call($this->base(['bike_name' => ''])),
            'Podaj nazwę roweru.'
        );
    }

    public function testNazwaRoweruZaDluga(): void
    {
        $this->assertJsonFail(
            $this->call($this->base(['bike_name' => str_repeat('x', 256)])),
            'Nazwa roweru jest za długa (max 255 znaków).'
        );
    }

    public function testNazwaRoweru255ZnakowPrzechodzi(): void
    {
        // graniczna długość akceptowana - funkcja przejdzie dalej i dopiero
        // modul() uderzy w bazę, więc wymagamy bazy
        $this->requireDatabase();
        $r = $this->runPhp($this->call($this->base(['bike_name' => str_repeat('x', 255)])));
        self::assertStringNotContainsString(
            'za długa',
            $r['stdout'] . $r['stderr'],
            '255 znaków powinno być przyjęte'
        );
    }

    public function testDataPrzyjeciaNieJestData(): void
    {
        $this->assertJsonFail(
            $this->call($this->base(['date_in' => 'wczoraj'])),
            'Nieprawidłowa data przyjęcia.'
        );
    }

    public function testDataPrzyjeciaNieistniejaca(): void
    {
        $this->assertJsonFail(
            $this->call($this->base(['date_in' => '2026-02-30'])),
            'Nieprawidłowa data przyjęcia.'
        );
    }

    public function testDataPrzyjeciaZlyFormat(): void
    {
        $this->assertJsonFail(
            $this->call($this->base(['date_in' => '29-09-2026'])),
            'Nieprawidłowa data przyjęcia.'
        );
    }

    /* --------------------------- błędy późniejsze ------------------------ */
    // Te ścieżki są ZA modul('kalendarz') -> db(), więc wymagają bazy.

    public function testZaKrotkiTelefon(): void
    {
        $this->requireDatabase();
        $this->assertJsonFail(
            $this->call($this->base(['customer_phone' => '123'])),
            'Podaj poprawny numer telefonu (min. 9 cyfr).'
        );
    }

    public function testTelefonBezOgonkowMusiMiec9Cyfr(): void
    {
        $this->requireDatabase();
        // "telefon" bez cyfr -> za krótki
        $this->assertJsonFail(
            $this->call($this->base(['customer_phone' => 'abc'])),
            'Podaj poprawny numer telefonu (min. 9 cyfr).'
        );
    }

    public function testPustaUsterka(): void
    {
        $this->requireDatabase();
        $this->assertJsonFail(
            $this->call($this->base(['fault_description' => '  '])),
            'Opisz usterkę roweru.'
        );
    }

    public function testNieprawidlowyStatus(): void
    {
        $this->requireDatabase();
        $this->assertJsonFail(
            $this->call($this->base(['status' => 'deleted'])),
            'Nieprawidłowy status zgłoszenia.'
        );
    }
}
