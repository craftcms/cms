<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue;

/**
 * ProfilableJobInterface aids in aggregating profile results for queue jobs.
 *
 * It should be implemented by jobs which could have wide-varying runtime lengths based on their configuration.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.6.0
 */
interface ProfilableJobInterface
{
    /**
     * Returns the job attributes that should be used to semi-uniquely identify this job, based on its configuration,
     * so that its profile results can be compared with recent similarly-configured jobs.
     *
     * @return array
     */
    public function getProfileAttributes(): array;
}
