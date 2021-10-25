<?php

declare(strict_types=1);

namespace Lazy\Csv;

use Generator;
use IteratorAggregate;

use function array_combine;
use function array_keys;
use function array_values;
use function fclose;
use function fgetcsv;
use function file_exists;
use function file_get_contents;
use function fopen;
use function fputcsv;
use function fwrite;
use function is_resource;
use function rewind;

/**
 * @implements IteratorAggregate<int, array<int|string, string>>
 */
class CsvFile implements IteratorAggregate
{
    private const DEFAULT_ENCLOSURE = '"';
    private const DEFAULT_ESCAPE = '\\';
    private const DEFAULT_SEPARATOR = ',';
    private const DEFAULT_USE_HEADER_ROW = true;

    /**
     * @param array<int, array<int|string, mixed>> $data
     * @param resource|null $resource
     */
    private function __construct(
        private array $data = [],
        private ?string $rawData = null,
        private $resource = null,
        private bool $parsed = false,
        private bool $useHeaderRow = self::DEFAULT_USE_HEADER_ROW,
        private string $separator = self::DEFAULT_SEPARATOR,
        private string $enclosure = self::DEFAULT_ENCLOSURE,
        private string $escape = self::DEFAULT_ESCAPE,
    ) {
    }

    public function __destruct()
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
    }

    public static function create(
        bool $useHeaderRow = self::DEFAULT_USE_HEADER_ROW,
        string $separator = self::DEFAULT_SEPARATOR,
        string $enclosure = self::DEFAULT_ENCLOSURE,
        string $escape = self::DEFAULT_ESCAPE,
    ): self {
        return new self(
            parsed: true,
            useHeaderRow: $useHeaderRow,
            separator: $separator,
            enclosure: $enclosure,
            escape: $escape,
        );
    }

    /**
     * @param array<int, array<int|string, mixed>> $data
     */
    public static function createFromArray(
        array $data,
        bool $useHeaderRow = self::DEFAULT_USE_HEADER_ROW,
        string $separator = self::DEFAULT_SEPARATOR,
        string $enclosure = self::DEFAULT_ENCLOSURE,
        string $escape = self::DEFAULT_ESCAPE,
    ): self {
        return new self(
            data: $data,
            parsed: true,
            useHeaderRow: $useHeaderRow,
            separator: $separator,
            enclosure: $enclosure,
            escape: $escape,
        );
    }

    public static function createFromFile(
        string $filename,
        bool $useHeaderRow = self::DEFAULT_USE_HEADER_ROW,
        string $separator = self::DEFAULT_SEPARATOR,
        string $enclosure = self::DEFAULT_ENCLOSURE,
        string $escape = self::DEFAULT_ESCAPE,
    ): self {
        if (!file_exists($filename)) {
            throw CsvFileException::create('Given file does not exist.');
        }

        $handle = fopen($filename, 'r+');

        if ($handle === false) {
            throw CsvFileException::create('Can\'t open given file.');
        }

        return self::createFromResource(
            resource: $handle,
            useHeaderRow: $useHeaderRow,
            separator: $separator,
            enclosure: $enclosure,
            escape: $escape,
        );
    }

    /**
     * @param resource $resource
     */
    public static function createFromResource(
        $resource,
        bool $useHeaderRow = self::DEFAULT_USE_HEADER_ROW,
        string $separator = self::DEFAULT_SEPARATOR,
        string $enclosure = self::DEFAULT_ENCLOSURE,
        string $escape = self::DEFAULT_ESCAPE,
    ): self {
        if (!is_resource($resource)) {
            throw CsvFileException::create('Given argument is not a resource.');
        }

        return new self(
            resource: $resource,
            parsed: false,
            useHeaderRow: $useHeaderRow,
            separator: $separator,
            enclosure: $enclosure,
            escape: $escape,
        );
    }

    public static function createFromString(
        string $data,
        bool $useHeaderRow = self::DEFAULT_USE_HEADER_ROW,
        string $separator = self::DEFAULT_SEPARATOR,
        string $enclosure = self::DEFAULT_ENCLOSURE,
        string $escape = self::DEFAULT_ESCAPE,
    ): self {
        return new self(
            rawData: $data,
            useHeaderRow: $useHeaderRow,
            separator: $separator,
            enclosure: $enclosure,
            escape: $escape,
        );
    }

    public static function createFromUrl(
        string $url,
        bool $useHeaderRow = self::DEFAULT_USE_HEADER_ROW,
        string $separator = self::DEFAULT_SEPARATOR,
        string $enclosure = self::DEFAULT_ENCLOSURE,
        string $escape = self::DEFAULT_ESCAPE,
    ): self {
        $content = file_get_contents($url);

        if ($content === false) {
            throw CsvFileException::create('Given file/url does not exist.');
        }

        return self::createFromString(
            data: $content,
            useHeaderRow: $useHeaderRow,
            separator: $separator,
            enclosure: $enclosure,
            escape: $escape,
        );
    }

    /**
     * @param array<int|string, string> $row
     */
    public function add(array $row): void
    {
        $this->data[] = $row;
    }

    /**
     * @return array<int, array<int|string, mixed>>
     */
    public function asArray(): array
    {
        if ($this->parsed === false) {
            $this->parse();
        }

        return $this->data;
    }

    /**
     * @return Generator<int, array<int|string, string>>
     */
    public function getIterator(): iterable
    {
        $headerRow = null;

        if ($this->parsed) {
            foreach ($this->data as $data) {
                yield $data;
            }
        }

        while ($this->parsed === false && $row = $this->getNextLine()) {
            if ($this->useHeaderRow && $headerRow === null) {
                $headerRow = $row;
                continue;
            }

            if ($this->useHeaderRow) {
                $row = array_combine($headerRow, $row);
            }

            $this->data[] = $row;

            yield $row;
        }

        $this->parsed = true;
    }

    public function saveAs(string $filename): void
    {
        if ($this->parsed === false) {
            $this->parse();
        }

        $handle = fopen($filename, 'w+');
        $printedHeaderRow = false;

        if (!is_resource($handle)) {
            throw CsvFileException::create('Can\'t open given file.');
        }

        foreach ($this as $csvLine) {
            if ($printedHeaderRow === false && $this->useHeaderRow) {
                fputcsv(
                    $handle,
                    array_keys($csvLine),
                    separator: $this->separator,
                    enclosure: $this->enclosure,
                    escape: $this->escape,
                );
                $printedHeaderRow = true;
            }

            fputcsv(
                $handle,
                array_values($csvLine),
                separator: $this->separator,
                enclosure: $this->enclosure,
                escape: $this->escape,
            );
        }

        fclose($handle);
    }

    /**
     * @return string[]|false
     */
    private function getNextLine(): array|bool
    {
        if ($this->resource === null) {
            $resource = fopen('php://memory', 'r+');

            if ($resource === false) {
                throw CsvFileException::create('Can\'t open "php://memory"');
            }

            $this->resource = $resource;

            fwrite($this->resource, $this->rawData);
            rewind($this->resource);
        }

        return fgetcsv(
            stream: $this->resource,
            separator: $this->separator,
            enclosure: $this->enclosure,
            escape: $this->escape,
        );
    }

    private function parse(): void
    {
        foreach ($this as $csvLine) {
        }

        $this->parsed = true;
    }
}
