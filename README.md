Lazier: CSV Component
===================

_Lazier CSV_ provides a simple way to handle CSV files.


Installation
------------

Using Composer:

    composer require lazier/csv


Usage
-----

Example:

```php
<?php

use Lazier\Csv\CsvFile;

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

use Lazier\Csv\CsvFile;

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

Optional _options_ for creating an instance of `CsvFile`:

* `useHeaderRow` (default: `true`) Uses first row as keys for following rows
* `separator` (default: `,`) Sets the field separator (one single-byte character only)
* `enclosure` (default: `"`) Sets the field enclosure character (one single-byte character only)
* `escape` (default: `\`) Sets the escape character (one single-byte character)

### Modifying CSV

```php
<?php

use Lazier\Csv\CsvFile;

$csvFile = CsvFile::createFromArray([
    ['id' => '1', 'name' => 'Nina'],
    ['id' => '2', 'name' => 'Angela'],
]);

$csvFile->add(['id' => '3', 'name' => 'John']);

// You can save your modified CSV file this way:
$csvFile->saveAs('names.csv');

// You can also output the CSV contents this way:
echo $csvFile->asString();
```
