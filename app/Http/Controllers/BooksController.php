<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Validator;

use App\Models\Books;

class BooksController extends Controller
{
    public function index(Request $request)
    {
        $page = intval($request->query('page', 1));
        $limit = intval($request->query('limit', 5));
        $offset = ($page - 1) * $limit;
        $keyword = $request->query('search', ''); 

        
        $sql = sprintf("
            SELECT id, title, author, description, year, stock   
            FROM books
            WHERE title LIKE ? 
            OR author LIKE ? 
            OR description LIKE ? 
            OR year LIKE ?
            OR stock LIKE ?  
            LIMIT %d OFFSET %d
        ", $limit, $offset);

        
        $books = DB::select($sql, ["%$keyword%", "%$keyword%", "%$keyword%", "%$keyword%", "%$keyword%"]);

        
        return response()->json($books, 200);
    }

    public function getBooks(){
        $books = DB::select("SELECT id, title FROM books");
        return response()->json(['data' => $books], 200);
    }

    public function store(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:30',
            'author' => 'required|string|max:30',
            'year' => 'required|integer|min:1000|max:'.date('Y'),
            'description' => 'required|string|max:50',
            'stock' => 'required|integer|max:500',
        ]);

        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data tidak boleh kosong periksa kembali',
                'errors' => $validator->errors()
            ], 422);
        }

        
        DB::insert("INSERT INTO books (title, author, description, year, stock, created_at) VALUES (?, ?, ?, ?, ?, NOW())", [
            $request->title, 
            $request->author, 
            $request->description,
            $request->year,
            $request->stock
        ]);

        return response()->json(['message' => 'Buku berhasil ditambahkan'], 201);
    }

    public function show($id)
    {
        $book = DB::selectOne("SELECT * FROM books WHERE id = ?", [$id]);

        if (!$book) {
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        }

        return response()->json($book, 200);
    }

    public function update(Request $request, $id)
    {
        
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'year' => 'required|integer|min:1000|max:'.date('Y'),
            'description' => 'required|string|max:50',
            'stock' => 'required|integer|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data tidak boleh kosong periksa kembali',
                'errors' => $validator->errors()
            ], 422);
        }

        
        $book = DB::selectOne("SELECT * FROM books WHERE id = ?", [$id]);
        if (!$book) {
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        }

        
        DB::update("UPDATE books SET title = ?, author = ?, description = ?, year = ?, stock = ?, updated_at = NOW() WHERE id = ?", [
            $request->title, 
            $request->author,
            $request->description, 
            $request->year,
            $request->stock, 
            $id
        ]);

        return response()->json(['message' => 'Buku berhasil diperbarui'], 200);
    }

    public function destroy($id)
    {
        
        $book = DB::selectOne("SELECT * FROM books WHERE id = ?", [$id]);
        if (!$book) {
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        }

       
        DB::delete("DELETE FROM books WHERE id = ?", [$id]);

        return response()->json(['message' => 'Buku berhasil dihapus'], 200);
    }
}
