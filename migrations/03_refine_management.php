<?php

/**
 * Migration to set up some tables and configs podium requires
 */
class RefineManagement extends Migration
{
    function up() {

        // Some Config entries
        Config::get()->create('PODIUM_MODULES_ORDER', array(
            'value' => '{"buzzword":0,"mycourses":0,"courses":0,"user":0,"inst":0,"file":0,"calendar":0,"messages":0,"forum":0,"resources":0,"semtree":0}',
            'type' => 'array',
            'range' => 'global',
            'section' => 'podium',
            'description' => _('Enthält die Reihenfolge aller Podiummodule Module')
        ));
    }

    function down() {
        Config::get()->delete('PODIUM_MODULES_ORDER');
    }
}