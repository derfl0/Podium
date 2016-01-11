<?php

/**
 * Migration to set up some tables and configs podium requires
 */
class AddModuleManagement extends Migration
{
    function up() {

        // Some Config entries
        Config::get()->create('PODIUM_MODULES', array(
            'value' => '',
            'type' => 'array',
            'range' => 'global',
            'section' => 'podium',
            'description' => _('Enthält alle deaktivierten Module')
        ));
    }

    function down() {
        Config::get()->delete('PODIUM_MODULES');
    }
}