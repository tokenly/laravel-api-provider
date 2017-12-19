<?php

namespace Tokenly\LaravelApiProvider\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Tokenly\LaravelEventLog\Facade\EventLog;

class NewAPIUserCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'api:new-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new API User';


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email Address')
            ->addArgument('name', InputArgument::OPTIONAL, 'Name', '')
            ->addArgument('username', InputArgument::OPTIONAL, 'Username')
            ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'Password', null)
            ->setHelp(<<<EOF
Create a new user with API Credentials
EOF
        );
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $user_repository = $this->laravel->make('Tokenly\LaravelApiProvider\Contracts\APIUserRepositoryContract');
        $user_vars = [
            'name'     => $this->input->getArgument('name'),
            'username' => $this->input->getArgument('username'),
            'email'    => $this->input->getArgument('email'),
            'password' => $this->input->getOption('password'),

        ];
        $user_model = $user_repository->create($user_vars);
        
        // log
        EventLog::log('user.create.cli', $user_model, ['id', 'email', 'apisecretkey']);

        // show the new user
        $user = clone $user_model;
        $user['password'] = '********';
        $this->line(json_encode($user, 192));
    }

}
