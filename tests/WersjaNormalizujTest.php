<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Normalizacja wersji do semver - wersja z config.php (function
 * wersja_normalizuj) decyduje o porównaniu w check_update().
 */
final class WersjaNormalizujTest extends TestCase
{
    public function testPelnaWersjaBezZmian(): void
    {
        self::assertSame('3.8.1', wersja_normalizuj('3.8.1'));
    }

    public function testWersjaZSufiksemInstalator(): void
    {
        // bez normalizacji version_compare dostaje "-instalator" i porównuje źle
        self::assertSame('3.8.0', wersja_normalizuj('3.8-instalator'));
    }

    public function testWersjaDwuskladnikowaDostajePatch(): void
    {
        self::assertSame('3.8.0', wersja_normalizuj('3.8'));
    }

    public function testWersjaZPrefiksemV(): void
    {
        self::assertSame('3.9.2', wersja_normalizuj('v3.9.2'));
    }

    public function testCzterySkladnikiBierzePierwszeTrzy(): void
    {
        self::assertSame('10.20.30', wersja_normalizuj('10.20.30.40'));
    }

    public function testSmieciZwracaZero(): void
    {
        self::assertSame('0.0.0', wersja_normalizuj('abc'));
        self::assertSame('0.0.0', wersja_normalizuj(''));
    }
}
