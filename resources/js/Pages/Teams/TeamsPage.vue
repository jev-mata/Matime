<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted, h } from 'vue';
import axios from 'axios';

import MultiselectDropdown from '@/packages/ui/src/Input/MultiselectDropdown.vue';
import { FolderIcon, UserGroupIcon } from '@heroicons/vue/20/solid';

import {
    TrashIcon,
    PencilSquareIcon,
} from '@heroicons/vue/20/solid';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import Modal from '@/packages/ui/src/Modal.vue';
const props = defineProps<{ organizationId: string }>();
interface User {
    id: string;
    name: string;
    // add more if needed
}

interface Project {
    id: string;
    name: string;
    // add more if needed
}

interface Team {
    id: string;
    name: string;
    users: User[];
    projects: Project[];
}

interface Manager {
    id: string;
    users: User[];
}

const users = ref<User[]>([]);
const projects = ref<Project[]>([]);
const teams = ref<Team[]>([]);
const managers = ref<User[]>([]);

const newTeamName = ref('');
const currentOrganization = ref('');
const search = ref('');

const selectedProject = reactive<Record<string, any[]>>({});
const selectedUsers = reactive<Record<string, any[]>>({});
const selectedManagers = reactive<Record<string, any[]>>({});
const selectedUserIds = reactive<Record<string, string[]>>({});

const selectedProjectIds = reactive<Record<string, string[]>>({});

const filteredTeams = computed(() => {
    const term = search.value.toLowerCase().trim();
    return teams.value.filter(team => team.name.toLowerCase().includes(term));
});

function onUsersChangedFromId(teamId: string) {
    const selectedIds = selectedUserIds[teamId] || [];
    const selectedUserObjects = users.value.filter(user => selectedIds.includes(user.id));
    selectedUsers[teamId] = selectedUserObjects;
    addMemberToTeam(teamId, selectedUserObjects);
}
function onProjectsChangedFromId(teamId: string) {
    const currentIds = selectedProjectIds[teamId] || [];
    const oldIds = (selectedProject[teamId] || []).map((p: any) => p.id);

    const addedIds = currentIds.filter(id => !oldIds.includes(id));

    // Convert current project IDs to full objects for reactivity
    const selectedProjectObjects = projects.value.filter(project => currentIds.includes(project.id));

    // Update the reactive object
    selectedProject[teamId] = selectedProjectObjects;

    // Assign only new projects
    addedIds.forEach(projectId => {
        assignProject(teamId, projectId);
    });
}
async function fetchCurrentOrg() {
    const res = await axios.post(`/get/current/org`);
    currentOrganization.value = res.data.org;
    projects.value = res.data.projects;
    users.value = res.data.user;
    teams.value = res.data.teams;
    managers.value = [];

    teams.value.forEach(team => {
        selectedUsers[team.id] = [...team.users];
        selectedUserIds[team.id] = team.users.map(u => u.id);

        selectedProjectIds[team.id] = team.projects.map(u => u.id);
        selectedProject[team.id] = [...team.projects];
    });

    res.data.managers.forEach((manager: Manager) => {
        selectedManagers[manager.id] = [...manager.users];
        managers.value.push(...manager.users);
    });
}

async function createTeam() {
    if (!newTeamName.value.trim()) return;
    await axios.post(`/organizations/teams`, { name: newTeamName.value.trim() });
    newTeamName.value = '';
    await fetchCurrentOrg();
}

async function assignProject(teamId: string, projectId: string) {
    await axios.post(`/teams/${teamId}/assign-project`, { project_id: projectId });
    await fetchCurrentOrg();
}

async function removeProject(team: any, project: any) {
    await axios.delete(`/teams/${team.id}/projects/${project.id}`);
    await fetchCurrentOrg();
}

async function removeGroup(team: any) {
    await axios.delete(`/teams/${team.id}`);
    await fetchCurrentOrg();
}
async function addMemberToTeam(teamId: string, userList: any[]) {
    const userIds = userList.map(u => u.id);
    await axios.post(`/teams/${teamId}/assign-members`, { user_ids: userIds });
    await fetchCurrentOrg();
}

async function removeMemberFromTeam(teamId: string, user: any) {
    await axios.delete(`/teams/${teamId}/members/${user.id}`);
    await fetchCurrentOrg();
}

onMounted(() => {
    fetchCurrentOrg();
});

const getKeyFromItem = (item: any) => item.id;
const getNameForItem = (item: any) => item.name;

const openEdit = ref(false)
const openDelete = ref(false)
</script>

<template>
    <div class="flow-root max-w-[100vw] overflow-x-auto p-4" style="min-height: 80vh;">
        <div class="inline-block min-w-full align-middle darK:bg-panel bg-neutral-100 p-5 rounded-md">
            <h2 class="text-xl font-bold mb-4">Teams</h2>

            <div class="flex mb-4 gap-2">
                <input v-model="search" placeholder="Search team name..."
                    class="border dark:border-gray-600 border-gray-300 rounded px-3 py-2 w-full dark:bg-gray-800" />
            </div>

            <form @submit.prevent="createTeam" class="mb-6 flex gap-2">
                <input v-model="newTeamName" placeholder="New team name"
                    class="border dark:border-gray-600 border-gray-300  p-2 rounded flex-1 dark:bg-gray-800" />
                <button class="bg-blue-600 text-white px-4 py-2 rounded">Add Team</button>
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
                <tbody class="dark:bg-gray-900  w-full">
                    <tr v-for="team in filteredTeams" :key="team.id"
                        class="border border-t-2 border-x-0 border-gray-700 rounded p-3 mb-2 shadow-sm gap-4 w-full">

                        <td class="px-4 py-2 align-top">
                            <div class="font-semibold text-lg">{{ team.name }}</div>
                            <div class="flex gap-2">
                                <div class="text-sm text-gray-400">
                                    {{ team.projects.length }} project{{ team.projects.length !== 1 ? 's' : '' }}
                                </div>
                                <div class="text-sm text-gray-400">
                                    {{ team.users.length }} member{{ team.users.length !== 1 ? 's' : '' }}
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-2 align-top">
                            <MultiselectDropdown :model-value="selectedUserIds[team.id] || []"
                                @update:modelValue="val => selectedUserIds[team.id] = val" :items="users"
                                search-placeholder="Select users" :getKeyFromItem="getKeyFromItem"
                                :getNameForItem="getNameForItem" @changed="onUsersChangedFromId(team.id)">
                                <template #trigger>
                                    <div
                                        class="text-sm flex dark:text-gray-400 border dark:border-gray-600 border-gray-400 rounded px-3 py-1 dark:bg-gray-800 w-full">
                                        <UserGroupIcon class="w-5 mr-2"></UserGroupIcon>
                                        Select users...
                                    </div>
                                </template>
                            </MultiselectDropdown>

                            <div class="flex gap-1 mt-2 flex-wrap">
                                <span v-for="user in selectedUsers[team.id]" :key="user.id"
                                    class="dark:bg-gray-700 bg-gray-300 dark:text-gray-200 text-gray-900 text-xs px-2 py-1 rounded">
                                    {{ user.name }}
                                </span>
                            </div>
                        </td>

                        <td class="px-4 py-2 align-top">
                            <MultiselectDropdown :model-value="selectedProjectIds[team.id] || []"
                                @update:modelValue="val => selectedProjectIds[team.id] = val" :items="projects"
                                search-placeholder="Select users" :getKeyFromItem="getKeyFromItem"
                                :getNameForItem="getNameForItem" @changed="onProjectsChangedFromId(team.id)">
                                <template #trigger>
                                    <div
                                        class="text-sm flex dark:text-gray-400 border dark:border-gray-600 border-gray-400 rounded px-3 py-1 dark:bg-gray-800 w-full">
                                        <UserGroupIcon class="w-5 mr-2"></UserGroupIcon>
                                        Select Projects...
                                    </div>
                                </template>
                            </MultiselectDropdown>

                            <div class="flex gap-1 mt-2 flex-wrap">
                                <span v-for="project in selectedProject[team.id]" :key="project.id"
                                    class="dark:bg-gray-700 dark:text-gray-200  bg-gray-300 text-gray-900 text-xs px-2 py-1 rounded">
                                    {{ project.name }}
                                </span>
                            </div>
                        </td>

                        <td class="px-4 py-2 align-top">
                            <!-- <MultiselectDropdown :model-value="selectedManagers[team.id]?.map(u => u.id)"
                                :items="managers" :getKeyFromItem="getKeyFromItem" :getNameForItem="getNameForItem"
                                search-placeholder="Search managers" /> -->

                            <div class="flex gap-1 mt-2 flex-wrap">
                                <span v-for="manager in selectedManagers[team.id]" :key="manager.id"
                                    class="dark:bg-gray-700 dark:text-gray-200  bg-gray-300 text-gray-900 text-xs px-2 py-1 rounded">
                                    {{ manager.name }}
                                </span>
                            </div>
                        </td>

                        <td class="relative">
                            <div class="absolute" style="top:50%;left:50%; transform: translate(-50%,-50%);">
                                <DropdownMenu class="">
                                    <DropdownMenuTrigger as-child>
                                        <button
                                            class="dark:text-gray-200 text-gray-800 my-auto focus-visible:outline-none focus-visible:bg-card-background rounded-full focus-visible:ring-2 focus-visible:ring-ring focus-visible:opacity-100 hover:bg-card-background group-hover:opacity-100 opacity-60 transition-opacity"
                                            :aria-label="'Actions for Group ' + team.name">
                                            <svg class="h-8 w-8 p-1 rounded-full dark:text-gray-200 text-gray-800"
                                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path fill="none" stroke="currentColor" stroke-linecap="round"
                                                    stroke-linejoin="round" stroke-width="1.5"
                                                    d="M12 5.92A.96.96 0 1 0 12 4a.96.96 0 0 0 0 1.92m0 7.04a.96.96 0 1 0 0-1.92a.96.96 0 0 0 0 1.92M12 20a.96.96 0 1 0 0-1.92a.96.96 0 0 0 0 1.92" />
                                            </svg>
                                        </button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent class="min-w-[150px]" align="end">
                                        <DropdownMenuItem :aria-label="'Edit Group ' + team.name"
                                            data-testid="project_edit" @click="openEdit = true"
                                            class="flex items-center space-x-3 cursor-pointer">
                                            <PencilSquareIcon class="w-5 text-icon-active" />
                                            <span>Edit</span>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem :aria-label="'Delete Group ' + team.name"
                                            data-testid="project_delete" @click.prevent="removeGroup(team)"
                                            class="flex items-center space-x-3 cursor-pointer text-destructive focus:text-destructive">
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

    <Modal :show="openEdit" @close="openEdit = false">
        <template #default>
            <div>

                <h2 class="text-lg font-bold mb-2">Hello from Modal!</h2>
                <p class="text-gray-600 dark:text-gray-300">
                    This is a simple modal using the Dialog wrapper.
                </p>
            </div>
        </template>

        <template #footer>
            <button @click="openEdit = false" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700">
                Close
            </button>
        </template>
    </Modal>
</template>
