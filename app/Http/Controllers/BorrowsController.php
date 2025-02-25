<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Validator;

use App\Models\Borrows;

use App\Models\BorrowsHistory;

class BorrowsController extends Controller
{
    // Tampil data peminjaman
    public function index(Request $request)
    {
        $page = intval($request->query('page', 1));
        $limit = intval($request->query('limit', 5));
        $offset = ($page - 1) * $limit;
        $keyword = $request->query('search', ''); 

        
        $sql = sprintf("
            SELECT borrows.id, borrows.books_id, borrows.borrower_name, borrows.borrow_date, borrows.return_date, books.title  
            FROM borrows 
            LEFT JOIN books ON books.id = borrows.books_id 
            WHERE borrows.borrower_name LIKE ? 
            OR borrows.borrow_date LIKE ? 
            OR borrows.return_date LIKE ? 
            OR books.title LIKE ? 
            LIMIT %d OFFSET %d
        ", $limit, $offset);

        
        $borrows = DB::select($sql, ["%$keyword%", "%$keyword%", "%$keyword%", "%$keyword%"]);

        
        return response()->json($borrows);

    }

    // Tampil data history peminjaman
    public function getBorrowingHistory(Request $request)
    {
        $page = intval($request->query('page', 1));
        $limit = intval($request->query('limit', 5));
        $offset = ($page - 1) * $limit;
        $keyword = $request->query('search', ''); 

        
        $sql = sprintf("
            SELECT borrows_histories.id, borrows_histories.books_id, borrows_histories.borrower_name, borrows_histories.borrow_date, borrows_histories.return_date, books.title  
            FROM borrows_histories 
            LEFT JOIN books ON books.id = borrows_histories.books_id 
            WHERE borrows_histories.borrower_name LIKE ? 
            OR borrows_histories.borrow_date LIKE ? 
            OR borrows_histories.return_date LIKE ? 
            OR books.title LIKE ? 
            LIMIT %d OFFSET %d
        ", $limit, $offset);

        
        $borrowsHistory = DB::select($sql, ["%$keyword%", "%$keyword%", "%$keyword%", "%$keyword%"]);

        
        return response()->json($borrowsHistory);
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
        DB::beginTransaction();

        try {
            $updated = DB::update("UPDATE books SET stock = stock - 1 WHERE id = ? AND stock > 0", [$request->books_id]);

            if ($updated) {
                DB::commit();
                //Stok berhasil dikurangi
            } else {
                DB::rollBack();
                //Stok tidak mencukupi atau ID tidak ditemukan
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal mengurangi stok ' . $e->getMessage()], 500);
        }

        // Mengambil User ID
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

        return response()->json(['message' => 'Peminjaman berhasil'], 200);
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

        // Cek stok buku
        $book = DB::selectOne("SELECT * FROM books WHERE id = ?", [$request->books_id]);
        if ($book->stock <= 0) {
            return response()->json(['message' => 'Stock buku habis'], 400);
        }

         // Kurangi stok buku
         DB::beginTransaction();

         try {

            //cek id peminjaman
            $borrow = DB::selectOne("SELECT * FROM borrows WHERE id = ?", [$id]);

            if($request->books_id != $borrow->books_id  ){

                $updated = DB::update("UPDATE books SET stock = stock - 1 WHERE id = ? AND stock > 0", [$request->books_id]);
 
                if ($updated) {
                    DB::commit();
                    //Stok berhasil dikurangi
                    
                } else {
                    DB::rollBack();
                    //Stok tidak mencukupi atau ID tidak ditemukan
                }

                

            }else if($request->books_id == $borrow->books_id ){

                 // Tambahkan kembali stok buku
                 DB::update("UPDATE books SET stock = stock + 1 WHERE id = ?", [$book->id]);

            }
            
         } catch (\Exception $e) {
             DB::rollBack();
             return response()->json(['message' => 'Gagal mengurangi stok ' . $e->getMessage()], 500);
         }

         DB::update("UPDATE borrows SET books_id = ?, borrower_name = ?, borrow_date = ?, updated_at = NOW() WHERE id = ?", [
            $request->books_id, $request->borrower_name, $request->borrow_date, $id
        ]);

        return response()->json(['message' => 'Peminjaman diperbarui'], 200);

    }

    // Fungsi mengembalikan buku & update stok
    public function returnBook($id)
    {
        $borrow = DB::selectOne("SELECT * FROM borrows WHERE id = ?", [$id]);

        if (!$borrow) {
            return response()->json(['message' => 'Peminjaman tidak ditemukan'], 404);
        }

        if($borrow->return_date == ""){
            // Update tanggal pengembalian
        DB::update("UPDATE borrows SET return_date = ? WHERE id = ?", [now(), $id]);

        // Tambahkan kembali stok buku
        DB::update("UPDATE books SET stock = stock + 1 WHERE id = ?", [$borrow->books_id]);

        return response()->json(['message' => 'Buku berhasil dikembalikan'], 200);

        }else{
            
            return response()->json(['message' => 'Buku sudah dikembalikan sebelumnya'], 500); 
        }
    }

    // Hapus data peminjaman
    public function destroy($id)
    {
        $borrow = DB::selectOne("SELECT * FROM borrows WHERE id = ?", [$id]);

        if (!$borrow) {
            return response()->json(['message' => 'Peminjaman tidak ditemukan'], 404);
        }

        // Tambahkan kembali stok buku
        DB::update("UPDATE books SET stock = stock + 1 WHERE id = ?", [$borrow->books_id]);

        // Hapus Peminjaman
        DB::delete("DELETE FROM borrows WHERE id = ?", [$id]);

        return response()->json(['message' => 'Peminjaman dihapus'], 200);
    }
}
