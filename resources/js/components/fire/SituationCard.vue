<script setup lang="ts">
import { t } from '@/lib/i18n';
import { Check, Copy, Sparkles } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{ text: string; source: 'ai' | 'template'; model: string | null }>();
const copied = ref(false);

async function copy() {
    await navigator.clipboard.writeText(props.text);
    copied.value = true;
    setTimeout(() => (copied.value = false), 1500);
}
</script>

<template>
    <section class="rounded-md border-l-2 border-orange-600 bg-muted/60 px-4 py-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold">{{ t('Reading of the current situation') }}</h2>
            <button class="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground" @click="copy">
                <component :is="copied ? Check : Copy" class="size-3.5" /> {{ copied ? t('Copied') : t('Copy') }}
            </button>
        </div>
        <p class="mt-1.5 text-[15px] leading-relaxed">{{ text }}</p>
        <p class="mt-2 inline-flex items-center gap-1 text-xs text-muted-foreground">
            <Sparkles v-if="source === 'ai'" class="size-3" />
            {{
                source === 'ai'
                    ? t(
                          'Anomalies are detected by statistics (median and MAD); this reading is written by AI (:model) from those numbers only, each one checked.',
                          { model: model ?? 'Gemma 4' },
                      )
                    : t('Anomalies are detected by statistics (median and MAD); this reading comes from a fixed template with those numbers.')
            }}
        </p>
    </section>
</template>
