<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue;

use CraftCms\Cms\Queue\Contracts\Batchable;
use Generator;
use RuntimeException;
use UnexpectedValueException;

/**
 * Reads the rows of a CSV file in slices.
 *
 * Rows are read lazily, and {@see $position} is updated as each one is read,
 * so a {@see BatchedJob} can save it after a batch (for example in
 * `afterBatch()`) and pass it back in for the next batch. The next slice then
 * starts at that byte position instead of reading past every row before it.
 *
 * ```php
 * public ?int $position = null;
 *
 * protected function loadData(): Batchable
 * {
 *     return new CsvBatcher($this->path, position: $this->position);
 * }
 *
 * protected function afterBatch(): void
 * {
 *     $this->position = $this->data()->position;
 * }
 * ```
 *
 * Blank lines are skipped.
 *
 * @since 6.0.0
 */
class CsvBatcher implements Batchable
{
    /**
     * @param  string  $path  The path to the CSV file.
     * @param  bool  $hasHeader  Whether the first row holds column names. When it does, rows are returned as arrays keyed by them.
     * @param  int|null  $position  The byte position the next slice should start at. When set, the slice’s offset is ignored.
     */
    public function __construct(
        public readonly string $path,
        public readonly bool $hasHeader = true,
        public readonly string $separator = ',',
        public readonly string $enclosure = '"',
        public ?int $position = null,
    ) {}

    public function count(): int
    {
        $handle = $this->open();
        $count = 0;

        try {
            while ($this->readRow($handle) !== null) {
                $count++;
            }
        } finally {
            fclose($handle);
        }

        return $this->hasHeader ? max($count - 1, 0) : $count;
    }

    /**
     * @return Generator<int, array<array-key, string|null>>
     */
    public function getSlice(int $offset, int $limit): Generator
    {
        $handle = $this->open();

        try {
            $header = $this->hasHeader ? $this->readHeader($handle) : null;

            if ($this->position !== null) {
                fseek($handle, $this->position);
            } else {
                $skipped = 0;

                while ($skipped < $offset && $this->readRow($handle) !== null) {
                    $skipped++;
                }
            }

            for ($i = 0; $i < $limit; $i++) {
                $row = $this->readRow($handle);

                if ($row === null) {
                    break;
                }

                $this->position = (int) ftell($handle);

                yield $header !== null ? $this->combine($header, $row) : $row;
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return resource
     */
    private function open()
    {
        $handle = @fopen($this->path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Unable to open $this->path.");
        }

        // Excel and other apps start UTF-8 CSV files with a byte order mark
        if (fread($handle, 3) !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        return $handle;
    }

    /**
     * @param  resource  $handle
     * @return list<string>|null
     */
    private function readHeader($handle): ?array
    {
        $header = $this->readRow($handle);

        return $header !== null ? array_map(strval(...), $header) : null;
    }

    /**
     * Returns the next row that isn’t blank, or `null` at the end of the file.
     *
     * @param  resource  $handle
     * @return array<int, string|null>|null
     */
    private function readRow($handle): ?array
    {
        while (($row = fgetcsv($handle, null, $this->separator, $this->enclosure, '')) !== false) {
            if ($row !== [null]) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $header
     * @param  array<int, string|null>  $row
     * @return array<string, string|null>
     */
    private function combine(array $header, array $row): array
    {
        if (count($row) > count($header)) {
            throw new UnexpectedValueException(sprintf(
                'A row in %s has %d values, but the header only has %d columns.',
                $this->path,
                count($row),
                count($header),
            ));
        }

        return array_combine($header, array_pad($row, count($header), null));
    }
}
