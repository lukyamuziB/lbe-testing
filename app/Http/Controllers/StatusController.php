<?php

namespace App\Http\Controllers;

use App\Skill;
use App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Status;

class StatusController extends Controller {
    use RESTActions;

    const MODEL = "App\Status";
}
