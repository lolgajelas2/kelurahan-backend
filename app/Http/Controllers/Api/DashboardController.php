<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use App\Models\Layanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $days = $request->get('days', 7);
        $startDate = now()->subDays($days);

        $stats = [
            'total_permohonan' => Permohonan::where('created_at', '>=', $startDate)->count(),
            'permohonan_selesai' => Permohonan::where('created_at', '>=', $startDate)
                                            ->where('status', 'selesai')->count(),
            'permohonan_proses' => Permohonan::where('created_at', '>=', $startDate)
                                           ->where('status', 'proses')->count(),
            'permohonan_baru' => Permohonan::where('created_at', '>=', $startDate)
                                         ->where('status', 'baru')->count(),
        ];

        $recentApplications = Permohonan::with('layanan')
                                     ->whereHas('layanan') // Only get permohonan with valid layanan
                                     ->orderBy('created_at', 'desc')
                                     ->limit(5)
                                     ->get();

        $popularServices = Layanan::select('layanan.nama')
                                ->selectRaw('COUNT(permohonan.id) as count')
                                ->leftJoin('permohonan', 'layanan.id', '=', 'permohonan.layanan_id')
                                ->where('permohonan.created_at', '>=', $startDate)
                                ->groupBy('layanan.id', 'layanan.nama')
                                ->orderBy('count', 'desc')
                                ->limit(4)
                                ->get();

        // Chart data - permohonan per hari (7 hari terakhir)
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = Permohonan::whereDate('created_at', $date)->count();
            $chartData[] = [
                'date' => now()->subDays($i)->format('d M'),
                'jumlah' => $count
            ];
        }

        // Status distribution
        $statusDistribution = [
            ['name' => 'Baru', 'value' => Permohonan::where('status', 'baru')->count()],
            ['name' => 'Proses', 'value' => Permohonan::where('status', 'proses')->count()],
            ['name' => 'Selesai', 'value' => Permohonan::where('status', 'selesai')->count()],
            ['name' => 'Ditolak', 'value' => Permohonan::where('status', 'ditolak')->count()],
        ];

        // Permohonan per layanan (Bar chart data)
        $permohonanPerLayanan = Layanan::select('layanan.nama')
            ->selectRaw('COUNT(permohonan.id) as total')
            ->leftJoin('permohonan', 'layanan.id', '=', 'permohonan.layanan_id')
            ->where('permohonan.created_at', '>=', $startDate)
            ->groupBy('layanan.id', 'layanan.nama')
            ->orderBy('total', 'desc')
            ->limit(6)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_applications' => $recentApplications,
                'popular_services' => $popularServices,
                'chart_data' => $chartData,
                'status_distribution' => $statusDistribution,
                'permohonan_per_layanan' => $permohonanPerLayanan
            ]
        ]);
    }
}
