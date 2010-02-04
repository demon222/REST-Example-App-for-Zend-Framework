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
}
