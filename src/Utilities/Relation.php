<?php

namespace Iber\Generator\Utilities;

class Relation
{
    private $_table;
    private $_column;
    private $_reltype;

    private $_rtable;
    private $_rcolumn;
    private $_rreltype;

    public function __construct($table, $column, $rtable, $rcolumn)
    {
        $this->_table = $table;
        $this->_column = $column;

        $this->_rcolumn  = $rcolumn;
        $this->_rtable   = $rtable;


        $this->_reltype = $this->solveRelation($table, $column, $rtable, $rcolumn);
        $this->_rreltype = $this->solveRelation($rtable, $rcolumn, $table, $column);
    }

    protected function solveRelation($table, $column, $rtable, $rcolumn)
    {
        $uniques = $table->getUniques();
        $runiques = $rtable->getUniques();

        if ($column == $table->getPkey() || in_array($column, $uniques)) {
            if ($rcolumn == $rtable->getPkey() || in_array($rcolumn, $runiques)) {
                return 'belongsTo';
            } else {
                return 'hasMany';
            }
        } else {
            return 'hasOne';
        }
    }

    public function getRelationName()
    {
        $Column = $this->getColumn();
        $relColumn = $this->getRelColumn();
        if (preg_match('/_'.$relColumn.'$/', $Column)) {
            $relName = substr($Column, 0,
                                strlen($Column)-strlen($relColumn)-1);
        } elseif (preg_match('/_'.$Column.'$/', $relColumn)) {
            $relName = $this->getRelTable()->getName();
            if ($this->isRelatedToOne()) {
                $relName = preg_replace('/s$/', '', $relName);
            }
        } else {
            $relName = $Column;
        }
        return $relName;
    }

    public function getReversed()
    {
        return new Relation($this->_rtable, $this->_rcolumn, $this->_table, $this->_column);
    }

    public function isRelatedToOne()
    {
        return in_array($this->_reltype, ['hasOne', 'belongsTo']);
    }

    public function getColumn()
    {
        return $this->_column;
    }

    public function getTable()
    {
        return $this->_table;
    }

    public function getRelTable()
    {
        return $this->_rtable;
    }

    public function getRelColumn()
    {
        return $this->_rcolumn;
    }

    public function getRelType()
    {
        return $this->_reltype;
    }
}
