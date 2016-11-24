<?php

/**
 * Migration to set up some tables and configs podium requires
 */
class Fulltext extends Migration
{
    function up() {

        // Some Config entries
        Config::get()->create('PODIUM_FULLTEXT_MODULES', array(
            'value' => '{}',
            'type' => 'array',
            'range' => 'global',
            'section' => 'podium',
            'description' => _('Enthält alle Module bei denen Fulltext aktiviert wurde')
        ));
    }

    function down() {
        Config::get()->delete('PODIUM_FULLTEXT_MODULES');
    }
}