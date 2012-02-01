<?php
namespace Blocks;

/**
 *
 */
class LicenseKey extends BaseModel
{
	protected $tableName = 'licensekeys';

	protected $attributes = array(
		'key' => array('type' => AttributeType::Char, 'length' => 36, 'matchPattern' => '/[\w0-9]{8}-[\w0-9]{4}-[\w0-9]{4}-[\w0-9]{4}-[\w0-9]{12}/', 'required' => true, 'unique' => true)
	);

	public function rules()
	{
		// Remove the length=36 validation rule, since that's implied by matchPattern
		$rules = parent::rules();
		foreach ($rules as $i => $rule)
		{
			if ($rule[0] == 'key' && $rule[1] == 'length')
			{
				array_splice($rules, $i, 1);
				break;
			}
		}

		return $rules;
	}
}
