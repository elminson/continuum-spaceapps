<script setup lang="ts">
import { t } from '@/lib/i18n';
import { Link } from '@inertiajs/vue3';
import { CalendarCheck, Mail, ShieldAlert } from 'lucide-vue-next';

const cases = [
    {
        icon: CalendarCheck,
        who: 'Forest-fire unit of the Ministry of Environment (Dominican Republic)',
        when: 'Monday morning, 10 minutes',
        does: 'Opens the Dominican Republic page: the next-week outlook, the bulletin and where last week burned, set against 26 years of the same week.',
        gets: 'Decides the readiness level of the week and where to position brigades with a measured number, not an impression.',
        href: '/areas/dominican-republic',
        label: 'The Monday page',
    },
    {
        icon: Mail,
        who: 'Provincial emergency operations (COE) and municipal brigades',
        when: 'Whenever it happens',
        does: 'Draw their province once and leave an email. When a week there turns unusual against the other years, one email arrives with the numbers and the map.',
        gets: 'Early warning for the territory they answer for, without watching a dashboard.',
        href: '/alerts/subscribe',
        label: 'Fire alerts',
    },
    {
        icon: ShieldAlert,
        who: 'Planners, journalists and researchers, in the Dominican Republic or anywhere in the five areas',
        when: 'After an event, or for a report',
        does: 'Compare any dates with the same dates in every other year, download the cells that burned and the monthly record, and read the method and its tests.',
        gets: 'A defensible baseline: "the highest in 26 years" said with the data that proves it.',
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
                    'One priority job: tell the people responsible for a territory whether this week’s fire is unusual and what next week looks like, early enough to act.',
                )
            }}
        </p>
        <div class="mt-5 grid gap-5 md:grid-cols-3">
            <article v-for="c in cases" :key="c.href" class="flex flex-col rounded-md border p-5">
                <component :is="c.icon" class="size-5 text-orange-600" />
                <h3 class="mt-3 font-medium">{{ t(c.who) }}</h3>
                <p class="mt-0.5 text-xs text-muted-foreground">{{ t(c.when) }}</p>
                <p class="mt-3 text-sm text-muted-foreground">{{ t(c.does) }}</p>
                <p class="mt-2 text-sm">{{ t(c.gets) }}</p>
                <Link :href="c.href" class="mt-auto pt-4 text-sm underline-offset-4 hover:underline">{{ t(c.label) }} →</Link>
            </article>
        </div>
    </section>
</template>
