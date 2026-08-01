<template>
  <!-- Card Skeleton -->
  <div v-if="type === 'card'" class="minimal-card p-5 space-y-4">
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <Skeleton width="w-9" height="h-9" rounded="rounded-full" />
        <div class="space-y-1.5">
          <Skeleton width="w-24" height="h-4" />
          <Skeleton width="w-16" height="h-3" />
        </div>
      </div>
      <Skeleton width="w-20" height="h-5" />
    </div>
  </div>

  <!-- Hero Skeleton -->
  <div v-else-if="type === 'hero'" class="minimal-card-hero p-6 sm:p-8 space-y-4">
    <div class="flex flex-col sm:flex-row justify-between gap-4">
      <div class="space-y-2">
        <Skeleton width="w-28" height="h-3" />
        <Skeleton width="w-48" height="h-9" />
      </div>
      <div class="flex items-center space-x-3">
        <Skeleton width="w-36" height="h-8" rounded="rounded-xl" />
        <Skeleton width="w-36" height="h-8" rounded="rounded-xl" />
      </div>
    </div>
  </div>

  <!-- List Item Skeleton -->
  <div v-else-if="type === 'row'" class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between">
    <div class="flex items-center space-x-3.5">
      <Skeleton width="w-9" height="h-9" rounded="rounded-full" />
      <div class="space-y-1.5">
        <Skeleton width="w-32" height="h-4" />
        <Skeleton width="w-48" height="h-3" />
      </div>
    </div>
    <Skeleton width="w-16" height="h-4" />
  </div>

  <!-- Default Generic Skeleton Block -->
  <div v-else class="minimal-card p-6 space-y-3">
    <Skeleton width="w-1/3" height="h-5" />
    <Skeleton width="w-full" height="h-12" />
  </div>
</template>

<script setup>
import Skeleton from './Skeleton.vue';

defineProps({
  type: {
    type: String,
    default: 'card' // 'card' | 'hero' | 'row'
  }
});
</script>
