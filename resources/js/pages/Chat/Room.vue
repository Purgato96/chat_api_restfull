<template>
    <AppLayout :title="`Sala: ${room.name}`">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Header da sala -->
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">{{ room.name }}</h2>
                                <p v-if="room.description" class="text-gray-600 mt-1">{{ room.description }}</p>
                                <div class="flex items-center mt-2 space-x-4">
                  <span class="text-sm text-gray-500">
                    {{ room.users.length }} {{ room.users.length === 1 ? 'usuário' : 'usuários' }}
                  </span>
                                    <span v-if="room.is_private" class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">
                    Privada
                  </span>
                                    <span v-else class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                    Pública
                  </span>
                                    <!-- Indicador de conexão -->
                                    <span
                                        :class="connectionStatus === 'connected' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                        class="px-2 py-1 text-xs rounded-full"
                                    >
                    {{ connectionStatus === 'connected' ? '🟢 Online' : '🔴 Offline' }}
                  </span>
                                </div>
                            </div>

                            <!-- Botões de ação -->
                            <div class="flex space-x-2">
                                <!-- Botão para gerenciar usuários (apenas para criador) -->
                                <button
                                    v-if="canManageUsers"
                                    @click="showUserManager = !showUserManager"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm"
                                >
                                    {{ showUserManager ? 'Ocultar' : 'Gerenciar' }} Usuários
                                </button>

                                <!-- Botão para sair da sala -->
                                <button
                                    @click="leaveRoom"
                                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm"
                                >
                                    Sair da Sala
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex">
                        <!-- Área principal do chat -->
                        <div class="flex-1 flex flex-col h-96">
                            <!-- Área de mensagens -->
                            <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4">
                                <div
                                    v-for="message in localMessages"
                                    :key="message.id"
                                    class="flex items-start space-x-3"
                                >
                                    <!-- Avatar do usuário -->
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                        {{ message.user.name.charAt(0).toUpperCase() }}
                                    </div>

                                    <!-- Conteúdo da mensagem -->
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2">
                                            <span class="font-medium text-gray-900">{{ message.user.name }}</span>
                                            <span class="text-xs text-gray-500">
                        {{ formatTime(message.created_at) }}
                      </span>
                                            <span v-if="message.edited_at" class="text-xs text-gray-400">(editada)</span>
                                        </div>
                                        <p class="text-gray-700 mt-1">{{ message.content }}</p>

                                        <!-- Ações da mensagem (apenas para o autor) -->
                                        <div v-if="message.user.id === $page.props.auth.user.id" class="flex space-x-2 mt-2">
                                            <button
                                                @click="editMessage(message)"
                                                class="text-xs text-blue-600 hover:text-blue-800"
                                            >
                                                Editar
                                            </button>
                                            <button
                                                @click="deleteMessage(message.id)"
                                                class="text-xs text-red-600 hover:text-red-800"
                                            >
                                                Excluir
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Formulário de envio de mensagem -->
                            <div class="border-t text-black border-gray-200 p-4">
                                <form @submit.prevent="sendMessage" class="flex space-x-2">
                                    <input
                                        v-model="newMessage"
                                        type="text"
                                        placeholder="Digite sua mensagem..."
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        :disabled="isSending"
                                    />
                                    <button
                                        type="submit"
                                        :disabled="!newMessage.trim() || isSending"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                                    >
                                        {{ isSending ? 'Enviando...' : 'Enviar' }}
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Painel lateral de gerenciamento de usuários -->
                        <div
                            v-if="showUserManager && canManageUsers"
                            class="w-80 border-l border-gray-200"
                        >
                            <RoomUserManager :room="room" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de edição de mensagem -->
        <div
            v-if="editingMessage"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">Editar Mensagem</h3>
                <textarea
                    v-model="editMessageContent"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    rows="3"
                ></textarea>
                <div class="flex justify-end space-x-3 mt-4">
                    <button
                        @click="cancelEdit"
                        class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-md"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="updateMessage"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        :disabled="!editMessageContent.trim() || isSending"
                    >
                        {{ isSending ? 'Salvando...' : 'Salvar' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import RoomUserManager from '@/components/RoomUserManager.vue'

const props = defineProps({
    room: {
        type: Object,
        required: true
    },
    messages: {
        type: Array,
        required: true
    }
})
const { props: pageProps } = usePage()

const newMessage = ref('')
const isSending = ref(false)
const messagesContainer = ref(null)
const showUserManager = ref(false)
const connectionStatus = ref('disconnected')

const localMessages = ref([...props.messages])

const editingMessage = ref(null)
const editMessageContent = ref('')

const canManageUsers = computed(() => {
    return props.room.created_by === pageProps.auth.user.id
})

const sendMessage = async () => {
    if (!newMessage.value.trim()) return

    isSending.value = true

    try {
        await router.post(route('messages.store', props.room.slug), {
            content: newMessage.value
        }, {
            preserveState: true,
            onSuccess: () => {
                // Adiciona mensagem localmente
                localMessages.value.push({
                    id: Date.now(), // ID temporário
                    content: newMessage.value,
                    room_id: props.room.id,
                    created_at: new Date().toISOString(),
                    edited_at: null,
                    user: {
                        id: pageProps.auth.user.id,
                        name: pageProps.auth.user.name
                    }
                })
                newMessage.value = ''
                scrollToBottom()
            }
        })
    } catch (error) {
        console.error('Erro ao enviar mensagem:', error)
    } finally {
        isSending.value = false
    }
}

const editMessage = (message) => {
    editingMessage.value = message
    editMessageContent.value = message.content
}

const cancelEdit = () => {
    editingMessage.value = null
    editMessageContent.value = ''
}

const updateMessage = async () => {
    if (!editMessageContent.value.trim()) return

    isSending.value = true

    try {
        await router.put(route('messages.update', editingMessage.value.slug), {
            content: editMessageContent.value
        }, {
            preserveState: true,
            onSuccess: () => {
                cancelEdit()
            }
        })
    } catch (error) {
        console.error('Erro ao editar mensagem:', error)
    } finally {
        isSending.value = false
    }
}

const deleteMessage = async (messageId) => {
    if (!confirm('Tem certeza que deseja excluir esta mensagem?')) return

    try {
        await router.delete(route('messages.destroy', messageId), {
            preserveState: true,
            onSuccess: () => {
                const index = localMessages.value.findIndex(msg => msg.id === messageId)
                if (index !== -1) {
                    localMessages.value.splice(index, 1)
                }
            }
        })
    } catch (error) {
        console.error('Erro ao excluir mensagem:', error)
    }
}

const leaveRoom = async () => {
    if (!confirm('Tem certeza que deseja sair desta sala?')) return

    try {
        await router.delete(route('rooms.leave', props.room.slug))
    } catch (error) {
        console.error('Erro ao sair da sala:', error)
    }
}

const formatTime = (timestamp) => {
    return new Date(timestamp).toLocaleTimeString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit'
    })
}

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
        }
    })
}

let echoChannel = null

const setupEcho = () => {
    if (!window.Echo) {
        console.error('❌ Laravel Echo não está disponível')
        return
    }

    console.log('🚀 Configurando Laravel Echo para a sala:', props.room.slug)

    echoChannel = window.Echo.private(`room.${props.room.slug}`)
        .listen('.message.sent', (event) => {
            console.log('📨 Nova mensagem recebida via broadcasting:', event)

            // Adiciona mensagem recebida ao chat
            localMessages.value.push({
                id: event.id,
                content: event.content,
                room_id: event.room_id,
                created_at: event.created_at,
                edited_at: event.edited_at,
                user: event.user
            })

            scrollToBottom()
        })
        .subscribed(() => {
            console.log('✅ Inscrito no canal da sala com sucesso')
            connectionStatus.value = 'connected'
        })
        .error((error) => {
            console.error('❌ Erro no canal da sala:', error)
            connectionStatus.value = 'disconnected'
        })

    if (window.Echo.connector?.pusher?.connection) {
        window.Echo.connector.pusher.connection.bind('connected', () => {
            console.log('✅ Pusher conectado')
            connectionStatus.value = 'connected'
        })

        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            console.log('❌ Pusher desconectado')
            connectionStatus.value = 'disconnected'
        })
    }
}

const cleanupEcho = () => {
    if (echoChannel) {
        window.Echo.leave(`room.${props.room.slug}`)
        echoChannel = null
        console.log('🧹 Canal desconectado')
    }
}

onMounted(() => {
    scrollToBottom()
    setTimeout(setupEcho, 100)
})

onUnmounted(() => {
    cleanupEcho()
})
</script>

