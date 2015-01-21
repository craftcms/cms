<?php

return [
	'clientaccount'                                                                              => ['route' => 'users/editUser', 'defaults' => ['account' => 'client']],
	'entries/<sectionHandle:{handle}>/<entryId:\d+><slug:(?:-{slug}?)?>/drafts/<draftId:\d+>'    => 'entries/editEntry',
	'entries/<sectionHandle:{handle}>/<entryId:\d+><slug:(?:-{slug})?>/versions/<versionId:\d+>' => 'entries/editEntry',
];
