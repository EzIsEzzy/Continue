<?php
namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    // Display a listing of posts
    public function index()
    {
        // Fetch all posts with user and comments relationship
        $posts = Post::with(['user', 'comments', 'likes'])->orderBy('created_at', 'desc')->get();
        return view('posts.index', compact('posts'));
    }

    // Show the form for creating a new post
    public function create()
    {
        return view('posts.create');
    }

    // Store a newly created post in the database
    public function store(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            'content' => 'required|max:255',
        ]);

        // Create a new post associated with the authenticated user
        Post::create([
            'user_id' => Auth::id(),
            'content' => $validated['content'],
        ]);

        return redirect()->route('posts.index')->with('success', 'Post created successfully!');
    }

    // Display the specified post
    public function show(Post $post)
    {
        return view('posts.show', compact('post'));
    }

    // Show the form for editing the specified post
    public function edit(Post $post)
    {
        // Ensure the post belongs to the authenticated user
        if (Auth::id() != $post->user_id) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        return view('posts.edit', compact('post'));
    }

    // Update the specified post in the database
    public function update(Request $request, Post $post)
    {
        // Ensure the post belongs to the authenticated user
        if (Auth::id() != $post->user_id) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        // Validate the request data
        $validated = $request->validate([
            'content' => 'required|max:255',
        ]);

        // Update the post's content
        $post->update([
            'content' => $validated['content'],
        ]);

        return redirect()->route('posts.index')->with('success', 'Post updated successfully!');
    }

    // Remove the specified post from the database
    public function destroy(Post $post)
    {
        // Ensure the post belongs to the authenticated user
        if (Auth::id() != $post->user_id) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        // Delete the post
        $post->delete();

        return redirect()->route('posts.index')->with('success', 'Post deleted successfully!');
    }
}
