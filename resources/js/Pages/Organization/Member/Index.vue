<template>
  <OrganizationLayout :title="$t('members')" :breadcrumb="breadcrumb">
    <!-- 按鈕區域 -->
    <div class="flex-auto pb-3 text-right flex justify-end gap-2">
      <inertia-link
        :href="route('manage.members.create')"
        class="ant-btn ant-btn-primary"
        as="button"
      >
        {{ $t("create_member") }}
      </inertia-link>
      <a-button type="primary" class="!rounded" @click="() => (visible = true)">
        {{ $t("import_member") }}
      </a-button>
    </div>

    <!-- 會員列表表格 -->
    <div class="container mx-auto pt-5">
      <div class="bg-white relative shadow rounded-lg overflow-x-auto">
        <a-table :dataSource="members" :columns="columns">
          <template #headerCell="{ column }">
            {{ column.i18n ? $t(column.i18n) : column.title }}
          </template>
          <template #bodyCell="{ column, text, record, index }">
            <template v-if="column.dataIndex == 'operation'">
              <inertia-link
                :href="route('manage.members.show', record.id)"
                class="ant-btn"
              >
                {{ $t("view") }}
              </inertia-link>
              <inertia-link
                :href="route('manage.members.edit', record.id)"
                class="ant-btn"
                as="button"
              >
                {{ $t("edit") }}
              </inertia-link>
              <a-popconfirm
                :title="$t('confirm_delete_record')"
                :ok-text="$t('yes')"
                :cancel-text="$t('no')"
                @confirm="deleteConfirmed(record.id)"
              >
                <a-button>{{ $t("delete") }}</a-button>
              </a-popconfirm>
              <a-button @click="createLogin(record.id)" :disabled="record.user != null">
                {{ $t("create_login") }}
              </a-button>
            </template>
            <template v-else-if="column.dataIndex == 'tier' && record.current_tier">
              {{ record.current_tier.tier_code }}
            </template>
            <template v-else-if="column.dataIndex == 'avatar'">
              <img :src="record.avatar_url" width="60" />
            </template>
            <template v-else>
              {{ record[column.dataIndex] }}
            </template>
          </template>
        </a-table>
      </div>
    </div>

    <!-- 導入會員模態框 -->
    <a-modal
      title="Import Athletes List"
      v-model:visible="visible"
      @cancel="closeImportModal"
    >
      <a-upload-dragger
        v-model:fileList="files"
        name="file"
        :beforeUpload="() => false"
        :multiple="false"
        :maxCount="1"
        accept=".xlsx,.xls"
      >
        <p class="ant-upload-drag-icon">
          <FileExcelOutlined />
        </p>
        <p class="ant-upload-text">點擊或拖曳檔案到這裡導入會員</p>
        <p class="ant-upload-hint">
          支持 xlsx 檔案上傳。請在上傳前確認數據格式與模板一致。
        </p>
      </a-upload-dragger>

      <div v-if="imported" class="mt-3">
        <div v-if="errors.length === 0" class="font-bold my-3 text-green-500">
          導入成功！
        </div>
        <div v-else class="font-bold my-3 text-yellow-500">部分信息未成功導入</div>
        <p v-for="(error, index) in errors" :key="index" class="font-mono m-0">
          <WarningOutlined class="!text-yellow-500" />
          第 {{ error.row }} 行, {{ error.errors[0] }}
        </p>
      </div>

      <template #footer>
        <div class="flex w-full justify-between">
          <a :href="templateUrl" download>
            <a-button type="link">
              <template #icon>
                <DownloadOutlined />
              </template>
              下載會員列表模板
            </a-button>
          </a>
          <a-button
            type="primary"
            @click="handleImport"
            :disabled="files.length === 0"
            :loading="importLoading"
          >
            導入
          </a-button>
        </div>
      </template>
    </a-modal>
  </OrganizationLayout>
</template>

<script>
import OrganizationLayout from "@/Layouts/OrganizationLayout.vue";
import { Modal as PopupModal } from "ant-design-vue";
import { defineComponent } from "vue";
import {
  DownloadOutlined,
  FileExcelOutlined,
  WarningOutlined,
} from "@ant-design/icons-vue";

export default {
  components: {
    OrganizationLayout,
    PopupModal,
    DownloadOutlined,
    FileExcelOutlined,
    WarningOutlined,
  },
  props: ["memberTiers", "members", "organization"],
  data() {
    return {
      breadcrumb: [
        {
          label: this.$t("home"),
          url: route("manage.organization.show", this.organization.id),
        },
        { label: this.$t("members"), url: null },
      ],
      columns: [
        {
          title: "given_name",
          dataIndex: "given_name",
          i18n: "name_zh",
          responsive: ["md"],
        },
        {
          title: "family_name",
          dataIndex: "family_name",
          i18n: "name_fn",
        },
        {
          title: "gender",
          dataIndex: "gender",
          i18n: "gender",
        },
        {
          title: "dob",
          dataIndex: "dob",
          i18n: "dob",
        },
        {
          title: "tier",
          dataIndex: "tier",
          i18n: "tier",
          filters:
            this.memberTiers?.map((t) => ({ value: t.label, text: t.label })) || [],
          onFilter: (value, record) => record.current_tier?.tier_code === value,
        },
        {
          title: "state",
          dataIndex: "state",
          i18n: "state",
        },
        {
          title: "avatar",
          dataIndex: "avatar",
          i18n: "avatar",
        },
        {
          title: "Operation",
          dataIndex: "operation",
          key: "operation",
          i18n: "operation",
        },
      ],
      visible: false,
      imported: false,
      errors: [],
      files: [],
      importLoading: false,
      templateUrl: "/templates/athlete_list.xlsx",
    };
  },
  methods: {
    deleteConfirmed(recordId) {
      PopupModal.confirm({
        title: this.$t("confirm_delete"),
        content: this.$t("confirm_delete_content"),
        okText: this.$t("yes"),
        cancelText: this.$t("no"),
        onOk: () => {
          this.$inertia.delete(route("manage.members.destroy", { member: recordId }), {
            onSuccess: () => {
              this.$message.success(this.$t("delete_success"));
            },
            onError: () => {
              this.$message.error(this.$t("delete_failed"));
            },
          });
        },
      });
    },
    createLogin(recordId) {
      PopupModal.confirm({
        title: this.$t("create_login_confirm"),
        content: this.$t("create_login_content"),
        okText: this.$t("yes"),
        cancelText: this.$t("no"),
        onOk: () => {
          axios
            .post(route("manage.member.createLogin", recordId))
            .then((response) => {
              if (response.data.result === false) {
                PopupModal.warning({
                  title: "郵箱重複",
                  content: response.data.message,
                });
              } else {
                this.$message.success(this.$t("create_login_success"));
              }
            })
            .catch((error) => {
              console.error(error);
              this.$message.error(this.$t("create_login_failed"));
            });
        },
      });
    },
    closeImportModal() {
      this.visible = false;
      this.files = [];
      this.imported = false;
      this.errors = [];
      this.importLoading = false;
    },
    handleImport() {
      if (this.files.length === 0) {
        this.$message.warning("請先選擇檔案");
        return;
      }

      this.importLoading = true;
      const formData = new FormData();
      formData.append("file", this.files[0].originFileObj);

      window.axios
        .post(
          route("manage.organization.members.import", this.organization.id),
          formData,
          {
            headers: {
              "Content-Type": "multipart/form-data",
            },
          }
        )
        .then(({ data }) => {
          this.importLoading = false;
          this.$message.success("導入成功");
          this.files = [];
          this.errors = data.errors || [];
          this.imported = true;

          // 刷新會員列表
          this.$inertia.reload();
        })
        .catch((error) => {
          this.importLoading = false;
          console.error(error);
          this.$message.error("導入失敗");
        });
    },
  },
};
</script>
