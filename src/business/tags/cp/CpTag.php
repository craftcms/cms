<?php

class CpTag extends Tag
{
	private static $defaultSections = array(
		'dashboard' => 'Dashboard',
		'content' => 'Content',
		'assets' => 'Assets',
		'users' => 'Users',
		'settings' => 'Settings',
		'guide' => 'User Guide',
	);

	public function dashboard()
	{
		return new CpDashboardTag();
	}

	public function resource($path)
	{
		return new CpResourceTag($path);
	}

	public function sections()
	{
		$sectionTags = array();

		foreach (self::$defaultSections as $handle => $name)
		{
			$sectionTags[] = new CpSectionTag($handle, $name);
		}

		return new ArrayTag($sectionTags);
	}

	public function noLicenseKey()
	{
		if (($blocksUpdateInfo = Blocks::app()->request->getBlocksUpdateInfo()) !== null)
			return $blocksUpdateInfo['blocksLicenseStatus'] == LicenseKeyStatus::MissingKey ? new BoolTag(true) : new BoolTag(false);

		return new BoolTag(false);
	}

	public function badLicenseKey()
	{
		if (($blocksUpdateInfo = Blocks::app()->request->getBlocksUpdateInfo()) !== null)
			return $blocksUpdateInfo['blocksLicenseStatus'] == LicenseKeyStatus::InvalidKey ? new BoolTag(true) : new BoolTag(false);

		return new BoolTag(false);
	}

	public function criticalUpdateAvailable()
	{
		return new BoolTag(true);
	}

	public function updates()
	{
		return new ArrayTag(array(
			new ArrayTag(array(
				'name' => 'Blocks Standard',
				'version' => '1.1',
				'notes' => '<h5>Blocks 1.1</h5>
					<ul>
						<li>Added a Plugin Store tab to the Control Panel.</li>
						<li>Added a new global search input to the nav bar.</li>
						<li>Plugins can now add new sections to the Control Panel.</li>
					</ul>
					<h5>Blocks 1.0 build 218</h5>
					<ul>
						<li>Fixed a caching issue with the Feed widget.</li>
					</ul>'
			)),
			new ArrayTag(array(
				'name' => 'Wygwam',
				'version' => '1.1',
				'notes' => '<h5>Wygwam 1.1</h5>
					<ul>
						<li>Added file browsing and uploading support.</li>
						<li>Made it possible to toggle individual toolbar buttons.</li>
						<li>Added a field height setting.</li>
					</ul>'
			)),
			new ArrayTag(array(
				'name' => 'Brilliant Retail',
				'version' => '1.0.1.0',
				'notes' => '<h5>Brilliant Retail 1.0.1.0</h5>
					<ul>
						<li>Added New Payment Gateway: PayPal Website Payment Standard</li>
						<li>Added New Payment Gateway: eWay Australia</li>
						<li>Added New Payment Gateway: PsiGate </li>
						<li>Added New Shipping Method: \'Pickup In store\' Shipping Option (i.e. No Shipping)</li>
						<li>Added New Report: Customer Search History Report</li>
						<li>Added New Report: List of Orders by Customer over time</li>
						<li>Added New Report: Product Best Sellers Report</li>
						<li>Added purchase_version number tag to customer_download tag</li>
						<li>Added cancel status to order</li>
						<li>Added additional developer hooks</li>
						<li>Updated category_menu tag to include sort parameter (accepts sort|title)</li>
						<li>Updated image tag to include alt parameter</li>
						<li>Updated all currency instances to pass through currency_round function</li>
						<li>Updated exp_br_state table so state_id start at 1 and are now standard across all installations</li>
						<li>Updated USPS with additional configuration options</li>
						<li>Fixed shipping method pre calculations for product weight</li>
						<li>Fixed path_separator and directory_separator on Zend library include in core file</li>
						<li>Fixed Brilliant_retail fieldtype to allow multiple instances per page</li>
						<li>Fixed quantity issue on cart_update and cart_add. Forced integers. </li>
						<li>Fixed download_version tag displaying improper number in customer_download tag</li>
						<li>Fixed issue with general sales report total</li>
						<li>Fixed issue with javascript file names in the WYSIWYG editor</li>
						<li>Fixed the cart_items tag to account for quantity of items </li>
						<li>Removed countries that are not recognized by PayPal</li>
						<li>Misc updates to Blank Theme</li>
					</ul>'
			))
		));
	}
}
