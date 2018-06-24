<?php

namespace Iber\Generator\Utilities;

class Relation
{
    private $_ltable;
    private $_lcolumn;
    private $_lreltype;

    private $_rtable;
    private $_rcolumn;
    private $_rreltype;

    public function __construct(&$ltable, $lcolumn, &$rtable, $rcolumn)
    {
        $this->_ltable = $ltable;
        $this->_lcolumn = $lcolumn;

        $this->_rtable   = $rtable;
        $this->_rcolumn  = $rcolumn;

        $this->_lreltype = $this->solveRelation($ltable, $lcolumn, $rtable, $rcolumn);
        $this->_rreltype = $this->solveRelation($rtable, $rcolumn, $ltable, $lcolumn);
    }

    protected function solveRelation(&$ltable, $lcolumn, &$rtable, $rcolumn)
    {
        $uniques = $ltable->getUniques();
        $runiques = $rtable->getUniques();

        if ($lcolumn == $ltable->getPkey() || in_array($lcolumn, $uniques)) {
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
        $lColumn = $this->getColumn();
        $rColumn = $this->getRelColumn();
        if (preg_match('/_'.$rColumn.'$/', $lColumn)) {
            // related table's column name is a part of local column
            $relName = substr($lColumn, 0, strlen($lColumn)-strlen($rColumn)-1);
        } else {
            $relName = $this->getRelTable()->getName();
            if ($this->isRelatedToOne()) {
                $relName = preg_replace('/s$/', '', $relName);
            }
        }
        return $relName;
    }

    public function getReversed()
    {
        return new Relation($this->_rtable, $this->_rcolumn, $this->_ltable, $this->_lcolumn);
    }

    public function toString()
    {
        return $this->_ltable->getName() . '.' . $this->_lcolumn . ' -> ' .
               $this->_rtable->getName() . '.' . $this->_rcolumn;
    }

    public function isRelatedToOne()
    {
        return in_array($this->_lreltype, ['hasOne', 'belongsTo']);
    }

    public function getColumn()
    {
        return $this->_lcolumn;
    }

    public function &getTable()
    {
        return $this->_ltable;
    }

    public function &getRelTable()
    {
        return $this->_rtable;
    }

    public function getRelColumn()
    {
        return $this->_rcolumn;
    }

    public function getRelType()
    {
        return $this->_lreltype;
    }
}
