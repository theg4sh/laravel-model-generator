<?php

namespace Iber\Generator\Utilities;

use Iber\Generator\Utilities\VariableConversion;
use Iber\Generator\Utilities\Relation;
use Iber\Generator\Command\MakeModelsCommand;

class Table 
{
    static protected $_namespace = 'App/';
    protected $name;
    protected $pkey;
    protected $columns;
    protected $uniques;

    private $relations;

    private $_raw_relations;
    private $_className;
    private $_namespaceClass;

    protected $properties;

    static public function setNamespace($ns)
    {
        Table::$_namespace = $ns;
    }

    public function __construct($name, $schema)
    {
        $this->name = $name;
        $this->_schema = $schema;

        $prefixRemovedTableName = str_replace($schema->getTablePrefix(), '', $name);
        $this->_className = VariableConversion::convertTableNameToClassName($prefixRemovedTableName);

        $this->pkey = $schema->getTablePkey($name);
        $this->columns = $schema->getTableColumns($name);
        $this->uniques = $schema->getTableUniques($name);
        $this->_raw_relations = $schema->getTableRelations($name);

        $this->properties = [
            'fillable' => [],
            'guarded' => [],
            'timestamps' => false,
        ];
        foreach ($this->columns as $column) {
            if ($schema->getRuleProcessor()->checkFillable($column)) {
                if (!in_array($column, array_merge(['id', 'created_at', 'updated_at', 'deleted_at'], $this->pkey ? [$this->pkey] : []))) {
                    $this->properties['fillable'][] = $column;
                }
            }
            if ($schema->getRuleProcessor()->checkGuarded($column)) {
                $this->properties['guarded'][] = $column;
            }
            if ($schema->getRuleProcessor()->checkTimestamps($column)) {
                $this->properties['timestamps'] = true;
            }
        }
    }

    public function getPkey()
    {
        return $this->pkey;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getClassName()
    {
        return $this->_className;
    }

    public function setNamespaceClass($nsClass)
    {
        $this->_namespaceClass = $nsClass;
    }

    public function getNamespaceClass()
    {
        if (is_null($this->_namespaceClass)) {
            return trim(Table::$_namespace, '/') . '/' . $this->getClassName();
        } else {
            return $this->_namespaceClass;
        }
    }

    /**
     * Get table columns.
     *
     * @param $table
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    public function getProperties()
    {
        // TODO: replace by getFillable, getGuarded, hasTimestamps methods
        return $this->properties;
    }

    public function getUniques()
    {
        return $this->uniques ?: [];
    }

    public function getRelations()
    {
        return $this->relations ?: [];
    }

    public function bindRelation(&$relation)
    {
        if (is_null($this->relations)) {
            $this->relations = [];
        }
        $this->relations[$relation->toString()] = $relation;
    }

}
