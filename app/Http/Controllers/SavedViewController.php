<?php

namespace App\Http\Controllers;

use App\Models\SavedView;
use Illuminate\Http\Request;

class SavedViewController extends Controller
{
    public function index(Request $request)
    {
        $scope = $request->get('scope');
        
        $views = SavedView::when($scope, function ($q) use ($scope) {
                return $q->allow($scope);
            })
            ->visibleTo($request->user())
            ->orderByDesc('is_shared') // Shared first
            ->orderByRaw('user_id = ? DESC', [$request->user()->id]) // Then own
            ->orderBy('name')
            ->get();

        return view('saved_views.index', compact('scope', 'views'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'required|in:quotes,sales_orders,contracts,work_orders,customer_ledgers',
            'name' => 'required|string|max:60',
            'query' => 'required|json',
            'is_shared' => 'boolean',
        ]);

        SavedView::create([
            'scope' => $validated['scope'],
            'name' => $validated['name'],
            'query' => json_decode($validated['query'], true),
            'user_id' => $request->user()->id,
            'is_shared' => $request->has('is_shared'),
        ]);

        return back()->with('success', 'Görünüm kaydedildi.');
    }

    public function destroy(SavedView $savedView)
    {
        if ($savedView->user_id !== auth()->id()) {
            abort(403);
        }

        $savedView->delete();

        return back()->with('success', 'Görünüm silindi.');
    }
}
