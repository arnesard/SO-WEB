<?php

namespace App\Services\dashboard;

use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Ambil data unik untuk filter master_items
     */
    public function getMasterFilters()
    {
        return [
            'grades'     => DB::table('master_items')->whereNotNull('grade')->distinct()->pluck('grade'),
            'products'   => DB::table('master_items')->whereNotNull('product')->distinct()->pluck('product'),
            'types'      => DB::table('master_items')->whereNotNull('type')->distinct()->pluck('type'),
            'brands'     => DB::table('master_items')->whereNotNull('brand')->distinct()->pluck('brand'),
            'categories' => DB::table('master_items')->whereNotNull('category')->distinct()->pluck('category'),
        ];
    }
}
