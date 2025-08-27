<template>
    <div class="flex h-screen bg-gray-50">

        <!-- Conteúdo principal -->
        <div class="flex-1 overflow-y-auto">
            <slot />
        </div>
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
