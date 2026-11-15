<script setup lang="ts">
import WeekMap from '@/components/fire/WeekMap.vue';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { number, t } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';

defineProps<{ bbox: number[]; week: string | null; times: number | null; focusName: string }>();

const cards = [
    {
        q: 'How do you get 89% less error?',
        a: 'Three steps and a test: a 1 km grid, one count per sensor, a monthly MODIS ÷ VIIRS ratio; proven on 1,352 held-out days.',
        href: '/questions#how',
    },
    {
        q: 'Compared with what?',
        a: 'Always against a named baseline: the naive merge, the same week in the other 26 years, climatology and persistence.',
        href: '/questions#baseline',
    },
    {
        q: 'What decision does it serve, and who takes it?',
        a: 'Three immediate ones: raise the readiness level and move brigades on Monday; dispatch to the flagged zone within 24 hours of the alert; suspend burn permits today when the season runs above normal.',
        href: '/questions#who',
    },
];

const more = [
    ['/resume', 'Live map and numbers'],
    ['/areas/dominican-republic', 'Dominican Republic'],
    ['/judges', 'For judges'],
    ['/ai', 'AI analysis'],
    ['/questions', 'All questions'],
];
</script>

<template>
    <Head :title="t('Was this fire unusual?')" />
    <SiteLayout>
        <div class="relative overflow-hidden rounded-md bg-neutral-950">
            <WeekMap :bbox="bbox" :week="week" :times="times" height="h-[40rem]" :chrome="false" />
            <div class="pointer-events-none absolute inset-0 bg-neutral-950/35" />
            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-3/4 bg-gradient-to-t from-neutral-950 via-neutral-950/70 to-transparent" />
            <div class="absolute inset-x-0 bottom-0 p-6 text-neutral-100 md:p-10">
                <p class="text-sm text-orange-400">
                    {{ t(':area · :n times a normal week, April 2015', { area: focusName, n: number(times ?? 0, 1) }) }}
                </p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight md:text-4xl">{{ t('Was this fire unusual?') }}</h1>
                <p class="mt-2 max-w-2xl text-neutral-300">
                    {{
                        t(
                            '26 years of NASA fire detections on one 1 km grid and one scale, so a week can be compared with the same week in every other year.',
                        )
                    }}
                </p>
                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    <Link
                        v-for="c in cards"
                        :key="c.href"
                        :href="c.href"
                        class="group rounded-md border border-white/10 bg-neutral-900/85 p-5 backdrop-blur transition hover:bg-neutral-900"
                    >
                        <h2 class="font-semibold">{{ t(c.q) }}</h2>
                        <p class="mt-2 text-sm leading-relaxed text-neutral-300">{{ t(c.a) }}</p>
                        <span class="mt-3 inline-flex items-center gap-1 text-sm text-orange-400"
                            >{{ t('Read the answer') }} <ArrowRight class="size-4 transition group-hover:translate-x-0.5"
                        /></span>
                    </Link>
                </div>
            </div>
        </div>

        <nav class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm">
            <Link
                v-for="[href, label] in more"
                :key="href"
                :href="href"
                class="text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                >{{ t(label) }} →</Link
            >
        </nav>
    </SiteLayout>
</template>
