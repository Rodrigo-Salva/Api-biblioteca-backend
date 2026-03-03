<?php

namespace App\Http\Controllers;

use App\Models\HomeBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class BannerController extends Controller
{
    public function index()
    {
        return HomeBanner::where('is_active', true)
            ->orderBy('order', 'asc')
            ->get();
    }

    public function all()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return HomeBanner::orderBy('order', 'asc')->get();
    }

    public function store(Request $request)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'image' => 'required|image|max:2048',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'link' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
        ]);

        $path = $request->file('image')->store('banners', 'public');

        $banner = HomeBanner::create([
            'image_path' => $path,
            'title' => $request->title,
            'description' => $request->description,
            'link' => $request->link,
            'order' => $request->order ?? 0,
            'is_active' => true,
        ]);

        return response()->json($banner, 201);
    }

    public function update(Request $request, HomeBanner $banner)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'link' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $banner->update($request->only(['title', 'description', 'link', 'order', 'is_active']));

        return response()->json($banner);
    }

    public function destroy(HomeBanner $banner)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        Storage::disk('public')->delete($banner->image_path);
        $banner->delete();

        return response()->json(['message' => 'Banner deleted']);
    }
}
