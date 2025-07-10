<?php

namespace App\Traits;

trait ResponseTrait
{
    /**
     * Success response.
     *
     * @param string $message
     * @param mixed $data
     * @param int $status
     * @param array|null $meta
     * @return \Illuminate\Http\JsonResponse
     */
    public function success($message, $data = [], $status = 200, $meta = null)
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $status);
    }


    /**
     * Failure response.
     *
     * @param string $message
     * @param int $status
     * @param mixed $errors
     * @return \Illuminate\Http\JsonResponse
     */
    public function fail($message, $status = 422, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Exception handling response.
     *
     * @param string $message
     * @param \Throwable|null $exception
     * @param int $status
     * @return \Illuminate\Http\JsonResponse
     */
    public function exception($message, $exception = null, $status = 500)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (config('app.debug') && $exception) {
            $response['error'] = $exception->getMessage();
            $response['trace'] = $exception->getTrace();
        }

        return response()->json($response, $status);
    }
}
