<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue;

use CraftCms\Cms\Queue\Contracts\Batchable;
use CraftCms\Cms\Support\Json;
use Generator;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

/**
 * Reads the lines of a JSON Lines (JSONL) file in slices, decoding each one.
 *
 * Lines are read lazily, and {@see $position} is updated as each one is read,
 * so a {@see BatchedJob} can save it after a batch (for example in
 * `afterBatch()`) and pass it back in for the next batch. The next slice then
 * starts at that byte position instead of reading past every line before it.
 *
 * ```php
 * public ?int $position = null;
 *
 * protected function loadData(): Batchable
 * {
 *     return new JsonlBatcher($this->path, position: $this->position);
 * }
 *
 * protected function afterBatch(): void
 * {
 *     $this->position = $this->data()->position;
 * }
 * ```
 *
 * With `reverse`, lines are read from the end of the file to the start. Use
 * this when later lines depend on earlier ones, such as a Shopify bulk
 * operation, which writes child objects after their parents.
 *
 * Blank lines are skipped. `count()` reads the whole file, so a subclass can
 * override it when the number of lines is already known, or when the file
 * won't exist until the job runs, since a job is counted when it's described.
 *
 * @since 6.0.0
 */
class JsonlBatcher implements Batchable
{
    private const int CHUNK_SIZE = 65536;

    /**
     * @param  string  $path  The path to the JSONL file.
     * @param  bool  $reverse  Whether to read lines from the end of the file to the start.
     * @param  int|null  $position  The byte position the next slice should start at. When reading in reverse, the slice
     *                              starts with the line that ends before it. When set, the slice’s offset is ignored.
     */
    public function __construct(
        public readonly string $path,
        public readonly bool $reverse = false,
        public ?int $position = null,
    ) {}

    public function count(): int
    {
        $handle = $this->open();
        $count = 0;

        try {
            foreach ($this->readForward($handle, 0) as $ignored) {
                $count++;
            }
        } finally {
            fclose($handle);
        }

        return $count;
    }

    /**
     * @return Generator<int, mixed>
     */
    public function getSlice(int $offset, int $limit): Generator
    {
        if ($limit <= 0) {
            return;
        }

        $handle = $this->open();

        try {
            $lines = $this->reverse
                ? $this->readBackward($handle, $this->position ?? (int) fstat($handle)['size'])
                : $this->readForward($handle, $this->position ?? 0);
            $skip = $this->position === null ? $offset : 0;
            $read = 0;

            foreach ($lines as $position => $line) {
                if ($skip > 0) {
                    $skip--;

                    continue;
                }

                $this->position = $position;

                yield $this->decode($line);

                if (++$read === $limit) {
                    break;
                }
            }

            // Otherwise a BatchedJob whose total is higher would spawn batches forever
            if ($read === 0) {
                throw new UnexpectedValueException("$this->path has no more lines to read.");
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

        return $handle;
    }

    /**
     * Yields the lines that aren’t blank from a byte position to the end of the file, keyed by the byte position
     * after each one.
     *
     * @param  resource  $handle
     * @return Generator<int, string>
     */
    private function readForward($handle, int $start): Generator
    {
        fseek($handle, $start);

        while (($line = fgets($handle)) !== false) {
            if (trim($line) !== '') {
                yield (int) ftell($handle) => $line;
            }
        }
    }

    /**
     * Yields the lines that aren’t blank from a byte position back to the start of the file, keyed by the byte
     * position each one starts at.
     *
     * @param  resource  $handle
     * @return Generator<int, string>
     */
    private function readBackward($handle, int $end): Generator
    {
        $buffer = '';
        $bufferStart = $end;
        // The buffer’s bytes from this length on have already been yielded
        $length = 0;

        while (true) {
            $newline = $length > 0 ? strrpos($buffer, "\n", $length - 1 - strlen($buffer)) : false;

            if ($newline === false) {
                if ($bufferStart === 0) {
                    $line = substr($buffer, 0, $length);

                    if (trim($line) !== '') {
                        yield 0 => $line;
                    }

                    return;
                }

                $chunkSize = min(self::CHUNK_SIZE, $bufferStart);
                $bufferStart -= $chunkSize;
                // fread() can return less than requested, such as through a stream wrapper
                $buffer = stream_get_contents($handle, $chunkSize, $bufferStart).substr($buffer, 0, $length);
                $length = strlen($buffer);

                continue;
            }

            $line = substr($buffer, $newline + 1, $length - $newline - 1);
            $length = $newline;

            if (trim($line) !== '') {
                yield $bufferStart + $newline + 1 => $line;
            }
        }
    }

    private function decode(string $line): mixed
    {
        try {
            return Json::decode($line);
        } catch (InvalidArgumentException $e) {
            throw new UnexpectedValueException("A line in $this->path isn’t valid JSON.", previous: $e);
        }
    }
}
