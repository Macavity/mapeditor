<script setup lang="ts">
import { Button } from '@/components/ui/button';
import HeadingSmall from '@/components/ui/HeadingSmall.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'API Tokens',
        href: '/settings/api-tokens',
    },
];

const form = {
    name: '',
    processing: false,
};

defineProps<{
    tokens: Array<{
        id: string;
        name: string;
        created_at: string;
    }>;
}>();

const createToken = async () => {
    if (form.processing) return;

    form.processing = true;

    try {
        const response = await axios.post('/api/api-tokens', {
            name: form.name,
        });

        // Show success message
        window.dispatchEvent(
            new CustomEvent('toast', {
                detail: {
                    type: 'success',
                    message: response.data.message,
                },
            }),
        );

        // Reset form and reload tokens
        form.name = '';
        router.reload({ only: ['tokens'] });
    } catch (error: any) {
        console.error('Error creating token:', error);
        window.dispatchEvent(
            new CustomEvent('toast', {
                detail: {
                    type: 'error',
                    message: error.response?.data?.message || 'Failed to create token',
                },
            }),
        );
    } finally {
        form.processing = false;
    }
};

const revokeToken = async (tokenId: string) => {
    if (confirm('Are you sure you want to revoke this token?')) {
        try {
            await axios.delete(`/api/api-tokens/${tokenId}`);

            // Show success message
            window.dispatchEvent(
                new CustomEvent('toast', {
                    detail: {
                        type: 'success',
                        message: 'Token revoked successfully',
                    },
                }),
            );

            // Reload the tokens list
            router.reload({ only: ['tokens'] });
        } catch (error: any) {
            console.error('Error revoking token:', error);
            window.dispatchEvent(
                new CustomEvent('toast', {
                    detail: {
                        type: 'error',
                        message: error.response?.data?.message || 'Failed to revoke token',
                    },
                }),
            );
        }
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="API Tokens" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="API Tokens" description="Manage your API tokens for external access" />

                <div class="space-y-6">
                    <div class="bg-card rounded-lg border p-6">
                        <h3 class="mb-4 text-lg font-medium">Create New Token</h3>
                        <form @submit.prevent="createToken" class="space-y-4">
                            <div class="grid gap-2">
                                <Label for="name">Token Name</Label>
                                <Input id="name" v-model="form.name" type="text" class="w-full" required placeholder="e.g., Production Server" />
                            </div>
                            <Button type="submit" :disabled="form.processing"> Create Token </Button>
                        </form>
                    </div>

                    <div class="space-y-4">
                        <h3 class="text-lg font-medium">Your API Tokens</h3>
                        <div v-if="tokens.length === 0" class="text-muted-foreground">No API tokens created yet.</div>
                        <div v-else class="space-y-2">
                            <div v-for="token in tokens" :key="token.id" class="flex items-center justify-between rounded-lg border p-4">
                                <div>
                                    <p class="font-medium">{{ token.name }}</p>
                                    <p class="text-muted-foreground text-sm">Created {{ new Date(token.created_at).toLocaleDateString() }}</p>
                                </div>
                                <Button variant="destructive" size="sm" @click="revokeToken(token.id)"> Revoke </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
