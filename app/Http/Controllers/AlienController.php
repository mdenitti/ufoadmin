<?php

namespace App\Http\Controllers;

use App\Models\Alien;
use Illuminate\Http\Request;

class AlienController extends Controller
{
    public function index()
    {
        $aliens = Alien::all();
        return response()->json($aliens);
    }

    public function show($id)
    {
        try {
            $alien = Alien::findOrFail($id);
            return response()->json($alien);
        } catch (Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Alien not found'], 404);
        }
    }

    public function store(Request $request)
    {
        $alien = Alien::create($request->all());
        return response()->json($alien, 201);
    }

    public function update(Request $request, $id)
    {
        $alien = Alien::findOrFail($id);
        $alien->update($request->all());
        return response()->json($alien, 200);
    }

    public function destroy($id)
    {
        Alien::destroy($id);
        return response()->json(null, 204);
    }
}