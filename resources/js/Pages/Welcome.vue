<script setup>
defineProps({ message: String });
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-100">
    <h1 class="text-3xl font-bold text-indigo-600">{{ message }}</h1>
  </div>
</template>