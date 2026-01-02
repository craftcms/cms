<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig;

use Twig\Sandbox\SecurityPolicyInterface;

/**
 * Security policy
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.17.0
 */
class SecurityPolicy implements SecurityPolicyInterface
{
    public function checkSecurity($tags, $filters, $functions): void
    {
    }

    public function checkMethodAllowed($obj, $method): void
    {
    }

    public function checkPropertyAllowed($obj, $property): void
    {
    }
}
