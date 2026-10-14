<template>
  <OrganizationLayout title="Dashboard">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ $t("paper") }}
      </h2>
    </template>
    <p>Exam Title: {{ exam.title }}</p>
    <p>Exam Category: {{ exam.category }}</p>
    <div class="flex-auto pb-3 text-right">
        <a-button @click="createRecord()">Create</a-button>
    </div>
    <div class="container mx-auto pt-5">
      <div class="bg-white relative shadow rounded-lg overflow-x-auto">
        <a-table :dataSource="exam.papers" :columns="columns">
          <template #headerCell="{ column }">
            {{ column.i18n ? $t(column.i18n) : column.title }}
          </template>
          <template #bodyCell="{ column, text, record, index }">
            <template v-if="column.dataIndex == 'operation'">
              <a-button @click="editRecord(record)">Edit</a-button>
              <a-button :disabled="record.published" @click="deleteRecord(record)">{{ $t("delete") }}</a-button>
              <inertia-link :href="route('manage.paper.answers.index',record.id)" class="ant-btn">Answers</inertia-link>
            </template>
            <template v-if="column.dataIndex=='user'">
                {{record.user.name}}
            </template>
            <template v-else>
              {{ record[column.dataIndex] }}
            </template>
          </template>
        </a-table>
      </div>
      
    </div>
    <!-- Modal Start-->
    <a-modal v-model:open="modal.isOpen" :title="modal.title" >
      <a-form
        ref="modalRef"
        :model="modal.data"
        name="Papers"
        layout="vertical"
        autocomplete="off"
        :rules="rules"
        :validate-messages="validateMessages"
      >
        <a-form-item :label="$t('user_id')" name="user_id">
          <span v-if="modal.data.user">{{ modal.data.user.name }}</span>
          <span v-else>
            <a-select v-model:value="modal.data.user_id" :options="users" :fieldNames="{value:'id',label:'name'}"/>
          </span>
        </a-form-item>
        <a-form-item :label="$t('valid_at')" name="valid_at">
          <a-date-picker v-model:value="modal.data.valid_at" :format="dateFormat" :valueFormat="dateFormat"/>
        </a-form-item>
        <a-form-item :label="$t('expire_at')" name="expire_at">
          <a-date-picker v-model:value="modal.data.expire_at" :format="dateFormat" :valueFormat="dateFormat"/>
        </a-form-item>
        <a-form-item :label="$t('submitted')" name="submitted">
            <a-switch v-model:checked="modal.submitted" checkedValue="true" unCheckValue="false"/>
        </a-form-item>
        <a-form-item :label="$t('published')" name="published">
            <a-switch v-model:checked="modal.published" checkedValue="true" unCheckValue="false"/>
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
  </OrganizationLayout>
</template>

<script>
import OrganizationLayout from "@/Layouts/OrganizationLayout.vue";
import { defineComponent, reactive } from "vue";
//import Editor from 'ckeditor5-custom-build/build/ckeditor';
import CKEditor from "@ckeditor/ckeditor5-vue";
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";
import UploadAdapter from "@/Components/ImageUploadAdapter.vue";

export default {
  components: {
    OrganizationLayout,
    ckeditor: CKEditor.component,
    UploadAdapter,
    //UploadAdapter
  },
  props: ["exam","users"],
  data() {
    return {
      dateFormat: "YYYY-MM-DD",
      modal: {
        isOpen: false,
        data: {},
        title: "Modal",
        mode: "",
      },
      columns: [
        {
          title: "User",
          i18n: "user",
          dataIndex: "user",
        },{
          title: "Score",
          i18n: "score",
          dataIndex: "score",
        },{
          title: "Submitted",
          i18n: "submitted",
          dataIndex: "submitted",
        },{
          title: "Valid At",
          i18n: "valid_at",
          dataIndex: "valid_at",
        },{
          title: "Expire At",
          i18n: "expire_at",
          dataIndex: "expire_at",
        },{
          title: "Operation",
          i18n: "operation",
          dataIndex: "operation",
          key: "operation",
        },
      ],
      rules: {
        category_code: { required: true },
        classify_id: { required: true },
        title_en: { required: true },
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
  mounted() {},
  methods: {
    createRecord() {
      this.modal.data = {};
      this.modal.data.published = 0;
      this.modal.mode = "CREATE";
      this.modal.title = "Create";
      this.modal.isOpen = true;
    },
    editRecord(record) {
      this.modal.data = { ...record };
      this.modal.mode = "EDIT";
      this.modal.title = "Edit";
      this.modal.isOpen = true;
    },
    storeRecord() {
      this.$refs.modalRef
        .validateFields()
        .then(() => {
          this.$inertia.post(route("manage.exams.store"), this.modal.data, {
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
          this.$inertia.put(
            route("manage.exams.update", this.modal.data.id),
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
    deleteConfirmed(record) {
      this.$inertia.delete(route("manage.exams.destroy", record.id), {
        onSuccess: (page) => {
          console.log(page);
        },
        onError: (error) => {
          console.log(error);
        },
      });
    },
    deleteRecord(recordId) {
    if (!confirm('Are you sure want to remove?')) return;
    this.$inertia.delete(route('manage.exams.destroy', recordId), {
        onSuccess: (page) => {
            console.log(page);
        },
        onError: (error) => {
            console.log(error);
        }
    });
    },
    createLogin(recordId) {
      console.log("create login" + recordId);
    },
    uploader(editor) {
      editor.plugins.get("FileRepository").createUploadAdapter = (loader) => {
        return new UploadAdapter(loader);
      };
    },
  },
};
</script>
