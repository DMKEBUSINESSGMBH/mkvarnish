#
# Table structure for table 'tx_mkvarnish_cache_tags'
#
CREATE TABLE `tx_mkvarnish_cache_tags` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `tag` varchar(250) NOT NULL DEFAULT '',
    `cache_hash` varchar(250) NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    KEY `cache_tag` (`tag`),
    KEY `cache_hash` (`cache_hash`)
) ENGINE=InnoDB;
