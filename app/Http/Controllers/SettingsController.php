<?php

namespace App\Http\Controllers;

use App\Services\LibrarySettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly LibrarySettingsService $settings) {}

    public function edit(): View
    {
        return view('settings.edit', [
            'settings' => $this->settings->circulationValues(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $values = $request->validate([
            'loan_duration_days' => ['required', 'integer', 'min:1'],
            'max_active_loans' => ['required', 'integer', 'min:1'],
            'max_renewals' => ['required', 'integer', 'min:0'],
            'fine_per_day' => ['required', 'integer', 'min:0'],
        ]);
        $this->settings->updateCirculationValues($values);

        return to_route('admin.settings.edit')->with('success', 'Pengaturan sirkulasi berhasil disimpan.');
    }
}
