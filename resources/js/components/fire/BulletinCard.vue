<script setup lang="ts">
import { locale, t } from '@/lib/i18n';
import { Check, Copy, Sparkles } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import Loading from './Loading.vue';

const props = defineProps<{ slug: string; week: string | null }>();
const text = ref<string | null>(null);
const source = ref<'ai' | 'template' | null>(null);
const model = ref<string | null>(null);
const loading = ref(false);
const copied = ref(false);

async function load() {
    loading.value = true;
    const q = new URLSearchParams({ locale: locale(), ...(props.week ? { week: props.week } : {}) });
    const body = await (await fetch(`/api/areas/${props.slug}/bulletin?${q}`, { headers: { Accept: 'application/json' } })).json();
    text.value = body.data?.text ?? null;
    source.value = body.data?.source ?? null;
    model.value = body.data?.model ?? null;
    loading.value = false;
}

async function copy() {
    if (!text.value) return;
    await navigator.clipboard.writeText(text.value);
    copied.value = true;
    setTimeout(() => (copied.value = false), 1500);
}

watch(() => props.week, load, { immediate: true });
</script>

<template>
    <section class="rounded-md border-l-2 border-orange-600 bg-muted/60 px-4 py-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold">{{ t('Fire bulletin') }}</h2>
            <button v-if="text" class="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground" @click="copy">
                <component :is="copied ? Check : Copy" class="size-3.5" /> {{ copied ? t('Copied') : t('Copy') }}
            </button>
        </div>
        <Loading v-if="loading" class="mt-2" :label="t('Writing the bulletin from the numbers…')" />
        <p v-else-if="text" class="mt-1.5 text-[15px] leading-relaxed">{{ text }}</p>
        <p v-if="!loading && source" class="mt-2 inline-flex items-center gap-1 text-xs text-muted-foreground">
            <Sparkles v-if="source === 'ai'" class="size-3" />
            {{
                source === 'ai'
                    ? t('Written by AI (:model) from the numbers on this page; every figure is checked against the data.', {
                          model: model ?? 'Gemma 4',
                      })
                    : t('Written from a fixed template with the numbers on this page.')
            }}
        </p>
    </section>
</template>
