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
        $params = array();
        $i = 0;

        // top level of where structure defines 'and' conditions, nested are
        // 'or' conditions
        $andSet = array();
        foreach ($whereStructure as $andIndex => $andCond) {
            if (is_string($andIndex)) {
                // a single cond has been specified, set it up for the general
                // case. $andIndex corresponds to the term, $andCond to the value
                $andCond = array($andIndex => $andCond);
            }

            if (!is_array($andCond)) {
                // just move on if the input isn't useful
                continue;
            }

            $orSet = array();
            foreach ($andCond as $orIndex => $orCond) {

                if (is_string($orIndex)) {
                    // standardize to multiple or cond form
                    $orCond = array($orIndex => $orCond);
                }

                foreach ($orCond as $term => $value) {
                    // suppress error if term lacks a comparison type and then correct
                    @list($comparisonType, $prop) = explode(' ', $term, 2);
                    if (null === $prop) {
                        $prop = $comparisonType;
                        // if not specified, '=' is the default comparison type
                        $comparisonType = '=';
                    }

                    // ensure that the comparison type specified is accepted
                    $validComparisonTypes = array('=', '~', '~=', '>', '>=', '<', '<=', '!=');
                    if (!in_array($comparisonType, $validComparisonTypes)) {
                        throw new Rest_Model_BadRequestException($comparisonType . ' is not a valid where comparison type [' . implode(', ', $validComparisonTypes) . ']');
                    }

                    // ensure that the property specified is a valid property
                    if (!in_array($prop, $validPropertyKeys)) {
                        throw new Rest_Model_BadRequestException($prop . ' is not a valid where property [' . implode(', ', $validPropertyKeys) . ']');
                    }

                    // create SQL for supported values types: array, string or integer
                    if (is_array($value) && !empty($value)) {
                        if (!in_array($comparisonType, array('!=', '='))) {
                            throw new Rest_Model_BadRequestException('where condition must have \'=\' or \'!=\' comparison type when using matching against an array. ' . $prop . ' ' . $comparisonType . ' ' . implode(',', $value) . ' is not valid.');
                        }

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

                        if ('=' == $comparisonType) {
                            $condition = '"' . $prop . '" IN (' . implode(', ', $orKeys) . ')';
                        } elseif ('!=' == $comparisonType) {
                            $condition = '"' . $prop . '" NOT IN (' . implode(', ', $orKeys) . ')';
                        }

                        $orSet[] = $condition;
                    } elseif (is_string($value) || is_int($value)) {
                        // standard way to handle things
                        $condition = '"' . $prop . '" ' . $comparisonType . ' ' . ':value_' . $i;
                        $param = $value;

                        // special cases
                        if ('~' == $comparisonType) {
                            $condition = '"' . $prop . '" LIKE :value_' . $i;
                            $param = '%' . $value . '%';
                        }

                        if ('~=' == $comparisonType) {
                            $condition = '(\' \' || "' . $prop . '" || \' \') LIKE :value_' . $i;
                            $param = '% ' . $value . ' %';
                        }

                        $orSet[] = $condition;
                        $params[':value_' . $i] = $param;

                        $i++;
                    }
                }
            }

            // hmm, didn't find something to do, nothing to see here, move along
            if (empty($orSet)) {
                continue;
            }

            $andSet[] = implode(' OR ', $orSet);
        }

        return array(
            'sql' => '(' . implode(') AND (', array_merge($andSet, array('1 = 1'))) . ')',
            'param' => $params,
        );
    }

    public static function generateSqlOrderBy($list, $validPropertyKeys, $defaultDirection = 'ASC')
    {
        if (is_string($list)) {
            $list = array($list);
        }
        // this just basically validates that the specified properties are
        // valid and that the direction is specified correctly
        $resultList = array();
        foreach ($list as $term) {
            list($prop, $direction) = explode(' ', $term . ' ');

            if (!in_array($prop, $validPropertyKeys)) {
                throw new Rest_Model_BadRequestException($prop . ' is not a valid sort property [' . implode(', ', $validPropertyKeys) . ']');
            }
            $direction = strtoupper($direction);
            if ($direction != '' && $direction != 'ASC' && $direction != 'DESC') {
                throw new Rest_Model_BadRequestException($direction . ' is not a valid sort direction [ASC, DESC]');
            }
            if ($direction === null) {
                $direction = $defaultDirection;
            }
            $resultList[] = '"' . $prop . '" ' . $direction;
        }
        return $resultList;
    }
}
