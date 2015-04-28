<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\base\Model;
use craft\app\behaviors\ContentBehavior;
use craft\app\behaviors\ContentTrait;
use ReflectionClass;
use ReflectionProperty;

/**
 * Entry content model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Content extends Model
{
	// Traits
	// =========================================================================

	use ContentTrait;

	// Properties
	// =========================================================================

	/**
	 * @var ElementInterface|Element The element associated with this content
	 */
	public $element;

	/**
	 * @var integer ID
	 */
	public $id;

	/**
	 * @var integer Element ID
	 */
	public $elementId;

	/**
	 * @var string Locale
	 */
	public $locale;

	/**
	 * @var string Title
	 */
	public $title;

	/**
	 * @var
	 */
	private $_requiredFields;

	/**
	 * @var
	 */
	private $_attributeConfigs;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		if ($this->locale === null)
		{
			$this->locale = Craft::$app->getI18n()->getPrimarySiteLocaleId();
		}
	}

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'customFields' => ContentBehavior::className(),
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributes()
	{
		$names = parent::attributes();
		$class = new ReflectionClass($this->getBehavior('customFields'));
		foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property)
		{
			if (!in_array($property->getName(), $names))
			{
				$names[] = $property->getName();
			}
		}
		return $names;
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'title' => Craft::t('app', 'Title'),
			'body' => Craft::t('app', 'Body'),
			'description' => Craft::t('app', 'Description'),
			'heading' => Craft::t('app', 'Heading'),
			'ingredients' => Craft::t('app', 'Ingredients'),
			'linkColor' => Craft::t('app', 'Link Color'),
			'metaDescription' => Craft::t('app', 'Meta Description'),
			'photos' => Craft::t('app', 'Photos'),
			'siteIntro' => Craft::t('app', 'Site Intro'),
			'tags' => Craft::t('app', 'Tags'),
		];
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['elementId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['locale'], 'craft\\app\\validators\\Locale'],
			[['linkColor'], 'string', 'length' => 7],
			[['title'], 'string', 'max' => 255],
			[['id', 'elementId', 'locale', 'title', 'body', 'description', 'heading', 'ingredients', 'linkColor', 'metaDescription', 'photos', 'siteIntro', 'tags'], 'safe', 'on' => 'search'],
		];
	}

	/**
	 * Sets the required fields.
	 *
	 * @param array $requiredFields
	 *
	 * @return null
	 */
	public function setRequiredFields($requiredFields)
	{
		$this->_requiredFields = $requiredFields;

		// Have the attributes already been defined?
		if (isset($this->_attributeConfigs))
		{
			foreach (Craft::$app->getFields()->getAllFields() as $field)
			{
				if (in_array($field->id, $this->_requiredFields) && isset($this->_attributeConfigs[$field->handle]))
				{
					$this->_attributeConfigs[$field->handle]['required'] = true;
				}
			}

			if (in_array('title', $this->_requiredFields))
			{
				$this->_attributeConfigs['title']['required'] = true;
			}
		}
	}

	/**
	 * Validates all of the attributes for the current Model. Any attributes that fail validation will additionally get
	 * logged to the `craft/storage/logs` folder as a warning.
	 *
	 * In addition we validates the custom fields on this model.
	 *
	 * @param array|null $attributes
	 * @param bool       $clearErrors
	 *
	 * @return bool
	 */
	public function validate($attributes = null, $clearErrors = true)
	{
		$validates = parent::validate($attributes, $clearErrors);

		foreach (Craft::$app->getFields()->getAllFields() as $field)
		{
			$handle = $field->handle;

			if (is_array($attributes) && !in_array($handle, $attributes))
			{
				continue;
			}

			// Don't worry about blank values. Those will already be caught by required field validation.
			if ($this->$handle !== null)
			{
				$errors = $field->validateValue($this->$handle, $this->element);

				if ($errors !== true)
				{
					if (is_string($errors))
					{
						$this->addError($handle, $errors);
					}
					else if (is_array($errors))
					{
						foreach ($errors as $error)
						{
							$this->addError($handle, $error);
						}
					}

					$validates = false;
				}
			}
		}

		return $validates;
	}
}
