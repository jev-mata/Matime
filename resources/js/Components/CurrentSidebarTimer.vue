<script setup lang="ts">
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { storeToRefs } from 'pinia';
import { computed } from 'vue';
import dayjs from 'dayjs';
import { formatDuration } from '@/packages/ui/src/utils/time';
import TimeTrackerStartStop from '@/packages/ui/src/TimeTrackerStartStop.vue';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { TrashIcon } from '@heroicons/vue/24/solid' // or outline

import { useTimeEntriesStore } from '@/utils/useTimeEntries';
import { useNotificationsStore } from '@/utils/notification';
import type { TimeEntry } from '@/packages/api/src';
const notification = useNotificationsStore();

const store = useCurrentTimeEntryStore();
const { currentTimeEntry, now, isActive } = storeToRefs(store);
const { setActiveState } = store;

const currentTime = computed(() => {
    if (now.value && currentTimeEntry.value.start) {
        const startTime = dayjs(currentTimeEntry.value.start);
        const diff = now.value.diff(startTime, 's');
        return formatDuration(diff);
    }
    return formatDuration(0);
});

const isRunningInDifferentOrganization = computed(() => {
    return (
        currentTimeEntry.value.organization_id &&
        getCurrentOrganizationId() &&
        currentTimeEntry.value.organization_id !== getCurrentOrganizationId()
    );
});
function toggleState() {
    if (isActive) {


        if (!currentTimeEntry.value.description) {

            // currentTimeEntryDescriptionInput.value?.focus();

            notification.addNotification('error', 'Description is Empty', 'Please Input your Description you\'ve working on.');
        } if (!currentTimeEntry.value.project_id) {
            // ProjectOpen.value = true;
            notification.addNotification('error', 'Project is Empty', 'Please Choose Project you\'ve working on.');
        } if (!currentTimeEntry.value.task_id) {
            // ProjectOpen.value = true;
            notification.addNotification('error', 'Task is Empty', 'Please Choose Task you\'ve working on.');
        } else {

            setActiveState(!isActive);
        }
    } else {

        setActiveState(!isActive);
    }
}

function deleteTimeEntries(timeEntries: TimeEntry[]) {
    useTimeEntriesStore().deleteTimeEntries(timeEntries, "Time Entries Discarded");

}
</script>

<template>
    <div class="pt-3 pb-2.5 px-2 flex justify-between items-center relative">
        <div v-if="isRunningInDifferentOrganization"
            class="absolute w-full h-full backdrop-blur-sm z-10 flex items-center justify-center">
            <div class="w-full h-[calc(100%+10px)] absolute bg-default-background opacity-75 backdrop-blur-sm"></div>
            <div class="flex space-x-3 items-center w-full z-20 justify-center">
                <span class="text-xs text-center text-text-primary">
                    The Timer is running in a different organization.
                </span>
            </div>
        </div>
        <div>
            <div class="text-text-secondary font-extrabold text-xs">
                Current Timer
            </div>
            <div class="text-text-primary font-medium text-lg">
                {{ currentTime }}
            </div>
        </div>
        <div class="flex">

            <TimeTrackerStartStop :active="isActive" size="base" @changed="toggleState"></TimeTrackerStartStop>
            <button v-if="isActive" @click="deleteTimeEntries([currentTimeEntry])" title="Discard Entry"
                class="p-2 rounded-full hover:bg-red-100 text-red-600 w-10 h-10 ml-2 text-center align-center flex">
                <TrashIcon class="flex-1 w-5 h-5" />
            </button>
        </div>
    </div>
</template>
