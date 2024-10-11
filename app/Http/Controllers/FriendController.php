<?php
namespace App\Http\Controllers;

use App\Models\Friend;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FriendController extends Controller
{
    // Display the list of friends for the authenticated user
    public function index()
    {
        $user = Auth::user();

        // Retrieve all friends where the authenticated user is involved
        $friends = Friend::where('user_id', $user->id)
                        ->orWhere('friend_id', $user->id)
                        ->with(['user', 'friend'])
                        ->get();

        return view('friends.index', compact('friends'));
    }

    // Send a friend request to another user
    public function store(Request $request)
    {
        $request->validate([
            'friend_id' => 'required|exists:users,id',
        ]);

        $user = Auth::user();
        $friendId = $request->input('friend_id');

        // Check if the user is trying to add themselves as a friend
        if ($user->id == $friendId) {
            return redirect()->back()->with('error', 'You cannot add yourself as a friend.');
        }

        // Check if the friend request already exists
        $existingFriend = Friend::where(function($query) use ($user, $friendId) {
            $query->where('user_id', $user->id)
                  ->where('friend_id', $friendId);
        })->orWhere(function($query) use ($user, $friendId) {
            $query->where('user_id', $friendId)
                  ->where('friend_id', $user->id);
        })->first();

        if ($existingFriend) {
            return redirect()->back()->with('error', 'You are already friends or the request is pending.');
        }

        // Create a new friend request (pending status)
        Friend::create([
            'user_id' => $user->id,
            'friend_id' => $friendId,
        ]);

        return redirect()->back()->with('success', 'Friend request sent!');
    }

    // Accept a friend request (only the recipient can accept)
    public function update(Request $request, Friend $friend)
    {
        // Ensure the logged-in user is the one receiving the friend request
        if ($friend->friend_id != Auth::id()) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        // Mark the friend request as accepted
        $friend->update([
            'accepted_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Friend request accepted!');
    }

    // Delete a friend (remove friend or decline request)
    public function destroy(Friend $friend)
    {
        // Check if the authenticated user is part of the friendship
        if ($friend->user_id != Auth::id() && $friend->friend_id != Auth::id()) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        // Delete the friendship
        $friend->delete();

        return redirect()->back()->with('success', 'Friend removed successfully.');
    }
}
