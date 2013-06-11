<?php
namespace Craft;

/**
 * Assets fieldtype
 */
class AssetsFieldType extends BaseElementFieldType
{
	/**
	 * @access protected
	 * @var string $elementType The element type this field deals with.
	 */
	protected $elementType = 'Asset';

	/**
	 * @access protected
	 * @var string|null $inputJsClass The JS class that should be initialized for the input.
	 */
	protected $inputJsClass = 'Craft.AssetSelectInput';
}
