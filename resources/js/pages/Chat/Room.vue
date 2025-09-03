<template>
    <ChatLayout :title="`Sala: ${room.name}`">
        <div class="bg-blue-50 py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Header da sala -->
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">
                                    <span v-if="activeTab === 'public'">{{ room.name }}</span>
                                    <span v-else-if="currentPrivateConversation">{{ currentPrivateConversation.other_user.name }}</span>
                                    <span v-else>Chat Privado</span>
                                </h2>
                                <p v-if="room.description && activeTab === 'public'" class="text-gray-600 mt-1">{{ room.description }}</p>
                                <div class="flex items-center mt-2 space-x-4">
                                    <span class="text-sm text-gray-500" v-if="activeTab === 'public'">
                                        {{ room.users.length }} {{ room.users.length === 1 ? 'usuário' : 'usuários' }}
                                    </span>
                                    <span v-if="room.is_private && activeTab === 'public'"
                                          class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">
                                        Privada
                                    </span>
                                    <span v-else-if="activeTab === 'public'" class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
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
                        </div>
                    </div>

                    <div class="flex">
                        <!-- Sidebar -->
                        <div class="w-1/4 bg-gray-50 border-r border-gray-200">
                            <div class="p-4 border-b">
                                <h2 class="text-lg font-semibold">Conversas</h2>
                            </div>

                            <!-- Toggle entre chat público e privados -->
                            <div class="p-4 border-b">
                                <div class="flex space-x-2">
                                    <button
                                        @click="switchToPublic"
                                        :class="[
                                            'px-3 py-2 rounded text-sm font-medium',
                                            activeTab === 'public'
                                                ? 'bg-blue-500 text-white'
                                                : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                        ]"
                                    >
                                        Chat Público
                                    </button>
                                    <button
                                        @click="activeTab = 'private'"
                                        :class="[
                                            'px-3 py-2 rounded text-sm font-medium',
                                            activeTab === 'private'
                                                ? 'bg-blue-500 text-white'
                                                : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                        ]"
                                    >
                                        Chats Privados
                                    </button>
                                </div>
                            </div>

                            <!-- Lista de conversas privadas -->
                            <div v-if="activeTab === 'private'" class="overflow-y-auto max-h-96">
                                <div
                                    v-for="conversation in privateConversations"
                                    :key="conversation.id"
                                    @click="selectPrivateConversation(conversation)"
                                    :class="[
                                        'p-4 border-b cursor-pointer hover:bg-gray-100',
                                        currentPrivateConversation?.id === conversation.id ? 'bg-blue-50' : ''
                                    ]"
                                >
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-medium mr-3">
                                            {{ conversation.other_user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-medium text-sm">{{ conversation.other_user.name }}</h3>
                                            <p v-if="conversation.latest_message" class="text-xs text-gray-500 truncate">
                                                {{ conversation.latest_message.content }}
                                            </p>
                                            <p v-else class="text-xs text-gray-400 italic">Nenhuma mensagem ainda</p>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="privateConversations.length === 0" class="p-4 text-center text-gray-500 text-sm">
                                    Nenhuma conversa privada ainda.<br>
                                    Digite @ no chat público para iniciar uma conversa.
                                </div>
                            </div>

                            <!-- Chat público info -->
                            <div v-if="activeTab === 'public'" class="p-4">
                                <div class="text-center text-gray-500 text-sm">
                                    Chat público ativo<br>
                                    Digite @ para mencionar usuários
                                </div>
                            </div>
                        </div>

                        <!-- Área principal do chat -->
                        <div class="flex-1 flex flex-col h-96">
                            <!-- Área de mensagens -->
                            <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4">
                                <!-- Mensagens do chat público -->
                                <div v-if="activeTab === 'public'"
                                     v-for="message in localMessages"
                                     :key="message.id"
                                     class="flex flex-col"
                                >
                                    <div
                                        :class="{
                                        'self-end bg-blue-500 text-white': message.user.id === $page.props.auth.user.id,
                                        'self-start bg-gray-100 text-gray-900': message.user.id !== $page.props.auth.user.id
                                    }"
                                        class="max-w-xs md:max-w-sm lg:max-w-md rounded-lg shadow px-4 py-2"
                                    >
                                        <div class="flex items-center space-x-2 mb-1">
                                            <span class="text-xs font-semibold">
                                                {{ message.user.name }}
                                            </span>
                                            <span class="text-[10px] text-black font-bold">
                                                {{ formatDate(message.created_at) }} - {{ formatTime(message.created_at) }}
                                            </span>
                                            <span v-if="message.edited_at"
                                                  class="text-[10px] text-gray-400">(editada)</span>
                                        </div>
                                        <p class="text-sm break-words">
                                            {{ message.content }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Mensagens do chat privado -->
                                <div v-if="activeTab === 'private' && currentPrivateConversation"
                                     v-for="message in currentPrivateMessages"
                                     :key="message.id"
                                     class="flex flex-col"
                                >
                                    <div
                                        :class="{
                                        'self-end bg-blue-500 text-white': message.sender.id === $page.props.auth.user.id,
                                        'self-start bg-gray-100 text-gray-900': message.sender.id !== $page.props.auth.user.id
                                    }"
                                        class="max-w-xs md:max-w-sm lg:max-w-md rounded-lg shadow px-4 py-2"
                                    >
                                        <div class="flex items-center space-x-2 mb-1">
                                            <span class="text-xs font-semibold">
                                                {{ message.sender.name }}
                                            </span>
                                            <span class="text-[10px] text-black font-bold">
                                                {{ formatDate(message.created_at) }} - {{ formatTime(message.created_at) }}
                                            </span>
                                            <span v-if="message.is_edited"
                                                  class="text-[10px] text-gray-400">(editada)</span>
                                        </div>
                                        <p class="text-sm break-words">
                                            {{ message.content }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Placeholder quando nenhuma conversa está selecionada -->
                                <div v-if="activeTab === 'private' && !currentPrivateConversation" class="flex items-center justify-center h-full">
                                    <div class="text-center text-gray-500">
                                        <p class="text-lg mb-2">💬</p>
                                        <p>Selecione uma conversa para ver as mensagens</p>
                                        <p class="text-sm mt-1">Ou digite @ no chat público para iniciar uma nova</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Formulário de envio de mensagem -->
                            <div class="border-t text-black border-gray-200 p-4">
                                <form @submit.prevent="sendMessage" class="flex space-x-2">
                                    <div class="flex-1 relative">
                                        <input
                                            v-model="newMessage"
                                            @input="handleInput"
                                            @keydown="handleKeydown"
                                            type="text"
                                            :placeholder="getPlaceholderText"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            :disabled="isSending || (activeTab === 'private' && !currentPrivateConversation)"
                                        />

                                        <!-- Dropdown de menções -->
                                        <div
                                            v-if="showMentionDropdown && mentionUsers.length > 0"
                                            class="absolute bottom-full left-0 right-0 bg-white border rounded-lg shadow-lg max-h-48 overflow-y-auto z-10 mb-1"
                                        >
                                            <div
                                                v-for="(mentionUser, index) in mentionUsers"
                                                :key="mentionUser.id"
                                                @click="selectMention(mentionUser)"
                                                :class="[
                                                    'p-3 hover:bg-gray-50 cursor-pointer border-b last:border-b-0',
                                                    selectedMentionIndex === index ? 'bg-blue-50' : ''
                                                ]"
                                            >
                                                <div class="flex items-center">
                                                    <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-medium mr-3">
                                                        {{ mentionUser.name.charAt(0).toUpperCase() }}
                                                    </div>
                                                    <div>
                                                        <p class="font-medium text-sm">{{ mentionUser.name }}</p>
                                                        <p class="text-xs text-gray-500">{{ mentionUser.email }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <button
                                        type="submit"
                                        :disabled="!newMessage.trim() || isSending || (activeTab === 'private' && !currentPrivateConversation)"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                                    >
                                        {{ isSending ? 'Enviando...' : 'Enviar' }}
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Painel lateral de gerenciamento de usuários (se estiver ativo) -->
                        <div
                            v-if="showUserManager && canManageUsers && activeTab === 'public'"
                            class="w-80 border-l border-gray-200"
                        >
                            <RoomUserManager :room="room" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de edição de mensagem (mantendo seu modal existente) -->
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
    </ChatLayout>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted, watchEffect, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import ChatLayout from '@/layouts/ChatLayout.vue';
import RoomUserManager from '@/components/RoomUserManager.vue';

const props = defineProps({
    room: Object,
    messages: Array
});

const { props: pageProps } = usePage();

// ESTADOS EXISTENTES
const newMessage = ref('');
const isSending = ref(false);
const messagesContainer = ref(null);
const showUserManager = ref(false);
const connectionStatus = ref('disconnected');
const localMessages = ref([]);
const editingMessage = ref(null);
const editMessageContent = ref('');
const propsInitialized = ref(false);

// NOVOS ESTADOS PARA CHAT PRIVADO
const activeTab = ref('public');
const privateConversations = ref([]);
const currentPrivateConversation = ref(null);
const currentPrivateMessages = ref([]);

// ESTADOS PARA MENÇÕES
const showMentionDropdown = ref(false);
const mentionUsers = ref([]);
const selectedMentionIndex = ref(0);
const mentionStartIndex = ref(-1);
const roomUsers = ref([]);

// COMPUTEDS EXISTENTES
const canManageUsers = computed(() => {
    return props.room.created_by === pageProps.auth.user.id;
});

// NOVOS COMPUTEDS
const getPlaceholderText = computed(() => {
    if (activeTab.value === 'public') {
        return 'Digite @ para mencionar usuários ou envie uma mensagem...';
    }
    if (currentPrivateConversation.value) {
        return 'Digite sua mensagem...';
    }
    return 'Selecione uma conversa para enviar mensagens';
});

// MÉTODOS PARA CHAT PRIVADO
const loadPrivateConversations = async () => {
    try {
        const response = await axios.get('/chat/private-conversations');
        privateConversations.value = response.data;
    } catch (error) {
        console.error('Erro ao carregar conversas privadas:', error);
    }
};

const loadRoomUsers = async () => {
    try {
        const response = await axios.get(`/chat/rooms/${props.room.slug}/available-users`);
        roomUsers.value = response.data.filter(roomUser => roomUser.id !== pageProps.auth.user.id);
    } catch (error) {
        console.error('Erro ao carregar usuários da sala:', error);
        // Fallback: usar lista de usuários da sala
        roomUsers.value = props.room.users?.filter(user => user.id !== pageProps.auth.user.id) || [];
    }
};

const selectPrivateConversation = async (conversation) => {
    currentPrivateConversation.value = conversation;

    try {
        const response = await axios.get(`/chat/private-conversations/${conversation.id}`);
        currentPrivateMessages.value = response.data.messages;

        await nextTick();
        scrollToBottom();
    } catch (error) {
        console.error('Erro ao carregar mensagens da conversa:', error);
    }
};

const switchToPublic = () => {
    activeTab.value = 'public';
    currentPrivateConversation.value = null;
    currentPrivateMessages.value = [];
    nextTick(() => scrollToBottom());
};

// MÉTODOS PARA MENÇÕES
const handleInput = () => {
    if (activeTab.value !== 'public') return;

    const input = document.querySelector('input[type="text"]');
    const value = newMessage.value;
    const cursorPosition = input.selectionStart;

    // Encontrar a última ocorrência de @ antes do cursor
    let mentionStart = -1;
    for (let i = cursorPosition - 1; i >= 0; i--) {
        if (value[i] === '@') {
            mentionStart = i;
            break;
        }
        if (value[i] === ' ') {
            break;
        }
    }

    if (mentionStart !== -1) {
        const searchTerm = value.substring(mentionStart + 1, cursorPosition).toLowerCase();
        mentionStartIndex.value = mentionStart;

        mentionUsers.value = roomUsers.value.filter(roomUser =>
            roomUser.name.toLowerCase().includes(searchTerm) ||
            roomUser.email.toLowerCase().includes(searchTerm)
        );

        showMentionDropdown.value = mentionUsers.value.length > 0;
        selectedMentionIndex.value = 0;
    } else {
        showMentionDropdown.value = false;
        mentionUsers.value = [];
    }
};

const handleKeydown = (event) => {
    if (!showMentionDropdown.value) return;

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        selectedMentionIndex.value = Math.min(selectedMentionIndex.value + 1, mentionUsers.value.length - 1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        selectedMentionIndex.value = Math.max(selectedMentionIndex.value - 1, 0);
    } else if (event.key === 'Enter' && mentionUsers.value.length > 0) {
        event.preventDefault();
        selectMention(mentionUsers.value[selectedMentionIndex.value]);
    } else if (event.key === 'Escape') {
        showMentionDropdown.value = false;
    }
};

const selectMention = async (mentionUser) => {
    if (activeTab.value === 'public') {
        // Para chat público, apenas mencionar o usuário
        const input = document.querySelector('input[type="text"]');
        const beforeMention = newMessage.value.substring(0, mentionStartIndex.value);
        const afterCursor = newMessage.value.substring(input.selectionStart);
        newMessage.value = beforeMention + '@' + mentionUser.name + ' ' + afterCursor;

        // Posicionar cursor após a menção
        nextTick(() => {
            const newPosition = beforeMention.length + mentionUser.name.length + 2;
            input.setSelectionRange(newPosition, newPosition);
        });
    } else {
        // Para iniciar uma conversa privada
        try {
            const response = await axios.post('/chat/private-conversations', { user_id: mentionUser.id })

            await loadPrivateConversations();

            currentPrivateConversation.value = response.data;
            currentPrivateMessages.value = response.data.messages || [];
            activeTab.value = 'private';
        } catch (error) {
            console.error('Erro ao criar conversa privada:', error);
        }
    }

    showMentionDropdown.value = false;
    mentionUsers.value = [];
};

// SCROLL (mantendo o existente)
const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

// ENVIO DE MENSAGEM (adaptado)
const sendMessage = async () => {
    if (!newMessage.value.trim()) return;
    isSending.value = true;

    try {
        if (activeTab.value === 'public') {
            // Enviar mensagem para o chat público (seu método existente)
            await router.post(route('messages.store', props.room.slug), {
                content: newMessage.value
            }, {
                preserveState: true,
                onSuccess: () => {
                    newMessage.value = '';
                    scrollToBottom();
                }
            });
        } else if (currentPrivateConversation.value) {
            // Enviar mensagem privada
            const response = await axios.post(
                `/chat/private-conversations/${currentPrivateConversation.value.id}/messages`,
                {
                    content: newMessage.value
                }
            );

            /*currentPrivateMessages.value.push(response.data);*/

            // Atualizar a lista de conversas
            await loadPrivateConversations();

            newMessage.value = '';
            await nextTick();
            scrollToBottom();
        }
    } catch (error) {
        console.error('Erro ao enviar mensagem:', error);
    } finally {
        isSending.value = false;
    }
};

// MÉTODOS EXISTENTES DE EDIÇÃO (mantendo inalterados)
const cancelEdit = () => {
    editingMessage.value = null;
    editMessageContent.value = '';
};

const updateMessage = async () => {
    if (!editMessageContent.value.trim()) return;
    isSending.value = true;

    try {
        await router.put(route('messages.update', editingMessage.value.slug), {
            content: editMessageContent.value
        }, {
            preserveState: true,
            onSuccess: () => {
                cancelEdit();
            }
        });
    } catch (error) {
        console.error('Erro ao editar mensagem:', error);
    } finally {
        isSending.value = false;
    }
};

const deleteMessage = async (messageId) => {
    if (!confirm('Tem certeza que deseja excluir esta mensagem?')) return;

    try {
        await router.delete(route('messages.destroy', messageId), {
            preserveState: true,
            onSuccess: () => {
                const index = localMessages.value.findIndex(msg => msg.id === messageId);
                if (index !== -1) localMessages.value.splice(index, 1);
            }
        });
    } catch (error) {
        console.error('Erro ao excluir mensagem:', error);
    }
};

const leaveRoom = async () => {
    if (!confirm('Tem certeza que deseja sair desta sala?')) return;

    try {
        await router.delete(route('rooms.leave', props.room.slug));
    } catch (error) {
        console.error('Erro ao sair da sala:', error);
    }
};

// FORMATAÇÃO (mantendo existente)
const formatTime = (timestamp) => {
    return new Date(timestamp).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
};

const formatDate = (timestamp) => {
    return new Date(timestamp).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

// ECHO (adaptado)
let echoChannel = null;
let privateEchoChannel = null;

const cleanupEcho = () => {
    if (echoChannel) {
        window.Echo.leave(`room.${props.room.slug}`);
        echoChannel = null;
        console.log('🧹 Canal público desconectado');
    }

    if (privateEchoChannel) {
        window.Echo.leave(`user.${pageProps.auth.user.id}`);
        privateEchoChannel = null;
        console.log('🧹 Canal privado desconectado');
    }
};

const setupEcho = () => {
    if (!window.Echo) {
        console.error('❌ Laravel Echo não está disponível');
        return;
    }

    console.log('🚀 Configurando Laravel Echo...');

    // Canal do chat público (existente)
    echoChannel = window.Echo.private(`room.${props.room.slug}`)
        .listen('.message.sent', (event) => {
            console.log('📨 Nova mensagem pública via broadcasting:', event);

            if (activeTab.value === 'public') {
                const alreadyExists = localMessages.value.some(msg => msg.id === event.id);
                if (!alreadyExists) {
                    localMessages.value.push(event);
                    scrollToBottom();
                }
            }
        })
        .subscribed(() => {
            console.log('✅ Inscrito no canal público');
            connectionStatus.value = 'connected';
        })
        .error(error => {
            console.error('❌ Erro no canal público:', error);
            connectionStatus.value = 'disconnected';
        });

    // Canal para mensagens privadas
    privateEchoChannel = window.Echo.private(`user.${pageProps.auth.user.id}`)
        .listen('.private-message-sent', (event) => {
            console.log('📨 Nova mensagem privada via broadcasting:', event);

            if (activeTab.value === 'private' &&
                currentPrivateConversation.value?.id === event.message.conversation_id) {
                currentPrivateMessages.value.push(event.message);
                nextTick(() => scrollToBottom());
            }

            // Atualizar lista de conversas
            loadPrivateConversations();
        })
        .subscribed(() => {
            console.log('✅ Inscrito no canal privado');
        })
        .error(error => {
            console.error('❌ Erro no canal privado:', error);
        });

    // Eventos de conexão do Pusher
    if (window.Echo.connector?.pusher?.connection) {
        window.Echo.connector.pusher.connection.bind('connected', () => {
            console.log('✅ Pusher conectado');
            connectionStatus.value = 'connected';
        });

        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            console.log('❌ Pusher desconectado');
            connectionStatus.value = 'disconnected';
        });
    }
};

// CICLO DE VIDA
onMounted(async () => {
    scrollToBottom();
    cleanupEcho();
    setupEcho();

    // Carregar dados para chat privado
    await loadPrivateConversations();
    await loadRoomUsers();
});

onUnmounted(() => {
    cleanupEcho();
});

// WATCHES EXISTENTES
watch(() => props.room.slug, (newSlug) => {
    console.log('🔄 Sala mudou para:', newSlug);
    propsInitialized.value = false;

    // Resetar chat privado ao mudar de sala
    activeTab.value = 'public';
    currentPrivateConversation.value = null;
    currentPrivateMessages.value = [];

    // Recarregar dados
    loadPrivateConversations();
    loadRoomUsers();
});

watchEffect(() => {
    if (!propsInitialized.value && props.messages?.length) {
        localMessages.value = props.messages.map(m => ({ ...m }));
        console.log('✅ Mensagens iniciais carregadas:', localMessages.value);
        propsInitialized.value = true;
        scrollToBottom();
    }
});

// Watch para scroll quando mudar de aba
watch(activeTab, () => {
    nextTick(() => scrollToBottom());
});
</script>
