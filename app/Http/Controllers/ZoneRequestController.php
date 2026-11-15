<?php

namespace App\Http\Controllers;

use App\Fire\Explorer;
use App\Models\ZoneRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ZoneRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255'],
            'bbox' => ['required', 'array', 'size:4'],
            'bbox.*' => ['numeric'],
        ]);

        $bbox = array_map(fn ($v) => round((float) $v, 2), $data['bbox']);

        if ($bbox[0] >= $bbox[2] || $bbox[1] >= $bbox[3]) {
            throw ValidationException::withMessages(['bbox' => __('The box is empty.')]);
        }

        if (Explorer::reject($bbox) === null) {
            throw ValidationException::withMessages(['bbox' => __('This box is already covered: analyze it instead.')]);
        }

        ZoneRequest::query()->create(['name' => $data['name'], 'email' => $data['email'], 'west' => $bbox[0], 'south' => $bbox[1], 'east' => $bbox[2], 'north' => $bbox[3]]);
        $estimate = ZoneRequest::estimate($bbox);

        return back()->with('status', __('Zone requested. NASA prepares its archive in :nasa, loading and harmonizing it here takes about :m minutes, and you get an email at :email when it is ready.', [
            'nasa' => __($estimate['nasa']), 'm' => $estimate['ours_minutes'], 'email' => $data['email'],
        ]));
    }
}
