<!-- Conteúdo de /home/ubuntu/Chat_Room_WithEcho.vue -->
<template>
    <AppLayout :title="room.name">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ room.name }}
                    </h2>
                    <p class="text-sm text-gray-600">{{ room.description }}</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-500">{{ room.users?.length || 0 }} membros</span>
                    <div :class="connectionStatus === 'connected' ? 'bg-green-500' : 'bg-red-500'" class="w-2 h-2 rounded-full"></div>
                    <span class="text-xs text-gray-500">{{ connectionStatus === 'connected' ? 'Online' : 'Offline' }}</span>
                </div>
                <button
                    @click="$inertia.visit(route('rooms.index'))"
                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm"
                >
                    Voltar
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg h-[calc(100vh-200px)] flex flex-col">

                    <!-- Área de mensagens -->
                    <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4">
                        <div
                            v-for="message in messages"
                            :key="message.id"
                            class="flex items-start space-x-3"
                            :class="{ 'animate-pulse': message.sending }"
                        >
                            <!-- Avatar -->
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                    {{ message.user.name.charAt(0).toUpperCase() }}
                                </div>
                            </div>

                            <!-- Conteúdo da mensagem -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2">
                                    <span class="font-semibold text-gray-900">{{ message.user.name }}</span>
                                    <span class="text-xs text-gray-500">{{ formatDate(message.created_at) }}</span>
                                    <span v-if="message.edited_at" class="text-xs text-gray-400">(editada)</span>
                                    <span v-if="message.sending" class="text-xs text-blue-500">Enviando...</span>
                                </div>
                                <p class="text-gray-700 mt-1 whitespace-pre-wrap">{{ message.content }}</p>

                                <!-- Ações da mensagem (apenas para o autor) -->
                                <div v-if="message.user.id === $page.props.auth.user.id && !message.sending" class="mt-2 flex space-x-2">
                                    <button
                                        @click="editMessage(message)"
                                        class="text-xs text-blue-600 hover:text-blue-800"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        @click="deleteMessage(message)"
                                        class="text-xs text-red-600 hover:text-red-800"
                                    >
                                        Deletar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Estado vazio -->
                        <div v-if="messages.length === 0" class="text-center py-12">
                            <div class="text-gray-500 mb-4">
                                <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.001 8.001 0 01-7.003-4.165L2 20l4.165-4.003A8.001 8.001 0 0112 4c4.418 0 8 3.582 8 8z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhuma mensagem ainda</h3>
                            <p class="text-gray-500">Seja o primeiro a enviar uma mensagem nesta sala!</p>
                        </div>
                    </div>

                    <!-- Formulário de envio -->
                    <div class="border-t bg-gray-50 p-4">
                        <form @submit.prevent="sendMessage" class="flex space-x-4">
                            <div class="flex-1">
                <textarea
                    v-model="newMessage"
                    @keydown.enter.exact.prevent="sendMessage"
                    @keydown.enter.shift.exact="newMessage += '\n'"
                    placeholder="Digite sua mensagem... (Enter para enviar, Shift+Enter para nova linha)"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 resize-none"
                    rows="2"
                    :disabled="sending"
                ></textarea>
                            </div>
                            <button
                                type="submit"
                                :disabled="!newMessage.trim() || sending"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ sending ? 'Enviando...' : 'Enviar' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de edição -->
        <div v-if="editingMessage" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Editar Mensagem</h3>

                    <form @submit.prevent="updateMessage">
                        <div class="mb-4">
              <textarea
                  v-model="editForm.content"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  rows="4"
                  required
              ></textarea>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button
                                type="button"
                                @click="cancelEdit"
                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="updating"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                            >
                                {{ updating ? 'Salvando...' : 'Salvar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, nextTick, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Echo from '@/echo'

const props = defineProps({
    room: Object,
    messages: Array
})

const messages = ref([...props.messages])
const newMessage = ref('')
const sending = ref(false)
const messagesContainer = ref(null)
const connectionStatus = ref('connecting')

const editingMessage = ref(null)
const updating = ref(false)
const editForm = reactive({
    content: ''
})

let channel = null

const sendMessage = async () => {
    if (!newMessage.value.trim() || sending.value) return

    sending.value = true

    // Adiciona mensagem temporária com indicador de envio
    const tempMessage = {
        id: Date.now(),
        content: newMessage.value,
        user: {
            id: props.room.users.find(u => u.id === window.Laravel.user.id) || window.Laravel.user,
            name: window.Laravel.user.name
        },
        created_at: new Date().toISOString(),
        sending: true
    }

    messages.value.push(tempMessage)
    const messageContent = newMessage.value
    newMessage.value = ''

    await nextTick()
    scrollToBottom()

    try {
        const response = await fetch(route('messages.store', props.room.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                content: messageContent
            })
        })

        if (response.ok) {
            // Remove a mensagem temporária
            const tempIndex = messages.value.findIndex(m => m.id === tempMessage.id)
            if (tempIndex !== -1) {
                messages.value.splice(tempIndex, 1)
            }
            // A mensagem real será adicionada via broadcasting
        } else {
            // Remove mensagem temporária em caso de erro
            const tempIndex = messages.value.findIndex(m => m.id === tempMessage.id)
            if (tempIndex !== -1) {
                messages.value.splice(tempIndex, 1)
            }
            newMessage.value = messageContent // Restaura o conteúdo
            alert('Erro ao enviar mensagem. Tente novamente.')
        }
    } catch (error) {
        console.error('Erro ao enviar mensagem:', error)
        // Remove mensagem temporária em caso de erro
        const tempIndex = messages.value.findIndex(m => m.id === tempMessage.id)
        if (tempIndex !== -1) {
            messages.value.splice(tempIndex, 1)
        }
        newMessage.value = messageContent // Restaura o conteúdo
        alert('Erro ao enviar mensagem. Tente novamente.')
    } finally {
        sending.value = false
    }
}

const editMessage = (message) => {
    editingMessage.value = message
    editForm.content = message.content
}

const cancelEdit = () => {
    editingMessage.value = null
    editForm.content = ''
}

const updateMessage = async () => {
    if (!editForm.content.trim() || updating.value) return

    updating.value = true

    try {
        const response = await fetch(route('messages.update', editingMessage.value.id), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                content: editForm.content
            })
        })

        if (response.ok) {
            const data = await response.json()
            const index = messages.value.findIndex(m => m.id === editingMessage.value.id)
            if (index !== -1) {
                messages.value[index] = data.message
            }
            cancelEdit()
        }
    } catch (error) {
        console.error('Erro ao editar mensagem:', error)
    } finally {
        updating.value = false
    }
}

const deleteMessage = async (message) => {
    if (!confirm('Tem certeza que deseja deletar esta mensagem?')) return

    try {
        const response = await fetch(route('messages.destroy', message.id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        })

        if (response.ok) {
            const index = messages.value.findIndex(m => m.id === message.id)
            if (index !== -1) {
                messages.value.splice(index, 1)
            }
        }
    } catch (error) {
        console.error('Erro ao deletar mensagem:', error)
    }
}

const formatDate = (date) => {
    return new Date(date).toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
}

const setupEcho = () => {
    channel = Echo.private(`room.${props.room.id}`)
        .listen('.message.sent', (e) => {
            // Verifica se a mensagem já existe (evita duplicatas)
            const exists = messages.value.find(m => m.id === e.id)
            if (!exists) {
                messages.value.push({
                    id: e.id,
                    content: e.content,
                    user: e.user,
                    room_id: e.room_id,
                    created_at: e.created_at,
                    edited_at: e.edited_at
                })

                nextTick(() => {
                    scrollToBottom()
                })
            }
        })

    // Monitora status da conexão
    Echo.connector.pusher.connection.bind('connected', () => {
        connectionStatus.value = 'connected'
    })

    Echo.connector.pusher.connection.bind('disconnected', () => {
        connectionStatus.value = 'disconnected'
    })

    Echo.connector.pusher.connection.bind('failed', () => {
        connectionStatus.value = 'failed'
    })
}

onMounted(() => {
    scrollToBottom()
    setupEcho()
})

onUnmounted(() => {
    if (channel) {
        Echo.leave(`room.${props.room.id}`)
    }
})
</script>
