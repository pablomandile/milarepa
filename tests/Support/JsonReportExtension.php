<?php declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErroredSubscriber;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\FailedSubscriber;
use PHPUnit\Event\Test\MarkedIncomplete;
use PHPUnit\Event\Test\MarkedIncompleteSubscriber;
use PHPUnit\Event\Test\Passed;
use PHPUnit\Event\Test\PassedSubscriber;
use PHPUnit\Event\Test\Skipped;
use PHPUnit\Event\Test\SkippedSubscriber;
use PHPUnit\Event\TestRunner\Finished as TestRunnerFinished;
use PHPUnit\Event\TestRunner\FinishedSubscriber as TestRunnerFinishedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * Extensión de PHPUnit que concentra el resultado de toda la corrida en un
 * único archivo JSON, pensado para leerse como reporte tras introducir un
 * cambio. El archivo se escribe en la raíz del proyecto como test-report.json
 * (está en .gitignore).
 *
 * Se registra en phpunit.xml dentro de <extensions>.
 */
final class JsonReportExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        // Permite sobreescribir la ruta del reporte vía parámetro en phpunit.xml.
        $path = $parameters->has('path')
            ? $parameters->get('path')
            : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'test-report.json';

        $collector = new JsonReportCollector($path);

        $facade->registerSubscribers(
            new class($collector) implements PassedSubscriber {
                public function __construct(private JsonReportCollector $c) {}
                public function notify(Passed $event): void
                {
                    $this->c->record('passed', $event->test());
                }
            },
            new class($collector) implements FailedSubscriber {
                public function __construct(private JsonReportCollector $c) {}
                public function notify(Failed $event): void
                {
                    $this->c->record('failed', $event->test(), $event->throwable()->message());
                }
            },
            new class($collector) implements ErroredSubscriber {
                public function __construct(private JsonReportCollector $c) {}
                public function notify(Errored $event): void
                {
                    $this->c->record('errored', $event->test(), $event->throwable()->message());
                }
            },
            new class($collector) implements SkippedSubscriber {
                public function __construct(private JsonReportCollector $c) {}
                public function notify(Skipped $event): void
                {
                    $this->c->record('skipped', $event->test(), $event->message());
                }
            },
            new class($collector) implements MarkedIncompleteSubscriber {
                public function __construct(private JsonReportCollector $c) {}
                public function notify(MarkedIncomplete $event): void
                {
                    $this->c->record('incomplete', $event->test(), $event->throwable()->message());
                }
            },
            new class($collector) implements TestRunnerFinishedSubscriber {
                public function __construct(private JsonReportCollector $c) {}
                public function notify(TestRunnerFinished $event): void
                {
                    $this->c->write();
                }
            },
        );
    }
}

/**
 * Acumula los resultados de cada test y escribe el JSON al finalizar la corrida.
 */
final class JsonReportCollector
{
    /** @var array<int, array{test: string, status: string, message: string|null}> */
    private array $results = [];

    public function __construct(private string $path) {}

    public function record(string $status, Test $test, ?string $message = null): void
    {
        $this->results[] = [
            'test'    => $this->testName($test),
            'status'  => $status,
            'message' => $message !== null ? trim($message) : null,
        ];
    }

    public function write(): void
    {
        $counts = [
            'passed'     => 0,
            'failed'     => 0,
            'errored'    => 0,
            'skipped'    => 0,
            'incomplete' => 0,
        ];

        foreach ($this->results as $r) {
            if (isset($counts[$r['status']])) {
                $counts[$r['status']]++;
            }
        }

        $failures = array_values(array_filter(
            $this->results,
            static fn (array $r) => in_array($r['status'], ['failed', 'errored'], true),
        ));

        $report = [
            'generated_at' => date('c'),
            'summary'      => [
                'total'   => count($this->results),
                'passed'  => $counts['passed'],
                'failed'  => $counts['failed'],
                'errored' => $counts['errored'],
                'skipped' => $counts['skipped'],
                'incomplete' => $counts['incomplete'],
                'success' => ($counts['failed'] + $counts['errored']) === 0,
            ],
            'failures' => $failures,
            'tests'    => $this->results,
        ];

        file_put_contents(
            $this->path,
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
        );
    }

    private function testName(Test $test): string
    {
        if ($test->isTestMethod()) {
            return $test->className() . '::' . $test->methodName();
        }

        return $test->name();
    }
}
