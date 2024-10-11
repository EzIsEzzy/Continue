<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobController extends Controller
{
    // Display a listing of jobs (All jobs or My jobs for logged-in user)
    public function index()
    {
        // Fetch all jobs or the user's own job postings
        $jobs = Job::with('publisher')->orderBy('created_at', 'desc')->get();

        return view('jobs.index', compact('jobs'));
    }
    // Show the form for creating a new job post
    public function create()
    {
        return view('jobs.create');
    }

    // Store a newly created job post in the database
    public function store(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'salary' => 'nullable|numeric',
            'location' => 'nullable|string|max:255',
        ]);

        // Create a new job post associated with the authenticated user
        Job::create([
            'publisher_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'salary' => $validated['salary'],
            'location' => $validated['location'],
        ]);

        return redirect()->route('jobs.index')->with('success', 'Job posted successfully!');
    }

    // Display the specified job post
    public function show(Job $job)
    {
        // Show job details along with applicants if the logged-in user is the publisher
        if (Auth::id() === $job->publisher_id) {
            $applicants = JobApplication::where('appliedJob', $job->id)->with('candidate')->get();
            return view('jobs.show', compact('job', 'applicants'));
        }

        return view('jobs.show', compact('job'));
    }

    // Show the form for editing the specified job post
    public function edit(Job $job)
    {
        // Ensure the job belongs to the authenticated user
        if (Auth::id() != $job->publisher_id) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        return view('jobs.edit', compact('job'));
    }

    // Update the specified job post in the database
    public function update(Request $request, Job $job)
    {
        // Ensure the job belongs to the authenticated user
        if (Auth::id() != $job->publisher_id) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        // Validate the request data
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'salary' => 'nullable|numeric',
            'location' => 'nullable|string|max:255',
        ]);

        // Update the job post's data
        $job->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'salary' => $validated['salary'],
            'location' => $validated['location'],
        ]);

        return redirect()->route('jobs.index')->with('success', 'Job updated successfully!');
    }

    // Remove the specified job post from the database
    public function destroy(Job $job)
    {
        // Ensure the job belongs to the authenticated user
        if (Auth::id() != $job->publisher_id) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        // Delete the job post
        $job->delete();

        return redirect()->route('jobs.index')->with('success', 'Job deleted successfully!');
    }

    // Apply for a job by a candidate
    public function apply(Request $request, Job $job)
    {
        // Validate the CV file
        $validated = $request->validate([
            'uploaded_CV' => 'required|mimes:pdf|max:2048',
        ]);

        // Save the CV file
        $cvPath = $request->file('uploaded_CV')->store('cvs');

        // Create a new job application record
        JobApplication::create([
            'candidateID' => Auth::id(),
            'appliedJob' => $job->id,
            'uploaded_CV' => $cvPath,
        ]);

        return redirect()->back()->with('success', 'You have applied for this job!');
    }

    // Accept or reject a candidate's job application (for job publishers)
    public function manageApplication(Request $request, JobApplication $JobApplication)
    {
        // Ensure the user owns the job
        if (Auth::id() != $JobApplication->job->publisher_id) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        // Validate the action (accepted/rejected)
        $validated = $request->validate([
            'is_accepted' => 'required|boolean',
        ]);

        // Update the application status
        $JobApplication->update([
            'is_accepted' => $validated['is_accepted'],
        ]);

        return redirect()->back()->with('success', 'Application status updated!');
    }
}
