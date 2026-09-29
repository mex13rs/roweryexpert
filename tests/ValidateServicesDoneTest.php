<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * validate_services_done() - walidacja listy wykonanych czynności.
 *
 * Funkcja jest czysta (bez bazy), ale błędy kończy json_fail() -> exit,
 * więc przypadki błędne lecą w podprocesie.
 */
final class ValidateServicesDoneTest extends TestCase
{
    use RunPhpProcess;

    /* ------------------------------ sukces ------------------------------ */

    public function testPustyStringToPustaLista(): void
    {
        self::assertSame([], validate_services_done(''));
        self::assertSame([], validate_services_done('   '));
    }

    public function testPoprawnaLista(): void
    {
        $raw = json_encode(['Wymiana klocków', 'Centrowanie koła'], JSON_UNESCAPED_UNICODE);
        self::assertSame(['Wymiana klocków', 'Centrowanie koła'], validate_services_done($raw));
    }

    public function testPrzycinaIUsuwaPowtorki(): void
    {
        // null i "" są pomijane, powtórka po trimie wylatuje przez array_unique
        $raw = json_encode(['  Wymiana klocków  ', 'Wymiana klocków', null, ''], JSON_UNESCAPED_UNICODE);
        $out = validate_services_done($raw);
        self::assertSame(['Wymiana klocków'], $out);
    }

    public function testKolejnoscSieZachowuje(): void
    {
        $raw = json_encode(['B', 'A', 'C'], JSON_UNESCAPED_UNICODE);
        self::assertSame(['B', 'A', 'C'], validate_services_done($raw));
    }

    /* ------------------------------ błędy ------------------------------- */

    public function testNieJson(): void
    {
        $this->assertJsonFail(
            "validate_services_done('to nie jest json');",
            'Nieprawidłowa lista wykonanych czynności.'
        );
    }

    public function testJsonToLiczbaNieTablica(): void
    {
        $this->assertJsonFail(
            'validate_services_done("42");',
            'Nieprawidłowa lista wykonanych czynności.'
        );
    }

    public function testNazwaZaDluga(): void
    {
        $long = str_repeat('x', 201);
        $this->assertJsonFail(
            'validate_services_done(' . var_export(json_encode([$long]), true) . ');',
            'Nazwa wykonanej czynności jest za długa (max 200 znaków).'
        );
    }

    public function testZaDuzoPozycji(): void
    {
        $items = [];
        for ($i = 0; $i < 51; $i++) {
            $items[] = 'Czynność ' . $i;
        }
        $this->assertJsonFail(
            'validate_services_done(' . var_export(json_encode($items, JSON_UNESCAPED_UNICODE), true) . ');',
            'Za dużo wykonanych czynności (max 50).'
        );
    }

    public function testDokladnie50PozycjiPrzechodzi(): void
    {
        $items = [];
        for ($i = 0; $i < 50; $i++) {
            $items[] = 'Czynność ' . $i;
        }
        $out = validate_services_done(json_encode($items, JSON_UNESCAPED_UNICODE));
        self::assertCount(50, $out);
    }
}
