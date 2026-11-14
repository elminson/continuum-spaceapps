<script setup lang="ts">
import { locale, t } from '@/lib/i18n';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <header class="border-b">
            <div class="mx-auto flex max-w-6xl items-center gap-6 px-4 py-3">
                <Link href="/" class="flex items-center gap-2 font-semibold tracking-tight">
                    <span class="inline-block size-3 rounded-full bg-orange-500" />
                    Continuum
                </Link>
                <nav class="flex gap-4 text-sm text-muted-foreground">
                    <Link href="/" class="hover:text-foreground">{{ t('Areas') }}</Link>
                    <Link href="/method" class="hover:text-foreground">{{ t('Method') }}</Link>
                </nav>
                <div class="ml-auto flex gap-1 text-sm">
                    <a href="/locale/en" :class="locale() === 'en' ? 'font-semibold' : 'text-muted-foreground'">EN</a>
                    <span class="text-muted-foreground">·</span>
                    <a href="/locale/es" :class="locale() === 'es' ? 'font-semibold' : 'text-muted-foreground'">ES</a>
                </div>
            </div>
        </header>
        <main class="mx-auto max-w-6xl px-4 py-8">
            <slot />
        </main>
        <footer class="border-t">
            <div class="mx-auto max-w-6xl px-4 py-6 text-xs text-muted-foreground">
                {{ t('Data: NASA FIRMS (MODIS and VIIRS active fires). Team Continuum · Elminson & Miguel · Santo Domingo · NASA Space Apps 2026.') }}
            </div>
        </footer>
    </div>
</template>
