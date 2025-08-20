<script setup>
import { ref, onMounted, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import axios from '@/axios'

const privateRooms = ref([])
const loading = ref(true)
const sidebarOpen = ref(true)

const currentRoomSlug = computed(() => {
    const segments = window.location.pathname.split('/')
    return segments[segments.length - 1] || ''
})

const fetchPrivateRooms = async () => {
    try {
        loading.value = true
        const response = await axios.get('/api/v1/rooms/private/all', {
            withCredentials: true,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        privateRooms.value = response.data
    } catch (error) {
        console.error('Erro ao carregar salas privadas:', error.response?.data || error)
    } finally {
        loading.value = false
    }
}

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value
}

const openRoom = (room) => {
    if (room.slug === currentRoomSlug.value) {
        return
    }
    router.visit(`/chat/rooms/${room.slug}`, {
        preserveState: false,
        preserveScroll: false,
    })
}

onMounted(() => {
    fetchPrivateRooms()
})
</script>

<template>
    <div class="h-full border-l border-gray-200 flex flex-col bg-white">
        <!-- Botão abrir/fechar -->
        <button
            @click="toggleSidebar"
            class="p-2 text-gray-600 hover:bg-gray-100 flex items-center justify-center"
        >
            <span v-if="sidebarOpen">⬅️</span>
            <span v-else>➡️</span>
        </button>

        <!-- Lista de salas -->
        <div v-if="sidebarOpen" class="flex-1 overflow-y-auto">
            <h3 class="text-xs font-semibold text-gray-500 px-2 mt-2">Mensagens Privadas</h3>
            <div v-if="loading" class="p-2 text-gray-500 text-sm">Carregando...</div>
            <ul v-else>
                <li
                    v-for="room in privateRooms"
                    :key="room.id"
                    @click="openRoom(room)"
                    :class="[
            'flex items-center space-x-2 cursor-pointer p-2 rounded text-black',
                        room.slug === activeRoomSlug ? 'bg-blue-100 text-blue-700 font-semibold' : 'hover:bg-gray-100'
          ]"
                >
                    <span class="text-gray-600">🔒</span>
                    <span class="text-sm">{{ room.name }}</span>
                </li>
                <li v-if="privateRooms.length === 0" class="text-sm text-gray-500 p-2">
                    Nenhuma conversa privada.
                </li>
            </ul>
        </div>

        <!-- Ícones quando fechado -->
        <div v-else class="flex-1 overflow-y-auto">
            <ul>
                <li
                    v-for="room in privateRooms"
                    :key="room.id"
                    @click="openRoom(room)"
                    class="flex items-center justify-center cursor-pointer hover:bg-gray-100 p-2"
                    :title="room.name"
                >
                    <span class="text-gray-600">🔒</span>
                </li>
            </ul>
        </div>
    </div>
</template>
