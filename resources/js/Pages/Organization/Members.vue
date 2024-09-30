<template>
  <OrganizationLayout :title="$t('members')" :breadcrumb="breadcrumb">
    <div class="flex-auto pb-3 text-right">
      <a-button type="primary" class="!rounded" @click="createRecord()">{{
        $t("create_member")
      }}</a-button>
    </div>
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
                >{{ $t("view") }}</inertia-link
              >
              <a-button @click="editRecord(record)">{{ $t("edit") }}</a-button>
              <a-popconfirm
                :title="$t('confirm_delete_record')"
                :ok-text="$t('yes')"
                :cancel-text="$t('no')"
                @confirm="deleteConfirmed(record.id)"
              >
                <a-button>{{ $t("delete") }}</a-button>
              </a-popconfirm>
              <a-button @click="createLogin(record.id)" :disabled="record.user != null">{{
                $t("create_login")
              }}</a-button>
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

    <!-- Modal Start-->
    <a-modal v-model:visible="modal.isOpen" :title="$t(modal.title)" width="60%">
      <a-form
        ref="modalRef"
        :model="modal.data"
        name="memberTier"
        :label-col="{ span: 4 }"
        autocomplete="off"
        :rules="rules"
        :validate-messages="validateMessages"
      >
        <a-form-item :label="$t('tier')" name="tier">
          {{ modal.data.current_tier.tier_code }}
        </a-form-item>
        <a-row :span="24">
          <a-col :span="8">
            <a-form-item
              :label="$t('valid_at')"
              :label-col="{ span: 12 }"
              name="valid_at"
            >
              {{ modal.data.current_tier.valid_at }}
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item
              :label="$t('expired_at')"
              :label-col="{ span: 8 }"
              name="expired_at"
            >
              {{ modal.data.current_tier.expired_at }}
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <img :src="modal.data.avatar_url" width="200" />
          </a-col>
        </a-row>

        <a-row :span="24">
          <a-col :span="12">
            <a-form-item :label="$t('name_zh')" name="name_zh">
              <a-input v-model:value="modal.data.name_zh" /> </a-form-item
          ></a-col>
          <a-col :span="12">
            <a-form-item :label="$t('name_fn')" name="name_fn" :label-col="{ span: 8 }">
              <a-input v-model:value="modal.data.name_fn" />
            </a-form-item>
          </a-col>
        </a-row>
        <a-form-item :label="$t('display_name')" name="display_name">
          <a-input v-model:value="modal.data.display_name" />
        </a-form-item>
        <a-row :span="24">
          <a-col :span="12">
            <a-form-item :label="$t('gender')" name="gender" :label-col="{ span: 8 }">
              <a-radio-group v-model:value="modal.data.gender" button-style="solid">
                <a-radio-button value="M">{{ $t("male") }}</a-radio-button>
                <a-radio-button value="F">{{ $t("female") }}</a-radio-button>
              </a-radio-group>
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item :label="$t('dob')" name="dob" :label-col="{ span: 8 }">
              <a-date-picker
                v-model:value="modal.data.dob"
                :format="dateFormat"
                :valueFormat="dateFormat"
              />
            </a-form-item>
          </a-col>
        </a-row>
        <a-row :span="24">
          <a-col :span="12">
            <a-form-item
              :label="$t('email')"
              name="email"
              :label-col="{ span: 8 }"
              :wrapper-col="{ span: 14, offset: 0 }"
            >
              <a-input v-model:value="modal.data.email" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item
              :label="$t('mobile_number')"
              name="mobile"
              :label-col="{ span: 8 }"
            >
              <a-input v-model:value="modal.data.mobile" />
            </a-form-item>
          </a-col>
        </a-row>
        <div class="flex-auto pb-3 text-right">
          <a-button @click="addTier">{{ $t("add") }}{{ $t("tier") }}</a-button>
        </div>
        <div class="ant-table">
          <div class="ant-table-container pl-10 pr-10">
            <table style="table-layout: auto">
              <thead class="ant-table-thead">
                <tr>
                  <th>{{ $t("tier") }}</th>
                  <th class="text-right">{{ $t("valid_at") }}</th>
                  <th>{{ $t("expired_at") }}</th>
                  <th>{{ $t("operation") }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(tier, idx) in modal.data.tiers">
                  <td>
                    <a-form-item
                      :name="'tier_code_' + tier.id"
                      :rules="[
                        {
                          value: tier.tier_code,
                          validator: validateNotNull,
                          trigger: 'blur',
                        },
                      ]"
                    >
                      <a-select
                        name="tier_id"
                        v-model:value="tier.tier_code"
                        :options="memberTiers"
                        :fieldNames="{ value: 'label', label: 'label' }"
                        style="width: 200px"
                      />
                    </a-form-item>
                  </td>
                  <td>
                    <a-form-item
                      :name="'valid_at_' + tier.id"
                      :wrapper-col="{ span: 24, offset: 0 }"
                      :rules="[
                        {
                          value: tier.valid_at,
                          validator: validateNotNull,
                          trigger: 'blur',
                        },
                      ]"
                    >
                      <a-date-picker
                        v-model:value="tier.valid_at"
                        :format="dateFormat"
                        :valueFormat="dateFormat"
                      />
                    </a-form-item>
                  </td>
                  <td>
                    <a-form-item
                      :name="'expired_at_' + tier.id"
                      :wrapper-col="{ span: 24, offset: 0 }"
                    >
                      <a-date-picker
                        v-model:value="tier.expired_at"
                        :format="dateFormat"
                        :valueFormat="dateFormat"
                      />
                    </a-form-item>
                  </td>
                  <td class="align-top">
                    <a-popconfirm
                      title="Are you sure delete this item?"
                      ok-text="Yes"
                      cancel-text="No"
                      @confirm="deleteMemberTier(idx, tier)"
                    >
                      <a-button>{{ $t("delete") }}</a-button>
                    </a-popconfirm>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </a-form>
      <template #footer>
        <a-button key="back" @click="onCancelModal">返回</a-button>

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
import { Modal as PopupModal } from "ant-design-vue";
import { defineComponent, reactive } from "vue";

export default {
  components: {
    OrganizationLayout,
    PopupModal,
  },
  props: ["memberTiers", "members"],
  data() {
    return {
      breadcrumb: [{ label: "會員列表", url: null }],
      dateFormat: "YYYY-MM-DD",
      modal: {
        isOpen: false,
        data: {},
        title: "Modal",
        mode: "",
      },
      columns2: [
        {
          title: "Tier Code",
          dataIndex: "tier_code",
        },
        {
          title: "valid at",
          dataIndex: "valid_at",
        },
      ],
      columns: [
        {
          title: "Name Zh",
          dataIndex: "name_zh",
          i18n: "name_zh",
          responsive: ["md"],
        },
        {
          title: "Name Fn",
          dataIndex: "name_fn",
          i18n: "name_fn",
        },
        {
          title: "Gender",
          dataIndex: "gender",
          i18n: "gender",
        },
        {
          title: "Date of birth",
          dataIndex: "dob",
          i18n: "dob",
        },
        {
          title: "Tier",
          dataIndex: "tier",
          i18n: "tier",
          filters: this.memberTiers.map((t) => ({ value: t.label, text: t.label })),
          onFilter: (value, record) => record.current_tier.tier_code === value,
        },
        {
          title: "State",
          dataIndex: "state",
          i18n: "state",
        },
        {
          title: "Avatar",
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
      rules: {
        name_zh: { required: true },
        name_fn: { required: true },
        gender: { required: true },
        dob: { required: true },
        email: { required: true, type: "email" },
        state: { required: true },
        // tier_code:{
        //   required:true,
        //   validator:this.validateMemberTier,
        //   trigger: 'change'
        // }
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
    onCancelModal() {
      this.modal.data = {};
      this.modal.data.current_tier = {};
      this.modal.isOpen = false;
    },
    createRecord() {
      this.modal.data = {};
      this.modal.data.current_tier = {};
      this.modal.mode = "CREATE";
      this.modal.title = "create";
      this.modal.isOpen = true;
    },
    editRecord(record) {
      this.modal.data = { ...record };
      this.modal.data.current_tier = { ...record.current_tier };
      this.modal.mode = "EDIT";
      this.modal.title = "edit";
      this.modal.isOpen = true;
    },
    storeRecord() {
      this.$refs.modalRef
        .validateFields()
        .then(() => {
          this.$inertia.post(route("manage.members.store"), this.modal.data, {
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
      this.$refs.modalRef
        .validateFields()
        .then(() => {
          this.$inertia.patch(
            route("manage.members.update", this.modal.data.id),
            this.modal.data,
            {
              onSuccess: (page) => {
                this.modal.isOpen = false;
                //this.modal.data = {};
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
    deleteConfirmed(recordId) {
      this.$inertia.delete(route("manage.members.destroy", { member: recordId }));
    },

    createLogin(recordId) {
      axios.post(route("manage.member.createLogin", recordId)).then((response) => {
        if (response.data.result == false) {
          PopupModal.warning({
            title: "Email Duplicated",
            content: response.data.message,
          });
        }
        this.modal.data = {};
        this.modal.isOpen = false;
      });
    },
    addTier() {
      this.modal.data.tiers.unshift({
        tier_code: null,
        valid_at: null,
        expired_at: null,
      });
    },
    validateNotNull(rule) {
      if (rule.value) {
        return Promise.resolve();
      } else {
        return Promise.reject("必填欄位 Required input!");
      }
    },
    deleteMemberTier(tierIdx, tier) {
      if (tier.id) {
        console.log("to delete record: " + tier.id);
      } else {
        this.modal.data.tiers.splice(tierIdx, 1);
      }
    },
  },
};
</script>
