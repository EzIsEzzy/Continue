<?php
namespace App\Http\Controllers;

use App\Models\JobApplication;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class JobApplicationController extends Controller
{
    // Display all applications for a specific job (for the job poster)
    public function index(Job $job)
    {
        // Ensure the logged-in user is the job publisher
        if (Auth::id() != $job->publisher_id) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        // Fetch all applications for this job
        $applications = JobApplication::where('job_id', $job->id)->with('candidate')->get();

        return view('jobs.applications.index', compact('job', 'applications'));
    }

    // Show a specific application (for the job poster)
    public function show(JobApplication $jobApplication)
    {
        // Ensure the job belongs to the logged-in user
        if (Auth::id() != $jobApplication->job->publisher_id) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        return view('jobs.applications.show', compact('jobApplication'));
    }

    // Store a new job application (when a candidate applies to a job)
    public function store(Request $request, Job $job)
    {
        // Ensure the authenticated user is not the job poster (cannot apply to own job)
        if (Auth::id() == $job->publisher_id) {
            return redirect()->back()->with('error', 'You cannot apply to your own job.');
        }

        // Validate the uploaded CV file
        $validated = $request->validate([
            'uploaded_cv' => 'required|mimes:pdf|max:2048',
        ]);

        // Store the CV file in storage
        $cvPath = $request->file('uploaded_cv')->store('cvs');

        // Create a new job application
        JobApplication::create([
            'candidate_id' => Auth::id(),
            'job_id' => $job->id,
            'uploaded_cv' => $cvPath,
        ]);

        return redirect()->back()->with('success', 'Job application submitted successfully!');
    }

    // Edit a job application (optional - for candidates to edit their own applications)
    public function edit(JobApplication $jobApplication)
    {
        // Ensure the logged-in user is the candidate who applied
        if (Auth::id() != $jobApplication->candidate_id) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        return view('jobs.applications.edit', compact('jobApplication'));
    }

    // Update a job application (if a candidate wants to change the CV)
    public function update(Request $request, JobApplication $jobApplication)
    {
        // Ensure the logged-in user is the candidate who applied
        if (Auth::id() != $jobApplication->candidate_id) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        // Validate the uploaded CV file
        $validated = $request->validate([
            'uploaded_cv' => 'nullable|mimes:pdf|max:2048',
        ]);

        // Check if a new CV is uploaded
        if ($request->hasFile('uploaded_cv')) {
            // Delete the old CV from storage
            Storage::delete($jobApplication->uploaded_cv);

            // Store the new CV
            $cvPath = $request->file('uploaded_cv')->store('cvs');

            // Update the job application with the new CV
            $jobApplication->update([
                'uploaded_cv' => $cvPath,
            ]);
        }

        return redirect()->route('jobs.applications.index', $jobApplication->job_id)->with('success', 'Application updated successfully!');
    }

    // Delete a job application (for candidates to withdraw their application)
    public function destroy(JobApplication $jobApplication)
    {
        // Ensure the logged-in user is the candidate who applied
        if (Auth::id() != $jobApplication->candidate_id) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        // Delete the application and its CV from storage
        Storage::delete($jobApplication->uploaded_cv);
        $jobApplication->delete();

        return redirect()->route('jobs.index')->with('success', 'Job application withdrawn successfully!');
    }

    // Accept or reject a candidate's job application (for job posters)
    public function manage(Request $request, JobApplication $jobApplication)
    {
        // Ensure the logged-in user is the job poster
        if (Auth::id() != $jobApplication->job->publisher_id) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        // Validate the decision (accept or reject the application)
        $validated = $request->validate([
            'is_accepted' => 'required|boolean',
        ]);

        // Update the application status
        $jobApplication->update([
            'is_accepted' => $validated['is_accepted'],
        ]);
        return redirect()->back()->with('success', 'Application status updated successfully!');
    }
}
