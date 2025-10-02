<?php

namespace craft\addresses;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 4.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Addresses\Repositories\SubdivisionRepository} instead.
     */
    class SubdivisionRepository extends \CommerceGuys\Addressing\Subdivision\SubdivisionRepository
    {
    }
}

class_alias(\CraftCms\Cms\Addresses\Repositories\SubdivisionRepository::class, SubdivisionRepository::class);
