<?php

namespace App\Http\Controllers;

use App\Fire\Explorer;
use App\Fire\FireAlert;
use App\Jobs\SendFireAlert;
use App\Models\Alert;
use App\Models\AlertSubscription;
use App\Models\Area;
use App\Models\SavedBox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        if (! self::allow("alert-test:{$s->id}")) {
            return back()->with('status', __('That is 5 test alerts today for this zone; the limit resets tomorrow.'));
        }

        SendFireAlert::dispatch($s, $alert, test: true);

        return back()->with('status', config('mail.default') === 'log'
            ? __('Test alert queued. This server has no mailer yet, so it is written to the mail log instead of sent.')
            : __('Test alert queued for :email. It arrives within a minute.', ['email' => $s->email]));
    }

    /** Emails a sample alert (the focus area's strongest week) to any address, 5 times a day per address. */
    public function sample(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $focus = Area::query()->where('slug', Area::FOCUS)->first();
        $top = $focus?->alerts()->orderByDesc('score')->first();

        if (! $focus || ! $top) {
            return back()->with('status', __('No unusual week has ever been found in this box, so there is nothing to send yet.'));
        }

        if (! self::allow('alert-sample:'.strtolower($data['email']))) {
            return back()->with('status', __('That is 5 sample alerts today for :email; the limit resets tomorrow.', ['email' => $data['email']]));
        }

        $s = new AlertSubscription([
            'name' => $focus->label(), 'email' => $data['email'], 'locale' => app()->getLocale(),
            'west' => $focus->west, 'south' => $focus->south, 'east' => $focus->east, 'north' => $focus->north,
        ]);
        $alert = ['week_start' => $top->week_start->toDateString(), 'observed' => (float) $top->observed, 'typical' => (float) $top->typical, 'score' => (float) $top->score, 'severity' => $top->severity, 'times' => $top->typical > 0 ? round($top->observed / $top->typical, 1) : null];

        SendFireAlert::dispatch($s, $alert, test: true);

        return back()->with('status', config('mail.default') === 'log'
            ? __('Sample alert queued. This server has no mailer yet, so it is written to the mail log instead of sent.')
            : __('Sample alert queued for :email. It arrives within a minute.', ['email' => $data['email']]));
    }

    /** At most 5 sends per key per day. */
    private static function allow(string $key): bool
    {
        $count = (int) Cache::get($key, 0);

        if ($count >= 5) {
            return false;
        }

        Cache::put($key, $count + 1, now()->endOfDay());

        return true;
    }

    public function update(Request $request, string $token): RedirectResponse
    {
        $data = $request->validate(['email_enabled' => ['sometimes', 'boolean'], 'phone_enabled' => ['sometimes', 'boolean'], 'phone' => ['sometimes', 'nullable', 'string', 'max:30']]);
        AlertSubscription::query()->where('token', $token)->firstOrFail()->update($data);

        return back();
    }

    public function destroy(string $token): RedirectResponse
    {
        AlertSubscription::query()->where('token', $token)->delete();

        return redirect()->route('subscriptions.create')->with('status', __('Alerts stopped.'));
    }
}
