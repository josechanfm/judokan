<template>
  <OrganizationLayout :title="$t('forms')" :breadcrumb="breadcrumb">
    <!-- 工具列：統計 + 操作按鈕 -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <p class="m-0 text-sm text-gray-500">
        共
        <span class="mx-1 text-base font-semibold text-[#0081C8]">{{ forms.length }}</span>
        個表格
      </p>
      <div class="flex flex-wrap items-center gap-2">
        <inertia-link
          :href="route('manage.forms.create')"
          class="btn-judo-primary"
          as="button"
        >
          <PlusOutlined />
          {{ $t("create_form") }}
        </inertia-link>
      </div>
    </div>

    <!-- 表格列表 -->
    <div class="card-judo">
      <div class="overflow-x-auto">
        <a-table
          :dataSource="forms"
          :columns="columns"
          :pagination="pagination"
          :scroll="{ x: 1080 }"
          row-key="id"
        >
          <template #headerCell="{ column }">
            {{ column.i18n ? $t(column.i18n) : column.title }}
          </template>
          <template #emptyText>
            <div class="py-12 text-center text-gray-400">
              <p class="m-0 text-3xl">📋</p>
              <p class="m-0 mt-3 text-sm">
                尚無表格資料，可點右上角「創建表格」開始建立
              </p>
            </div>
          </template>
          <template #bodyCell="{ column, record }">
            <template v-if="column.dataIndex == 'operation'">
              <div class="form-actions flex flex-wrap items-center gap-1">
                <inertia-link
                  :href="route('manage.form.entries.index', { form: record.id })"
                  class="ant-btn ant-btn-link ant-btn-sm"
                  as="button"
                >
                  {{ $t("applications") }}
                </inertia-link>
                <a
                  :href="route('manage.entry.export', { form: record.id })"
                  class="ant-btn ant-btn-link ant-btn-sm"
                >
                  {{ $t("export") }}
                </a>
                <inertia-link
                  :href="route('manage.form.fields.index', { form: record.id })"
                  class="ant-btn ant-btn-link ant-btn-sm"
                  as="button"
                >
                  {{ $t("data_fields") }}
                </inertia-link>
                <inertia-link
                  :href="route('manage.forms.edit', record.id)"
                  class="ant-btn ant-btn-link ant-btn-sm"
                  as="button"
                >
                  {{ $t("edit") }}
                </inertia-link>
                <a-popconfirm
                  :title="$t('confirm_delete_record')"
                  :ok-text="$t('yes')"
                  :cancel-text="$t('no')"
                  :disabled="record.entries_count > 0"
                  @confirm="deleteConfirmed(record)"
                >
                  <a-button
                    type="link"
                    size="small"
                    danger
                    :disabled="record.entries_count > 0"
                  >
                    {{ $t("delete") }}
                  </a-button>
                </a-popconfirm>
                <a-button
                  v-if="record.entries_count > 0"
                  type="link"
                  size="small"
                  @click="backupRecords(record)"
                >
                  {{ $t("backup") }}
                </a-button>
              </div>
            </template>
            <template v-else-if="column.type == 'yesno'">
              <span
                class="form-pill"
                :class="{ 'form-pill-blue': record[column.dataIndex] == 1 }"
              >
                {{ record[column.dataIndex] == 1 ? $t("yes") : $t("no") }}
              </span>
            </template>
            <template v-else-if="column.dataIndex == 'entries'">
              <span v-if="record.entries_count != null">{{ record.entries_count }}</span>
              <span v-else class="text-gray-300">—</span>
            </template>
            <template v-else>
              {{ record[column.dataIndex] }}
            </template>
          </template>
        </a-table>
      </div>
    </div>

    <p class="m-0 mt-3 text-xs text-gray-400">
      表格在已有回應資料時無法刪除，可先備份後再刪除。
    </p>
  </OrganizationLayout>
</template>

<script>
import OrganizationLayout from "@/Layouts/OrganizationLayout.vue";
import { PlusOutlined } from "@ant-design/icons-vue";
import { message } from "ant-design-vue";

export default {
  components: {
    OrganizationLayout,
    PlusOutlined,
  },
  props: ["organization", "forms"],
  data() {
    return {
      breadcrumb: [{ label: this.$t("forms"), url: null }],
      pagination: {
        defaultPageSize: 20,
        showSizeChanger: true,
        pageSizeOptions: ["10", "20", "50", "100"],
      },
      columns: [
        {
          title: "Name",
          i18n: "name",
          dataIndex: "name",
          width: 180,
          ellipsis: true,
          sorter: (a, b) => String(a.name || "").localeCompare(String(b.name || "")),
        },
        {
          title: "Title",
          i18n: "title",
          dataIndex: "title",
          width: 260,
          ellipsis: true,
          sorter: (a, b) => String(a.title || "").localeCompare(String(b.title || "")),
        },
        {
          title: "Require_login",
          i18n: "require_login",
          dataIndex: "require_login",
          type: "yesno",
          width: 130,
        },
        {
          title: "Published",
          i18n: "published",
          dataIndex: "published",
          type: "yesno",
          width: 120,
        },
        {
          title: "Entries",
          i18n: "entries",
          dataIndex: "entries",
          key: "entries",
          width: 100,
        },
        {
          title: "Operation",
          i18n: "operation",
          dataIndex: "operation",
          key: "operation",
          width: 400,
        },
      ],
    };
  },
  methods: {
    deleteConfirmed(record) {
      this.$inertia.delete(route("manage.forms.destroy", { form: record.id }), {
        onSuccess: () => {
          message.success(this.$t("delete_success"));
        },
        onError: () => {
          message.error(this.$t("delete_failed"));
        },
      });
    },
    backupRecords(record) {
      if (!confirm("Do you sure want to backup?")) return;

      this.$inertia.post(
        route("manage.form.backup", record.id),
        {},
        {
          onSuccess: () => {
            message.success("備份成功");
          },
          onError: () => {
            message.error("備份失敗");
          },
        }
      );
    },
  },
};
</script>

<style scoped>
/* ---------- 表格質感 ---------- */
:deep(.ant-table-thead > tr > th) {
  background-color: #f8fafc;
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.02em;
  border-bottom: 1px solid #e2e8f0;
}
:deep(.ant-table-tbody > tr > td) {
  padding-top: 12px;
  padding-bottom: 12px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 14px;
  color: #334155;
}
:deep(.ant-table-tbody > tr:last-child > td) {
  border-bottom: none;
}
:deep(.ant-table-tbody > tr:hover > td) {
  background-color: rgba(0, 129, 200, 0.04);
}

/* ---------- 操作欄按鈕 ---------- */
.form-actions :deep(.ant-btn) {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 28px;
  padding: 0 10px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  line-height: 1;
  box-shadow: none;
}
/* <a> 不會像 <button> 一樣自動垂直置中，需統一對齊 */
.form-actions :deep(a.ant-btn) {
  text-decoration: none;
}
.form-actions :deep(.ant-btn-link) {
  color: #0081c8;
}
.form-actions :deep(.ant-btn-link:hover) {
  color: #0b6ea8;
  background-color: rgba(0, 129, 200, 0.08);
}
.form-actions :deep(.ant-btn-link.ant-btn-dangerous) {
  color: #d4380d;
}
.form-actions :deep(.ant-btn-link.ant-btn-dangerous:hover) {
  color: #a8071a;
  background-color: rgba(212, 56, 13, 0.08);
}
.form-actions :deep(.ant-btn[disabled]) {
  color: #cbd5e1;
}
.form-actions :deep(.ant-btn[disabled]:hover) {
  background-color: transparent;
}

/* ---------- 是／否標籤 ---------- */
.form-pill {
  display: inline-flex;
  align-items: center;
  border-radius: 9999px;
  padding: 1px 10px;
  font-size: 12px;
  font-weight: 500;
  line-height: 20px;
  background-color: #f1f5f9;
  color: #64748b;
}
.form-pill-blue {
  background-color: rgba(0, 129, 200, 0.1);
  color: #0081c8;
}
</style>
