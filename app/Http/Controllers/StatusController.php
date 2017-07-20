<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Status;

class StatusController extends Controller {
    use RESTActions;

    const MODEL = "App\Models\Status";
}
