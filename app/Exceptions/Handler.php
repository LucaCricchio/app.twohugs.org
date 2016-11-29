<?php

namespace App\Exceptions;

use App\Helpers\ErrorCode;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof ModelNotFoundException) {
            $exception = new NotFoundHttpException($exception->getMessage(), $exception);
        }

        if($exception instanceof ValidationException){
            return $this->validationErrorResponse($exception->getErrors()->toArray());
        }

        if($exception instanceof ExceptionWithCustomCode){
            return $this->errorResponse($exception->getMessage(), $exception->getCode(), $exception->getErrorCode());
        }

        return $this->genericErrorResponse($exception);
    }


    /**
     * Conforme per quanto possibile a http://jsonapi.org/format/
     *
     * @param string|array $errors
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse($errors = [], $statusCode, $errorCode)
    {
        //$errors deve essere sempre un array secondo lo standard JSON API
        if(!is_array($errors))  {
            $errors = [$errors];
        }

        $response = [
            'jsonapi' => [
                'version' => config('app.api_version')
            ],
            'errorCode'    => $errorCode,
            'errors'    => $errors,
        ];

        return response()->json($response)->setStatusCode($statusCode);
    }

    protected function validationErrorResponse($errors)
    {
        return $this->errorResponse($errors, Response::HTTP_UNPROCESSABLE_ENTITY, ErrorCode::VALIDATION);

    }

    /**
     * @param Exception $e
     * @return \Illuminate\Http\JsonResponse
     */
    public function genericErrorResponse(Exception $e)
    {
        $errors = [];

        // If the app is in debug mode
        if (config('app.debug')) {
            // Add the exception class name, message and stack trace to response
            $errors['exception'] = get_class($e); // Reflection might be better here
            $errors['message'] = $e->getMessage();
            $errors['trace'] = $e->getTrace();
        }

        // Default response of 500
        $status = 500;

        // If this exception is an instance of HttpException
        if ($this->isHttpException($e)) {
            // Grab the HTTP status code from the Exception
            $status = $e->getStatusCode();
        }

        return $this->errorResponse($errors, $status, ErrorCode::UNKNOWN);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest('login');
    }
}
