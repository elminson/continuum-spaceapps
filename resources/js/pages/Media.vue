<script setup lang="ts">
import SiteLayout from '@/layouts/SiteLayout.vue';
import { t } from '@/lib/i18n';
import { Head } from '@inertiajs/vue3';
import { Award, Code, Download, ExternalLink, FileText, Image, PlayCircle } from 'lucide-vue-next';

const videos = [
    {
        title: '30-second demo (English)',
        note: 'The pitch for the NASA global judges: two sensors, one scale, the proof, the Cuba week, the forecast and the alerts.',
        youtube: 'https://youtu.be/ZkYbmGTxsQQ',
        src: '/videos/continuum-demo-en.mp4',
        poster: '/videos/continuum-demo-en.jpg',
    },
    {
        title: 'Full explanation (Spanish, English subtitles, 3:57)',
        note: 'For the Santiago judges: the three steps and the test, one day through the method, the decisions it serves and how impact is measured.',
        youtube: 'https://youtu.be/2qLaag9OLKY',
        src: '/videos/continuum-local-es.mp4',
        poster: '/videos/continuum-local-es.jpg',
    },
];

const links = [
    { href: 'https://github.com/elminson/continuum-spaceapps', label: 'Code on GitHub', note: 'Laravel, Vue and the whole method; every commit from the event.', icon: Code },
    { href: 'https://www.spaceappschallenge.org/2026/find-a-team/continuum1/', label: 'NASA Space Apps project page', note: 'The official team page and submission.', icon: Award },
    { href: 'https://github.com/elminson/continuum-spaceapps/blob/main/docs/SUBMISSION.md', label: 'Submission text', note: 'Summary, description, use of AI and data sources, as submitted.', icon: FileText },
    { href: '/judges', label: 'For judges', note: 'Each line of the challenge and each criterion mapped to its proof.', icon: Award },
];
</script>

<template>
    <Head :title="t('Media')" />
    <SiteLayout>
        <h1 class="text-3xl font-semibold tracking-tight">{{ t('Media') }}</h1>
        <p class="mt-2 max-w-3xl text-muted-foreground">
            {{ t('The videos, the slides, the code and the submission in one place. Narration is an AI-generated voice (ElevenLabs); every image is a real screen of this app.') }}
        </p>

        <section class="mt-8 grid gap-6 lg:grid-cols-2">
            <article v-for="v in videos" :key="v.src" class="overflow-hidden rounded-md border">
                <video controls preload="metadata" :poster="v.poster" class="aspect-video w-full bg-black">
                    <source :src="v.src" type="video/mp4" />
                </video>
                <div class="p-5">
                    <h2 class="font-semibold">{{ t(v.title) }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">{{ t(v.note) }}</p>
                    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm">
                        <a :href="v.youtube" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 underline-offset-4 hover:underline">
                            <PlayCircle class="size-4" />{{ t('Watch on YouTube') }}
                        </a>
                        <a :href="v.src" download class="inline-flex items-center gap-1.5 text-muted-foreground underline-offset-4 hover:underline">
                            <Download class="size-4" />{{ t('Download MP4') }}
                        </a>
                    </div>
                </div>
            </article>
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-2">
            <article class="overflow-hidden rounded-md border">
                <a href="/slides/continuum-slides.pdf" target="_blank" rel="noopener">
                    <img src="/slides/continuum-slides.jpg" :alt="t('First slide of the deck')" class="aspect-video w-full object-cover" />
                </a>
                <div class="p-5">
                    <h2 class="font-semibold">{{ t('7-slide deck (PDF)') }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">{{ t('The problem, the three steps, the proof, the uses and the end of MODIS, in seven slides.') }}</p>
                    <a href="/slides/continuum-slides.pdf" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1.5 text-sm underline-offset-4 hover:underline">
                        <FileText class="size-4" />{{ t('Open the 7-slide deck') }}
                    </a>
                </div>
            </article>

            <article class="overflow-hidden rounded-md border">
                <a href="/og.png" target="_blank" rel="noopener">
                    <img src="/og.png" :alt="t('Sharing card')" class="aspect-video w-full object-cover" />
                </a>
                <div class="p-5">
                    <h2 class="font-semibold">{{ t('Sharing card') }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">{{ t('The image shown when a link to this site is shared: the Dominican Republic, week of 27 April 2015, 11.5 times a normal week.') }}</p>
                    <a href="/og.png" download="continuum.png" class="mt-3 inline-flex items-center gap-1.5 text-sm underline-offset-4 hover:underline">
                        <Image class="size-4" />{{ t('Download PNG') }}
                    </a>
                </div>
            </article>
        </section>

        <section class="mt-6 grid gap-4 sm:grid-cols-2">
            <a
                v-for="l in links"
                :key="l.href"
                :href="l.href"
                :target="l.href.startsWith('http') ? '_blank' : undefined"
                rel="noopener"
                class="group flex items-start gap-3 rounded-md border p-4 transition hover:bg-muted"
            >
                <component :is="l.icon" class="mt-0.5 size-5 shrink-0 text-orange-600" />
                <span>
                    <span class="font-medium">{{ t(l.label) }}</span>
                    <ExternalLink v-if="l.href.startsWith('http')" class="ml-1.5 inline size-3.5 text-muted-foreground" />
                    <span class="mt-0.5 block text-sm text-muted-foreground">{{ t(l.note) }}</span>
                </span>
            </a>
        </section>
    </SiteLayout>
</template>
