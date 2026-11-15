<script setup lang="ts">
import SituationCard from '@/components/fire/SituationCard.vue';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { date, t } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/vue3';
import { Calculator, CheckCheck, ShieldCheck, Sparkles } from 'lucide-vue-next';

type Text = { text: string; source: 'ai' | 'template'; model: string | null };
type AreaTexts = { slug: string; name: string; top_week: string | null; top_times: number | null; top: Text | null; latest: Text };

defineProps<{
    situation: Text;
    areas: AreaTexts[];
    writers: { primary: string; fallback: string };
}>();

const rules = [
    {
        icon: Calculator,
        title: 'Statistics decide',
        body: 'Which week is unusual, how unusual, and its rank among 26 years come from the median and MAD of the same week in every other year. The model never flags or changes a number.',
    },
    {
        icon: Sparkles,
        title: 'The model only writes',
        body: 'It receives the computed facts as JSON and returns two to five plain sentences in English or Spanish. Nothing else goes in: no raw data, no web, no memory.',
    },
    {
        icon: CheckCheck,
        title: 'Every number is checked',
        body: 'Each figure in the text must be one of the facts, written exactly. One unknown number and a fixed template answers instead, labelled as such.',
    },
    {
        icon: ShieldCheck,
        title: 'Two writers, one rule',
        body: 'Claude through the Anthropic API, or Gemma 4 on our own server; each is the fallback for the other, and every text says which one wrote it.',
    },
];
</script>

<template>
    <Head :title="t('AI analysis')" />
    <SiteLayout>
        <h1 class="text-2xl font-semibold tracking-tight">{{ t('AI analysis') }}</h1>
        <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
            {{
                t(
                    'Everything the AI writes on this site, in one place: the reading across areas and the bulletin of each area, each from checked numbers and labelled with the model that wrote it.',
                )
            }}
        </p>

        <SituationCard class="mt-6" :text="situation.text" :source="situation.source" :model="situation.model" />

        <section class="mt-10">
            <h2 class="text-xl font-semibold tracking-tight">{{ t('Bulletins by area') }}</h2>
            <div class="mt-4 grid gap-6 lg:grid-cols-2">
                <article v-for="a in areas" :key="a.slug" class="rounded-md border p-5">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h3 class="font-semibold">
                            <Link :href="`/areas/${a.slug}`" class="hover:underline">{{ a.name }}</Link>
                        </h3>
                        <Link :href="`/areas/${a.slug}#bulletin`" class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                            >{{ t('Open the area') }} →</Link
                        >
                    </div>

                    <div v-if="a.top" class="mt-4">
                        <div class="flex items-baseline gap-2 text-sm">
                            <span class="font-medium">{{ t('Most unusual week') }}</span>
                            <span class="text-muted-foreground">{{ a.top_week ? date(a.top_week) : '' }}</span>
                            <span v-if="a.top_times" class="tabular-nums text-orange-700 dark:text-orange-400">×{{ a.top_times }}</span>
                        </div>
                        <p class="mt-1 text-[15px] leading-relaxed">{{ a.top.text }}</p>
                        <p class="mt-1 inline-flex items-center gap-1 text-xs text-muted-foreground">
                            <Sparkles v-if="a.top.source === 'ai'" class="size-3" />
                            {{ a.top.source === 'ai' ? t('Written by AI (:model)', { model: a.top.model ?? '' }) : t('Fixed template') }}
                        </p>
                    </div>

                    <div class="mt-4 border-t pt-4">
                        <div class="text-sm font-medium">{{ t('Last 30 days') }}</div>
                        <p class="mt-1 text-[15px] leading-relaxed">{{ a.latest.text }}</p>
                        <p class="mt-1 inline-flex items-center gap-1 text-xs text-muted-foreground">
                            <Sparkles v-if="a.latest.source === 'ai'" class="size-3" />
                            {{ a.latest.source === 'ai' ? t('Written by AI (:model)', { model: a.latest.model ?? '' }) : t('Fixed template') }}
                        </p>
                    </div>
                </article>
            </div>
        </section>

        <section class="mt-12">
            <h2 class="text-xl font-semibold tracking-tight">{{ t('How the AI is used') }}</h2>
            <div class="mt-4 grid gap-6 md:grid-cols-2">
                <div v-for="r in rules" :key="r.title" class="flex gap-3">
                    <component :is="r.icon" class="mt-0.5 size-5 shrink-0 text-orange-600" />
                    <div>
                        <div class="font-medium">{{ t(r.title) }}</div>
                        <p class="mt-1 text-sm text-muted-foreground">{{ t(r.body) }}</p>
                    </div>
                </div>
            </div>
            <p class="mt-5 text-sm text-muted-foreground">
                {{
                    t(
                        'Writers right now: :primary, with :fallback as fallback. The decision rule and the facts the model receives are shown on How it works.',
                        { primary: writers.primary, fallback: writers.fallback },
                    )
                }}
                <Link href="/how-it-works" class="underline-offset-4 hover:underline">{{ t('How it works') }} →</Link>
            </p>
        </section>
    </SiteLayout>
</template>
