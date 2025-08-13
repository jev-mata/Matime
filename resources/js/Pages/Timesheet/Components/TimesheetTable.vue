<script setup lang="ts"> 
const props =  defineProps<{
        getPeriodInfo: (period: string) => { endDate: string; endDate2: string; isHighlighted: boolean };
        formatDate: (date: string) => string;
        period: string;
        userEntries: Object[];
    }>();
</script>
<template>
    <div class="p-3 font-bold" :class="getPeriodInfo(period).isHighlighted
            ? 'dark:bg-[#0F1426]  text-white'
            : 'dark:bg-[#0C101E] '
        ">
        {{ formatDate(period) }} -
        {{ getPeriodInfo(period).endDate }}
    </div>
    <a v-for="entry in userEntries" :key="entry.user.id" class="flex border  dark:border-[#303F61]  p-3" :href="route('approval.ApprovalOverview', {
        user_id: entry.user.member.id,
        date_start: period,
        date_end: getPeriodInfo(period).endDate2,
    })
        ">
        <div class="flex-1">{{ entry.user.name }}</div>
        <div class="flex-1">
            {{
                entry.user.groups?.[0]?.manager?.name ?? '—'
            }}
        </div>
        <div class="flex-1">{{ entry.totalHours }}</div>
    </a>
</template>