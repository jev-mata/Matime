<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue'
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import InputError from '@/packages/ui/src/Input/InputError.vue'
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue'

import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
const props = defineProps({
    invitation: Object,
    user: Object,
})

const form = useForm({
    name: '',
    password: null,
    password_confirmation: '',
})

const submit = () => {

    console.log('Submitting to:', route('team-invitations.accept', props.invitation.id));
    form.post(route('team-invitations.accept', props.invitation.id));
}
</script>

<template>
    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>
 

        <div class="mb-6 text-gray-300 text-md pb-4">
            You’ve been invited to join the <strong>{{ invitation.team.name }}</strong> team as a <strong>{{
                invitation.role }}</strong> using email <strong>{{ invitation.email }}</strong>
        </div>

        <form @submit.prevent="submit" class="mt-6 space-y-4 w-full" autocomplete="off">
            <input type="text" name="fake_username" autocomplete="username" style="display: none;" />
            <input type="password" name="fake_password" autocomplete="new-password" style="display: none;" />

            <div v-if="!props.user">
                <InputLabel for="name" value="Your Name" />
                <TextInput v-model="form.name" id="name" type="text" class="mt-1 block w-full " autocomplete="off"
                    placeholder="Complete Name" required autofocus />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <div v-if="!props.user">
                <InputLabel for="password" value="Password" />
                <TextInput v-model="form.password" id="password" type="password" class="mt-1 block w-full " required
                    autocomplete="off" placeholder="Password" />
                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <div v-if="!props.user">
                <InputLabel for="password_confirmation" value="Confirm Password" />
                <TextInput v-model="form.password_confirmation" id="password_confirmation" type="password"
                    autocomplete="new-name" placeholder="Confirm Password" class="mt-1 block w-full " required />
                <InputError :message="form.errors.password_confirmation" class="mt-2" />
            </div>
            <div class="w-full flex justify-center">
                <PrimaryButton class="mt-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Accept Invitation
                </PrimaryButton>
            </div>

        </form>
    </AuthenticationCard>
</template>
