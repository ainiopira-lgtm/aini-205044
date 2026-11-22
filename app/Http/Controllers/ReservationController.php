<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\ReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function __construct(private readonly ReservationService $reservations) {}

    public function store(Request $request, Book $book): RedirectResponse
    {
        $member = $request->user()->memberProfile;
        abort_unless($member, 403);

        $this->reservations->reserve($member->id, $book->id);

        return to_route('catalog.show', $book)->with('success', 'Reservasi berhasil ditambahkan ke antrean.');
    }
}
