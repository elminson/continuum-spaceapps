<script setup lang="ts">
import { locale, t } from '@/lib/i18n';
import { Link, usePage } from '@inertiajs/vue3';
import { BellRing, BookOpen, Code, Compass, Cpu, Database, ExternalLink, LayoutGrid, LogIn, LogOut, MapPin, Rocket } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => (page.props.auth as { user: { name: string } | null } | undefined)?.user ?? null);

const nav = [
    { href: '/', label: 'Overview', icon: LayoutGrid },
    { href: '/areas', label: 'Areas of interest', icon: MapPin },
    { href: '/explore', label: 'Explore', icon: Compass },
    { href: '/alerts', label: 'Unusual periods', icon: BellRing },
    { href: '/data', label: 'Ingestion', icon: Database },
    { href: '/method', label: 'Method', icon: BookOpen },
    { href: '/how-it-works', label: 'How it works', icon: Cpu },
    { href: '/api-docs', label: 'API', icon: Code },
];

const active = (href: string) => (href === '/' ? page.url === '/' : page.url.startsWith(href));
</script>

<template>
    <div class="min-h-screen bg-background text-foreground lg:flex">
        <aside class="border-b lg:sticky lg:top-0 lg:flex lg:h-screen lg:w-60 lg:shrink-0 lg:flex-col lg:border-b-0 lg:border-r">
            <Link href="/" class="flex items-center gap-2 px-4 py-4">
                <span class="grid size-9 place-items-center rounded-lg bg-neutral-900"><img src="/favicon.svg" alt="" class="size-7" /></span>
                <span class="font-semibold">Continuum</span>
            </Link>

            <nav class="flex gap-1 overflow-x-auto px-2 pb-2 text-sm lg:flex-col lg:overflow-visible">
                <Link
                    v-for="item in nav"
                    :key="item.href"
                    :href="item.href"
                    class="flex shrink-0 items-center gap-2 rounded-md px-3 py-2 hover:bg-muted"
                    :class="active(item.href) ? 'bg-muted font-medium' : 'text-muted-foreground'"
                >
                    <component :is="item.icon" class="size-4" />
                    {{ t(item.label) }}
                </Link>
            </nav>

            <div class="hidden flex-col gap-1 px-2 pb-4 text-sm text-muted-foreground lg:mt-auto lg:flex">
                <a
                    href="https://firms.modaps.eosdis.nasa.gov"
                    target="_blank"
                    rel="noopener"
                    class="flex items-center gap-2 rounded-md px-3 py-2 hover:bg-muted"
                >
                    <ExternalLink class="size-4" /> NASA FIRMS
                </a>
                <a
                    href="https://www.spaceappschallenge.org/2026/find-a-team/continuum1/"
                    target="_blank"
                    rel="noopener"
                    class="flex items-center gap-2 rounded-md px-3 py-2 hover:bg-muted"
                >
                    <Rocket class="size-4" /> {{ t('Space Apps challenge') }}
                </a>
                <Link v-if="!user" href="/login" class="flex items-center gap-2 rounded-md px-3 py-2 hover:bg-muted">
                    <LogIn class="size-4" /> {{ t('Sign in to save areas') }}
                </Link>
                <Link v-else href="/logout" method="post" as="button" class="flex items-center gap-2 rounded-md px-3 py-2 text-left hover:bg-muted">
                    <LogOut class="size-4" /> {{ t('Sign out (:name)', { name: user.name }) }}
                </Link>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <div class="flex justify-end px-4 pt-4 lg:px-8">
                <div class="inline-flex rounded-md border p-0.5 text-sm" role="group" aria-label="Language">
                    <a
                        v-for="[code, name] in [
                            ['en', 'English'],
                            ['es', 'Español'],
                        ]"
                        :key="code"
                        :href="`/locale/${code}`"
                        :aria-current="locale() === code ? 'true' : undefined"
                        class="rounded px-3 py-1"
                        :class="locale() === code ? 'bg-foreground text-background' : 'text-muted-foreground hover:text-foreground'"
                        >{{ name }}</a
                    >
                </div>
            </div>
            <main class="mx-auto max-w-6xl px-4 pb-6 pt-2 lg:px-8 lg:pb-8">
                <slot />
            </main>
            <footer class="border-t">
                <div class="mx-auto max-w-6xl px-4 py-6 text-xs text-muted-foreground lg:px-8">
                    {{
                        t(
                            'Data: NASA FIRMS (MODIS and VIIRS active fires). Team Continuum · Elminson & Miguel · Santo Domingo · NASA Space Apps 2026.',
                        )
                    }}
                </div>
            </footer>
        </div>
    </div>
</template>
