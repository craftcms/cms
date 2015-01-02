<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

/**
 * DeprecationError model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DeprecationError extends BaseModel
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns a simple indication of the origin of the deprecation error.
	 *
	 * @return string
	 */
	public function getOrigin()
	{
		if ($this->template)
		{
			$file = $this->template;

			if (strncmp($file, 'string:', 7) === 0)
			{
				$file = substr($file, 7);
				$line = null;
			}
			else
			{
				$line = $this->templateLine;
			}
		}
		else
		{
			$file = $this->file;
			$line = $this->line;
		}

		return $file.($line ? " ({$line})" : '');
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'             => AttributeType::Number,
			'key'            => AttributeType::String,
			'fingerprint'    => AttributeType::String,
			'lastOccurrence' => AttributeType::DateTime,
			'file'           => AttributeType::String,
			'line'           => AttributeType::Number,
			'class'          => AttributeType::String,
			'method'         => AttributeType::String,
			'template'       => AttributeType::String,
			'templateLine'   => AttributeType::Number,
			'message'        => AttributeType::String,
			'traces'         => AttributeType::Mixed,
		);
	}
}
