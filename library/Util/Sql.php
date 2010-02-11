<?php

class Util_Sql
{
    public static function generateSqlWheresAndParams($whereStructure, $validPropertyKeys)
    {
        $andSet = array();
        $params = array();

        $i = 0;
        foreach ($whereStructure as $conds => $value) {
            $conds = explode(' ', $conds);

            $orSet = array();
            foreach ($conds as $cond) {
                if (!in_array($cond, $validPropertyKeys)) {
                    throw new Rest_Model_BadRequestException($cond . ' is not a valid where property [' . implode(', ', $validPropertyKeys) . ']');
                }

                if (is_array($value) && !empty($value)) {
                    $orKeys = array();
                    foreach ($value as $v) {
                        if (is_string($v) || !is_int($v)) {
                            $orKeys[] = ':value_' . $i;
                            $params[':value_' . $i] = $v;
                            $i++;
                        }
                    }
                    $orSet[] = $cond . ' IN (' . implode(', ', $orKeys) . ')';
                } elseif (is_string($value) || is_int($value)) {
                    $params[':value_' . $i] = $value;
                    $orSet[] = $cond . ' = ' . ':value_' . $i;
                    $i++;
                }
            }

            $andSet[] = implode(' OR ', $orSet);
        }

        return array('sql' => $andSet, 'param' => $params);
    }

}
