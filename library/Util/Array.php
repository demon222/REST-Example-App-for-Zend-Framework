<?php

class Util_Array
{
    public static function mapIntersectingKeys($prop, $map)
    {
        $item = array();
        foreach($map as $from => $to) {
            if (isset($prop[$from])) {
                $item[$to] = $prop[$from];
            }
        }
        return $item;
    }

    public static function arrayFromKeyValuesOfSet($key, $set)
    {
        $results = array();
        foreach ($set as $item) {
            if (isset($item[$key])) {
                $results[] = $item[$key];
            }
        }
        return $results;
    }
}
