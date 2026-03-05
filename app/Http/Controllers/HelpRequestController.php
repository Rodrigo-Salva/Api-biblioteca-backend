<?php

namespace App\Http\Controllers;

use App\Models\HelpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HelpRequestController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if ($user->role === 'admin') {
            return HelpRequest::with('user')->latest()->paginate(10);
        }
        
        return HelpRequest::where('user_id', $user->id)->latest()->paginate(10);
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $helpRequest = HelpRequest::create([
            'user_id' => Auth::id(),
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'pending',
        ]);

        return response()->json($helpRequest, 201);
    }

    public function show(HelpRequest $helpRequest)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $helpRequest->user_id !== $user->id) {
            abort(403);
        }
        return $helpRequest->load('user');
    }

    public function update(Request $request, HelpRequest $helpRequest)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:pending,in_progress,resolved',
            'admin_response' => 'nullable|string',
        ]);

        $helpRequest->update($request->only(['status', 'admin_response']));

        return response()->json($helpRequest);
    }

    public function destroy(HelpRequest $helpRequest)
    {
        if (Auth::user()->role !== 'admin' && $helpRequest->user_id !== Auth::id()) {
            abort(403);
        }

        $helpRequest->delete();
        return response()->noContent();
    }
}
