<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

use Craft;
use craft\app\events\Event;
use craft\app\helpers\UrlHelper;

/**
 * Widget is the base class for classes representing dashboard widgets in terms of objects.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class Widget extends SavableComponent implements WidgetInterface
{
	// Traits
	// =========================================================================

	use WidgetTrait;

	// Constants
	// =========================================================================

	/**
	 * @event Event The event that is triggered before the widget is saved
	 *
	 * You may set [[Event::performAction]] to `false` to prevent the widget from getting saved.
	 */
	const EVENT_BEFORE_SAVE = 'beforeSave';

	/**
	 * @event Event The event that is triggered after the widget is saved
	 */
	const EVENT_AFTER_SAVE = 'afterSave';

	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function isSelectable()
	{
		return (static::allowMultipleInstances() || !Craft::$app->getDashboard()->doesUserHaveWidget(static::className()));
	}

	/**
	 * Returns whether the widget can be selected more than once.
	 *
	 * @return boolean Whether the widget can be selected more than once
	 */
	protected static function allowMultipleInstances()
	{
		return true;
	}

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		$rules = [
			[['userId'], 'required'],
			[['userId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
		];

		// Only validate the ID if it's not a new widget
		if ($this->id !== null && strncmp($this->id, 'new', 3) !== 0)
		{
			$rules[] = [['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true];
		}

		return $rules;
	}

	/**
	 * @inheritdoc
	 */
	public function beforeSave()
	{
		// Trigger a 'beforeSave' event
		$event = new Event();
		$this->trigger(self::EVENT_BEFORE_SAVE, $event);
		return $event->performAction;
	}

	/**
	 * @inheritdoc
	 */
	public function afterSave()
	{
		// Trigger an 'afterSave' event
		$this->trigger(self::EVENT_AFTER_SAVE, new Event());
	}

	/**
	 * @inheritdoc
	 */
	public function getTitle()
	{
		// Default to the widget's display name
		return static::displayName();
	}

	/**
	 * @inheritdoc
	 */
	public function getColspan()
	{
		return 1;
	}

	/**
	 * @inheritdoc
	 */
	public function getBodyHtml()
	{
		return '<div style="margin: 0 -30px -30px;">' .
				'<img style="display: block; width: 100%;" src="'.UrlHelper::getResourceUrl('images/prg.jpg').'">' .
			'</div>';
	}

	/**
	 * @inheritdoc
	 */
	public function getUser()
	{
		return Craft::$app->getUsers()->getUserById($this->userId);
	}
}
