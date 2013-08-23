<?php
namespace Craft;

/**
 * Handles entry tasks
 */
class EntriesController extends BaseController
{
	/**
	 * Edit an entry.
	 *
	 * @param array $variables
	 * @throws HttpException
	 */
	public function actionEditEntry(array $variables = array())
	{
		$this->_prepEditEntryVariables($variables);

		// Page title w/ revision label
		if (Craft::hasPackage(CraftPackage::PublishPro))
		{
			switch ($variables['entry']->getClassHandle())
			{
				case 'EntryDraft':
				{
					$variables['revisionLabel'] = Craft::t('Draft {id}', array('id' => $variables['draftId']));
					break;
				}

				case 'EntryVersion':
				{
					$variables['revisionLabel'] = Craft::t('Version {id}', array('id' => $variables['versionId']));
					break;
				}

				default:
				{
					$variables['revisionLabel'] = Craft::t('Current');
				}
			}
		}

		if (!$variables['entry']->id)
		{
			$variables['title'] = Craft::t('Create a new entry');
		}
		else
		{
			$variables['title'] = $variables['entry']->title;

			if (Craft::hasPackage(CraftPackage::PublishPro) && $variables['entry']->getClassHandle() != 'Entry')
			{
				$variables['title'] .= ' <span class="hidden">('.$variables['revisionLabel'].')</span>';
			}
		}

		// Breadcrumbs
		$variables['crumbs'] = array(
			array('label' => Craft::t('Entries'), 'url' => UrlHelper::getUrl('entries'))
		);

		if (Craft::hasPackage(CraftPackage::PublishPro))
		{
			// Not really necessary, but it's nice to see that section name...
			$variables['crumbs'][] = array('label' => $variables['section']->name, 'url' => UrlHelper::getUrl('entries'));
		}

		// Multiple entry types?
		$entryTypes = $variables['section']->getEntryTypes();

		if (count($entryTypes) > 1)
		{
			$variables['showEntryTypes'] = true;

			foreach ($entryTypes as $entryType)
			{
				$variables['entryTypeOptions'][] = array('label' => Craft::t($entryType->name), 'value' => $entryType->id);
			}

			craft()->templates->includeJsResource('js/EntryTypeSwitcher.js');
			craft()->templates->includeJs('new Craft.EntryTypeSwitcher();');
		}
		else
		{
			$variables['showEntryTypes'] = false;
		}

		// Enable preview mode?
		$variables['showPreviewBtn'] = false;

		if (!craft()->request->isMobileBrowser(true) && $variables['section']->hasUrls)
		{
			// Make sure the section's template actually exists
			$templatesPath = craft()->path->getTemplatesPath();
			craft()->path->setTemplatesPath(craft()->path->getSiteTemplatesPath());

			try
			{
				$templateExists = (bool) craft()->templates->findTemplate($variables['section']->template);
			}
			catch (TemplateLoaderException $e)
			{
				$templateExists = false;
			}

			craft()->path->setTemplatesPath($templatesPath);

			if ($templateExists)
			{
				craft()->templates->includeJsResource('js/EntryPreviewMode.js');
				craft()->templates->includeJs('new Craft.EntryPreviewMode('.JsonHelper::encode($variables['entry']->getUrl()).', "'.$variables['entry']->locale.'");');
				$variables['showPreviewBtn'] = true;
			}
		}

		// Render the template!
		craft()->templates->includeCssResource('css/entry.css');
		$this->renderTemplate('entries/_edit', $variables);
	}

	public function actionSwitchEntryType()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$variables['sectionId'] = craft()->request->getRequiredPost('sectionId');
		$variables['entry'] = $this->_populateEntryModel();
		$variables['showEntryTypes'] = true;

		$this->_prepEditEntryVariables($variables);

		$tabsHtml = '<ul>';

		foreach ($variables['tabs'] as $tabId => $tab)
		{
			$tabsHtml .= '<li><a id="tab-'.$tabId.'" class="tab'.(isset($tab['class']) ? ' '.$tab['class'] : '').'" href="'.$tab['url'].'">'.$tab['label'].'</a></li>';
		}

		$tabsHtml .= '</ul>';

		$this->returnJson(array(
			'tabsHtml'   => $tabsHtml,
			'fieldsHtml' => craft()->templates->render('entries/_fields', $variables),
			'headHtml'   => craft()->templates->getHeadHtml(),
			'footHtml'   => craft()->templates->getFootHtml(),
		));
	}

	/**
	 * Previews an entry.
	 */
	public function actionPreviewEntry()
	{
		$this->requirePostRequest();

		craft()->setLanguage(craft()->request->getPost('locale'));

		$entry = $this->_populateEntryModel();

		if (!$entry->postDate)
		{
			$entry->postDate = new DateTime();
		}

		$section = $entry->getSection();

		if ($section)
		{
			$this->renderTemplate($section->template, array(
				'entry' => $entry
			));
		}

		craft()->end();
	}

	/**
	 * Saves an entry.
	 */
	public function actionSaveEntry()
	{
		$this->requirePostRequest();

		$entry = $this->_populateEntryModel();

		if (!$entry->id)
		{
			// Make sure the user is allowed to create entries in this section
			craft()->userSession->requirePermission('createEntries:'.$entry->sectionId);
		}
		else
		{
			// Make sure the user is allowed to edit entries in this section
			craft()->userSession->requirePermission('editEntries:'.$entry->sectionId);
		}

		if (craft()->entries->saveEntry($entry))
		{
			if (craft()->request->isAjaxRequest())
			{
				$return['success']   = true;
				$return['title']     = $entry->title;
				$return['cpEditUrl'] = $entry->getCpEditUrl();
				$return['author']    = $entry->getAuthor()->getAttributes();
				$return['postDate']  = ($entry->postDate ? $entry->postDate->localeDate() : null);

				$this->returnJson($return);
			}
			else
			{
				craft()->userSession->setNotice(Craft::t('Entry saved.'));

				// TODO: Remove for 2.0
				if (isset($_POST['redirect']) && mb_strpos($_POST['redirect'], '{entryId}') !== false)
				{
					Craft::log('The {entryId} token within the ‘redirect’ param on entries/saveEntry requests has been deprecated. Use {id} instead.', LogLevel::Warning);
					$_POST['redirect'] = str_replace('{entryId}', '{id}', $_POST['redirect']);
				}

				$this->redirectToPostedUrl($entry);
			}
		}
		else
		{
			if (craft()->request->isAjaxRequest())
			{
				$this->returnJson(array(
					'errors' => $entry->getErrors(),
				));
			}
			else
			{
				craft()->userSession->setError(Craft::t('Couldn’t save entry.'));

				// Send the entry back to the template
				craft()->urlManager->setRouteVariables(array(
					'entry' => $entry
				));
			}
		}
	}

	/**
	 * Deletes an entry.
	 */
	public function actionDeleteEntry()
	{
		$this->requirePostRequest();

		$entry = $this->_populateEntryModel();
		$section = $entry->getSection();
		craft()->userSession->requirePermission('deleteEntries:'.$section->id);

		$entryId = $entry->id;

		craft()->elements->deleteElementById($entryId);

		$this->redirectToPostedUrl();
	}

	/**
	 * Preps entry edit variables.
	 *
	 * @access private
	 * @param array &$variables
	 */
	private function _prepEditEntryVariables(&$variables)
	{
		if (!empty($variables['sectionHandle']))
		{
			$variables['section'] = craft()->sections->getSectionByHandle($variables['sectionHandle']);
		}
		else if (!empty($variables['sectionId']))
		{
			$variables['section'] = craft()->sections->getSectionById($variables['sectionId']);
		}

		if (empty($variables['section']))
		{
			throw new HttpException(404);
		}

		$variables['permissionSuffix'] = ':'.$variables['section']->id;

		// Make sure the user is allowed to edit entries in this section
		craft()->userSession->requirePermission('editEntries'.$variables['permissionSuffix']);

		if (Craft::hasPackage(CraftPackage::Localize))
		{
			// Figure out which locales the user is allowed to edit in this section
			$sectionLocaleIds = array_keys($variables['section']->getLocales());
			$editableLocaleIds = craft()->i18n->getEditableLocaleIds();
			$editableSectionLocaleIds = array_intersect($sectionLocaleIds, $editableLocaleIds);

			if (!$editableSectionLocaleIds)
			{
				throw new HttpException(404);
			}

			if (empty($variables['localeId']))
			{
				$variables['localeId'] = craft()->language;

				if (!in_array($variables['localeId'], $editableSectionLocaleIds))
				{
					$variables['localeId'] = $editableSectionLocaleIds[0];
				}
			}
			else if (!in_array($variables['localeId'], $editableSectionLocaleIds))
			{
				throw new HttpException(404);
			}
		}

		// Now let's set up the actual entry
		if (empty($variables['entry']))
		{
			if (!empty($variables['entryId']))
			{
				if (!empty($variables['draftId']))
				{
					$variables['entry'] = craft()->entryRevisions->getDraftById($variables['draftId']);
				}
				else if (!empty($variables['versionId']))
				{
					$variables['entry'] = craft()->entryRevisions->getVersionById($variables['versionId']);
				}
				else
				{
					$criteria = craft()->elements->getCriteria(ElementType::Entry);
					$criteria->id = $variables['entryId'];
					$criteria->status = '*';

					if (Craft::hasPackage(CraftPackage::Localize))
					{
						$criteria->locale = $variables['localeId'];
					}

					$variables['entry'] = $criteria->first();
				}

				if (!$variables['entry'])
				{
					throw new HttpException(404);
				}
			}
			else
			{
				$variables['entry'] = new EntryModel();
				$variables['entry']->sectionId = $variables['section']->id;
				$variables['entry']->authorId = craft()->userSession->getUser()->id;
				$variables['entry']->enabled = true;
			}
		}

		// More permission enforcement
		if (!$variables['entry']->id)
		{
			craft()->userSession->requirePermission('createEntries'.$variables['permissionSuffix']);
		}
		else if ($variables['entry']->authorId != craft()->userSession->getUser()->id)
		{
			craft()->userSession->requirePermission('editPeerEntries'.$variables['permissionSuffix']);
		}

		if ($variables['entry']->id && $variables['entry']->getClassHandle() == 'EntryDraft')
		{
			if ($variables['entry']->creatorId != craft()->userSession->getUser()->id)
			{
				craft()->userSession->requirePermission('editPeerEntryDrafts'.$variables['permissionSuffix']);
			}
		}

		// Entry type
		$variables['entryType'] = $variables['entry']->getType();

		if (!$variables['entryType'])
		{
			throw new Exception(Craft::t('No entry types are available for this entry.'));
		}

		// Tabs
		$variables['tabs'] = array();

		foreach ($variables['entryType']->getFieldLayout()->getTabs() as $index => $tab)
		{
			// Do any of the fields on this tab have errors?
			$hasErrors = false;

			if ($variables['entry']->hasErrors())
			{
				foreach ($tab->getFields() as $field)
				{
					if ($variables['entry']->getErrors($field->getField()->handle))
					{
						$hasErrors = true;
						break;
					}
				}
			}

			$variables['tabs'][] = array(
				'label' => $tab->name,
				'url'   => '#tab'.($index+1),
				'class' => ($hasErrors ? 'error' : null)
			);
		}

		// Settings tab
		$hasErrors = ($variables['entry']->hasErrors() && (
			$variables['entry']->getErrors('slug') ||
			$variables['entry']->getErrors('postDate') ||
			$variables['entry']->getErrors('expiryDate')
		));

		$variables['tabs'][] = array(
			'label' => Craft::t('Settings'),
			'url'   => '#entry-settings',
			'class' => ($hasErrors ? 'error' : null)
		);
	}

	/**
	 * Populates an EntryModel with post data.
	 *
	 * @access private
	 * @return EntryModel
	 */
	private function _populateEntryModel()
	{
		$entryId = craft()->request->getPost('entryId');

		if ($entryId)
		{
			$criteria = craft()->elements->getCriteria(ElementType::Entry);
			$criteria->id = $entryId;
			$criteria->status = '*';
			$entry = $criteria->first();

			if (!$entry)
			{
				throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $entryId)));
			}
		}
		else
		{
			$entry = new EntryModel();
		}

		$entry->sectionId  = craft()->request->getRequiredPost('sectionId');
		$entry->typeId     = craft()->request->getPost('typeId');
		$entry->locale     = craft()->request->getPost('locale', craft()->i18n->getPrimarySiteLocaleId());
		$entry->id         = craft()->request->getPost('entryId');
		$entry->authorId   = craft()->request->getPost('author', craft()->userSession->getUser()->id);
		$entry->slug       = craft()->request->getPost('slug');
		$entry->postDate   = (($postDate   = craft()->request->getPost('postDate'))   ? DateTime::createFromString($postDate,   craft()->timezone) : null);
		$entry->expiryDate = (($expiryDate = craft()->request->getPost('expiryDate')) ? DateTime::createFromString($expiryDate, craft()->timezone) : null);
		$entry->enabled    = (bool)craft()->request->getPost('enabled');

		$entry->getContent()->title = craft()->request->getPost('title');

		$fields = craft()->request->getPost('fields');
		$entry->getContent()->setAttributes($fields);

		return $entry;
	}
}
