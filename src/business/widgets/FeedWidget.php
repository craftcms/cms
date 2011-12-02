<?php

class FeedWidget extends Widget
{
	public $className = 'rss';

	public $settings = array(
		'url' => 'http://feeds.feedburner.com/blogandtonic',
		'title' => 'Blog &amp; Tonic',
		'limit' => 5
	);

	protected function init()
	{
		$this->title = $this->settings['title'];
	}

	public function displayBody()
	{
		return '<table>
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

	public function displaySettings()
	{
		return '<label for="widget1-url">URL</label>
			<div class="input-wrapper"><input id="widget1-url" type="text" value="'.$this->settings['url'].'"></div>
			<label for="widget1-title">Title</label>
			<div class="input-wrapper"><input id="widget1-url" type="text" value="'.$this->settings['title'].'"></div>
			<label for="widget1-limit">Limit</label>
			<input id="widget1-limit" type="number" value="'.$this->settings['limit'].'">';
	}
}
