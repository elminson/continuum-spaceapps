<script setup lang="ts">
import { number, t } from '@/lib/i18n';
import { computed } from 'vue';

const props = defineProps<{ page: number; pages: number; total: number; perPage: number }>();
const emit = defineEmits<{ go: [page: number] }>();

/** 1 … 4 5 6 … 12 */
const links = computed(() => {
    const out: (number | '…')[] = [];
    for (let p = 1; p <= props.pages; p++) {
        if (p === 1 || p === props.pages || Math.abs(p - props.page) <= 1) out.push(p);
        else if (out[out.length - 1] !== '…') out.push('…');
    }
    return out;
});
</script>

<template>
    <nav v-if="pages > 1" class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm" :aria-label="t('Pages')">
        <span class="tabular-nums text-muted-foreground">{{
            t(':from–:to of :total', { from: number((page - 1) * perPage + 1), to: number(Math.min(page * perPage, total)), total: number(total) })
        }}</span>
        <div class="flex items-center gap-1">
            <button class="rounded-md border px-2.5 py-1 disabled:opacity-40" :disabled="page === 1" @click="emit('go', page - 1)">
                ← {{ t('Previous') }}
            </button>
            <template v-for="(p, i) in links" :key="i">
                <span v-if="p === '…'" class="px-1 text-muted-foreground">…</span>
                <button
                    v-else
                    class="min-w-8 rounded-md border px-2 py-1 tabular-nums"
                    :class="p === page ? 'bg-foreground text-background' : 'hover:bg-muted'"
                    :aria-current="p === page ? 'page' : undefined"
                    @click="emit('go', p)"
                >
                    {{ p }}
                </button>
            </template>
            <button class="rounded-md border px-2.5 py-1 disabled:opacity-40" :disabled="page === pages" @click="emit('go', page + 1)">
                {{ t('Next') }} →
            </button>
        </div>
    </nav>
</template>
