<template>
  <div class="bg-white text-black rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold mb-4">Gerenciar Usuários da Sala</h3>

    <!-- Adicionar usuário por email -->
    <div class="mb-6">
      <h4 class="text-md text-black font-medium mb-2">Adicionar Usuário</h4>
      <form @submit.prevent="addUserByEmail" class="flex gap-2">
        <input
          v-model="newUserEmail"
          type="email"
          placeholder="Email do usuário"
          class="flex-1 text-black px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          required
        />
        <button
          type="submit"
          :disabled="isLoading"
          class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
        >
          {{ isLoading ? 'Adicionando...' : 'Adicionar' }}
        </button>
      </form>
      <div v-if="addUserError" class="text-red-600 text-sm mt-1">
        {{ addUserError }}
      </div>
      <div v-if="addUserSuccess" class="text-green-600 text-sm mt-1">
        {{ addUserSuccess }}
      </div>
    </div>

    <!-- Lista de usuários na sala -->
    <div>
      <h4 class="text-md font-medium mb-2">Usuários na Sala ({{ room.users.length }})</h4>
      <div class="space-y-2">
        <div
          v-for="user in room.users"
          :key="user.id"
          class="flex items-center justify-between p-3 bg-gray-50 rounded-md"
        >
          <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium">
              {{ user.name.charAt(0).toUpperCase() }}
            </div>
            <div>
              <p class="font-medium">{{ user.name }}</p>
              <p class="text-sm text-gray-600">{{ user.email }}</p>
              <p v-if="user.id === room.created_by" class="text-xs text-blue-600 font-medium">
                Criador da sala
              </p>
            </div>
          </div>

          <!-- Botão de remover (apenas para não-criadores) -->
          <button
            v-if="user.id !== room.created_by && canManageUsers"
            @click="removeUser(user.id)"
            class="px-3 py-1 text-red-600 hover:bg-red-50 rounded-md text-sm"
            :disabled="isLoading"
          >
            Remover
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de confirmação para remoção -->
    <div
      v-if="showRemoveModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white  rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Confirmar Remoção</h3>
        <p class="text-gray-600 mb-6">
          Tem certeza que deseja remover este usuário da sala?
        </p>
        <div class="flex justify-end space-x-3">
          <button
            @click="showRemoveModal = false"
            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-md"
          >
            Cancelar
          </button>
          <button
            @click="confirmRemoveUser"
            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
            :disabled="isLoading"
          >
            {{ isLoading ? 'Removendo...' : 'Remover' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3';

const props = defineProps({
  room: {
    type: Object,
    required: true
  }
})
const { props: pageProps } = usePage()

const newUserEmail = ref('')
const isLoading = ref(false)
const addUserError = ref('')
const addUserSuccess = ref('')
const showRemoveModal = ref(false)
const userToRemove = ref(null)

// Verifica se o usuário atual pode gerenciar usuários (é o criador)
const canManageUsers = computed(() => {
  return props.room.created_by === pageProps.auth.user.id
})

const addUserByEmail = async () => {
  if (!newUserEmail.value.trim()) return

  isLoading.value = true
  addUserError.value = ''
  addUserSuccess.value = ''

  try {
    await router.post(route('rooms.addUserByEmail', { room: props.room.slug }), {
      email: newUserEmail.value
    }, {
      preserveState: true,
      onSuccess: () => {
        newUserEmail.value = ''
        addUserSuccess.value = 'Usuário adicionado com sucesso!'
        setTimeout(() => {
          addUserSuccess.value = ''
        }, 3000)
      },
      onError: (errors) => {
        addUserError.value = errors.email || 'Erro ao adicionar usuário'
      }
    })
  } catch (error) {
    addUserError.value = 'Erro ao adicionar usuário'
  } finally {
    isLoading.value = false
  }
}

const removeUser = (userId) => {
  userToRemove.value = userId
  showRemoveModal.value = true
}

const confirmRemoveUser = async () => {
  if (!userToRemove.value) return

  isLoading.value = true

  try {
    await router.delete(route('rooms.removeUser', {
      room: props.room.id,
      userId: userToRemove.value
    }), {
      preserveState: true,
      onSuccess: () => {
        showRemoveModal.value = false
        userToRemove.value = null
      }
    })
  } catch (error) {
    console.error('Erro ao remover usuário:', error)
  } finally {
    isLoading.value = false
  }
}
onMounted(() => {
    console.log('ID da sala:', props.room.slug)
    console.log('URL da rota:', route('rooms.addUserByEmail', props.room.slug))
})
</script>

