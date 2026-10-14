<template>
  <OrganizationLayout title="表格列表" :breadcrumb="breadcrumb">
    {{ selectedDisplayName }}
    <!-- <a-select
      v-model:value="selectedDisplayName"
      style="width: 120px"
      :options="entryColumns.filter((c) => c.dataIndex.substring(0, 6) == 'extra_')"
      :field-names="{ value: 'dataIndex', label: 'title' }"
    />
    <a-button @click="createEventAttendees" :disabled="!selectedDisplayName"
      >Event Attendees</a-button
    > -->
    <a :href="route('manage.entry.export', form.id)" class="ant-btn">滙出Excel</a>
    <a-table
      :dataSource="entries"
      :columns="entryColumns"
      :row-selection="{ onChange: onChangeSelection, selectedRowKeys: selectedItems }"
      :rowKey="(record) => record.id"
    >
      <template #bodyCell="{ column, text, record, index }">
        <template v-if="column.dataIndex == 'operation'">
          <a-button @click="editRecord(record)">{{ $t("edit") }}</a-button>
          <a
            :href="route('manage.form.entry.success', { form: form, entry: record.id })"
            target="_blank"
            class="ant-btn"
            >{{ $t("receipt") }}</a
          >
          <a-popconfirm
            :title="$t('confirm_delete_record')"
            :ok-text="$t('yes')"
            :cancel-text="$t('no')"
            @confirm="deleteRecord(record)"
          >
            <a-button>{{ $t("delete") }}</a-button>
          </a-popconfirm>
        </template>

        <template v-else-if="column.dataIndex == 'created_at'">
          {{ record[column.dataIndex] }}
        </template>
        <template v-else>
          {{ record[column.dataIndex] }}
        </template>
      </template>
    </a-table>

    <!-- Modal Start-->
    <a-modal v-model:open="modal.isOpen" title="View Only" width="60%">
      <a-form
        :model="modal.data"
        ref="formRef"
        name="default"
        layout="vertical"
        :validate-messages="validateMessages"
      >
        <template v-for="field in form.fields" :key="field.id">
          <div v-if="form.require_member">
            <a-form-item
              label="Member Id"
              :name="field.id"
              :rules="[{ required: field.required }]"
            >
              <a-input v-model:value="$page.props.user.id" />
            </a-form-item>
          </div>

          <div v-if="field.type == 'input'">
            <a-form-item
              :label="field.field_label"
              :name="field.id"
              :rules="[{ required: field.required }]"
            >
              <a-input v-model:value="formData[field.id]" />
            </a-form-item>
          </div>
          <div v-else-if="field.type == 'textarea'">
            <a-form-item
              :label="field.field_label"
              :name="field.id"
              :rules="[{ required: field.required }]"
            >
              <a-textarea v-model:value="formData[field.id]" />
            </a-form-item>
          </div>
          <div v-else-if="field.type == 'richtext'">
            <a-form-item
              :label="field.field_label"
              :name="field.id"
              :rules="[{ required: field.required }]"
            >
              <quill-editor
                v-model:value="formData[field.id]"
                style="min-height: 200px"
              />
            </a-form-item>
          </div>
          <div v-else-if="field.type == 'radio'">
            <a-form-item
              :label="field.field_label"
              :name="field.id"
              :rules="[{ required: field.required }]"
            >
              <a-radio-group v-model:value="formData[field.id]">
                <a-radio
                  v-for="option in JSON.parse(field.options)"
                  :key="option.id"
                  :style="field.direction == 'H' ? '' : verticalStyle"
                  :value="option.value"
                  >{{ option.label }}</a-radio
                >
              </a-radio-group>
            </a-form-item>
          </div>
          <div v-else-if="field.type == 'checkbox'">
            <a-form-item
              :label="field.field_label"
              :name="field.id"
              :rules="[{ required: field.required }]"
            >
              <a-checkbox-group v-model:value="formData[field.id]">
                <a-checkbox
                  v-for="option in JSON.parse(field.options)"
                  :key="option.id"
                  :style="field.direction == 'H' ? '' : verticalStyle"
                  :value="option.value"
                  >{{ option.label }}</a-checkbox
                >
              </a-checkbox-group>
            </a-form-item>
          </div>
          <div v-else-if="field.type == 'dropdown'">
            <a-form-item
              :label="field.field_label"
              :name="field.id"
              :rules="[{ required: field.required }]"
            >
              <a-select
                v-model:value="formData[field.id]"
                :options="JSON.parse(field.options)"
              ></a-select>
            </a-form-item>
          </div>
          <div v-else-if="field.type == 'true_false'">
            <a-form-item
              :label="field.field_label"
              :name="field.id"
              :rules="[{ required: field.required }]"
            >
              <a-switch v-model:checked="formData[field.id]" />
            </a-form-item>
          </div>
          <div v-else-if="field.type == 'date'">
            <a-form-item
              :label="field.field_label"
              :name="field.id"
              :rules="[{ required: field.required }]"
            >
              <a-date-picker
                v-model:value="formData[field.id]"
                :format="dateFormat"
                :valueFormat="dateFormat"
              />
            </a-form-item>
          </div>
          <div v-else-if="field.type == 'datetime'">
            <a-form-item
              :label="field.field_label"
              :name="field.id"
              :rules="[{ required: field.required }]"
            >
              <a-date-picker
                v-model:value="formData[field.id]"
                show-time
                :format="dateTimeFormat"
                :valueFormat="dateTimeFormat"
              />
            </a-form-item>
          </div>
          <div v-else-if="field.type == 'email'">
            <a-form-item
              :label="field.field_label"
              :name="field.id"
              :rules="[{ required: field.required }, { type: 'email' }]"
            >
              <a-input v-model:value="formData[field.id]" />
            </a-form-item>
          </div>
          <div v-else-if="field.type == 'photo'">
            <a-button v-if="!formData[field.id]" @click="showCropModal = true">{{
              $t("upload_profile_image")
            }}</a-button>
            <a-button v-else @click="deletePhoto(field.id)">{{
              $t("delete_photo")
            }}</a-button>
            <CropperModal
              v-if="showCropModal"
              :minAspectRatioProp="{ width: 8, height: 8 }"
              :maxAspectRatioProp="{ width: 8, height: 8 }"
              @croppedImageData="setCroppedImageData"
              @showModal="showCropModal = false"
            />
            <div class="flex flex-wrap mt-4 mb-6">
              <div class="w-full md:w-1/2 px-3">
                <div v-if="avatarPreview !== null">
                  <img :src="avatarPreview" />
                </div>
                <div v-else>
                  <img :src="formData[field.id]" />
                </div>
              </div>
            </div>
          </div>
          <div v-else>
            <a-form-item
              :label="field.field_label"
              :name="field.id"
              :rules="[{ required: field.required }, { type: 'email' }]"
            >
              <p>Data type undefined</p>
            </a-form-item>
          </div>
        </template>
      </a-form>
      <template #footer>
        <a-button key="back" @click="modal.isOpen = false">{{ $t("cancel") }}</a-button>
        <a-button key="submit" type="primary" @click="updateRecord">{{
          $t("update")
        }}</a-button>
      </template>
    </a-modal>
    <!-- Modal End-->
  </OrganizationLayout>
</template>

<script>
import OrganizationLayout from "@/Layouts/OrganizationLayout.vue";
import { message } from "ant-design-vue";
import CropperModal from "@/Components/Member/CropperModal.vue";
import { quillEditor } from 'vue3-quill';

export default {
  components: {
    OrganizationLayout,
    CropperModal,
    quillEditor
  },
  props: ["form", "entries", "entryColumns"],
  data() {
    return {
      breadcrumb: [
        { label: "表格列表", url:route('manage.forms.index')},
        { label: "表格報名", url: null }
      ],
      dateFormat: "YYYY-MM-DD",
      selectedDisplayName: null,
      avatarPreview: null,
      formData: {},
      rowSelection: {},
      selectedItems: [],
      showCropModal: false,
      modal: {
        isOpen: false,
        data: {},
        title: "Modal",
        mode: "",
      },
      validateMessages: {
        required: "必填欄位 ",
        types: {
          email: "不是有效電郵",
          number: "不是數字格式",
        },
        number: {
          range: "必須介於 ${min} 至 ${max}",
        },
      },
      verticalStyle: {
        display: "flex",
        height: "30px",
        lineHeight: "30px",
        width: "100%",
        marginLeft: "20px",
      },
    };
  },
  methods: {
    onChangeSelection(a, b) {
      this.selectedItems = a;
    },
    createEventAttendees() {
      var data = {};
      this.entries.forEach((entry) => {
        if (this.selectedItems.includes(entry.id)) {
          if (entry[this.selectedDisplayName]) {
            data[entry.id] = { display_name: entry[this.selectedDisplayName] };
          }
        }
      });
      this.$inertia.post(route("manage.form.createEventAttendees", this.form.id), data, {
        onSuccess: (page) => {
          this.modal.isOpen = false;
          this.imageUrl = null;
          console.log("created");
        },
        onError: (err) => {
          console.log(err);
        },
      });
    },
    setCroppedImageData(data) {
      this.avatarPreview = data.imageUrl;
      this.formData[this.form.fields.find((x) => x.type == "photo").id] = data;
      //console.log(data);
    },
    deletePhoto(id) {
      this.formData["delete_photo"] = true;
      this.formData[id] = "";
    },
    updateRecord() {
      console.log(this.formData);
      this.$inertia.patch(
        route("manage.form.entries.update", { form: this.form, entry: this.modal.data }),
        { fields: this.formData },
        {
          onSuccess: (page) => {
            message.success(this.$t("update_success"));
            this.modal.isOpen = false;
          },
          onError: (err) => {
            console.log(err);
          },
        }
      );
    },
    editRecord(record) {
      this.formData = {};
      this.modal.data = record;
      this.modal.isOpen = true;
      this.modal.data.records.forEach((element) => {
        if (
          this.form.fields.find((x) => x.id == element.form_field_id).type == "checkbox"
        ) {
          this.formData[element.form_field_id] = JSON.parse(element.field_value);
        } else {
          this.formData[element.form_field_id] = element.field_value;
        }
      });
    },
    deleteRecord(record) {
      this.$inertia.delete(
        route("manage.form.entries.destroy", { form: this.form, entry: record }),
        {
          onSuccess: (page) => {
            message.success(this.$t("delete_success"));
          },
          onError: (err) => {
            console.log(err);
          },
        }
      );
    },
    getFieldValue(field) {
      const fv = this.modal.data.records.find((r) => r.form_field_id == field.id);
      if (fv) {
        return fv.field_value;
      }
      return "";
    },
  },
};
</script>
