<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base\auth;

interface BaseAuthInterface
{
    /**
     * Name of the 2FA authentication method
     *
     * @return string
     */
    public static function displayName(): string;

    /**
     * Description of the 2FA authentication method
     *
     * @return string
     */
    public static function getDescription(): string;

    /**
     * Get html for 2FA verification inputs
     *
     * @param string $html
     * @param array $options
     * @return string
     */
    public function getInputHtml(string $html = '', array $options = []): string;

    /**
     * Returns all the fields with an additional namespace key
     *
     * @return array
     */
    public function getNamespacedFields(): array;

    /**
     * Returns the array of field names used in the 2FA verification form
     *
     * @return array|null
     */
    public function getFields(): ?array;

    /**
     * Verify provided 2FA code
     *
     * @param array $data
     * @return bool
     */
    public function verify(array $data): bool;
}
