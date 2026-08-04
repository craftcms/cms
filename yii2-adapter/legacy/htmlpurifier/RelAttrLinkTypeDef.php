<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\htmlpurifier;

use HTMLPurifier_AttrDef_HTML_LinkTypes;

/**
 * Class VideoEmbedUrlDef
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.6.11
 */
class RelAttrLinkTypeDef extends HTMLPurifier_AttrDef_HTML_LinkTypes
{
    public function validate($string, $config, $context)
    {
        // allow any value in the "rel" attribute
        $allowed = $config->get('Attr.' . $this->name);
        if (isset($allowed['*']) && $allowed['*']) {
            return $string;
        }

        return parent::validate($string, $config, $context);
    }
}
