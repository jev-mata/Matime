<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { computed, onMounted, ref, watch } from 'vue';
import type {
    CreateProjectMemberBody,
    ProjectMember,
} from '@/packages/api/src';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { useFocus, useMounted } from '@vueuse/core';
import { useProjectMembersStore } from '@/utils/useProjectMembers';
import BillableRateInput from '@/packages/ui/src/Input/BillableRateInput.vue';
import { getOrganizationCurrencyString } from '@/utils/money';
import axios from 'axios';
import { Badge } from '@/packages/ui/src';
import { UserIcon, ChevronDownIcon } from '@heroicons/vue/24/solid';
import SelectDropdown from '@/packages/ui/src/Input/SelectDropdown.vue';
import MemberCombobox from '../Member/MemberCombobox.vue';
const { createProjectMember } = useProjectMembersStore();
const show = defineModel('show', { default: false });
const saving = ref(false);
const props = defineProps<{
    projectId: string;
    existingMembers: ProjectMember[];
}>();
const emit = defineEmits(['refresh']);

const projectMember = ref<CreateProjectMemberBody>({
    member_id: '',
    billable_rate: null,
});
type group = {
    id: string,
    name: string,
    billable_rate: number | null,
};
const projectGroupMember = ref<group[]>([]); // now holds array of groups

const selectedGroupId = ref(''); // holds selected group's id
const groupBillableRate = ref<number | null>(null); // stores rate separately

async function getGroupNotMember() {
    const { data } = await axios.get(`/organizations/teams/projects/${props.projectId}`);
    projectGroupMember.value = data.team.length > 0 ? data.team : [{ id: null, name: 'No Group Available', billable_rate: null }]; // team from your sample response
 
}
watch(
    () => show.value,
    (value) => {
        if (value) {
            getGroupNotMember();
        }
    }
);

onMounted(getGroupNotMember);
async function submit() {
    await createProjectMember(props.projectId, projectMember.value);
    show.value = false;
    projectMember.value = {
        member_id: '',
        billable_rate: null,
    };
}

async function submitGroup() {
    console.log(selectedGroupId.value);
    const url = `/teams/${selectedGroupId.value}/projects/${props.projectId}`;
    console.log(url);
    show.value = false;
    const { data } = await axios.post(url);
    clearGroupSelection();
    console.log(data);
    getGroupNotMember();
    emit('refresh');
}
const groupOptions = computed(() =>
    projectGroupMember.value.map(group => ({
        label: group.name,
        value: group.id,
    }))
);

// For display
const selectedGroupName = computed<string | 'Select Group...' | 'No Group Available'>(() => {
    return projectGroupMember.value.find(group => group.id === selectedGroupId.value)?.name || 'Select Group...';
});

const projectNameInput = ref<HTMLInputElement | null>(null);

useFocus(projectNameInput, { initialValue: true });
function getKeyFromItem(item: { label: string; value: string }) {
    return item.value;
}

function getNameForItem(item: { label: string; value: string }) {
    return item.label;
}
function clearGroupSelection() {
    selectedGroupId.value = '';
    groupBillableRate.value = null;
}

</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span>Add Project Member</span>
            </div>
        </template>

        <template #content>
            <div class="grid grid-cols-3 items-center space-x-4">
                <div class="col-span-3 sm:col-span-2">
                    <MemberCombobox v-model="projectMember.member_id" :hidden-members="props.existingMembers"
                        :placeholder="'Select Members...'">
                    </MemberCombobox>
                </div>
                <div class="col-span-3 sm:col-span-1 flex-1">
                    <BillableRateInput v-model="projectMember.billable_rate
                        " name="billable_rate" :currency="getOrganizationCurrencyString()"></BillableRateInput>
                </div>
            </div>
            <div class="flex items-center gap-4 my-4">
                <div class="flex-grow border-t border-gray-600"></div>
                <span class="text-gray-500 text-sm">or</span>
                <div class="flex-grow border-t border-gray-600"></div>
            </div>

            <div class="grid grid-cols-3 items-center space-x-4">
                <div class="col-span-3 sm:col-span-2">
                    <SelectDropdown v-model="selectedGroupId" :items="groupOptions" :getKeyFromItem="getKeyFromItem"
                        :getNameForItem="getNameForItem">
                        <template #trigger>
                            <Badge tag="button"
                                class="flex w-full text-base text-left space-x-3 px-3 text-text-secondary bg-input-background font-normal cursor py-1.5">
                                <UserIcon class="relative z-10 w-4 text-text-secondary" />
                                <div class="flex-1 truncate">
                                    {{ selectedGroupName }}
                                </div>
                                <ChevronDownIcon class="w-4 text-text-secondary" />
                            </Badge>
                        </template>
                    </SelectDropdown>


                </div>
                <div class="col-span-3 sm:col-span-1 flex-1">
                    <BillableRateInput v-model="groupBillableRate" name="group_billable_rate"
                        :currency="getOrganizationCurrencyString()" />

                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false">Cancel</SecondaryButton>
            <SecondaryButton v-if="selectedGroupName != 'Select Group...'" @click="clearGroupSelection" class="ml-3">
                Clear
            </SecondaryButton>
            <PrimaryButton v-if="selectedGroupName == 'Select Group...'" class="ms-3" :class="{ 'opacity-25': saving }"
                :disabled="saving" @click="submit">
                Add Project Member
            </PrimaryButton>
            <PrimaryButton v-if="(selectedGroupName != 'Select Group...' && selectedGroupName != 'No Group Available')"
                class="ms-3" :class="{ 'opacity-25': saving }" :disabled="saving" @click="submitGroup">
                Add Project Group
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
