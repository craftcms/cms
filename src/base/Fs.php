<?php
declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use craft\validators\HandleValidator;

/**
 * Fs is the base filesystem class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class Fs extends SavableComponent implements FsInterface
{
    use FsTrait;

    /* @since 4.0.0 */
    public const CONFIG_MIMETYPE = 'mimetype';
    /* @since 4.0.0 */
    public const CONFIG_VISIBILITY = 'visibility';

    /* @since 4.0.0 */
    public const VISIBILITY_DEFAULT = 'default';
    /* @since 4.0.0 */
    public const VISIBILITY_HIDDEN = 'hidden';
    /* @since 4.0.0 */
    public const VISIBILITY_PUBLIC = 'public';

    /**
     * @inheritdoc
     */
    public function getRootUrl(): ?string
    {
        if (!$this->hasUrls) {
            return null;
        }

        return rtrim(Craft::parseEnv($this->url), '/') . '/';
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => [
                'dateCreated',
                'dateUpdated',
                'edit',
                'id',
                'title',
                'uid',
            ],
        ];

        return $rules;
    }
}
