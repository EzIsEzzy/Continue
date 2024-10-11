<?php
namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    // Store a newly created like in storage (for post or comment)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'post_id' => 'nullable|exists:posts,id',
            'comment_id' => 'nullable|exists:comments,id',
        ]);

        // Ensure the user has not already liked the post or comment
        if ($request->post_id) {
            $existingLike = Like::where('user_id', Auth::id())->where('post_id', $request->post_id)->first();
        } else {
            $existingLike = Like::where('user_id', Auth::id())->where('comment_id', $request->comment_id)->first();
        }

        if (!$existingLike) {
            Like::create([
                'user_id' => Auth::id(),
                'post_id' => $validated['post_id'] ?? null,
                'comment_id' => $validated['comment_id'] ?? null,
            ]);

            return redirect()->back()->with('success', 'Like added!');
        }

        return redirect()->back()->with('error', 'You already liked this!');
    }

    // Remove the specified like from storage (for post or comment)
    public function destroy(Request $request)
    {
        $like = Like::where('user_id', Auth::id());

        if ($request->post_id) {
            $like = $like->where('post_id', $request->post_id);
        } elseif ($request->comment_id) {
            $like = $like->where('comment_id', $request->comment_id);
        }

        $like->delete();

        return redirect()->back()->with('success', 'Like removed!');
    }
}
