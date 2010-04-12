<?php

class Util_Sql
{
    /**
     * Where structure takes the form of
     * $whereStructure = array(
     *     'space_separated_or_conditions' => $value,
     *     'space_separated_or_conditions' => $value,
     *     ...
     * );
     *
     * 'space_separated_or_conditions' are useful for doing searches across
     * multiple properties for the value.
     * Example: 'name_first name_last username' => 'Kelly'
     *
     * @param array $whereStructure
     * @param array $validPropertyKeys
     * @return array('sql' => $andSet, 'params' => $params)
     */
    public static function generateSqlWheresAndParams($whereStructure, $validPropertyKeys)
    {
        $andSet = array();
        $params = array();

        $i = 0;
        // top level of where structure define conditions that are anded together
        foreach ($whereStructure as $conds => $value) {
            // space_separated_or_conditions
            $conds = explode(' ', $conds);

            $orSet = array();
            foreach ($conds as $cond) {
                // ensure that the condition specified is a valid property
                if (!in_array($cond, $validPropertyKeys)) {
                    throw new Rest_Model_BadRequestException($cond . ' is not a valid where property [' . implode(', ', $validPropertyKeys) . ']');
                }

                // create SQL for supported values types: array, string or integer
                if (is_array($value) && !empty($value)) {

                    $orKeys = array();
                    foreach ($value as $v) {
                        // if array of strings or integers, creates a
                        // 'property IN (:value_0, :value_1, ...)' type of thing
                        if (is_string($v) || is_int($v)) {
                            $orKeys[] = ':value_' . $i;
                            $params[':value_' . $i] = $v;
                            $i++;
                        }
                    }
                    $orSet[] = '"' . $cond . '" IN (' . implode(', ', $orKeys) . ')';
                } elseif (is_string($value) || is_int($value)) {
                    // a simple string or an integer, create 'property = :value_0'
                    $params[':value_' . $i] = $value;
                    $orSet[] = '"' . $cond . '" = ' . ':value_' . $i;
                    $i++;
                }
            }

            // hmm, didn't find something to do, nothing to see here, move along
            if (empty($orSet)) {
                continue;
            }

            $andSet[] = implode(' OR ', $orSet);
        }

        return array('sql' => $andSet, 'param' => $params);
    }

    public static function generateSqlSort($sortList, $validPropertyKeys)
    {
        // this just basically validates that the specified properties are
        // valid and that the direction is specified correctly
        $resultList = array();
        foreach ($sortList as $sortTerm) {
            list($prop, $direction) = explode(' ', $sortTerm . ' ');

            if (!in_array($prop, $validPropertyKeys)) {
                throw new Rest_Model_BadRequestException($prop . ' is not a valid sort property [' . implode(', ', $validPropertyKeys) . ']');
            }
            if ($direction != '' && strtoupper($direction != 'ASC') && strtoupper($direction != 'DESC')) {
                throw new Rest_Model_BadRequestException($direction . ' is not a valid sort direction [ASC, DESC]');
            }
            if ($direction === null) {
                $direction = 'ASC';
            }
            $resultList[] = '"' . $prop . '" ' . strtoupper($direction);
        }
        return $resultList;
    }

}
