<?php

namespace App\Http\Controllers;

use App\Fire\Explorer;
use App\Models\SavedBox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SavedBoxController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'bbox' => ['required', 'array', 'size:4'],
            'bbox.*' => ['numeric'],
        ]);

        $bbox = array_map(fn ($v) => round((float) $v, 2), $data['bbox']);

        if ($problem = Explorer::reject($bbox)) {
            throw ValidationException::withMessages(['bbox' => __($problem)]);
        }

        abort_if($request->user()->savedBoxes()->count() >= 20, 422, 'Up to 20 saved boxes.');

        [$west, $south, $east, $north] = $bbox;
        $request->user()->savedBoxes()->create(['name' => $data['name']] + compact('west', 'south', 'east', 'north'));

        return back();
    }

    public function destroy(Request $request, SavedBox $box): RedirectResponse
    {
        abort_unless($box->user_id === $request->user()->id, 403);
        $box->delete();

        return back();
    }
}
