<?php
namespace Blocks;

/**
 *
 */
class LicenseKey extends BaseModel
{
	protected $tableName = 'licensekeys';

	protected $attributes = array(
		'license_key' => AttributeType::LicenseKey
	);

	/**
	 * @return array
	 */
	public function rules()
	{
		// Remove the length=36 validation rule, since that's implied by matchPattern
		$rules = parent::rules();
		foreach ($rules as $i => $rule)
		{
			if ($rule[0] == 'license_key' && $rule[1] == 'length')
			{
				array_splice($rules, $i, 1);
				break;
			}
		}

		return $rules;
	}
}
