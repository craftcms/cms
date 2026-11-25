<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\controllers;

use Craft;
use craft\controllers\ElementsController;
use craft\elements\Address;
use craft\elements\Entry;
use craft\elements\User;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Addresses;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\test\TestCase;
use crafttests\fixtures\SitesFixture;
use RuntimeException;

/**
 * Unit tests for ElementsController
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.1
 */
class ElementsControllerTest extends TestCase
{
    private Addresses $addressesField;
    private EntryType $entryType;
    private Section $section;
    private ?string $originalRequestMethod = null;

    public function _fixtures(): array
    {
        return [
            'sites' => [
                'class' => SitesFixture::class,
            ],
        ];
    }

    /**
     * @see https://github.com/craftcms/cms/issues/20413
     */
    public function testSaveNestedAddressForDerivativeInNonPrimarySite(): void
    {
        $elementsService = Craft::$app->getElements();
        $draftsService = Craft::$app->getDrafts();
        $siteId = (int)Craft::$app->getSites()->getSiteByHandle('testSite1')->id;
        $userId = Craft::$app->getUser()->getId();

        // An entry that isn't saved in the primary site, with an address
        $entry = new Entry([
            'sectionId' => $this->section->id,
            'typeId' => $this->entryType->id,
            'siteId' => $siteId,
            'title' => 'Test Entry',
        ]);
        self::assertTrue($elementsService->saveElement($entry), implode(', ', $entry->getFirstErrors()));

        $address = new Address([
            'fieldId' => $this->addressesField->id,
            'ownerId' => $entry->id,
            'title' => 'Test Address',
            'countryCode' => 'NL',
            'locality' => 'Amsterdam',
            'postalCode' => '1011 AB',
            'addressLine1' => 'Original',
        ]);
        self::assertTrue($elementsService->saveElement($address), implode(', ', $address->getFirstErrors()));

        // Provisional drafts of the entry and address, as the element editors create them
        $entryDraft = $draftsService->createDraft($entry, $userId, provisional: true);
        $addressDraft = $draftsService->createDraft($address, $userId, provisional: true);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = Craft::$app->getRequest();
        $request->setBodyParams([
            'elementId' => $address->id,
            'draftId' => $addressDraft->draftId,
            'provisional' => true,
            'siteId' => $siteId,
            'newOwnerId' => $entryDraft->id,
            'addressLine1' => 'Changed',
        ]);
        $request->getHeaders()->set('Accept', 'application/json');

        $controller = new ElementsController('elements', Craft::$app);
        $controller->enableCsrfValidation = false;
        self::assertTrue($controller->beforeAction($controller->createAction('save-nested-element-for-derivative')));
        $response = $controller->actionSaveNestedElementForDerivative();

        self::assertSame(200, $response->getStatusCode(), (string)json_encode($response->data));

        // The address is now owned by the entry draft, and the canonical entry’s address is untouched
        $draftAddress = Address::find()->ownerId($entryDraft->id)->status(null)->one();
        self::assertNotNull($draftAddress);
        self::assertSame('Changed', $draftAddress->addressLine1);
        self::assertSame($entryDraft->id, $draftAddress->getPrimaryOwnerId());
        self::assertSame($address->id, $draftAddress->getCanonicalId());
        self::assertSame('Original', Address::find()->id($address->id)->status(null)->one()->addressLine1);
    }

    protected function _before(): void
    {
        parent::_before();

        $this->originalRequestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());

        $this->addressesField = new Addresses([
            'name' => 'Test Addresses',
            'handle' => 'testAddresses',
        ]);
        if (!Craft::$app->getFields()->saveField($this->addressesField)) {
            throw new RuntimeException('Could not save Addresses field.');
        }

        $fieldLayout = new FieldLayout(['type' => Entry::class]);
        $tab = new FieldLayoutTab(['name' => 'Content']);
        $tab->setLayout($fieldLayout);
        $tab->setElements([new CustomField($this->addressesField)]);
        $fieldLayout->setTabs([$tab]);

        $this->entryType = new EntryType([
            'name' => 'Test Addresses Owner',
            'handle' => 'testAddressesOwner',
        ]);
        $this->entryType->setFieldLayout($fieldLayout);
        if (!Craft::$app->getEntries()->saveEntryType($this->entryType)) {
            throw new RuntimeException('Could not save entry type.');
        }

        // Only enabled for a non-primary site
        $this->section = new Section([
            'name' => 'Test Addresses Section',
            'handle' => 'testAddressesSection',
            'type' => Section::TYPE_CHANNEL,
        ]);
        $this->section->setEntryTypes([$this->entryType]);
        $this->section->setSiteSettings([
            new Section_SiteSettings([
                'siteId' => Craft::$app->getSites()->getSiteByHandle('testSite1')->id,
                'enabledByDefault' => true,
                'hasUrls' => false,
            ]),
        ]);
        if (!Craft::$app->getEntries()->saveSection($this->section)) {
            throw new RuntimeException('Could not save section.');
        }
    }

    protected function _after(): void
    {
        Craft::$app->getEntries()->deleteSection($this->section);
        Craft::$app->getEntries()->deleteEntryType($this->entryType);
        Craft::$app->getFields()->deleteField($this->addressesField);
        Craft::$app->getUser()->logout(false);

        if ($this->originalRequestMethod === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->originalRequestMethod;
        }

        parent::_after();
    }
}
