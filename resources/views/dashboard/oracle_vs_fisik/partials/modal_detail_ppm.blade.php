@php
    $products = $data->groupBy('product');
    $grades = ['OE', 'OK', '2nd'];

    // Hitung total untuk kalkulasi PPM di bawah
    $totalAllOnHand = $data->sum('on_hand');
    $totalAllCounted = $data->sum('counted');
    $totalAllVar = $totalAllCounted - $totalAllOnHand;

    // Rumus PPM: (Variance / Total Oracle) * 1.000.000
    // Gunakan abs() agar PPM selalu positif sebagai indikator akurasi
    $ppm = $totalAllOnHand > 0 ? (abs($totalAllVar) / $totalAllOnHand) * 1000000 : 0;
@endphp

<div class="table-responsive">
    <table class="table table-bordered table-sm table-hover mb-0" style="font-size: 11px;">
        <thead class="bg-dark text-white text-center align-middle">
            <tr>
                <th rowspan="2" class="text-start ps-2">Product</th>
                @foreach ($grades as $g)
                    <th colspan="3" class="border-start border-light">{{ $g }}</th>
                @endforeach
                <th colspan="3" class="border-start border-light bg-primary">TOTAL</th>
            </tr>
            <tr>
                @foreach ($grades as $g)
                    <th class="border-start border-light">On Hand</th>
                    <th>Counted</th>
                    <th>Var</th>
                @endforeach
                <th class="border-start border-light">On Hand</th>
                <th>Counted</th>
                <th>Var</th>
            </tr>
        </thead>
        <tbody class="text-end">
            @foreach ($products as $productName => $items)
                @php
                    $rowTotals = ['on_hand' => 0, 'counted' => 0, 'variance' => 0];
                @endphp
                <tr>
                    <td class="text-start fw-bold ps-2">{{ $productName }}</td>
                    @foreach ($grades as $g)
                        @php
                            $item = $items->firstWhere('grade', $g);
                            $onHand = $item ? $item->on_hand : 0;
                            $counted = $item ? $item->counted : 0;
                            $var = $counted - $onHand;
                            $rowTotals['on_hand'] += $onHand;
                            $rowTotals['counted'] += $counted;
                            $rowTotals['variance'] += $var;
                        @endphp
                        <td class="border-start">{{ number_format($onHand, 0, ',', '.') }}</td>
                        <td>{{ number_format($counted, 0, ',', '.') }}</td>
                        <td class="fw-bold {{ $var < 0 ? 'text-danger' : ($var > 0 ? 'text-primary' : 'text-muted') }}">
                            {{ number_format($var, 0, ',', '.') }}
                        </td>
                    @endforeach
                    <td class="border-start fw-bold text-dark">{{ number_format($rowTotals['on_hand'], 0, ',', '.') }}
                    </td>
                    <td class="fw-bold text-dark">{{ number_format($rowTotals['counted'], 0, ',', '.') }}</td>
                    <td
                        class="fw-bold {{ $rowTotals['variance'] < 0 ? 'text-danger' : ($rowTotals['variance'] > 0 ? 'text-primary' : 'text-muted') }}">
                        {{ number_format($rowTotals['variance'], 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="table-dark fw-bold">
            <tr>
                <td class="text-start ps-2">TOTAL ALL</td>
                @foreach ($grades as $g)
                    @php $gTotal = $data->where('grade', $g); @endphp
                    <td class="border-start">{{ number_format($gTotal->sum('on_hand'), 0, ',', '.') }}</td>
                    <td>{{ number_format($gTotal->sum('counted'), 0, ',', '.') }}</td>
                    <td>{{ number_format($gTotal->sum('counted') - $gTotal->sum('on_hand'), 0, ',', '.') }}</td>
                @endforeach
                <td class="border-start">{{ number_format($totalAllOnHand, 0, ',', '.') }}</td>
                <td>{{ number_format($totalAllCounted, 0, ',', '.') }}</td>
                <td>{{ number_format($totalAllVar, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</div>

{{-- AREA PPM --}}
<div class="mt-4 p-3 border rounded shadow-sm bg-white">
    <div class="d-flex align-items-center justify-content-center">
        <h4 class="fw-black mb-0 me-4">PPM : <span class="text-emerald">{{ number_format($ppm, 2, ',', '.') }}</span>
        </h4>
        <div class="text-muted border-start ps-3" style="font-size: 13px;">
            <div class="fw-bold">Detail Perhitungan:</div>
            <div>( Total Variance : <strong>{{ number_format(abs($totalAllVar), 0, ',', '.') }}</strong> ) /
                ( Total On Hand : <strong>{{ number_format($totalAllOnHand, 0, ',', '.') }}</strong> )
                × 1.000.000
            </div>
        </div>
    </div>
</div>
