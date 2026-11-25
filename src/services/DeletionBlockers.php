<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Chippable;
use craft\base\ElementInterface;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\deletionblockers\DeletionBlockerInterface;
use craft\elements\deletionblockers\EntryTypeUsageBlocker;
use craft\elements\deletionblockers\FieldUsageBlocker;
use craft\elements\deletionblockers\FilesystemUsageBlocker;
use craft\elements\deletionblockers\SiteSectionsBlocker;
use craft\elements\deletionblockers\SourceUsageBlocker;
use craft\elements\deletionblockers\TrashedElementsBlocker;
use craft\elements\deletionblockers\UserGroupUsageBlocker;
use craft\elements\deletionblockers\UserPhotoVolumeBlocker;
use craft\elements\ElementCollection;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\events\DefineDeletionBlockersEvent;
use craft\events\RegisterDeletionSubjectTypesEvent;
use craft\fields\Assets as AssetsField;
use craft\fields\Categories as CategoriesField;
use craft\fields\Entries as EntriesField;
use craft\fields\Tags as TagsField;
use craft\models\Section;
use yii\base\Component;
use yii\base\InvalidArgumentException;

/**
 * Deletion Blockers service.
 *
 * Collects the blockers that should be addressed before something is deleted in the control panel, such as elements,
 * a field, a volume, or anything a plugin registers.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getDeletionBlockers()|`Craft::$app->getDeletionBlockers()`]].
 *
 * Element types define their own blockers with [[\craft\base\ElementInterface::deletionBlockers()]], and
 * [[getElementBlockers()]] adds the ones from [[EVENT_DEFINE_BLOCKERS]] handlers.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class DeletionBlockers extends Component
{
    /**
     * @event RegisterDeletionSubjectTypesEvent The event that is triggered when registering the subject types that
     * can have deletion blockers.
     *
     * ```php
     * use craft\events\RegisterDeletionSubjectTypesEvent;
     * use craft\services\DeletionBlockers;
     * use yii\base\Event;
     *
     * Event::on(DeletionBlockers::class, DeletionBlockers::EVENT_REGISTER_SUBJECT_TYPES, function(RegisterDeletionSubjectTypesEvent $event) {
     *     $event->types['my-plugin-thing'] = [
     *         'find' => fn(int|string $id) => MyPlugin::getInstance()->getThings()->getThingById((int)$id),
     *     ];
     * });
     * ```
     */
    public const EVENT_REGISTER_SUBJECT_TYPES = 'registerSubjectTypes';

    /**
     * @event DefineDeletionBlockersEvent The event that is triggered when defining the blockers that should be
     * addressed before a subject is deleted.
     *
     * ```php
     * use craft\events\DefineDeletionBlockersEvent;
     * use craft\services\DeletionBlockers;
     * use yii\base\Event;
     *
     * Event::on(DeletionBlockers::class, DeletionBlockers::EVENT_DEFINE_BLOCKERS, function(DefineDeletionBlockersEvent $event) {
     *     // Elements are passed as an ElementCollection, with the type `element`
     *     if ($event->type === 'volume') {
     *         $event->blockers[] = new MyVolumeBlocker($event->subject);
     *     }
     * });
     * ```
     */
    public const EVENT_DEFINE_BLOCKERS = 'defineBlockers';

    /**
     * @var array<string, array{find: callable(int|string): mixed, label?: callable(mixed): string, requireAdmin?: bool}>
     */
    private array $_subjectTypes;

    /**
     * Returns the subject types that can have deletion blockers, indexed by their keys.
     *
     * @return array<string, array{find: callable(int|string): mixed, label?: callable(mixed): string, requireAdmin?: bool}>
     */
    public function getSubjectTypes(): array
    {
        if (!isset($this->_subjectTypes)) {
            $event = new RegisterDeletionSubjectTypesEvent([
                'types' => $this->coreSubjectTypes(),
            ]);
            $this->trigger(self::EVENT_REGISTER_SUBJECT_TYPES, $event);
            $this->_subjectTypes = $event->types;
        }

        return $this->_subjectTypes;
    }

    /**
     * Returns the subject to be deleted, or `null` if it doesn’t exist.
     *
     * @param string $type The subject type, such as `volume`
     * @param int|string $id The subject’s ID (or handle or UID, depending on the type)
     * @throws InvalidArgumentException if the type isn’t registered
     */
    public function findSubject(string $type, int|string $id): mixed
    {
        return $this->subjectType($type)['find']($id);
    }

    /**
     * Returns the name of the subject to be deleted.
     *
     * @param string $type The subject type, such as `volume`
     * @param mixed $subject The subject
     * @throws InvalidArgumentException if the type isn’t registered
     */
    public function getSubjectLabel(string $type, mixed $subject): string
    {
        $config = $this->subjectType($type);

        return match (true) {
            isset($config['label']) => $config['label']($subject),
            $subject instanceof Chippable => $subject->getUiLabel(),
            is_object($subject) && isset($subject->name) => (string)$subject->name,
            default => '',
        };
    }

    /**
     * Returns whether only admins can view the blockers for a subject type.
     *
     * @throws InvalidArgumentException if the type isn’t registered
     */
    public function getRequiresAdmin(string $type): bool
    {
        return $this->subjectType($type)['requireAdmin'] ?? true;
    }

    /**
     * Returns the blockers that should be addressed before a subject is deleted.
     *
     * Only active blockers apply; see [[DeletionBlockerInterface::isActive()]].
     *
     * @param string $type The subject type, such as `volume`
     * @param mixed $subject The subject to be deleted
     * @return DeletionBlockerInterface[]
     */
    public function getBlockers(string $type, mixed $subject): array
    {
        $blockers = match ($type) {
            'category-group' => [
                new TrashedElementsBlocker(
                    Category::class,
                    ['groupId' => $subject->id],
                    fn(int $num) => Craft::t('app', 'The category group contains {num, number} {num, plural, =1{category} other{categories}}.', ['num' => $num]),
                ),
                new SourceUsageBlocker(
                    CategoriesField::class,
                    ["group:$subject->uid"],
                    $subject->name,
                    Table::CATEGORIES,
                    ['groupId' => $subject->id],
                    fn(int $num) => Craft::t('app', 'The category group is selected as a source for {num, number} {num, plural, =1{Categories field} other{Categories fields}}.', ['num' => $num]),
                ),
            ],
            'entry-type' => [
                new EntryTypeUsageBlocker($subject),
                new TrashedElementsBlocker(
                    Entry::class,
                    ['typeId' => $subject->id],
                    fn(int $num) => Craft::t('app', '{num, number} {num, plural, =1{entry uses} other{entries use}} the entry type.', ['num' => $num]),
                ),
            ],
            'field' => [
                new FieldUsageBlocker($subject),
            ],
            'filesystem' => [
                new FilesystemUsageBlocker($subject),
            ],
            'section' => [
                new TrashedElementsBlocker(
                    Entry::class,
                    ['sectionId' => $subject->id],
                    fn(int $num) => Craft::t('app', 'The section contains {num, number} {num, plural, =1{entry} other{entries}}.', ['num' => $num]),
                ),
                new SourceUsageBlocker(
                    EntriesField::class,
                    $subject->type === Section::TYPE_SINGLE ? ["section:$subject->uid", 'singles'] : ["section:$subject->uid"],
                    $subject->name,
                    Table::ENTRIES,
                    ['sectionId' => $subject->id],
                    fn(int $num) => Craft::t('app', 'The section is selected as a source for {num, number} {num, plural, =1{Entries field} other{Entries fields}}.', ['num' => $num]),
                ),
            ],
            'site' => [
                new SiteSectionsBlocker($subject),
            ],
            'tag-group' => [
                new TrashedElementsBlocker(
                    Tag::class,
                    ['groupId' => $subject->id],
                    fn(int $num) => Craft::t('app', 'The tag group contains {num, number} {num, plural, =1{tag} other{tags}}.', ['num' => $num]),
                ),
                new SourceUsageBlocker(
                    TagsField::class,
                    ["taggroup:$subject->uid"],
                    $subject->name,
                    Table::TAGS,
                    ['groupId' => $subject->id],
                    fn(int $num) => Craft::t('app', 'The tag group is selected as a source for {num, number} {num, plural, =1{Tags field} other{Tags fields}}.', ['num' => $num]),
                ),
            ],
            'user-group' => [
                new UserGroupUsageBlocker($subject),
            ],
            'volume' => [
                new TrashedElementsBlocker(
                    Asset::class,
                    ['volumeId' => $subject->id],
                    fn(int $num) => Craft::t('app', 'The volume contains {num, number} {num, plural, =1{asset} other{assets}}.', ['num' => $num]),
                ),
                new SourceUsageBlocker(
                    AssetsField::class,
                    ["volume:$subject->uid"],
                    $subject->name,
                    Table::ASSETS,
                    ['volumeId' => $subject->id],
                    fn(int $num) => Craft::t('app', 'The volume is selected as a source for {num, number} {num, plural, =1{Assets field} other{Assets fields}}.', ['num' => $num]),
                ),
                new UserPhotoVolumeBlocker($subject),
            ],
            default => [],
        };

        return $this->defineBlockers($type, $subject, $blockers);
    }

    /**
     * Returns the blockers that should be addressed before elements are deleted.
     *
     * These are the element type’s own blockers (see [[ElementInterface::deletionBlockers()]]), followed by any that
     * [[EVENT_DEFINE_BLOCKERS]] handlers add for the `element` type.
     *
     * @param class-string<ElementInterface> $elementType
     * @param ElementCollection $elements The elements to be deleted
     * @param bool $hardDelete Whether the elements will be hard-deleted
     * @return DeletionBlockerInterface[]
     */
    public function getElementBlockers(string $elementType, ElementCollection $elements, bool $hardDelete): array
    {
        return $this->defineBlockers('element', $elements, $elementType::deletionBlockers($elements, $hardDelete), $hardDelete);
    }

    /**
     * Prepares the active blockers for `Craft.ElementDeletionManager` and `Craft.DeletionBlockersModal`.
     *
     * A blocker’s details start out expanded when it has an `$expandDetails` property set to `true`.
     *
     * @param DeletionBlockerInterface[] $blockers
     * @return array{summary: string, details: string|null, actions: array, expanded: bool}[]
     */
    public function getBlockerData(array $blockers): array
    {
        return array_values(array_map(
            fn(DeletionBlockerInterface $blocker) => [
                'summary' => $blocker->getSummary(),
                'details' => $blocker->getDetails(),
                'actions' => $blocker->getActions(),
                'expanded' => property_exists($blocker, 'expandDetails') && $blocker->expandDetails,
            ],
            array_filter($blockers, fn(DeletionBlockerInterface $blocker) => $blocker->isActive()),
        ));
    }

    /**
     * @param DeletionBlockerInterface[] $blockers
     * @return DeletionBlockerInterface[]
     */
    private function defineBlockers(string $type, mixed $subject, array $blockers, bool $hardDelete = false): array
    {
        // Fire a 'defineBlockers' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_BLOCKERS)) {
            $event = new DefineDeletionBlockersEvent([
                'type' => $type,
                'subject' => $subject,
                'hardDelete' => $hardDelete,
                'blockers' => $blockers,
            ]);
            $this->trigger(self::EVENT_DEFINE_BLOCKERS, $event);
            $blockers = $event->blockers;
        }

        return $blockers;
    }

    /**
     * @return array{find: callable(int|string): mixed, label?: callable(mixed): string, requireAdmin?: bool}
     */
    private function subjectType(string $type): array
    {
        return $this->getSubjectTypes()[$type] ?? throw new InvalidArgumentException("Invalid deletion subject type: $type");
    }

    /**
     * @return array<string, array{find: callable(int|string): mixed, label?: callable(mixed): string, requireAdmin?: bool}>
     */
    private function coreSubjectTypes(): array
    {
        return [
            'category-group' => ['find' => fn($id) => Craft::$app->getCategories()->getGroupById((int)$id)],
            'entry-type' => ['find' => fn($id) => Craft::$app->getEntries()->getEntryTypeById((int)$id)],
            'field' => ['find' => fn($id) => Craft::$app->getFields()->getFieldById((int)$id)],
            'filesystem' => ['find' => fn($id) => Craft::$app->getFs()->getFilesystemByHandle((string)$id)],
            'global-set' => ['find' => fn($id) => Craft::$app->getGlobals()->getSetById((int)$id)],
            'graphql-schema' => ['find' => fn($id) => Craft::$app->getGql()->getSchemaById((int)$id)],
            'graphql-token' => ['find' => fn($id) => Craft::$app->getGql()->getTokenById((int)$id)],
            'image-transform' => ['find' => fn($id) => Craft::$app->getImageTransforms()->getTransformById((int)$id)],
            'route' => [
                'find' => fn($id) => Craft::$app->getProjectConfig()->get(ProjectConfig::PATH_ROUTES . '.' . $id),
                'label' => fn(array $route) => (string)($route['uriPattern'] ?? ''),
            ],
            'section' => ['find' => fn($id) => Craft::$app->getEntries()->getSectionById((int)$id)],
            'site' => ['find' => fn($id) => Craft::$app->getSites()->getSiteById((int)$id, true)],
            'site-group' => ['find' => fn($id) => Craft::$app->getSites()->getGroupById((int)$id)],
            'tag-group' => ['find' => fn($id) => Craft::$app->getTags()->getTagGroupById((int)$id)],
            'user-group' => ['find' => fn($id) => Craft::$app->getUserGroups()->getGroupById((int)$id)],
            'volume' => ['find' => fn($id) => Craft::$app->getVolumes()->getVolumeById((int)$id)],
        ];
    }
}
