<?php

declare(strict_types=1);

namespace Lazy\Csv;

use PHPUnit\Framework\TestCase;

use function assert;
use function file_get_contents;
use function is_string;
use function iterator_to_array;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

use const DIRECTORY_SEPARATOR;

/**
 * @covers \Lazy\Csv\CsvFile
 */
class CsvFileTest extends TestCase
{
    public function testItLoadsFromFile(): void
    {
        $csvFile = CsvFile::createFromFile(__DIR__ . DIRECTORY_SEPARATOR . 'example.csv');

        self::assertInstanceOf(CsvFile::class, $csvFile);
    }

    public function testItLoadsFromString(): void
    {
        $csvFile = CsvFile::createFromString("a,b\n1,2");

        self::assertInstanceOf(CsvFile::class, $csvFile);
    }

    public function testItLoadsFromArray(): void
    {
        $givenArray = [
            ['id' => '1', 'name' => 'Johanna'],
            ['id' => '2', 'name' => 'John'],
        ];

        $csvFile = CsvFile::createFromArray($givenArray);

        $parsedArray = iterator_to_array($csvFile);

        self::assertEquals($givenArray, $parsedArray);
    }

    public function testItDetectsMissingFile(): void
    {
        self::expectException(CsvFileException::class);

        CsvFile::createFromFile('404.csv');
    }

    public function testItCanIterate(): void
    {
        $numbers = CsvFile::createFromString("as_number,as_string\n1,one\n2,two");

        $numbersArray = iterator_to_array($numbers);

        self::assertCount(2, $numbersArray);
        self::assertEquals(['as_number' => '1', 'as_string' => 'one'], $numbersArray[0]);
        self::assertEquals(['as_number' => '2', 'as_string' => 'two'], $numbersArray[1]);
    }

    public function testItCanHandleCsvFilesWithoutHeader(): void
    {
        $csvFile = CsvFile::createFromString(
            "name,max\nage,42\ngender,male",
            useHeaderRow: false,
        );

        $data = iterator_to_array($csvFile);

        self::assertCount(3, $data);
        self::assertEquals(['name', 'max'], $data[0]);
        self::assertEquals(['age', '42'], $data[1]);
        self::assertEquals(['gender', 'male'], $data[2]);
    }

    public function testItCanParseOtherSeparators(): void
    {
        $csvFile = CsvFile::createFromString(
            "country;city\nde;berlin\nnl;amsterdam",
            separator: ';',
        );

        $data = iterator_to_array($csvFile);

        self::assertEquals([
            ['country' => 'de', 'city' => 'berlin'],
            ['country' => 'nl', 'city' => 'amsterdam'],
        ], $data);
    }

    public function testItCanSaveAsFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'lazy_test_csv_');

        assert(is_string($tempFile));

        $csvFile = CsvFile::createFromArray([
            ['id' => '1', 'name' => 'foo'],
            ['id' => '2', 'name' => 'bar'],
        ]);

        $csvFile->saveAs($tempFile);

        self::assertFileExists($tempFile);
        self::assertEquals("id,name\n1,foo\n2,bar\n", file_get_contents($tempFile));

        unlink($tempFile);
    }

    public function testItCanAddLines(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'lazy_test_csv_');

        assert(is_string($tempFile));

        $csvFile = CsvFile::create();

        $csvFile->add(['id' => '1', 'name' => 'Anne']);
        $csvFile->add(['id' => '2', 'name' => 'Alex']);
        $csvFile->saveAs($tempFile);

        self::assertFileExists($tempFile);
        self::assertEquals("id,name\n1,Anne\n2,Alex\n", file_get_contents($tempFile));

        unlink($tempFile);
    }
}
