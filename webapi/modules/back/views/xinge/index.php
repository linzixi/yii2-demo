<?php $baseUrl = $this->theme->baseUrl; ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>专题列表</title>
    <link rel="stylesheet" type="text/css" href="/static/css/style.css"/>
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
                <span class='listTitle'>专题列表</span>
                <span class="btn fr btnHandle btnNormal" @click='addModal()'><img src="/static/img/add.png">添加专题</span>
            </div>
            <div class="content">
                <div class="searchOptions">
                    <div class="sItem">
                        <label>专题名称</label>
                        <input class='searchTip' v-model="topic_name" type="text" placeholder="请输入专题名称"/>
                    </div>
                    <span class="btn btnSmall btnHandle" @click="searchList"><img src="/static/img/icon_search.png" >查询</span>
                </div>
            </div>
            <table class='tableList'>
                <thead>
                <tr>
                    <th>序号</th>
                    <th>专题名称</th>
                    <th>封面图</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(item,index) in list" :key="index">
                    <td>{{index + 1}}</td>
                    <td>{{item.topic_name}}</td>
                    <td>
<!--                        <input ref='code' type="file" class="upload space" @change="addCode($event)"/>-->
                        <p v-if="item.img"><img style="width:100px;margin-left:0px;" class="preview" :src="item.img"></p>
                    </td>
                  <td class='handle'>
                      <span @click="toModify(item)">修改专题</span> |
                      <span @click="deleteModal(item.id)">删除专题</span>
                  </td>
                </tr>
                </tbody>
            </table>
            <page-component v-if="totalnums != ''" :currentpage.sync="page" :limit.sync="limit" :totalnums="totalnums" @turn="getList"></page-component>
        </div>
    </div>
    <!-- 右边部分结束 -->
    <loading-component :active='loadShow'></loading-component>
    <toast ref="toast" :message='message'></toast>
    <ys-modal-component ref="modal" modal-title="温馨提示" width="300" @on-ok="save">
        <div slot="modal-content">
            <div>
                确定要退出吗？
            </div>
        </div>
    </ys-modal-component>
    <ys-modal-component ref="increase" modal-title="添加专题" width="430" draw="1" @on-ok="toRefer">
        <div slot="modal-content">
            <div>
                <div class="addItem">
                    <label>专题：</label>
                    <input type="text" v-model="referList.topic_name" placeholder="请输入昵称"/>
                </div>
                <div class="addItem">
                    <label>封面：</label>
<!--                    <span class="item-title">封面</span>-->
                    <input ref='cover' type="file" class="upload space" style="border:none;" @change="addCover($event)"/>
                    <p v-if="referList.img_show"><img class="preview" :src="referList.img_show" style="height:100px;"><a class="close icon" @click="delCover()"></a></p>
                </div>
            </div>
        </div>
    </ys-modal-component>
    <ys-modal-component ref="modify" modal-title="编辑专题" width="430" @on-ok="save">
        <div slot="modal-content">
            <div>
                <div class="addItem">
                    <label>专题：</label>
                    <input type="text" v-model="referList.topic_name" placeholder="请输入昵称"/>
                </div>
                <div class="addItem">
                    <label>封面：</label>
                    <input ref='cover' type="file" class="upload space" style="border:none;" @change="addCover($event)"/>
                    <p class="img" v-if="referList.img_show"><img class="preview" :src="referList.img_show" style="height: 100px;"><a class="close icon" @click="delCover()"></a></p>
                </div>
            </div>
        </div>
    </ys-modal-component>
    <ys-modal-component ref="keep" modal-title="删除专题" width="300" @on-ok="delTopic">
        <div slot="modal-content">
            <div>
                确定要删除吗？专题下的机会以及关联关系会一并删除！
            </div>
        </div>
    </ys-modal-component>
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
                time: '',
                status:0,
                topic_name:'',
                loadShow:false,
                modifyShow:false,
                message:'',
                id:'',
               /* img:'',
                img_show:''*/
                loading:'loading',
                showButton:true,
                referList:{
                    topic_name:'',
                    img:'',
                    img_show:''
                },
                delete_id:'',
            }
        },
        created() {
            this.getList()
        },
        methods: {
            searchList(){
                this.page = 1;
                this.getList()
            },
            openModal () {
                this.$refs.modal.openModal();
            },
            addModal () {
                this.resetData();
                this.$refs.increase.openModal();
            },
            toModify (item) {
                this.referList.topic_name = item.topic_name;
                this.referList.img = item.img;
                this.referList.img_show = item.img;
                this.id = item.id;
                this.$refs.modify.openModal();
            },
            save () {
                var that = this;
                var param = {
                    "topic_name":this.referList.topic_name,
                    "id":this.id,
                };
                if(param.topic_name==='' || param.id===''){
                    that.message='参数不能为空!';
                    that.$refs.toast.toShow();
                    return false;
                }
                if(this.referList.img===''){
                    that.message='请上传封面图!';
                    that.$refs.toast.toShow();
                    return false;
                }
                let formData = new FormData();
                formData.append('data[topic_name]', param.topic_name);
                formData.append('data[id]', param.id);
                formData.append('img',this.referList.img);
                this.loadShow=true;
                $.ajax({
                    type: "POST",
                    url: "/back/xinge/add-topic",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(msg){
                        if (msg.code == 10000) {
                            that.loadShow=false;
                            that.message=msg.msg;
                            that.$refs.toast.toShow();
                            that.$refs.modify.cancelHandle();
                            that.getList();
                        }else{
                            that.loadShow=false;
                            that.message='失败！'+msg.msg;
                            that.$refs.toast.toShow();
                        }
                    }
                });
                this.$refs.modal.isHide = false;
            },
            toRefer () {
                var that = this;
                var data = {
                    "topic_name":this.referList.topic_name,
                    "img":this.referList.img,
                };
                if(data.topic_name===''){
                    that.message='专题不能为空!';
                    that.$refs.toast.toShow();
                    return false;
                }
                if(data.img===''){
                    that.message='请上传封面图!';
                    that.$refs.toast.toShow();
                    return false;
                }
                this.loadShow=true;
                let formData = new FormData();
                formData.append('data[topic_name]', data.topic_name);
                formData.append('img',this.referList.img);

                this.loadShow=true;
                $.ajax({
                    type: "POST",
                    url: "/back/xinge/add-topic",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(msg){
                        if (msg.code == 10000) {
                            that.loadShow=false;
                            that.message=msg.msg;
                            that.$refs.toast.toShow();
                            that.$refs.increase.cancelHandle();
                            that.getList();
                        }else{
                            that.loadShow=false;
                            that.message='失败！'+msg.msg;
                            that.$refs.toast.toShow();
                        }
                    }
                });
            },
            getList(){
                var that = this;
                this.loadShow=true;
                var data = {
                    "page":that.page,
                    "limit":that.limit,
                    "topic_name":that.topic_name,
                };
                $.post("/back/xinge/index",data,function (data) {
                    if (data.code == 10000) {
                        that.list = data.data.data;
                        that.totalnums = data.data.count;
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
            addCover(e){
                let that=this;
                let file=e.target.files[0];
                that.referList.img = file;
                var reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = function(){
                    that.referList.img_show=reader.result;
                }
                that.$set(that.referList, 'img_show', reader.result)
            },
            delCover(){
                this.$refs.cover.value=''
                this.referList.img_show=''
                this.referList.img=''
            },
            deleteModal(id) {
                this.delete_id = id;
                this.$refs.keep.openModal();
            },
            delTopic(){
                var that = this;
                var param = {
                    "topic_id":this.delete_id
                }
                $.post("/back/xinge/delete",param,function (data) {
                    if (data.code == 10000) {
                        that.loadShow=false;
                        that.message=data.msg;
                        that.$refs.toast.toShow();
                        that.$refs.keep.cancelHandle();
                        if(that.page != 1 && that.list.length == 1){
                            that.page --;
                        }
                        that.getList();
                    }else{
                        that.loadShow=false;
                        that.message=data.msg;
                        that.$refs.toast.toShow();
                    }
                },"json")
            }
        }
    })
</script>
</html>