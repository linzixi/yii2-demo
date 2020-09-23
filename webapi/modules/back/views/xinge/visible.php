<?php $baseUrl = $this->theme->baseUrl; ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>机会分配可见</title>
    <link rel="stylesheet" type="text/css" href="<?= $baseUrl; ?>/css/style.css"/>
    <script src="/static/js/vue.js" type="text/javascript" charset="utf-8"></script>
    <script src="/static/js/modal.js" type="text/javascript" charset="utf-8"></script>
    <script src="/static/js/page.js" type="text/javascript" charset="utf-8"></script>
    <script src="/static/js/jquery-1.7.2.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="/static/js/loading.js" type="text/javascript" charset="utf-8"></script>
    <script src="/static/js/vue-datepicker-local.js" type="text/javascript" charset="utf-8"></script>
    <link rel="stylesheet" type="text/css" href="/static/css/vue-datepicker-local.css"/>
    <link rel="stylesheet" type="text/css" href="<?= $baseUrl; ?>/css/iview.css"/>
</head>
<body>
<div id="app" style="display: none;">
    <div class="container-body">
        <div class="list">
            <div class="top">
                <span class='listTitle'>机会分配可见-{{chance_title}}</span>
            </div>
            <div class="content">
                <div class="searchOptions">
                    <div class="sItem">
                        <label>昵称</label>
                        <input class='searchTip' v-model="nickname" type="text" placeholder="请输入昵称"/>
                    </div>
                    <div class="sItem">
                        <label>手机号</label>
                        <input class='searchTip'v-model="tel" type="text" placeholder="请输入手机号"/>
                    </div>
                    <div class="sItem">
                        <label>状态</label>
                        <select class='searchTip' v-model="status">
                            <option value ="0">全部</option>
                            <option value ="1">未过期</option>
                            <option value ="2">已过期</option>
                        </select>
                    </div>
                    <span class="btn btnSmall btnHandle" @click="getList"><img src="/static/img/icon_search.png" >查询</span>
                </div>
            </div>
            <table class='tableList'>
                <thead>
                <tr>
                    <th>昵称</th>
                    <th>手机号</th>
                    <th>开通时间</th>
                    <th>到期时间</th>
                    <th>用户状态</th>
                    <th>是否可见</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(item,index) in list" :key="index">
                    <td>{{item.nickname}}</td>
                    <td>{{item.tel}}</td>
                    <td>{{item.start_time}}</td>
                    <td>{{item.end_time}}</td>
                    <th>{{item.status_result}}</th>
                    <td><label><input class="mui-switch mui-switch-anim" type="checkbox" :checked="item.is_visible==!0" @change="toSwitch(index)"></label></td>
                </tr>
                </tbody>
            </table>
            <page-component v-if="totalnums != ''" :currentpage.sync="page" :limit.sync="limit" :totalnums="totalnums" @turn="getList"></page-component>
        </div>
    </div>
    <!-- 右边部分结束 -->
    <loading-component :active='loadShow'></loading-component>
    <toast ref="toast" :message='message'></toast>
</div>
</body>
<script>
    window.onload = function () {
        $("#app").show()
    }
    window.App = new Vue({
        el: '#app',
        mounted() {

        },
        data() {
            return {
                list:[],
                state:false,
                page:1,
                limit:10,
                totalnums:0,
                nickname:'',
                tel:'',
                status:0,
                loadShow:false,
                modifyShow:false,
                message:'',
                chance_id:'',
                chance_title:'',
                loading:'loading',
                showButton:true,
            }
        },
        created() {
            var url = location.search;
            if (url.indexOf("?") != -1) {
                var strs = url.substr(1);
                var id = strs.split("chance_id=")[1];
                this.chance_id = id;
            }
            this.getList()
        },
        methods: {
            openModal () {
                this.$refs.modal.openModal();
            },
            addModal () {
                this.$refs.increase.openModal();
            },
            toModify (item) {
                var data  = {id:item.id};
                this.$refs.increase.openModal();
            },
            getList(){
                var that = this;
                this.loadShow=true;
                var data = {
                    "chance_id":this.chance_id,
                    "page":that.page,
                    "limit":that.limit,
                    "nickname":that.nickname,
                    "tel":that.tel,
                    "status":that.status,
                };
                $.post("/back/xinge/set-visible",data,function (data) {
                    if (data.code == 10000) {
                        that.list = data.data.data;
                        that.totalnums = data.data.count;
                        that.chance_title = data.data.chance_title;
                        that.loadShow=false;
                    }
                },"json")
            },
            //重置数据
            resetData(){
                this.referList  = {
                   topic_name:'',
                    img:'',
                }
            },
            toSwitch(index){
                let that = this
                let state=this.list[index].is_visible;
                if(state==0){
                    this.list[index].is_visible=1
                }else{
                    this.list[index].is_visible=0
                }
                this.loadShow=true;
                var data = {"user_id":this.list[index].id,"status":this.list[index].is_visible,"chance_id":this.chance_id};
                $.post("/back/xinge/switch",data,function (data) {
                    that.message=data.msg;
                    that.$refs.toast.toShow();
                    that.loadShow=false;
                },"json")
            },
        }
    })
</script>
</html>