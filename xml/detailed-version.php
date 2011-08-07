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
	'Wedge': '0.1',

	'SourcesActivate.php': '0.1',
	'SourcesAdmin.php': '0.1',
	'SourcesAjax.php': '0.1',
	'SourcesAnnounce.php': '0.1',
	'SourcesBoardIndex.php': '0.1',
	'SourcesBuddy.php': '0.1',
	'SourcesCalendar.php': '0.1',
	'SourcesClass-CSS.php': '0.1',
	'SourcesClass-DB.php': '0.1',
	'SourcesClass-DBExtra.php': '0.1',
	'SourcesClass-DBPackages.php': '0.1',
	'SourcesClass-DBSearch.php': '0.1',
	'SourcesClass-Editor.php': '0.1',
	'SourcesClass-GifAnimator.php': '0.1',
	'SourcesClass-JSMin.php': '0.1',
	'SourcesClass-Package.php': '0.1',
	'SourcesClass-Packer.php': '0.1',
	'SourcesClass-String.php': '0.1',
	'SourcesCollapse.php': '0.1',
	'SourcesCoppaForm.php': '0.1',
	'SourcesCredits.php': '0.1',
	'SourcesDbExtra.php': '0.1',
	'SourcesDbPackages.php': '0.1',
	'SourcesDbSearch.php': '0.1',
	'SourcesDisplay.php': '0.1',
	'SourcesDlattach.php': '0.1',
	'SourcesDumpDatabase.php': '0.1',
	'SourcesErrors.php': '0.1',
	'SourcesFeed.php': '0.1',
	'SourcesFindMember.php': '0.1',
	'SourcesGroups.php': '0.1',
	'SourcesHelp.php': '0.1',
	'SourcesJSModify.php': '0.1',
	'SourcesJSEditor.php': '0.1',
	'SourcesJSOption.php': '0.1',
	'SourcesLoad.php': '0.1',
	'SourcesLock.php': '0.1',
	'SourcesLockTopic.php': '0.1',
	'SourcesLogin.php': '0.1',
	'SourcesLogin2.php': '0.1',
	'SourcesLogout.php': '0.1',
	'SourcesManageAttachments.php': '0.1',
	'SourcesManageBans.php': '0.1',
	'SourcesManageBoards.php': '0.1',
	'SourcesManageCalendar.php': '0.1',
	'SourcesManageErrors.php': '0.1',
	'SourcesManageMail.php': '0.1',
	'SourcesManageMaintenance.php': '0.1',
	'SourcesManageMembergroups.php': '0.1',
	'SourcesManageMembers.php': '0.1',
	'SourcesManageNews.php': '0.1',
	'SourcesManagePaid.php': '0.1',
	'SourcesManagePermissions.php': '0.1',
	'SourcesManagePosts.php': '0.1',
	'SourcesManageRegistration.php': '0.1',
	'SourcesManageSearch.php': '0.1',
	'SourcesManageSearchEngines.php': '0.1',
	'SourcesManageScheduledTasks.php': '0.1',
	'SourcesManageServer.php': '0.1',
	'SourcesManageSettings.php': '0.1',
	'SourcesManageSmileys.php': '0.1',
	'SourcesMemberlist.php': '0.1',
	'SourcesMessageIndex.php': '0.1',
	'SourcesModerationCenter.php': '0.1',
	'SourcesModlog.php': '0.1',
	'SourcesMoveTopic.php': '0.1',
	'SourcesNews.php': '0.1',
	'SourcesNotify.php': '0.1',
	'SourcesPackageGet.php': '0.1',
	'SourcesPackages.php': '0.1',
	'SourcesPersonalMessage.php': '0.1',
	'SourcesPoll.php': '0.1',
	'SourcesPost.php': '0.1',
	'SourcesPost2.php': '0.1',
	'SourcesPostModeration.php': '0.1',
	'SourcesPrettyUrls-Filters.php': '0.1',
	'SourcesPrintPage.php': '0.1',
	'SourcesProfile.php': '0.1',
	'SourcesProfile-Actions.php': '0.1',
	'SourcesProfile-Modify.php': '0.1',
	'SourcesProfile-View.php': '0.1',
	'SourcesQueryString.php': '0.1',
	'SourcesQuickMod.php': '0.1',
	'SourcesQuoteFast.php': '0.1',
	'SourcesRecent.php': '0.1',
	'SourcesRegister.php': '0.1',
	'SourcesReminder.php': '0.1',
	'SourcesRemoveTopic.php': '0.1',
	'SourcesRepairBoards.php': '0.1',
	'SourcesReport.php': '0.1',
	'SourcesReports.php': '0.1',
	'SourcesSSI.php': '0.1',
	'SourcesScheduledTasks.php': '0.1',
	'SourcesSearch.php': '0.1',
	'SourcesSearch2.php': '0.1',
	'SourcesSearchAPI-Custom.php': '0.1',
	'SourcesSearchAPI-Fulltext.php': '0.1',
	'SourcesSearchAPI-Standard.php': '0.1',
	'SourcesSecurity.php': '0.1',
	'SourcesSendTopic.php': '0.1',
	'SourcesSpellcheck.php': '0.1',
	'SourcesSplitTopics.php': '0.1',
	'SourcesStats.php': '0.1',
	'SourcesSticky.php': '0.1',
	'SourcesSubs.php': '0.1',
	'SourcesSubs-Admin.php': '0.1',
	'SourcesSubs-Auth.php': '0.1',
	'SourcesSubs-BBC.php': '0.1',
	'SourcesSubs-BoardIndex.php': '0.1',
	'SourcesSubs-Boards.php': '0.1',
	'SourcesSubs-Cache.php': '0.1',
	'SourcesSubs-Calendar.php': '0.1',
	'SourcesSubs-Captcha.php' : '0.1',
	'SourcesSubs-Categories.php' : '0.1',
	'SourcesSubs-Charset.php' : '0.1',
	'SourcesSubs-Database.php': '0.1',
	'SourcesSubs-Editor.php': '0.1',
	'SourcesSubs-Graphics.php': '0.1',
	'SourcesSubs-List.php': '0.1',
	'SourcesSubs-Login.php': '0.1',
	'SourcesSubs-Membergroups.php': '0.1',
	'SourcesSubs-Members.php': '0.1',
	'SourcesSubs-MembersOnline.php': '0.1',
	'SourcesSubs-Menu.php': '0.1',
	'SourcesSubs-MessageIndex.php': '0.1',
	'SourcesSubs-OpenID.php': '0.1',
	'SourcesSubs-Package.php': '0.1',
	'SourcesSubs-Post.php': '0.1',
	'SourcesSubs-PrettyUrls.php': '0.1',
	'SourcesSubs-Recent.php': '0.1',
	'SourcesSubscriptions-PayPal.php': '0.1',
	'Sourcessubscriptions.php': '0.1',
	'SourcesSubs-Scheduled.php': '0.1',
	'SourcesSubs-Sound.php': '0.1',
	'SourcesSuggest.php': '0.1',
	'SourcesThemes.php': '0.1',
	'SourcesUnread.php': '0.1',
	'SourcesUnreadReplies.php': '0.1',
	'SourcesVerificationCode.php': '0.1',
	'SourcesViewQuery.php': '0.1',
	'SourcesViewRemote.php': '0.1',
	'SourcesWho.php': '0.1',
	'SourcesXml.php': '0.1',

	'DefaultAdmin.template.php': '0.1',
	'DefaultAnnounce.template.php': '0.1',
	'DefaultBoardIndex.template.php': '0.1',
	'DefaultBoardIndexInfoCenter.template.php': '0.1',
	'DefaultCalendar.template.php': '0.1',
	'DefaultDisplay.template.php': '0.1',
	'DefaultErrors.template.php': '0.1',
	'DefaultGenericControls.template.php': '0.1',
	'DefaultGenericList.template.php': '0.1',
	'DefaultGenericMenu.template.php': '0.1',
	'DefaultHelp.template.php': '0.1',
	'DefaultLogin.template.php': '0.1',
	'DefaultManageAttachments.template.php': '0.1',
	'DefaultManageBans.template.php': '0.1',
	'DefaultManageBoards.template.php': '0.1',
	'DefaultManageCalendar.template.php': '0.1',
	'DefaultManageMail.template.php': '0.1',
	'DefaultManageMaintenance.template.php': '0.1',
	'DefaultManageMedia.template.php': '0.1',
	'DefaultManageMembergroups.template.php': '0.1',
	'DefaultManageMembers.template.php': '0.1',
	'DefaultManageNews.template.php': '0.1',
	'DefaultManagePaid.template.php': '0.1',
	'DefaultManagePermissions.template.php': '0.1',
	'DefaultManageScheduledTasks.template.php': '0.1',
	'DefaultManageSearch.template.php': '0.1',
	'DefaultManageSmileys.template.php': '0.1',
	'DefaultMedia.template.php': '0.1',
	'DefaultMemberlist.template.php': '0.1',
	'DefaultMessageIndex.template.php': '0.1',
	'DefaultModerationCenter.template.php': '0.1',
	'DefaultMoveTopic.template.php': '0.1',
	'DefaultNotify.template.php': '0.1',
	'DefaultPackages.template.php': '0.1',
	'DefaultPersonalMessage.template.php': '0.1',
	'DefaultPoll.template.php': '0.1',
	'DefaultPost.template.php': '0.1',
	'DefaultPrintpage.template.php': '0.1',
	'DefaultProfile.template.php': '0.1',
	'DefaultRecent.template.php': '0.1',
	'DefaultRegister.template.php': '0.1',
	'DefaultReminder.template.php': '0.1',
	'DefaultReports.template.php': '0.1',
	'DefaultSearch.template.php': '0.1',
	'DefaultSendTopic.template.php': '0.1',
	'DefaultSettings.template.php': '0.1',
	'DefaultSplitTopics.template.php': '0.1',
	'DefaultStats.template.php': '0.1',
	'DefaultThemes.template.php': '0.1',
	'DefaultWho.template.php': '0.1',
	'DefaultWireless.template.php': '0.1',
	'DefaultXml.template.php': '0.1',
	'Defaultindex.template.php': '0.1',

	'TemplatesAdmin.template.php': '0.1',
	'TemplatesBoardIndex.template.php': '0.1',
	'TemplatesCalendar.template.php': '0.1',
	'TemplatesDisplay.template.php': '0.1',
	'TemplatesErrors.template.php': '0.1',
	'TemplatesGenericControls.template.php': '0.1',
	'TemplatesGenericList.template.php': '0.1',
	'TemplatesGenericMenu.template.php': '0.1',
	'TemplatesHelp.template.php': '0.1',
	'TemplatesLogin.template.php': '0.1',
	'TemplatesManageAttachments.template.php': '0.1',
	'TemplatesManageBans.template.php': '0.1',
	'TemplatesManageBoards.template.php': '0.1',
	'TemplatesManageCalendar.template.php': '0.1',
	'TemplatesManageMail.template.php': '0.1',
	'TemplatesManageMaintenance.template.php': '0.1',
	'TemplatesManageMembergroups.template.php': '0.1',
	'TemplatesManageMembers.template.php': '0.1',
	'TemplatesManageNews.template.php': '0.1',
	'TemplatesManagePaid.template.php': '0.1',
	'TemplatesManagePermissions.template.php': '0.1',
	'TemplatesManageSearch.template.php': '0.1',
	'TemplatesManageSmileys.template.php': '0.1',
	'TemplatesMemberlist.template.php': '0.1',
	'TemplatesMessageIndex.template.php': '0.1',
	'TemplatesModerationCenter.template.php': '0.1',
	'TemplatesModlog.template.php': '0.1',
	'TemplatesMoveTopic.template.php': '0.1',
	'TemplatesNotify.template.php': '0.1',
	'TemplatesPackages.template.php': '0.1',
	'TemplatesPersonalMessage.template.php': '0.1',
	'TemplatesPoll.template.php': '0.1',
	'TemplatesPost.template.php': '0.1',
	'TemplatesPrintpage.template.php': '0.1',
	'TemplatesProfile.template.php': '0.1',
	'TemplatesRecent.template.php': '0.1',
	'TemplatesRegister.template.php': '0.1',
	'TemplatesReminder.template.php': '0.1',
	'TemplatesReports.template.php': '0.1',
	'TemplatesSearch.template.php': '0.1',
	'TemplatesSendTopic.template.php': '0.1',
	'TemplatesSettings.template.php': '0.1',
	'TemplatesSplitTopics.template.php': '0.1',
	'TemplatesStats.template.php': '0.1',
	'TemplatesThemes.template.php': '0.1',
	'TemplatesWho.template.php': '0.1',
	'TemplatesWireless.template.php': '0.1',
	'TemplatesXml.template.php': '0.1',
	'Templatesindex.template.php': '0.1'
};

window.smfLanguageVersions = {
	'Admin': '0.1',
	'EmailTemplates': '0.1',
	'Errors': '0.1',
	'Help': '0.1',
	'index': '0.1',
	'Install': '0.1',
	'Login': '0.1',
	'ManageBoards': '0.1',
	'ManageCalendar': '0.1',
	'ManageMail': '0.1',
	'ManageMaintenance': '0.1',
	'ManageMembers': '0.1',
	'ManagePaid': '0.1',
	'ManagePermissions': '0.1',
	'ManageSettings': '0.1',
	'ManageSmileys': '0.1',
	'Manual': '0.1',
	'Media': '0.1',
	'ModerationCenter': '0.1',
	'Modifications': '0.1',
	'Modlog': '0.1',
	'Packages': '0.1',
	'PersonalMessage': '0.1',
	'Post': '0.1',
	'Profile': '0.1',
	'Reports': '0.1',
	'Search': '0.1',
	'Security': '0.1',
	'Settings': '0.1',
	'Stats': '0.1',
	'Themes': '0.1',
	'ThemeStrings': '0.1',
	'Who': '0.1',
	'Wireless': '0.1'
};