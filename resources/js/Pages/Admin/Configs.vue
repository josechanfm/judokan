<template>
  <AdminLayout title="Dashboard">
    <template #header>
      <div>
        <h1 class="text-2xl font-bold">{{ $t("configs") }}</h1>
      </div>
    </template>

    <div class="p-6 min-h-screen">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 text-sm font-medium">
                {{ $t("total_configs") }}
              </p>
              <h3 class="text-2xl font-bold text-gray-800 mt-1">
                {{ configs.length }}
              </h3>
            </div>
            <div class="p-3 bg-blue-50 rounded-xl">
              <SettingOutlined class="text-blue-500 text-xl" />
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-green-500">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 text-sm font-medium">
                {{ $t("general_configs") }}
              </p>
              <h3 class="text-2xl font-bold text-gray-800 mt-1">
                {{ configs.filter((config) => config.organization_id === 0).length }}
              </h3>
            </div>
            <div class="p-3 bg-green-50 rounded-xl">
              <GlobalOutlined class="text-green-500 text-xl" />
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-orange-500">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 text-sm font-medium">
                {{ $t("organization_configs") }}
              </p>
              <h3 class="text-2xl font-bold text-gray-800 mt-1">
                {{ configs.filter((config) => config.organization_id !== 0).length }}
              </h3>
            </div>
            <div class="p-3 bg-orange-50 rounded-xl">
              <HomeOutlined class="text-orange-500 text-xl" />
            </div>
          </div>
        </div>
      </div>

      <!-- Configs Table Card -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div
          class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4"
        >
          <div>
            <h3 class="text-lg font-semibold text-gray-800">
              {{ $t("configs_list") }}
            </h3>
            <p class="text-gray-500 text-sm mt-1">{{ $t("manage_all_configs") }}</p>
          </div>
          <div
            class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 w-full sm:w-auto"
          >
            <a-button
              @click="createRecord()"
              type="primary"
              class="flex items-center justify-center bg-blue-500 hover:bg-blue-600 border-blue-500 rounded-xl"
            >
              <PlusOutlined />
              {{ $t("create_config_item") }}
            </a-button>
            <a-input-search
              type="input"
              v-model:value="searchText"
              :placeholder="$t('search_configs')"
              class="w-full sm:w-64 rounded-xl"
              @search="handleSearch"
            />
            <a-button class="flex items-center justify-center border-gray-300 rounded-xl">
              <FilterOutlined />
              {{ $t("filter") }}
            </a-button>
          </div>
        </div>

        <!-- Table Container -->
        <div class="w-full overflow-x-auto">
          <div class="min-w-[800px]">
            <a-table
              :dataSource="filteredConfigs"
              :columns="columns"
              :pagination="{
                pageSize: 10,
                showSizeChanger: true,
                showQuickJumper: true,
                showTotal: (total, range) =>
                  `${$t('showing')} ${range[0]}-${range[1]} ${$t('of')} ${total} ${$t(
                    'configs'
                  )}`,
              }"
              rowKey="id"
              class="custom-table w-full"
              :scroll="{ x: 'max-content' }"
            >
              <template #headerCell="{ column }">
                <span class="font-semibold text-gray-700 flex items-center">
                  {{ column.i18n ? $t(column.i18n) : column.title }}
                </span>
              </template>

              <template #bodyCell="{ column, text, record, index }">
                <template v-if="column.dataIndex == 'operation'">
                  <div class="flex flex-wrap gap-2 min-w-[120px]">
                    <a-tooltip :title="$t('edit_config')">
                      <a-button
                        @click="editRecord(record)"
                        class="flex items-center space-x-1 text-green-600 hover:text-green-800 border-green-200 hover:border-green-300 rounded-xl px-4 py-2 transition-colors duration-200"
                      >
                        <EditOutlined />
                        <span class="whitespace-nowrap">{{ $t("edit") }}</span>
                      </a-button>
                    </a-tooltip>

                    <a-tooltip :title="$t('delete_config')">
                      <a-popconfirm
                        :title="$t('confirm_delete_record')"
                        :ok-text="$t('yes')"
                        :cancel-text="$t('no')"
                        @confirm="deleteRecord(record)"
                        okType="danger"
                        placement="topRight"
                      >
                        <a-button
                          class="flex items-center space-x-1 text-red-600 hover:text-red-800 border-red-200 hover:border-red-300 rounded-xl px-4 py-2 transition-colors duration-200"
                        >
                          <DeleteOutlined />
                          <span class="whitespace-nowrap">{{ $t("delete") }}</span>
                        </a-button>
                      </a-popconfirm>
                    </a-tooltip>
                  </div>
                </template>

                <template v-else-if="column.dataIndex == 'organization'">
                  <div class="flex items-center">
                    <a-tag
                      :color="record.organization_id === 0 ? 'blue' : 'green'"
                      class="rounded-full px-3 py-1 font-medium"
                    >
                      {{
                        record.organization_id === 0
                          ? $t("general")
                          : getOrganizationName(record.organization_id)
                      }}
                    </a-tag>
                  </div>
                </template>

                <template v-else-if="column.dataIndex == 'key'">
                  <code
                    class="bg-gray-100 px-2 py-1 rounded text-sm font-mono text-gray-800"
                  >
                    {{ record.key }}
                  </code>
                </template>

                <template v-else-if="column.dataIndex == 'value'">
                  <div class="max-w-xs">
                    <span class="text-gray-700 text-sm line-clamp-2">
                      {{ record.value }}
                    </span>
                  </div>
                </template>

                <template v-else-if="column.dataIndex == 'remark'">
                  <span class="text-gray-500 text-sm">
                    {{ record.remark || "-" }}
                  </span>
                </template>

                <template v-else-if="column.dataIndex == 'updated_at'">
                  <span class="text-gray-500 text-sm whitespace-nowrap">
                    {{ formatDate(record.updated_at) }}
                  </span>
                </template>

                <template v-else>
                  <span class="text-gray-700 whitespace-nowrap">
                    {{ record[column.dataIndex] || "-" }}
                  </span>
                </template>
              </template>
            </a-table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <a-modal
      v-model:visible="modal.isOpen"
      :title="modal.title"
      width="800px"
      :confirm-loading="submitting"
      :styles="{
        body: { padding: '24px' },
        content: { borderRadius: '16px', overflow: 'hidden' },
      }"
      :header-style="{
        borderBottom: '1px solid #e5e7eb',
        padding: '16px 24px',
        borderRadius: '16px 16px 0 0',
        backgroundColor: '#f8fafc',
      }"
      :body-style="{
        padding: '24px',
        maxHeight: '70vh',
        overflowY: 'auto',
      }"
      :footer-style="{
        borderTop: '1px solid #e5e7eb',
        padding: '16px 24px',
        borderRadius: '0 0 16px 16px',
      }"
      @cancel="handleModalCancel"
    >
      <a-form
        ref="modalRef"
        :model="modal.data"
        name="Config"
        :label-col="{ span: 6 }"
        :wrapper-col="{ span: 17 }"
        autocomplete="off"
        :rules="rules"
        :validate-messages="validateMessages"
        class="modal-form"
      >
        <a-form-item
          :label="$t('organization')"
          name="organization_id"
          :label-style="{ fontWeight: '500', color: '#374151' }"
        >
          <a-select
            v-model:value="modal.data.organization_id"
            :options="organizationsWithGeneral"
            :fieldNames="{ value: 'id', label: 'full_name' }"
            class="w-full rounded-xl"
            :placeholder="$t('select_organization')"
          />
        </a-form-item>

        <a-form-item
          :label="$t('key')"
          name="key"
          :label-style="{ fontWeight: '500', color: '#374151' }"
        >
          <a-input
            type="input"
            v-model:value="modal.data.key"
            :placeholder="$t('enter_config_key')"
            class="rounded-xl"
          />
        </a-form-item>

        <a-form-item
          :label="$t('value')"
          name="value"
          :label-style="{ fontWeight: '500', color: '#374151' }"
        >
          <a-textarea
            v-model:value="modal.data.value"
            :rows="8"
            :placeholder="$t('enter_config_value')"
            class="rounded-xl font-mono text-sm"
          />
        </a-form-item>

        <a-form-item
          :label="$t('remark')"
          name="remark"
          :label-style="{ fontWeight: '500', color: '#374151' }"
        >
          <a-textarea
            v-model:value="modal.data.remark"
            :rows="3"
            :placeholder="$t('enter_remark')"
            class="rounded-xl"
          />
        </a-form-item>
      </a-form>

      <template #footer>
        <div class="flex justify-end gap-3">
          <a-button @click="handleModalCancel" size="large" class="rounded-xl">
            {{ $t("cancel") }}
          </a-button>
          <a-button
            v-if="modal.mode == 'EDIT'"
            key="Update"
            type="primary"
            @click="updateRecord()"
            :loading="submitting"
            size="large"
            class="rounded-xl"
          >
            {{ $t("update") }}
          </a-button>
          <a-button
            v-if="modal.mode == 'CREATE'"
            key="Store"
            type="primary"
            @click="storeRecord()"
            :loading="submitting"
            size="large"
            class="rounded-xl"
          >
            {{ $t("add") }}
          </a-button>
        </div>
      </template>
    </a-modal>
  </AdminLayout>
</template>

<script>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import {
  PlusOutlined,
  EditOutlined,
  DeleteOutlined,
  SettingOutlined,
  GlobalOutlined,
  HomeOutlined,
  FilterOutlined,
} from "@ant-design/icons-vue";

export default {
  components: {
    AdminLayout,
    PlusOutlined,
    EditOutlined,
    DeleteOutlined,
    SettingOutlined,
    GlobalOutlined,
    HomeOutlined,
    FilterOutlined,
  },
  props: ["organizations", "users", "configs"],
  data() {
    return {
      searchText: "",
      submitting: false,
      modal: {
        isOpen: false,
        data: {},
        title: "Modal",
        mode: "",
      },
      columns: [
        {
          title: "Organization",
          i18n: "organization",
          dataIndex: "organization",
          width: 180,
        },
        {
          title: "Key",
          i18n: "key",
          dataIndex: "key",
          width: 200,
        },
        {
          title: "Remark",
          i18n: "remark",
          dataIndex: "remark",
          width: 200,
        },
        {
          title: "Updated At",
          i18n: "updated_at",
          dataIndex: "updated_at",
          width: 150,
        },
        {
          title: "Actions",
          i18n: "actions",
          dataIndex: "operation",
          key: "operation",
          width: 200,
          fixed: "right",
        },
      ],
      rules: {
        organization_id: { required: true },
        key: { required: true },
        value: { required: true },
      },
      validateMessages: {
        required: "${label} is required!",
        types: {
          email: "${label} is not a valid email!",
          number: "${label} is not a valid number!",
        },
      },
    };
  },
  computed: {
    organizationsWithGeneral() {
      return [
        { id: 0, full_name: this.$t("general_config_item") },
        ...this.organizations,
      ];
    },
    filteredConfigs() {
      if (!this.searchText) return this.configs;
      const search = this.searchText.toLowerCase();
      return this.configs.filter(
        (config) =>
          config.key?.toLowerCase().includes(search) ||
          config.value?.toLowerCase().includes(search) ||
          config.remark?.toLowerCase().includes(search) ||
          this.getOrganizationName(config.organization_id)?.toLowerCase().includes(search)
      );
    },
  },
  methods: {
    getOrganizationName(organizationId) {
      if (organizationId === 0) return this.$t("general");
      const org = this.organizations.find((org) => org.id === organizationId);
      return org ? org.full_name : "-";
    },
    formatDate(dateString) {
      if (!dateString) return "-";
      return new Date(dateString).toLocaleDateString();
    },
    handleSearch(value) {
      this.searchText = value;
    },
    createRecord() {
      this.modal.data = {
        organization_id: 0,
      };
      this.modal.mode = "CREATE";
      this.modal.title = this.$t("create_config_item");
      this.modal.isOpen = true;
    },
    editRecord(record) {
      this.modal.data = { ...record };
      this.modal.mode = "EDIT";
      this.modal.title = this.$t("edit_config_item");
      this.modal.isOpen = true;
    },
    handleModalCancel() {
      this.modal.isOpen = false;
      this.modal.data = {};
    },
    storeRecord() {
      this.submitting = true;
      this.$refs.modalRef
        .validateFields()
        .then(() => {
          this.$inertia.post(route("admin.configs.store"), this.modal.data, {
            onSuccess: (page) => {
              this.modal.isOpen = false;
              this.submitting = false;
              this.modal.data = {};
            },
            onError: (err) => {
              console.log(err);
              this.submitting = false;
            },
          });
        })
        .catch((err) => {
          console.log(err);
          this.submitting = false;
        });
    },
    updateRecord() {
      this.submitting = true;
      this.$refs.modalRef
        .validateFields()
        .then(() => {
          this.$inertia.patch(
            route("admin.configs.update", this.modal.data.id),
            this.modal.data,
            {
              onSuccess: (page) => {
                this.modal.isOpen = false;
                this.submitting = false;
                this.modal.data = {};
              },
              onError: (error) => {
                console.log(error);
                this.submitting = false;
              },
            }
          );
        })
        .catch((err) => {
          console.log("error", err);
          this.submitting = false;
        });
    },
    deleteRecord(record) {
      this.$inertia.delete(route("admin.configs.destroy", record.id), {
        onSuccess: (page) => {
          console.log(page);
        },
        onError: (error) => {
          console.log(error);
        },
      });
    },
  },
};
</script>

<style scoped>
/* 確保表格容器可以水平滾動 */
.overflow-x-auto {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

/* 自定義表格樣式 */
.custom-table :deep(.ant-table) {
  min-width: 100%;
}

.custom-table :deep(.ant-table-container) {
  overflow: auto;
}

.custom-table :deep(.ant-table-thead > tr > th) {
  @apply bg-gray-50 text-gray-700 font-semibold border-b border-gray-200 whitespace-nowrap;
}

.custom-table :deep(.ant-table-tbody > tr > td) {
  @apply whitespace-nowrap;
}

.custom-table :deep(.ant-table-tbody > tr:hover > td) {
  @apply bg-blue-50;
}

/* 響應式設計 */
@media (max-width: 768px) {
  .custom-table :deep(.ant-table) {
    font-size: 14px;
  }

  .custom-table :deep(.ant-table-thead > tr > th),
  .custom-table :deep(.ant-table-tbody > tr > td) {
    padding: 8px 12px;
  }
}

/* 模態框樣式 */
.modal-form :deep(.ant-form-item) {
  margin-bottom: 16px;
}

.modal-form :deep(.ant-form-item-label > label) {
  font-weight: 500;
  color: #374151;
}

/* 文本截斷 */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
