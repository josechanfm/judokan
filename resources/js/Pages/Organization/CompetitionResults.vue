<template>
  <OrganizationLayout title="賽事結果" :breadcrumb="breadcrumb">
    <a-typography-title :level="4">{{ competition.title_zh }}</a-typography-title>
    <p>
      開始日期: {{ competition.start_date }}<br>
      結束日期: {{ competition.end_date }}
    </p>
    <a-row :gutter="10">
      <a-col flex="400px">
        <a-card title="Categories & Weights">
          <table>
            <tr v-for="cw in competition.categories_weights">
              <td width="100px">{{ cw.name }}</td>
              <td width="100px">
                <ol>
                  <li v-for="w in cw.female">
                    <input type="radio" class="form-radio" v-model="selectedCategoryWeight" @change="onChangeCW"
                      :value="cw.code + '|F|' + w.code" />&nbsp;{{ w.name }}
                  </li>
                </ol>
              </td>
              <td width="100px">
                <ol>
                  <li v-for="w in cw.male">
                    <input type="radio" class="form-radio" v-model="selectedCategoryWeight" @change="onChangeCW"
                      :value="cw.code + '|M|' + w.code" />&nbsp;{{ w.name }}
                  </li>
                </ol>
              </td>
            </tr>
          </table>
        </a-card>
      </a-col>
      <a-col flex="400px">
        <a-card :title="competition.title_zh">
          <template #extra>
            <a :href="route('manage.competition.applications.index', competition.id)">{{$t('applications') }}</a>
          </template>
          <template v-for="result in competitionResults">
            <a-row class="pb-5">
              <a-col :span="6">{{ result.label }}</a-col>
              <a-col :span="18">
                <template v-if="result.value == 'advance' || result.value == 'participate'">
                  <a-select v-model:value="result.application_id" mode="multiple" :options="athletes"
                    :fieldNames="{ value: 'id', label: 'display_name' }" :allowClear="true" style="width: 100%"
                    @change="onChangeResult(result)" />
                </template>

                <template v-else>
                  <a-select v-model:value="result.application_id" :options="athletes"
                    :fieldNames="{ value: 'id', label: 'display_name' }" :allowClear="true" style="width: 100%"
                    @change="onChangeResult(result)" />
                </template>
              </a-col>
            </a-row>
          </template>
          <a-button @click="submitResult">Submit</a-button>
        </a-card>
      </a-col>
      <a-col flex="auto">
        <div class="container mx-auto">
          <a-table :dataSource="participateAthletes" :columns="columns">

            <template #headerCell="{ column }">
              {{ column.i18n ? $t(column.i18n) : column.title }}
            </template>

            <template #bodyCell="{ column, text, record, index }">
              {{ text }}
            </template>
          </a-table>
        </div>
      </a-col>
    </a-row>


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
    OrganizationLayout, Modal, createVNode, ExclamationCircleOutlined
  },
  props: ["competitionResults", "competition"],
  data() {
    return {
      breadcrumb: [
        { label: "賽事列表", url: route('manage.competitions.index') },
        { label: "賽事結果", url: null }
      ],
      dateFormat: "YYYY-MM-DD",
      selectedCategoryWeight: null,
      participateAthletes: [],
      athletes: [],
      columns: [
        {
          title: "Display Name",
          i18n: "display_name",
          dataIndex: "display_name",
        }, {
          title: "Gender",
          i18n: "gender",
          dataIndex: "gender",
        }, {
          title: "Category",
          i18n: "category",
          dataIndex: "category",
        }, {
          title: "Weight",
          i18n: "weight",
          dataIndex: "weight",
        }, {
          title: "Result",
          i18n: "result_rank",
          dataIndex: "result_rank",
        }, {
          title: "Score",
          i18n: "result_score",
          dataIndex: "result_score",
        },
      ]
    };
  },
  created() {
    this.resetParams()
  },
  mounted() {
  },
  methods: {
    resetParams() {
      this.participateAthletes = []
      this.competition.applications.forEach(a => {
        if (a.result_rank) {
          this.participateAthletes.push(a)
        }
      })
      this.selectedCategoryWeight = null
      this.competitionResults.forEach(r => {
        if (r.value == 'advance' || r.value == 'participate') {
          r.application_id = []
        } else {
          r.application_id = null
        }
      })
    },
    onChangeCW() {
      const cw = this.selectedCategoryWeight.split('|');
      this.athletes = this.competition.applications.filter(a =>
        a.category == cw[0] && a.gender == cw[1] && a.weight == cw[2]
      );
      this.athletes.forEach(a => {
        this.competitionResults.forEach(r => {

          if (r.value == 'advance' || r.value == 'participate') {
            if (r.value == a.result_rank) {
              r.application_id.push(a.id)
              a.disabled = true
            }
          } else {
            if (r.value == a.result_rank) {
              r.application_id = a.id
              a.disabled = true
            }
          }
        })
      })
    },
    onChangeResult(result) {
      this.athletes.forEach(a => {
        a.disabled = false;
      })
      this.competitionResults.forEach(r => {
        if (Array.isArray(r.application_id)) {
          r.application_id.forEach(id => {
            this.athletes.find(a => a.id == id).disabled = true;
          })
        } else {
          if (r.application_id) {
            this.athletes.find(a => a.id == r.application_id).disabled = true;
          }
        }
      })
    },
    submitResult() {
      console.log(this.competitionResults)
      this.$inertia.post(
        route("manage.competition.results.store", { competition: this.competition }), {
        cgw: this.selectedCategoryWeight.split('|'),
        results: this.competitionResults
      }, {
        onSuccess: (page) => {
          console.log(page);
          this.resetParams()
          this.athletes = []
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
