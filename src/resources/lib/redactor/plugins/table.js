(function($)
{
	$.Redactor.prototype.table = function()
	{
		return {
			langs: {
				en: {
					"table": "Table",
					"insert-table": "Insert table",
					"insert-row-above": "Insert row above",
					"insert-row-below": "Insert row below",
					"insert-column-left": "Insert column left",
					"insert-column-right": "Insert column right",
					"add-head": "Add head",
					"delete-head": "Delete head",
					"delete-column": "Delete column",
					"delete-row": "Delete row",
					"delete-table": "Delete table"
				}
			},
			init: function()
			{
				var dropdown = {};

				dropdown.insert_table = {
					title: this.lang.get('insert-table'),
					func: this.table.insert,
					observe: {
						element: 'table',
						in: {
							attr: {
								'class': 'redactor-dropdown-link-inactive',
								'aria-disabled': true,
							}
						}
					}
				};

				dropdown.insert_row_above = {
					title: this.lang.get('insert-row-above'),
					func: this.table.addRowAbove,
					observe: {
						element: 'table',
						out: {
							attr: {
								'class': 'redactor-dropdown-link-inactive',
								'aria-disabled': true,
							}
						}
					}
				};

				dropdown.insert_row_below = {
					title: this.lang.get('insert-row-below'),
					func: this.table.addRowBelow,
					observe: {
						element: 'table',
						out: {
							attr: {
								'class': 'redactor-dropdown-link-inactive',
								'aria-disabled': true,
							}
						}
					}
				};

				dropdown.insert_column_left = {
					title: this.lang.get('insert-column-left'),
					func: this.table.addColumnLeft,
					observe: {
						element: 'table',
						out: {
							attr: {
								'class': 'redactor-dropdown-link-inactive',
								'aria-disabled': true,
							}
						}
					}
				};

				dropdown.insert_column_right = {
					title: this.lang.get('insert-column-right'),
					func: this.table.addColumnRight,
					observe: {
						element: 'table',
						out: {
							attr: {
								'class': 'redactor-dropdown-link-inactive',
								'aria-disabled': true,
							}
						}
					}
				};

				dropdown.add_head = {
					title: this.lang.get('add-head'),
					func: this.table.addHead,
					observe: {
						element: 'table',
						out: {
							attr: {
								'class': 'redactor-dropdown-link-inactive',
								'aria-disabled': true,
							}
						}
					}
				};

				dropdown.delete_head = {
					title: this.lang.get('delete-head'),
					func: this.table.deleteHead,
					observe: {
						element: 'table',
						out: {
							attr: {
								'class': 'redactor-dropdown-link-inactive',
								'aria-disabled': true,
							}
						}
					}
				};

				dropdown.delete_column = {
    				title: this.lang.get('delete-column'),
    				func: this.table.deleteColumn,
    				observe: {
    					element: 'table',
    					out: {
    						attr: {
    							'class': 'redactor-dropdown-link-inactive',
    							'aria-disabled': true,
    						}
    					}
    				}
    			};

				dropdown.delete_row = {
					title: this.lang.get('delete-row'),
					func: this.table.deleteRow,
					observe: {
						element: 'table',
						out: {
							attr: {
								'class': 'redactor-dropdown-link-inactive',
								'aria-disabled': true,
							}
						}
					}
				};

				dropdown.delete_table = {
					title: this.lang.get('delete-table'),
					func: this.table.deleteTable,
					observe: {
						element: 'table',
						out: {
							attr: {
								'class': 'redactor-dropdown-link-inactive',
								'aria-disabled': true,
							}
						}
					}
				};


				var button = this.button.addBefore('link', 'table', this.lang.get('table'));
				this.button.setIcon(button, '<i class="re-icon-table"></i>');
				this.button.addDropdown(button, dropdown);
			},
			insert: function()
			{
				if (this.table.getTable())
				{
					return;
				}

				this.placeholder.hide();

				var rows = 2;
				var columns = 3;
				var $tableBox = $('<div>');
				var $table = $('<table />');

				for (var i = 0; i < rows; i++)
				{
					var $row = $('<tr>');

					for (var z = 0; z < columns; z++)
					{
						var $column = $('<td>' + this.opts.invisibleSpace + '</td>');

						// set the focus to the first td
						if (i === 0 && z === 0)
						{
							$column.append(this.marker.get());
						}

						$($row).append($column);
					}

					$table.append($row);
				}

				$tableBox.append($table);
				var html = $tableBox.html();

				this.buffer.set();

				var current = this.selection.current();
				if ($(current).closest('li', this.core.editor()[0]).length !== 0)
				{
					$(current).closest('ul, ol').first().after(html);
				}
				else
				{
					this.air.collapsed();
					this.insert.html(html);
				}

				this.selection.restore();
				this.core.callback('insertedTable', $table);
			},
			getTable: function()
			{
				var $table = $(this.selection.current()).closest('table');

				if (!this.utils.isRedactorParent($table))
				{
					return false;
				}

				if ($table.length === 0)
				{
					return false;
				}

				return $table;
			},
			restoreAfterDelete: function($table)
			{
				this.selection.restore();
				$table.find('span.redactor-selection-marker').remove();

			},
			deleteTable: function()
			{
				var $table = this.table.getTable();
				if (!$table)
				{
					return;
				}

				this.buffer.set();


				var $next = $table.next();
				if (!this.opts.linebreaks && $next.length !== 0)
				{
					this.caret.start($next);
				}
				else
				{
					this.caret.after($table);
				}


				$table.remove();


			},
			deleteRow: function()
			{
				var $table = this.table.getTable();
				if (!$table)
				{
					return;
				}

				var $current = $(this.selection.current());

				this.buffer.set();

				var $current_tr = $current.closest('tr');
				var $focus_tr = $current_tr.prev().length ? $current_tr.prev() : $current_tr.next();
				if ($focus_tr.length)
				{
					var $focus_td = $focus_tr.children('td, th').first();
					if ($focus_td.length)
					{
						$focus_td.prepend(this.marker.get());
					}
				}

				$current_tr.remove();
				this.table.restoreAfterDelete($table);
			},
			deleteColumn: function()
			{
				var $table = this.table.getTable();
				if (!$table)
				{
					return;
				}

				this.buffer.set();

				var $current = $(this.selection.current());
				var $current_td = $current.closest('td, th');
				var index = $current_td[0].cellIndex;

				$table.find('tr').each($.proxy(function(i, elem)
				{
					var $elem = $(elem);
					var focusIndex = index - 1 < 0 ? index + 1 : index - 1;
					if (i === 0)
					{
						$elem.find('td, th').eq(focusIndex).prepend(this.marker.get());
					}

					$elem.find('td, th').eq(index).remove();

				}, this));

				this.table.restoreAfterDelete($table);
			},
			addHead: function()
			{
				var $table = this.table.getTable();
				if (!$table)
				{
					return;
				}

				this.buffer.set();

				if ($table.find('thead').length !== 0)
				{
					this.table.deleteHead();
					return;
				}

				var tr = $table.find('tr').first().clone();
				tr.find('td').replaceWith($.proxy(function()
				{
					return $('<th>').html(this.opts.invisibleSpace);
				}, this));

				$thead = $('<thead></thead>').append(tr);
				$table.prepend($thead);



			},
			deleteHead: function()
			{
				var $table = this.table.getTable();
				if (!$table)
				{
					return;
				}

				var $thead = $table.find('thead');
				if ($thead.length === 0)
				{
					return;
				}

				this.buffer.set();

				$thead.remove();

			},
			addRowAbove: function()
			{
				this.table.addRow('before');
			},
			addRowBelow: function()
			{
				this.table.addRow('after');
			},
			addColumnLeft: function()
			{
				this.table.addColumn('before');
			},
			addColumnRight: function()
			{
				this.table.addColumn('after');
			},
			addRow: function(type)
			{
				var $table = this.table.getTable();
				if (!$table)
				{
					return;
				}

				this.buffer.set();

				var $current = $(this.selection.current());
				var $current_tr = $current.closest('tr');
				var new_tr = $current_tr.clone();

				new_tr.find('th').replaceWith(function()
				{
					var $td = $('<td>');
					$td[0].attributes = this.attributes;

					return $td.append($(this).contents());
				});

				new_tr.find('td').html(this.opts.invisibleSpace);

				if (type === 'after')
				{
					$current_tr.after(new_tr);
				}
				else
				{
					$current_tr.before(new_tr);
				}


			},
			addColumn: function (type)
			{
				var $table = this.table.getTable();
				if (!$table)
				{
					return;
				}

				var index = 0;
				var current = $(this.selection.current());

				this.buffer.set();

				var $current_tr = current.closest('tr');
				var $current_td = current.closest('td, th');

				$current_tr.find('td, th').each($.proxy(function(i, elem)
				{
					if ($(elem)[0] === $current_td[0])
					{
						index = i;
					}

				}, this));

				$table.find('tr').each($.proxy(function(i, elem)
				{
					var $current = $(elem).find('td, th').eq(index);

					var td = $current.clone();
					td.html(this.opts.invisibleSpace);

					if (type === 'after')
					{
						$current.after(td);
					}
					else
					{
						$current.before(td);
					}

				}, this));


			}
		};
	};
})(jQuery);