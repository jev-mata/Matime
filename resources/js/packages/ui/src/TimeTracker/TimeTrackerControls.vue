<script setup lang="ts">
import TimeTrackerTagDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerTagDropdown.vue';
import TimeTrackerStartStop from '@/packages/ui/src/TimeTrackerStartStop.vue';
import TimeTrackerRangeSelector from '@/packages/ui/src/TimeTracker/TimeTrackerRangeSelector.vue';
import BillableToggleButton from '@/packages/ui/src/Input/BillableToggleButton.vue';
import TimeTrackerProjectTaskDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import type {
    CreateClientBody,
    CreateProjectBody,
    Project,
    Tag,
    Task,
    TimeEntry,
    Client,
} from '@/packages/api/src';
import { computed, nextTick, ref, watch } from 'vue';
import type { Dayjs } from 'dayjs';
import { useTimeEntriesStore } from '@/utils/useTimeEntries';
import { storeToRefs } from 'pinia';
import { useFocus } from "@vueuse/core";
import { autoUpdate, flip, limitShift, offset, shift, useFloating } from "@floating-ui/vue";
import TimeTrackerRecentlyTrackedEntry from "@/packages/ui/src/TimeTracker/TimeTrackerRecentlyTrackedEntry.vue";
import { useSelectEvents } from "@/packages/ui/src/utils/select";
import { useMediaQuery } from '@vueuse/core'; // or your preferred media query hook

import { TrashIcon } from '@heroicons/vue/24/solid' // or outline
import { useNotificationsStore } from '@/utils/notification';
const notification = useNotificationsStore();
const currentTimeEntry = defineModel<TimeEntry>('currentTimeEntry', {
    required: true,
});
const liveTimer = defineModel<Dayjs | null>('liveTimer', { required: true });

const currentTimeEntryDescriptionInput = ref<HTMLInputElement | null>(null);
const currentTimeEntryProjectInput = ref<HTMLInputElement | null>(null);
const currentTimeEntryTaskInput = ref<HTMLInputElement | null>(null);

const props = defineProps<{
    projects: Project[];
    tasks: Task[];
    tags: Tag[];
    clients: Client[];
    createTag: (name: string) => Promise<Tag | undefined>;
    createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
    isActive: boolean;
    currency: string;
    enableEstimatedTime: boolean;
    canCreateProject: boolean;
}>();

const emit = defineEmits<{
    startTimer: [];
    stopTimer: [];
    updateTimeEntry: [];
    startLiveTimer: [];
    stopLiveTimer: [];
    discard:[isActive:boolean,TimeEntry[]];
    reset:[]
}>();

function updateProject() {
    setBillableDefaultForProject();
    emit('updateTimeEntry');
}

function setAndStartTimer(timeEntry: TimeEntry) {
    setCurrentTimeEntry(timeEntry);
    if (!props.isActive) {
        emit('startTimer');
    }
    else {
        emit('updateTimeEntry');
    }
}

function setCurrentTimeEntry(timeEntry: TimeEntry) {
    currentTimeEntry.value.description = timeEntry.description;
    currentTimeEntry.value.project_id = timeEntry.project_id;
    currentTimeEntry.value.task_id = timeEntry.task_id;
    currentTimeEntry.value.tags = timeEntry.tags;
    currentTimeEntry.value.billable = timeEntry.billable;
}
function clearCurrentTimeEntry() {
    currentTimeEntry.value.description = null;
    currentTimeEntry.value.project_id = null;
    currentTimeEntry.value.task_id = null;
    currentTimeEntry.value.tags = [];
    currentTimeEntry.value.billable = false;
}

function startTimerIfNotActive() {
    if (highlightedDropdownEntryId.value) {
        const timeEntry = filteredRecentlyTrackedTimeEntries.value.find((item) => item.id === highlightedDropdownEntryId.value);
        if (timeEntry) {
            setCurrentTimeEntry(timeEntry);
            showDropdown.value = false;
        }
    }
    else {
        currentTimeEntry.value.description = tempDescription.value;
    }

    if (!props.isActive) {
        emit('startTimer');
    }
    else {
        emit('updateTimeEntry');
    }
}

function setBillableDefaultForProject() {
    const project = props.projects.find(
        (project) => project.id === currentTimeEntry.value.project_id
    );
    if (project) {
        currentTimeEntry.value.billable = project.is_billable;
    }
}
const ProjectOpen = ref(false);
const blockRefocus = ref(false);
function OpenProject(open: boolean) {
    ProjectOpen.value = open;
}
function onToggleButtonPress(newState: boolean) {
    if (newState) {
        emit('startTimer');
        if (!blockRefocus.value) {
            currentTimeEntryDescriptionInput.value?.focus();
        }
    } else {

        if (!tempDescription.value) {

            // currentTimeEntryDescriptionInput.value?.focus();

            notification.addNotification('error', 'Description is Empty', 'Please Input your Description you\'ve working on.');
        } if (!currentTimeEntry.value.project_id) {
            // ProjectOpen.value = true;
            notification.addNotification('error', 'Project is Empty', 'Please Choose Project you\'ve working on.');
        } if (!currentTimeEntry.value.task_id) {
            // ProjectOpen.value = true;
            notification.addNotification('error', 'Task is Empty', 'Please Choose Task you\'ve working on.');
        } else {
            emit('stopTimer');
        }
    }
}

const tempDescription = ref(currentTimeEntry.value.description);
watch(
    () => currentTimeEntry.value.description,
    () => {
        tempDescription.value = currentTimeEntry.value.description;
    }
);

function updateTimeEntryDescription() {
    if (currentTimeEntry.value.description !== tempDescription.value) {
        currentTimeEntry.value.description = tempDescription.value;
        emit('updateTimeEntry');
    }
}

const { timeEntries } = storeToRefs(useTimeEntriesStore());
const filteredRecentlyTrackedTimeEntries = computed(() => {
    // do not include running time entries
    const finishedTimeEntries = timeEntries.value.filter((item) => item.end !== null);

    // filter out duplicates based on description, task, project, tags and billable
    const nonDuplicateTimeEntries = finishedTimeEntries.filter((item, index, self) => {
        return index === self.findIndex((t) => (
            t.description === item.description &&
            t.task_id === item.task_id &&
            t.project_id === item.project_id &&
            t.tags.length === item.tags.length &&
            t.tags.every((tag) => item.tags.includes(tag)) &&
            t.billable === item.billable
        ));
    });

    // filter time entries based on current description
    return nonDuplicateTimeEntries.filter((item) => {
        return item.description
            ?.toLowerCase()
            ?.includes(tempDescription.value?.toLowerCase()?.trim() || '');
    }).slice(0, 5);
});

const showDropdown = ref(false);
const { focused } = useFocus(currentTimeEntryDescriptionInput)

watch(focused, (focused) => {
    nextTick(() => {
        // make sure the click event on the dropdown does not get interrupted
        showDropdown.value = focused

        // make sure that the input does not get refocused after the dropdown is closed
        if (!focused) {
            blockRefocus.value = true;
            setTimeout(() => {
                blockRefocus.value = false;
            }, 100);
        }
    });
});
 

const isMobile = useMediaQuery('(max-width: 640px)');
const floating = ref(null);
const { floatingStyles } = useFloating(currentTimeEntryDescriptionInput, floating, {
    placement: 'bottom-start',
    whileElementsMounted: autoUpdate,
    middleware: [
        offset(10),
        ...(isMobile.value
            ? [] // Don't use shift or flip on mobile
            : [
                shift({
                    limiter: limitShift({ offset: 5 }),
                }),
                flip({
                    fallbackAxisSideDirection: 'start',
                }),
            ]),
    ],
});
const highlightedDropdownEntryId = ref<string | null>(null);
const discardTimeEntry=(isActive:boolean,currentTimeEntry:TimeEntry)=>{
    emit('discard',isActive,[currentTimeEntry]);
    emit('reset');
}
useSelectEvents(filteredRecentlyTrackedTimeEntries,
    highlightedDropdownEntryId,
    (item) => item.id,
    showDropdown)
</script>

<template>
    <div class="flex items-center relative @container " data-testid="dashboard_timer">
        <div
            class="flex grid sm:grid-cols-8 md:grid-cols-8 xl:grid-cols-8 2xl:grid-cols-9  @2xl:flex-row w-full justify-between relative  rounded-lg dark:bg-[#13192B] bg-white  transition shadow">
            <div class=" 2xl:col-span-3 xl:col-span-3 md:col-span-8 sm:col-span-3 flex-1 items-center pr-6   w-full  ">
                <input ref="currentTimeEntryDescriptionInput" v-model="tempDescription"
                    placeholder="What are you working on?" data-testid="time_entry_description"
                    class="w-full rounded-l-lg py-4 sm:py-2.5 px-3.5   @2xl:px-4 text-base   text-text-primary font-medium bg-transparent border-none placeholder-muted focus:ring-0 transition"
                    type="text" required @keydown.enter="startTimerIfNotActive" @keydown.esc="showDropdown = false"
                    @blur="updateTimeEntryDescription" />
                <div v-if="showDropdown && filteredRecentlyTrackedTimeEntries.length > 0" ref="floating"
                    class="z-50 absolute w-full" :style="floatingStyles">
                    <div
                        class="rounded-lg w-full fixed min-w-xl top-0 left-0   overflow-none shadow dark:bg-[#13192B] bg-white border dark:border-[#5D6370]  ">
                        <div class="text-text-tertiary text-xs font-semibold   px-2 py-1.5">
                            Recently Tracked Time Entries
                        </div>
                        <div class="text-text-secondary py-1 px-1.5">
                            <TimeTrackerRecentlyTrackedEntry v-for="timeEntry in filteredRecentlyTrackedTimeEntries"
                                :key="timeEntry.id" :time-entry="timeEntry"
                                :highlighted="highlightedDropdownEntryId === timeEntry.id" :projects="projects"
                                :tasks="tasks" @mousedown="setAndStartTimer(timeEntry)"
                                @mouseenter="highlightedDropdownEntryId = timeEntry.id">
                            </TimeTrackerRecentlyTrackedEntry>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex grid grid-cols-4 2xl:col-span-5 xl:col-span-4 md:col-span-7 sm:col-span-3 items-center truncate  justify-between pl-2 shrink min-w-0">
                <div class="flex flex-1 items-center col-span-2 @2xl:w-auto shrink min-w-0 ">
                    <TimeTrackerProjectTaskDropdown v-model:project="currentTimeEntry.project_id" v-model:task="currentTimeEntry.task_id
                        " :create-client :can-create-project :clients :create-project :currency="currency"
                        :projects="projects" :tasks="tasks" :enable-estimated-time="enableEstimatedTime"
                        class=" border-[#77D36F] truncate bg-[#D4FFD1] hover:bg-[#C1F7B0] dark:border-[#5D6370] dark:bg-[#13192B] dark:hover:bg-[#0C101E] "
                        :setOpen="OpenProject" :open="ProjectOpen" ref="currentTimeEntryProjectInput"
                        @changed="updateProject">
                    </TimeTrackerProjectTaskDropdown>
                </div>
                <div class="flex flex-1  grid grid-cols-2  items-center col-span-1  @2xl:space-x-2 px-2 @2xl:px-4">
                    <TimeTrackerTagDropdown v-model="currentTimeEntry.tags
                        " ref="currentTimeEntryTaskInput" :create-tag :tags="tags" @changed="$emit('updateTimeEntry')">
                    </TimeTrackerTagDropdown>
                    <BillableToggleButton v-model="currentTimeEntry.billable
                        " @changed="$emit('updateTimeEntry')"></BillableToggleButton>
                </div>
                <div class="  flex-1  col-span-1">
                    <TimeTrackerRangeSelector v-model:current-time-entry="currentTimeEntry"
                        v-model:live-timer="liveTimer" @start-live-timer="emit('startLiveTimer')"
                        @stop-live-timer="emit('stopLiveTimer')" @update-timer="emit('updateTimeEntry')"
                        @start-timer="emit('startTimer')" @keydown.enter="
                            startTimerIfNotActive
                        "></TimeTrackerRangeSelector>
                </div>
            </div>
        <div class="flex   2xl:col-span-1 xl:col-span-1 md:col-span-1 sm:col-span-3   absolute sm:relative top-[6px] sm:top-0 right-0">
            <TimeTrackerStartStop :active="isActive" size="large" @changed="onToggleButtonPress"></TimeTrackerStartStop>
            <button v-if="isActive" @click="discardTimeEntry(isActive,currentTimeEntry)" title="Discard Entry"
                class="p-2 rounded-full hover:bg-red-100 text-red-600 w-10 h-10 ml-3 text-center align-center flex">
                <TrashIcon class="flex-1 w-5 h-5" />
            </button>
        </div>
        </div>
    </div>
</template>

<style scoped></style>
