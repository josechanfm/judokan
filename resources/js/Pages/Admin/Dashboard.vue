<template>
    <AdminLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                行政後台管理....
            </h2>
        </template>
        <p>Admin Dashboard</p>
        <template v-if="organizations.length>0">
            <a :href="route('manage')">Organization Management</a>
        </template>
    </AdminLayout>

</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';


export default {
    components: {
        AdminLayout,
    },
    props: ['organizations'],
    data() {
        return {
            
        }
    },
    methods: {

    },
}
</script>