<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig;

/**
 * NodeVisitor adds “head”, “beginBody”, and “endBody” events to the template as it’s being compiled.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class NodeVisitor implements \Twig_NodeVisitorInterface
{
	// Properties
	// =========================================================================

	private $_foundHead = false;
	private $_findingBeginBody = false;
	private $_foundBeginBody = false;
	private $_foundEndBody = false;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function enterNode(\Twig_NodeInterface $node, \Twig_Environment $env)
	{
		// Is this the top-level template node?
		if ($node instanceof \Twig_Node_Module)
		{
			$node = $this->_findEventTags($node);
		}
		else if ($this->_foundAllEventTags() === false && $node instanceof \Twig_Node_Text)
		{
			$data = $node->getAttribute('data');

			if ($this->_findingBeginBody === true)
			{
				if (preg_match('/^[^>]*>/', $data, $matches))
				{
					$this->_findingBeginBody = false;
					$this->_foundBeginBody = true;
					$beginBodyPos = strlen($matches[0]);
					return $this->_splitTextNode($node, $data, $beginBodyPos, 'beginBody');
				}
			}

			if ($this->_foundHead === false && ($headPos = stripos($data, '</head>')) !== false)
			{
				$this->_foundHead = true;
				return $this->_splitTextNode($node, $data, $headPos, 'head');
			}
			else if ($this->_foundBeginBody === false && preg_match('/(<body\b[^>]*)(>)?/', $data, $matches, PREG_OFFSET_CAPTURE) === 1)
			{
				if (empty($matches[2][0]))
				{
					// Will have to wait for the next text node
					$this->_findingBeginBody = true;
				}
				else
				{
					$this->_foundBeginBody = true;
					$beginBodyPos = $matches[0][1] + strlen($matches[0][0]);
					return $this->_splitTextNode($node, $data, $beginBodyPos, 'beginBody');
				}
			}
			else if ($this->_foundEndBody === false && ($endBodyPos = stripos($data, '</body>')) !== false)
			{
				$this->_foundEndBody = true;
				return $this->_splitTextNode($node, $data, $endBodyPos, 'endBody');
			}
		}

		return $node;
	}

	/**
	 * @inheritdoc
	 */
	public function leaveNode(\Twig_NodeInterface $node, \Twig_Environment $env)
	{
		return $node;
	}

	/**
	 * @inheritdoc
	 */
	public function getPriority()
	{
		return 100;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns whether all event tags have been found.
	 *
	 * @return boolean
	 */
	public function _foundAllEventTags()
	{
		return ($this->_foundHead === true && $this->_foundBeginBody === true && $this->_foundEndBody === true);
	}

	/**
	 * Traverses through a node and its children, looking for event tags.
	 *
	 * @param \Twig_NodeInterface $node The current node to traverse
	 * @return \Twig_NodeInterface
	 */
	private function _findEventTags(\Twig_NodeInterface $node = null)
	{
		if (null === $node)
		{
			return;
		}

		// Check to see if this is a template event tag
		if ($node instanceof \Twig_Node_Print || $node instanceof \Twig_Node_Do)
		{
			$expression = $node->getNode('expr');

			if ($expression instanceof \Twig_Node_Expression_Filter)
			{
				$expression = $expression->getNode('node');
			}

			if ($expression instanceof \Twig_Node_Expression_Function)
			{
				$name = $expression->getAttribute('name');

				if (in_array($name, ['head', 'beginBody', 'endBody']))
				{
					$property = '_found'.ucfirst($name);

					if ($this->$property === false)
					{
						$this->$property = true;

						if ($node instanceof \Twig_Node_Print)
						{
							// Switch it to a {% do %} tag
							$node = new \Twig_Node_Do($expression, $expression->getLine());
						}
					}
				}
			}
		}

		// Should we keep looking?
		if ($this->_foundAllEventTags() === false)
		{
			foreach ($node as $k => $n)
			{
				if (false !== $n = $this->_findEventTags($n))
				{
					$node->setNode($k, $n);
				}
				else
				{
					$node->removeNode($k);
				}
			}
		}

		return $node;
	}

	/**
	 * Places a new event function call at a specific point in a given text node’s data.
	 *
	 * @param \Twig_Node_Text $node
	 * @param string $data
	 * @param integer $pos
	 * @param string $name
	 * @return \Twig_Node
	 */
	private function _splitTextNode($node, $data, $pos, $name)
	{
		$preSplitHtml = substr($data, 0, $pos);
		$postSplitHtml = substr($data, $pos);
		$startLine = $node->getLine();
		$splitLine = $startLine + substr_count($preSplitHtml, "\n");

		return new \Twig_Node([
			new \Twig_Node_Text($preSplitHtml, $startLine),
			new \Twig_Node_Do(new \Twig_Node_Expression_Function($name, new \Twig_Node(), $splitLine), $splitLine),
			new \Twig_Node_Text($postSplitHtml, $splitLine)
		], [], $startLine);
	}
}
