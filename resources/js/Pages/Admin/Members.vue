<template>
  <AdminLayout title="Dashboard">
    <template #header>
      <div>
        <h1 class="text-2xl font-bold">{{ $t("members") }}</h1>
      </div>
    </template>

    <div class="p-6 min-h-screen">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 text-sm font-medium">
                {{ $t("total_members") }}
              </p>
              <h3 class="text-2xl font-bold text-gray-800 mt-1">
                {{ members.length }}
              </h3>
            </div>
            <div class="p-3 bg-blue-50 rounded-lg">
              <TeamOutlined class="text-blue-500 text-xl" />
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-green-500">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 text-sm font-medium">
                {{ $t("members_with_login") }}
              </p>
              <h3 class="text-2xl font-bold text-gray-800 mt-1">
                {{ members.filter((member) => member.user).length }}
              </h3>
            </div>
            <div class="p-3 bg-green-50 rounded-lg">
              <UserOutlined class="text-green-500 text-xl" />
            </div>
          </div>
        </div>
      </div>

      <!-- Members Table Card -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div
          class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4"
        >
          <div>
            <h3 class="text-lg font-semibold text-gray-800">
              {{ $t("members_list") }}
            </h3>
            <p class="text-gray-500 text-sm mt-1">{{ $t("manage_all_members") }}</p>
          </div>
          <div
            class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 w-full sm:w-auto"
          >
            <a-button
              @click="createRecord()"
              type="primary"
              class="flex items-center justify-center bg-blue-500 hover:bg-blue-600 border-blue-500"
            >
              <PlusOutlined />
              {{ $t("create_member") }}
            </a-button>
            <a-input-search
              type="input"
              v-model:value="searchText"
              :placeholder="$t('search_members')"
              class="w-full sm:w-64"
              @search="handleSearch"
            />
            <a-button class="flex items-center justify-center border-gray-300">
              <FilterOutlined />
              {{ $t("filter") }}
            </a-button>
          </div>
        </div>

        <!-- 表格容器 -->
        <div class="w-full overflow-x-auto" style="-webkit-overflow-scrolling: touch">
          <div class="min-w-[800px]">
            <a-table
              :dataSource="filteredMembers"
              :columns="columns"
              :pagination="{
                pageSize: 10,
                showSizeChanger: true,
                showQuickJumper: true,
                showTotal: (total, range) =>
                  `${$t('showing')} ${range[0]}-${range[1]} ${$t('of')} ${total} ${$t(
                    'members'
                  )}`,
              }"
              rowKey="id"
              class="custom-table w-full"
              :scroll="{ x: 'max-content' }"
            >
              <template #headerCell="{ column }">
                <span
                  class="font-semibold text-gray-700 flex items-center"
                  style="white-space: nowrap"
                >
                  {{ column.i18n ? $t(column.i18n) : column.title }}
                </span>
              </template>

              <template #bodyCell="{ column, text, record, index }">
                <template v-if="column.dataIndex == 'operation'">
                  <div class="flex flex-wrap gap-2" style="min-width: 200px">
                    <a-tooltip :title="$t('edit_member')">
                      <a-button
                        @click="editRecord(record)"
                        class="flex items-center space-x-1 text-green-600 hover:text-green-800 border-green-200 hover:border-green-300 rounded-lg px-3 py-1 transition-colors duration-200"
                      >
                        <EditOutlined />
                        <span style="white-space: nowrap">{{ $t("edit") }}</span>
                      </a-button>
                    </a-tooltip>

                    <a-tooltip :title="$t('delete_member')">
                      <a-popconfirm
                        :title="$t('confirm_delete_record')"
                        :ok-text="$t('yes')"
                        :cancel-text="$t('no')"
                        @confirm="deleteRecord(record)"
                        okType="danger"
                        placement="topRight"
                      >
                        <a-button
                          class="flex items-center space-x-1 text-red-600 hover:text-red-800 border-red-200 hover:border-red-300 rounded-lg px-3 py-1 transition-colors duration-200"
                        >
                          <DeleteOutlined />
                          <span style="white-space: nowrap">{{ $t("delete") }}</span>
                        </a-button>
                      </a-popconfirm>
                    </a-tooltip>

                    <a-tooltip :title="$t('create_login')">
                      <a-button
                        @click="createLogin(record)"
                        class="flex items-center space-x-1 text-blue-600 hover:text-blue-800 border-blue-200 hover:border-blue-300 rounded-lg px-3 py-1 transition-colors duration-200"
                        :disabled="!!record.user"
                      >
                        <UserAddOutlined />
                        <span style="white-space: nowrap">{{ $t("create_login") }}</span>
                      </a-button>
                    </a-tooltip>
                  </div>
                </template>

                <template v-else-if="column.dataIndex == 'avatar'">
                  <div class="flex justify-center">
                    <a-avatar
                      :src="record.avatar_url || '/default-avatar.png'"
                      :size="50"
                      class="border-2 border-gray-200"
                    >
                      <template v-if="!record.avatar_url" #icon>
                        <UserOutlined />
                      </template>
                    </a-avatar>
                  </div>
                </template>

                <template v-else-if="column.dataIndex == 'login'">
                  <div v-if="record['user']" class="flex items-center">
                    <MailOutlined class="mr-2 text-gray-400" />
                    <span class="text-gray-700" style="white-space: nowrap">
                      {{ record["user"].email }}
                    </span>
                  </div>
                  <a-tag
                    v-else
                    color="orange"
                    class="rounded-full px-3 py-1"
                    style="white-space: nowrap"
                  >
                    {{ $t("no_login") }}
                  </a-tag>
                </template>

                <template v-else-if="column.dataIndex == 'organizations'">
                  <div style="max-width: 200px">
                    <a-tag
                      v-for="org in record.organizations"
                      :key="org.id"
                      color="blue"
                      class="mb-1 rounded-full px-2 py-0.5 text-xs"
                      style="white-space: nowrap"
                    >
                      {{ org.abbr || org.full_name }}
                    </a-tag>
                  </div>
                </template>

                <template v-else-if="column.dataIndex == 'gender'">
                  <a-badge
                    :status="record.gender ? 'success' : 'default'"
                    :text="record.gender ? $t('female') : $t('male')"
                    style="white-space: nowrap"
                  />
                </template>

                <template v-else-if="column.dataIndex == 'name_zh'">
                  <div class="flex items-center" style="min-width: 150px">
                    <div
                      class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3 flex-shrink-0"
                    >
                      <template v-if="record.avatar_url">
                        <img
                          :src="record.avatar_url"
                          width="120"
                          height="120"
                          class="rounded-lg object-cover border-2 border-gray-200 mb-2"
                        />
                      </template>
                      <template v-else>
                        <UserOutlined class="text-blue-500 text-sm" />
                      </template>
                    </div>
                    <div>
                      <span class="font-medium text-gray-800 block">{{
                        record.name_zh
                      }}</span>
                      <span class="text-gray-500 text-sm">{{ record.name_fn }}</span>
                    </div>
                  </div>
                </template>

                <template v-else>
                  <span class="text-gray-700" style="white-space: nowrap">{{
                    record[column.dataIndex]
                  }}</span>
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
        content: { borderRadius: '12px', overflow: 'hidden' },
      }"
      :header-style="{
        borderBottom: '1px solid #e5e7eb',
        padding: '16px 24px',
        borderRadius: '12px 12px 0 0',
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
        borderRadius: '0 0 12px 12px',
      }"
      @cancel="handleModalCancel"
    >
      <a-form
        ref="modalRef"
        :model="modal.data"
        name="Member"
        :label-col="{ span: 6 }"
        :wrapper-col="{ span: 17 }"
        autocomplete="off"
        :rules="rules"
        :validate-messages="validateMessages"
        class="modal-form"
      >
        <!-- Avatar Upload Section -->
        <a-row :gutter="16" class="mb-6">
          <a-col :span="24">
            <div
              class="flex flex-col items-center justify-center p-4 border border-dashed border-gray-300 rounded-lg bg-gray-50"
            >
              <div class="flex flex-col sm:flex-row items-center gap-6 w-full max-w-md">
                <div class="flex-shrink-0">
                  <a-avatar
                    :src="modal.data.avatar_url || '/default-avatar.png'"
                    :size="100"
                    class="border-2 border-gray-300 shadow-sm"
                  >
                    <template v-if="!modal.data.avatar_url" #icon>
                      <UserOutlined />
                    </template>
                  </a-avatar>
                </div>
                <div class="flex-1 text-center sm:text-left">
                  <p class="font-medium text-gray-700 mb-2">{{ $t("avatar_preview") }}</p>
                  <p class="text-gray-500 text-sm mb-3">{{ $t("avatar_upload_tip") }}</p>
                  <div
                    class="flex flex-col sm:flex-row gap-2 justify-center sm:justify-start"
                  >
                    <a-upload
                      v-model:file-list="fileList"
                      name="avatar"
                      list-type="picture"
                      :show-upload-list="false"
                      :before-upload="beforeUpload"
                      :custom-request="handleAvatarUpload"
                      accept="image/*"
                    >
                      <a-button type="primary" size="small">
                        <UploadOutlined />
                        {{ $t("upload_avatar") }}
                      </a-button>
                    </a-upload>
                    <a-button
                      v-if="modal.data.avatar_url"
                      @click="removeAvatar"
                      size="small"
                      danger
                    >
                      <DeleteOutlined />
                      {{ $t("remove_avatar") }}
                    </a-button>
                  </div>
                </div>
              </div>
            </div>
          </a-col>
        </a-row>

        <!-- Form Fields -->
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item
              :label="$t('organizations')"
              name="organization_ids"
              :label-style="{ fontWeight: '500', color: '#374151' }"
            >
              <a-select
                v-model:value="modal.data.organization_ids"
                mode="multiple"
                show-search
                :filter-option="filterOption"
                :options="organizations"
                :fieldNames="{ value: 'id', label: 'full_name' }"
                class="w-full"
                :placeholder="$t('select_organizations')"
              />
            </a-form-item>

            <a-form-item
              :label="$t('name_zh')"
              name="name_zh"
              :label-style="{ fontWeight: '500', color: '#374151' }"
            >
              <a-input
                type="input"
                v-model:value="modal.data.name_zh"
                :placeholder="$t('enter_chinese_name')"
              />
            </a-form-item>

            <a-form-item
              :label="$t('name_fn')"
              name="name_fn"
              :label-style="{ fontWeight: '500', color: '#374151' }"
            >
              <a-input
                type="input"
                v-model:value="modal.data.name_fn"
                :placeholder="$t('enter_foreign_name')"
              />
            </a-form-item>

            <a-form-item
              :label="$t('email')"
              name="email"
              :label-style="{ fontWeight: '500', color: '#374151' }"
            >
              <a-input
                type="input"
                v-model:value="modal.data.email"
                :placeholder="$t('enter_email')"
              />
            </a-form-item>
          </a-col>

          <a-col :span="12">
            <a-form-item
              :label="$t('gender')"
              name="gender"
              :label-style="{ fontWeight: '500', color: '#374151' }"
            >
              <a-radio-group v-model:value="modal.data.gender" button-style="solid">
                <a-radio-button :value="0">{{ $t("male") }}</a-radio-button>
                <a-radio-button :value="1">{{ $t("female") }}</a-radio-button>
              </a-radio-group>
            </a-form-item>

            <a-form-item
              :label="$t('dob')"
              name="dob"
              :label-style="{ fontWeight: '500', color: '#374151' }"
            >
              <a-date-picker
                v-model:value="modal.data.dob"
                :format="dateFormat"
                :valueFormat="dateFormat"
                class="w-full"
                :placeholder="$t('select_dob')"
              />
            </a-form-item>

            <template v-if="modal.data.user_id">
              <a-form-item
                :label="$t('users')"
                name="user_id"
                :label-style="{ fontWeight: '500', color: '#374151' }"
              >
                <p class="text-gray-700 font-medium py-2 px-3 bg-gray-50 rounded border">
                  {{ modal.data.user?.email }}
                </p>
              </a-form-item>
            </template>
            <template v-else>
              <a-form-item
                :label="$t('users')"
                name="user_id"
                :label-style="{ fontWeight: '500', color: '#374151' }"
              >
                <a-select
                  v-model:value="modal.data.user_id"
                  show-search
                  :options="users"
                  :fieldNames="{ value: 'id', label: 'email' }"
                  class="w-full"
                  :placeholder="$t('select_user')"
                  allow-clear
                />
              </a-form-item>
            </template>
          </a-col>
        </a-row>
      </a-form>

      <template #footer>
        <div class="flex justify-end gap-3">
          <a-button @click="handleModalCancel" size="large">
            {{ $t("cancel") }}
          </a-button>
          <a-button
            v-if="modal.mode == 'EDIT'"
            key="Update"
            type="primary"
            @click="updateRecord()"
            :loading="submitting"
            size="large"
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
  TeamOutlined,
  UserOutlined,
  WomanOutlined,
  HomeOutlined,
  FilterOutlined,
  MailOutlined,
  UserAddOutlined,
  UploadOutlined,
} from "@ant-design/icons-vue";
import { message } from "ant-design-vue";

export default {
  components: {
    AdminLayout,
    PlusOutlined,
    EditOutlined,
    DeleteOutlined,
    TeamOutlined,
    UserOutlined,
    WomanOutlined,
    HomeOutlined,
    FilterOutlined,
    MailOutlined,
    UserAddOutlined,
    UploadOutlined,
  },
  props: ["organizations", "members", "users"],
  data() {
    return {
      dateFormat: "YYYY-MM-DD",
      searchText: "",
      submitting: false,
      fileList: [],
      modal: {
        isOpen: false,
        data: {},
        title: "Modal",
        mode: "",
      },
      columns: [
        {
          title: "Name",
          i18n: "name",
          dataIndex: "name_zh",
          width: 200,
          fixed: "left",
        },
        {
          title: "Login Email",
          i18n: "login_email",
          dataIndex: "login",
          width: 200,
        },
        {
          title: "Organizations",
          i18n: "organizations",
          dataIndex: "organizations",
          width: 180,
        },
        {
          title: "Gender",
          i18n: "gender",
          dataIndex: "gender",
          width: 100,
        },
        {
          title: "Date of Birth",
          i18n: "dob",
          dataIndex: "dob",
          width: 120,
        },
        {
          title: "Actions",
          i18n: "actions",
          dataIndex: "operation",
          key: "operation",
          width: 280,
          fixed: "right",
        },
      ],
      rules: {
        name_zh: { required: true },
        organization_ids: { required: true },
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
    filteredMembers() {
      if (!this.searchText) return this.members;
      return this.members.filter(
        (member) =>
          member.name_zh?.toLowerCase().includes(this.searchText.toLowerCase()) ||
          member.name_fn?.toLowerCase().includes(this.searchText.toLowerCase()) ||
          member.email?.toLowerCase().includes(this.searchText.toLowerCase())
      );
    },
  },
  methods: {
    filterOption(input, option) {
      return option.full_name.toLowerCase().indexOf(input.toLowerCase()) >= 0;
    },
    handleSearch(value) {
      this.searchText = value;
    },
    createRecord() {
      this.modal.data = {
        organization_ids: [],
        gender: 0,
      };
      this.modal.mode = "CREATE";
      this.modal.title = this.$t("create_member");
      this.modal.isOpen = true;
      this.fileList = [];
    },
    editRecord(record) {
      this.modal.data = { ...record };
      this.modal.data.organization_ids = record.organizations.map((item) => item.id);
      this.modal.mode = "EDIT";
      this.modal.title = this.$t("edit_member");
      this.modal.isOpen = true;
      this.fileList = [];
    },
    handleModalCancel() {
      this.modal.isOpen = false;
      this.fileList = [];
    },
    beforeUpload(file) {
      const isImage = file.type.startsWith("image/");
      if (!isImage) {
        message.error("只能上傳圖片文件！");
        return false;
      }

      const isLt2M = file.size / 1024 / 1024 < 2;
      if (!isLt2M) {
        message.error("圖片大小不能超過 2MB！");
        return false;
      }

      return true;
    },
    handleAvatarUpload(options) {
      const { file, onSuccess, onError } = options;

      // 這裡模擬上傳過程，實際應該調用您的上傳 API
      const reader = new FileReader();
      reader.onload = (e) => {
        // 模擬上傳延遲
        setTimeout(() => {
          // 這裡應該返回實際的圖片 URL
          const avatarUrl = e.target.result;
          this.modal.data.avatar_url = avatarUrl;
          onSuccess("上傳成功");
          message.success("頭像上傳成功！");
        }, 1000);
      };
      reader.onerror = () => {
        onError("上傳失敗");
        message.error("頭像上傳失敗！");
      };
      reader.readAsDataURL(file);
    },
    removeAvatar() {
      this.modal.data.avatar_url = null;
      this.fileList = [];
      message.info("頭像已移除");
    },
    storeRecord() {
      this.submitting = true;
      this.$refs.modalRef
        .validateFields()
        .then(() => {
          this.$inertia.post(route("admin.members.store"), this.modal.data, {
            onSuccess: (page) => {
              this.modal.isOpen = false;
              this.submitting = false;
              this.fileList = [];
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
            route("admin.members.update", this.modal.data.id),
            this.modal.data,
            {
              onSuccess: (page) => {
                this.modal.isOpen = false;
                this.submitting = false;
                this.fileList = [];
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
      this.$inertia.delete(route("admin.members.destroy", record.id), {
        onSuccess: (page) => {
          console.log(page);
        },
        onError: (error) => {
          console.log(error);
        },
      });
    },
    createLogin(record) {
      console.log("create login" + record.id);
    },
  },
};
</script>

<style scoped>
.modal-form .ant-form-item {
  margin-bottom: 16px;
}

.modal-form .ant-form-item-label > label {
  font-weight: 500;
  color: #374151;
}

:deep(.ant-upload-select) {
  display: block !important;
}
</style>
