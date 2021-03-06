<?php

namespace Tokenly\LaravelApiProvider\Filter;

use Exception;
use Illuminate\Http\Request;

/*
* RequestFilter

[
    'fields' => [
        'name'   => ['field' => 'name',],
        'label'  => ['field' => 'label', 'allowLike' => true, 'assumeLike' => true], // allows searches with % (or assumes % on either end)
        'token'  => ['useFilterFn' => function($query, $param_value, $params) {}],
        'token'  => ['useSortFn' => function($query, $parsed_sort_query, $params) {}],
        'active' => ['field' => 'active', 'default' => 1, 'transformFn' => ['Tokenly\LaravelApiProvider\Filter\Transformers','toBooleanInteger'] ],
        'serial' => ['sortField' => 'serial', 'defaultSortDirection' => 'asc'],
        'botId'  => ['field' => 'bot_id', 'allow_multiple' => true, 'separator' => ',' ],
    ],
    'limit' => [
        'field'       => 'limit', // optional
        'max'         => 50,      // optional
        'pagingField' => 'pg',    // optional
    ],
    'defaults' => ['sort' => 'serial', 'operator' => 'AND',],    // default operator is AND
    'operator' => [
        'field' => 'operator',   // specify an optional field to override the default operator
    ],
]


'useFilterFn' can return an array of parameters that are fed to $query->where or $query->orWhere
'useFilterFn' can also return a function which is fed to $query->where or $query->orWhere

*/
abstract class RequestFilter
{

    protected $request             = null;
    protected $override_parameters = null;
    protected $filter_definitions  = [];
    protected $apply_context       = null;

    public $used_limit       = null;
    public $used_page_offset = null;
    public $query            = null;

    static $INDEX_UNIQUE_ID = 0;

    const OP_AND = 1;
    const OP_OR  = 2;

    public static function createFromRequest(Request $request, $filter_definitions=null) {
        $instance = app(get_called_class());
        $instance->setRequest($request);
        if ($filter_definitions !== null) { $instance->setFilterDefinitions($filter_definitions); }
        return $instance;
    }

    public function setRequest(Request $request) {
        $this->request = $request;
        return $this;
    }

    public function setOverrideParameters($override_parameters) {
        $this->override_parameters = $override_parameters;
        return $this;
    }

    public function setFilterDefinitions($filter_definitions) {
        $this->filter_definitions = $filter_definitions;
        return $this;
    }
    
    public function apply($query) {
        $this
            ->select($query)
            ->filter($query)
            ->sort($query)
            ->limit($query);

        return $this;
    }

    // accepts ?select=name description data
    public function select($query) {
        $this->validateQuery($query);
        $this->query = $query;

        $params = $this->getParameters();
        if ($params) {
            if (isset($params['select']) AND strlen($params['select'])) {
                $field_names = explode(' ', $params['select']);
                array_walk($field_names, function(&$val) { $val = trim($val); });
                $field_names_map = array_fill_keys($field_names, true);

                // always select the id and the uuid
                $field_names_map['id'] = true;
                $field_names_map['uuid'] = true;

                $query->select(array_keys($field_names_map));
            }
        }

        return $this;
    }

    // accepts ?name=joe
    public function filter($query) {
        $this->validateQuery($query);
        $this->query = $query;

        $params = $this->getParameters();
        if ($params) {
            $field_filter_definitions = isset($this->filter_definitions['fields']) ? $this->filter_definitions['fields'] : [];

            $operator_type = $this->getOperatorType($params);

            foreach($params as $param_key => $param_value) {
                if (isset($field_filter_definitions[$param_key]) AND $filter_def = $field_filter_definitions[$param_key]) {
                    // apply filter function
                    if (isset($filter_def['useFilterFn']) AND is_callable($filter_def['useFilterFn'])) {
                        if ($this->apply_context === null) { $this->apply_context = new \ArrayObject(); }
                        $filter_result = call_user_func($filter_def['useFilterFn'], $query, $param_value, $params, $this->apply_context);

                        $is_array    = is_array($filter_result);
                        $is_callable = is_callable($filter_result);
                        if ($is_array OR $is_callable) {
                            $where_method = ($operator_type == self::OP_OR ? 'orWhere' : 'where');
                            call_user_func_array([$query, $where_method], $is_callable ? [$filter_result] : $filter_result);
                        }
                    }

                    // field
                    if (isset($filter_def['field'])) {
                        $transform_fn = isset($filter_def['transformFn']) ? $filter_def['transformFn'] : null;

                        if ($param_value !== '*') {
                            if (isset($filter_def['allow_multiple']) AND $filter_def['allow_multiple']) {
                                $sep = isset($filter_def['separator']) ? $filter_def['separator'] : ',';
                                $param_value_collection = collect(explode($sep, $param_value))->map(function($item) use ($transform_fn) {
                                    $item = trim($item);
                                    // transform each item individually
                                    if ($transform_fn) { $item = call_user_func($transform_fn, $item); }
                                    return $item;
                                });

                                if ($operator_type == self::OP_OR) {
                                    $query->orWhereIn($filter_def['field'], $param_value_collection->toArray());
                                } else {
                                    $query->whereIn($filter_def['field'], $param_value_collection->toArray());
                                }

                            } else if (strlen($param_value)) {
                                if ($transform_fn) { $param_value = call_user_func($transform_fn, $param_value); }

                                $match_operator = '=';
                                if (($filter_def['allowLike'] ?? false) AND strpos($param_value, '%')) {
                                    $match_operator = 'LIKE';
                                } else if ($filter_def['assumeLike'] ?? false) {
                                    $match_operator = 'LIKE';
                                    $param_value = '%'.$param_value.'%';
                                }

                                if ($operator_type == self::OP_OR) {
                                    $func = 'orWhere';
                                } else {
                                    $func = 'where';
                                }

                                // $query->where($filter_def['field'], '=', $param_value);
                                // $query->orWhere($filter_def['field'], '=', $param_value);
                                call_user_func([$query, $func], $filter_def['field'], $match_operator, $param_value);
                            }
                        }
                    }
                }
            }
        }

        return $this;
    }

    // accepts ?limit=10
    public function limit($query) {
        $this->used_limit       = null;
        $this->used_page_offset = null;

        $this->validateQuery($query);
        $this->query = $query;

        $params = $this->getParameters();

        $limit_def = isset($this->filter_definitions['limit']) ? $this->filter_definitions['limit'] : [];
        $limit_field = isset($limit_def['field']) ? $limit_def['field'] : 'limit';
        $paging_field = isset($limit_def['pagingField']) ? $limit_def['pagingField'] : 'pg';
        $limit = null;

        if (isset($params[$limit_field])) {
            $limit = intval($params[$limit_field]);
            if ($limit <= 0) { $limit = null; }
        }

        if (isset($limit_def['max'])) {
            if ($limit === null) {
                $limit = $limit_def['max'];
            } else {
                $limit = min($limit, $limit_def['max']);
            }
        }

        if ($limit !== null) {
            $this->used_limit = $limit;
            $this->used_page_offset = 0;

            $query->limit($limit);

            // check paging
            if ($paging_field !== null) {
                $page = isset($params[$paging_field]) ? intval($params[$paging_field]) : 0;
                if ($page > 0) {
                    $this->used_page_offset = $page;
                    $query->skip($page * $limit);
                }
            }
        }

        return $this;
    }

    // accepts ?sort=name desc
    public function sort($query) {
        $this->validateQuery($query);
        $this->query = $query;

        $params = $this->getParameters();

        $any_sorts_found = false;

        $field_filter_definitions = isset($this->filter_definitions['fields']) ? $this->filter_definitions['fields'] : [];
        if (isset($params['sort'])) {
            $parsed_sort_queries = $this->parseSortString($params['sort']);
            foreach($parsed_sort_queries as $parsed_sort_query) {
                if (isset($field_filter_definitions[$parsed_sort_query['field']])) {
                    $filter_def = $field_filter_definitions[$parsed_sort_query['field']];
                    // check for a custom sort function
                    if (isset($filter_def['useSortFn'])) {
                        if ($this->apply_context === null) { $this->apply_context = new \ArrayObject(); }
                        call_user_func($filter_def['useSortFn'], $query, $parsed_sort_query, $params, $this->apply_context);
                        $any_sorts_found = true;
                    }

                    // use the sort field settings
                    if (isset($filter_def['sortField'])) {
                        $field = $filter_def['sortField'];
                        $direction = $this->normalizeDirection($parsed_sort_query['direction'], isset($filter_def['defaultSortDirection']) ? $filter_def['defaultSortDirection'] : null);
                        $query->orderBy($field, $direction);
                        $any_sorts_found = true;
                    }
                }

            }
        }

        if (!$any_sorts_found) {
            // default sort
            if (isset($this->filter_definitions['defaults']) AND isset($this->filter_definitions['defaults']['sort'])) {
                $sort = $this->filter_definitions['defaults']['sort'];
                $sorts = is_array($sort) ? $sort : [$sort];
                foreach($sorts as $sort_field) {
                    $filter_def = isset($this->filter_definitions['fields'][$sort_field]) ? $this->filter_definitions['fields'][$sort_field] : null;
                    if ($filter_def) {
                        $field = $filter_def['sortField'];
                        $direction = $this->normalizeDirection(isset($filter_def['defaultSortDirection']) ? $filter_def['defaultSortDirection'] : null);
                        $query->orderBy($field, $direction);
                    }
                }
            }
        }


        return $this;
    }

    public function getCountForPagination() {
        $query = $this->query;
        if ($query instanceof \Illuminate\Database\Eloquent\Builder) {
            $query = $this->query->toBase();
        }
        if ($query instanceof \Illuminate\Database\Query\Builder) {
            return $query->getCountForPagination();
        }
        throw new Exception("Unsupported query type of ".get_class($query), 1);
    }

    protected function getParameters() {
        $params = [];

        // start with all fields defaults
        $field_filter_definitions = isset($this->filter_definitions['fields']) ? $this->filter_definitions['fields'] : [];

        // fill in default filters
        foreach($field_filter_definitions as $filter_def) {
            if (isset($filter_def['default'])) {
                $params[$filter_def['field']] = $filter_def['default'];
            }
        }



        if ($this->request !== null) {
            $params = array_merge($params, $this->request->all());
        }

        if ($this->override_parameters !== null) {
            $params = array_merge($params, $this->override_parameters);
        }

        return $params;
    }

    protected function parseSortString($sort_string) {
        $sort_phrases = explode(',', $sort_string);

        $sorts = [];
        foreach($sort_phrases as $sort_phrase) {
            $pieces = explode(' ', $sort_phrase, 2);
            $sorts[] = [
                'field'        => trim($pieces[0]),
                'direction'    => isset($pieces[1]) ? $this->normalizeDirection($pieces[1]) : null,
                // 'rawDirection' => isset($pieces[1]) ? $pieces[1] : null,
            ];
        }
        return $sorts;
    }

    protected function normalizeDirection($raw_direction, $default_direction=null) {
        $direction = ($raw_direction === null ? $default_direction : $raw_direction);
        if (strtoupper(trim($direction)) === 'DESC') { return 'DESC'; }
        return 'ASC';
    }

    protected function validateQuery($query) {
        if ($query instanceof \Illuminate\Database\Eloquent\Builder) { return; }
        if ($query instanceof \Illuminate\Database\Query\Builder) { return; }
        throw new Exception("Unsupported query type of ".get_class($query), 1);
    }

    protected function getOperatorType($params) {
        $operator_definition = isset($this->filter_definitions['operator']) ? $this->filter_definitions['operator'] : [];

        // check field
        $op_type = null;
        if (isset($operator_definition['field'])) {
            $field = $operator_definition['field'];
            if (isset($params[$field])) {
                $op_type = $this->parseOperatorType($params[$field]);
            }
        }
        if ($op_type !== null) { return $op_type; }

        // check default
        if (isset($this->filter_definitions['defaults']) AND isset($this->filter_definitions['defaults']['operator'])) {
            $op_type = $this->parseOperatorType($this->filter_definitions['defaults']['operator']);
        }
        if ($op_type !== null) { return $op_type; }

        // fall back to OP_AND
        return self::OP_AND;
    }

    protected function parseOperatorType($op_type_string) {
        switch (strtoupper(trim($op_type_string))) {
            case 'AND':
                return self::OP_AND;
            case 'OR':
                return self::OP_OR;
        }

        return null;
    }
}

