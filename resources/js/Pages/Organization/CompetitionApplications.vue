<template>
  <OrganizationLayout title="報名列表" :breadcrumb="breadcrumb">
    <a :href="route('manage.competition.applications.receipts',{competition:competition.id,applicationIds:selectedRowKeyIds.toString()})" target="_blank" class="ant-btn">打印收據</a>
    <a :href="route('manage.competition.applications.export',competition.id)" class="ant-btn">滙出Excel</a>
    <a-table 
      :dataSource="competition.applications" 
      :columns="columns"
      :rowSelection="{
        selectedRowKeys:selectedRowKeyIds,
        onChange:onCheckChange,
        onSelectAll:onCheckSelectAll,
        onSelect:onCheckSelect}"
      rowKey="id"
    >
      <template #headerCell="{ column }">
        {{ column.title }}
      </template>
      <template #bodyCell="{ column, text, record, index }">
        <template v-if="column.dataIndex == 'operation'">
          <a :href="route('manage.competition.application.success',record.id)" target="_blank" class="ant-btn">{{ $t('receipt') }}</a>
          <a-button @click="editRecord(record)">{{ $t("edit") }}</a-button>
          <a-popconfirm
            :title="$t('confirm_delete_record') +record.name_zh+'/'+record.name_fn"
            :ok-text="$t('confirm')"
            :cancel-text="$t('aband')"
            @confirm="deleteRecord(record)"
          >
            <a-button>{{ $t("delete") }}</a-button>
          </a-popconfirm>
        </template>
        <template v-else-if="column.dataIndex == 'full_name'">
          {{ record.given_name }} {{ record.middle_name }} {{ record.family_name }}
        </template>
        <template v-else-if="column.dataIndex == 'age'">
          {{calculateAge(record.dob)}}      
        </template>
        <template v-else-if="column.dataIndex == 'avatar'">
          <img :src="record.avatar_url" width="60"/>
        </template>
        <template v-else-if="column.dataIndex == 'accepted'">
          <a-popconfirm
            :title="$t('sure_confirmed')+(record.accepted?$t('competition_unaccepted')+'?':$t('competition_accepted')+'?')"
            :ok-text="$t('confirm')"
            :cancel-text="$t('aband')"
            @confirm="acceptedConfirmed(record)"
          >
          <a-checkbox v-model:checked="record.accepted"/>
          </a-popconfirm>
        </template>
        <template v-else-if="column.dataIndex=='competition_result'">
          <a-select 
            v-if="competition.result_scores"
            v-model:value="record.result_rank"
            :options="competitionResults"
            style="width:100px"
            @change="onChangeResult(record)"
          />
        <!-- <span v-if="column.enabled">
          </span>
          <span v-else-if="record.result_rank">
            {{ competitionResults.find(r=>r.value==record.result_rank).label }} /
            {{ competition.result_scores[record.result_rank] }}
          </span> -->
        </template>
        <template v-else>
          {{ record[column.dataIndex] }}
        </template>
      </template>
    </a-table>
    <!-- Modal Start-->
    <a-modal v-model:visible="modal.isOpen" :title="modal.title" width="60%">
      <a-form
        ref="modalRef"
        :model="modal.data"
        name="Teacher"
        :label-col="{ span: 4 }"
        autocomplete="off"
        :rules="rules"
        :validate-messages="validateMessages"
      >
        <!-- <a-form-item label="Certificate name" name="name">
                <a-input v-model:value="modal.data.name" />
            </a-form-item> -->
        <a-form-item :label="$t('name_zh')" name="name_zh">
          <a-input v-model:value="modal.data.name_zh" />
        </a-form-item>
        <a-form-item :label="$t('name_fn')" name="name_fn">
          <a-input v-model:value="modal.data.name_fn" />
        </a-form-item>
        <a-row :span="24">
          <a-col :span="12">
            <a-form-item :label="$t('display_name')" name="display_name" :label-col="{ span: 8 }">
              <a-input v-model:value="modal.data.display_name" />
            </a-form-item>
            <a-form-item :label="$t('email')" name="email" :label-col="{ span: 8 }">
              <a-input v-model:value="modal.data.email" />
            </a-form-item>
            <a-form-item :label="$t('gender')" name="gender" :label-col="{ span: 8 }">
              <a-radio-group
                v-model:value="modal.data.gender"
                button-style="solid"
                @change="onChangeGender"
              >
                <a-radio-button value="M">{{$t('male')}}</a-radio-button>
                <a-radio-button value="F">{{$t('female')}}</a-radio-button>
              </a-radio-group>
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item :label="$t('id_num')" name="id_num" :label-col="{ span: 8 }">
              <a-input v-model:value="modal.data.id_num" />
            </a-form-item>
            <a-form-item :label="$t('mobile_number')" name="mobile" :label-col="{span:8}">
              <a-input v-model:value="modal.data.mobile" />
            </a-form-item>
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
            <a-form-item :label="$t('role')" name="role" :label-col="{ span: 8 }">
              <a-select v-model:value="modal.data.role" :options="competition.roles" />
            </a-form-item>
            <template v-if="modal.data.role == 'athlete'">
              <a-form-item
                :label="$t('category')"
                :label-col="{ span: 8 }"
                name="category"
              >
                <a-select
                  v-model:value="modal.data.category"
                  :options="competition.categories_weights"
                  :fieldNames="{ value: 'code', label: 'name' }"
                  @change="onChangeCategory"
                />
              </a-form-item>
              <a-form-item :label="$t('weight')" :label-col="{ span: 8 }" name="weight">
                <a-select
                  v-model:value="modal.data.weight"
                  :options="modal.data.weight_list"
                  :fieldNames="{ value: 'code', label: 'name' }"
                />
              </a-form-item>
            </template>
            <a-form-item :label="$t('competition_accepted')" name="accepted" :label-col="{ span: 8 }">
              <a-switch v-model:checked="modal.data.accepted" 
                :checked-children="$t('competition_accepted')"
                :un-checked-children="$t('competition_unaccepted')"
              />
            </a-form-item>
            <a-form-item :label="$t('competition_result')"  name="resule" :label-col="{span:8}">
              <a-select 
                v-model:value="modal.data.result_rank"
                :options="competitionResults"
                style="width:150px"
                @change="onChangeResult(record)"
              />
            </a-form-item>
          </a-col>
          <a-col :span="12">
              <img :src="modal.data.avatar_url" width="200"/>
          </a-col>
        </a-row>
      </a-form>
      <template #footer>
        <a-button
          v-if="modal.mode == 'EDIT'"
          key="Update"
          type="primary"
          @click="updateRecord()"
          >{{$t('update')}}</a-button
        >
        <a-button
          v-if="modal.mode == 'CREATE'"
          key="Store"
          type="primary"
          @click="storeRecord()"
          >{{$t('add')}}</a-button
        >
      </template>
    </a-modal>
    <!-- Modal End-->

  </OrganizationLayout>
</template>

<script>
import OrganizationLayout from "@/Layouts/OrganizationLayout.vue";
import { defineComponent, reactive } from "vue";
import dayjs from "dayjs";
import { Modal } from 'ant-design-vue';
import { ref, createVNode } from 'vue';
import { ExclamationCircleOutlined } from '@ant-design/icons-vue';

export default {
  components: {
    OrganizationLayout,Modal,createVNode,ExclamationCircleOutlined
  },
  props: ["competitionResults","competition"],
  data() {
    return {
      breadcrumb: [
        { label: "賽事列表", url: route('manage.competitions.index') },
        { label: "報名列表", url: null }
      ],
      dateFormat: "YYYY-MM-DD",
      modal: {
        isOpen: false,
        data: {},
        title: "Modal",
        mode: "",
      },
      rules: {
        name_fn: { required: true },
        display_name: { required: true },
        id_num: { required: true },
        dob: { required: true },
        role: { required: true },
        email: { required: true, type: "email" },
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
      selectedRowKeyIds:[],
    };
  },
  created() {
    //this.competitionResults.unshift({value:null,label:'---'})
  },
  computed: {
    columns() {
      return [
        {
          title: this.$t('name_zh'),
          i18n:"name_zh",
          dataIndex: "name_zh",
          key:'name_zh',
        },{
          title: this.$t('name_fn'),
          i18n:"name_fn",
          dataIndex: "name_fn",
          key: "name_fn",
        },{
          title: this.$t('gender'),
          i18n:"gender",
          dataIndex: "gender",
          key: "gender",
        },{
          title: this.$t('age'),
          i18n:"age",
          dataIndex: "age",
          key: "age",
        },{
          title: this.$t('dob'),
          i18n:"dob",
          dataIndex: "dob",
          key: "dob",
        },{
          title: this.$t('role'),
          i18n:"role",
          dataIndex: "role",
          key: "role",
        },{
          title: this.$t('category'),
          i18n:"category",
          dataIndex: "category",
          key: "category",
        },{
          title: this.$t('weight'),
          i18n:"weight",
          dataIndex: "weight",
          key: "weight",
        },{
          title: this.$t('avatar'),
          i18n:"avatar",
          dataIndex: "avatar",
          key: "avatar",
        },{
          title: this.$t('competition_accepted'),
          i18n:"competition_accepted",
          dataIndex: "accepted",
          key: "accepted",
          enabled: false,
          width: "150px"
        },{
          title: this.$t('competition_result'),
          i18n:"competition_result",
          dataIndex: "competition_result",
          key: "result",
          enabled: false,
          width: "150px"
        },{
          title: this.$t('operation'),
          i18n:"operation",
          dataIndex: "operation",
          key: "operation",
        },
      ]
    }
  },
  methods: {
    onChangeGender(gender) {
      this.modal.data.category = null;
      this.modal.data.weight = null;
    },
    onChangeCategory(category) {
      this.modal.data.weight = null;
      this.genWeightList();
    },
    editRecord(record) {
      this.modal.data = { ...record };
      //this.modal.data.dob=dayjs(this.modal.data.dob)
      this.modal.mode = "EDIT";
      this.modal.title = "Modify";
      this.modal.isOpen = true;
      if (this.modal.data.gender !== "" && this.modal.data.category !== "") {
        this.genWeightList();
      }
    },
    deleteRecord(record){
      Modal.confirm({
          title: '是否確定',
          icon: createVNode(ExclamationCircleOutlined),
          content: '刪除報名記錄?'+record.name_zh +' / ' + record.name_fn,
          okText: '確定',
          cancelText: '取消',
          onOk: () => {
            this.$inertia.delete(route('manage.competition.applications.destroy',
              {
                competition: this.competition.id,
                application: record.id,
              }),{
              onSuccess: (page) => {
                console.log(page);
              },
              onError: (error) => {
                console.log(error);
              },
            })
          }
      })

    }, 
    genWeightList() {
      if (this.modal.data.gender == "M") {
        this.modal.data.weight_list = this.competition.categories_weights.find(
          (c) => c.code == this.modal.data.category
        )["male"];
      } else {
        this.modal.data.weight_list = this.competition.categories_weights.find(
          (c) => c.code == this.modal.data.category
        )["female"];
      }
    },
    updateRecord() {
      this.$refs.modalRef
        .validateFields()
        .then(() => {
          this.$inertia.put(
            route("manage.competition.applications.update", {
              competition: this.competition.id,
              application: this.modal.data.id,
            }),
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
    calculateAge(birthDate) {
        if (!birthDate) return;
        const currentDate = new Date();
        if (new Date(birthDate) > currentDate) {
            var birthDate = null
            var years = null;
            var months = null;
            var days = null;
            return 'false';
        }

        const diffTime = currentDate - new Date(birthDate);
        const totalDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
        years = Math.floor(totalDays / 365.25);
        months = Math.floor((totalDays % 365.25) / 30.4375);
        days = Math.floor((totalDays % 365.25) % 30.4375);
        return years
    },
    disabledDate(current){
      return current>dayjs(new Date()).subtract(3,'year');
    },
    onCheckChange(selectedRowKeys, selectedRows){
    },
    onCheckSelect(record, selected, selectedRows){
      if(selected){
        this.selectedRowKeyIds.push(record.id)
      }else{
        console.log()
        this.selectedRowKeyIds=this.selectedRowKeyIds.filter(x=>x!=record.id)
        
      }
      //console.log(record, selected, selectedRows);
    },
    onCheckSelectAll(selected, selectedRows, changeRows) {
      selectedRows.forEach((r)=>{
        this.selectedRowKeyIds.push(r.id)
      })
      //console.log(selected, selectedRows, changeRows);
    },      
    acceptedConfirmed(record){
      record.accepted=!record.accepted
      console.log(record.accepted);
      this.$inertia.put(
        route("manage.competition.applications.update", {
          competition: this.competition.id,
          application: record.id,
        }),
        record,
        {
          onSuccess: (page) => {
            console.log(page);
          },
          onError: (error) => {
            console.log(error);
          },
        }
      );
    },
    onChangeResult(record){
      this.$inertia.put(
        route("manage.competition.applications.update", {
          competition: this.competition.id,
          application: record.id,
        }),
        record,
        {
          onSuccess: (page) => {
            console.log(page);
          },
          onError: (error) => {
            console.log(error);
          },
        }
      );
    }
  },
};
</script>
