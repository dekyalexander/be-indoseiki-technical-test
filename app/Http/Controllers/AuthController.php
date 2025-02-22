<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Hash;

use Tymon\JWTAuth\Facades\JWTAuth;

use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Email Sudah Terdaftar, Periksa Kembali Email Dan Nama Yang Di Masukkan',
                'errors' => $validator->errors()
            ], 422);
        }

        $password = Hash::make($request->password);


        try {
            DB::insert("INSERT INTO users (name, email, password, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())", [
                $request->name,
                $request->email,
                $password
            ]);
    
            return response()->json(['message' => 'Berhasil Register!'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal Register!', 'error' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'email' => 'required|string|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email tidak boleh kosong!',
            'email.email' => 'Format email tidak valid!',
            'password.required' => 'Password tidak boleh kosong!',
            'password.min' => 'Password minimal 6 karakter!',
        ]);

        $user = DB::selectOne("SELECT * FROM users WHERE email = ?", [$request->email]);

        // dd($user);

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah!',
                'errors' => $validator->errors()
            ], 401);
        }

        $user = new User((array) $user);

        $user->exists = true;
        
        try {
            $token = JWTAuth::fromUser($user);
            $expires_in = JWTAuth::factory()->getTTL() * 60;
    
            return response()->json([
                'message' => 'Berhasil Login!',
                'token' => $token,
                'id' => $user->id,
                'username' => $user->name,
                'email' => $user->email,
                'expires_in' => $expires_in
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal membuat token!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout()
    {
        try{

            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Berhasil Logout!'], 200);

        }catch(\Exception $e){

            return response()->json(['message' => 'Gagal Logout!'], 500);

        }
    }

    public function refreshToken(){
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            return response()->json([
                'token' => $newToken,
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }
    }
}
