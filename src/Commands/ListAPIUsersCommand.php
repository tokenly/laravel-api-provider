<?php

namespace Tokenly\LaravelApiProvider\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ListAPIUsersCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'api:list-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List API Users';


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setHelp(<<<EOF
Show User API Credentials
EOF
        );
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {

        $user_repository = $this->laravel->make('Tokenly\LaravelApiProvider\Contracts\APIUserRepositoryContract');
        $users = $user_repository->findAll();

        foreach($users as $user) {
            $user['password'] = '********';
            $this->line(json_encode($user, 192));
        }
    }

}
