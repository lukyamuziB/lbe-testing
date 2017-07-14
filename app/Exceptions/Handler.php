<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        $response = null;

        // Get exception type
        switch ($e) {
            case $e instanceof NotFoundException:
                $response = $this->composeJsonResponse(
                    Response::HTTP_NOT_FOUND, $e->getMessage()
                );
                break;
            case $e instanceof UnauthorizedException:
                $response = $this->composeJsonResponse(
                    Response::HTTP_UNAUTHORIZED, $e->getMessage()
                );
                break;
            case $e instanceof AccessDeniedException:
                $response = $this->composeJsonResponse(
                    Response::HTTP_FORBIDDEN, $e->getMessage()
                );
                break;
            default:
                $response = parent::render($request, $e);
        }

        return $response;
    }

    /**
     * Compose http json responses
     *
     * @param  $header
     * @param  $message
     * @return \Illuminate\Http\Response
     */
    private function composeJsonResponse($header, $message)
    {
        return response()->json(["message" => $message], $header);
    }

}
