<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    projects: unknown;
}>();
</script>

<template>
    <div>
        <Head title="Projects" />
        <div class="p-4">
            <h1 class="text-xl font-semibold">Projects</h1>
        </div>
    </div>
</template>
