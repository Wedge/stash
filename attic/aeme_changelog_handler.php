<?php

// From ManageMedia.php/aeva_admin_about()

	if ($sa == 'readme' || $sa == 'changelog')
	{
		$context['disable_media_tag'] = true;
		$readme = trim(@file_get_contents($boarddir . '/Themes/default/aeva/' . $sa . '.txt'));
		if (empty($readme))
			return;
		// A lovely series of regex to turn the ugly changelog layout into Audrey Hepburn.
		if ($sa == 'changelog')
		{
			parsesmileys($readme);
			$readme = str_replace(
				array('bullet_!', 'bullet_@', 'bullet_+', 'bullet_-', 'bullet_*'),
				array('bullet_f', 'bullet_c', 'bullet_a', 'bullet_r', 'bullet_m'),
				preg_replace('~(?:^\t*</ul>|<ul class="bbc_list">\t*$)~', '', preg_replace(
				array(
					'/^(Version[^\r\n]*?)\s{2,}([^\r\n]*)[\r\n]+\-+/m',
					'/^# ([^\r\n]*)$/m',
					'/^([*+@!-]) ([^\r\n]*)(?:[\r\n]+ ( [^\r\n]+))*/m',
					'/<ul class="bbc_list">[\r\n]*<\/ul>/',
				), array(
					'</ul><div style="font-size: 11pt; color: #396; font-weight: bold; padding-top: 12px"><div style="float: right;">$2</div>$1</div><hr><ul class="bbc_list">',
					'</ul><div style="padding: 8px 16px">$1</div><ul class="bbc_list">',
					'<li class="bullet_$1">$2$3$4$5</li>',
					'',
				),
				$readme)));
			$readme = substr_replace($readme, '', strpos($readme, '; padding-top: 12px'), 19);
		}
		else
			$readme = parse_bbc($readme);
		$context['aeva_readme_file'] = str_replace(array('& ', '<pre></pre>'), array('&amp; ', ''), $readme);
		return;
	}

/*
	Related CSS

.bullet_a, .bullet_c, .bullet_f, .bullet_m, .bullet_r
	padding-bottom: 3px

.bullet_a
	list-style-image: url($images/aeva/bullet_new.png)
.bullet_c
	list-style-image: url($images/aeva/bullet_com.png)
.bullet_f
	list-style-image: url($images/aeva/bullet_fix.png)
.bullet_m
	list-style-image: url($images/aeva/bullet_mod.png)
.bullet_r
	list-style-image: url($images/aeva/bullet_rem.png)
*/

?>