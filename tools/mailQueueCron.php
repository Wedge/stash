<?php
/**
 * mail_queue_cron.php
 *
 * @package Wedge
 * @copyright 2010 René-Gilles Deberdt, wedge.org
 * @license http://wedge.org/license/
 * @author see contributors.txt
 */

// THIS SCRIPT IS INTENDED TO BE CALLED BY A CRON TASK!
// We let the server admin control how often this script is called, we will just respect the limit.

// Oh where, oh where, has Wedge gone? Oh where can it be?
require_once('SSI.php');

// Are we not supposed to use the cron?
if (empty($settings['mail_queue_use_cron']))
	return;

// Ensure we don't run out of memory with large email batches
ini_set('memory_limit', '32M');

$request = $smcFunc['db_query']('', '
	SELECT /*!40001 SQL_NO_CACHE */ id_mail, recipient, body, subject, headers, send_html
	FROM {db_prefix}mail_queue
	ORDER BY priority ASC, id_mail ASC
	LIMIT {int:limit}',
	array(
		'limit' => $settings['mail_limit'],
	)
);

$emails = array();
while ($row = $smcFunc['db_fetch_assoc']($request))
	$emails[$row['id_mail']] = $row;
$smcFunc['db_free_result']($request);

// Ctrl+Alt+Delete (tm). Copyright pending.
if (!empty($emails))
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}mail_queue
		WHERE id_mail IN ({array_int:emails})',
		array(
			'emails' => array_keys($emails),
		)
	);

if (empty($emails))
	return false;
elseif (!empty($settings['mail_type']) && $settings['smtp_host'] != '')
	require_once($sourcedir . '/Subs-Post.php');

// Send each email, yea!
$failed_emails = array();
foreach ($emails as $key => $email)
{
	if (empty($settings['mail_type']) || $settings['smtp_host'] == '')
	{
		$email['subject'] = strtr($email['subject'], array("\r" => '', "\n" => ''));
		if (!empty($settings['mail_strip_carriage']))
		{
			$email['body'] = strtr($email['body'], array("\r" => ''));
			$email['headers'] = strtr($email['headers'], array("\r" => ''));
		}

		// No point logging a specific error here, as we have no language. PHP error is helpful anyway...
		$result = mail(strtr($email['recipient'], array("\r" => '', "\n" => '')), $email['subject'], $email['body'], $email['headers']);
	}
	else
		$result = smtp_mail(array($email['recipient']), $email['subject'], $email['body'], $email['send_html'] ? $email['headers'] : 'Mime-Version: 1.0' . "\r\n" . $email['headers']);

	// Hopefully it sent?
	if (!$result)
		$failed_emails[] = array($email['recipient'], $email['body'], $email['subject'], $email['headers'], $email['send_html']);
}

// Any emails that didn't send?
if (!empty($failed_emails))
{
	// Update the failed attempts check.
	$smcFunc['db_insert']('replace',
		'{db_prefix}settings',
		array('variable' => 'string', 'value' => 'string'),
		array('mail_failed_attempts', empty($settings['mail_failed_attempts']) ? 1 : ++$settings['mail_failed_attempts']),
		array('variable')
	);

	// If we have failed to many times, tell mail to wait a bit and try again.
	if ($settings['mail_failed_attempts'] > 5)
		log_error('SMTP failed to send more than 5 emails', 'critical');

	// Add our email back to the queue, manually.
	$smcFunc['db_insert']('insert',
		'{db_prefix}mail_queue',
		array('recipient' => 'string', 'body' => 'string', 'subject' => 'string', 'headers' => 'string', 'send_html' => 'string'),
		$failed_emails,
		array('id_mail')
	);

	return false;
}
// We where unable to send the email, clear our failed attempts.
elseif (!empty($settings['mail_failed_attempts']))
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}settings
		SET value = {string:zero}
		WHERE variable = {string:mail_failed_attempts}',
		array(
			'zero' => '0',
			'mail_failed_attempts' => 'mail_failed_attempts',
	));

// Had something to send...
return true;
