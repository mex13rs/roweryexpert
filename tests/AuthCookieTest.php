<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Funkcje auth z config.php, które nie wymagają bazy:
 * auth_cookie_plausible, auth_is_https, auth_cookie_options.
 */
final class AuthCookieTest extends TestCase
{
    private array $serverBackup = [];

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
    }

    /* ----------------------- auth_cookie_plausible ---------------------- */

    public function testPoprawnyToken64Hex(): void
    {
        self::assertTrue(auth_cookie_plausible(str_repeat('a1b2c3d4', 8)));
    }

    public function testTokenZaKrotki(): void
    {
        self::assertFalse(auth_cookie_plausible(str_repeat('a', 63)));
    }

    public function testTokenZaDlugi(): void
    {
        self::assertFalse(auth_cookie_plausible(str_repeat('a', 65)));
    }

    public function testTokenWielkieLiteryOdrzucony(): void
    {
        // tokeny generujemy bin2hex -> wyłącznie małe litery a-f
        self::assertFalse(auth_cookie_plausible(str_repeat('A', 64)));
    }

    public function testTokenNieHexowy(): void
    {
        self::assertFalse(auth_cookie_plausible(str_repeat('g', 64)));
        self::assertFalse(auth_cookie_plausible(''));
    }

    /* -------------------------- auth_is_https --------------------------- */

    public function testHttpsOn(): void
    {
        $_SERVER['HTTPS'] = 'on';
        self::assertTrue(auth_is_https());
    }

    public function testHttpsOff(): void
    {
        $_SERVER['HTTPS'] = 'off';
        self::assertFalse(auth_is_https());
    }

    public function testBrakHttps(): void
    {
        unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO']);
        self::assertFalse(auth_is_https());
    }

    public function testReverseProxyHttps(): void
    {
        unset($_SERVER['HTTPS']);
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        self::assertTrue(auth_is_https());
    }

    /* ------------------------ auth_cookie_options ----------------------- */

    public function testOpcjeCookieZabezpieczone(): void
    {
        unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO']);
        $opt = auth_cookie_options(12345);
        self::assertSame(12345, $opt['expires']);
        self::assertSame('/', $opt['path']);
        self::assertTrue($opt['httponly']);
        self::assertSame('Lax', $opt['samesite']);
        self::assertFalse($opt['secure'], 'Bez HTTPS cookie nie moze byc secure');
    }

    public function testOpcjeCookieSecureNaHttps(): void
    {
        $_SERVER['HTTPS'] = 'on';
        $opt = auth_cookie_options(12345);
        self::assertTrue($opt['secure'], 'Na HTTPS cookie musi byc secure');
    }
}
