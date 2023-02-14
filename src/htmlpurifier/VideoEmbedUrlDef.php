<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\htmlpurifier;

use HTMLPurifier_AttrDef_URI;

/**
 * Class VideoEmbedUrlDef
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.9
 */
class VideoEmbedUrlDef extends HTMLPurifier_AttrDef_URI
{
    public function validate($uri, $config, $context)
    {
        $regexp = $config->get('URI.SafeIframeRegexp');
        if ($regexp !== null) {
            if (!preg_match($regexp, $uri)) {
                return false;
            }
        }

        return parent::validate($uri, $config, $context);
    }
}
