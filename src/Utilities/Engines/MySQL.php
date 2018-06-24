<?php

namespace Iber\Generator\Utilities\Engines;

use Iber\Generator\Utilities\Engines\DummyEngine;

class MySQL extends DummyEngine
{
    public function getTables()
    {
        return \DB::select("SELECT
                    table_name AS name
                FROM information_schema.tables
                WHERE table_type='BASE TABLE'
                  AND table_schema = '" . env('DB_DATABASE') . "'");
    }

    public function getTablePkeys()
    {
        return \DB::select("SELECT
                    TABLE_NAME as table_name,
                    COLUMN_NAME AS column_name
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = '" . env("DB_DATABASE") . "'
                  AND COLUMN_KEY = 'PRI'");
    }

    public function getTableColumns()
    {
        return \DB::select("SELECT
                    TABLE_NAME AS `table_name`,
                    COLUMN_NAME AS `column_name`
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = '" . env("DB_DATABASE") . "'");
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
