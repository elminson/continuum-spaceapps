<script setup lang="ts">
import { t } from '@/lib/i18n';
import { Link } from '@inertiajs/vue3';
import { Flame, Siren, TriangleAlert } from 'lucide-vue-next';

type Row = [string, string];

const cases: { icon: typeof Siren; decision: string; rows: Row[]; href: string; label: string }[] = [
    {
        icon: Siren,
        decision: 'Raise the readiness level and move brigades before the week starts',
        rows: [
            ['Who', 'Forest-fire unit of the Ministry of Environment (Dominican Republic).'],
            ['When', 'Monday morning, for the week that starts.'],
            ['Trigger', 'Next-week outlook at ×2 of normal or more, or last week flagged high or extreme.'],
            [
                'Action in the next hours',
                'Set the week’s readiness level; pre-position brigades in the provinces where last week burned (the map shows the cells); brief the regional offices.',
            ],
            ['Impact, measured', 'Weeks with the level set 7 days ahead; outlook skill −19% error vs climatology, re-measured yearly.'],
        ],
        href: '/areas/dominican-republic#outlook',
        label: 'Outlook and map',
    },
    {
        icon: TriangleAlert,
        decision: 'Dispatch to the flagged zone within 24 hours of the alert',
        rows: [
            ['Who', 'Provincial emergency operations (COE), municipal brigades.'],
            ['When', 'The afternoon the email arrives: it is sent within a day of NASA data when a week in their territory turns unusual.'],
            ['Trigger', 'Alert email: ×N normal, rank among 26 years, map of the burning 1 km cells.'],
            [
                'Action in the next hours',
                'Send a brigade to the cells on the map; restrict access to the zone; warn the municipalities around it; ask the Ministry for support if the level is extreme.',
            ],
            ['Impact, measured', 'Alerts sent; hours between alert and dispatch; counted from the first subscription.'],
        ],
        href: '/alerts/subscribe',
        label: 'Fire alerts',
    },
    {
        icon: Flame,
        decision: 'Suspend burn permits and issue a public warning today',
        rows: [
            ['Who', 'Ministry of Environment and municipalities, during the fire season (Dominican Republic: March–April).'],
            ['When', 'Any day the fire season is on and the current week is running above normal.'],
            ['Trigger', '"In fire season now" plus this week at ×2 of normal or more on the area page.'],
            [
                'Action in the next hours',
                'Suspend agricultural burn permits in the affected provinces; issue the public warning; lift both when the week returns to normal.',
            ],
            ['Impact, measured', 'Days of suspension per season; share of the year’s fire inside the declared window (44% today).'],
        ],
        href: '/areas/dominican-republic#season',
        label: 'Fire season',
    },
];
</script>

<template>
    <section>
        <h2 class="text-xl font-semibold tracking-tight">{{ t('Three decisions it is built for') }}</h2>
        <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
            {{
                t(
                    'Not a news bulletin: each card is an operational decision, with who takes it, what triggers it, what they do in the next hours and how its effect is counted. Planners, journalists and researchers use the same numbers afterwards, to budget, publish and study with a 26-year baseline.',
                )
            }}
        </p>
        <div class="mt-5 grid gap-5 lg:grid-cols-3">
            <article v-for="c in cases" :key="c.href" class="flex flex-col rounded-md border p-5">
                <component :is="c.icon" class="size-5 text-orange-600" />
                <h3 class="mt-3 font-semibold leading-snug">{{ t(c.decision) }}</h3>
                <dl class="mt-3 space-y-2 text-sm">
                    <div v-for="[label, text] in c.rows" :key="label">
                        <dt class="text-xs text-muted-foreground">{{ t(label) }}</dt>
                        <dd :class="label === 'Action in the next hours' || label === 'Trigger' ? '' : 'text-muted-foreground'">{{ t(text) }}</dd>
                    </div>
                </dl>
                <Link :href="c.href" class="mt-auto pt-4 text-sm underline-offset-4 hover:underline">{{ t(c.label) }} →</Link>
            </article>
        </div>
    </section>
</template>
