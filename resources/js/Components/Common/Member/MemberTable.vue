<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import MemberTableHeading from '@/Components/Common/Member/MemberTableHeading.vue';
import MemberTableRow from '@/Components/Common/Member/MemberTableRow.vue';
import { useMembersStore } from '@/utils/useMembers';

const { members } = storeToRefs(useMembersStore());

onMounted(async () => {
    await useMembersStore().fetchMembers();
});
const gridTemplate = computed(() => {
    return `grid-template-columns: minmax(auto, auto) minmax(auto, auto) minmax(auto, auto) minmax(auto, auto) minmax(auto, auto) 80px;`;
});
</script>

<template>
    <div class="flow-root w-full  ">
        <div class="inline-block min-w-full align-middle">
            <div
                data-testid="client_table"
                class="grid min-w-full"
                :style="` ${gridTemplate}`">
                <MemberTableHeading></MemberTableHeading>
                <template v-for="member in members" :key="member.id">
                    <MemberTableRow :member="member"></MemberTableRow>
                </template>
            </div>
        </div>
    </div>
</template>
