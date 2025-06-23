<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input/InputError.vue';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, User } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, LoaderCircle } from 'lucide-vue-next';

interface Props {
    user: User;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/manage-users',
    },
    {
        title: 'Edit User',
        href: `/manage-users/${props.user.id}/edit`,
    },
];

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    is_admin: props.user.is_admin,
});

const submit = () => {
    form.put(route('manage-users.update', props.user.id));
};
</script>

<template>
    <Head title="Edit User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border flex-1 rounded-xl border p-4">
                <div class="mb-6">
                    <Button variant="ghost" as-child class="mb-4">
                        <Link :href="route('manage-users.index')">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Users
                        </Link>
                    </Button>
                    <h1 class="text-3xl font-bold tracking-tight">Edit User</h1>
                    <p class="text-muted-foreground">Update user information and permissions</p>
                </div>

                <Card class="max-w-2xl">
                    <CardHeader>
                        <CardTitle>User Information</CardTitle>
                        <CardDescription> Update the user's details and permissions. </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="space-y-2">
                                <Label for="name">Name</Label>
                                <Input id="name" type="text" required autofocus v-model="form.name" placeholder="Full name" />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="space-y-2">
                                <Label for="email">Email address</Label>
                                <Input id="email" type="email" required v-model="form.email" placeholder="email@example.com" />
                                <InputError :message="form.errors.email" />
                            </div>

                            <div class="flex items-center space-x-2">
                                <Checkbox id="is_admin" v-model:checked="form.is_admin" />
                                <Label for="is_admin" class="text-sm font-normal"> Grant admin privileges </Label>
                            </div>

                            <div class="flex gap-4">
                                <Button type="submit" :disabled="form.processing">
                                    <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                    Update User
                                </Button>
                                <Button type="button" variant="outline" as-child>
                                    <Link :href="route('manage-users.index')">Cancel</Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
