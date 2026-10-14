<template>
  <AdminLayout title="Dashboard">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ $t('competition_score_categories') }}
      </h2>
    </template>
    <button
      @click="createRecord()"
      class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded my-3"
    >
      {{ $t("create") }}
    </button>
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
              {{ competitionScoreCategories.find(c=>c.value==text).label}}
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
          <a-select v-model:value="modal.data.category" :options="competitionScoreCategories"/>
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
  </AdminLayout>
</template>

<script>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { defineComponent, reactive } from "vue";

export default {
  components: {
    AdminLayout,
  },
  props: ["competitionScoreCategories","competitionScores"],
  data() {
    return {
      modal: {
        isOpen: false,
        data: {},
        title: "Modal",
        mode: "",
      },
      columns: [
        {
          title: "Category",
          i18n: "category",
          dataIndex: "category",
        },{
          title: "Title",
          i18n: "title",
          dataIndex: "title",
        },{
          title: "First",
          i18n: "competition_score_first",
          dataIndex: "first",
          width: 80,
        },{
          title: "Second",
          i18n: "competition_score_second",
          dataIndex: "second",
          width: 80,
        },{
          title: "Third",
          i18n: "competition_score_third",
          dataIndex: "third",
          width: 80,
        },{
          title: "Fifth",
          i18n: "competition_score_fifth",
          dataIndex: "fifth",
          width: 80,
        },{
          title: "Seventh",
          i18n: "competition_score_seventh",
          dataIndex: "seventh",
          width: 80,
        },{
          title: "Advance",
          i18n: "competition_score_advance",
          dataIndex: "advance",
          width: 80,
        },{
          title: "Participate",
          i18n: "competition_score_participate",
          dataIndex: "participate",
          width: 80,
        },{
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
        participate: { required: true }
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
