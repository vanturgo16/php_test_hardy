<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Mail\UserCreatedMail;
use App\Mail\AdminNewUserNotificationMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'name' => $request->name,
        ]);

        Mail::to($user->email)->send(new UserCreatedMail($user));
        Mail::to('admin@example.com')
            ->send(new AdminNewUserNotificationMail($user));

        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'created_at' => $user->created_at,
        ], 201);
    }

    public function index(Request $request)
    {
        $query = User::query()
            ->withCount('orders')
            ->where('active', true);

        if ($search = $request->input('search')) {
            $query->where(fn($q) => 
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
            );
        }

        $sortBy = $request->input('sortBy', 'created_at');
        $users = $query->orderBy($sortBy)->paginate(10);

        $authUser = $request->user();

        $users->getCollection()->transform(function ($user) use ($authUser) {
            $user->can_edit = app(\App\Policies\UserPolicy::class)->edit($authUser, $user);
            return $user;
        });

        return response()->json([
            'page' => $users->currentPage(),
            'users' => UserResource::collection($users->items())
        ]);
    }
}
