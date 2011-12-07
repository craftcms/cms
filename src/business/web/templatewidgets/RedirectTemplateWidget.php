<?php

class RedirectTemplateWidget extends COutputProcessor
{
	public function processOutput($output)
	{
		header('Location: '.$output);
	}
}
