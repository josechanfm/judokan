<template>
  <OrganizationLayout title="Dashboard">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ $t("question") }}
      </h2>
    </template>
    <p>Exam title: {{exam.title}}</p>
    <p>Exam category: {{exam.category_code}}</p>
    <div class="flex-auto pb-3 text-right">
        <a-button @click="createRecord()">Create</a-button>
    </div>
    <div class="container mx-auto pt-5">
      <div class="bg-white relative shadow rounded-lg overflow-x-auto">
        <a-table :dataSource="exam.questions" :columns="columns">
          <template #headerCell="{ column }">
            {{ column.i18n ? $t(column.i18n) : column.title }}
          </template>
          <template #bodyCell="{ column, text, record, index }">
            <template v-if="column.dataIndex == 'operation'">
              <a-button @click="editRecord(record)">Edit</a-button>
              <a-button :disabled="record.published" @click="deleteRecord(record)">{{ $t("delete") }}</a-button>
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
        name="Questions"
        layout="vertical"
        autocomplete="off"
        :rules="rules"
        :validate-messages="validateMessages"
      >
        <a-form-item :label="$t('question_title')" name="title">
          <a-input v-model:value="modal.data.title" />
        </a-form-item>
        <a-form-item :label="$t('question_type')" name="question_type">
            <a-input v-model:value="modal.data.type" />
        </a-form-item>
        <a-form-item :label="$t('question_options')" name="answer">
          <a-radio-group v-model:value="modal.data.answer" style="width:100%">
              <template v-for="option in modal.data.options">
                  <a-radio :style="virticalStyle" :value="option.value">
                    {{ option.label }}
                    <span class="float-right" type="delete" @click="removeOptionItem(option)">
                      <delete-outlined style="color:#eb2f96"/>
                    </span>
                  </a-radio>
              </template>
          </a-radio-group>
        </a-form-item>
        <a-form-item :label="$t('question_new_option')" name="question_new_option">
            <a-input-search v-model:value="modal.data.newOption" @search="onBlurNewOption">
              <template #enterButton>
                <a-button>Add</a-button>
              </template>
            </a-input-search>
        </a-form-item>
        <a-form-item :label="$t('question_answer')" name="question_answer">
            <a-input v-model:value="modal.data.answer" />
        </a-form-item>
        <a-form-item :label="$t('question_score')" name="question_score">
            <a-input v-model:value="modal.data.score" />
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
import { createVNode } from 'vue';
import { Modal } from 'ant-design-vue';
import { ExclamationCircleOutlined } from '@ant-design/icons-vue';
import { DeleteOutlined } from '@ant-design/icons-vue';


export default {
  components: {
    OrganizationLayout,
    ckeditor: CKEditor.component,
    UploadAdapter,
    //UploadAdapter
    createVNode,
    Modal,
    ExclamationCircleOutlined,
    DeleteOutlined
  },
  props: ["exam"],
  data() {
    return {
      dateFormat: "YYYY-MM-DD",
      randomList:[],
      modal: {
        isOpen: false,
        data: {},
        title: "Modal",
        mode: "",
      },
      virticalStyle:{
          display: 'block',
          height: '30px',
          lineHeight: '30px',
          width: '100%'
      },
      teacherStateLabels: {},
      editor: ClassicEditor,
      editorData: "<p>Content of the editor.</p>",
      editorConfig: {
        extraPlugins: [
          function (editor) {
            editor.plugins.get("FileRepository").createUploadAdapter = (loader) => {
              return new UploadAdapter(loader);
            };
          },
        ],
        // The configuration of the editor.
      },
      columns: [
        {
          title: "Title",
          i18n: "question_title",
          dataIndex: "title",
        },{
          title: "Options",
          i18n: "question_options",
          dataIndex: "options",
          key: "options",
        },{
          title: "Answer",
          i18n: "question_answer",
          dataIndex: "answer",
          key: "answer",
        },{
          title: "Score",
          i18n: "question_score",
          dataIndex: "score",
          key: "score",
        },{
          title: "Operation",
          i18n: "operation",
          dataIndex: "operation",
          key: "operation",
        },
      ],
      rules: {
        title: { required: true },
        options: { required: true },
        answer: { required: true },
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
  mounted() {
  },
  methods: {
    generateRandomList(question){
      this.randomList=[]
      for(var i=0; i<10; i++){
        var randomNumber=Math.floor(Math.random()*900)+100;
        this.randomList.push(randomNumber);
      }
      question.options.map((option,idx)=>{
        option.value=this.randomList[idx]
      })
      question.answer=null
    },
    createRecord() {
      this.modal.data = {};
      this.modal.data.published = 0;
      this.modal.mode = "CREATE";
      this.modal.title = "Create";
      this.modal.isOpen = true;
    },
    editRecord(record) {
      console.log(record);
      this.modal.data = _.cloneDeep(record);
      this.modal.mode = "EDIT";
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
            route("manage.exam.questions.update", {exam:this.modal.data.exam_id,question:this.modal.data.id}),
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
    uploader(editor) {
      editor.plugins.get("FileRepository").createUploadAdapter = (loader) => {
        return new UploadAdapter(loader);
      };
    },
    onBlurNewOption(value){
      if(this.randomList.length==0){
        this.generateRandomList(this.modal.data)
      }
      if(value){
        this.modal.data.options.push({value:this.randomList[this.modal.data.options.length],label:value})
        console.log(this.modal.data.options)
        console.log(this.exam.questions);
        this.modal.data.newOption=null
      }
    },
    removeOptionItem(option){
      Modal.confirm({
                title: '是否確定',
                icon: createVNode(ExclamationCircleOutlined),
                content: '刪除報名記錄?'+option.label,
                okText: '確定',
                cancelText: '取消',
                onOk: () => {
                  this.modal.data.options=this.modal.data.options.reduce((acc, curr) =>{
                    if(curr.value!==option.value){
                      acc.push(curr)
                    }
                    return acc
                  },[])

                }
            })

    }
  },
};
</script>
