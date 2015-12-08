<?php

/**
 * Migration to set up some tables and configs podium requires
 */
class SetupPodiumDB extends Migration
{
    function up() {

        // Create Buzzwords table
        DBManager::get()->exec("CREATE TABLE IF NOT EXISTS `podium_buzzwords` (
  `buzz_id` char(32) NOT NULL DEFAULT '',
  `rights` enum('user','autor','tutor','dozent','admin','root') NOT NULL DEFAULT 'user',
  `name` varchar(255) NOT NULL DEFAULT '',
  `buzzwords` varchar(2048) NOT NULL DEFAULT '',
  `subtitle` varchar(255) DEFAULT NULL,
  `url` varchar(2048) NOT NULL DEFAULT '',
  PRIMARY KEY (`buzz_id`)
)");

        // Create Faillog table
        DBManager::get()->exec("CREATE TABLE IF NOT EXISTS `podium_faillog` (
  `input` varchar(255) NOT NULL DEFAULT '',
  `count` int(11) NOT NULL,
  PRIMARY KEY (`input`)
)");

        // Some Config entries
        Config::get()->create('PODIUM_FAILLOG', array(
            'value' => 'false',
            'type' => 'boolean',
            'range' => 'global',
            'section' => 'podium',
            'description' => _('Schaltet den Podium Log ein. Darin werden gesuchte Begriffe gespeichert, die zu keinem Treffer führten')
        ));
    }

    function down() {
        DBManager::get()->exec("DELETE TABLE IF EXISTS `podium_buzzwords`, `podium_faillog`");
        Config::get()->delete('PODIUM_FAILLOG');
    }
}