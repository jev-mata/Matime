<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue'
import TextInput from '@/packages/ui/src/Input/TextInput.vue'
import InputError from '@/packages/ui/src/Input/InputError.vue'
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue'
import { ref } from 'vue'

const props = defineProps({
    invitation: Object,
})

const form = useForm({
    name: '',
    password: '',
    password_confirmation: '',
})

const submit = () => {

    console.log('Submitting to:', route('team-invitations.accept', props.invitation.id));
    form.post(route('team-invitations.accept', props.invitation.id));
}
</script>

<template>
    <div class="p-10 bg-gray-900 text-gray-200" style="height: 100vh;">
        <div class="p-10 mx-auto" style="width: 50%;">

            <div class="mb-4 text-gray-600 text-sm">
                You’ve been invited to join the <strong>{{ invitation.team.name }}</strong> team as a <strong>{{
                    invitation.role }}</strong> using email <strong>{{ invitation.email }}</strong>.
            </div>

            <form @submit.prevent="submit" class="mt-6 space-y-4">
                <div>
                    <InputLabel for="name" value="Your Name" />
                    <TextInput v-model="form.name" id="name" type="text" class="mt-1 block w-full" required autofocus />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="password" value="Password" />
                    <TextInput v-model="form.password" id="password" type="password" class="mt-1 block w-full"
                        required />
                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="password_confirmation" value="Confirm Password" />
                    <TextInput v-model="form.password_confirmation" id="password_confirmation" type="password"
                        class="mt-1 block w-full" required />
                    <InputError :message="form.errors.password_confirmation" class="mt-2" />
                </div>

                <PrimaryButton class="mt-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Accept Invitation
                </PrimaryButton>
            </form>
        </div>
    </div>
</template>
