<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, User } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertTriangle, Edit, Plus, Shield, ShieldOff, Trash2 } from 'lucide-vue-next';

interface Props {
    users: {
        data: User[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/manage-users',
    },
];

const deleteUser = (user: User) => {
    if (confirm(`Are you sure you want to delete ${user.name}? This action cannot be undone.`)) {
        router.delete(route('manage-users.destroy', user.id));
    }
};

const toggleAdmin = (user: User) => {
    const action = user.is_admin ? 'remove admin privileges from' : 'grant admin privileges to';
    if (confirm(`Are you sure you want to ${action} ${user.name}?`)) {
        router.patch(route('manage-users.toggle-admin', user.id));
    }
};
</script>

<template>
    <Head title="Manage Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border flex-1 rounded-xl border p-4">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">User Management</h1>
                        <p class="text-muted-foreground">Manage user accounts and permissions</p>
                    </div>
                    <Button as-child>
                        <Link :href="route('manage-users.create')">
                            <Plus class="mr-2 h-4 w-4" />
                            Add User
                        </Link>
                    </Button>
                </div>

                <!-- Status Messages -->
                <div v-if="$page.props.flash?.status || $page.props.flash?.success" class="mb-6">
                    <Alert>
                        <AlertDescription>{{ $page.props.flash?.status || $page.props.flash?.success }}</AlertDescription>
                    </Alert>
                </div>

                <div v-if="$page.props.flash?.error" class="mb-6">
                    <Alert variant="destructive">
                        <AlertTriangle class="h-4 w-4" />
                        <AlertDescription>{{ $page.props.flash.error }}</AlertDescription>
                    </Alert>
                </div>

                <!-- Users Table -->
                <Card>
                    <CardHeader>
                        <CardTitle>Users</CardTitle>
                        <CardDescription> A list of all users in the system. You can manage their permissions and accounts. </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="p-2 text-left font-medium">Name</th>
                                        <th class="p-2 text-left font-medium">Email</th>
                                        <th class="p-2 text-left font-medium">Role</th>
                                        <th class="p-2 text-left font-medium">Created</th>
                                        <th class="p-2 text-left font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="user in users.data" :key="user.id" class="hover:bg-muted/50 border-b">
                                        <td class="p-2">
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium">{{ user.name }}</span>
                                                <Badge v-if="user.id === $page.props.auth.user.id" variant="secondary">You</Badge>
                                            </div>
                                        </td>
                                        <td class="text-muted-foreground p-2">{{ user.email }}</td>
                                        <td class="p-2">
                                            <Badge :variant="user.is_admin ? 'default' : 'secondary'">
                                                {{ user.is_admin ? 'Admin' : 'User' }}
                                            </Badge>
                                        </td>
                                        <td class="text-muted-foreground p-2">
                                            {{ new Date(user.created_at).toLocaleDateString() }}
                                        </td>
                                        <td class="p-2">
                                            <div class="flex items-center gap-2">
                                                <Button variant="ghost" size="sm" as-child>
                                                    <Link :href="route('manage-users.edit', user.id)">
                                                        <Edit class="h-4 w-4" />
                                                    </Link>
                                                </Button>

                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="toggleAdmin(user)"
                                                    :disabled="user.id === $page.props.auth.user.id"
                                                >
                                                    <Shield v-if="!user.is_admin" class="h-4 w-4" />
                                                    <ShieldOff v-else class="h-4 w-4" />
                                                </Button>

                                                <Dialog>
                                                    <DialogTrigger as-child>
                                                        <Button variant="ghost" size="sm" :disabled="user.id === $page.props.auth.user.id">
                                                            <Trash2 class="h-4 w-4" />
                                                        </Button>
                                                    </DialogTrigger>
                                                    <DialogContent>
                                                        <DialogHeader>
                                                            <DialogTitle>Delete User</DialogTitle>
                                                            <DialogDescription>
                                                                Are you sure you want to delete {{ user.name }}? This action cannot be undone.
                                                            </DialogDescription>
                                                        </DialogHeader>
                                                        <DialogFooter>
                                                            <Button variant="outline" @click="$event.target.closest('[role=dialog]').close()">
                                                                Cancel
                                                            </Button>
                                                            <Button variant="destructive" @click="deleteUser(user)"> Delete User </Button>
                                                        </DialogFooter>
                                                    </DialogContent>
                                                </Dialog>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="users.last_page > 1" class="mt-4 flex items-center justify-between">
                            <div class="text-muted-foreground text-sm">
                                Showing {{ (users.current_page - 1) * users.per_page + 1 }} to
                                {{ Math.min(users.current_page * users.per_page, users.total) }} of {{ users.total }} results
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="users.current_page === 1"
                                    @click="router.get(route('manage-users.index'), { page: users.current_page - 1 }, { preserveState: true })"
                                >
                                    Previous
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="users.current_page === users.last_page"
                                    @click="router.get(route('manage-users.index'), { page: users.current_page + 1 }, { preserveState: true })"
                                >
                                    Next
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
