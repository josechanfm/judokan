<template>
  <AdminLayout title="Dashboard">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ $t("competition_score_categories") }}
      </h2>
    </template>
    <!-- 工具列 -->
    <div class="my-3 flex flex-wrap items-center gap-2">
      <a-button type="primary" @click="createRecord()">
        <PlusOutlined />
        {{ $t("create") }}
      </a-button>
      <a-button @click="openImportModal">
        <UploadOutlined />
        {{ $t("import_competition_score") }}
      </a-button>
      <a :href="templateUrl" download class="ant-btn toolbar-link">
        <DownloadOutlined />
        {{ $t("download_template") }}
      </a>
    </div>
    <div class="container mx-auto pt-5">
      <div class="bg-white relative shadow rounded-lg overflow-x-auto">
        <a-table :dataSource="competitionScores" :columns="columns">
          <template #headerCell="{ column }">
            {{ column.i18n ? $t(column.i18n) : column.title }}
          </template>
          <template #bodyCell="{ column, text, record, index }">
            <template v-if="column.dataIndex == 'operation'">
              <!-- <inertia-link
                :href="route('admin.competitions.index')"
                class="ant-btn"
                >{{ $t("competitions") }}</inertia-link
              > -->
              <a-button @click="editRecord(record)">{{ $t("edit") }}</a-button>
              <a-popconfirm
                :title="$t('confirm_delete_record')"
                :ok-text="$t('yes')"
                :cancel-text="$t('no')"
                @confirm="deleteRecord(record)"
              >
                <a-button>{{ $t("delete") }}</a-button>
              </a-popconfirm>
            </template>
            <template v-if="column.dataIndex == 'category'">
              {{ competitionScoreCategories.find((c) => c.value == text).label }}
            </template>
            <template v-else>
              {{ record[column.dataIndex] }}
            </template>
          </template>
        </a-table>
      </div>
    </div>
    <!-- Modal Start-->
    <a-modal v-model:open="modal.isOpen" :title="modal.title" width="60%">
      <a-form
        ref="modalRef"
        :model="modal.data"
        name="Organization"
        :label-col="{ span: 8 }"
        :wrapper-col="{ span: 16 }"
        autocomplete="off"
        :rules="rules"
        :validate-messages="validateMessages"
      >
        <a-form-item :label="$t('category')" name="category">
          <a-select
            v-model:value="modal.data.category"
            :options="competitionScoreCategories"
          />
        </a-form-item>
        <a-form-item :label="$t('title')" name="title">
          <a-input v-model:value="modal.data.title" />
        </a-form-item>
        <a-form-item :label="$t('competition_score_first')" name="first">
          <a-input v-model:value="modal.data.first" />
        </a-form-item>
        <a-form-item :label="$t('competition_score_second')" name="second">
          <a-input v-model:value="modal.data.second" />
        </a-form-item>
        <a-form-item :label="$t('competition_score_third')" name="third">
          <a-input v-model:value="modal.data.third" />
        </a-form-item>
        <a-form-item :label="$t('competition_score_fifth')" name="fifth">
          <a-input v-model:value="modal.data.fifth" />
        </a-form-item>
        <a-form-item :label="$t('competition_score_seventh')" name="seventh">
          <a-input v-model:value="modal.data.seventh" />
        </a-form-item>
        <a-form-item :label="$t('competition_score_advance')" name="advance">
          <a-input v-model:value="modal.data.advance" />
        </a-form-item>
        <a-form-item :label="$t('competition_score_participate')" name="participate">
          <a-input v-model:value="modal.data.participate" />
        </a-form-item>
      </a-form>
      <template #footer>
        <a-button
          v-if="modal.mode == 'EDIT'"
          key="Update"
          type="primary"
          @click="updateRecord()"
          >{{ $t("update") }}</a-button
        >
        <a-button
          v-if="modal.mode == 'CREATE'"
          key="Store"
          type="primary"
          @click="storeRecord()"
          >{{ $t("add") }}</a-button
        >
      </template>
    </a-modal>
    <!-- Modal End-->

    <!-- 導入積分表 Modal Start-->
    <a-modal
      v-model:open="importModal.visible"
      :title="$t('import_competition_score')"
      :width="640"
      @cancel="closeImportModal"
    >
      <a-upload-dragger
        v-model:fileList="importModal.files"
        name="file"
        :beforeUpload="() => false"
        :multiple="false"
        :maxCount="1"
        accept=".xlsx,.xls"
      >
        <p class="ant-upload-drag-icon">
          <FileExcelOutlined />
        </p>
        <p class="ant-upload-text">點擊或拖曳檔案到這裡導入積分表</p>
        <p class="ant-upload-hint">
          支援 .xlsx / .xls 檔案，一次一個。請先下載下方的積分表範本填寫，
          組別可填代號（例如 LOCAL）或名稱（例如 本地）， 同一組別且名稱重複的列會被略過。
        </p>
      </a-upload-dragger>

      <div v-if="importModal.done" class="mt-3">
        <div v-if="importModal.errors.length === 0" class="my-3 font-bold text-green-600">
          成功導入 {{ importModal.count }} 筆積分表
        </div>
        <div v-else class="my-3 font-bold text-yellow-600">
          成功導入 {{ importModal.count }} 筆，以下
          {{ importModal.errors.length }} 筆未導入
        </div>
        <p
          v-for="(error, index) in importModal.errors"
          :key="index"
          class="m-0 font-mono"
        >
          <WarningOutlined class="!text-yellow-500" />
          第 {{ error.row }} 行（{{ error.attribute }}）: {{ error.errors[0] }}
        </p>
      </div>

      <template #footer>
        <div class="flex w-full items-center justify-between">
          <a :href="templateUrl" download class="ant-btn ant-btn-link toolbar-link">
            <DownloadOutlined />
            下載積分表範本
          </a>
          <a-button
            type="primary"
            :loading="importModal.loading"
            :disabled="importModal.files.length === 0"
            @click="handleImport"
          >
            {{ $t("import") }}
          </a-button>
        </div>
      </template>
    </a-modal>
    <!-- 導入積分表 Modal End-->
  </AdminLayout>
</template>

<script>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { defineComponent, reactive } from "vue";
import {
  DownloadOutlined,
  FileExcelOutlined,
  PlusOutlined,
  UploadOutlined,
  WarningOutlined,
} from "@ant-design/icons-vue";
import { message } from "ant-design-vue";

export default {
  components: {
    AdminLayout,
    DownloadOutlined,
    FileExcelOutlined,
    PlusOutlined,
    UploadOutlined,
    WarningOutlined,
  },
  props: ["competitionScoreCategories", "competitionScores"],
  data() {
    return {
      modal: {
        isOpen: false,
        data: {},
        title: "Modal",
        mode: "",
      },
      importModal: {
        visible: false,
        files: [],
        done: false,
        count: 0,
        errors: [],
        loading: false,
      },
      templateUrl: "/templates/competition_score_list.xlsx",
      columns: [
        {
          title: "Category",
          i18n: "category",
          dataIndex: "category",
        },
        {
          title: "Title",
          i18n: "title",
          dataIndex: "title",
        },
        {
          title: "First",
          i18n: "competition_score_first",
          dataIndex: "first",
          width: 80,
        },
        {
          title: "Second",
          i18n: "competition_score_second",
          dataIndex: "second",
          width: 80,
        },
        {
          title: "Third",
          i18n: "competition_score_third",
          dataIndex: "third",
          width: 80,
        },
        {
          title: "Fifth",
          i18n: "competition_score_fifth",
          dataIndex: "fifth",
          width: 80,
        },
        {
          title: "Seventh",
          i18n: "competition_score_seventh",
          dataIndex: "seventh",
          width: 80,
        },
        {
          title: "Advance",
          i18n: "competition_score_advance",
          dataIndex: "advance",
          width: 80,
        },
        {
          title: "Participate",
          i18n: "competition_score_participate",
          dataIndex: "participate",
          width: 80,
        },
        {
          title: "Operation",
          i18n: "operation",
          dataIndex: "operation",
          key: "operation",
        },
      ],
      rules: {
        category: { required: true },
        title: { required: true },
        first: { required: true },
        second: { required: true },
        third: { required: true },
        fifth: { required: true },
        seventh: { required: true },
        advance: { required: true },
        participate: { required: true },
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
  methods: {
    openImportModal() {
      this.importModal.visible = true;
    },
    closeImportModal() {
      this.importModal.visible = false;
      this.importModal.files = [];
      this.importModal.done = false;
      this.importModal.count = 0;
      this.importModal.errors = [];
      this.importModal.loading = false;
    },
    handleImport() {
      if (this.importModal.files.length === 0) {
        message.warning("請先選擇檔案");
        return;
      }

      this.importModal.loading = true;

      const formData = new FormData();
      formData.append("file", this.importModal.files[0].originFileObj);

      window.axios
        .post(route("admin.competitionScores.import"), formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        })
        .then(({ data }) => {
          this.importModal.loading = false;
          this.importModal.files = [];
          this.importModal.count = data.imported || 0;
          this.importModal.errors = data.errors || [];
          this.importModal.done = true;

          message.success(`成功導入 ${this.importModal.count} 筆積分表`);

          // 刷新列表（preserveState 讓上面的導入結果不會被清掉）
          this.$inertia.reload({ preserveState: true });
        })
        .catch((error) => {
          this.importModal.loading = false;

          const validationErrors = error?.response?.data?.errors;
          message.error(
            validationErrors
              ? Object.values(validationErrors)[0][0]
              : "導入失敗，請稍後再試"
          );
        });
    },
    createRecord() {
      this.modal.data = {};
      this.modal.mode = "CREATE";
      this.modal.title = "Create Record";
      this.modal.isOpen = true;
    },
    editRecord(record) {
      this.modal.data = { ...record };
      this.modal.mode = "EDIT";
      this.modal.title = "Edit Record";
      this.modal.isOpen = true;
    },
    storeRecord() {
      this.$refs.modalRef
        .validateFields()
        .then(() => {
          this.$inertia.post(route("admin.competitionScores.store"), this.modal.data, {
            onSuccess: (page) => {
              this.modal.data = {};
              this.modal.isOpen = false;
            },
            onError: (err) => {
              console.log(err);
            },
          });
        })
        .catch((err) => {
          console.log(err);
        });
    },
    updateRecord() {
      console.log(this.modal.data);
      this.$refs.modalRef
        .validateFields()
        .then(() => {
          this.$inertia.patch(
            route("admin.competitionScores.update", this.modal.data.id),
            this.modal.data,
            {
              onSuccess: (page) => {
                this.modal.data = {};
                this.modal.isOpen = false;
                console.log(page);
              },
              onError: (error) => {
                console.log(error);
              },
            }
          );
        })
        .catch((err) => {
          console.log("error", err);
        });
    },
    deleteRecord(record) {
      this.$inertia.delete(route("admin.competitionScores.destroy", record.id), {
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
.ant-btn.toolbar-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  line-height: 1;
  text-decoration: none;
}
.ant-btn.toolbar-link:hover {
  text-decoration: none;
}
</style>
