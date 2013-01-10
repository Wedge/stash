<?php

function Post()
{
	/* The following is to be found around line 870 of Post.php, replacing this:

			// Remove any nested quotes, if necessary.
			if (!empty($settings['removeNestedQuotes']))
				$form_message = preg_replace(array('~\n?\[quote.*?].+?\[/quote]\n?~is', '~^\n~', '~\[/quote]~'), '', $form_message);

			// Add a quote string on the front and end.
			$form_message = '[quote author=' . $mname . ' link=msg=' . (int) $_REQUEST['quote'] . ' date=' . $mdate . ']' . "\n" . rtrim($form_message) . "\n" . '[/quote]';

	*/

			// Remove any nested quotes, if necessary.
			if (!empty($settings['removeNestedQuotes']))
				$form_message = preg_replace(array('~\n?\[quote.*?].+?\[/quote]\n?~is', '~^\n~', '~\[/quote]~'), '', $form_message);
			else
			{
				while (preg_match_all('~\[quote(.*?)]((?:[^[]|\[(?!/?quote))+)\[/quote]~', $form_message, $matches))
				{
					foreach ($matches[2] as $k => $m)
					{
						$mo = '';
						if (!empty($matches[1][$k]))
						{
							preg_match('~author=([^] ]+)(?: link=([^ ]+))?(?: date=(\d+))?~', $matches[1][$k], $from);
							$mo = '(From ' . $from[1] . ' in #' . $from[2] . ', on ' . $from[3] . '):';
						}
						$t = straighten_up(trim($m));
						$form_message = str_replace($matches[0][$k], $mo . $t . "\n", $form_message);
					}
				}
			}

			// Add a quote string on the front and end.
			$t = straighten_up($form_message);
			$form_message = '(From ' . $mname . ' in post #' . (int) $_REQUEST['quote'] . ' on ' . $mdate . '):' . rtrim($t) . "\n"; // !!! Hardcoded!
			$form_message = str_replace("\n", "\n> ", rtrim($form_message));
}

function straighten_up($t)
{
	$l = $mo = '';
	$max_length = 80;
	$max_length_before_newline = 50;
	// !!! For testing purposes, generate a proper UTF8 string (and yes, it does work.)
	// $t .= str_repeat(utf8_encode('יייייייייייייייייייייי') . mb_convert_encoding('&#52402;', 'UTF-8', 'HTML-ENTITIES'), 50);
	preg_match_all('~^(?!> ).{' . $max_length . ',}$~mu', $t, $matches);
	foreach ($matches[0] as $k => $m)
	{
		$words = explode(' ', $m);
		$text = $line = '';
		foreach ($words as $word)
		{
			if (empty($text) && empty($line))
				$line .= $word;
			elseif (westr::strlen($line . ' ' . $word) < $max_length)
				$line .= ' ' . $word;
			else
			{
				if (westr::strlen($line) < $max_length_before_newline) // would it be considered as an end of line if we didn't add the word?
				{
					preg_match('~(.{' . ($max_length - westr::strlen($line)) . '})(.*)~u', $word, $cut_word);
					$text .= $line . ' ' . $cut_word[1] . "&ndash;\n";
					while (!empty($cut_word[2]) && westr::strlen($cut_word[2]) > $max_length)
					{
						preg_match('~(.{0,' . $max_length . '})(.*)~u', $cut_word[2], $cut_word);
						$text .= $cut_word[1] . "&ndash;\n";
					}
					$line = $cut_word[2];
				}
				else
				{
					$text .= $line . "\n";
					$line = $word;
				}
			}
		}
		$t = str_replace($m, $text . $line, $t);
	}
	$mo = '';
	foreach (explode("\n", $t) as $l)
		$mo .= "\n> " . $l;
	return $mo;
}
