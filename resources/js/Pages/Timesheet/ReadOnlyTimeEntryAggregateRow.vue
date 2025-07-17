<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue'; 
import type {
    Project,
    Tag,
    Task,
    Client,
    Organization,
} from '@/packages/api/src';
import { ref, inject, type ComputedRef, computed } from 'vue';
import {
    formatHumanReadableDuration,
    formatStartEnd,
} from '@/packages/ui/src/utils/time';
import TimeEntryRow from './ReadOnlyTimeEntryRow.vue';
import GroupedItemsCountButton from '@/packages/ui/src/GroupedItemsCountButton.vue';
import type { TimeEntriesGroupedByType } from '@/types/time-entries'; 
import { twMerge } from 'tailwind-merge';
import TagBadge from '@/packages/ui/src/Tag/TagBadge.vue';
import ProjectBadge from '@/packages/ui/src/Project/ProjectBadge.vue';
import { ChevronRightIcon } from '@heroicons/vue/16/solid';
const props = defineProps<{
    timeEntry: TimeEntriesGroupedByType;
    projects: Project[];
    tasks: Task[];
    tags: Tag[];
    clients: Client[];
}>();
const organization = inject<ComputedRef<Organization>>('organization');

const expanded = ref(false);

function timeEntryTags(model: string[]) {
    return props.tags.filter((tag) => model.includes(tag.id));
};

const currentProject = computed(() => {
    return props.projects.find(
        (iteratingProject) => iteratingProject.id === props.timeEntry.timeEntries[0].project_id
    );
});

const currentTask = computed(() => {
    return props.tasks.find(
        (iteratingTasks) => iteratingTasks.id === props.timeEntry.task_id
    );
});

const selectedProjectName = computed(() => {

    return currentProject.value?.name ? currentProject.value.name : 'No Project';
});

const selectedProjectColor = computed(() => {
    return currentProject.value?.color || 'var(--theme-color-icon-default)';
});
</script>

<template>
    <div class="border-b border-default-background-separator bg-row-background min-w-0 transition"
        data-testid="time_entry_row">
        <MainContainer class="min-w-0 ">
            <div class="sm:flex py-1.5 items-center min-w-0 justify-between group">
                <div class="flex space-x-3 items-center min-w-0">
                    <div class="flex items-center min-w-0">
                        <GroupedItemsCountButton :expanded="expanded" @click="expanded = !expanded" class="mx-2 font-bold border-2">
                            {{ timeEntry?.timeEntries?.length }}
                        </GroupedItemsCountButton>
                        <div class="min-w-0 mr-4 font-semibold">
                            {{ timeEntry.description }}
                        </div>
                        <ProjectBadge :color="selectedProjectColor" :border="false" tag="button"
                            @click="expanded = !expanded" :name="selectedProjectName" :class="'focus:border-border-tertiary w-full focus:outline-0 focus:bg-card-background-separator min-w-0 relative w-35'
                                ">
                            <div class="flex items-center lg:space-x-1 min-w-0">
                                <span class="whitespace-nowrap text-xs lg:text-sm">
                                    {{ selectedProjectName }}
                                </span>
                                <ChevronRightIcon v-if="currentTask" class="w-4 lg:w-5 text-text-secondary shrink-0">
                                </ChevronRightIcon>
                                <div v-if="currentTask" class="min-w-0 shrink text-xs lg:text-sm truncate">
                                    {{ currentTask.name }}
                                </div>
                            </div>
                        </ProjectBadge>
                    </div>
                </div>
                <div class="flex items-center font-medium lg:space-x-2">
                    <TagBadge :border="false" size="large"
                        class="border-0 sm:px-1.5 text-icon-default group-focus-within/dropdown:text-text-primary"
                        :name="timeEntryTags(timeEntry.tags).map((tag: Tag) => tag.name).join(', ')
                            "></TagBadge>
                    <div class="flex-1">
                        <button
                            :class="twMerge('hidden lg:block text-text-secondary w-[110px] px-1 py-1.5 bg-transparent text-center hover:bg-card-background rounded-lg border border-transparent hover:border-card-border text-sm font-medium focus-visible:outline-none focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:bg-tertiary', organization?.time_format === '12-hours' ? 'w-[160px]' : 'w-[110px]')"
                            @click="expanded = !expanded">
                            {{ formatStartEnd(timeEntry.start, timeEntry.end, organization?.time_format) }}
                        </button>
                    </div>
                    <button
                        class="text-text-primary min-w-[90px] px-2.5 py-1.5 bg-transparent text-right hover:bg-card-background rounded-lg border border-transparent hover:border-card-border text-sm font-semibold focus-visible:outline-none focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:bg-tertiary"
                        @click="expanded = !expanded">
                        {{
                            formatHumanReadableDuration(
                                timeEntry.duration ?? 0,
                                organization?.interval_format,
                                organization?.number_format
                            )
                        }}
                    </button>
 
                </div>
            </div>
        </MainContainer>
        <div v-if="expanded" class="w-full border-t border-default-background-separator bg-black/15 ">
            <TimeEntryRow class="pl-7" v-for="subEntry in timeEntry.timeEntries" :key="subEntry.id" :projects="projects"
                :tasks="tasks" :clients :tags="tags" :time-entry="subEntry"></TimeEntryRow>
        </div>
    </div>
</template>

<style scoped></style>
