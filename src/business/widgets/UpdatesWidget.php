<?php

class UpdatesWidget extends Widget
{
	public $title = 'Updates Available';
	public $classname = 'updates';

	public function init()
	{
		$this->body = '<a class="btn dark update-all" href=""><span class="label">Update all</span></a>
			<table>
				<tbody>
					<tr>
						<td>Blocks 1.0.1</td>
						<td><a href="">Notes</a></td>
						<td><a class="btn" href=""><span class="label">Update</span></a></td>
					</tr>
					<tr>
						<td>Analytics 1.3</td>
						<td><a href="">Notes</a></td>
						<td><a class="btn" href=""><span class="label">Update</span></a></td>
					</tr>
				</tbody>
			</table>';
	}
}
