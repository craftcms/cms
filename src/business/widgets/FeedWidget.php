<?php

class FeedWidget extends Widget
{
	public $classname = 'rss';

	public $settings = array(
		'url' => 'http://feeds.feedburner.com/blogandtonic',
		'title' => 'Blog &amp; Tonic',
		'show' => 5
	);

	protected function init()
	{
		$this->title = $this->settings['title'];

		$this->body = '<table>
				<tr>
					<td><a href="">Introducing Assets</a></td>
					<td class="date">Jun 28, 2011</td>
				</tr>
				<tr>
					<td><a href="">Wygwam 2.2 Released</a></td>
					<td class="date">Feb 9, 2011</td>
				</tr>
				<tr>
					<td><a href="">Playa 4 has arrived!</a></td>
					<td class="date">Feb 2, 2011</td>
				</tr>
				<tr>
					<td><a href="">Have a drink on us!</a></td>
					<td class="date">Sep 9, 2010</td>
				</tr>
				<tr>
					<td><a href="">Introducing the Dive Bar</a></td>
					<td class="date">Jul 23, 2010</td>
				</tr>
			</table>';
	}
}
