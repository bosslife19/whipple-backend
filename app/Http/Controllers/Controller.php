<?php

namespace App\Http\Controllers;

abstract class Controller
{
    public function errRes($data, $message)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => $data
        ], 402);
    }

    public function sucRes($data, $message)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], 202);
    }
}
