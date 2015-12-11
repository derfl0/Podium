<?php

/**
 * Created by PhpStorm.
 * User: intelec
 * Date: 11.12.15
 * Time: 10:58
 */
class PodiumModule
{

    public static function search($search) {
        if (!$search) {
            return null;
        }
    }

    public static function getId() {
        return get_called_class();
    }

    public static function getName() {
        return get_called_class();
    }

    public static function filter($id, $search) {
        return array(
            'name' => _('Dummy Module')
        );
    }

    public static function register() {
        Podium::register(get_called_class());
    }
}