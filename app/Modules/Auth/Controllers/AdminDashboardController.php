<?php

namespace Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $totalAdmins = User::where('role', 'admin')->count();
        $totalPustakawan = User::where('role', 'pustakawan')->count();
        $totalRegularUsers = User::where('role', 'user')->count();

        $recentUsers = User::latest()->limit(6)->get();

        return view('admin.dashboard', compact(
            'recentUsers',
            'totalUsers',
            'totalAdmins',
            'totalPustakawan',
            'totalRegularUsers'
        ));
    }
}
