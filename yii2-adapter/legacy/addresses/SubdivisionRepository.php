<?php

namespace craft\addresses;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 4.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Address\Repositories\SubdivisionRepository} instead.
     */
    class SubdivisionRepository extends \CommerceGuys\Addressing\Subdivision\SubdivisionRepository
    {
    }
}

class_alias(\CraftCms\Cms\Address\Repositories\SubdivisionRepository::class, SubdivisionRepository::class);
