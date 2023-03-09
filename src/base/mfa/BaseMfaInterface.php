<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base\mfa;

interface BaseMfaInterface
{
    /**
     * Name of the MFA authentication method
     *
     * @return string
     */
    public static function displayName(): string;

    /**
     * Description of the MFA authentication method
     *
     * @return string
     */
    public static function getDescription(): string;

    /**
     * Get html for MFA verification inputs
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
     * Returns the array of field names used in the MFA verification form
     *
     * @return array|null
     */
    public function getFields(): ?array;

    /**
     * Verify provided MFA code
     *
     * @param array $data
     * @return bool
     */
    public function verify(array $data): bool;
}
