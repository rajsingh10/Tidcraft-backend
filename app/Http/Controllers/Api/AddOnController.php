<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AddOn;
use Illuminate\Http\Request;

class AddOnController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $addOns = AddOn::all();
        return response()->json([
            'status' => 'success',
            'data' => $addOns
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'period' => 'required|string|max:255',
            'limit' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $addOn = AddOn::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Add-on created successfully.',
            'data' => $addOn
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(AddOn $addOn)
    {
        return response()->json([
            'status' => 'success',
            'data' => $addOn
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AddOn $addOn)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'period' => 'sometimes|required|string|max:255',
            'limit' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $addOn->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Add-on updated successfully.',
            'data' => $addOn
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AddOn $addOn)
    {
        $addOn->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Add-on deleted successfully.'
        ]);
    }
}
