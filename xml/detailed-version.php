<?php

header('Content-Type: text/javascript');

if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']))
{
	list ($modified_since) = explode(';', $_SERVER['HTTP_IF_MODIFIED_SINCE']);
	if (strtotime($modified_since) >= filemtime(__FILE__))
	{
		header('HTTP/1.1 304 Not Modified');
		die;
	}
}

?>window.smfVersions = {
	'SMF': '0.1 (SMF 2.0 RC5)',
	'SourcesAdmin.php': 'Wedge 0.1',
	'SourcesBoardIndex.php': 'Wedge 0.1',
	'SourcesCalendar.php': 'Wedge 0.1',
	'SourcesClass-Package.php': 'Wedge 0.1',
	'SourcesDbExtra.php': 'Wedge 0.1',
	'SourcesDbPackages.php': 'Wedge 0.1',
	'SourcesDbSearch.php': 'Wedge 0.1',
	'SourcesDisplay.php': 'Wedge 0.1',
	'SourcesDumpDatabase.php': 'Wedge 0.1',
	'SourcesErrors.php': 'Wedge 0.1',
	'SourcesGroups.php': 'Wedge 0.1',
	'SourcesHelp.php': 'Wedge 0.1',
	'SourcesLoad.php': 'Wedge 0.1',
	'SourcesLockTopic.php': 'Wedge 0.1',
	'SourcesLogInOut.php': 'Wedge 0.1',
	'SourcesManageAttachments.php': 'Wedge 0.1',
	'SourcesManageBans.php': 'Wedge 0.1',
	'SourcesManageBoards.php': 'Wedge 0.1',
	'SourcesManageCalendar.php': 'Wedge 0.1',
	'SourcesManageErrors.php': 'Wedge 0.1',
	'SourcesManageMail.php': 'Wedge 0.1',
	'SourcesManageMaintenance.php': 'Wedge 0.1',
	'SourcesManageMembergroups.php': 'Wedge 0.1',
	'SourcesManageMembers.php': 'Wedge 0.1',
	'SourcesManageNews.php': 'Wedge 0.1',
	'SourcesManagePaid.php': 'Wedge 0.1',
	'SourcesManagePermissions.php': 'Wedge 0.1',
	'SourcesManagePosts.php': 'Wedge 0.1',
	'SourcesManageRegistration.php': 'Wedge 0.1',
	'SourcesManageSearch.php': 'Wedge 0.1',
	'SourcesManageSearchEngines.php': 'Wedge 0.1',
	'SourcesManageServer.php': 'Wedge 0.1',
	'SourcesManageSettings.php': 'Wedge 0.1',
	'SourcesManageSmileys.php': 'Wedge 0.1',
	'SourcesMemberlist.php': 'Wedge 0.1',
	'SourcesMessageIndex.php': 'Wedge 0.1',
	'SourcesModerationCenter.php': 'Wedge 0.1',
	'SourcesModlog.php': 'Wedge 0.1',
	'SourcesMoveTopic.php': 'Wedge 0.1',
	'SourcesNews.php': 'Wedge 0.1',
	'SourcesNotify.php': 'Wedge 0.1',
	'SourcesPackageGet.php': 'Wedge 0.1',
	'SourcesPackages.php': 'Wedge 0.1',
	'SourcesPersonalMessage.php': 'Wedge 0.1',
	'SourcesPoll.php': 'Wedge 0.1',
	'SourcesPost.php': 'Wedge 0.1',
	'SourcesPostModeration.php': 'Wedge 0.1',
	'SourcesPrintpage.php': 'Wedge 0.1',
	'SourcesProfile.php': 'Wedge 0.1',
	'SourcesProfile-Actions.php': 'Wedge 0.1',
	'SourcesProfile-Modify.php': 'Wedge 0.1',
	'SourcesProfile-View.php': 'Wedge 0.1',
	'SourcesQueryString.php': 'Wedge 0.1',
	'SourcesRecent.php': 'Wedge 0.1',
	'SourcesRegister.php': 'Wedge 0.1',
	'SourcesReminder.php': 'Wedge 0.1',
	'SourcesRemoveTopic.php': 'Wedge 0.1',
	'SourcesRepairBoards.php': 'Wedge 0.1',
	'SourcesReports.php': 'Wedge 0.1',
	'SourcesSSI.php': 'Wedge 0.1',
	'SourcesScheduledTasks.php': 'Wedge 0.1',
	'SourcesSearch.php': 'Wedge 0.1',
	'SourcesSearchAPI-Custom.php': 'Wedge 0.1',
	'SourcesSearchAPI-Fulltext.php': 'Wedge 0.1',
	'SourcesSearchAPI-Standard.php': 'Wedge 0.1',
	'SourcesSecurity.php': 'Wedge 0.1',
	'SourcesSendTopic.php': 'Wedge 0.1',
	'SourcesSplitTopics.php': 'Wedge 0.1',
	'SourcesStats.php': 'Wedge 0.1',
	'SourcesSubs.php': 'Wedge 0.1',
	'SourcesSubs-Admin.php': 'Wedge 0.1',
	'SourcesSubs-Auth.php': 'Wedge 0.1',
	'SourcesSubs-BoardIndex.php': 'Wedge 0.1',
	'SourcesSubs-Boards.php': 'Wedge 0.1',
	'SourcesSubs-Calendar.php': 'Wedge 0.1',
	'SourcesSubs-Categories.php' : 'Wedge 0.1',
	'SourcesSubs-Charset.php' : 'Wedge 0.1',
	'SourcesSubs-Database.php': 'Wedge 0.1',
	'SourcesSubs-Editor.php': 'Wedge 0.1',
	'SourcesSubs-Graphics.php': 'Wedge 0.1',
	'SourcesSubs-List.php': 'Wedge 0.1',
	'SourcesSubs-Membergroups.php': 'Wedge 0.1',
	'SourcesSubs-Members.php': 'Wedge 0.1',
	'SourcesSubs-MembersOnline.php': 'Wedge 0.1',
	'SourcesSubs-Menu.php': 'Wedge 0.1',
	'SourcesSubs-MessageIndex.php': 'Wedge 0.1',
	'SourcesSubs-OpenID.php': 'Wedge 0.1',
	'SourcesSubs-Package.php': 'Wedge 0.1',
	'SourcesSubs-Post.php': 'Wedge 0.1',
	'SourcesSubs-Recent.php': 'Wedge 0.1',
	'SourcesSubscriptions-PayPal.php': 'Wedge 0.1',
	'Sourcessubscriptions.php': 'Wedge 0.1',
	'SourcesSubs-Sound.php': 'Wedge 0.1',
	'SourcesThemes.php': 'Wedge 0.1',
	'SourcesViewQuery.php': 'Wedge 0.1',
	'SourcesWho.php': 'Wedge 0.1',
	'SourcesXml.php': 'Wedge 0.1',
	'DefaultAdmin.template.php': 'Wedge 0.1',
	'DefaultBoardIndex.template.php': 'Wedge 0.1',
	'DefaultCalendar.template.php': 'Wedge 0.1',
	'DefaultDisplay.template.php': 'Wedge 0.1',
	'DefaultErrors.template.php': 'Wedge 0.1',
	'DefaultGenericControls.template.php': 'Wedge 0.1',
	'DefaultGenericList.template.php': 'Wedge 0.1',
	'DefaultGenericMenu.template.php': 'Wedge 0.1',
	'DefaultHelp.template.php': 'Wedge 0.1',
	'DefaultLogin.template.php': 'Wedge 0.1',
	'DefaultManageAttachments.template.php': 'Wedge 0.1',
	'DefaultManageBans.template.php': 'Wedge 0.1',
	'DefaultManageBoards.template.php': 'Wedge 0.1',
	'DefaultManageCalendar.template.php': 'Wedge 0.1',
	'DefaultManageMail.template.php': 'Wedge 0.1',
	'DefaultManageMaintenance.template.php': 'Wedge 0.1',
	'DefaultManageMembergroups.template.php': 'Wedge 0.1',
	'DefaultManageMembers.template.php': 'Wedge 0.1',
	'DefaultManageNews.template.php': 'Wedge 0.1',
	'DefaultManagePaid.template.php': 'Wedge 0.1',
	'DefaultManagePermissions.template.php': 'Wedge 0.1',
	'DefaultManageSearch.template.php': 'Wedge 0.1',
	'DefaultManageSmileys.template.php': 'Wedge 0.1',
	'DefaultMemberlist.template.php': 'Wedge 0.1',
	'DefaultMessageIndex.template.php': 'Wedge 0.1',
	'DefaultModerationCenter.template.php': 'Wedge 0.1',
	'DefaultMoveTopic.template.php': 'Wedge 0.1',
	'DefaultNotify.template.php': 'Wedge 0.1',
	'DefaultPackages.template.php': 'Wedge 0.1',
	'DefaultPersonalMessage.template.php': 'Wedge 0.1',
	'DefaultPoll.template.php': 'Wedge 0.1',
	'DefaultPost.template.php': 'Wedge 0.1',
	'DefaultPrintpage.template.php': 'Wedge 0.1',
	'DefaultProfile.template.php': 'Wedge 0.1',
	'DefaultRecent.template.php': 'Wedge 0.1',
	'DefaultRegister.template.php': 'Wedge 0.1',
	'DefaultReminder.template.php': 'Wedge 0.1',
	'DefaultReports.template.php': 'Wedge 0.1',
	'DefaultSearch.template.php': 'Wedge 0.1',
	'DefaultSendTopic.template.php': 'Wedge 0.1',
	'DefaultSettings.template.php': 'Wedge 0.1',
	'DefaultSplitTopics.template.php': 'Wedge 0.1',
	'DefaultStats.template.php': 'Wedge 0.1',
	'DefaultThemes.template.php': 'Wedge 0.1',
	'DefaultWho.template.php': 'Wedge 0.1',
	'DefaultWireless.template.php': 'Wedge 0.1',
	'DefaultXml.template.php': 'Wedge 0.1',
	'Defaultindex.template.php': 'Wedge 0.1',
	'TemplatesAdmin.template.php': 'Wedge 0.1',
	'TemplatesBoardIndex.template.php': 'Wedge 0.1',
	'TemplatesCalendar.template.php': 'Wedge 0.1',
	'TemplatesDisplay.template.php': 'Wedge 0.1',
	'TemplatesErrors.template.php': 'Wedge 0.1',
	'TemplatesGenericControls.template.php': 'Wedge 0.1',
	'TemplatesGenericList.template.php': 'Wedge 0.1',
	'TemplatesGenericMenu.template.php': 'Wedge 0.1',
	'TemplatesHelp.template.php': 'Wedge 0.1',
	'TemplatesLogin.template.php': 'Wedge 0.1',
	'TemplatesManageAttachments.template.php': 'Wedge 0.1',
	'TemplatesManageBans.template.php': 'Wedge 0.1',
	'TemplatesManageBoards.template.php': 'Wedge 0.1',
	'TemplatesManageCalendar.template.php': 'Wedge 0.1',
	'TemplatesManageMail.template.php': 'Wedge 0.1',
	'TemplatesManageMaintenance.template.php': 'Wedge 0.1',
	'TemplatesManageMembergroups.template.php': 'Wedge 0.1',
	'TemplatesManageMembers.template.php': 'Wedge 0.1',
	'TemplatesManageNews.template.php': 'Wedge 0.1',
	'TemplatesManagePaid.template.php': 'Wedge 0.1',
	'TemplatesManagePermissions.template.php': 'Wedge 0.1',
	'TemplatesManageSearch.template.php': 'Wedge 0.1',
	'TemplatesManageSmileys.template.php': 'Wedge 0.1',
	'TemplatesMemberlist.template.php': 'Wedge 0.1',
	'TemplatesMessageIndex.template.php': 'Wedge 0.1',
	'TemplatesModerationCenter.template.php': 'Wedge 0.1',
	'TemplatesModlog.template.php': 'Wedge 0.1',
	'TemplatesMoveTopic.template.php': 'Wedge 0.1',
	'TemplatesNotify.template.php': 'Wedge 0.1',
	'TemplatesPackages.template.php': 'Wedge 0.1',
	'TemplatesPersonalMessage.template.php': 'Wedge 0.1',
	'TemplatesPoll.template.php': 'Wedge 0.1',
	'TemplatesPost.template.php': 'Wedge 0.1',
	'TemplatesPrintpage.template.php': 'Wedge 0.1',
	'TemplatesProfile.template.php': 'Wedge 0.1',
	'TemplatesRecent.template.php': 'Wedge 0.1',
	'TemplatesRegister.template.php': 'Wedge 0.1',
	'TemplatesReminder.template.php': 'Wedge 0.1',
	'TemplatesReports.template.php': 'Wedge 0.1',
	'TemplatesSearch.template.php': 'Wedge 0.1',
	'TemplatesSendTopic.template.php': 'Wedge 0.1',
	'TemplatesSettings.template.php': 'Wedge 0.1',
	'TemplatesSplitTopics.template.php': 'Wedge 0.1',
	'TemplatesStats.template.php': 'Wedge 0.1',
	'TemplatesThemes.template.php': 'Wedge 0.1',
	'TemplatesWho.template.php': 'Wedge 0.1',
	'TemplatesWireless.template.php': 'Wedge 0.1',
	'TemplatesXml.template.php': 'Wedge 0.1',
	'Templatesindex.template.php': 'Wedge 0.1'
};

window.smfLanguageVersions = {
	'Admin': 'Wedge 0.1',
	'EmailTemplates': 'Wedge 0.1',
	'Errors': 'Wedge 0.1',
	'Help': 'Wedge 0.1',
	'index': 'Wedge 0.1',
	'Install': 'Wedge 0.1',
	'Login': 'Wedge 0.1',
	'ManageBoards': 'Wedge 0.1',
	'ManageCalendar': 'Wedge 0.1',
	'ManageMail': 'Wedge 0.1',
	'ManageMaintenance': 'Wedge 0.1',
	'ManageMembers': 'Wedge 0.1',
	'ManagePaid': 'Wedge 0.1',
	'ManagePermissions': 'Wedge 0.1',
	'ManageSettings': 'Wedge 0.1',
	'ManageSmileys': 'Wedge 0.1',
	'Manual': 'Wedge 0.1',
	'ModerationCenter': 'Wedge 0.1',
	'Modifications': 'Wedge 0.1',
	'Modlog': 'Wedge 0.1',
	'Packages': 'Wedge 0.1',
	'PersonalMessage': 'Wedge 0.1',
	'Post': 'Wedge 0.1',
	'Profile': 'Wedge 0.1',
	'Reports': 'Wedge 0.1',
	'Search': 'Wedge 0.1',
	'Settings': 'Wedge 0.1',
	'Stats': 'Wedge 0.1',
	'Themes': 'Wedge 0.1',
	'ThemeStrings': 'Wedge 0.1',
	'Who': 'Wedge 0.1',
	'Wireless': 'Wedge 0.1'
};