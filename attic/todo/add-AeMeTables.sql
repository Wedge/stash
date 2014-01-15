#
# It would be interesting if AeMe albums could have their own pretty URLs.
# Unfortunately, a technical problem currently prevents me from doing that. It's called, laziness.
#

#
# Table structure for table `pretty_album_urls`
#

CREATE TABLE {$db_prefix}pretty_album_urls (
	id_album mediumint(8) unsigned NOT NULL default 0,
	pretty_url varchar(80) NOT NULL,
	PRIMARY KEY (id_album),
	KEY pretty_url (pretty_url)
) ENGINE=MyISAM;
