<?php

namespace Iber\Generator\Utilities;

use Iber\Generator\Utilities\StubTemplate;

class RelationGenerator
{
    protected $_table;
    protected $relationStub;
    public function __construct(&$table, $relationStub)
    {
        $this->_table = $table;
        $this->relationStub = $relationStub;
    }

    public function generateRelationFunctions()
    {
        $relations = "";
        foreach($this->_table->getRelations() as &$relation)
        {
            $relations .= $this->generateRelationFunction($relation);
        }
        return $relations;
    }

    protected function generateRelationFunction(&$relation)
    {
        $c = new StubTemplate($this->relationStub);
        $c->bind('comment', $relation->toString());

        $c->bind('model', str_replace('/', '\\', $relation->getRelTable()->getNamespaceClass()));

        $c->bind('function', $this->attributeNameToFunction('get', $relation->getRelationName(), array()));
        $c->bind('reltype', $relation->getRelType());
        $c->bindFormat('attribute', $relation->getColumn());
        $c->bindFormat('rattribute', $relation->getRelColumn());

        return $c->finalize();
    }

    /**
     * Converts the given string to function. Support database names (underscores)
     * @param  string $prefixFunction desired function prefix (get/set)
     * @param  string $str            attribute name
     * @param  array  $noStrip        ?
     * @return string
     */
    public function attributeNameToFunction($prefixFunction, $str, array $noStrip = array())
    {
        // non-alpha and non-numeric characters become spaces
        $str = preg_replace('/[^a-z0-9' . implode("", $noStrip) . ']+/i', ' ', $str);
        $str = trim($str);
        // uppercase the first character of each word
        $str = ucwords($str);
        $str = str_replace(" ", "", $str);
        $str = ucfirst($str);

        return $prefixFunction.$str;
    }
}
