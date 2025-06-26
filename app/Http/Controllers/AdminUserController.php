<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{   // Show all users (only for admin)
   public function index(Request $request)
   {
       return response()->json(User::all());
   }   // Update user role (only for admin)
   public function updateRole(Request $request, $id)
   {
       $validated = $request->validate([
           'role' => 'required|in:user,admin',
       ]);

       $user = User::find($id);
       if (!$user) {
           return response()->json(['message' => 'User not found'], 404);
       }

       $user->role = $validated['role'];
       $user->save();

       return response()->json(['message' => 'Role updated successfully', 'user' => $user]);
   }
}
