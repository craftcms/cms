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
		$variables['section'] = craft()->sections->getSectionByHandle($variables['sectionHandle']);

		if (!$variables['section'])
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
			$variables['crumbs'][] = array('label' => $variables['section']->name, 'url' => UrlHelper::getUrl('entries/'.$variables['section']->handle));
		}


		// Tabs
		$variables['tabs'] = array();

		foreach ($variables['section']->getFieldLayout()->getTabs() as $index => $tab)
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
			$variables['entry']->getErrors('expiryDate') ||
			$variables['entry']->getErrors('tags')
		));

		$variables['tabs'][] = array(
			'label' => Craft::t('Settings'),
			'url'   => '#entry-settings',
			'class' => ($hasErrors ? 'error' : null)
		);

		// Render the template!
		craft()->templates->includeCssResource('css/entry.css');
		$this->renderTemplate('entries/_edit', $variables);
	}

	/**
	 * Saves an entry.
	 */
	public function actionSaveEntry()
	{
		$this->requirePostRequest();

		$entry = new EntryModel();

		$entry->sectionId  = craft()->request->getRequiredPost('sectionId');
		$entry->locale     = craft()->request->getPost('locale', craft()->i18n->getPrimarySiteLocaleId());
		$entry->id         = craft()->request->getPost('entryId');
		$entry->authorId   = craft()->request->getPost('author', craft()->userSession->getUser()->id);
		$entry->title      = craft()->request->getPost('title');
		$entry->slug       = craft()->request->getPost('slug');
		$entry->postDate   = (($postDate   = craft()->request->getPost('postDate'))   ? DateTime::createFromString($postDate,   craft()->timezone) : null);
		$entry->expiryDate = (($expiryDate = craft()->request->getPost('expiryDate')) ? DateTime::createFromString($expiryDate, craft()->timezone) : null);
		$entry->enabled    = craft()->request->getPost('enabled');
		$entry->tags       = craft()->request->getPost('tags');

		$fields = craft()->request->getPost('fields');
		$entry->setContent($fields);

		if (craft()->entries->saveEntry($entry))
		{
			if (craft()->request->isAjaxRequest())
			{
				$return['success']   = true;
				$return['entry']     = $entry->getAttributes();
				$return['cpEditUrl'] = $entry->getCpEditUrl();
				$return['author']    = $entry->getAuthor()->getAttributes();
				$return['postDate']  = ($entry->postDate ? $entry->postDate->w3cDate() : null);

				$this->returnJson($return);
			}
			else
			{
				craft()->userSession->setNotice(Craft::t('Entry saved.'));

				$this->redirectToPostedUrl(array(
					'entryId'   => $entry->id,
					'slug'      => $entry->slug,
					'url'       => $entry->getUrl(),
					'cpEditUrl' => $entry->getCpEditUrl(),
				));
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
		$this->requireAjaxRequest();

		$entryId = craft()->request->getRequiredPost('id');

		craft()->elements->deleteElementById($entryId);
		$this->returnJson(array('success' => true));
	}
}
