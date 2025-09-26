<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        if ($request instanceof Request && ($request->expectsJson() || $request->is('api/*'))) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request instanceof Request && ($request->expectsJson() || $request->is('api/*'))) {
            return $this->formatErrorResponse(
                'Não autenticado.',
                Response::HTTP_UNAUTHORIZED,
                'UNAUTHENTICATED',
                null,
                $exception
            );
        }

        return parent::unauthenticated($request, $exception);
    }

    private function handleApiException(Request $request, Throwable $exception)
    {
        $exception = $this->prepareException($exception);

        if ($exception instanceof HttpResponseException) {
            return $exception->getResponse();
        }

        if ($exception instanceof ValidationException) {
            return $this->formatErrorResponse(
                'Dados inválidos.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'VALIDATION_ERROR',
                $exception->errors(),
                $exception
            );
        }

        if ($exception instanceof AuthenticationException) {
            return $this->formatErrorResponse(
                'Não autenticado.',
                Response::HTTP_UNAUTHORIZED,
                'UNAUTHENTICATED',
                null,
                $exception
            );
        }

        if ($exception instanceof AuthorizationException) {
            return $this->formatErrorResponse(
                'Ação não autorizada.',
                Response::HTTP_FORBIDDEN,
                'FORBIDDEN',
                null,
                $exception
            );
        }

        if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
            return $this->formatErrorResponse(
                'Recurso não encontrado.',
                Response::HTTP_NOT_FOUND,
                'NOT_FOUND',
                null,
                $exception
            );
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return $this->formatErrorResponse(
                'Método não permitido para esta rota.',
                Response::HTTP_METHOD_NOT_ALLOWED,
                'METHOD_NOT_ALLOWED',
                null,
                $exception
            );
        }

        if ($exception instanceof ThrottleRequestsException) {
            return $this->formatErrorResponse(
                'Muitas requisições, tente novamente mais tarde.',
                Response::HTTP_TOO_MANY_REQUESTS,
                'TOO_MANY_REQUESTS',
                null,
                $exception
            );
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $message = $exception->getMessage();

            if ($status >= 500 && ! config('app.debug')) {
                $message = 'Erro interno do servidor.';
            } elseif ($message === '') {
                $message = Response::$statusTexts[$status] ?? 'Erro no processamento da requisição.';
            }

            return $this->formatErrorResponse(
                $message,
                $status,
                $this->errorCodeFromStatus($status),
                null,
                $exception
            );
        }

        $message = config('app.debug') && $exception->getMessage() !== ''
            ? $exception->getMessage()
            : 'Erro interno do servidor.';

        return $this->formatErrorResponse(
            $message,
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'SERVER_ERROR',
            null,
            $exception
        );
    }

    private function formatErrorResponse(
        string $message,
        int $status,
        string $code,
        ?array $errors = null,
        ?Throwable $exception = null
    ): JsonResponse {
        $payload = [
            'status' => 'error',
            'code' => $code,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ];

        if (! empty($errors)) {
            $payload['errors'] = $errors;
        }

        if (config('app.debug') && $exception !== null) {
            $payload['debug'] = [
                'exception' => get_class($exception),
            ];
        }

        return response()->json($payload, $status);
    }

    private function errorCodeFromStatus(int $status): string
    {
        return match ($status) {
            Response::HTTP_BAD_REQUEST => 'BAD_REQUEST',
            Response::HTTP_UNAUTHORIZED => 'UNAUTHENTICATED',
            Response::HTTP_FORBIDDEN => 'FORBIDDEN',
            Response::HTTP_NOT_FOUND => 'NOT_FOUND',
            Response::HTTP_METHOD_NOT_ALLOWED => 'METHOD_NOT_ALLOWED',
            Response::HTTP_CONFLICT => 'CONFLICT',
            Response::HTTP_TOO_MANY_REQUESTS => 'TOO_MANY_REQUESTS',
            default => 'HTTP_ERROR_' . $status,
        };
    }
}
