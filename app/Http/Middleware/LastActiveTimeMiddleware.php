<?php
namespace App\Http\Middleware;

use App\Repositories\LastActiveRepository;
use Carbon\Carbon;

class LastActiveTimeMiddleware
{
    protected $lastActiveRepository;

    public function __construct(LastActiveRepository $lastActiveRepository)
    {
        $this->lastActiveRepository = $lastActiveRepository;
    }

    /**
     * Intercepts an incoming request and log the last active time of a user.
     *
     * @param \Illuminate\Http\Request $request - incoming request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        $user = $request->user();

        if ($user) {
            $this->lastActiveRepository->set($user->uid, Carbon::now()->toDateTimeString());
        }

        return $next($request);
    }
}
