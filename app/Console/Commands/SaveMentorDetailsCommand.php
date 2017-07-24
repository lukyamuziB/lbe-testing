<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Clients\AISClient as AISClient;
use App\Models\Request as MentorshipRequest;
use App\Models\User as User;
use Mockery\Exception;

class SaveMentorDetailsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'save:mentor-details';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Saves all the mentors emails and ids to users table';

    protected $ais_client;

    public function __construct(AISClient $ais_client)
    {
        parent::__construct();
        $this->ais_client = $ais_client;

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $mentors_ids = $this -> getAllMentorIds();

        foreach ($mentors_ids as $mentor_id) {
            // Get mentor data by their id
            $response = $this->ais_client->getUserById($mentor_id);
            $mentor_details = [
                "user_id" => $mentor_id,
                "email" => $response['email']
            ];
            // Save Mentor email and id in the users table 
            User::updateOrCreate($mentor_details);
        }
    }

     /**
     * This fetches all the mentor ids from
     * request table and stores them in an array
     *
     * @return array mentors' ids 
     */
    private function getAllMentorIds()
    {   
        // Returns all the mentors ids in an array
        return MentorshipRequest::pluck('mentor_id');
    }

}
