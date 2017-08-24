<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\User;
use App\Repositories\SlackUsersRepository;

class UpdateUsersSlackIdsCommand extends Command
{
    /**
     * The console command name and signature.
     *
     * @var string
     */
    protected $signature = 'update:users:slack-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Update all users model with slack ids";

    protected $slack_repository;

    /**
     * Create a new command instance.
     *
     * @param  SlackUsersRepository $slack_repository Dependency Injection
     */
    public function __construct(SlackUsersRepository $slack_repository)
    {
         parent::__construct();

         $this->slack_repository = $slack_repository;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $users = $this->getUsersWithoutSlackId();

        foreach ($users as $user) {
            // fetch slack user from the repository
            $slack_user = $this->slack_repository->getByEmail($user->email);

            if ($slack_user) {
                $user->update(['slack_id' => $slack_user->id]);
            }
        }

        $missed_users = $this->getUsersWithoutSlackId()->implode('email', "\n");
        $message = "The following users were not updated:\n{$missed_users}";
        $this->info($message);
    }

    /**
     * This fetches all the users without slack_id
     *
     * @return array collection of users
     */
    private function getUsersWithoutSlackId()
    {
        return User::where('slack_id', null)->get();
    }
}
