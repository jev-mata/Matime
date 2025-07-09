<script>
import axios from 'axios';
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.min.css';

export default {
    components: { Multiselect },
    props: ['organizationId'],
    data() {
        return {
            newTeamName: '',
            currentOrganization: '',
            teams: [],
            projects: [],
            users: [],
            managers: [],
            search: '',
            selectedProject: {},
            selectedUsers: {},
            selectedManagers: {}, // { teamId: [user1, user2, ...] }
        };
    },
    computed: {
        filteredTeams() {
            const term = this.search.toLowerCase().trim();
            return this.teams.filter(team =>
                team.name.toLowerCase().includes(term)
            );
        },
    },
    watch: {
        selectedUsers: {
            deep: true,
        },
        selectedManagers: {
            deep: true,
        },
    },
    methods: {
        onUsersChanged(newVal, team) {
            const oldVal = this.selectedUsers[team.id] || [];

            const added = newVal.filter(user =>
                !oldVal.some(prev => prev.id === user.id)
            );

            const removed = oldVal.filter(user =>
                !newVal.some(curr => curr.id === user.id)
            );

            this.selectedUsers[team.id] = newVal; // update local data

            added.forEach(user => {
                this.addMemberToTeam(team.id, this.selectedUsers[team.id]);
            });

            removed.forEach(user => {
                this.removeMemberFromTeam(team.id, user);
                console.log('Removed:', team.id, user);
            });
        },
        onManagersChanged(newVal, team) {
            const oldVal = this.selectedManagers[team.id] || [];

            const added = newVal.filter(user =>
                !oldVal.some(prev => prev.id === user.id)
            );

            const removed = oldVal.filter(user =>
                !newVal.some(curr => curr.id === user.id)
            );

            this.selectedManagers[team.id] = newVal; // update local data

            added.forEach(user => {
                this.addMemberToTeam(team.id, this.selectedManagers[team.id]);
            });

            removed.forEach(user => {
                this.removeMemberFromTeam(team.id, user);
                console.log('Removed:', team.id, user);
            });
        },
        onProjectChange(newVal, team) {
            const oldVal = this.selectedProject[team.id] || [];

            const added = newVal.filter(user =>
                !oldVal.some(prev => prev.id === user.id)
            );

            const removed = oldVal.filter(user =>
                !newVal.some(curr => curr.id === user.id)
            );

            this.selectedProject[team.id] = newVal; // update local data

            added.forEach(user => {
                console.log('selectedProject:', user);
                this.assignProject(team.id, user.id);
            });

            removed.forEach(project => {

                this.removeProject(team, project)
                console.log('Removed:', team.id, project);
            });
        },
        async fetchCurrentOrg() {
            const res = await axios.post(`/get/current/org`);
            this.currentOrganization = res.data.org;
            this.projects = res.data.projects;
            this.users = res.data.user;
            this.teams = res.data.teams;
            this.managers = [];
            // Initialize selected users per team
            this.teams.forEach(team => {
                this.selectedUsers[team.id] = [...team.users];
            });

            res.data.managers.forEach(manager => {
                this.selectedManagers[manager.id] = [...manager.users];
                this.managers.push(...manager.users);
            });
            this.teams.forEach(team => {
                this.selectedProject[team.id] = [...team.projects];
            });
        },

        async createTeam() {
            if (!this.newTeamName.trim()) return;
            await axios.post(`/organizations/teams`, {
                name: this.newTeamName.trim(),
            });
            this.newTeamName = '';
            await this.fetchCurrentOrg();
        },

        async assignProject(team, projectId) {

            await axios.post(`/teams/${team}/assign-project`, {
                project_id: projectId,
            });
            await this.fetchCurrentOrg();
        },

        async removeProject(team, project) {
            await axios.delete(`/teams/${team.id}/projects/${project.id}`);
            await this.fetchCurrentOrg();
        },

        async assignMembers(team) {
            const userIds = (this.selectedUsers[team.id] || []).map(u => u.id);
            await axios.post(`/teams/${team.id}/assign-members`, {
                user_ids: userIds,
            });
            await this.fetchCurrentOrg();
        },

        async addMemberToTeam(teamId, user) {

            const userIds = user.map(u => u.id);
            console.log('addMemberToTeam', teamId, user.id);
            await axios.post(`/teams/${teamId}/assign-members`, {
                user_ids: userIds,
            });
            await this.fetchCurrentOrg();
        },

        async removeMemberFromTeam(teamId, user) {
            await axios.delete(`/teams/${teamId}/members/${user.id}`);
            await this.fetchCurrentOrg();
        },
    },
    mounted() {
        this.fetchCurrentOrg();
    },
};
</script>

<style scoped>
.multiselect {
    --tw-bg-opacity: 1;
    background-color: #1f2937 !important;
    /* Tailwind's bg-gray-800 */
    /* gray-800 */
    /* gray-600 */
    color: white;
    border-radius: 0.375rem;
}

.multiselect__control {
    background-color: #1f2937 !important;
    /* Tailwind's bg-gray-800 */
    border-color: #4b5563;
    /* Tailwind's border-gray-600 */
    color: #d1d5db;
    /* Tailwind's text-gray-300 */
}

.multiselect__content-wrapper {
    background-color: #1f2937 !important;
    /* dropdown */
    color: #d1d5db;
    /* dropdown items text color */
}

.multiselect__option--highlight {
    background-color: #2563eb !important;
    /* Tailwind blue-600 */
    color: white;
}
</style>
<style>
/* Force override styles */
.multiselect {
    background-color: #1f2937 !important;
    /* bg-gray-800 */
    border-color: #4b5563 !important;
    /* border-gray-600 */
    color: #d1d5db !important;
    /* text-gray-300 */
}

.multiselect__tag {

    background-color: #094d00 !important;
}

.multiselect__tag-icon::after {

    color: #ffffff !important;
}

.multiselect__input,
.multiselect__single,
.multiselect__tags,
.multiselect__option {
    background-color: #1f2937 !important;
    color: #d1d5db !important;
    border-color: #1f2937 !important;
}

.multiselect__content-wrapper {
    background-color: #1f2937 !important;
    border-color: #4b5563 !important;
}

/* Hide the dropdown arrow */
.no-arrow .multiselect__select {
    display: none;
}

/* Hide the 'x' on each selected tag */
.no-remove .multiselect__tag-icon {
    display: none;
    pointer-events: none;
}
.bg-panel {

    background-color: #04070c !important;
}
.visually-enabled.multiselect--disabled {
    background-color: white;
    opacity: 1;
    pointer-events: none; /* Still prevents interaction */ 
    color: #111827; /* Optional: normal text */
}

/* Optional: fix the cursor */
.visually-enabled.multiselect--disabled .multiselect__single,
.visually-enabled.multiselect--disabled .multiselect__tags {
    cursor: default;
    color: #111827; /* Normal dark text */
}
</style> 
<template>
    <div class="flow-root max-w-[100vw] overflow-x-auto p-4" style="min-height: 80vh;">
        <div class="inline-block min-w-full align-middle bg-panel p-5 rounded-md">
            <h2 class="text-xl font-bold mb-4">Teams</h2>

            <!-- Search -->
            <div class="flex mb-4 gap-2">
                <input v-model="search" placeholder="Search team name..."
                    class="border border-gray-600 rounded px-3 py-2 w-full bg-gray-800" />
            </div>

            <!-- Create Team Form -->
            <form @submit.prevent="createTeam" class="mb-6 flex gap-2">
                <input v-model="newTeamName" placeholder="New team name"
                    class="border border-gray-600 p-2 rounded flex-1 bg-gray-800" />
                <button class="bg-blue-600 text-white px-4 py-2 rounded">Add Team</button>
            </form>

            <!-- Teams List -->
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="text-left px-4 py-2">Name</th>
                        <th class="text-left px-4 py-2">Access</th>
                        <th class="text-left px-4 py-2">Managers</th>
                    </tr>
                </thead>
                <tbody class="bg-gray-900  w-full">


                    <tr v-for="team in filteredTeams" :key="team.id"
                        class="border border-t-2 border-x-0 border-gray-700 rounded p-3 mb-2 shadow-sm   gap-4 w-full">

                        <td class="px-4 py-2 align-top">
                            <div class="flex-1">
                                <div class="font-semibold text-lg">{{ team.name }}</div>

                                <!-- <div class="mt-2 flex items-center gap-2 text-gray-300">
                            <select v-model="selectedProject[team.id]"
                                class="border rounded px-2 py-1 bg-gray-700 text-gray-300">
                                <option disabled value="">Assign project</option>
                                <option v-for="project in projects" :value="project.id" :key="project.id">
                                    {{ project.name }}
                                </option>
                            </select>
                            <button @click="assignProject(team)" class="bg-green-600 text-white px-3 py-1 rounded">
                                Assign
                            </button>
                        </div> -->
                                <div class="flex gap-2">
                                    <div class="  text-sm text-gray-400">
                                        {{ team.projects.length }} project{{ team.projects.length !== 1 ? 's' : '' }}
                                    </div>
                                    <div class=" text-sm text-gray-400">
                                        {{ team.users.length }} member{{ team.users.length !== 1 ? 's' : '' }}
                                    </div>
                                </div>


                                <!-- <div class="mt-3">
                            <label class="text-sm font-medium">Assigned Projects:</label>
                            <ul class="list-disc list-inside mt-1">
                                <li v-for="project in team.projects" :key="project.id"
                                    class="flex items-center justify-between">
                                    {{ project.name }}
                                    <button @click="removeProject(team, project)"
                                        class="ml-2 text-red-500 hover:underline text-sm">
                                        Remove
                                    </button>
                                </li>
                            </ul>
                        </div> -->
                            </div>
                        </td>
                        <!-- <div>
                        <label class="text-sm font-medium">Assign Projects:</label>
                        <Multiselect :model-value="selectedProject[team.id]"
                            @update:modelValue="onProjectChange($event, team)" :options="projects" :multiple="true"
                            :close-on-select="false" :clear-on-select="false" :preserve-search="true"
                            placeholder="Search and select projects" label="name" track-by="id" class="mt-1" />

 
                    </div> -->
                        <td class="px-4 py-2 align-top">
                            <div>
                                <Multiselect :model-value="selectedUsers[team.id]"
                                    @update:modelValue="onUsersChanged($event, team)" :options="users" :multiple="true"
                                    :close-on-select="false" :clear-on-select="false" :preserve-search="true"
                                    placeholder="Search and select users" label="name" track-by="id" class="mt-1" />


                                <!-- Optional Save Button -->
                            </div>
                        </td>

                        <td class="px-4 py-2 align-top">
                            <Multiselect :model-value="selectedManagers[team.id]"
                                @update:modelValue="onUsersChanged($event, team)" :options="managers" :multiple="true"
                                :close-on-select="false" :clear-on-select="false" :preserve-search="true"
                                placeholder="No Manager Assigned" label="name" track-by="id" :disabled="true"
                                class="mt-1 no-arrow no-remove visually-enabled" />


                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
