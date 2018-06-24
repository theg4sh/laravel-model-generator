<?php

namespace Iber\Generator\Utilities\Engines;

use Iber\Generator\Utilities\Engines\DummyEngine;

abstract class DummyEngine
{
    abstract public function getTables();
    abstract public function getTablePkeys();
    abstract public function getTableColumns();
    abstract public function getTableRelations();
    abstract public function getTableUniques();
}
