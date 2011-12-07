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
INSERT INTO `blx_info` (`edition`,`version`,`build`) VALUES ('Standard','0.1', '1');

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
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`display_order`) VALUES ('1', 'UpdateWidget', '1');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`display_order`) VALUES ('1', 'RecentActivityWidget', '2');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`display_order`) VALUES ('1', 'ContentWidget', '3');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`display_order`) VALUES ('1', 'FeedWidget', '4');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`display_order`) VALUES ('2', 'UpdateWidget', '1');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`display_order`) VALUES ('2', 'RecentActivityWidget', '2');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`display_order`) VALUES ('2', 'ContentWidget', '3');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`display_order`) VALUES ('2', 'FeedWidget', '4');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`display_order`) VALUES ('3', 'UpdateWidget', '1');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`display_order`) VALUES ('3', 'RecentActivityWidget', '2');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`display_order`) VALUES ('3', 'ContentWidget', '3');
INSERT INTO `blx_userwidgets` (`user_id`,`type`,`display_order`) VALUES ('3', 'FeedWidget', '4');
