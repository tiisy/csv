<?php

declare(strict_types=1);

namespace Tiisy\Csv;

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
 * @covers CsvFile
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

    public function testItCanHandleEmptyCells(): void
    {
        $csvFile = CsvFile::createFromString(
            'id;data' . PHP_EOL .
            '1;hi' . PHP_EOL .
            '2;""' . PHP_EOL .
            '3;' . PHP_EOL .
            '4' . PHP_EOL,
            separator: ';',
        );

        $data = iterator_to_array($csvFile);

        self::assertCount(4, $data);
        self::assertEquals(['id' => '1', 'data' => 'hi'], $data[0]);
        self::assertEquals(['id' => '2', 'data' => ''], $data[1]);
        self::assertEquals(['id' => '3', 'data' => null], $data[2]);
        self::assertEquals(['id' => '4', 'data' => null], $data[3]);
    }

    public function testItThrowsExceptionIfARowContainsMoreCellsThanHeaderRow(): void
    {
        self::expectException(CsvFileException::class);

        $csvFile = CsvFile::createFromString(
            'id,data' . PHP_EOL .
            '1,foo' . PHP_EOL .
            '2,foo,bar' . PHP_EOL,
        );

        $csvFile->asArray();
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
        $tempFile = tempnam(sys_get_temp_dir(), 'Lazier_test_csv_');

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
        $tempFile = tempnam(sys_get_temp_dir(), 'tiisy_test_csv_');

        assert(is_string($tempFile));

        $csvFile = CsvFile::create();

        $csvFile->add(['id' => '1', 'name' => 'Anne']);
        $csvFile->add(['id' => '2', 'name' => 'Alex']);
        $csvFile->saveAs($tempFile);

        self::assertFileExists($tempFile);
        self::assertEquals("id,name\n1,Anne\n2,Alex\n", file_get_contents($tempFile));

        unlink($tempFile);
    }

    public function testItCanAddLinesAndPreserveKeyOrder(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'tiisy_test_csv_');

        assert(is_string($tempFile));

        $csvFile = CsvFile::create();

        $csvFile->add(['id' => '1', 'name' => 'Anne']);
        $csvFile->add(['name' => 'Billy', 'id' => '2']);
        $csvFile->saveAs($tempFile);

        self::assertFileExists($tempFile);
        self::assertEquals("id,name\n1,Anne\n2,Billy\n", file_get_contents($tempFile));

        unlink($tempFile);
    }

    public function testItCanAddLinesWithEmptyCells(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'tiisy_test_csv_');

        assert(is_string($tempFile));

        $csvFile = CsvFile::create();

        $csvFile->add(['id' => '1', 'name' => 'Anne']);
        $csvFile->add(['id' => '2', 'name' => 'John', 'surname' => 'Doe']);
        $csvFile->saveAs($tempFile);

        self::assertFileExists($tempFile);
        self::assertEquals("id,name,surname\n1,Anne,\n2,John,Doe\n", file_get_contents($tempFile));

        unlink($tempFile);
    }
}
