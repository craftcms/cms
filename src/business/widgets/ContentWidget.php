<?php

class ContentWidget extends Widget
{
	public $title = 'Content';
	public $className = 'content';

	public function init()
	{
		$this->body = '<div class="search"><input type="text" /></div>

		<div class="tree content">
			<h6>Add-ons</h6>
			<ul>
				<li><span class="toggle"></span><a href=""><span class="status live"></span>Assets</a>
					<h6>Docs</h6>
					<ul>
						<li><a href=""><span class="status live"></span>Installation Instructions</a></li>
						<li><a href=""><span class="status live"></span>Updating Instructions</a></li>
						<li><a href=""><span class="status live"></span>Template Tags &amp; Parameters</a></li>
					</ul>
				</li>
				<li><span class="toggle"></span><a href=""><span class="status live"></span>FieldFrame</a></li>
				<li><span class="toggle"></span><a href=""><span class="status live"></span>Matrix</a></li>
				<li><span class="toggle"></span><a href=""><span class="status live"></span>Playa</a></li>
				<li><span class="toggle"></span><a href=""><span class="status live"></span>Wygwam</a></li>
			</ul>

			<h6>Dive Bar</h6>
			<ul>
				<li><a href=""><span class="status live"></span>P&amp;T Field Pack</a></li>
				<li><a href=""><span class="status live"></span>P&amp;T List</a></li>
				<li><a href=""><span class="status live"></span>P&amp;T Pill</a></li>
				<li><a href=""><span class="status live"></span>P&amp;T Switch</a></li>
			</ul>

			<h6>Blog &amp; Tonic</h6>
			<ul>
				<li><a href=""><span class="status live"></span>Introducing Assets</a></li>
				<li><a href=""><span class="status live"></span>Wygwam 2.2 Released!</a></li>
				<li><a href=""><span class="status live"></span>Playa 4 has arrived!</a></li>
				<li><a href=""><span class="status live"></span>Have a drink on us!</a></li>
				<li><a class="etc" href=""></a></li>
			</ul>
		</div>';
	}
}
