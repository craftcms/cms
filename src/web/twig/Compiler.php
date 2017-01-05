<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\twig;

use craft\helpers\Template;

/**
 * Twig Compiler class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Compiler extends \Twig_Compiler
{
    /**
     * @inheritdoc
     */
    public function raw($string)
    {
        // Use our own Template::getAttribute(). This is honestly the least hacky way right now.
        /** @noinspection StrNcmpUsedAsStrPosInspection */
        if (strncmp($string, 'twig_get_attribute(', 19) === 0) {
            $string = Template::class.'::attribute('.substr($string, 19);
        }

        return parent::raw($string);
    }
}
