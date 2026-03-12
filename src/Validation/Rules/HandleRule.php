<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

use function CraftCms\Cms\t;

class HandleRule implements ValidationRule
{
    public static string $handlePattern = '[a-zA-Z][a-zA-Z0-9_]*';

    public static array $baseReservedWords = [
        'attribute',
        'attributeLabels',
        'attributeNames',
        'attributes',
        'dateCreated',
        'dateUpdated',
        'errors',
        'false',
        'fields',
        'handle',
        'id',
        'n',
        'name',
        'no',
        'rules',
        'this',
        'true',
        'uid',
        'y',
        'yes',
    ];

    public function __construct(
        public array $reservedWords = [],
    ) {}

    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match(sprintf('/^%s$/', self::$handlePattern), (string) $value)) {
            $fail(t('“{handle}” isn’t a valid handle.'));

            return;
        }

        $reservedWords = array_merge($this->reservedWords, self::$baseReservedWords);
        $reservedWords = array_map(strtolower(...), $reservedWords);

        if (in_array(strtolower((string) $value), $reservedWords, true)) {
            $fail(t('“{handle}” is a reserved word.', ['handle' => (string) $value]));
        }
    }
}
