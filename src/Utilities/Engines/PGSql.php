<?php

namespace Iber\Generator\Utilities\Engines;

use Iber\Generator\Utilities\Engines\DummyEngine;

class PGSql extends DummyEngine
{
    public function getTables()
    {
        return \DB::select("SELECT
                    table_name AS name
                FROM information_schema.tables
                WHERE table_schema = 'public'
                  AND table_type='BASE TABLE'
                  AND table_catalog = '" . env('DB_DATABASE') . "'");
    }

    public function getTablePkeys()
    {
        return \DB::select("SELECT
                    kcu.table_name,
                    kcu.column_name
                FROM
                    information_schema.table_constraints AS tc
                    JOIN information_schema.key_column_usage AS kcu
                      ON tc.constraint_name = kcu.constraint_name
                WHERE tc.constraint_type = 'PRIMARY KEY'
                  AND tc.table_catalog = '" . env('DB_DATABASE') . "'");
    }

    public function getTableColumns()
    {
        return \DB::select("SELECT
                    TABLE_NAME as table_name,
                    COLUMN_NAME as column_name
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_CATALOG = '" . env("DB_DATABASE") . "'");
    }

    public function getTableRelations()
    {
        return \DB::select("SELECT
                    tc.constraint_name,
                    tc.table_name,
                    kcu.column_name,
                    ccu.table_name AS foreign_table_name,
                    ccu.column_name AS foreign_column_name
                FROM
                    information_schema.table_constraints AS tc
                    JOIN information_schema.key_column_usage AS kcu
                      ON tc.constraint_name = kcu.constraint_name
                    JOIN information_schema.constraint_column_usage AS ccu
                      ON ccu.constraint_name = tc.constraint_name
                WHERE constraint_type = 'FOREIGN KEY'
                  AND tc.table_catalog = '" . env('DB_DATABASE') . "'");
    }

    public function getTableUniques()
    {
        return \DB::select("SELECT
                    kcu.table_name,
                    kcu.column_name
                FROM
                    information_schema.table_constraints AS tc
                    JOIN information_schema.key_column_usage AS kcu
                      ON tc.constraint_name = kcu.constraint_name
                WHERE tc.constraint_type = 'UNIQUE'
                  AND tc.table_catalog = '" . env('DB_DATABASE') . "'");
    }
}
