<template>
    <div class="flex h-screen bg-gray-50">
        <!-- Sidebar de Mensagens Privadas -->
        <PrivateRoomsSidebar />

        <!-- Conteúdo principal -->
        <div class="flex-1 overflow-y-auto">
            <slot />
        </div>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import PrivateRoomsSidebar from '@/components/PrivateRoomsSidebar.vue'

const props = defineProps({
    title: {
        type: String,
        default: 'Chat'
    }
})
</script>

<Head :title="props.title" />
