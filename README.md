Tiisy CSV Component
===================

_Tiisy CSV_ provides a simple way to handle CSV files.


Advantages
----------

* Can treat CSV files as associative arrays using header row logic (`useHeaderRow: true`)
* Uses PHP's native `fgetcsv` and `fputcsv` 
* No dependencies
* Framework-agnostic
* KISS, keep it simple and stupid (easy maintenance and contributing)
* Well tested


Installation
------------

Using Composer:

    composer require tiisy/csv


Usage
-----

Example:

```php
<?php

use Tiisy\Csv\CsvFile;

foreach(CsvFile::createFromString("id,name\n1,foo\n2,bar") as $row => $data) {
    echo $data['id'] . ': ' . $data['name'] . PHP_EOL;
}

// will output:
// 1: foo
// 2: bar
```

### Create instance of CsvFile

Example:

```php
<?php

use Tiisy\Csv\CsvFile;

foreach(CsvFile::createFromFile('example.csv', useHeaderRow: false) as $data) {
    echo $data[0] . ': ' . $data[1];
}
```

Ways to create an instance of `CsvFile`:

* `create(<options>)` – Creates an empty CSV file
* `createFromArray(array $data)` – Creates an CSV file with given data
* `createFromFile(string $filename, <options>)` – Loads CSV file by given file
* `createFromResource(resource $handle, <options>)` – Loads CSV file by given resource
* `createFromString(string $input, <options>)` – Loads CSV file by given string
* `createFromUrl(string $url, <options>)` – Loads CSV file by given URL
* `createFromGoogleSpreadsheetId(string $spreadsheetId, <options>)` – Loads CSV file by given Google Spreadsheet ID

Optional _options_ for creating an instance of `CsvFile`:

* `useHeaderRow` (default: `true`) Uses first row as keys for following rows
* `separator` (default: `,`) Sets the field separator (one single-byte character only)
* `enclosure` (default: `"`) Sets the field enclosure character (one single-byte character only)
* `escape` (default: `\`) Sets the escape character (one single-byte character)

### Modifying CSV

```php
<?php

use Tiisy\Csv\CsvFile;

$csvFile = CsvFile::createFromArray([
    ['id' => '1', 'name' => 'Nina'],
    ['id' => '2', 'name' => 'Angela'],
]);

$csvFile->add(['id' => '3', 'name' => 'John']);

// You can save your modified CSV file this way:
$csvFile->saveAs('names.csv');
```
