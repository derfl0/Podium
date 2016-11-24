<?php

interface PodiumFulltext
{
    public static function enable();

    public static function disable();

    public static function getFulltextSearch($search);
}