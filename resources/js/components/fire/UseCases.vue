<script setup lang="ts">
import { t } from '@/lib/i18n';
import { Link } from '@inertiajs/vue3';
import { CalendarCheck, Mail, ShieldAlert } from 'lucide-vue-next';

type Row = [string, string];

const cases: { icon: typeof Mail; rows: Row[]; href: string; label: string }[] = [
    {
        icon: CalendarCheck,
        rows: [
            ['Who', 'Forest-fire unit of the Ministry of Environment (Dominican Republic).'],
            ['When', 'Every Monday morning, 10 minutes.'],
            [
                'Does in Continuum',
                'Opens the Dominican Republic page: next-week outlook, bulletin, and where last week burned against 26 years of the same week.',
            ],
            ['Decides', 'The readiness level of the week and where to position brigades.'],
            ['How it helps', 'The decision rests on a measured number with 7 days of lead time, not on an impression.'],
            [
                'Impact, measured',
                'Weeks with the level set on time; outlook skill (−19% error vs climatology in the Dominican Republic), re-measured yearly.',
            ],
        ],
        href: '/areas/dominican-republic',
        label: 'The Monday page',
    },
    {
        icon: Mail,
        rows: [
            ['Who', 'Provincial emergency operations (COE) and municipal brigades.'],
            ['When', 'The moment a week in their territory turns unusual: one email within a day of NASA data.'],
            ['Does in Continuum', 'Draws the province once and leaves an email address.'],
            ['Decides', 'Whether to move brigades or ask for support toward the flagged zone.'],
            ['How it helps', 'Early warning for the territory they answer for, without watching a dashboard.'],
            ['Impact, measured', 'Alerts sent, and the time between the alert and the response, counted from the first subscription.'],
        ],
        href: '/alerts/subscribe',
        label: 'Fire alerts',
    },
    {
        icon: ShieldAlert,
        rows: [
            ['Who', 'Planners, journalists and researchers, in the Dominican Republic or any of the five areas.'],
            ['When', 'After an event, or when writing a report.'],
            [
                'Does in Continuum',
                'Compares any dates with the same dates in every other year, downloads the cells that burned and the monthly record, reads the method and its tests.',
            ],
            ['Decides', 'What to budget, what to publish, what to study.'],
            ['How it helps', 'A defensible baseline: "the highest in 26 years" said with the data that proves it.'],
            ['Impact, measured', 'Reports and articles that cite the figure; downloads through the API.'],
        ],
        href: '/explore',
        label: 'Explore and download',
    },
];
</script>

<template>
    <section>
        <h2 class="text-xl font-semibold tracking-tight">{{ t('Who uses it, when, and for what') }}</h2>
        <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
            {{
                t(
                    'Priority function: tell the person responsible for a territory whether this week’s fire is unusual and what next week looks like, early enough to act. Three users execute it; each row below says who, when, what they do, what they decide, how it helps and how the impact is counted.',
                )
            }}
        </p>
        <div class="mt-5 grid gap-5 lg:grid-cols-3">
            <article v-for="c in cases" :key="c.href" class="flex flex-col rounded-md border p-5">
                <component :is="c.icon" class="size-5 text-orange-600" />
                <dl class="mt-3 space-y-2 text-sm">
                    <div v-for="[label, text] in c.rows" :key="label">
                        <dt class="text-xs text-muted-foreground">{{ t(label) }}</dt>
                        <dd :class="label === 'Decides' || label === 'How it helps' ? '' : 'text-muted-foreground'">{{ t(text) }}</dd>
                    </div>
                </dl>
                <Link :href="c.href" class="mt-auto pt-4 text-sm underline-offset-4 hover:underline">{{ t(c.label) }} →</Link>
            </article>
        </div>
    </section>
</template>
