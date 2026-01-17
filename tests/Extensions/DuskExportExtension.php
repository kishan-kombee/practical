<?php

namespace Tests\Extensions;

use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErroredSubscriber;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\FailedSubscriber;
use PHPUnit\Event\Test\Passed;
use PHPUnit\Event\Test\PassedSubscriber;
use PHPUnit\Event\Test\Skipped;
use PHPUnit\Event\Test\SkippedSubscriber;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DuskExportExtension implements Extension
{
    public static array $results = [];

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new DuskTestPassedSubscriber());
        $facade->registerSubscriber(new DuskTestFailedSubscriber());
        $facade->registerSubscriber(new DuskTestErroredSubscriber());
        $facade->registerSubscriber(new DuskTestSkippedSubscriber());
        $facade->registerSubscriber(new DuskExportSubscriber());
    }
}

class DuskTestPassedSubscriber implements PassedSubscriber
{
    public function notify(Passed $event): void
    {
        DuskExportExtension::$results[$event->test()->id()] = 'Passed';
    }
}

class DuskTestFailedSubscriber implements FailedSubscriber
{
    public function notify(Failed $event): void
    {
        DuskExportExtension::$results[$event->test()->id()] = 'Failed';
    }
}

class DuskTestErroredSubscriber implements ErroredSubscriber
{
    public function notify(Errored $event): void
    {
        DuskExportExtension::$results[$event->test()->id()] = 'Error';
    }
}

class DuskTestSkippedSubscriber implements SkippedSubscriber
{
    public function notify(Skipped $event): void
    {
        DuskExportExtension::$results[$event->test()->id()] = 'Skipped';
    }
}

class DuskExportSubscriber implements ExecutionFinishedSubscriber
{
    public function notify(ExecutionFinished $event): void
    {
        echo "\n\nGenerating Dusk Test Cases CSV with Status...\n";

        $basePath = getcwd();
        $directory = $basePath . '/tests/Browser';
        $outputFile = $basePath . '/dusk_use_cases.csv';

        if (! is_dir($directory)) {
            echo " Directory not found: $directory\n";

            return;
        }

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        $csvData = [['File', 'Class', 'Test Method', 'Description', 'Status']];

        $executedTests = DuskExportExtension::$results;

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getRealPath());
            $className = '';

            // Extract class name
            if (preg_match('/class\s+(\w+)/', $content, $matches)) {
                $className = $matches[1];
            } else {
                continue; // Not a class file
            }

            $fullClassName = $this->resolveFullClassName($content, $className);

            // Skip Page objects (usually in Pages folder or extending Page)
            if (strpos($file->getPathname(), 'Pages') !== false) {
                continue;
            }

            // Extract methods and docblocks more robustly
            // 1. Find all public test methods
            preg_match_all('/public\s+function\s+(test\w+)\s*\(/', $content, $methodMatches, PREG_OFFSET_CAPTURE);

            foreach ($methodMatches[0] as $index => $match) {
                $methodName = $methodMatches[1][$index][0];
                $methodOffset = $match[1];
                $description = '';

                // 2. Look backwards for the closest docblock before this method
                $beforeMethod = substr($content, 0, $methodOffset);

                // Regex to find a docblock that ends just before the function keyword
                // Allows for whitespace, and visibility modifiers
                if (preg_match('/(\/\*\* (?: [^*] | \*+(?!\/) )*? \*+\/) \s* $/x', rtrim($beforeMethod), $docMatches)) {
                    $docBlock = $docMatches[1];

                    // Clean up docblock and extract description
                    $lines = explode("\n", $docBlock);
                    $summaryLines = [];
                    foreach ($lines as $line) {
                        $line = trim($line, " \t/*");
                        if (strpos($line, '@') === 0) {
                            break;
                        } // Stop at first annotation
                        if ($line) {
                            $summaryLines[] = $line;
                        }
                    }
                    $description = implode(' ', $summaryLines);
                }

                // Fallback description from method name
                if (empty($description)) {
                    $description = ucfirst(str_replace('_', ' ', preg_replace('/(?<!^)[A-Z]/', ' $0', substr($methodName, 4))));
                }

                // Determine Status
                // PHPUnit test IDs are usually "Class::Method"
                $testId = $fullClassName . '::' . $methodName;
                $status = $executedTests[$testId] ?? 'Not Run';

                // Try fuzzy matching if exact match fails (namespace issues)
                if ($status === 'Not Run') {
                    foreach ($executedTests as $id => $res) {
                        if (str_ends_with($id, '::' . $methodName)) {
                            $status = $res;
                            break;
                        }
                    }
                }

                $csvData[] = [
                    $file->getFilename(),
                    $className,
                    $methodName,
                    $description,
                    $status,
                ];
            }
        }

        $fp = fopen($outputFile, 'w');
        // Add BOM for Excel compatibility
        fwrite($fp, "\xEF\xBB\xBF");
        foreach ($csvData as $fields) {
            fputcsv($fp, $fields);
        }
        fclose($fp);

        echo 'Exported ' . (count($csvData) - 1) . " test cases to $outputFile\n";
    }

    private function resolveFullClassName($content, $className)
    {
        if (preg_match('/namespace\s+(.+?);/', $content, $matches)) {
            return $matches[1] . '\\' . $className;
        }

        return $className;
    }
}
