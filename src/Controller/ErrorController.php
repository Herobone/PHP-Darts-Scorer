<?php

namespace App\Controller;

use App\Core\BaseController;
use Throwable;

class ErrorController extends BaseController
{
    /**
     * Renders the error page with appropriate details.
     *
     * @param int $statusCode The HTTP status code (e.g., 404, 500).
     * @param string $message A custom message to display (optional).
     * @param Throwable|null $exception The exception that occurred (optional).
     */
    public function showError(int $statusCode, string $message = '', ?Throwable $exception = null): void
    {
        http_response_code($statusCode);

        $data = [
            'status_code' => $statusCode,
            'message' => $message ?: 'An unexpected error occurred.',
            'exception' => $exception,
        ];

        // Determine a more specific title or message based on status code
        switch ($statusCode) {
            case 400:
                $data['title'] = 'Bad Request';
                $data['message'] = $message ?: 'The server could not understand the request due to invalid syntax.';
                break;
            case 401:
                $data['title'] = 'Unauthorized';
                $data['message'] = $message ?: 'You are not authorized to access this page. Please log in.';
                break;
            case 403:
                $data['title'] = 'Forbidden';
                $data['message'] = $message ?: 'You do not have permission to access this resource.';
                break;
            case 404:
                $data['title'] = 'Page Not Found';
                $data['message'] = $message ?: 'Sorry, the page you are looking for could not be found.';
                break;
            case 500:
                $data['title'] = 'Internal Server Error';
                $data['message'] = $message ?: 'An unexpected error occurred on the server. Please try again later.';
                break;
            default:
                $data['title'] = 'Error ' . $statusCode;
                break;
        }

        if ($exception && DEV) { // In dev mode, show more details from the exception
            $data['detailed_message'] = $exception->getMessage();
            $data['trace'] = $exception->getTraceAsString();
        } else if ($exception) {
             error_log("Error: " . $exception->getMessage() . " on " . $exception->getFile() . ":" . $exception->getLine());
        }


        $this->render('error', $data);
    }
}

