<script setup lang="ts">
import { t } from '@/lib/i18n';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{ label?: string }>();
</script>

<template>
    <span class="inline-flex items-center gap-1.5 text-sm text-muted-foreground" role="status" aria-live="polite">
        <LoaderCircle class="size-4 animate-spin" />
        {{ label ?? t('Loading…') }}
    </span>
</template>
