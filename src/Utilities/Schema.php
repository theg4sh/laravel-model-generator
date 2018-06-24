<?php

namespace Iber\Generator\Utilities;

use Iber\Generator\Utilities\VariableConversion;
use Iber\Generator\Utilities\Table;
use Iber\Generator\Utilities\Relation;
use Iber\Generator\Command\MakeModelsCommand;
use Iber\Generator\Utilities\DBEngine;

class Schema
{
    private static $engine;

    protected static $tables;

    protected $_ruleProcessor;
    protected $_tablePrefix;
    protected $pkeys;
    protected $columns;
    protected $relations;
    protected $uniques;

    public function __construct($ruleProcessor, $tablePrefix = "")
    {
        Schema::$engine = DBEngine::getInstance();
        $this->_ruleProcessor = $ruleProcessor;
        $this->_tablePrefix = $tablePrefix;
    }

    public function getTables()
    {
        if (is_null(Schema::$tables)) {
            Schema::$tables = [];
            foreach (Schema::$engine->getTables() as $t) {
                Schema::$tables[$t->name] = new Table($t->name, $this);
            }
        }
        return Schema::$tables;
    }

    public function getTablePrefix()
    {
        return $this->_tablePrefix ?: \DB::getTablePrefix();
    }

    public function getRuleProcessor()
    {
        return $this->_ruleProcessor;
    }

    public function getTable($tableName)
    {
        return isset(Schema::$tables[$tableName]) ? Schema::$tables[$tableName] : NULL;
    }

    public function getTablePkey($tableName)
    {
        if (is_null($this->pkeys)) {
            $pkeys = Schema::$engine->getTablePkeys();
            $this->pkeys = [];
            foreach ($pkeys as $pk) {
                $this->pkeys[$pk->table_name] = $pk->column_name;
            }
        }

        return isset($this->pkeys[$tableName]) ? $this->pkeys[$tableName] : NULL;
    }

    public function getTableColumns($tableName)
    {
        if (is_null($this->columns)) {
            $this->columns = [];
            foreach (Schema::$engine->getTableColumns() as $ck) {
                if (!isset($this->columns[$ck->table_name])) {
                    $this->columns[$ck->table_name] = [];
                }
                $this->columns[$ck->table_name][] = $ck->column_name;
            }
        }
        return isset($this->columns[$tableName]) ? $this->columns[$tableName] : NULL;
    }

    public function getTableRelations($tableName)
    {
        if (is_null($this->relations)) {
            $this->relations = [];
            foreach (Schema::$engine->getTableRelations() as $rel) {
                foreach ([$rel->table_name, $rel->foreign_table_name] as $tn) {
                    if (!isset($this->relations[$tn])) {
                        $this->relations[$tn] = [];
                    }
                    $this->relations[$tn][] = $rel;
                }
            }
        }
        return isset($this->relations[$tableName]) ? $this->relations[$tableName] : NULL;
    }

    public function getTableUniques($tableName)
    {
        if (is_null($this->uniques)) {
            $this->uniques = [];
            foreach (Schema::$engine->getTableUniques() as $rel) {
                if (!isset($this->uniques[$rel->table_name])) {
                    $this->uniques[$rel->table_name] = [];
                }
                $this->uniques[$rel->table_name][] = $rel->column_name;
            }
        }
        return isset($this->uniques[$tableName]) ? $this->uniques[$tableName] : NULL;
    }
}

