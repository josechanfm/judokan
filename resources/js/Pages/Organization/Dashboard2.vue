<template>
    <OrganizationLayout title="Dashboard" :organization="organization">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Management Dashboard
            </h2>
        </template>
        <p>Club Management</p>
        {{ organization }}
    </OrganizationLayout>
</template>

<script>
import OrganizationLayout from '@/Layouts/OrganizationLayout.vue';
import OrganizationList from '@/Components/Organization/OrganizationList.vue';


export default {
    components: {
        OrganizationLayout,
        OrganizationList,
    },
    props: ['organization'],
    data() {
        return {

        }
    },
    methods: {

    },
}
</script>