<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SensorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/sensors
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'id_equipement' => 'required|integer',
            'description'   => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Retrieve the id_device from the parent equipment
        $equipment = DB::table('equipement')->where('id', $request->id_equipement)->first();
        $id_device = $equipment ? $equipment->id_device : null;

        $sensorId = DB::table('sensors')->insertGetId([
            'name'          => $request->name,
            'description'   => $request->description,
            'id_equipement' => $request->id_equipement,
            'id_device'     => $id_device,
            'id_unit'       => null,
        ]);

        $sensor = DB::table('sensors')->where('id', $sensorId)->first();

        return response()->json([
            'success' => true,
            'message' => 'Capteur créé avec succès',
            'data'    => $sensor
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/sensors/{id}
     */
    public function destroy(string $id)
    {
        $deleted = DB::table('sensors')->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Capteur introuvable'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Capteur supprimé avec succès'
        ], 200);
    }
}
