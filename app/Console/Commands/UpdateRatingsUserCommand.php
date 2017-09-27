<?php

namespace App\Console\Commands;

use App\Http\App\Http\Controllers\SessionController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Models\Session;
use App\Models\Rating;

class UpdateRatingsUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:ratings-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update ratings user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sessions = Session::with("request")->get();

        foreach ($sessions as $session) {
            Rating::where("session_id", $session->id)
            ->update(["user_id" => $session->request->mentor_id]);
        }
    }
}
