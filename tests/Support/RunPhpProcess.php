<?php
declare(strict_types=1);

/**
 * Uruchamia fragment kodu PHP w osobnym procesie.
 *
 * Potrzebne dla funkcji z config.php, które kończą się przez json_fail()
 * -> exit: w procesie PHPUnita exit zakończyłby cały test suite, a nie
 * tylko pojedynczy test. W podprocesie json_fail wypisuje JSON na stdout
 * i go odczytujemy.
 */
trait RunPhpProcess
{
    /**
     * @param string $snippet kod wykonywany PO wczytaniu config.php
     * @return array{stdout: string, stderr: string, exit: int}
     */
    private function runPhp(string $snippet): array
    {
        $config = is_file(__DIR__ . '/../../config.php')
            ? __DIR__ . '/../../config.php'
            : __DIR__ . '/../../config.example.php';

        $code = 'require ' . var_export($config, true) . '; ' . $snippet;

        $proc = proc_open(
            [
                PHP_BINARY,
                '-d', 'display_errors=stderr',   // błędy na stderr, stdout czysty dla JSON
                '-d', 'error_reporting=E_ALL',
                '-r', $code,
            ],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        if ($proc === false) {
            self::fail('Nie udalo sie uruchomic podprocesu PHP');
        }

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);

        return ['stdout' => $stdout, 'stderr' => $stderr, 'exit' => $exit];
    }

    /**
     * Uruchamia wyrażenie w podprocesie i zwraca jego wynik JSON
     * (json_fail/json_out wypisują JSON i kończą proces).
     */
    private function runJson(string $snippet): array
    {
        $r = $this->runPhp($snippet);
        $decoded = json_decode($r['stdout'], true);
        if (!is_array($decoded)) {
            self::fail(sprintf(
                "Oczekiwano JSON na stdout, dostano:\nstdout: %s\nstderr: %s",
                $r['stdout'],
                $r['stderr']
            ));
        }
        return $decoded;
    }

    /** json_fail po stronie podprocesu - sprawdza komunikat błędu. */
    private function assertJsonFail(string $snippet, string $expectedError): void
    {
        $out = $this->runJson($snippet);
        self::assertArrayHasKey('success', $out, 'Odpowiedz powinna miec success');
        self::assertFalse($out['success'], 'success powinno byc false');
        self::assertSame($expectedError, $out['error'] ?? null, 'Komunikat bledu');
    }
}
