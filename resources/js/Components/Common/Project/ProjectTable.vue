<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { FolderPlusIcon } from '@heroicons/vue/24/solid';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { computed, ref, onMounted, watch } from 'vue';
import ProjectCreateModal from '@/packages/ui/src/Project/ProjectCreateModal.vue';
import ProjectTableHeading from '@/Components/Common/Project/ProjectTableHeading.vue';
import ProjectTableRow from '@/Components/Common/Project/ProjectTableRow.vue';
import { canCreateProjects } from '@/utils/permissions';
import type {
    CreateProjectBody,
    Project,
    Client,
    CreateClientBody,
} from '@/packages/api/src';
import { useProjectsStore } from '@/utils/useProjects';
import { useClientsStore } from '@/utils/useClients';
import { storeToRefs } from 'pinia';
import { getOrganizationCurrencyString } from '@/utils/money';
 
import TableRow from '@/Components/TableRow.vue';
const props = defineProps<{
    projects: Project[];
    showBillableRate: boolean;
}>();

const showCreateProjectModal = ref(false);
async function createProject(
    project: CreateProjectBody
): Promise<Project | undefined> {
    return await useProjectsStore().createProject(project);
}

async function createClient(
    client: CreateClientBody
): Promise<Client | undefined> {
    return await useClientsStore().createClient(client);
}
const { clients } = storeToRefs(useClientsStore());
const gridTemplate = computed(() => {
    return `grid-template-columns: minmax(300px, 1fr) minmax(150px, auto) minmax(100px, auto) minmax(100px, auto) ${props.showBillableRate ? 'minmax(70px, auto)' : ''} minmax(120px, auto) 80px;`;
});
import { isAllowedToPerformPremiumAction } from '@/utils/billing';

watch(
    () => props.projects,
    (newVal) => {
        
    },
    { deep: true, immediate: false }
);

const getClient = (clientID: string) => {
    return clients.value.find(
        (client) => client.id === clientID
    );
}


</script>

<template>
    <ProjectCreateModal v-model:show="showCreateProjectModal" :create-project :create-client
        :currency="getOrganizationCurrencyString()" :clients="clients"
        :enable-estimated-time="isAllowedToPerformPremiumAction"></ProjectCreateModal>
    <div class="flow-root  overflow-x-hidden">
        <div class="inline-block min-w-full align-middle">
            <div data-testid="project_table" class="grid min-w-full" :style="gridTemplate">
                <ProjectTableHeading :show-billable-rate="props.showBillableRate
                    "></ProjectTableHeading>
                <div v-if="projects.length === 0" class="col-span-5 py-24 text-center">
                    <FolderPlusIcon class="w-8 text-icon-default inline pb-2"></FolderPlusIcon>
                    <h3 class="text-text-primary font-semibold">
                        {{
                            canCreateProjects()
                                ? 'No projects found'
                                : 'You are not a member of any projects'
                        }}
                    </h3>
                    <p class="pb-5 max-w-md mx-auto text-sm pt-1">
                        {{
                            canCreateProjects()
                                ? 'Create your first project now!'
                                : 'Ask your manager to add you to a project as a team member.'
                        }}
                    </p>
                    <SecondaryButton v-if="canCreateProjects()" :icon="PlusIcon" @click="showCreateProjectModal = true">
                        Create your First Project
                    </SecondaryButton>
                </div>
                <template v-for="(project, index) in projects" :key="project.id" class="flex w-full flex-row">
                    <TableRow  class="w-full mt-5" v-if="project?.client_id !== projects[index - 1]?.client_id && !isAllowedToPerformPremiumAction()">
                        <div
                            class="whitespace-nowrap min-w-0 flex items-center space-x-5 3xl:pl-12 py-4 pr-3 text-sm font-medium text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
                            <div v-if="project.client_id" class="overflow-ellipsis overflow-hidden">
                                <div class="whitespace-nowrap min-w-0 px-3 py-4 text-sm text-text-secondary">
                                    <div v-if="getClient(project?.client_id)" class="overflow-ellipsis overflow-hidden">
                                        {{ getClient(project?.client_id)?.name }}
                                    </div>
                                    <div v-else>No client</div>
                                </div>
                            </div>
                        </div> 

                        <div class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary">
                        </div>
                        <div class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary"></div>

                        <div class="whitespace-nowrap px-3 flex items-center text-sm text-text-secondary"></div>
                        <div
                            class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary flex space-x-1 items-center font-medium">
                        </div>

                        <div
                            class="relative whitespace-nowrap flex items-center pl-3 text-right text-sm font-medium pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
                        </div>

                    </TableRow>

                    <ProjectTableRow :show-billable-rate="props.showBillableRate" :project="project" />
                </template>

            </div>
        </div>
    </div>
</template>
