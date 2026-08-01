<template>
  <div
    :class="[
      'skeleton-shimmer shrink-0',
      width || 'w-full',
      height || 'h-4',
      rounded || 'rounded-xl',
      customClass
    ]"
  ></div>
</template>

<script setup>
defineProps({
  width: {
    type: String,
    default: ''
  },
  height: {
    type: String,
    default: ''
  },
  rounded: {
    type: String,
    default: ''
  },
  customClass: {
    type: String,
    default: ''
  }
});
</script>
