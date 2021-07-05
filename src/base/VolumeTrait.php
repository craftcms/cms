<?php
declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * VolumeTrait
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
trait VolumeTrait
{
    /**
     * @var string|null Name
     */
    public ?string $name = null;

    /**
     * @var string|null Handle
     */
    public ?string $handle = null;

    /**
     * @var bool|null Whether the volume has a public URL
     */
    public bool $hasUrls = false;

    /**
     * @var string|null The volume’s URL
     */
    public ?string $url = null;

    /**
     * @var string Title translation method
     * @since 3.6.0
     */
    public string $titleTranslationMethod = Field::TRANSLATION_METHOD_SITE;

    /**
     * @var string|null Title translation key format
     * @since 3.6.0
     */
    public ?string $titleTranslationKeyFormat = null;

    /**
     * @var int|null Sort order
     */
    public ?int $sortOrder = null;

    /**
     * @var int|null Field layout ID
     */
    public ?int $fieldLayoutId = null;

    /**
     * @var string|null UID
     */
    public ?string $uid = null;
}
