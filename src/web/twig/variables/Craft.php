<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\variables;

use craft\app\elements\Asset;
use craft\app\elements\Category;
use craft\app\elements\db\AssetQuery;
use craft\app\elements\db\CategoryQuery;
use craft\app\elements\db\EntryQuery;
use craft\app\elements\db\TagQuery;
use craft\app\elements\db\UserQuery;
use craft\app\elements\Entry;
use craft\app\elements\Tag;
use craft\app\elements\User;
use yii\di\ServiceLocator;

/**
 * Craft defines the `craft` global template variable.
 *
 * @property App $app
 * @property Config $config
 * @property Elements $elements
 * @property Cp $cp
 * @property Dashboard $dashboard
 * @property Deprecator $deprecator
 * @property Fields $fields
 * @property Feeds $feeds
 * @property Globals $globals
 * @property Plugins $plugins
 * @property HttpRequest $request
 * @property Routes $routes
 * @property Sections $sections
 * @property SystemSettings $systemSettings
 * @property Tasks $tasks
 * @property Updates $updates
 * @property UserSession $session
 * @property I18n $i18n
 * @property UserGroups $userGroups
 * @property UserPermissions $userPermissions
 * @property EmailMessages $emailMessages
 * @property EntryRevisions $entryRevisions
 * @property Rebrand $rebrand
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Craft extends ServiceLocator
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function __construct($config = [])
	{
		// Set the core components
		$config['components'] = [
			'app' => 'craft\app\web\twig\variables\App',
			'config' => 'craft\app\web\twig\variables\Config',
			'elements' => 'craft\app\web\twig\variables\Elements',
			'cp' => 'craft\app\web\twig\variables\Cp',
			'dashboard' => 'craft\app\web\twig\variables\Dashboard',
			'deprecator' => 'craft\app\web\twig\variables\Deprecator',
			'fields' => 'craft\app\web\twig\variables\Fields',
			'feeds' => 'craft\app\web\twig\variables\Feeds',
			'globals' => 'craft\app\web\twig\variables\Globals',
			'plugins' => 'craft\app\web\twig\variables\Plugins',
			'request' => 'craft\app\web\twig\variables\HttpRequest',
			'routes' => 'craft\app\web\twig\variables\Routes',
			'sections' => 'craft\app\web\twig\variables\Sections',
			'systemSettings' => 'craft\app\web\twig\variables\SystemSettings',
			'tasks' => 'craft\app\web\twig\variables\Tasks',
			'updates' => 'craft\app\web\twig\variables\Updates',
			'session' => 'craft\app\web\twig\variables\UserSession',
			'i18n' => 'craft\app\web\twig\variables\I18N',
		];

		switch (\Craft::$app->getEdition())
		{
			case \Craft::Pro:
			{
				$config['components'] = array_merge($config['components'], [
					'userGroups' => 'craft\app\web\twig\variables\UserGroups',
					'userPermissions' => 'craft\app\web\twig\variables\UserPermissions',
				]);
				// Keep going...
			}
			case \Craft::Client:
			{
				$config['components'] = array_merge($config['components'], [
					'emailMessages' => 'craft\app\web\twig\variables\EmailMessages',
					'entryRevisions' => 'craft\app\web\twig\variables\EntryRevisions',
					'rebrand' => 'craft\app\web\twig\variables\Rebrand',
				]);
			}
		}

		// Add plugin components
		foreach (\Craft::$app->getPlugins()->getAllPlugins() as $handle => $plugin)
		{
			if (!isset($config['components'][$handle]))
			{
				$component = $plugin->getVariableDefinition();

				if ($component !== null)
				{
					$config['components'][$handle] = $component;
				}
			}
		}

		parent::__construct($config);
	}

	/**
	 * @inheritdoc
	 */
	public function __call($name, $params)
	{
		// Are they calling one of the components as if it's still a function?
		if ($params === [] && $this->has($name))
		{
			\Craft::$app->getDeprecator()->log('CraftVariable::__call()', "craft.{$name}() is no longer a function. Use “craft.{$name}” instead (without the parentheses).");
			return $this->get($name);
		}
		else
		{
			return parent::__call($name, $params);
		}
	}

	// General info
	// -------------------------------------------------------------------------

	/**
	 * @inheritdoc
	 */
	public function canGetProperty($name, $checkVars = true, $checkBehaviors = true)
	{
		// Check the services
		if ($this->has($name))
		{
			return true;
		}

		return parent::canGetProperty($name, $checkVars, $checkBehaviors);
	}

	/**
	 * Gets the current language in use.
	 *
	 * @return string
	 */
	public function getLocale()
	{
		return \Craft::$app->language;
	}

	/**
	 * Returns whether this site has multiple locales.
	 *
	 * @return bool
	 */
	public function getIsLocalized()
	{
		return \Craft::$app->isLocalized();
	}

	// Element queries
	// -------------------------------------------------------------------------

	/**
	 * Returns a new AssetQuery instance.
	 *
	 * @param mixed $criteria
	 * @return AssetQuery
	 */
	public function getAssets($criteria = null)
	{
		return Asset::find()->configure($criteria);
	}

	/**
	 * Returns a new CategoryQuery instance.
	 *
	 * @param mixed $criteria
	 * @return CategoryQuery
	 */
	public function getCategories($criteria = null)
	{
		return Category::find()->configure($criteria);
	}

	/**
	 * Returns a new EntryQuery instance.
	 *
	 * @param mixed $criteria
	 * @return EntryQuery
	 */
	public function getEntries($criteria = null)
	{
		return Entry::find()->configure($criteria);
	}

	/**
	 * Returns a new TagQuery instance.
	 *
	 * @param mixed $criteria
	 * @return TagQuery
	 */
	public function getTags($criteria = null)
	{
		return Tag::find()->configure($criteria);
	}

	/**
	 * Returns a new UserQuery instance
	 *
	 * @param mixed $criteria
	 * @return UserQuery
	 */
	public function getUsers($criteria = null)
	{
		return User::find()->configure($criteria);
	}
}
