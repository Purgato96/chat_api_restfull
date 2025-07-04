<template>
    <div class="min-h-screen bg-gray-100 text-gray-900 flex flex-col">
        <main class="flex-1">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'

const props = defineProps({
    title: {
        type: String,
        default: 'Chat'
    }
})
</script>

<Head :title="props.title" />
