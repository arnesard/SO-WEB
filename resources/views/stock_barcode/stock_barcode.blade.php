@extends('layouts.app')

@section('content')
    <style>
        /* STYLE HEADER & TOMBOL */
        .header-container-gt {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #dee2e6;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-shrink: 0;
        }

        .btn-gt-custom {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: transparent;
            color: #6c757d;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-weight: 700;
            font-size: 11px;
            padding: 8px 15px;
            text-transform: uppercase;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .active-report {
            background-color: #ffc107 !important;
            color: #000 !important;
            border-color: #000 !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .active-master {
            background-color: #0d6efd !important;
            color: #fff !important;
            border-color: #0d6efd !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
    {{-- 1. Header Card --}}
    <div class="mt-4 p-4 border-2 border-dashed rounded-4 text-center bg-white shadow-sm">
        <i data-lucide="cog" class="text-muted mb-2 animate-spin" style="animation: spin 4s linear infinite;"></i>
        <h6 class="fw-bold text-dark">System Under Heavy Development</h6>
        <p class="text-muted small mb-0">Modul Analytics, Export PDF, dan User Management sedang dalam antrean
            pengerjaan.</p>
    </div>
@endsection
