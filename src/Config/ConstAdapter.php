<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

use Dotenv\Repository\Adapter\AdapterInterface;
use PhpOption\Option;
use PhpOption\Some;

final class ConstAdapter implements AdapterInterface
{
    /**
     * Create a new array adapter instance.
     *
     * @return void
     */
    private function __construct() {}

    /**
     * Create a new instance of the adapter, if it is available.
     *
     * @return Option<AdapterInterface>
     */
    #[\Override]
    public static function create()
    {
        /** @var Option<AdapterInterface> */
        return Some::create(new self);
    }

    /**
     * Read an environment variable, if it exists.
     *
     * @param  non-empty-string  $name
     * @return Option<string>
     */
    #[\Override]
    public function read(string $name)
    {
        return Option::fromValue(defined($name) ? constant($name) : null);
    }

    /**
     * Write to an environment variable, if possible.
     *
     * @param  non-empty-string  $name
     * @return bool
     */
    #[\Override]
    public function write(string $name, string $value)
    {
        define($name, $value);

        return true;
    }

    /**
     * Delete an environment variable, if possible.
     *
     * @param  non-empty-string  $name
     * @return bool
     */
    #[\Override]
    public function delete(string $name)
    {
        return false;
    }
}
