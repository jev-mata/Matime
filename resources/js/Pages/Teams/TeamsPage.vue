<script setup lang="ts">
import { ref, computed, onMounted } from "vue"
import axios from "axios"

import MultiselectDropdown from "@/packages/ui/src/Input/MultiselectDropdown.vue"
import { UserGroupIcon } from "@heroicons/vue/20/solid"
import { TrashIcon, PencilSquareIcon } from "@heroicons/vue/20/solid"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu"
import Modal from "@/packages/ui/src/Modal.vue"
import type { Project, Team, User } from "../Members.vue"

import { useAppStore } from "@/stores/app"

const store = useAppStore()

const newTeamName = ref("")
const search = ref("")

// search filter based on store.teams
const filteredTeams = computed(() => {
  const term = search.value.toLowerCase().trim()
  return store.teams.filter((team) => team.name.toLowerCase().includes(term))
})

// ----------------------------
// Helpers for multiselect
// ----------------------------
const getKeyFromItem = (item: any) => item.id
const getNameForItem = (item: any) => item.name

// ----------------------------
// Team actions
// ----------------------------
async function createTeam() {
  if (!newTeamName.value.trim()) return
  await axios.post(`/organizations/teams`, { name: newTeamName.value.trim() })
  newTeamName.value = ""
  await store.fetchCurrentOrg()
}

async function assignProject(teamId: string, projectId: string) {
  await axios.post(`/teams/${teamId}/assign-project`, { project_id: projectId })
  await store.fetchCurrentOrg()
}

async function removeProject(team: Team, project: Project) {
  await axios.delete(`/teams/${team.id}/projects/${project.id}`)
  await store.fetchCurrentOrg()
}

async function removeGroup(team: Team) {
  await axios.delete(`/teams/${team.id}`)
  openDelete.value = false
  await store.fetchCurrentOrg()
}

async function updateGroup(team: Team, name: string) {
  await axios.post(`/teams/${team.id}/name/${name}`)
  openEdit.value = false
  await store.fetchCurrentOrg()
}

async function addMemberToTeam(teamId: string, userList: User[]) {
  const userIds = userList.map((u) => u.id)
  await axios.post(`/teams/${teamId}/assign-members`, { user_ids: userIds })
  await store.fetchCurrentOrg()
}

async function removeMemberFromTeam(teamId: string, user: User) {
  await axios.delete(`/teams/${teamId}/members/${user.id}`)
  await store.fetchCurrentOrg()
}

// ----------------------------
// Selections
// ----------------------------
function onUsersChangedFromId(teamId: string) {
  const selectedIds = store.selectedUserIds[teamId] || []
  const selectedUserObjects = store.users.filter((user) =>
    selectedIds.includes(user.id)
  )
  store.selectedUsers[teamId] = selectedUserObjects
  addMemberToTeam(teamId, selectedUserObjects)
}

function onProjectsChangedFromId(teamId: string) {
  const currentIds = store.selectedProjectIds[teamId] || []
  const oldIds = (store.selectedProject[teamId] || []).map((p: any) => p.id)

  const addedIds = currentIds.filter((id) => !oldIds.includes(id))

  const selectedProjectObjects = store.projects.filter((project) =>
    currentIds.includes(project.id)
  )

  store.selectedProject[teamId] = selectedProjectObjects

  addedIds.forEach((projectId) => {
    assignProject(teamId, projectId)
  })
}

// ----------------------------
// Edit & delete modals
// ----------------------------
const editGroupName = ref<string>("")
const openEdit = ref<boolean>(false)
const selectedTeam = ref<Team | null>(null)
const openDelete = ref<boolean>(false)

function openEditModal(team: Team) {
  selectedTeam.value = team
  editGroupName.value = team.name
  openEdit.value = true
}
function openDeleteModal(team: Team) {
  selectedTeam.value = team
  editGroupName.value = team.name
  openDelete.value = true
}

// ----------------------------
// Lifecycle
// ----------------------------
onMounted(() => {
  store.fetchCurrentOrg()
})
</script>

<template>
  <div class="flow-root max-w-[100vw] overflow-x-auto p-4" style="min-height: 80vh">
    <div class="inline-block min-w-full align-middle bg-default-background p-5 rounded-md">
      <h2 class="text-xl font-bold mb-4">Teams</h2>

      <div class="flex mb-4 gap-2">
        <input
          v-model="search"
          placeholder="Search team name..."
          class="border border-gray-300 rounded px-3 py-2 w-full dark:border-[#303F61] dark:bg-[#0F1426] dark:text-[#7D88A1] hover:dark:text-[#BFC7D6]"
        />
      </div>

      <form @submit.prevent="createTeam" class="mb-6 flex gap-2">
        <input
          v-model="newTeamName"
          placeholder="New team name"
          class="border border-gray-300 p-2 rounded flex-1 dark:border-[#303F61] dark:bg-[#0F1426] dark:text-[#7D88A1] hover:dark:text-[#BFC7D6]"
        />
        <button
          class="dark:border-[#303F61] dark:bg-[#2770DB] dark:text-[#FFFFFF] hover:dark:text-[#BFC7D6] px-4 py-2 rounded"
        >
          Add Team
        </button>
      </form>

      <table class="w-full">
        <thead>
          <tr>
            <th class="text-left px-4 py-2">Group</th>
            <th class="text-left px-4 py-2">Members</th>
            <th class="text-left px-4 py-2">Projects</th>
            <th class="text-left px-4 py-2">Managers</th>
            <th class="text-left px-4 py-2">Action</th>
          </tr>
        </thead>
        <tbody class="dark:bg-gray-900 w-full">
          <tr
            v-for="team in filteredTeams"
            :key="team.id"
            class="border border-t-2 border-x-0 border-gray-700 rounded p-3 mb-2 shadow-sm gap-4 w-full"
          >
            <!-- Team info -->
            <td class="px-4 py-2 align-top">
              <div class="font-semibold text-lg">{{ team.name }}</div>
              <div class="flex gap-2">
                <div class="text-sm">
                  {{ team.projects.length }} project{{ team.projects.length !== 1 ? "s" : "" }}
                </div>
                <div class="text-sm">
                  {{ team.users.length }} member{{ team.users.length !== 1 ? "s" : "" }}
                </div>
              </div>
            </td>

            <!-- Members -->
            <td class="px-4 py-2 align-top">
              <MultiselectDropdown
                :model-value="store.selectedUserIds[team.id] || []"
                @update:modelValue="(val) => (store.selectedUserIds[team.id] = val)"
                :items="store.users"
                search-placeholder="Select Users"
                :getKeyFromItem="getKeyFromItem"
                :getNameForItem="getNameForItem"
                @changed="onUsersChangedFromId(team.id)"
              >
                <template #trigger>
                  <div class="text-sm flex border rounded px-3 py-1 w-full">
                    <UserGroupIcon class="w-5 mr-2"></UserGroupIcon>
                    Select Users...
                  </div>
                </template>
              </MultiselectDropdown>

              <div class="flex gap-1 mt-2 flex-wrap">
                <span
                  v-for="user in store.selectedUsers[team.id]"
                  :key="user.id"
                  class="text-xs px-2 py-1 rounded"
                >
                  {{ user.name }}
                </span>
              </div>
            </td>

            <!-- Projects -->
            <td class="px-4 py-2 align-top">
              <MultiselectDropdown
                :model-value="store.selectedProjectIds[team.id] || []"
                @update:modelValue="(val) => (store.selectedProjectIds[team.id] = val)"
                :items="store.projects"
                search-placeholder="Select Projects"
                :getKeyFromItem="getKeyFromItem"
                :getNameForItem="getNameForItem"
                @changed="onProjectsChangedFromId(team.id)"
              >
                <template #trigger>
                  <div class="text-sm flex border rounded px-3 py-1 w-full">
                    <UserGroupIcon class="w-5 mr-2"></UserGroupIcon>
                    Select Projects...
                  </div>
                </template>
              </MultiselectDropdown>

              <div class="flex gap-1 mt-2 flex-wrap">
                <span
                  v-for="project in store.selectedProject[team.id]"
                  :key="project.id"
                  class="text-xs px-2 py-1 rounded"
                >
                  {{ project.name }}
                </span>
              </div>
            </td>

            <!-- Managers -->
            <td class="px-4 py-2 align-top">
              <div class="flex gap-1 mt-2 flex-wrap">
                <span
                  v-for="manager in store.selectedManagers[team.id]"
                  :key="manager.id"
                  class="bg-default-background text-xs px-2 py-1 rounded"
                >
                  {{ manager.name }}
                </span>
              </div>
            </td>

            <!-- Actions -->
            <td class="relative">
              <div class="absolute" style="top: 50%; left: 50%; transform: translate(-50%, -50%)">
                <DropdownMenu>
                  <DropdownMenuTrigger as-child>
                    <button
                      class="text-gray-800 my-auto focus-visible:outline-none focus-visible:bg-card-background rounded-full focus-visible:ring-2 focus-visible:ring-ring focus-visible:opacity-100 hover:bg-card-background group-hover:opacity-100 opacity-60 transition-opacity"
                      :aria-label="'Actions for Group ' + team.name"
                    >
                      <svg
                        class="h-8 w-8 p-1 rounded-full text-gray-800"
                        viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path
                          fill="none"
                          stroke="currentColor"
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="1.5"
                          d="M12 5.92A.96.96 0 1 0 12 4a.96.96 0 0 0 0 1.92m0 7.04a.96.96 0 1 0 0-1.92a.96.96 0 0 0 0 1.92M12 20a.96.96 0 1 0 0-1.92a.96.96 0 0 0 0 1.92"
                        />
                      </svg>
                    </button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent class="min-w-[150px]" align="end">
                    <DropdownMenuItem
                      @click="openEditModal(team)"
                      class="flex items-center space-x-3 cursor-pointer"
                    >
                      <PencilSquareIcon class="w-5 text-icon-active" />
                      <span>Edit</span>
                    </DropdownMenuItem>
                    <DropdownMenuItem
                      @click.prevent="openDeleteModal(team)"
                      class="flex items-center space-x-3 cursor-pointer text-destructive focus:text-destructive"
                    >
                      <TrashIcon class="w-5" />
                      <span>Delete</span>
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Edit Modal -->
  <Modal :show="openEdit" @close="openEdit = false">
    <template #default>
      <div class="p-5">
        <h2 class="text-lg font-bold mb-2">
          Edit Group Name on {{ selectedTeam?.name }}
        </h2>
        <input
          v-model="editGroupName"
          type="text"
          id="GroupName"
          placeholder="Enter a Group Name"
          class="w-full rounded"
        />
      </div>
    </template>

    <template #footer>
      <div class="p-5">
        <button
          @click="openEdit = false"
          class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700"
        >
          Cancel
        </button>
        <button
          @click="selectedTeam && updateGroup(selectedTeam, editGroupName)"
          class="bg-blue-800 text-white px-4 py-2 ml-2 rounded hover:bg-blue-700"
        >
          Save
        </button>
      </div>
    </template>
  </Modal>

  <!-- Delete Modal -->
  <Modal :show="openDelete" @close="openDelete = false">
    <template #default>
      <div class="p-5">
        <h2 class="flex text-xl mb-2">
          Are you sure to delete Group
          <h2 class="ml-2 text-xl font-bold mb-2">{{ selectedTeam?.name }}?</h2>
        </h2>
        <div class="text-md mb-2">This action can't be undone.</div>
      </div>
    </template>

    <template #footer>
      <div class="p-5">
        <button
          @click="openDelete = false"
          class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700"
        >
          Cancel
        </button>
        <button
          @click="selectedTeam && removeGroup(selectedTeam)"
          class="bg-red-800 text-white px-4 py-2 ml-2 rounded hover:bg-red-700"
        >
          Delete
        </button>
      </div>
    </template>
  </Modal>
</template>
