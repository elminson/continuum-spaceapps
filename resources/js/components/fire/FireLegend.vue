<script setup lang="ts">
import { t } from '@/lib/i18n';
</script>

<template>
    <span class="inline-flex flex-wrap gap-3">
        <span><span class="inline-block size-2 rounded-full bg-[#facc15]" /> {{ t('both sensors') }}</span>
        <span><span class="inline-block size-2 rounded-full bg-[#f97316]" /> {{ t('VIIRS only') }}</span>
        <span><span class="inline-block size-2 rounded-full bg-[#3b82f6]" /> {{ t('MODIS only') }}</span>
    </span>
</template>
