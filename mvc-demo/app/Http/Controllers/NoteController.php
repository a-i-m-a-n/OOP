<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function index()
    {
        $notes = Note::latest()->get();

        return view('notes', compact('notes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|max:255'
        ]);

        Note::create([
            'title' => $request->title
        ]);

        return redirect('/notes')->with('success', 'Note added successfully!');
    }
}