<?php

/**
 * Interface PodiumModule
 *
 * Module for Podium extension
 */
interface PodiumModule
{

    /**
     * Returns the id for this podium module. The search sql must also return this id as type
     *
     * @return String id for this module
     */
    public static function getPodiumId();

    /**
     * Returns the displayname for this module
     *
     * @return mixed
     */
    public static function getPodiumName();

    public static function getPodiumSearch($search);

    public static function podiumFilter($id, $search);
}