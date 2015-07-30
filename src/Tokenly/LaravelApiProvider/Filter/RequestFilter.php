<?php

namespace Tokenly\LaravelApiProvider\Filter;

use Exception;
use Illuminate\Http\Request;

/*
* RequestFilter

[
    'fields' => [
        'name'   => ['field' => 'name',],
        'active' => ['field' => 'active', 'default' => 1, 'transformFn' => ['Tokenly\LaravelApiProvider\Filter\Transformers','toBooleanInteger'] ],
        'serial' => ['sortField' => 'serial', 'defaultSortDirection' => 'asc'],
    ],
    'defaults' => ['sort' => 'serial'],
]
*/
abstract class RequestFilter
{

    protected $request = null;
    protected $filter_definitions = [];

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

    public function setFilterDefinitions($filter_definitions) {
        $this->filter_definitions = $filter_definitions;
        return $this;
    }
    
    public function apply($query) {
        $this
            ->filter($query)
            ->sort($query)
            ->limit($query);

        return $this;
    }

    // accepts ?name=joe
    public function filter($query) {
        $this->validateQuery($query);

        if ($this->request !== null) {
            $params = $this->request->all();
            $field_filter_definitions = isset($this->filter_definitions['fields']) ? $this->filter_definitions['fields'] : [];

            // fill in default filters
            foreach($field_filter_definitions as $filter_def) {
                if (isset($filter_def['default'])) {
                    if (!isset($params[$filter_def['field']])) {
                        $params[$filter_def['field']] = $filter_def['default'];
                    }
                }
            }            

            foreach($params as $param_key => $param_value) {
                if (isset($field_filter_definitions[$param_key]) AND $filter_def = $field_filter_definitions[$param_key]) {
                    if (isset($filter_def['field'])) {

                        // transform
                        if (isset($filter_def['transformFn'])) {
                            $param_value = call_user_func($filter_def['transformFn'], $param_value);
                        }

                        $query->where($filter_def['field'], '=', $param_value);
                    }
                }
            }
        }
    }

    // accepts ?limit=10
    public function limit($query) {
        $this->validateQuery($query);

        if ($this->request !== null) {
            $params = $this->request->all();
            if (isset($params['limit']) AND ($limit = intval($params['limit'])) > 0) {
                $query->limit($limit);
            }
        }

        return $this;
    }

    // accepts ?sort=name desc
    public function sort($query) {
        $this->validateQuery($query);

        $params = $this->request->all();

        $field = null;
        $direction = null;

        $field_filter_definitions = isset($this->filter_definitions['fields']) ? $this->filter_definitions['fields'] : [];
        if (isset($params['sort']) AND $sort_data = $this->parseSortString($params['sort'])) {
            if (isset($field_filter_definitions[$sort_data['field']]) AND isset($field_filter_definitions[$sort_data['field']]['sortField'])) {
                $filter_def = $field_filter_definitions[$sort_data['field']];
                $field = $filter_def['sortField'];
                $direction = $sort_data['direction'] ?: $this->normalizeDirection($filter_def['defaultSortDirection']);
            }
        }

        if ($field === null) {
            // default sort
            if (isset($this->filter_definitions['defaults']) AND isset($this->filter_definitions['defaults']['sort'])) {
                $default_sort_field = $this->filter_definitions['defaults']['sort'];
                $filter_def = $field_filter_definitions[$default_sort_field];
                $field = $filter_def['sortField'];
                $direction = $this->normalizeDirection($filter_def['defaultSortDirection']);
            }
        }

        if ($field !== null) {
            $query->orderBy($field, $direction);
        }

        return $this;
    }

    protected function parseSortString($sort_string) {
        $pieces = explode(' ', $sort_string, 2);
        return ['field' => trim($pieces[0]), 'direction' => isset($pieces[1]) ? $this->normalizeDirection($pieces[1]) : null];
    }

    protected function normalizeDirection($direction) {
        if (strtoupper(trim($direction)) === 'DESC') { return 'DESC'; }
        return 'ASC';
    }

    protected function validateQuery($query) {
        if ($query instanceof \Illuminate\Database\Eloquent\Builder) { return; }
        if ($query instanceof \Illuminate\Database\Query\Builder) { return; }
        throw new Exception("Unsupported query type of ".get_class($query), 1);
    }
}

