<?php

/**
 * Created by PhpStorm.
 * User: intelec
 * Date: 10.12.15
 * Time: 14:12
 */
class PodiumBuzzword extends SimpleORMap
{
    protected static function configure($config = array()) {
        $config['db_table'] = 'podium_buzzwords';
        $config['additional_fields']['rightsname'] = true;
        parent::configure($config);
    }

    public function getRightsname() {
        return array_search($this->rights, $GLOBALS['perm']->permissions);
    }
}