<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    project: {
        data?: {
            name?: string;
        };
    };
}>();
</script>

<template>
    <div>
        <Head :title="project.data?.name ?? 'Project'" />
        <div class="p-4">
            <h1 class="text-xl font-semibold">{{ project.data?.name }}</h1>
        </div>
    </div>
</template>
