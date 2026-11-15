<script setup lang="ts">
import { date, number, percent, r, t } from '@/lib/i18n';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

type Window = { temperature: number | null; humidity: number | null; rain: number | null; wind: number | null; days: number };

export type ForecastData = {
    week_start: string;
    expected: number;
    low: number;
    high: number;
    climatology: number;
    times_normal: number | null;
    last_week: { start: string; value: number };
    weather_through: string | null;
    weather: { last_7_days: Window | null; last_28_days: Window | null } | null;
    validation: {
        weeks: number;
        from: number;
        to: number;
        mae: number;
        mae_climatology: number;
        mae_persistence: number;
        skill_vs_climatology: number;
        skill_vs_persistence: number;
        correlation: number;
    };
    weights: Record<string, number>;
};

const props = defineProps<{ forecast: ForecastData }>();

const range = computed(() => {
    const c = props.forecast.climatology;
    return c > 0 ? [props.forecast.low / c, props.forecast.high / c] : null;
});
const skill = computed(() => props.forecast.validation.skill_vs_climatology);
const useful = computed(() => skill.value > 0.05);
const drivers = computed(() =>
    Object.entries(props.forecast.weights)
        .sort((a, b) => Math.abs(b[1]) - Math.abs(a[1]))
        .slice(0, 3)
        .map(([k]) => t(LABELS[k] ?? k)),
);
const LABELS: Record<string, string> = {
    fire_last_week: 'last week’s fire',
    fire_2_weeks_ago: 'fire two weeks ago',
    fire_3_weeks_ago: 'fire three weeks ago',
    fire_last_4_weeks: 'the last four weeks of fire',
    climatology_same_week: 'the same week in other years',
    temperature_7d: 'temperature of the last 7 days',
    humidity_7d: 'humidity of the last 7 days',
    wind_7d: 'wind of the last 7 days',
    rain_7d: 'rain of the last 7 days',
    rain_28d: 'rain of the last 28 days',
    temperature_28d: 'temperature of the last 28 days',
    humidity_28d: 'humidity of the last 28 days',
    season_sin: 'time of year',
    season_cos: 'time of year',
};
</script>

<template>
    <section id="outlook" class="scroll-mt-6 rounded-md border p-5">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="text-lg font-semibold">{{ t('Next week outlook') }}</h2>
            <span class="text-sm text-muted-foreground">{{ t('Week of :date', { date: date(forecast.week_start) }) }}</span>
        </div>

        <div class="mt-3 flex flex-wrap items-baseline gap-x-3 gap-y-1 tabular-nums">
            <span class="text-4xl font-semibold" :class="useful ? 'text-orange-600' : ''">
                <template v-if="forecast.times_normal !== null">×{{ number(forecast.times_normal, 1) }}</template>
                <template v-else>{{ number(forecast.expected) }}</template>
            </span>
            <span class="text-muted-foreground">
                <template v-if="forecast.times_normal !== null">{{ t('of normal expected') }}</template>
                <template v-else>{{ t('burning cell-days expected') }}</template>
                <template v-if="range"> · {{ t('range ×:a–×:b', { a: number(range[0], 1), b: number(range[1], 1) }) }}</template>
            </span>
        </div>
        <p class="mt-1 text-sm text-muted-foreground">
            {{
                t(':n burning 1 km cell-days expected (:low–:high); typical for this week: :c. Last week: :last.', {
                    n: number(forecast.expected),
                    low: number(forecast.low),
                    high: number(forecast.high),
                    c: number(forecast.climatology),
                    last: number(forecast.last_week.value),
                })
            }}
        </p>

        <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3 text-sm sm:grid-cols-4">
            <div>
                <dt class="text-muted-foreground">{{ t('Skill vs climatology') }}</dt>
                <dd class="font-medium tabular-nums" :class="useful ? '' : 'text-red-600'">
                    {{ skill >= 0 ? '−' : '+' }}{{ percent(Math.abs(skill)) }} {{ t('error') }}
                </dd>
            </div>
            <div>
                <dt class="text-muted-foreground">{{ t('Skill vs persistence') }}</dt>
                <dd class="font-medium tabular-nums">
                    {{ forecast.validation.skill_vs_persistence >= 0 ? '−' : '+' }}{{ percent(Math.abs(forecast.validation.skill_vs_persistence)) }}
                    {{ t('error') }}
                </dd>
            </div>
            <div>
                <dt class="text-muted-foreground">{{ t('Correlation') }}</dt>
                <dd class="font-medium tabular-nums">{{ r(forecast.validation.correlation) }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground">{{ t('Tested on') }}</dt>
                <dd class="font-medium tabular-nums">
                    {{
                        t(':n weeks, :from–:to', {
                            n: number(forecast.validation.weeks),
                            from: String(forecast.validation.from),
                            to: String(forecast.validation.to),
                        })
                    }}
                </dd>
            </div>
        </dl>

        <dl v-if="forecast.weather" class="mt-4 grid gap-x-6 gap-y-2 border-t pt-4 text-sm sm:grid-cols-2">
            <template
                v-for="[label, w] in [
                    [t('Weather, last 7 days'), forecast.weather.last_7_days],
                    [t('Weather, last 28 days'), forecast.weather.last_28_days],
                ]"
                :key="label"
            >
                <div v-if="w">
                    <dt class="text-muted-foreground">{{ label }}</dt>
                    <dd class="tabular-nums">
                        {{ number(w.temperature, 1) }} °C · {{ number(w.humidity) }}% {{ t('humidity') }} · {{ number(w.rain) }} mm {{ t('rain') }} ·
                        {{ number(w.wind, 1) }} m/s {{ t('wind') }}
                    </dd>
                </div>
            </template>
        </dl>

        <p class="mt-4 text-xs text-muted-foreground">
            <template v-if="!useful"
                >{{ t('For this area the model is not yet better than the same-week climatology, so read the outlook as the climatology.') }}
            </template>
            {{
                t(
                    'Ridge regression per area on this harmonized record and NASA POWER weather (through :date), validated year by year since :from. Strongest drivers here: :d.',
                    {
                        date: forecast.weather_through ? date(forecast.weather_through) : '—',
                        from: String(forecast.validation.from),
                        d: drivers.join(', '),
                    },
                )
            }}
            <Link href="/method#forecast" class="underline-offset-4 hover:underline">{{ t('Method') }} →</Link>
        </p>
    </section>
</template>
