<?php
namespace Craft;

/**
 * Get Help widget.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.widgets
 * @since     1.0
 */
class GetHelpWidget extends BaseWidget
{
	// Properties
	// =========================================================================

	/**
	 * Whether users should be able to select more than one of this widget type.
	 *
	 * @var bool
	 */
	protected $multi = false;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Craft Support');
	}

	/**
	 * @inheritDoc IWidget::getIconPath()
	 *
	 * @return string
	 */
	public function getIconPath()
	{
		return craft()->path->getResourcesPath().'images/widgets/buoey.svg';
	}

	/**
	 * @inheritDoc IWidget::getBodyHtml()
	 *
	 * @return string|false
	 */
	public function getBodyHtml()
	{
		// Only admins get the Get Help widget.
		if (!craft()->userSession->isAdmin())
		{
			return false;
		}

		$id = $this->model->id;

		$plugins = '';
		foreach (craft()->plugins->getPlugins() as $plugin)
		{
			$plugins .= "\n    - ".$plugin->getName().' '.$plugin->getVersion();
		}

		$envInfoJs = JsonHelper::encode(array(
			'Craft version' => craft()->getVersion().' ('.craft()->getEditionName().')',
			'PHP version' => phpversion(),
			'Database driver & version' => 'MySQL '.craft()->db->getServerVersion(),
			'Plugins & versions' => $plugins,
		));

		$js = "new Craft.CraftSupportWidget({$id}, {$envInfoJs});";
		craft()->templates->includeJs($js);

		craft()->templates->includeCssResource('css/CraftSupportWidget.css');
		craft()->templates->includeJsResource('js/CraftSupportWidget.js');
		craft()->templates->includeTranslations(
			'Message sent successfully.'
		);

		$iconsDir = craft()->path->getResourcesPath().'images/widgets';

		return craft()->templates->render('_components/widgets/CraftSupport/body', array(
			'widgetId' => $id,
			'buoeyIcon' => file_get_contents($iconsDir.'/buoey.svg'),
			'bullhornIcon' => file_get_contents($iconsDir.'/bullhorn.svg'),
			'seIcon' => file_get_contents($iconsDir.'/craft-stack-exchange.svg'),
			'ghIcon' => file_get_contents($iconsDir.'/github.svg'),
		));
	}

	/**
	 * @inheritDoc IComponentType::isSelectable()
	 *
	 * @return bool
	 */
	public function isSelectable()
	{
		// Only admins get the Get Help widget.
		if (parent::isSelectable() && craft()->userSession->isAdmin())
		{
			return true;
		}

		return false;
	}
}
