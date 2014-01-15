<?php
		foreach ($cached_files[$fold] as $file)
		{
			if (substr($file, -4) !== '.css')
				continue;

			$radix = substr($file, 0, strpos($file, '.'));
			if (!isset($original_files[$radix], $not_found[$radix]))
				continue;

			// Get the list of suffixes in the file name.
//			$suffixes = array_flip(explode(',', substr(strstr($file, '.'), 1, -4)));
//			$keep_suffixes = array_intersect_key($suffixes, $requested_suffixes);

			// If we can't find a required suffix in the suffix list, skip the file.
//			if (!$keep_suffixes)
//				continue;

			$css[] = $fold . $file;

			if ($db_show_debug === true)
				$context['debug']['sheets'][] = $file . ' (' . basename($theme[$target . 'url']) . ')';
			$latest_date = max($latest_date, filemtime($fold . $file));
		}
	}
