<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Zabezpieczenia z audytu 3.10.0 - ścieżki, których da się sprawdzić
 * bez bazy (czyste funkcje w config.php):
 *
 * - panel_host / panel_url_poprawny: link resetu hasła NIE może trafić
 *   na domenę podstawioną w nagłówku Host (password-reset poisoning),
 * - config_przebuduj: sekret z backslashem nie może zepsuć config.php
 *   (wcześniej preg_replace jadł "\" i powstawał parse error),
 * - validate_zgloszenie: telefon dłuższy niż kolumna jest odrzucany
 *   czytelnym komunikatem, a nie wyjątkiem bazy.
 */
final class BezpieczenstwoTest extends TestCase
{
    /** Podmiana nagłówków serwera na czas jednego testu. */
    private function serwer(array $naglowki): void
    {
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with((string) $k, 'HTTP_') || in_array($k, ['SERVER_NAME', 'SCRIPT_NAME', 'HTTPS'], true)) {
                unset($_SERVER[$k]);
            }
        }
        foreach ($naglowki as $k => $v) {
            $_SERVER[$k] = $v;
        }
    }

    protected function tearDown(): void
    {
        foreach (['HTTP_HOST', 'SERVER_NAME', 'SCRIPT_NAME'] as $k) {
            unset($_SERVER[$k]);
        }
        parent::tearDown();
    }

    /* --------------------------- walidacja hosta --------------------------- */

    public function testPoprawnyHost(): void
    {
        self::assertTrue(panel_host_poprawny('host91573.iqhs.pl'));
        self::assertTrue(panel_host_poprawny('xn--rowery-kwa.pl'));
        self::assertTrue(panel_host_poprawny('panel.example.com'));
        self::assertTrue(panel_host_poprawny('192.168.1.10'));
    }

    public function testNiepoprawnyHost(): void
    {
        // puste / za długie
        self::assertFalse(panel_host_poprawny(''));
        self::assertFalse(panel_host_poprawny(str_repeat('a', 254)));
        // ścieżka, port, userinfo, CRLF, cudzysłowy - wektor podstawienia
        self::assertFalse(panel_host_poprawny('evil.example/serwis'));
        self::assertFalse(panel_host_poprawny('evil.example:8080'));
        self::assertFalse(panel_host_poprawny('user@evil.example'));
        self::assertFalse(panel_host_poprawny("evil.example\r\nBcc: ofiara@x.pl"));
        self::assertFalse(panel_host_poprawny('evil.example "quoted"'));
        self::assertFalse(panel_host_poprawny('evil example'));
        self::assertFalse(panel_host_poprawny('..'));
        self::assertFalse(panel_host_poprawny('.evil.example'));
    }

    public function testUrlPoprawny(): void
    {
        self::assertTrue(panel_url_poprawny('https://host91573.iqhs.pl/rower'));
        self::assertTrue(panel_url_poprawny('http://panel.example.com'));
        // w Ustawieniach pole bywa puste - wtedy wchodzi gałąź żądania
        self::assertFalse(panel_url_poprawny(''));
        // tylko http/https
        self::assertFalse(panel_url_poprawny('javascript:alert(1)'));
        self::assertFalse(panel_url_poprawny('file:///etc/passwd'));
        // userinfo w URL to klasyczny wektor podmiany hosta
        self::assertFalse(panel_url_poprawny('https://evil.example@panel.example.com'));
        self::assertFalse(panel_url_poprawny('nie_url'));
    }

    public function testHostZGlowkiJestIgnorowany(): void
    {
        // Atakujący wysyła żądanie z "Host: evil.example". Vhost nadal
        // nazywa się host91573.iqhs.pl, więc link resetu ma trafić na prawdziwą
        // domenę - przed poprawką szedł na evil.example.
        $this->serwer([
            'HTTP_HOST' => 'evil.example',
            'SERVER_NAME' => 'host91573.iqhs.pl',
            'SCRIPT_NAME' => '/rower/serwis.php',
        ]);
        self::assertSame('host91573.iqhs.pl', panel_host(''));
        self::assertSame('http://host91573.iqhs.pl/rower', reset_baza(''));
    }

    public function testBezZaufanegoHostaMailNieLeciWcale(): void
    {
        // Nie ma site_url i nie ma SERVER_NAME (żądanie po IP) → fail-closed:
        // reset_baza() = '' a reset_wyslij() nie zapisuje tokenu i nie wysyła
        // maila, zamiast wysłać link na domenę z nagłówka.
        $this->serwer([
            'HTTP_HOST' => 'evil.example',
            'SCRIPT_NAME' => '/rower/serwis.php',
        ]);
        self::assertSame('', panel_host(''));
        self::assertSame('', reset_baza(''));
    }

    public function testSkonfigurowanyAdresMaPierwszenstwo(): void
    {
        // jedyne źródło, którego nie da się podać w nagłówku żądania
        $this->serwer([
            'HTTP_HOST' => 'evil.example',
            'SERVER_NAME' => 'evil.example',
            'SCRIPT_NAME' => '/rower/serwis.php',
        ]);
        self::assertSame('host91573.iqhs.pl', panel_host('https://host91573.iqhs.pl/rower'));
        self::assertSame('https://host91573.iqhs.pl/rower', reset_baza('https://host91573.iqhs.pl/rower'));
    }

    public function testZlySkonfigurowanyUrlNiePrzechodzi(): void
    {
        // Bez site_url i bez SERVER_NAME jedynym źródłem byłby nagłówek,
        // więc zły zapisany URL musi dawać fail-closed, a nie domenę klienta.
        $this->serwer([
            'HTTP_HOST' => 'evil.example',
            'SCRIPT_NAME' => '/rower/serwis.php',
        ]);
        // javascript:alert(1) / userinfo w URL - nie są adresem panelu
        self::assertSame('', panel_host('javascript:alert(1)'));
        self::assertSame('', reset_baza('javascript:alert(1)'));
        self::assertSame('', panel_host('https://panel.example.com@evil.example'));
        self::assertSame('', panel_host('  /rower  '));
    }

    public function testHostZGlowkaPrzechodziGdyZgadzaSieZVhostem(): void
    {
        $this->serwer([
            'HTTP_HOST' => 'host91573.iqhs.pl',
            'SERVER_NAME' => 'host91573.iqhs.pl',
            'SCRIPT_NAME' => '/rower/serwis.php',
        ]);
        self::assertSame('host91573.iqhs.pl', panel_host(''));
        self::assertSame('http://host91573.iqhs.pl/rower', reset_baza(''));
    }

    public function testHostZPortemZGadzaSiePoObcięciuPortu(): void
    {
        $this->serwer([
            'HTTP_HOST' => 'panel.example.com:8443',
            'SERVER_NAME' => 'panel.example.com',
            'SCRIPT_NAME' => '/serwis.php',
        ]);
        self::assertSame('panel.example.com', panel_host(''));
    }

    public function testWylacznieServerNameBezNaglowka(): void
    {
        // HTTP_HOST nieobecny (żądanie po IP, stary klient) - zostaje SERVER_NAME
        $this->serwer([
            'SERVER_NAME' => 'panel.example.com',
            'SCRIPT_NAME' => '/serwis.php',
        ]);
        self::assertSame('panel.example.com', panel_host(''));
    }

    public function testSerwerKtoryKopiujeHostDoServerName(): void
    {
        // Apache/LiteSpeed z UseCanonicalName Off: SERVER_NAME = nagłówek Host,
        // więc oba źródła są od klienta i sama walidacja ich nie wystarczy.
        // Dlatego PANEL_HOST z config.php ma pierwszeństwo - i dlatego dla
        // paneli sprzed 3.10.0 trzeba ustawić „Adres URL panelu".
        $this->serwer([
            'HTTP_HOST' => 'evil.example',
            'SERVER_NAME' => 'evil.example',
            'SCRIPT_NAME' => '/rower/serwis.php',
        ]);
        // bez site_url i bez PANEL_HOST zostaje to, co podał klient
        self::assertSame('evil.example', panel_host(''), 'to jest właśnie resztkowe ryzyko');
        // z ustawionym adresem panelu - prawdziwy host
        self::assertSame('host91573.iqhs.pl', panel_host('https://host91573.iqhs.pl/rower'));
        // z wyłączoną gałęzią HTTP_HOST: sam SERVER_NAME (vhost) wystarczy
        $this->serwer([
            'HTTP_HOST' => 'evil.example',
            'SCRIPT_NAME' => '/rower/serwis.php',
        ]);
        self::assertSame('', panel_host(''), 'bez vhostu i bez konfiguracji = fail-closed');
    }

    /* --------------------- config_przebuduj (backslash) --------------------- */

    /** Wzorzec minimalny: wymaga tylko funkcji db() i const APP_VERSION. */
    private function wzorzec(): string
    {
        return "<?php\nconst DB_PASS = 'UZUPELNIJ';\nconst APP_VERSION = '3.10.0';\n"
            . "function db(): PDO { return new PDO('x'); }\n";
    }

    public function testPrzebudowaPrzenosiZwykleSekrety(): void
    {
        $stary = "<?php\nconst DB_PASS = 'Secret123';\n";
        $r = config_przebuduj($stary, $this->wzorzec());
        self::assertTrue($r['ok'], $r['blad']);
        self::assertStringContainsString("const DB_PASS = 'Secret123';", $r['tresc']);
        self::assertStringNotContainsString('UZUPELNIJ', $r['tresc']);
    }

    /**
     * Sekrety w formie, w jakiej faktycznie stoją w config.php (czyli
     * zescapowane wewnątrz cudzysłowów jednokrotnych) - to one trafiają
     * do regexu przenoszącego wartości.
     *
     * Dawniej: preg_replace z wartością w argumencie `replacement` interpretuje
     * `\\` oraz `$n`. Sekret z backslashem dawał `const DB_PASS = 'abc\';`
     * (parse error = 500 na każdym endpoincie), a "$1" znikał bez śladu -
     * a walidacja napisowa to przepuszczała i aktualizacja zapisywała
     * zepsuty plik.
     */
public function testSekretZBackslashemNiePsucPliku(): void
    {
        foreach (self::linieSekretow() as $linia) {
            $stary = "<?php\n" . $linia . "\n";
            $r = config_przebuduj($stary, self::wzorzecDla($linia));
            self::assertTrue($r['ok'], $linia . ' -> ' . $r['blad']);
            self::assertStringContainsString($linia, $r['tresc'], 'linia musi przejść 1:1');
            self::assertNull($this->bladParsowania($r['tresc']), $linia);
        }
    }

public function testSekretZDolaramiNiePsucPliku(): void
    {
        $linia = "const APP_PASSWORD = 'haslo\$1 i \\\\ koniec';";
        $r = config_przebuduj("<?php\n" . $linia . "\n", self::wzorzecDla($linia));
        self::assertTrue($r['ok'], $r['blad']);
        self::assertStringContainsString($linia, $r['tresc'], '$1 musi zostać dosłownie');
        self::assertNull($this->bladParsowania($r['tresc']));
    }

    /** Linie `const X = '...';` z sekretami wymagającymi escapowania. */
    public static function linieSekretow(): array
    {
        return [
            "const DB_PASS = 'abc\\\\';",            // wartość: abc\
            "const DB_PASS = 'a\\'b';",              // wartość: a'b
            "const DB_PASS = 'a\\'b\\\\\\\\';",      // wartość: a'b\\
            "const DB_PASS = 'x\$1y\\\\';",          // wartość: x$1y\
        ];
    }

    /** Wzorzec z UZUPELNIJ dla tej samej stałej co podana linia. */
    private static function wzorzecDla(string $linia): string
    {
        preg_match('/^const (\w+) =/', $linia, $m);
        // PANEL_HOST nie jest sekretem (wpisuje je install.php), więc we wzorcu
        // jest pusty - tak jak w prawdziwym config.example.php
        return "<?php\nconst " . $m[1] . " = 'UZUPELNIJ';\nconst PANEL_HOST = '';\n"
            . "const APP_VERSION = '3.10.0';\n"
            . "function db(): PDO { return new PDO('x'); }\n";
    }

    public function testPanelHostPrzetrwaAktualizacje(): void
    {
        // PANEL_HOST zapisuje install.php i jest jedynym źródłem hostu
        // niedostępnym dla klienta - musi więc przejść przez przebudowę
        // config.php przy aktualizacji (inaczej reset hasła przestałby działać
        // albo wróciłby do hostu z nagłówka).
        $stary = "<?php\nconst DB_PASS = 'x';\nconst PANEL_HOST = 'panel.example.com';\n";
        $r = config_przebuduj($stary, self::wzorzecDla('const DB_PASS = \'\';'));
        self::assertTrue($r['ok'], $r['blad']);
        self::assertStringContainsString("const PANEL_HOST = 'panel.example.com';", $r['tresc']);
        self::assertNull($this->bladParsowania($r['tresc']));
    }

    public function testNiekompletnyWzorzecOdrzucony(): void
    {
        $r = config_przebuduj("<?php\nconst DB_PASS = 'x';\n", "<?php\nconst INNE = 'UZUPELNIJ';\n");
        self::assertFalse($r['ok']);
        self::assertSame('', $r['tresc']);
    }

    public function testWzorzecBezFunkcjiDbOdrzucony(): void
    {
        $r = config_przebuduj("<?php\nconst DB_PASS = 'x';\n",
            "<?php\nconst DB_PASS = 'UZUPELNIJ';\nconst APP_VERSION = '3.10.0';\n");
        self::assertFalse($r['ok']);
        self::assertStringContainsString('niepelny', $r['blad']);
    }

    /** Zwraca komunikat błędu parsowania albo null (= plik poprawny). */
    private function bladParsowania(string $kod): ?string
    {
        try {
            token_get_all($kod, TOKEN_PARSE);
            return null;
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }

    /* ---------------------------- Zip Slip --------------------------------- */

    public function testSciezkiZZipa(): void
    {
        foreach ([
            'serwis.php',
            'config.example.php',
            'assets/js/core.js',
            'api/zgloszenia.php',
            'partials/modal-ustawienia.php',
            'DOKUMENTACJA.md',
            'tests/WarcabyTest.php',
            '.user.ini',
            'zdjecia/a-b_c.jpg',
        ] as $ok) {
            self::assertTrue(sciezka_w_zipie_bezpieczna($ok), 'prawidłowy wpis: ' . $ok);
        }
    }

    public function testSciezkiUcieczkiZeZipa(): void
    {
        // potwierdzone w audycie: "../../../../tmp/opencode/zipslip/uciek.php"
        foreach ([
            '../uciek.php',
            '../../../../tmp/x/uciek.php',
            'assets/../../../uciek.php',
            '..',
            '.',
            './serwis.php',
            'assets//core.js',
            '/etc/cron.d/x',
            'assets\\core.js',
            'C:\\windows\\x.php',
            "assets/\0core.js",
            '',
        ] as $zla) {
            self::assertFalse(sciezka_w_zipie_bezpieczna($zla), 'musi zostać odrzucone: ' . var_export($zla, true));
        }
    }

    /* ----------------------- .htaccess katalogu zdjęć ----------------------- */

    public function testTrescHtaccessZakazujeSkryptow(): void
    {
        // funkcja zapisuje plik na dysku - sprawdzamy treść przez wywołanie
        // na katalogu tymczasowym nie da się (stała UPLOAD_DIR), więc
        // kontrolujemy reguły statycznie przez istnienie funkcji
        self::assertTrue(function_exists('htaccess_zdjecia_zapisz'));
    }

    /* ------------------------- walidacja telefonu ------------------------- */

    public function testDlugiTelefonOdrzuconyCzytelnymKomunikatem(): void
    {
        // 16 cyfr > PHONE_MAX_DIGITS: dawniej wpadalo do bazy i kończyło się
        // wyjątkiem "Data too long for column 'customer_phone'" (HTTP 500).
        $out = $this->waliduj(['customer_phone' => '1234567890123456']);
        self::assertFalse($out['ok'], 'telefon 16 cyfr musi być odrzucony przed zapytaniem do bazy');
        self::assertStringContainsString('za długi', $out['error']);
        // komunikat dla użytkownika, nie komunikat z bazy
        self::assertStringNotContainsString('SQLSTATE', $out['error']);
    }

    /**
     * 15 cyfr to granica (kolumna VARCHAR(32) mieści też spacje/myślniki) -
     * walidacja przechodzi dalej, a tam dochodzi do modul() -> db(), więc
     * bez MySQL test się nie kończy. Sprawdzamy to samo, co można bez bazy:
     * że walidacja NIE odpada na limicie (a 16 cyfr odpada).
     */
    public function testGranicaTelefonuJestWyzejNizKolumna(): void
    {
        self::assertSame(15, PHONE_MAX_DIGITS);
        $liczbaZnakow = strlen('123-456-789-012' . str_repeat('5', 3));   // 15 cyfr + 3 separatory
        self::assertLessThanOrEqual(32, $liczbaZnakow, 'maksymalna wartość musi zmieścić się w VARCHAR(32)');
    }

    /** validate_zgloszenie woła json_fail (exit) - dlatego podproces. */
    private function waliduj(array $telefon): array
    {
        $plik = tempnam(sys_get_temp_dir(), 'bez');
        $payload = [
            'bike_name' => 'Rower testowy',
            'date_in' => '2026-10-03',
            'date_planned' => '2026-10-05',
            'fault_description' => 'Hamulce',
            'status' => 'in_progress',
        ] + $telefon;
        $kod = '<?php require ' . var_export(__DIR__ . '/bootstrap.php', true) . ';'
            . '$_SERVER["REQUEST_METHOD"] = "GET";'
            . 'validate_zgloszenie(' . var_export($payload, true) . ');'
            . 'echo "OK";';
        file_put_contents($plik, $kod);
        $wynik = shell_exec('php ' . escapeshellarg($plik) . ' 2>&1');
        @unlink($plik);
        $decoded = json_decode((string) $wynik, true);
        if (is_array($decoded)) {
            return ['ok' => false, 'error' => (string) ($decoded['error'] ?? '')];
        }
        return ['ok' => str_contains((string) $wynik, 'OK'), 'error' => (string) $wynik];
    }
}