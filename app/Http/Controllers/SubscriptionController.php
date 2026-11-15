<?php

namespace App\Http\Controllers;

use App\Fire\Explorer;
use App\Fire\FireAlert;
use App\Jobs\SendFireAlert;
use App\Models\AlertSubscription;
use App\Models\Area;
use App\Models\SavedBox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Subscribe', [
            'areas' => Area::query()->ordered()->get()->map(fn (Area $a) => ['slug' => $a->slug, 'name' => $a->label(), 'bbox' => [$a->west, $a->south, $a->east, $a->north]])->all(),
            'places' => Explorer::places(),
            'boxes' => $request->user()?->savedBoxes()->latest()->get()->map(fn (SavedBox $b) => $b->toPlace())->all() ?? [],
            'mine' => $request->user() ? AlertSubscription::query()->where('user_id', $request->user()->id)->latest()->get()->map->toArray()->all() : [],
            'mailer' => config('mail.default'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'bbox' => ['required', 'array', 'size:4'],
            'bbox.*' => ['numeric'],
        ]);

        $bbox = array_map(fn ($v) => round((float) $v, 2), $data['bbox']);

        if ($problem = Explorer::reject($bbox)) {
            throw ValidationException::withMessages(['bbox' => __($problem)]);
        }

        $s = AlertSubscription::query()->create([
            'user_id' => $request->user()?->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'locale' => app()->getLocale(),
            'west' => $bbox[0], 'south' => $bbox[1], 'east' => $bbox[2], 'north' => $bbox[3],
        ]);

        return redirect()->route('subscriptions.show', $s->token);
    }

    public function show(string $token, FireAlert $alerts): Response
    {
        $s = AlertSubscription::query()->where('token', $token)->firstOrFail();
        $strongest = $alerts->strongest($s);

        return Inertia::render('Subscription', [
            'subscription' => $s->toArray(),
            'strongest' => $strongest,
            'current' => $alerts->current($s),
            'preview' => $strongest ? FireAlert::text($s, $strongest) : null,
            'mailer' => config('mail.default'),
        ]);
    }

    /** Sends the strongest week of the box right now, marked as a test. */
    public function test(string $token, FireAlert $alerts): RedirectResponse
    {
        $s = AlertSubscription::query()->where('token', $token)->firstOrFail();
        $alert = $alerts->strongest($s);

        if ($alert === null) {
            return back()->with('status', __('No unusual week has ever been found in this box, so there is nothing to send yet.'));
        }

        SendFireAlert::dispatch($s, $alert, test: true);

        return back()->with('status', config('mail.default') === 'log'
            ? __('Test alert queued. This server has no mailer yet, so it is written to the mail log instead of sent.')
            : __('Test alert queued for :email. It arrives within a minute.', ['email' => $s->email]));
    }

    public function destroy(string $token): RedirectResponse
    {
        AlertSubscription::query()->where('token', $token)->delete();

        return redirect()->route('subscriptions.create')->with('status', __('Alerts stopped.'));
    }
}
