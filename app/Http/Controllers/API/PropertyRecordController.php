<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PropertyRecord;

class PropertyRecordController extends Controller
{
   public function store(Request $request)
{
    $records = $request->input('records');

    if (!is_array($records)) {
        return response()->json(['error' => 'Invalid data format.'], 400);
    }

    $batchData = [];

    foreach ($records as $data) {
        $batchData[] = [
            'pin' => $data['pin'] ?? null,
            'ratingarea' => $data['ratingarea'] ?? null,
            'circle' => $data['circle'] ?? null,
            'Locality' => $data['Locality'] ?? null,
            'Block' => $data['Block'] ?? null,
            'Street_Address' => $data['Street_Address'] ?? null,
            'OwnerName' => $data['OwnerName'] ?? null,
            'Road' => $data['Road'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    // Insert in chunks if too large
    $chunks = array_chunk($batchData, 1000); // Laravel recommends batching like this

    foreach ($chunks as $chunk) {
        PropertyRecord::insert($chunk);
    }

    return response()->json(['message' => 'Records stored successfully']);
}

}
