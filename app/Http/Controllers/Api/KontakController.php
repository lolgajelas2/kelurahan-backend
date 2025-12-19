<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kontak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Mail\BalasanPesanKontak;
use Illuminate\Support\Facades\Mail;

class KontakController extends Controller
{
    /**
     * Display a listing of messages.
     */
    public function index(Request $request)
    {
        $query = Kontak::query();

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('subjek', 'like', '%' . $request->search . '%');
            });
        }

        $messages = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));
        
        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * Store a newly created message.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subjek' => 'required|string|max:255',
            'pesan' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $kontak = Kontak::create([
            'nama' => $request->nama,
            'email' => $request->email,
            'subjek' => $request->subjek,
            'pesan' => $request->pesan,
            'status' => 'baru',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pesan berhasil dikirim',
            'data' => $kontak
        ], 201);
    }

    /**
     * Display the specified message.
     */
    public function show(string $id)
    {
        $kontak = Kontak::find($id);
        
        if (!$kontak) {
            return response()->json([
                'success' => false,
                'message' => 'Pesan tidak ditemukan'
            ], 404);
        }

        // Mark as read
        if ($kontak->status === 'baru') {
            $kontak->update(['status' => 'dibaca']);
        }

        return response()->json([
            'success' => true,
            'data' => $kontak
        ]);
    }

    /**
     * Update the message status.
     */
    public function update(Request $request, string $id)
    {
        $kontak = Kontak::find($id);
        
        if (!$kontak) {
            return response()->json([
                'success' => false,
                'message' => 'Pesan tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:baru,dibaca,dibalas',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $kontak->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status pesan diperbarui',
            'data' => $kontak
        ]);
    }

    /**
     * Remove the specified message.
     */
    public function destroy(string $id)
    {
        $kontak = Kontak::find($id);
        
        if (!$kontak) {
            return response()->json([
                'success' => false,
                'message' => 'Pesan tidak ditemukan'
            ], 404);
        }

        $kontak->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pesan berhasil dihapus'
        ]);
    }

    /**
     * Send a reply to a contact message.
     */
    public function reply(Request $request, string $id)
    {
        $kontak = Kontak::find($id);
        if (!$kontak) {
            return response()->json([
                'success' => false,
                'message' => 'Pesan tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'balasan' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Kirim email balasan
        Mail::to($kontak->email)->send(new BalasanPesanKontak($kontak, $request->balasan));
        // Update status menjadi dibalas
        $kontak->update(['status' => 'dibalas']);

        return response()->json([
            'success' => true,
            'message' => 'Balasan berhasil dikirim',
        ]);
    }
}
