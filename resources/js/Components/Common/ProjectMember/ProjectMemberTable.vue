<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { onMounted, ref, inject, type ComputedRef } from 'vue';
import ProjectMemberTableRow from '@/Components/Common/ProjectMember/ProjectMemberTableRow.vue';
import TableRow from '@/Components/TableRow.vue';
import { UserGroupIcon } from '@heroicons/vue/24/solid';
import ProjectMemberTableHeading from '@/Components/Common/ProjectMember/ProjectMemberTableHeading.vue';
import ProjectMemberCreateModal from '@/Components/Common/ProjectMember/ProjectMemberCreateModal.vue';
import type { ProjectMember } from '@/packages/api/src';
import axios from 'axios';

import type { Organization } from '@/packages/api/src';
import { formatCents } from '@/packages/ui/src/utils/money';
import { capitalizeFirstLetter } from '@/utils/format';
import { getOrganizationCurrencyString } from '@/utils/money';

import { useProjectMembersStore } from '@/utils/useProjectMembers';
import ProjectMemberMoreOptionsDropdown from '@/Components/Common/ProjectMember/ProjectMemberMoreOptionsDropdown.vue';
import ProjectMemberEditModal from '@/Components/Common/ProjectMember/ProjectMemberEditModal.vue';
const { projectId, projectMembers } = defineProps<{
    projectId: string;
    projectMembers: ProjectMember[];
}>();

const organization = inject<ComputedRef<Organization>>('organization');
const createProjectMember = ref(false);

type group = {
    id: string;
    name: string;
    member_id: string;
    project_id: string;
    billable_rate: number | null;
    role: string | null;
};
const projectGroupMember = ref<group[]>([]); // now holds array of groups 

async function getGroupMember() {
    const { data } = await axios.get(`/organizations/projects/teams/${projectId}`);
    projectGroupMember.value = data.team ?? []; // team from your sample response
}
onMounted(() => {
    getGroupMember();
}); 
async function deleteProjectMember(teamid: string) {

    console.log(teamid);
    const url = `/teams/${teamid}/projects/${projectId}`;
    console.log(url);
    const { data } = await axios.delete(url);
    console.log(data);
    reinit();

}
const reinit = () => {
    console.log('ReInit called'); // for debugging
    getGroupMember();
}
function editProjectMember() {
    showEditModal.value = true;
}

const showEditModal = ref(false);
</script>

<template>
    <ProjectMemberCreateModal v-model:show="createProjectMember" :existing-members="projectMembers" @refresh="reinit"
        :project-id="projectId"></ProjectMemberCreateModal>
    <div class="flow-root">
        <div class="inline-block min-w-full align-middle">
            <div data-testid="project_member_table" class="grid min-w-full"
                style="grid-template-columns: 1fr 150px 150px 80px">
                <ProjectMemberTableHeading></ProjectMemberTableHeading>
                <div v-if="projectMembers.length === 0 && projectGroupMember.length === 0"
                    class="col-span-5 py-24 text-center">
                    <UserGroupIcon class="w-8 text-icon-default inline pb-2"></UserGroupIcon>
                    <h3 class="text-text-primary font-semibold">No project members</h3>
                    <p class="pb-5">Add the first project member!</p>
                    <SecondaryButton :icon="PlusIcon" @click="createProjectMember = true">Add a new Project Member
                    </SecondaryButton>
                </div>
                <template v-for="projectMember in projectMembers" :key="projectMember.id">
                    <ProjectMemberTableRow :project-member="projectMember"></ProjectMemberTableRow>
                </template>
                <template v-for="projectMember in projectGroupMember" :key="projectMember.id">

                    <TableRow>
                        <ProjectMemberEditModal v-model:show="showEditModal" :name="projectMember?.name"
                            :project-member="projectMember"></ProjectMemberEditModal>
                        <div
                            class="whitespace-nowrap flex items-center space-x-5 3xl:pl-12 py-4 pr-3 text-sm font-medium text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
                            <span>
                                {{ projectMember?.name }}
                            </span>
                        </div>
                        <div class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary">
                            {{
                                projectMember.billable_rate
                                    ? formatCents(
                                        projectMember.billable_rate,
                                        getOrganizationCurrencyString(),
                                        organization?.currency_format,
                                        organization?.currency_symbol,
                                        organization?.number_format
                                    )
                                    : '--'
                            }}
                        </div>
                        <div class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary">
                            {{ capitalizeFirstLetter('Group') }}
                        </div>
                        <div
                            class="relative whitespace-nowrap flex items-center pl-3 text-right text-sm font-medium sm:pr-0 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
                            <ProjectMemberMoreOptionsDropdown :project-member="projectMember"
                                @delete="deleteProjectMember(projectMember.id)" @editProjectMember="editProjectMember">
                            </ProjectMemberMoreOptionsDropdown>
                        </div>
                    </TableRow>
                </template>
            </div>
        </div>
    </div>
</template>
