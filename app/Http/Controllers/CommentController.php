<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    // Display all comments for a post
    public function index($postId)
    {
        $comments = Comment::where('post_id', $postId)->get();
        return view('comments.index', compact('comments'));
    }

    // Show the form for creating a new comment
    public function create($postId)
    {
        return view('comments.create', ['post_id' => $postId]);
    }

    // Store a newly created comment in storage
    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|max:255',
            'post_id' => 'required|exists:posts,id',
        ]);

        Comment::create([
            'user_id' => Auth::id(),
            'post_id' => $validated['post_id'],
            'content' => $validated['content'],
        ]);

        return redirect()->route('posts.show', $validated['post_id'])->with('success', 'Comment added successfully!');
    }

    // Show the form for editing the specified comment
    public function edit(Comment $comment)
    {
        return view('comments.edit', compact('comment'));
    }

    // Update the specified comment in storage
    public function update(Request $request, Comment $comment)
    {
        $validated = $request->validate([
            'content' => 'required|max:255',
        ]);

        $comment->update([
            'content' => $validated['content'],
        ]);

        return redirect()->route('posts.show', $comment->post_id)->with('success', 'Comment updated successfully!');
    }

    // Remove the specified comment from storage
    public function destroy(Comment $comment)
    {
        $comment->delete();

        return redirect()->route('posts.show', $comment->post_id)->with('success', 'Comment deleted successfully!');
    }
}
