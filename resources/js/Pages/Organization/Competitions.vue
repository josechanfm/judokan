<template>
  <OrganizationLayout title="賽事列表" :breadcrumb="breadcrumb">
    <div class="flex-auto pb-3 text-right">
      <inertia-link
        :href="route('manage.competitions.create')"
        class="ant-btn ant-btn-primary"
        >{{ $t("competition_create") }}</inertia-link
      >
    </div>
    <div class="container mx-auto pt-5">
      <div class="bg-white relative shadow rounded-lg overflow-x-auto">
        <a-table :dataSource="competitions.data" :columns="columns">
          <template #headerCell="{ column }">
            {{ column.i18n ? $t(column.i18n) : column.title }}
          </template>
          <template #bodyCell="{ column, text, record, index }">
            <template v-if="column.dataIndex == 'operation'">
              <inertia-link
                class="ant-btn"
                :href="route('manage.competitions.show', record.id)"
                >{{ $t("view") }}</inertia-link
              >
              <inertia-link
                :href="route('manage.competitions.edit', record.id)"
                class="ant-btn"
              >{{ $t("edit") }}</inertia-link
              >
              <inertia-link
                :href="route('manage.competition.applications.index', record.id)"
                class="ant-btn"
              >{{ $t("applications") }}</inertia-link
              >
              <inertia-link 
                class="ant-btn"
                :href="route('manage.competition.results.index',record.id)"
              >
                {{$t("competition_result")}}
              </inertia-link>
            </template>
            <template v-else-if="column.dataIndex == 'state'">
              {{ teacherStateLabels[text] }}
            </template>
            <template v-else>
              {{ record[column.dataIndex] }}
            </template>
          </template>
        </a-table>
      </div>
    </div>
  </OrganizationLayout>
</template>

<script>
import OrganizationLayout from "@/Layouts/OrganizationLayout.vue";
import { defineComponent, reactive } from "vue";

export default {
  components: {
    OrganizationLayout,
  },
  props: ["competitions"],
  data() {
    return {
      breadcrumb: [
        { label: "賽事列表", url: null }
      ],
      modal: {
        isOpen: false,
        data: {},
        title: "Modal",
        mode: "",
      },
      teacherStateLabels: {},
      columns: [
        {
          title: "Competition title",
          i18n: "competition_title_zh",
          dataIndex: "title_zh",
        },
        {
          title: "Start date",
          i18n: "start_date",
          dataIndex: "start_date",
        },
        {
          title: "End date",
          i18n: "end_date",
          dataIndex: "end_date",
        },
        {
          title: "Operation",
          i18n: "operation",
          dataIndex: "operation",
          key: "operation",
        },
      ],
      rules: {
        name_zh: { required: true },
        mobile: { required: true },
        state: { required: true },
      },
      validateMessages: {
        required: "${label} is required!",
        types: {
          email: "${label} is not a valid email!",
          number: "${label} is not a valid number!",
        },
        number: {
          range: "${label} must be between ${min} and ${max}",
        },
      },
      labelCol: {
        style: {
          width: "150px",
        },
      },
    };
  },
  created() {},
  methods: {},
};
</script>
