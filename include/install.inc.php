<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

function coa_install() 
{
  global $prefixeTable;
  
  pwg_query('
CREATE TABLE IF NOT EXISTS `' . $prefixeTable . 'comments_categories` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `date` datetime NOT NULL DEFAULT "0000-00-00 00:00:00",
  `author` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `author_id` smallint(5) DEFAULT NULL,
  `anonymous_id` varchar(45) NOT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `content` longtext,
  `validated` enum("true","false") NOT NULL DEFAULT "false",
  `validation_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comments_i2` (`validation_date`),
  KEY `comments_i1` (`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8');
  
  $result = pwg_query('SHOW COLUMNS FROM `' . $prefixeTable . 'comments_categories` LIKE "anonymous_id";');
  if (!pwg_db_num_rows($result))
  {      
    pwg_query('ALTER TABLE `' . $prefixeTable . 'comments_categories` ADD `anonymous_id` VARCHAR( 45 ) DEFAULT NULL;');
  }
  
  $result = pwg_query('SHOW COLUMNS FROM `' . $prefixeTable . 'comments_categories` LIKE "email";');
  if (!pwg_db_num_rows($result))
  {      
    pwg_query('ALTER TABLE `' . $prefixeTable . 'comments_categories` 
      ADD `email` varchar(255) DEFAULT NULL,
      ADD `website_url` varchar(255) DEFAULT NULL,
      ADD KEY `comments_i2` (`validation_date`),
      ADD KEY `comments_i1` (`category_id`)
      ;');
  }
}

?>