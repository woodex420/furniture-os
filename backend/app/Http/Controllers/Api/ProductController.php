<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Implementation for React Frontend
        return response()->json([
            'status' => 'success',
            'data' => []
        ]);
    }

    public function store(Request $request)
    {
        // Implementation for React Frontend
        return response()->json([
            'status' => 'success',
            'message' => 'Product stored'
        ]);
    }
}