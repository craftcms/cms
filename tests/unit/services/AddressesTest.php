<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\elements\Address;
use craft\test\TestCase;

/**
 * Unit tests for the addresses service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
class AddressesTest extends TestCase
{
    /**
     *
     */
    public function testFormatAddress(): void
    {
        $address = new Address([
            'firstName' => 'Wile',
            'lastName' => 'Coyote',
            'fullName' => 'Wile E. Coyote',
            'countryCode' => 'US',
            'administrativeArea' => 'CA',
            'postalCode' => '91505',
            'addressLine1' => '123 Acme Ln',
        ]);

        $addressesService = Craft::$app->getAddresses();

        $formatted = $addressesService->formatAddress($address);
        $this->assertStringContainsString('<span class="given-name">Wile E. Coyote</span>', $formatted);

        $formatted = $addressesService->formatAddress($address, ['html' => false]);
        $this->assertStringContainsString('Wile E. Coyote', $formatted);
        $this->assertStringNotContainsString('<span', $formatted);
    }
}
