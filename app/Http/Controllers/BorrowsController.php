<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Validator;

use App\Models\Borrows;

use App\Models\BorrowsHistory;

class BorrowsController extends Controller
{
    // Ambil semua data peminjaman
    public function index()
    {
        $borrows = DB::select("SELECT * FROM borrows");
        return response()->json($borrows);
    }

    // Tambah peminjaman
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'books_id' => 'required|exists:books,id',
            'borrower_name' => 'required|string',
            'borrow_date' => 'required|date'
        ]);

        

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data tidak boleh kosong periksa kembali',
                'errors' => $validator->errors()
            ], 422);
        }

        // Cek stok buku
        $book = DB::selectOne("SELECT * FROM books WHERE id = ?", [$request->books_id]);
        if ($book->stock <= 0) {
            return response()->json(['message' => 'Stock buku habis'], 400);
        }

        // Kurangi stok buku
        DB::update("UPDATE books SET stock = stock - 1 WHERE id = ?", [$request->books_id]);

        
        $userId = auth()->id();
        
        // Simpan data peminjaman
        DB::insert("INSERT INTO borrows (books_id, user_id, borrower_name, borrow_date, created_at) VALUES (?, ?, ?, ?, NOW())", [
            $request->books_id, $userId, $request->borrower_name, $request->borrow_date
        ]);

        $lastId = DB::getPdo()->lastInsertId();

        // Simpan data history peminjaman 
        DB::insert("INSERT INTO borrows_histories (borrows_id, books_id, user_id, borrower_name, borrow_date, created_at) VALUES (?, ?, ?, ?, ?, NOW())", [
            $lastId, $request->books_id, $userId, $request->borrower_name, $request->borrow_date
        ]);

        return response()->json(['message' => 'Peminjaman berhasil'], 201);
    }

    // Update data peminjaman
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'borrower_name' => 'required|string',
            'borrow_date' => 'required|date',
            'return_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data tidak boleh kosong periksa kembali',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::update("UPDATE borrows SET borrower_name = ?, borrow_date = ?, return_date = ? WHERE id = ?", [
            $request->borrower_name, $request->borrow_date, $request->return_date, $id
        ]);

        return response()->json(['message' => 'Peminjaman diperbarui']);
    }

    // Fungsi mengembalikan buku & update stok
    public function returnBook($id)
    {
        $borrow = DB::selectOne("SELECT * FROM borrows WHERE id = ?", [$id]);

        if (!$borrow) {
            return response()->json(['message' => 'Peminjaman tidak ditemukan'], 404);
        }

        // Update tanggal pengembalian
        DB::update("UPDATE borrows SET return_date = ? WHERE id = ?", [now(), $id]);

        // Tambahkan kembali stok buku
        DB::update("UPDATE books SET stock = stock + 1 WHERE id = ?", [$borrow->books_id]);

        return response()->json(['message' => 'Buku berhasil dikembalikan']);
    }

    // Hapus data peminjaman
    public function destroy($id)
    {
        DB::delete("DELETE FROM borrows WHERE id = ?", [$id]);
        return response()->json(['message' => 'Peminjaman dihapus']);
    }
}
