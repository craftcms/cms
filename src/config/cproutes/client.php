<?php

return [
	'clientaccount'                                                                              => ['route' => 'users/edit-user', 'defaults' => ['userId' => 'client']],
	'entries/<sectionHandle:{handle}>/<entryId:\d+><slug:(?:-{slug}?)?>/drafts/<draftId:\d+>'    => 'entries/edit-entry',
	'entries/<sectionHandle:{handle}>/<entryId:\d+><slug:(?:-{slug})?>/versions/<versionId:\d+>' => 'entries/edit-entry',
];
