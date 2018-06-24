<?php

namespace Iber\Generator\Utilities;

use Iber\Generator\Utilities\VariableConversion;
use Iber\Generator\Utilities\Relation;
use Iber\Generator\Utilities\Engines;
use Iber\Generator\Command\MakeModelsCommand;

class DBEngine
{
    static private $dbEngine;

    static public function getInstance()
    {
        if (DBEngine::$dbEngine) {
            return DBEngine::$dbEngine;
        }
        $databaseEngine = config('database.default', 'mysql');
        switch ($databaseEngine) {
            case 'mysql':
                DBEngine::$dbEngine = new Engines\MySQL();
                break;
            case 'sqlsrv':
                DBEngine::$dbEngine = new Engines\SqlSrv();
                break;
            case 'dblib':
                DBEngine::$dbEngine = new Engines\DBLib();
                break;

            case 'pgsql':
                DBEngine::$dbEngine = new Engines\PGSql();
                break;
        }
        return DBEngine::$dbEngine;
    }
}

