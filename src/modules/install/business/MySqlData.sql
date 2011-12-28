/*
	Data for the table `groups`
*/
INSERT INTO `blx_groups` (`name`,`description`) VALUES ('Administrator', 'Can do everything.');
INSERT INTO `blx_groups` (`name`,`description`) VALUES ('Author', 'Can add and edit entries.');

/*
	Data for the table `sites`
*/
INSERT INTO `blx_sites` (`handle`,`label`,`url`) VALUES ('default', 'Default Site', 'blocks.dev');

/*
	Data for the table `info`
*/
INSERT INTO `blx_info` (`edition`,`version`,`build`) VALUES ('Standard', '0.1', '1');

/*
	Data for the table `licensekeys`
*/
INSERT INTO `blx_licensekeys` (`key`) VALUES ('70573ac9-d16b-498a-8be5-eb196b6bda9b-a89568ed-20a6-46a8-b402-c07721e34d7a');
INSERT INTO `blx_licensekeys` (`key`) VALUES ('edc591cd-ebb0-4cfd-96fd-2423c2f1c8a3-cbb099c4-fe6a-410d-b58e-dfa520cce28f');

/*
	Data for the table `plugins`
*/
INSERT INTO `blx_plugins` (`name`,`version`,`enabled`) VALUES ('BrilliantRetail', '1.0', '1');
INSERT INTO `blx_plugins` (`name`,`version`,`enabled`) VALUES ('GAnalytics', '0.01', '1');
INSERT INTO `blx_plugins` (`name`,`version`,`enabled`) VALUES ('Wygwam', '1.0', '1');

/*
	Data for the table `users`
*/
INSERT INTO `blx_users` (`user_name`,`email`,`first_name`,`last_name`,`password`,`salt`) VALUES ('brad', 'brad@pixelandtonic.com', 'Brad', 'Bell', 'letmein', 'letmein');
INSERT INTO `blx_users` (`user_name`,`email`,`first_name`,`last_name`,`password`,`salt`) VALUES ('admin', 'admin@pixelandtonic.com', 'Ad', 'Min', 'letmein', 'letmein');
INSERT INTO `blx_users` (`user_name`,`email`,`first_name`,`last_name`,`password`,`salt`) VALUES ('brandon', 'brandon@pixelandtonic.com', 'Brandon', 'Kelly', 'letmein', 'letmein');

/*
	Data for the table `userwidgets`
*/
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`sort_order`) VALUES ('1', 'UpdatesWidget', '1');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`sort_order`) VALUES ('1', 'RecentActivityWidget', '2');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`sort_order`) VALUES ('1', 'SiteMapWidget', '3');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`sort_order`) VALUES ('1', 'FeedWidget', '4');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`sort_order`) VALUES ('2', 'UpdatesWidget', '1');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`sort_order`) VALUES ('2', 'RecentActivityWidget', '2');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`sort_order`) VALUES ('2', 'SiteMapWidget', '3');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`sort_order`) VALUES ('2', 'FeedWidget', '4');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`sort_order`) VALUES ('3', 'UpdatesWidget', '1');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`sort_order`) VALUES ('3', 'RecentActivityWidget', '2');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`sort_order`) VALUES ('3', 'SiteMapWidget', '3');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`sort_order`) VALUES ('3', 'FeedWidget', '4');
