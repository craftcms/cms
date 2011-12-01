<?php

class RecentActivityWidget extends Widget
{
	public $title = 'Recent Activity';
	public $className = 'recent_activity';

	public function body()
	{
		return '<table>
				<tr>
					<td><a href="">Brandon</a> is editing <a href="">Blocks</a></td>
					<td class="date">right now</td>
				</tr>
				<tr>
					<td><a href="">Brandon</a> published a new version of <a href="">Assets</a></td>
					<td class="date">yesterday</td>
				</tr>
				<tr>
					<td><a href="">Brad</a> updated Blocks and Wygwam</a></td>
					<td class="date">Sep 5, 2011</td>
			</table>';
	}
}
