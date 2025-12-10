<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\EagerLoadPlan;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\UserQuery;
use craft\models\FieldLayout;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Queries\ElementQuery;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User as UserElement;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\DB as DbFacade;
use Illuminate\Support\Traits\ForwardsCalls;
use Traversable;
use Twig\Markup;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\web\IdentityInterface;
use yii\web\Response;

/**
 * @mixin UserElement
 */
class User implements IdentityInterface, ElementInterface
{
    use ForwardsCalls;

    private UserElement $userElement;

    public function __construct(
        array $config = [],
        UserElement|null $user = null,
    ) {
        $this->userElement = $user ?? new UserElement($config);
    }

    public static function __callStatic(string $name, array $arguments)
    {
        return UserElement::$name($arguments);
    }

    public function __get($name)
    {
        return $this->userElement->$name;
    }

    public function __set($name, $value): void
    {
        $this->userElement->$name = $value;
    }

    public function __isset($name): bool
    {
        return isset($this->userElement->$name);
    }

    public function __call($name, $params)
    {
        return $this->forwardDecoratedCallTo($this->userElement, $name, $params);
    }

    public static function find(): UserQuery
    {
        return new UserQuery(self::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id): ?self
    {
        $user = self::find()
            ->addSelect(['users.password'])
            ->id($id)
            ->status(null)
            ->one();

        if ($user === null) {
            return null;
        }

        // Only accept active users, unless they're being impersonated
        if (
            $user->getStatus() !== UserElement::STATUS_ACTIVE &&
            !Craft::$app->getUser()->getImpersonator()
        ) {
            return null;
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null): ?self
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey(): ?string
    {
        $token = Craft::$app->getUser()->getToken();

        if ($token === null) {
            throw new Exception('No user session token exists.');
        }

        $userAgent = Craft::$app->getRequest()->getUserAgent();

        // The auth key is a combination of the hashed token, its row's UID, and the user agent string
        return Json::encode([
            $token,
            null,
            md5($userAgent),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey): ?bool
    {
        $data = Json::decodeIfJson($authKey);

        if (!is_array($data) || count($data) !== 3 || !isset($data[0], $data[2])) {
            return false;
        }

        [$token, , $userAgent] = $data;

        if (!$this->_validateUserAgent($userAgent)) {
            return false;
        }

        $tokenId = DbFacade::table(Table::SESSIONS)
            ->where('token', $token)
            ->where('userId', $this->id)
            ->value('id');

        if (!$tokenId) {
            return false;
        }

        // Update the session row's dateUpdated value so it doesn't get GC'd
        DbFacade::table(Table::SESSIONS)
            ->where('id', $tokenId)
            ->update([
                'dateUpdated' => now(),
            ]);

        return true;
    }

    /**
     * Validates a cookie's stored user agent against the current request's user agent string,
     * if the 'requireMatchingUserAgentForSession' config setting is enabled.
     */
    private function _validateUserAgent(string $userAgent): bool
    {
        if (!Cms::config()->requireMatchingUserAgentForSession) {
            return true;
        }

        $requestUserAgent = Craft::$app->getRequest()->getUserAgent();

        if (!$requestUserAgent) {
            return false;
        }

        if (!hash_equals($userAgent, md5($requestUserAgent))) {
            Craft::warning('Tried to restore session from the the identity cookie, but the saved user agent (' . $userAgent . ') does not match the current request’s (' . $requestUserAgent . ').', __METHOD__);

            return false;
        }

        return true;
    }

    public function getId(): ?int
    {
        return $this->userElement->getId();
    }

    public function getActionMenuItems(): array
    {
        return $this->userElement->getActionMenuItems();
    }

    public function fields(): array
    {
        return $this->userElement->fields();
    }

    public function extraFields(): array
    {
        return $this->userElement->extraFields();
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        return $this->userElement->toArray($fields, $expand, $recursive);
    }

    public static function get(int|string $id): ?ElementInterface
    {
        return UserElement::get($id);
    }

    public function getUiLabel(): string
    {
        return $this->userElement->getUiLabel();
    }

    public static function displayName(): string
    {
        return UserElement::displayName();
    }

    public static function isSelectable(): bool
    {
        return UserElement::isSelectable();
    }

    public function getIterator(): Traversable
    {
        return $this->userElement->getIterator();
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->userElement->offsetGet($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->userElement->offsetSet($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->userElement->offsetUnset($offset);
    }

    public function getCpEditUrl(): ?string
    {
        return $this->userElement->getCpEditUrl();
    }

    public static function lowerDisplayName(): string
    {
        return UserElement::lowerDisplayName();
    }

    public static function pluralDisplayName(): string
    {
        return UserElement::pluralDisplayName();
    }

    public static function pluralLowerDisplayName(): string
    {
        return UserElement::pluralLowerDisplayName();
    }

    public static function refHandle(): ?string
    {
        return UserElement::refHandle();
    }

    public static function hasDrafts(): bool
    {
        return UserElement::hasDrafts();
    }

    public static function trackChanges(): bool
    {
        return UserElement::trackChanges();
    }

    public static function hasTitles(): bool
    {
        return UserElement::hasTitles();
    }

    public static function hasThumbs(): bool
    {
        return UserElement::hasThumbs();
    }

    public static function hasUris(): bool
    {
        return UserElement::hasUris();
    }

    public static function isLocalized(): bool
    {
        return UserElement::isLocalized();
    }

    public static function hasStatuses(): bool
    {
        return UserElement::hasStatuses();
    }

    public static function findOne(mixed $criteria = null): ?static
    {
        return UserElement::findOne($criteria);
    }

    public static function findAll(mixed $criteria = null): array
    {
        return UserElement::findAll($criteria);
    }

    public static function createCondition(): ElementConditionInterface
    {
        return UserElement::createCondition();
    }

    public static function multiPageSources(): bool
    {
        return UserElement::multiPageSources();
    }

    public static function sources(string $context): array
    {
        return UserElement::sources($context);
    }

    public static function findSource(string $sourceKey, ?string $context): ?array
    {
        return UserElement::findSource($sourceKey, $context);
    }

    public static function sourcePath(string $sourceKey, string $stepKey, ?string $context): ?array
    {
        return UserElement::sourcePath($sourceKey, $stepKey, $context);
    }

    public static function fieldLayouts(?string $source): array
    {
        return UserElement::fieldLayouts($source);
    }

    public static function modifyCustomSource(array $config): array
    {
        return UserElement::modifyCustomSource($config);
    }

    public static function actions(string $source): array
    {
        return UserElement::actions($source);
    }

    public static function exporters(string $source): array
    {
        return UserElement::exporters($source);
    }

    public static function searchableAttributes(): array
    {
        return UserElement::searchableAttributes();
    }

    public static function baseBulkDuplicateAttributes(): array
    {
        return UserElement::baseBulkDuplicateAttributes();
    }

    public static function indexHtml(
        ElementQueryInterface $elementQuery,
        ?array $disabledElementIds,
        array $viewState,
        ?string $sourceKey,
        ?string $context,
        bool $includeContainer,
        bool $selectable,
        bool $sortable,
    ): string {
        return UserElement::indexHtml($elementQuery, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer, $selectable, $sortable);
    }

    public static function indexElementCount(ElementQueryInterface $elementQuery, ?string $sourceKey): int
    {
        return UserElement::indexElementCount($elementQuery, $sourceKey);
    }

    public static function sortOptions(): array
    {
        return UserElement::sortOptions();
    }

    public static function indexViewModes(): array
    {
        return UserElement::indexViewModes();
    }

    public static function tableAttributes(): array
    {
        return UserElement::tableAttributes();
    }

    public static function defaultTableAttributes(string $source): array
    {
        return UserElement::defaultTableAttributes($source);
    }

    public static function cardAttributes(?FieldLayout $fieldLayout = null): array
    {
        return UserElement::cardAttributes();
    }

    public static function defaultCardAttributes(): array
    {
        return UserElement::defaultCardAttributes();
    }

    public static function attributePreviewHtml(array $attribute): mixed
    {
        return UserElement::attributePreviewHtml($attribute);
    }

    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        return UserElement::eagerLoadingMap($sourceElements, $handle);
    }

    public static function baseGqlType(): Type
    {
        return UserElement::baseGqlType();
    }

    public static function gqlScopesByContext(mixed $context): array
    {
        return UserElement::gqlScopesByContext($context);
    }

    public function getIsDraft(): bool
    {
        return $this->userElement->getIsDraft();
    }

    public function getIsRevision(): bool
    {
        return $this->userElement->getIsRevision();
    }

    public function getIsCanonical(): bool
    {
        return $this->userElement->getIsCanonical();
    }

    public function getIsDerivative(): bool
    {
        return $this->userElement->getIsDerivative();
    }

    public function getCanonical(bool $anySite = false): ElementInterface
    {
        return $this->userElement->getCanonical($anySite);
    }

    public function setCanonical(ElementInterface $element): void
    {
        $this->userElement->setCanonical($element);
    }

    public function getCanonicalId(): ?int
    {
        return $this->userElement->getCanonicalId();
    }

    public function setCanonicalId(?int $canonicalId): void
    {
        $this->userElement->setCanonicalId($canonicalId);
    }

    public function getCanonicalUid(): ?string
    {
        return $this->userElement->getCanonicalUid();
    }

    public function getIsUnpublishedDraft(): bool
    {
        return $this->userElement->getIsUnpublishedDraft();
    }

    public function mergeCanonicalChanges(): void
    {
        $this->userElement->mergeCanonicalChanges();
    }

    public function getFieldLayout(): ?FieldLayout
    {
        return $this->userElement->getFieldLayout();
    }

    public function getSite(): Site
    {
        return $this->userElement->getSite();
    }

    public function getLanguage(): string
    {
        return $this->userElement->getLanguage();
    }

    public function getSupportedSites(): array
    {
        return $this->userElement->getSupportedSites();
    }

    public function getUriFormat(): ?string
    {
        return $this->userElement->getUriFormat();
    }

    public function getSearchKeywords(string $attribute): string
    {
        return $this->userElement->getSearchKeywords($attribute);
    }

    public function getRoute(): mixed
    {
        return $this->userElement->getRoute();
    }

    public function getIsHomepage(): bool
    {
        return $this->userElement->getIsHomepage();
    }

    public function getUrl(): ?string
    {
        return $this->userElement->getUrl();
    }

    public function getLink(): ?Markup
    {
        return $this->userElement->getLink();
    }

    public function getCrumbs(): array
    {
        return $this->userElement->getCrumbs();
    }

    public function setUiLabel(?string $label): void
    {
        $this->userElement->setUiLabel($label);
    }

    public function getUiLabelPath(): array
    {
        return $this->userElement->getUiLabelPath();
    }

    public function setUiLabelPath(array $path): void
    {
        $this->userElement->setUiLabelPath($path);
    }

    public function getChipLabelHtml(): string
    {
        return $this->userElement->getChipLabelHtml();
    }

    public function showStatusIndicator(): bool
    {
        return $this->userElement->showStatusIndicator();
    }

    public function getCardTitle(): ?string
    {
        return $this->userElement->getCardTitle();
    }

    public function getCardBodyHtml(): ?string
    {
        return $this->userElement->getCardBodyHtml();
    }

    public function getRef(): ?string
    {
        return $this->userElement->getRef();
    }

    public function createAnother(): ?ElementInterface
    {
        return $this->userElement->createAnother();
    }

    public function canView(UserElement $user): bool
    {
        return $this->userElement->canView($user);
    }

    public function canSave(UserElement $user): bool
    {
        return $this->userElement->canSave($user);
    }

    public function canDuplicate(UserElement $user): bool
    {
        return $this->userElement->canDuplicate($user);
    }

    public function canDuplicateAsDraft(UserElement $user): bool
    {
        return $this->userElement->canDuplicateAsDraft($user);
    }

    public function canCopy(UserElement $user): bool
    {
        return $this->userElement->canCopy($user);
    }

    public function canDelete(UserElement $user): bool
    {
        return $this->userElement->canDelete($user);
    }

    public function canDeleteForSite(UserElement $user): bool
    {
        return $this->userElement->canDeleteForSite($user);
    }

    public function canCreateDrafts(UserElement $user): bool
    {
        return $this->userElement->canCreateDrafts($user);
    }

    public function hasRevisions(): bool
    {
        return $this->userElement->hasRevisions();
    }

    public function prepareEditScreen(Response $response, string $containerId): void
    {
        $this->userElement->prepareEditScreen($response, $containerId);
    }

    public function getPostEditUrl(): ?string
    {
        return $this->userElement->getPostEditUrl();
    }

    public function getCpRevisionsUrl(): ?string
    {
        return $this->userElement->getCpRevisionsUrl();
    }

    public function getAdditionalButtons(): string
    {
        return $this->userElement->getAdditionalButtons();
    }

    public function getAltActions(): array
    {
        return $this->userElement->getAltActions();
    }

    public function getPreviewTargets(): array
    {
        return $this->userElement->getPreviewTargets();
    }

    public function getEnabledForSite(?int $siteId = null): ?bool
    {
        return $this->userElement->getEnabledForSite($siteId);
    }

    public function setEnabledForSite(bool|array $enabledForSite): void
    {
        $this->userElement->setEnabledForSite($enabledForSite);
    }

    public function getRootOwner(): ElementInterface
    {
        return $this->userElement->getRootOwner();
    }

    public function getLocalized(): ElementQueryInterface|ElementQuery|ElementCollection
    {
        return $this->userElement->getLocalized();
    }

    public function getNext(mixed $criteria = false): ?UserElement
    {
        return $this->userElement->getNext($criteria);
    }

    public function getPrev(mixed $criteria = false): ?UserElement
    {
        return $this->userElement->getPrev($criteria);
    }

    public function setNext(ElementInterface|false $element): void
    {
        $this->userElement->setNext($element);
    }

    public function setPrev(ElementInterface|false $element): void
    {
        $this->userElement->setPrev($element);
    }

    public function getParent(): ?ElementInterface
    {
        return $this->userElement->getParent();
    }

    public function getParentUri(): ?string
    {
        return $this->userElement->getParentUri();
    }

    public function setParent(?ElementInterface $parent): void
    {
        $this->userElement->setParent($parent);
    }

    public function getAncestors(?int $dist = null): ElementQueryInterface|ElementQuery|ElementCollection
    {
        return $this->userElement->getAncestors();
    }

    public function getDescendants(?int $dist = null): ElementQueryInterface|ElementQuery|ElementCollection
    {
        return $this->userElement->getDescendants();
    }

    public function getChildren(): ElementQueryInterface|ElementQuery|ElementCollection
    {
        return $this->userElement->getChildren();
    }

    public function getSiblings(): ElementQueryInterface|ElementQuery|ElementCollection
    {
        return $this->userElement->getSiblings();
    }

    public function getPrevSibling(): ?ElementInterface
    {
        return $this->userElement->getPrevSibling();
    }

    public function getNextSibling(): ?ElementInterface
    {
        return $this->userElement->getNextSibling();
    }

    public function getHasDescendants(): bool
    {
        return $this->userElement->getHasDescendants();
    }

    public function getTotalDescendants(): int
    {
        return $this->userElement->getTotalDescendants();
    }

    public function isAncestorOf(ElementInterface $element): bool
    {
        return $this->userElement->isAncestorOf($element);
    }

    public function isDescendantOf(ElementInterface $element): bool
    {
        return $this->userElement->isDescendantOf($element);
    }

    public function isParentOf(ElementInterface $element): bool
    {
        return $this->userElement->isParentOf($element);
    }

    public function isChildOf(ElementInterface $element): bool
    {
        return $this->userElement->isChildOf($element);
    }

    public function isSiblingOf(ElementInterface $element): bool
    {
        return $this->userElement->isSiblingOf($element);
    }

    public function isPrevSiblingOf(ElementInterface $element): bool
    {
        return $this->userElement->isPrevSiblingOf($element);
    }

    public function isNextSiblingOf(ElementInterface $element): bool
    {
        return $this->userElement->isNextSiblingOf($element);
    }

    public function offsetExists($offset): bool
    {
        return $this->userElement->offsetExists($offset);
    }

    public function setAttributesFromRequest(array $values): void
    {
        $this->userElement->setAttributesFromRequest($values);
    }

    public function getAttributeStatus(string $attribute): ?array
    {
        return $this->userElement->getAttributeStatus($attribute);
    }

    public function getOutdatedAttributes(): array
    {
        return $this->userElement->getOutdatedAttributes();
    }

    public function isAttributeOutdated(string $name): bool
    {
        return $this->userElement->isAttributeOutdated($name);
    }

    public function getModifiedAttributes(): array
    {
        return $this->userElement->getModifiedAttributes();
    }

    public function isAttributeModified(string $name): bool
    {
        return $this->userElement->isAttributeModified($name);
    }

    public function isAttributeDirty(string $name): bool
    {
        return $this->userElement->isAttributeDirty($name);
    }

    public function getDirtyAttributes(): array
    {
        return $this->userElement->getDirtyAttributes();
    }

    public function setDirtyAttributes(array $names, bool $merge = true): void
    {
        $this->userElement->setDirtyAttributes($names, $merge);
    }

    public function getIsTitleTranslatable(): bool
    {
        return $this->userElement->getIsTitleTranslatable();
    }

    public function getTitleTranslationDescription(): ?string
    {
        return $this->userElement->getTitleTranslationDescription();
    }

    public function getTitleTranslationKey(): string
    {
        return $this->userElement->getTitleTranslationKey();
    }

    public function getIsSlugTranslatable(): bool
    {
        return $this->userElement->getIsSlugTranslatable();
    }

    public function getSlugTranslationDescription(): ?string
    {
        return $this->userElement->getSlugTranslationDescription();
    }

    public function getSlugTranslationKey(): string
    {
        return $this->userElement->getSlugTranslationKey();
    }

    public function isFieldEmpty(string $handle): bool
    {
        return $this->userElement->isFieldEmpty($handle);
    }

    public function getFieldValues(?array $fieldHandles = null): array
    {
        return $this->userElement->getFieldValues();
    }

    public function getSerializedFieldValues(?array $fieldHandles = null): array
    {
        return $this->userElement->getSerializedFieldValues();
    }

    public function getSerializedFieldValuesForDb(?array $fieldHandles = null): array
    {
        return $this->userElement->getSerializedFieldValuesForDb();
    }

    public function setFieldValues(array $values): void
    {
        $this->userElement->setFieldValues($values);
    }

    public function getFieldValue(string $fieldHandle): mixed
    {
        return $this->userElement->getFieldValue($fieldHandle);
    }

    public function setFieldValue(string $fieldHandle, mixed $value): void
    {
        $this->userElement->setFieldValue($fieldHandle, $value);
    }

    public function setFieldValueFromRequest(string $fieldHandle, mixed $value): void
    {
        $this->userElement->setFieldValueFromRequest($fieldHandle, $value);
    }

    public function getOutdatedFields(): array
    {
        return $this->userElement->getOutdatedFields();
    }

    public function isFieldOutdated(string $fieldHandle): bool
    {
        return $this->userElement->isFieldOutdated($fieldHandle);
    }

    public function getModifiedFields(bool $anySite = false): array
    {
        return $this->userElement->getModifiedFields($anySite);
    }

    public function isFieldModified(string $fieldHandle, bool $anySite = false): bool
    {
        return $this->userElement->isFieldModified($fieldHandle, $anySite);
    }

    public function isFieldDirty(string $fieldHandle): bool
    {
        return $this->userElement->isFieldDirty($fieldHandle);
    }

    public function getDirtyFields(): array
    {
        return $this->userElement->getDirtyFields();
    }

    public function setDirtyFields(array $fieldHandles, bool $merge = true): void
    {
        $this->userElement->setDirtyFields($fieldHandles, $merge);
    }

    public function markAsDirty(): void
    {
        $this->userElement->markAsDirty();
    }

    public function markAsClean(): void
    {
        $this->userElement->markAsClean();
    }

    public function getCacheTags(): array
    {
        return $this->userElement->getCacheTags();
    }

    public function setFieldValuesFromRequest(string $paramNamespace): void
    {
        $this->userElement->setFieldValuesFromRequest($paramNamespace);
    }

    public function getFieldParamNamespace(): ?string
    {
        return $this->userElement->getFieldParamNamespace();
    }

    public function setFieldParamNamespace(string $namespace): void
    {
        $this->userElement->setFieldParamNamespace($namespace);
    }

    public function getFieldContext(): string
    {
        return $this->userElement->getFieldContext();
    }

    public function getGeneratedFieldValues(): array
    {
        return $this->userElement->getGeneratedFieldValues();
    }

    public function setGeneratedFieldValues(array $values): void
    {
        $this->userElement->setGeneratedFieldValues($values);
    }

    public function getInvalidNestedElementIds(): array
    {
        return $this->userElement->getInvalidNestedElementIds();
    }

    public function addInvalidNestedElementIds(array $ids): void
    {
        $this->userElement->addInvalidNestedElementIds($ids);
    }

    public function hasEagerLoadedElements(string $handle): bool
    {
        return $this->userElement->hasEagerLoadedElements($handle);
    }

    public function getEagerLoadedElements(string $handle): ?ElementCollection
    {
        return $this->userElement->getEagerLoadedElements($handle);
    }

    public function setEagerLoadedElements(string $handle, array $elements, EagerLoadPlan $plan): void
    {
        $this->userElement->setEagerLoadedElements($handle, $elements, $plan);
    }

    public function setLazyEagerLoadedElements(string $handle, bool $value = true): void
    {
        $this->userElement->setLazyEagerLoadedElements($handle, $value);
    }

    public function getEagerLoadedElementCount(string $handle): ?int
    {
        return $this->userElement->getEagerLoadedElementCount($handle);
    }

    public function setEagerLoadedElementCount(string $handle, int $count): void
    {
        $this->userElement->setEagerLoadedElementCount($handle, $count);
    }

    public function getIsFresh(): bool
    {
        return $this->userElement->getIsFresh();
    }

    public function setIsFresh(bool $isFresh = true): void
    {
        $this->userElement->setIsFresh();
    }

    public function setRevisionCreatorId(?int $creatorId): void
    {
        $this->userElement->setRevisionCreatorId($creatorId);
    }

    public function setRevisionNotes(?string $notes): void
    {
        $this->userElement->setRevisionNotes($notes);
    }

    public function getCurrentRevision(): ?ElementInterface
    {
        return $this->userElement->getCurrentRevision();
    }

    public function getIsCrossSiteCopyable(): bool
    {
        return $this->userElement->getIsCrossSiteCopyable();
    }

    public function getHtmlAttributes(string $context): array
    {
        return $this->userElement->getHtmlAttributes($context);
    }

    public function getAttributeHtml(string $attribute): string
    {
        return $this->userElement->getAttributeHtml($attribute);
    }

    public function getInlineAttributeInputHtml(string $attribute): string
    {
        return $this->userElement->getInlineAttributeInputHtml($attribute);
    }

    public function getSidebarHtml(bool $static): string
    {
        return $this->userElement->getSidebarHtml($static);
    }

    public function getMetadata(): array
    {
        return $this->userElement->getMetadata();
    }

    public function getGqlTypeName(): string
    {
        return $this->userElement->getGqlTypeName();
    }

    public function beforeSave(bool $isNew): bool
    {
        return $this->userElement->beforeSave($isNew);
    }

    public function afterSave(bool $isNew): void
    {
        $this->userElement->afterSave($isNew);
    }

    public function afterPropagate(bool $isNew): void
    {
        $this->userElement->afterPropagate();
    }

    public function beforeDelete(): bool
    {
        return $this->userElement->beforeDelete();
    }

    public function afterDelete(): void
    {
        $this->userElement->afterDelete();
    }

    public function beforeDeleteForSite(): bool
    {
        return $this->userElement->beforeDeleteForSite();
    }

    public function afterDeleteForSite(): void
    {
        $this->userElement->afterDeleteForSite();
    }

    public function beforeRestore(): bool
    {
        return $this->userElement->beforeRestore();
    }

    public function afterRestore(): void
    {
        $this->userElement->afterRestore();
    }

    public function beforeMoveInStructure(int $structureId): bool
    {
        return $this->userElement->beforeMoveInStructure($structureId);
    }

    public function afterMoveInStructure(int $structureId): void
    {
        $this->userElement->afterMoveInStructure($structureId);
    }

    public function __toString(): string
    {
        return $this->userElement->__toString();
    }

    public function render(array $variables = []): Markup
    {
        return $this->userElement->render($variables);
    }

    public static function instance($refresh = false)
    {
        return UserElement::instance($refresh);
    }

    public static function statuses(): array
    {
        return UserElement::statuses();
    }

    public function getStatus(): ?string
    {
        return $this->userElement->getStatus();
    }

    public function getThumbHtml(int $size): ?string
    {
        return $this->userElement->getThumbHtml($size);
    }
}
