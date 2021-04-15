<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use DateTime;

/**
 * AssetIndexData model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class AssetIndexData extends Model
{
    /**
     * @var int|null ID
     */
    public ?int $id;

    /**
     * @var int|null Volume ID
     */
    public ?int $volumeId;

    /**
     * @var string|null Session ID
     */
    public ?string $sessionId;

    /**
     * @var string|null URI
     */
    public ?string $uri;

    /**
     * @var int|null Size
     */
    public ?int $size;

    /**
     * @var int|null Record ID
     */
    public ?int $recordId;

    /**
     * @var bool|null Whether the path was skipped
     */
    public ?bool $isSkipped;

    /**
     * @var DateTime|null The index timestamp
     */
    public ?DateTime $timestamp;

    /**
     * @var bool is Dir
     */
    public bool $isDir;

    /**
     * @var bool Is completed
     */
    public bool $completed = false;

    /**
     * @var bool In progress
     */
    public bool $inProgress = false;

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'volumeId', 'number', 'size', 'recordId'], 'number', 'integerOnly' => true];
        $rules[] = [['completed', 'inProgress'], 'boolean'];
        return $rules;
    }

    /**
     * Use the translated volume name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->uri;
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'timestamp';

        return $attributes;
    }


}
