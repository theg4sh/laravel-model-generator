<?php

namespace Iber\Generator\Commands;

use Illuminate\Database\Connection;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Support\Pluralizer;
use Illuminate\Console\GeneratorCommand;
use Iber\Generator\Utilities\RuleProcessor;
use Iber\Generator\Utilities\SetGetGenerator;
use Iber\Generator\Utilities\VariableConversion;
use Iber\Generator\Utilities\Table;
use Iber\Generator\Utilities\Schema;
use Iber\Generator\Utilities\RelationGenerator;
use Iber\Generator\Utilities\StubTemplate;
use Symfony\Component\Console\Input\InputOption;

class MakeModelsCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:models';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build models from existing schema.';

    /**
     * Default model namespace.
     *
     * @var string
     */
    protected $namespace = 'Models/';

    /**
     * Default class the model extends.
     *
     * @var string
     */
    protected $extends = 'Illuminate\Database\Eloquent\Model';

    /**
     * Rule processor class instance.
     *
     * @var
     */
    protected $ruleProcessor;

    /**
     * Rules for columns that go into the guarded list.
     *
     * @var array
     */
    protected $guardedRules = 'ends:_guarded'; //['ends' => ['_id', 'ids'], 'equals' => ['id']];

    /**
     * Rules for columns that go into the fillable list.
     *
     * @var array
     */
    protected $fillableRules = '';

    /**
     * Rules for columns that set whether the timestamps property is set to true/false.
     *
     * @var array
     */
    protected $timestampRules = 'ends:_at'; //['ends' => ['_at']];

    /**
     * Contains the template stub for set function
     * @var string
     */
    protected $setFunctionStub;
    /**
     * Contains the template stub for get function
     * @var string
     */
    protected $getFunctionStub;
    /**
     * Contains the template stub for relation function
     * @var string
     */
    protected $relationsStub;

    /**
     * @var Schema
     */
    protected $schema;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $folder = __DIR__ . '/../stubs/';
        if ($this->option("getset")) {
            // load the get/set function stubs

            $this->setFunctionStub = $this->files->get($folder . "setFunction.stub");
            $this->getFunctionStub = $this->files->get($folder . "getFunction.stub");
        }
        $this->relationStub = $this->files->get($folder . "relation.stub");

        // create rule processor

        Table::setNamespace('App/' . $this->namespace);
        $this->ruleProcessor = new RuleProcessor(
            $this->option('fillable'),
            $this->option('guarded'),
            $this->option('timestamps')
        );

        if (method_exists($this, 'qualifyClass')) {
            $qualifyClass = function ($string) {
                return Pluralizer::singular( $this->qualifyClass($string) ); };
        } else {
            $qualifyClass = function ($string) {
                return Pluralizer::singular( $this->parseName($string) ); };
        }

        $this->schema = new Schema($this->ruleProcessor,
            $this->option('prefix'),
            $this->option('dir'),
            $qualifyClass
        );

        \Event::listen(StatementPrepared::class, function ($event) {
            /** @var \PDOStatement $statement */
            $statement = $event->statement;
            /** @var Connection $connection */
            $connection = $event->connection;

            $pdo = $connection->getPdo();
            $statement->setFetchMode($pdo::FETCH_CLASS, \stdClass::class);
        });

        $tableNames = $this->option("tables") ? explode(',', trim($this->option('tables'))) : [];

        foreach ($this->generateTables() as $name => &$table) {
            if (!empty($tableNames) and !in_array($name, $tableNames)) {
                continue;
            }
            $this->generateTable( $table );
        }
    }

    protected function generateTables()
    {
        $this->schema->buildRelations();
        return $this->schema->getTables();
    }

    /**
     * Generate a model file from a database table.
     *
     * @param $table
     * @param $relations
     * @param $uniques
     * @param $pkeys
     * @return void
     */
    protected function generateTable(&$table)
    {
        $ignoreTable = $this->option("ignore");

        if ($this->option("ignoresystem")) {
            $ignoreSystem = "users,permissions,permission_role,roles,role_user,migrations,password_resets";

            if (is_string($ignoreTable)) {
                $ignoreTable .= "," . $ignoreSystem;
            } else {
                $ignoreTable = $ignoreSystem;
            }
        }

        // if we have ignore tables, we need to find all the posibilites
        $tableName = $table->getName();
        if (is_string($ignoreTable) && preg_match("/^" . $tableName . "|^" . $tableName . ",|," . $tableName . ",|," . $tableName . "$/", $ignoreTable)) {
            $this->info($tableName . " is ignored");

            return;
        }

        //prefix is the sub-directory within app
        /*
        $prefix = $this->option('dir');
        $class = $table->getClassName();
        if (method_exists($this, 'qualifyClass')) {
            $name = Pluralizer::singular($this->qualifyClass($prefix . $class));
        } else {
            $name = Pluralizer::singular($this->parseName($prefix . $class));
        }
        //$table->setNamespaceClass($name);
        */
        $name = $table->getNamespaceClass();

        if ($this->files->exists($path = $this->getPath($name)) && !$this->option('force')) {
            return $this->error($this->extends . ' for ' . $tableName . ' already exists!');
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->replaceTokens($name, $table));

        $this->info($this->extends . ' for ' . $tableName . ' created successfully.');
    }

    /**
     * Replace all stub tokens with properties.
     *
     * @param $name
     * @param $table
     *
     * @return mixed|string
     */
    protected function replaceTokens($name, $table)
    {
        $c = new StubTemplate($this->buildClass($name));

        $extends = $this->option('extends');

        $c->bind('extends', $extends);
        $c->bind('shortNameExtends', explode('\\', $extends)[count(explode('\\', $extends)) - 1]);

        $c->bindProperty('table', 'protected', 'table', $table->getName());

        if ($table->getPkey()) {
            $c->bindProperty('primaryKey', 'protected', 'primaryKey', $table->getPkey());
        }

        $properties = $table->getProperties();
        $c->bindProperty('fillable',   'protected', 'fillable',   $properties['fillable']);
        $c->bindProperty('guarded',    'protected', 'guarded',    $properties['guarded']);
        $c->bindProperty('timestamps', 'public',    'timestamps', $properties['timestamps']);

        if ($this->option("getset")) {
            $this->replaceTokensWithSetGetFunctions($properties, $c, "");
        }

        $relations = new RelationGenerator($table, $this->relationStub);
        $c->bind('relations', $relations->generateRelationFunctions());

        return $c->finalize();
    }

    /**
     * Replaces setters and getters from the stub. The functions are created
     * from provider properties.
     *
     * @param  array  $properties
     * @param  string $class
     * @return string
     */
    protected function replaceTokensWithSetGetFunctions($properties, &$c, $class)
    {
        $getters = "";
        $setters = "";

        $fillableGetSet = new SetGetGenerator($properties['fillable'], $this->getFunctionStub, $this->setFunctionStub);
        $getters .= $fillableGetSet->generateGetFunctions();
        $setters .= $fillableGetSet->generateSetFunctions();

        $guardedGetSet = new SetGetGenerator($properties['guarded'], $this->getFunctionStub, $this->setFunctionStub);
        $getters .= $guardedGetSet->generateGetFunctions();

        $c->bind('setters', $setters);
        $c->bind('getters', $getters);
    }

    /**
     * Get stub file location.
     *
     * @return string
     */
    public function getStub()
    {
        return __DIR__ . '/../stubs/model.stub';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['tables', null, InputOption::VALUE_OPTIONAL, 'Comma separated table names to generate', null],
            ['prefix', null, InputOption::VALUE_OPTIONAL, 'Table prefix', null],
            ['dir', null, InputOption::VALUE_OPTIONAL, 'Model directory', $this->namespace],
            ['extends', null, InputOption::VALUE_OPTIONAL, 'Parent class', $this->extends],
            ['fillable', null, InputOption::VALUE_OPTIONAL, 'Rules for $fillable array columns', $this->fillableRules],
            ['guarded', null, InputOption::VALUE_OPTIONAL, 'Rules for $guarded array columns', $this->guardedRules],
            ['timestamps', null, InputOption::VALUE_OPTIONAL, 'Rules for $timestamps columns', $this->timestampRules],
            ['ignore', "i", InputOption::VALUE_OPTIONAL, 'Ignores the tables you define, separated with ,', null],
            ['force', "f", InputOption::VALUE_OPTIONAL, 'Force override', false],
            ['ignoresystem', "s", InputOption::VALUE_NONE, 'If you want to ignore system tables.
            Just type --ignoresystem or -s'],
            ['getset', 'm', InputOption::VALUE_NONE, 'Defines if you want to generate set and get methods']
        ];
    }
}
