<?php

	class UnitTest_tidyhtml extends UnitTest
	{
		protected $_tidyPath;

		protected $_tests = array();

		protected $_ignoreErrors = array(
			'~Warning: trimming empty <[^>]+>~',
		);

		protected $_id_board;
		protected $_id_topic;
		protected $_id_topic2;
		protected $_id_msg;
		protected $_id_msg2;
		protected $_id_msg3;
		protected $_id_member;
		protected $_id_cat;

		public function __construct()
		{
			global $boarddir, $scripturl;

			$this->_tidyPath = $boarddir . '/other/unittest/validation/tidy.exe';
			$this->_openSpPath = $boarddir . '/other/unittest/validation/onsgmls.exe';

			$this->_id_board = $this->_getUnitTestBoardId();

			$this->_id_member = $this->_getUnitTestMemberId('admin');

			list ($this->_id_msg, $this->_id_topic) = $this->_getUnitTestTopic($this->_id_board, $this->_id_member, 'Testing HTML tidy', 'This topic will is only there to check if the display page is properly xHTML compatible.');
			$this->_createReply($this->_id_board, $this->_id_topic, $this->_id_member, 'HTML tidy reply', 'A reply to the topic');

			list ($this->_id_msg3, $this->_id_topic2) = $this->_getUnitTestTopic($this->_id_board, $this->_id_member, 'Testing HTML tidy - merge topic', 'This topic is needed to test the second step of a topic merge.');
			$this->_id_cat = $this->_getUnitTestCatId();

			$this->_tests = array(
				'Admin_1' => array(
					'name' => 'Admin (1)',
					'description' => "Main admin center",
					'url' => SCRIPT . '?action=admin',
				),
				'Admin_2' => array(
					'name' => 'Admin (2)',
					'description' => "Copyright removal",
					'url' => SCRIPT . '?action=admin;area=copyright',
				),
				'Admin_3' => array(
					'name' => 'Admin (3)',
					'description' => "Search in admin center",
					'url' => SCRIPT . '?action=admin;area=search;search_term=template',
				),
				'Admin_4' => array(
					'name' => 'Admin (4)',
					'description' => "Error logs",
					'url' => SCRIPT . '?action=admin;area=logs;sa=errorlog',
				),
				'Admin_5' => array(
					'name' => 'Admin (5)',
					'description' => "Admin logs",
					'url' => SCRIPT . '?action=admin;area=logs;sa=adminlog',
				),
				'Admin_6' => array(
					'name' => 'Admin (6)',
					'description' => "Mod logs",
					'url' => SCRIPT . '?action=admin;area=logs;sa=modlog',
				),
				'Admin_7' => array(
					'name' => 'Admin (7)',
					'description' => "Ban logs",
					'url' => SCRIPT . '?action=admin;area=logs;sa=banlog',
				),
				'Admin_8' => array(
					'name' => 'Admin (8)',
					'description' => "Spider logs",
					'url' => SCRIPT . '?action=admin;area=logs;sa=spiderlog',
				),
				'Admin_9' => array(
					'name' => 'Admin (9)',
					'description' => "Task logs",
					'url' => SCRIPT . '?action=admin;area=logs;sa=tasklog',
				),
				'Boards_1' => array(
					'name' => 'Board list (1)',
					'description' => "Board list",
					'url' => $scripturl,
				),
				'Display_1' => array(
					'name' => 'Display (1)',
					'description' => "Simple display screen",
					'url' => SCRIPT . '?topic=' . $this->_id_topic . '.0',
				),
				'Groups_1' => array(
					'name' => 'Moderation center - Groups (1)',
					'description' => "Group overview",
					'url' => SCRIPT . '?action=moderate;area=groups;sa=index',
				),
				'Groups_2' => array(
					'name' => 'Moderation center - Groups (2)',
					'description' => "Members within a group",
					'url' => SCRIPT . '?action=moderate;area=groups;sa=members;group=1',
				),
				'Groups_3' => array(
					'name' => 'Moderation center - Groups (3)',
					'description' => "Group requests",
					'url' => SCRIPT . '?action=moderate;area=groups;sa=requests',
				),
				'Login_1' => array(
					'name' => 'Login (1)',
					'description' => "Login screen",
					'url' => SCRIPT . '?action=login',
					'id_member' => 0,
				),
				'ManageAttachments_1' => array(
					'name' => 'Manage attachments (1)',
					'description' => "Attachment list",
					'url' => SCRIPT . '?action=admin;area=manageattachments',
				),
				'ManageAttachments_2' => array(
					'name' => 'Manage attachments (2)',
					'description' => "Browse avatars",
					'url' => SCRIPT . '?action=admin;area=manageattachments;sa=browse;avatars',
				),
				'ManageAttachments_3' => array(
					'name' => 'Manage attachments (3)',
					'description' => "Browse thumbs",
					'url' => SCRIPT . '?action=admin;area=manageattachments;sa=browse;thumbs',
				),
				'ManageAttachments_4' => array(
					'name' => 'Manage attachments (4)',
					'description' => "Attachment settings",
					'url' => SCRIPT . '?action=admin;area=manageattachments;sa=attachments',
				),
				'ManageAttachments_5' => array(
					'name' => 'Manage attachments (5)',
					'description' => "Avatar settings",
					'url' => SCRIPT . '?action=admin;area=manageattachments;sa=avatars',
				),
				'ManageAttachments_6' => array(
					'name' => 'Manage attachments (6)',
					'description' => "File maintenance",
					'url' => SCRIPT . '?action=admin;area=manageattachments;sa=maintenance',
				),
				'ManageAttachments_7' => array(
					'name' => 'Manage attachments (7)',
					'description' => "Configure multiple upload paths",
					'url' => SCRIPT . '?action=admin;area=manageattachments;sa=attachpaths',
				),
				'ManageBans_1' => array(
					'name' => 'Manage bans (1)',
					'description' => "Ban list",
					'url' => SCRIPT . '?action=admin;area=ban',
				),
				'ManageBans_2' => array(
					'name' => 'Manage bans (2)',
					'description' => "Add ban",
					'url' => SCRIPT . '?action=admin;area=ban;sa=add',
				),
				'ManageBans_3' => array(
					'name' => 'Manage bans (3)',
					'description' => "Ban trigger list",
					'url' => SCRIPT . '?action=admin;area=ban;sa=browse',
				),
				'ManageBans_4' => array(
					'name' => 'Manage bans (4)',
					'description' => "Ban log",
					'url' => SCRIPT . '?action=admin;area=ban;sa=log',
				),
				'ManageBoards_1' => array(
					'name' => 'Manage boards (1)',
					'description' => "Board overview",
					'url' => SCRIPT . '?action=admin;area=manageboards',
				),
				'ManageBoards_2' => array(
					'name' => 'Manage boards (2)',
					'description' => "Edit category",
					'url' => SCRIPT . '?action=admin;area=manageboards;sa=cat;cat=' . $this->_id_cat,
				),
				'ManageBoards_3' => array(
					'name' => 'Manage boards (3)',
					'description' => "Add category",
					'url' => SCRIPT . '?action=admin;area=manageboards;sa=newcat',
				),
				'ManageBoards_4' => array(
					'name' => 'Manage boards (4)',
					'description' => "Board overview - move board",
					'url' => SCRIPT . '?action=admin;area=manageboards;move=' . $this->_id_board . '.0',
				),
				'ManageBoards_5' => array(
					'name' => 'Manage boards (5)',
					'description' => "Edit board",
					'url' => SCRIPT . '?action=admin;area=manageboards;sa=board;boardid=' . $this->_id_board . '.0',
				),
				'ManageBoards_6' => array(
					'name' => 'Manage boards (6)',
					'description' => "Add board",
					'url' => SCRIPT . '?action=admin;area=manageboards;sa=newboard;cat=' . $this->_id_cat . '.0',
				),
				'ManageMail_1' => array(
					'name' => 'Manage mail (1)',
					'description' => "Mail queue",
					'url' => SCRIPT . '?action=admin;area=mailqueue',
				),
				'ManageMail_2' => array(
					'name' => 'Manage mail (2)',
					'description' => "Mail settings",
					'url' => SCRIPT . '?action=admin;area=mailqueue;sa=settings',
				),
				'ManageMaintenance_1' => array(
					'name' => 'Manage maintenance (1)',
					'description' => "Maintenance overview",
					'url' => SCRIPT . '?action=admin;area=maintain',
				),
				'ManageMaintenance_2' => array(
					'name' => 'Manage maintenance (2)',
					'description' => "Optimize tables",
					'url' => SCRIPT . '?action=admin;area=maintain;sa=optimize',
				),
				'ManageMaintenance_3' => array(
					'name' => 'Manage maintenance (3)',
					'description' => "Check version",
					'url' => SCRIPT . '?action=admin;area=maintain;sa=version',
				),
				'ManageMaintenance_4' => array(
					'name' => 'Manage maintenance (4)',
					'description' => "Remove inactive members",
					'url' => SCRIPT . '?action=admin;area=maintain;sa=admintask;activity=maintain_members',
				),
				'ManageMaintenance_5' => array(
					'name' => 'Manage maintenance (5)',
					'description' => "Reattribute posts",
					'url' => SCRIPT . '?action=admin;area=maintain;sa=admintask;activity=maintain_reattribute_posts',
				),
				'ManageMaintenance_6' => array(
					'name' => 'Manage maintenance (6)',
					'description' => "Reattribute posts",
					'url' => SCRIPT . '?action=admin;area=maintain;sa=admintask;activity=maintain_old',
				),
				'ManageMaintenance_7' => array(
					'name' => 'Manage maintenance (7)',
					'description' => "Move topics",
					'url' => SCRIPT . '?action=admin;area=maintain;sa=admintask;activity=move_topics_maintenance',
				),
				'ManageMaintenance_8' => array(
					'name' => 'Manage maintenance (8)',
					'description' => "Convert to UTF-8",
					'url' => SCRIPT . '?action=admin;area=maintain;sa=convertutf8',
				),
				'ManageMembergroups_1' => array(
					'name' => 'Manage membergroups (1)',
					'description' => "Membergroup overview",
					'url' => SCRIPT . '?action=admin;area=membergroups',
				),
				'ManageMembergroups_2' => array(
					'name' => 'Manage membergroups (2)',
					'description' => "Membergroup members overview",
					'url' => SCRIPT . '?action=admin;area=membergroups;sa=members;group=1',
				),
				'ManageMembergroups_3' => array(
					'name' => 'Manage membergroups (3)',
					'description' => "Edit membergroup admin",
					'url' => SCRIPT . '?action=admin;area=membergroups;sa=edit;group=1',
				),
				'ManageMembergroups_4' => array(
					'name' => 'Manage membergroups (4)',
					'description' => "Edit post count based membergroup",
					'url' => SCRIPT . '?action=admin;area=membergroups;sa=edit;group=4',
				),
				'ManageMembergroups_5' => array(
					'name' => 'Manage membergroups (5)',
					'description' => "Add membergroup",
					'url' => SCRIPT . '?action=admin;area=membergroups;sa=add',
				),
				'ManageMembergroups_6' => array(
					'name' => 'Manage membergroups (6)',
					'description' => "Membergroup settings",
					'url' => SCRIPT . '?action=admin;area=membergroups;sa=settings',
				),
				'ManageMembers_1' => array(
					'name' => 'Manage members (1)',
					'description' => "View members",
					'url' => SCRIPT . '?action=admin;area=viewmembers',
				),
				'ManageMembers_2' => array(
					'name' => 'Manage members (2)',
					'description' => "Search for members",
					'url' => SCRIPT . '?action=admin;area=viewmembers;sa=search',
				),
				'ManageMembers_3' => array(
					'name' => 'Manage members (3)',
					'description' => "Show duplicates in members awaiting activation",
					'url' => SCRIPT . '?action=admin;area=viewmembers;sa=browse;showdupes=1;type=activate',
				),
				'ManageNews_1' => array(
					'name' => 'Manage news (1)',
					'description' => "Show news items",
					'url' => SCRIPT . '?action=admin;area=news',
				),
				'ManageNews_2' => array(
					'name' => 'Manage news (2)',
					'description' => "Compose a mailing",
					'url' => SCRIPT . '?action=admin;area=news;sa=mailingmembers',
				),
				'ManageNews_3' => array(
					'name' => 'Manage news (3)',
					'description' => "News settings",
					'url' => SCRIPT . '?action=admin;area=news;sa=settings',
				),
				'ManagePaid_1' => array(
					'name' => 'Manage paid subscriptions (1)',
					'description' => "Subscription settings",
					'url' => SCRIPT . '?action=admin;area=paidsubscribe',
				),
				'ManagePaid_2' => array(
					'name' => 'Manage paid subscriptions (2)',
					'description' => "Subscription settings",
					'url' => SCRIPT . '?action=admin;area=paidsubscribe;sa=view',
				),
				'ManagePaid_3' => array(
					'name' => 'Manage paid subscriptions (3)',
					'description' => "View subscriptions",
					'url' => SCRIPT . '?action=admin;area=paidsubscribe;sa=modify',
				),
				'ManagePaid_4' => array(
					'name' => 'Manage paid subscriptions (4)',
					'description' => "View subscriptions",
					'url' => SCRIPT . '?action=admin;area=paidsubscribe;sa=viewsub;sid=1',
				),
				'ManagePermissions_1' => array(
					'name' => 'Manage permissions (1)',
					'description' => "General permission overview",
					'url' => SCRIPT . '?action=admin;area=permissions',
				),
				'ManagePermissions_2' => array(
					'name' => 'Manage permissions (2)',
					'description' => "General permission settings for guests [simple]",
					'url' => SCRIPT . '?action=admin;area=permissions;sa=modify;group=-1;view=simple',
				),
				'ManagePermissions_3' => array(
					'name' => 'Manage permissions (3)',
					'description' => "General permission settings for guests [classic]",
					'url' => SCRIPT . '?action=admin;area=permissions;sa=modify;group=-1;view=classic',
				),
				'ManagePermissions_4' => array(
					'name' => 'Manage permissions (4)',
					'description' => "Board permission overview",
					'url' => SCRIPT . '?action=admin;area=permissions;sa=board',
				),
				'ManagePermissions_5' => array(
					'name' => 'Manage permissions (5)',
					'description' => "Board permission overview (edit all)",
					'url' => SCRIPT . '?action=admin;area=permissions;sa=board;edit',
				),
				'ManagePermissions_6' => array(
					'name' => 'Manage permissions (6)',
					'description' => "Edit profiles",
					'url' => SCRIPT . '?action=admin;area=permissions;sa=profiles',
				),
				'ManagePermissions_7' => array(
					'name' => 'Manage permissions (7)',
					'description' => "Edit profile 'default'",
					'url' => SCRIPT . '?action=admin;area=permissions;sa=index;pid=1',
				),
				'ManagePermissions_8' => array(
					'name' => 'Manage permissions (8)',
					'description' => "Post moderation",
					'url' => SCRIPT . '?action=admin;area=permissions;sa=postmod',
				),
				'ManagePermissions_9' => array(
					'name' => 'Manage permissions (9)',
					'description' => "Permission settings",
					'url' => SCRIPT . '?action=admin;area=permissions;sa=settings',
				),
				'ManagePosts_1' => array(
					'name' => 'Manage posts and topics (1)',
					'description' => "Post settings",
					'url' => SCRIPT . '?action=admin;area=postsettings',
				),
				'ManagePosts_2' => array(
					'name' => 'Manage posts and topics (2)',
					'description' => 'Bulletin Board Code',
					'url' => SCRIPT . '?action=admin;area=postsettings;sa=bbc',
				),
				'ManagePosts_3' => array(
					'name' => 'Manage posts and topics (3)',
					'description' => "Censored Words",
					'url' => SCRIPT . '?action=admin;area=postsettings;sa=censor',
				),
				'ManagePosts_4' => array(
					'name' => 'Manage posts and topics (4)',
					'description' => "Topic settings",
					'url' => SCRIPT . '?action=admin;area=postsettings;sa=topics',
				),
				'ManageRegistration_1' => array(
					'name' => 'Manage registration (1)',
					'description' => "Register a new member",
					'url' => SCRIPT . '?action=admin;area=regcenter;sa=register',
				),
				'ManageRegistration_2' => array(
					'name' => 'Manage registration (2)',
					'description' => "Registration Agreement",
					'url' => SCRIPT . '?action=admin;area=regcenter;sa=agreement',
				),
				'ManageRegistration_4' => array(
					'name' => 'Manage registration (4)',
					'description' => "Settings",
					'url' => SCRIPT . '?action=admin;area=regcenter;sa=settings',
				),
				'ManageSearch_1' => array(
					'name' => 'Manage search (1)',
					'description' => "Search weights",
					'url' => SCRIPT . '?action=admin;area=managesearch;sa=weights',
				),
				'ManageSearch_2' => array(
					'name' => 'Manage search (2)',
					'description' => "Search sethod",
					'url' => SCRIPT . '?action=admin;area=managesearch;sa=method',
				),
				'ManageSearch_3' => array(
					'name' => 'Manage search (3)',
					'description' => "Search settings",
					'url' => SCRIPT . '?action=admin;area=managesearch;sa=settings',
				),
				'ManageSearchEngines_1' => array(
					'name' => 'Manage search engines (1)',
					'description' => "Search engine stats",
					'url' => SCRIPT . '?action=admin;area=sengines;sa=stats',
				),
				'ManageSearchEngines_2' => array(
					'name' => 'Manage search engines (2)',
					'description' => "Spider Log",
					'url' => SCRIPT . '?action=admin;area=sengines;sa=logs',
				),
				'ManageSearchEngines_3' => array(
					'name' => 'Manage search engines (3)',
					'description' => "Spiders",
					'url' => SCRIPT . '?action=admin;area=sengines;sa=spiders',
				),
				'ManageSearchEngines_4' => array(
					'name' => 'Manage search engines (4)',
					'description' => "Settings",
					'url' => SCRIPT . '?action=admin;area=sengines;sa=settings',
				),
				'ManageServer_1' => array(
					'name' => 'Manage server settings (1)',
					'description' => 'Core configuration',
					'url' => SCRIPT . '?action=admin;area=serversettings;sa=core',
				),
				'ManageServer_2' => array(
					'name' => 'Manage server settings (2)',
					'description' => 'Feature Configuration',
					'url' => SCRIPT . '?action=admin;area=serversettings;sa=other',
				),
				'ManageServer_3' => array(
					'name' => 'Manage server settings (3)',
					'description' => "Languages",
					'url' => SCRIPT . '?action=admin;area=serversettings;sa=languages',
				),
				'ManageServer_4' => array(
					'name' => 'Manage server settings (4)',
					'description' => "Caching",
					'url' => SCRIPT . '?action=admin;area=serversettings;sa=cache',
				),
				'ManageSettings_1' => array(
					'name' => 'Manage settings (1)',
					'description' => "Core features",
					'url' => SCRIPT . '?action=admin;area=featuresettings;sa=core',
				),
				'ManageSettings_2' => array(
					'name' => 'Manage settings (2)',
					'description' => "Options",
					'url' => SCRIPT . '?action=admin;area=featuresettings;sa=basic',
				),
				'ManageSettings_3' => array(
					'name' => 'Manage settings (3)',
					'description' => "Layout",
					'url' => SCRIPT . '?action=admin;area=featuresettings;sa=layout',
				),
				'ManageSettings_4' => array(
					'name' => 'Manage settings (4)',
					'description' => "Signatures",
					'url' => SCRIPT . '?action=admin;area=featuresettings;sa=sig',
				),
				'ManageSettings_5' => array(
					'name' => 'Manage settings (5)',
					'description' => "Profile Fields",
					'url' => SCRIPT . '?action=admin;area=featuresettings;sa=profile',
				),
				'ManageSettings_6' => array(
					'name' => 'Manage settings (6)',
					'description' => "Log Pruning",
					'url' => SCRIPT . '?action=admin;area=logs;sa=settings',
				),
				'ManageSettings_7' => array(
					'name' => 'Manage settings (7)',
					'description' => "Core features",
					'url' => SCRIPT . '?action=admin;area=securitysettings;sa=general',
				),
				'ManageSettings_8' => array(
					'name' => 'Manage settings (8)',
					'description' => "Core features",
					'url' => SCRIPT . '?action=admin;area=securitysettings;sa=spam',
				),
				'ManageSettings_9' => array(
					'name' => 'Manage settings (9)',
					'description' => "Core features",
					'url' => SCRIPT . '?action=admin;area=securitysettings;sa=moderation',
				),
				'ManageSmileys_1' => array(
					'name' => 'Manage smileys (1)',
					'description' => "Smiley Sets",
					'url' => SCRIPT . '?action=admin;area=smileys;sa=editsets',
				),
				'ManageSmileys_2' => array(
					'name' => 'Manage smileys (2)',
					'description' => "Edit smiley set",
					'url' => SCRIPT . '?action=admin;area=smileys;sa=modifyset;set=0',
				),
				'ManageSmileys_3' => array(
					'name' => 'Manage smileys (3)',
					'description' => "Add smiley set",
					'url' => SCRIPT . '?action=admin;area=smileys;sa=modifyset',
				),
				'ManageSmileys_4' => array(
					'name' => 'Manage smileys (4)',
					'description' => "Add smiley",
					'url' => SCRIPT . '?action=admin;area=smileys;sa=addsmiley',
				),
				'ManageSmileys_5' => array(
					'name' => 'Manage smileys (5)',
					'description' => "Smiley overview",
					'url' => SCRIPT . '?action=admin;area=smileys;sa=editsmileys',
				),
				'ManageSmileys_6' => array(
					'name' => 'Manage smileys (6)',
					'description' => "Edit smiley",
					'url' => SCRIPT . '?action=admin;area=smileys;sa=modifysmiley;smiley=1',
				),
				'ManageSmileys_7' => array(
					'name' => 'Manage smileys (7)',
					'description' => "Set smiley order",
					'url' => SCRIPT . '?action=admin;area=smileys;sa=setorder',
				),
				'ManageSmileys_8' => array(
					'name' => 'Manage smileys (8)',
					'description' => "Set smiley order (move smiley)",
					'url' => SCRIPT . '?action=admin;area=smileys;sa=setorder;move=1',
				),
				'ManageSmileys_9' => array(
					'name' => 'Manage smileys (9)',
					'description' => "Message icon overview",
					'url' => SCRIPT . '?action=admin;area=smileys;sa=editicons',
				),
				'ManageSmileys_10' => array(
					'name' => 'Manage smileys (10)',
					'description' => "Add message icon",
					'url' => SCRIPT . '?action=admin;area=smileys;sa=editicon',
				),
				'ManageSmileys_11' => array(
					'name' => 'Manage smileys (11)',
					'description' => "Edit message icon",
					'url' => SCRIPT . '?action=admin;area=smileys;sa=editicon;icon=1',
				),
				'ManageSmileys_12' => array(
					'name' => 'Manage smileys (12)',
					'description' => "Message icon settings",
					'url' => SCRIPT . '?action=admin;area=smileys;sa=settings',
				),
				'Memberlist_1' => array(
					'name' => 'Memberlist (1)',
					'description' => "Memberlist",
					'url' => SCRIPT . '?action=mlist',
				),
				'Memberlist_2' => array(
					'name' => 'Memberlist (2)',
					'description' => "Memberlist search",
					'url' => SCRIPT . '?action=mlist;sa=search',
				),
				'MessageIndex_1' => array(
					'name' => 'Message index (1)',
					'description' => "Message index as an admin",
					'url' => SCRIPT . '?board=' . $this->_id_board . '.0',
				),
				'ModerationCenter_1' => array(
					'name' => 'Moderation center (1)',
					'description' => "Moderation center index",
					'url' => SCRIPT . '?action=moderate',
				),
				'ModerationCenter_2' => array(
					'name' => 'Moderation center (2)',
					'description' => "Moderation center active reports",
					'url' => SCRIPT . '?action=moderate;area=reports',
				),
				'ModerationCenter_3' => array(
					'name' => 'Moderation center (3)',
					'description' => "Show moderation notic",
					'url' => SCRIPT . '?action=moderate;area=notice;nid=1',
				),
				'ModerationCenter_4' => array(
					'name' => 'Moderation center (4)',
					'description' => "View watched users by member",
					'url' => SCRIPT . '?action=moderate;area=userwatch;sa=member',
				),
				'ModerationCenter_5' => array(
					'name' => 'Moderation center (5)',
					'description' => "View watched users by post",
					'url' => SCRIPT . '?action=moderate;area=userwatch;sa=post',
				),
				'ModerationCenter_6' => array(
					'name' => 'Moderation center (6)',
					'description' => "View warnings",
					'url' => SCRIPT . '?action=moderate;area=warnings;sa=log',
				),
				'ModerationCenter_7' => array(
					'name' => 'Moderation center (7)',
					'description' => "View customer templates",
					'url' => SCRIPT . '?action=moderate;area=warnings;sa=templateedit',
				),
				'ModerationCenter_8' => array(
					'name' => 'Moderation center (8)',
					'description' => "Add template",
					'url' => SCRIPT . '?action=moderate;area=warnings;sa=templates',
				),
				'ModerationCenter_9' => array(
					'name' => 'Moderation center (9)',
					'description' => "Moderation center settings",
					'url' => SCRIPT . '?action=moderate;area=settings',
				),
				'MoveTopic_1' => array(
					'name' => 'Move topic (1)',
					'description' => "Move a topic",
					'url' => SCRIPT . '?action=movetopic;topic=' . $this->_id_topic . '.0',
				),
				'Notify_1' => array(
					'name' => 'Notify (1)',
					'description' => "Confirmation of topic notification",
					'url' => SCRIPT . '?action=notify;topic=' . $this->_id_topic . '.0',
				),
				'Notify_2' => array(
					'name' => 'Notify (2)',
					'description' => "Confirmation of board notification",
					'url' => SCRIPT . '?action=notifyboard;board=' . $this->_id_board . '.0',
				),
				'PackageGet_1' => array(
					'name' => 'Package center (1)',
					'description' => "Browse packages",
					'url' => SCRIPT . '?action=admin;area=packages;sa=browse',
				),
				'PackageGet_2' => array(
					'name' => 'Package center (2)',
					'description' => "Download packages",
					'url' => SCRIPT . '?action=admin;area=packages;sa=packageget;get',
				),
				'PackageGet_3' => array(
					'name' => 'Package center (3)',
					'description' => "Browse server",
					'url' => SCRIPT . '?action=admin;area=packages;sa=browse;server=1',
				),
				'PackageGet_4' => array(
					'name' => 'Package center (4)',
					'description' => "Installed packages",
					'url' => SCRIPT . '?action=admin;area=packages;sa=installed',
				),
				'PackageGet_5' => array(
					'name' => 'Package center (5)',
					'description' => "File permissions",
					'url' => SCRIPT . '?action=admin;area=packages;sa=perms',
				),
				'PackageGet_6' => array(
					'name' => 'Package center (6)',
					'description' => "Options",
					'url' => SCRIPT . '?action=admin;area=packages;sa=options',
				),
				'PersonalMessages_1' => array(
					'name' => 'Personal messages (1)',
					'description' => "Inbox",
					'url' => SCRIPT . '?action=pm',
				),
				'PersonalMessages_2' => array(
					'name' => 'Personal messages (2)',
					'description' => "Sent items",
					'url' => SCRIPT . '?action=pm;f=sent',
				),
				'PersonalMessages_3' => array(
					'name' => 'Personal messages (3)',
					'description' => "Send new message",
					'url' => SCRIPT . '?action=pm;sa=send',
				),
				'PersonalMessages_4' => array(
					'name' => 'Personal messages (4)',
					'description' => "Search messages",
					'url' => SCRIPT . '?action=pm;sa=search',
				),
				'PersonalMessages_6' => array(
					'name' => 'Personal messages (6)',
					'description' => "Prune messages",
					'url' => SCRIPT . '?action=pm;sa=prune',
				),
				'PersonalMessages_7' => array(
					'name' => 'Personal messages (7)',
					'description' => "Manage labels",
					'url' => SCRIPT . '?action=pm;sa=manlabels',
				),
				'PersonalMessages_8' => array(
					'name' => 'Personal messages (8)',
					'description' => "Manage rules",
					'url' => SCRIPT . '?action=pm',
				),
				'PersonalMessages_9' => array(
					'name' => 'Personal messages (9)',
					'description' => "Add rule",
					'url' => SCRIPT . '?action=pm;sa=manrules;add;rid=0',
				),
				'PersonalMessages_10' => array(
					'name' => 'Personal messages (10)',
					'description' => "Change settings",
					'url' => SCRIPT . '?action=pm;sa=settings',
				),
				'Post_1' => array(
					'name' => 'Post (1)',
					'description' => "Post new topic",
					'url' => SCRIPT . '?action=post;board=' . $this->_id_board . '.0',
				),
				'Post_2' => array(
					'name' => 'Post (2)',
					'description' => "Post new poll",
					'url' => SCRIPT . '?action=post;board=' . $this->_id_board . '.0;poll',
				),
				'Post_3' => array(
					'name' => 'Post (3)',
					'description' => "Post new reply",
					'url' => SCRIPT . '?action=post;topic=' . $this->_id_topic . '.0',
				),
				'Post_4' => array(
					'name' => 'Post (4)',
					'description' => "Announce topic",
					'url' => SCRIPT . '?action=announce;sa=selectgroup;topic=' . $this->_id_topic . '.0',
				),
				'PostModeration_1' => array(
					'name' => 'Post moderation (1)',
					'description' => "Unapproved replies",
					'url' => SCRIPT . '?action=moderate;area=postmod;sa=post',
				),
				'PostModeration_2' => array(
					'name' => 'Post moderation (2)',
					'description' => "Unapproved topics",
					'url' => SCRIPT . '?action=moderate;area=postmod;sa=topics',
				),
				'Printpage_1' => array(
					'name' => 'Print page (1)',
					'description' => "Print page",
					'url' => SCRIPT . '?action=printpage;topic=' . $this->_id_topic . '.0',
				),
				'Profile_1' => array(
					'name' => 'Profile (1)',
					'description' => "Profile summary",
					'url' => SCRIPT . '?action=profile;area=summary',
				),
				'Profile_2' => array(
					'name' => 'Profile (2)',
					'description' => "Show stats",
					'url' => SCRIPT . '?action=profile;area=statistics',
				),
				'Profile_3' => array(
					'name' => 'Profile (3)',
					'description' => "Show posts",
					'url' => SCRIPT . '?action=profile;area=showposts;sa=messages',
				),
				'Profile_4' => array(
					'name' => 'Profile (4)',
					'description' => "Show topics",
					'url' => SCRIPT . '?action=profile;area=showposts;sa=topics',
				),
				'Profile_5' => array(
					'name' => 'Profile (5)',
					'description' => "Show attachments",
					'url' => SCRIPT . '?action=profile;area=showposts;sa=attach',
				),
				'Profile_6' => array(
					'name' => 'Profile (6)',
					'description' => "Show permissions",
					'url' => SCRIPT . '?action=profile;area=permissions',
				),
				'Profile_7' => array(
					'name' => 'Profile (7)',
					'description' => "Track user",
					'url' => SCRIPT . '?action=profile;area=tracking;sa=user',
				),
				'Profile_8' => array(
					'name' => 'Profile (8)',
					'description' => "Track IP",
					'url' => SCRIPT . '?action=profile;area=tracking;sa=ip',
				),
				'Profile_9' => array(
					'name' => 'Profile (9)',
					'description' => "Track edits",
					'url' => SCRIPT . '?action=profile;area=tracking;sa=edits',
				),
				'Profile_10' => array(
					'name' => 'Profile (10)',
					'description' => "Account settings",
					'url' => SCRIPT . '?action=profile;area=account',
				),
				'Profile_11' => array(
					'name' => 'Profile (11)',
					'description' => "Profile settings",
					'url' => SCRIPT . '?action=profile;area=forumprofile',
				),
				'Profile_12' => array(
					'name' => 'Profile (12)',
					'description' => "Theme settings",
					'url' => SCRIPT . '?action=profile;area=theme',
				),
				'Profile_13' => array(
					'name' => 'Profile (13)',
					'description' => "Notification settings",
					'url' => SCRIPT . '?action=profile;area=notification',
				),
				'Profile_14' => array(
					'name' => 'Profile (14)',
					'description' => "Personal message preferences",
					'url' => SCRIPT . '?action=profile;area=pmprefs',
				),
				'Profile_15' => array(
					'name' => 'Profile (15)',
					'description' => "Ignore boards",
					'url' => SCRIPT . '?action=profile;area=ignoreboards',
				),
				'Profile_16' => array(
					'name' => 'Profile (16)',
					'description' => "Edit buddies",
					'url' => SCRIPT . '?action=profile;area=buddies',
				),
				'Profile_17' => array(
					'name' => 'Profile (17)',
					'description' => "Subscriptions",
					'url' => SCRIPT . '?action=profile;area=subscriptions',
				),
				'Profile_18' => array(
					'name' => 'Profile (18)',
					'description' => "Delete account",
					'url' => SCRIPT . '?action=profile;area=deleteaccount',
				),
				'Recent_1' => array(
					'name' => 'Recent posts (1)',
					'description' => "Recent posts",
					'url' => SCRIPT . '?action=recent',
				),
				'Recent_2' => array(
					'name' => 'Recent posts (2)',
					'description' => "Unread posts",
					'url' => SCRIPT . '?action=unread',
				),
				'Recent_3' => array(
					'name' => 'Recent posts (3)',
					'description' => "Unread replies",
					'url' => SCRIPT . '?action=unreadreplies',
				),
				'Register_1' => array(
					'name' => 'Register (1)',
					'description' => "Register account",
					'url' => SCRIPT . '?action=register',
					'id_member' => 0,
				),
				'Reminder_1' => array(
					'name' => 'Reminder (1)',
					'description' => "Authentication reminder",
					'url' => SCRIPT . '?action=reminder',
					'id_member' => 0,
				),
				'RepairBoards_1' => array(
					'name' => 'Repair boards (1)',
					'description' => "Repair check",
					'url' => SCRIPT . '?action=admin;area=repairboards',
				),
				'Reports_1' => array(
					'name' => 'Reports (1)',
					'description' => "Select report type",
					'url' => SCRIPT . '?action=admin;area=reports',
				),
				'Reports_2' => array(
					'name' => 'Reports (2)',
					'description' => "Boards report",
					'url' => SCRIPT . '?action=admin;area=reports;rt=boards',
				),
				'Reports_3' => array(
					'name' => 'Reports (3)',
					'description' => "Board permissions report",
					'url' => SCRIPT . '?action=admin;area=reports;rt=board_perms',
				),
				'Reports_4' => array(
					'name' => 'Reports (4)',
					'description' => "Membergroups report",
					'url' => SCRIPT . '?action=admin;area=reports;rt=member_groups',
				),
				'Reports_5' => array(
					'name' => 'Reports (5)',
					'description' => "Group permissions report",
					'url' => SCRIPT . '?action=admin;area=reports;rt=group_perms',
				),
				'Reports_6' => array(
					'name' => 'Reports (6)',
					'description' => "Staff report",
					'url' => SCRIPT . '?action=admin;area=reports;rt=staff',
				),
				'Search_1' => array(
					'name' => 'Search (1)',
					'description' => "Search forum",
					'url' => SCRIPT . '?action=search',
				),
				'SendTopic_1' => array(
					'name' => 'Send topic (1)',
					'description' => "Send topic",
					'url' => SCRIPT . '?action=emailuser;sa=sendtopic;topic=' . $this->_id_topic . '.0',
				),
				'SendTopic_2' => array(
					'name' => 'Send topic (2)',
					'description' => "Send user a mail",
					'url' => SCRIPT . '?action=emailuser;sa=email;msg=' . $this->_id_msg,
				),
				'SendTopic_3' => array(
					'name' => 'Send topic (3)',
					'description' => "Send topic",
					'url' => SCRIPT . '?action=report;topic=' . $this->_id_topic . '.0;msg=' . $this->_id_msg,
				),
				'SplitTopics_1' => array(
					'name' => 'Split topic (1)',
					'description' => "Split topic",
					'url' => SCRIPT . '?action=splittopics;topic=' . $this->_id_topic . '.0;at=' . $this->_id_msg2,
				),
				'SplitTopics_2' => array(
					'name' => 'Split topic (2)',
					'description' => "Merge topic",
					'url' => SCRIPT . '?action=mergetopics;board=' . $this->_id_board . '.0;from=' . $this->_id_topic,
				),
				'SplitTopics_3' => array(
					'name' => 'Split topic (3)',
					'description' => "Merge topic - step 2",
					'url' => SCRIPT . '?action=mergetopics;sa=options;board=' . $this->_id_board . '.0;from=' . $this->_id_topic . ';to=' . $this->_id_topic2,
				),
				'Stats_1' => array(
					'name' => 'Statistics center (1)',
					'description' => "Statistics center",
					'url' => SCRIPT . '?action=stats',
				),
				'Stats_2' => array(
					'name' => 'Statistics center (2)',
					'description' => "Statistics center - current month collapsed",
					'url' => SCRIPT . '?action=stats;expand=' . date('Ym'),
				),
				'Themes_1' => array(
					'name' => 'Themes (1)',
					'description' => "Manage and install",
					'url' => SCRIPT . '?action=admin;area=theme;sa=admin',
				),
				'Themes_2' => array(
					'name' => 'Themes (2)',
					'description' => "Theme list",
					'url' => SCRIPT . '?action=admin;area=theme;sa=list',
				),
				// !! @todo: remove this. Heck, remove this entire file... I don't do unit testing. Maybe someone else will, but not me.
				'Themes_3' => array(
					'name' => 'Themes (3)',
					'description' => "Default theme settings",
					'url' => SCRIPT . '?action=admin;area=theme;sa=settings;th=1',
				),
				'Themes_4' => array(
					'name' => 'Themes (4)',
					'description' => "Default theme options",
					'url' => SCRIPT . '?action=admin;area=theme;sa=reset;th=1',
				),
				'Themes_5' => array(
					'name' => 'Themes (5)',
					'description' => "Set/reset theme options for guests and new users",
					'url' => SCRIPT . '?action=admin;area=theme;sa=reset',
				),
				'Themes_6' => array(
					'name' => 'Themes (6)',
					'description' => "Set/reset theme options for all members",
					'url' => SCRIPT . '?action=admin;area=theme;sa=reset;who=1',
				),
				'Themes_7' => array(
					'name' => 'Themes (7)',
					'description' => "Modify themes",
					'url' => SCRIPT . '?action=admin;area=theme;sa=edit',
				),
				'Themes_8' => array(
					'name' => 'Themes (8)',
					'description' => "Browse templates",
					'url' => SCRIPT . '?action=admin;area=theme;sa=edit;th=1',
				),
				'Themes_9' => array(
					'name' => 'Themes (9)',
					'description' => "Edit template",
					'url' => SCRIPT . '?action=admin;area=theme;sa=edit;th=1;filename=Boards.template.php',
				),
				'Themes_10' => array(
					'name' => 'Themes (10)',
					'description' => "Edit CSS",
					'url' => SCRIPT . '?action=admin;area=theme;sa=edit;th=1;filename=css/admin.css',
				),
				'Themes_11' => array(
					'name' => 'Themes (11)',
					'description' => "Edit JS",
					'url' => SCRIPT . '?action=admin;area=theme;sa=edit;th=1;filename=scripts/script.js',
				),
				'Who_1' => array(
					'name' => 'Whos online (1)',
					'description' => "Whos online overview",
					'url' => SCRIPT . '?action=who',
				),
			);

		}

		public function initialize()
		{

		}

		public function getTests()
		{
			$tests = array();
			foreach ($this->_tests as $testID => $testInfo)
				$tests[$testID] = array(
					'name' => $testInfo['name'],
					'description' => $testInfo['description'],
				);

			return $tests;
		}

		public function doTest($testID)
		{
			global $scripturl;

			if (!isset($this->_tests[$testID]))
				return 'Invalid test ID given';

			$returnDoc = $this->_simulateClick($this->_tests[$testID]['url'], isset($this->_tests[$testID]['id_member']) ? $this->_tests[$testID]['id_member'] : $this->_id_member);
			$testResults = $this->_testHtml($returnDoc['html'], $this->_openSpPath . ' -wvalid -wnon-sgml-char-ref -wno-duplicate -E0 -s ' . dirname($this->_openSpPath) . '/xml.dcl -');
			if (empty($testResults))
				$testResults = $this->_testHtml($returnDoc['html'], $this->_tidyPath . ' -errors -quiet -access -1');

			if (empty($testResults))
				return true;
			else
				return htmlspecialchars(implode("\n", $testResults) . "\n" . $this->_tests[$testID]['url']);
		}

		public function getTestDescription($testID)
		{
			if (isset($this->_tests[$testID]['description']))
				return $this->_tests[$testID]['description'];
			elseif (isset($this->_tests[$testID]))
				return 'No description available';
			else
				return 'Invalid test ID given';
		}

		protected function _testHtml($html, $tool)
		{
			global $cachedir;

			// Apparently windows can't handle large stdin values, therefor a file streaming is needed..
			$tempFile = $cachedir . '/tmp_validator_' . md5(mt_rand(0, 10000000000)) . '.html';
			file_put_contents($tempFile, $html);

			$descriptorspec = array(
				0 => array('file', $tempFile, 'r'), // stdin
				1 => array('pipe', 'w'), // stdout
				2 => array('pipe', 'w') // stder
			);

			$process = @proc_open($tool, $descriptorspec, $pipes, null, null, array('bypass_shell' => true));

			if (is_resource($process))
			{
				fclose($pipes[1]);

				$errorList = array();
				while (!feof($pipes[2]))
				{
					$line = trim(fgets($pipes[2], 1024), "\n\r");
					if (empty($line))
						continue;

					foreach ($this->_ignoreErrors as $ignorePattern)
						if (preg_match($ignorePattern, $line) === 1)
							continue 2;
					$errorList[] = $line;
				}
				fclose($pipes[2]);

				proc_close($process);
			}
			else
				$errorList = array('Unable to test page');

			@unlink($tempFile);

			return $errorList;
		}
	}
