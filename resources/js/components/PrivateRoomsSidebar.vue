<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from '@/axios'

const privateRooms = ref([]);
const loading = ref(true);
const sidebarOpen = ref(true);

const fetchPrivateRooms = async () => {
    try {
        const response = await axios.get('/api/v1/rooms/private/all', {
            withCredentials: true,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        privateRooms.value = response.data.data
    } catch (error) {
        console.error('Erro ao carregar salas privadas:', error.response?.data || error)
    }
}

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

const openRoom = (room) => {
    router.visit(`/chat/rooms/${room.slug}`);
};

onMounted(() => {
    fetchPrivateRooms();
});
</script>

<template>
    <div class="h-full border-l border-gray-200 flex flex-col bg-white">
        <!-- Botão de abrir/fechar -->
        <button
            @click="toggleSidebar"
            class="p-2 text-gray-600 hover:bg-gray-100 flex items-center justify-center"
        >
            <span v-if="sidebarOpen">⬅️</span>
            <span v-else>➡️</span>
        </button>

        <!-- Conteúdo -->
        <div v-if="sidebarOpen" class="flex-1 overflow-y-auto">
            <h3 class="text-xs font-semibold text-gray-500 px-2 mt-2">
                Mensagens Privadas
            </h3>
            <div v-if="loading" class="p-2 text-gray-500 text-sm">Carregando...</div>
            <ul v-else>
                <li
                    v-for="room in privateRooms"
                    :key="room.id"
                    @click="openRoom(room)"
                    class="flex items-center space-x-2 cursor-pointer hover:bg-gray-100 p-2"
                >
                    <span class="text-gray-600">🔒</span>
                    <span class="text-sm font-medium text-gray-700">{{ room.name }}</span>
                </li>
                <li v-if="privateRooms.length === 0" class="text-sm text-gray-500 p-2">
                    Nenhuma conversa privada.
                </li>
            </ul>
        </div>

        <!-- Ícones apenas quando fechado -->
        <div v-else class="flex-1 overflow-y-auto">
            <ul>
                <li
                    v-for="room in privateRooms"
                    :key="room.id"
                    @click="openRoom(room)"
                    class="flex items-center justify-center cursor-pointer hover:bg-gray-100 p-2"
                    title="Abrir conversa"
                >
                    <span class="text-gray-600">🔒</span>
                </li>
            </ul>
        </div>
    </div>
</template>
