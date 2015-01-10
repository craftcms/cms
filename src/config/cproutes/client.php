<?php

return [
	'clientaccount'                                                                                => ['action' => 'users/editUser', 'params' => ['account' => 'client']],
	'entries/(?P<sectionHandle>{handle})/(?P<entryId>\d+)(?:-{slug}?)?/drafts/(?P<draftId>\d+)'    => ['action' => 'entries/editEntry'],
	'entries/(?P<sectionHandle>{handle})/(?P<entryId>\d+)(?:-{slug})?/versions/(?P<versionId>\d+)' => ['action' => 'entries/editEntry'],
];
