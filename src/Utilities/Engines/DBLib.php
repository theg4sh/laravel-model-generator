<?php

namespace Iber\Generator\Utilities\Engines;

use Iber\Generator\Utilities\Engines\DummyEngine;

class DBLib extends DummyEngine
{
    public function getTables()
    {
        return \DB::select("SELECT
                table_name AS name
            FROM information_schema.tables
            WHERE table_type='BASE TABLE'
              AND table_catalog = '" . env('DB_DATABASE') . "'");
    }

    public function getTablePkeys()
    {
        return \DB::select("SELECT
                TABLE_NAME AS table_name,
                ku.COLUMN_NAME AS column_name
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS tc
            INNER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS ku
                ON tc.CONSTRAINT_TYPE = 'PRIMARY KEY'
               AND tc.CONSTRAINT_NAME = ku.CONSTRAINT_NAME
            WHERE ku.TABLE_CATALOG ='" . env("DB_DATABASE") . "';");
    }

    public function getTableColumns()
    {
        return \DB::select("SELECT
                TABLE_NAME as 'table_name',
                COLUMN_NAME as 'column_name'
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_CATALOG = '" . env("DB_DATABASE") . "'");
    }

    public function getTableRelations()
    {
        return [];
    }

    public function getTableUniques()
    {
        return [];
    }
}

