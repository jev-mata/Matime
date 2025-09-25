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
import BillableToggleButton from '@/packages/ui/src/Input/BillableToggleButton.vue';
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
    <div class="border-b border-[#2c354f] bg-row-background min-w-0 transition"
        data-testid="time_entry_row">
        <MainContainer class="min-w-0 dark:bg-[#171e31]">
            <div class="grid 2xl:grid-cols-12  xl:grid-cols-12 lg:grid-cols-6 md:grid-cols-6 sm:grid-cols-6 xs:grid-cols-6 py-2 min-w-0 items-center justify-between group">
                <div
                    class=" flex 2xl:col-span-3 xl:col-span-3 lg:col-span-3 md:col-span-3 sm:col-span-6 xs:col-span-6 truncate text-ellipsis whitespace-nowrap overflow-hidden px-0 h-full min-w-0 pl-3 pr-1 left-0 top-0  text-sm text-text-primary font-medium bg-transparent focus-visible:ring-0 rounded-lg border-0">
                    <GroupedItemsCountButton :expanded="expanded" @click="expanded = !expanded"
                        class="mx-2 font-bold border-2">
                        {{ timeEntry?.timeEntries?.length }}
                    </GroupedItemsCountButton>
                    <div class="min-w-0 truncate font-semibold">
                        {{ timeEntry.description }}
                    </div>
                </div>
                <ProjectBadge :color="selectedProjectColor" :border="false" tag="button" @click="expanded = !expanded"
                    :name="selectedProjectName" :class="'2xl:col-span-4 xl:col-span-4 lg:col-span-3 md:col-span-3 sm:col-span-6 xs:col-span-6 focus:border-border-tertiary focus:outline-0 focus:bg-card-background-separator min-w-0 relative'
                        ">
                    <div class="flex  items-center lg:space-x-1 min-w-0">
                        <span class="whitespace-nowrap text-xs lg:text-sm text-primary">
                            {{ selectedProjectName }}
                        </span>
                        <ChevronRightIcon v-if="currentTask" class="w-4 lg:w-5 text-text-secondary shrink-0">
                        </ChevronRightIcon>
                        <div v-if="currentTask" class="min-w-0 shrink text-xs lg:text-sm truncate">
                            {{ currentTask.name }}
                        </div>
                    </div>
                </ProjectBadge>

                <div class="grid grid-cols-7   2xl:col-span-3   xl:col-span-2 lg:col-span-4 md:col-span-4 sm:col-span-3 xs:col-span-3">
                    <div :border="false" :title="timeEntryTags(timeEntry.tags).map((tag: Tag) => tag.name).join(', ')"
                         size="large"
                        class="border-0 col-span-6 px-2 text-sm truncate sm:px-1.5 text-icon-default group-focus-within/dropdown:text-text-primary">
                        {{timeEntryTags(timeEntry.tags).map((tag: Tag) => tag.name).join(', ')}}</div>


                    <BillableToggleButton v-if="timeEntry.billable" :model-value="timeEntry.billable"
                        :class="twMerge('opacity-50 col-span-1  group-hover:opacity-100 focus-visible:opacity-100')"
                        size="small">
                    </BillableToggleButton>
                </div>
              
                <div class="  grid grid-cols-8 2xl:col-span-2 xl:col-span-3 lg:col-span-2 md:col-span-2  sm:col-span-3 xs:col-span-3 font-medium  ">
                <div :class="twMerge('col-span-6  ')" @click="expanded = !expanded">
                        {{ formatStartEnd(timeEntry.start, timeEntry.end, organization?.time_format) }}
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
