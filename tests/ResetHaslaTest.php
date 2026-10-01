<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Reset hasła admina („nie pamiętam hasła"):
 * - generator losowego hasła (bez mylących znaków),
 * - budowa linku potwierdzającego,
 * - wczesne odrzucenie błędnego tokenu (BEZ bazy - gałąź długości tokenu).
 */
final class ResetHaslaTest extends TestCase
{
    public function testHasloMaZadanaDlugosc(): void
    {
        self::assertSame(12, strlen(reset_nowe_haslo()));
        self::assertSame(8, strlen(reset_nowe_haslo(8)));
        self::assertSame(20, strlen(reset_nowe_haslo(20)));
    }

    public function testHasloBezMylajacychZnakow(): void
    {
        // 0/O oraz 1/l/I użytkownik przepisujący hasło z maila musiałby
        // zgadywać - generator ich nie używa
        $alfabet = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        for ($i = 0; $i < 20; $i++) {
            $haslo = reset_nowe_haslo();
            self::assertMatchesRegularExpression('/^[' . $alfabet . ']+$/', $haslo);
        }
    }

    public function testHaslaSieRoznia(): void
    {
        $zestaw = [];
        for ($i = 0; $i < 5; $i++) {
            $zestaw[reset_nowe_haslo()] = true;
        }
        self::assertGreaterThan(1, count($zestaw), 'pięć losowań nie może dać jednego hasła');
    }

    public function testLinkPotwierdzajacy(): void
    {
        $token = str_repeat('a', 64);
        self::assertSame(
            'https://host91573.iqhs.pl/rower/serwis.php?reset=' . $token,
            reset_link('https://host91573.iqhs.pl/rower', $token)
        );
        // nadmiarowy slash na bazie nie może dać podwójnego
        self::assertSame(
            'https://x.pl/serwis.php?reset=' . $token,
            reset_link('https://x.pl/', $token)
        );
    }

    public function testPustyLubKrotkiTokenOdrzuconyBezBazy(): void
    {
        // ta gałąź zwraca zanim php sięgnie po db() - stąd test bez MySQL
        self::assertSame('bledny', reset_potwierdz(''));
        self::assertSame('bledny', reset_potwierdz('za_krotki_token'));
        self::assertSame('bledny', reset_potwierdz(str_repeat('z', 63)));
        self::assertSame('bledny', reset_potwierdz(str_repeat('z', 65)));
    }
}
