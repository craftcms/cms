<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * RegisterDeletionSubjectTypesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class RegisterDeletionSubjectTypesEvent extends Event
{
    /**
     * @var array<string, array{find: callable(int|string): mixed, label?: callable(mixed): string, requireAdmin?: bool}>
     * The subject types that can have deletion blockers, indexed by a key such as `volume`.
     *
     * Each type has the following keys:
     *
     * - `find` – A callable that returns the subject for an ID (or handle or UID), or `null` if it doesn’t exist
     *   or the current user can’t delete it.
     * - `label` _(optional)_ – A callable that returns the subject’s name. Defaults to its UI label or `name`.
     * - `requireAdmin` _(optional)_ – Whether only admins can view its blockers. Defaults to `true`.
     */
    public array $types = [];
}
