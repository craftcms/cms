<?php

class UserTag extends Tag
{
	public function __construct($userId)
	{
		parent::__construct($userId);
	}

	public function toBool()
	{
		return (bool) $this->_userId;
	}

	public function toString()
	{
		return $this->name();
	}

	public function name()
	{
		return new StringTag($this->name);
	}

	public function handle()
	{
		return new StringTag($this->handle);
	}

	public function pages()
	{
		return new ContentPagesTag($this);
	}
}
