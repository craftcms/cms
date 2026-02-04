<?php

declare(strict_types=1);

namespace CraftCms\Cms\Deprecator\Models;

use Carbon\Carbon;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use CraftCms\Cms\Support\Str;

/**
 * @property int $id
 * @property string $key
 * @property string $fingerprint
 * @property Carbon $lastOccurrence
 * @property string $file
 * @property int $line
 * @property string $message
 * @property string $traces
 */
final class DeprecationError extends BaseModel
{
    use HasUid;

    protected $table = Table::DEPRECATIONERRORS;

    #[\Override]
    protected function casts(): array
    {
        return [
            'line' => 'int',
            'lastOccurrence' => 'datetime',
            'traces' => 'json',
        ];
    }

    /**
     * Laravel tries to determine this automatically by checking the database connection.
     * However, we don't always have a connection yet when logging deprecation errors.
     */
    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }

    public function getOriginHtml(): string
    {
        $html = Str::replace('/', '/<wbr/>', $this->file);

        if ($this->line) {
            $html .= ':'.$this->line;
        }

        return $html;
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'message' => Str::markdown($this->message, [
                'inlineOnly' => true,
                'encode' => true,
            ]),
            'origin' => $this->getOriginHtml(),
            'lastOccurrence' => $this->lastOccurrence,
        ];
    }
}
