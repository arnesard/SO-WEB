<?php

namespace App\Http\Controllers\data_master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterItemController extends Controller
{
    public function index()
    {
        // Ambil data master terbaru di atas
        $items = DB::table('master_items')
            ->orderBy('updated_at', 'desc')
            ->get();
        return response()->json($items);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->except(['_token', 'id']);
            DB::table('master_items')->insert(array_merge($data, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $request->except(['_token', 'id']);
            DB::table('master_items')->where('id', $id)->update(array_merge($data, [
                'updated_at' => now()
            ]));
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        DB::table('master_items')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }
}
