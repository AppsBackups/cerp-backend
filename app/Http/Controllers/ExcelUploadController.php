<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExcelUploadController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'records' => 'required|array|min:1',
            'records.*.pin' => 'required',
            'records.*.lat' => 'required|numeric',
            'records.*.lng' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $records = array_map(fn ($r) => [
            'pin' => $r['pin'],
            'lat' => $r['lat'],
            'lng' => $r['lng'],
            'created_at' => now(),
            'updated_at' => now(),
        ], $request->records);

        // 🚀 bulk insert (fast)
        DB::table('locations')->insert($records);

        return response()->json([
            'error' => false,
            'message' => 'Locations inserted',
            'count' => count($records),
        ]);
    }
}
