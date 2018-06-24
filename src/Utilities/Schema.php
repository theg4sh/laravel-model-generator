<?php

namespace Iber\Generator\Utilities;

use Iber\Generator\Utilities\VariableConversion;
use Iber\Generator\Utilities\Table;
use Iber\Generator\Utilities\Relation;
use Iber\Generator\Command\MakeModelsCommand;
use Iber\Generator\Utilities\DBEngine;
use Illuminate\Support\Pluralizer;

class Schema
{
    private static $engine;

    protected static $tables;

    protected $_ruleProcessor;
    protected $_tablePrefix;
    protected $_nsPluralizer;

    protected $pkeys;
    protected $columns;
    protected $relations;
    protected $uniques;

    public function __construct($ruleProcessor, $tablePrefix, $pathPrefix, callable $nsPluralizer)
    {
        Schema::$engine = DBEngine::getInstance();
        $this->_ruleProcessor = $ruleProcessor;
        $this->_tablePrefix = $tablePrefix;
        $this->_pathPrefix = $pathPrefix;
        $this->_nsPluralizer = $nsPluralizer;
    }

    public function getTablePrefix()
    {
        return $this->_tablePrefix ?: \DB::getTablePrefix();
    }

    public function getRuleProcessor()
    {
        return $this->_ruleProcessor;
    }

    public function &getTables()
    {
        if (is_null(Schema::$tables)) {
            Schema::$tables = [];
            $tables = Schema::$engine->getTables();
            foreach ($tables as $t) {
                $table = new Table($t->name, $this);
                $table->setNamespaceClass(
                    call_user_func($this->_nsPluralizer,
                        $this->_pathPrefix . $table->getClassName())
                );
                Schema::$tables[$t->name] = $table;
            }
        }
        return Schema::$tables;
    }

    public function getTable($tableName)
    {
        return Schema::$tables[$tableName] ?: NULL;
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
        return isset($this->columns[$tableName]) ? $this->columns[$tableName] : [];
    }

    public function getTableRelations($tableName)
    {
        if (is_null($this->relations)) {
            $this->relations = [];
            foreach (Schema::$engine->getTableRelations() as $rel) {
                if (!isset($this->relations[$rel->table_name])) {
                    $this->relations[$rel->table_name] = [];
                }
                //if (!isset($this->relations[$rel->foreign_table_name])) {
                //    $this->relations[$rel->foreign_table_name] = [];
                //}

                $this->relations[$rel->table_name][] = $rel;
                //$this->relations[$rel->foreign_table_name][] = $rel->getReversed();
            }
        }
        return isset($this->relations[$tableName]) ? $this->relations[$tableName] : [];
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
        return isset($this->uniques[$tableName]) ? $this->uniques[$tableName] : [];
    }

    public function buildRelations()
    {
        foreach ($this->getTables() as $tableName => &$ltable) {
            $lrels = $this->getTableRelations($tableName);
            if (!$lrels) continue;
            foreach ($lrels as $r) {
                $rtable = $this->getTable($r->foreign_table_name);
                $lrel = new Relation($ltable, $r->column_name,
                    $rtable, $r->foreign_column_name);
                $ltable->bindRelation($lrel);
                $rrel = $lrel->getReversed();
                $rtable->bindRelation($rrel);
            }
        }
    }
}

