<?php

namespace Tokenly\LaravelApiProvider\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeAPIModelCommand extends GeneratorCommand {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'api:new-model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new API Model';


    public function getHelp() { return 'Generates a new API model class'; }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'Model Class Name like BearCub'],
        ];
    }
    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['with-repository', 'r', InputOption::VALUE_NONE, 'Also generate a repository'],
            ['with-migration', 'm', InputOption::VALUE_NONE, 'Also generate a migration'],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if (parent::fire() !== false) {
            if ($this->option('with-repository')) {
                $this->comment('making repository');
                $this->call('api:new-repository', ['name' => $this->argument('name')]);
            }
            if ($this->option('with-migration')) {
                $this->comment('making migration');
                $name = $this->argument('name');
                $table = str_plural(snake_case($name));
                $this->call('make:migration', ['name' => "create_{$table}_table", '--create' => $table]);

            }
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/apimodel.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Models';
    }


    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $class = parent::buildClass($name);
        return $class;
    }


}
