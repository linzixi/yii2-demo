<?php $baseUrl = $this->theme->baseUrl; ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>机会列表</title>
    <link rel="stylesheet" type="text/css" href="/static/css/style.css"/>
    <script src="/static/js/vue.js" type="text/javascript" charset="utf-8"></script>
    <script src="/static/js/modal.js" type="text/javascript" charset="utf-8"></script>
    <script src="/static/js/page.js" type="text/javascript" charset="utf-8"></script>
    <script src="/static/js/jquery-1.7.2.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="/static/js/loading.js" type="text/javascript" charset="utf-8"></script>
    <script src="/static/tinymce/tinymce.min.js"></script>
    <script src="/static/js/tinymce.js"></script>
    <script src="/static/js/vue-datepicker-local.js" type="text/javascript" charset="utf-8"></script>
    <link rel="stylesheet" type="text/css" href="/static/css/vue-datepicker-local.css"/>
    <link rel="stylesheet" type="text/css" href="<?= $baseUrl; ?>/css/iview.css"/>
</head>
<body>
<div id="app" style="display: none;">
    <div class="container-body">
        <div class="list">
            <div class="top">
                <span class='listTitle'>机会列表</span>
				<span class="btn fr btnHandle btnNormal" style="padding-left:15px;margin-left:15px;" @click='openAdd'>批量配置</span>
                <span class="btn fr btnHandle btnNormal" @click='addModal()'><img src="/static/img/add.png">添加机会</span>
            </div>
            <div class="content">
                <div class="searchOptions">
                    <div class="sItem">
                        <label>标题</label>
                        <input class='searchTip' v-model="title" type="text" placeholder="请输入标题"/>
                    </div>
                    <div class="sItem">
                        <label>类型</label>
                        <select class='searchTip' v-model="type">
                            <option value ="0">全部</option>
                            <option value ="1">传播日历</option>
                            <option value ="2">机会</option>
                        </select>
                    </div>
                    <div class="sItem">
                        <label>专题</label>
                        <select class='searchTip' v-model="topic_id">
                            <option value ="0">全部</option>
                            <option v-for="(item,index) in topic_list" :key="index" :value="item.id">{{item.topic_name}}</option>
                        </select>
                    </div>
                    <div class="sItem">
                        <label>用户</label>
                        <select class='searchTip' v-model="user_id">
                            <option value ="0">全部</option>
                            <option  v-for="(item,index) in user_list" :key="index" :value="item.id">{{item.nickname}}</option>
                        </select>
                    </div>
                    <span class="btn btnSmall btnHandle" @click="searchList"><img src="/static/img/icon_search.png" >查询</span>
                </div>
            </div>
            <table class='tableList'>
                <thead>
                <tr>
					<th @click="all">
						<div class="weui-cell__hd">
							<input type="checkbox" class="weui-check" name="checkbox1" v-model="state">
							<i class="icon weui-icon-checked"></i>
						</div>
					</th>
                    <th>类型</th>
                    <th>专题名称</th>
                    <th>标题</th>
                    <th style="width:100px;">时间</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(item,index) in list" :key="index">
					<td width="2%">
						<div class="weui-cell__hd" @click="toCheck(index)">
							<input type="checkbox" class="weui-check" name="checkbox1" v-model="item.ckeckVal">
							<i class="icon weui-icon-checked"></i>
						</div>
					</td>
                    <td width="8%">{{item.type==1 ? '传播日历' : '机会'}}</td>
                    <td width="15%">{{item.topic_name}}</td>
                    <td width="35%"><a :href="item.url" target="_blank">{{item.title}}</a></td>
                    <td width="12%">{{item.show_date}}</td>
                    <td width="20%" class='handle'>
                      <span @click="toModify(item)">修改 </span> |
                      <span @click="redirect(item.id)"> 配置可见用户</span> |
                      <span @click="deleteModal(item.id)">删除</span>
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
    <ys-modal-component ref="increase" modal-title="添加机会" width="430" draw="1" @on-ok="toRefer">
        <div slot="modal-content">
            <div class="mode-item">
                <div class="addItem">
                    <label>类型：</label>
                    <label class="radio-lable label-width">
                        <input class="tab-radio weui-check" type="radio" name="type" v-model="referList.type" value="1"/>
                        <i class="icon weui-icon-radio"></i>
                        <span>传播日历</span>
                    </label>
                    <label class="radio-lable label-width">
                        <input class="tab-radio weui-check" type="radio" name="type" v-model="referList.type" value="2"/>
                        <i class="icon weui-icon-radio"></i>
                        <span>机会</span>
                    </label>
                </div>
                <div class="addItem" v-show="referList.type==2">
                    <label><span class="rColor">*</span>专题名称</label>
                    <select class='searchTip' style="width:260px;" v-model="referList.topic_id">
                        <option v-for="(item,index) in topic_list" :key="index" :value="item.id">{{item.topic_name}}</option>
                    </select>
                </div>
                <div class="addItem">
                    <label><span class="rColor">*</span>标题：</label>
                    <input type="text" v-model="referList.title" placeholder="请输入标题"/>
                </div>
                <div class="addItem">
                    <label><span class="rColor">*</span>时间：</label>
                    <vue-datepicker-local v-model="referList.show_date"></vue-datepicker-local>
                </div>
                <div class="addItem">
                    <label>url：</label>
                    <input type="text" v-model="referList.url" placeholder="请输入url"/>
                </div>
                <div class="addItem" v-show="referList.type==2">
                    <label>传播建议：</label>
                    <textarea class="textInput" placeholder="请输入传播建议" v-model="referList.suggestion"></textarea>
<!--                    <tinymce-component ref="tinymce" :height="150" @input="getContent"></tinymce-component>-->
                </div>
            </div>
        </div>
    </ys-modal-component>
    <ys-modal-component ref="modify" modal-title="编辑机会" width="430" @on-ok="save">
        <div slot="modal-content">
            <div class="mode-item">
                <div class="addItem">
                    <label>类型：</label>
                    <label class="radio-lable label-width">
                        <input class="tab-radio weui-check" disabled="true" type="radio" name="type" v-model="referList.type" value="1"/>
                        <i class="icon weui-icon-radio"></i>
                        <span>传播日历</span>
                    </label>
                    <label class="radio-lable label-width">
                        <input class="tab-radio weui-check" disabled="true" type="radio" name="type" v-model="referList.type" value="2"/>
                        <i class="icon weui-icon-radio"></i>
                        <span>机会</span>
                    </label>
                </div>
                <div class="addItem" v-show="referList.type==2">
                    <label><span class="rColor">*</span>专题名称</label>
                    <select class="searchTip"  style="width:260px;" v-model="referList.topic_id">
                        <option v-for="(item,index) in topic_list" :key="index" :value="item.id">{{item.topic_name}}</option>
                    </select>
                </div>
                <div class="addItem">
                    <label><span class="rColor">*</span>标题：</label>
                    <input type="text" v-model="referList.title" placeholder="请输入标题"/>
                </div>
                <div class="addItem">
                    <label><span class="rColor">*</span>时间：</label>
                    <vue-datepicker-local v-model="referList.show_date"></vue-datepicker-local>
                </div>
                <div class="addItem">
                    <label>url：</label>
                    <input type="text" v-model="referList.url" placeholder="请输入url"/>
                </div>
                <div class="addItem" v-show="referList.type==2">
                    <label>传播建议：</label>
                    <textarea class="textInput" placeholder="请输入传播建议" v-model="referList.suggestion"></textarea>
<!--                    <tinymce-component ref="tinymce" :height="150" :html="referList.suggestion" @input="getContent"></tinymce-component>-->
                </div>
            </div>
        </div>
    </ys-modal-component>
	<ys-modal-component ref="add" modal-title="批量提交权限" width="600" @on-ok="addValue">
	    <div slot="modal-content">
	        <div class="mode-item">
				<!--<div class="addItem">
				    <label style="width:15%;">用户：</label>
				    <input type="text" v-model="userName" placeholder="请输入用户"/>
				</div>-->
	            <div class="model-left">
					<ul>
						<li class="modelLi" v-for="(item,index) in userList" :key="index">
							<label class="addLeft">
								<input type="checkbox" class="weui-check" name="checkbox2" v-model="item.checkStatus" >
								<i class="icon weui-icon-checked"></i><span>{{item.username}}</span>
							</label>
						</li>
					</ul>
				</div>
				<div class="model-left model-right">
					<ul>
						<li class="modelLi" v-for="(item,index) in userList" :key="index" v-if="item.checkStatus">
							<label class="addLeft">
								<span>{{item.username}}</span>
							</label>
						</li>
					</ul>
				</div>
	        </div>
	    </div>
	</ys-modal-component>
    <ys-modal-component ref="keep" modal-title="删除机会" width="300" @on-ok="delChance">
        <div slot="modal-content">
            <div>
                确定要删除吗？关联的用户关系会一并删除！
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
                userList:[],//弹窗显示的用户数据
                userOriginalList:[],//弹窗原始的用户数据
                state:false,
                page:1,
                limit:10,
                totalnums:0,
                time: '',
                type:0,
                topic_id:0,
                title:'',
                loadShow:false,
                modifyShow:false,
                topic_list:'',
                message:'',
                id:'',
                loading:'loading',
                showButton:true,
                referList:{
                    title:'',
                    type:1,
                    topic_id:'1',
                    show_date:'',
                    url:'',
                    suggestion:''
                },
				userName:'',//用户
				checkList:[],
                delete_id:'',
                user_id:0,
                user_list:'',
            }
        },
        created() {
            this.getList()
			this.getUserList()
        },
		mounted() {
			
		},
        methods: {
            getUserList(){
                var that = this;
                $.post("/back/xinge/user-list",{},function (data) {
                    if (data.code == 10000) {
                        that.userList = data.data;
                    }
                },"json")
            },
            searchList(){
                this.page = 1;
                this.getList()
            },
            openModal () {
                this.$refs.modal.openModal();
            },
            addModal () {
                this.arrow=false;
                this.resetData();
                this.referList.topic_id =  this.topic_list[0].id;
                this.referList.topic_name=this.topic_list[0].topic_name;
                this.referList.type =  1;
                this.$refs.increase.openModal();
            },
            toModify (item) {
                this.arrow=false;
                this.id  = item.id;
                this.referList.type  = item.type;
                this.referList.topic_id  = item.topic_id ? item.topic_id :this.topic_list[0].id;
                this.referList.topic_name = item.topic_name ? item.topic_name : this.topic_list[0].topic_name;
                this.referList.title  = item.title;
                this.referList.show_date  = this.toDate(item.show_date);
                this.referList.url  = item.url;
                this.referList.suggestion  = item.suggestion;
                this.$refs.modify.openModal();
            },
            redirect(id){
                var url = "/back/xinge/set-visible?chance_id=" + id;
                // window.open(url);
                window.location.href = url;
            },
            save () {
                var that = this;
                if(this.referList.show_date == ''){
                    that.message='日期不能为空!';
                    that.$refs.toast.toShow();
                    return false;
                }
                var param = {
                    "id":this.id,
                    "type":this.referList.type,
                    "topic_id":this.referList.topic_id ? this.referList.topic_id :this.topic_list[0].topic_id,
                    "title":this.referList.title,
                    "show_date":this.formatTime(this.referList.show_date),
                    "url":this.referList.url,
                    "suggestion":this.referList.suggestion,
                };
                if(param.type==1){
                    param.topic_id = '';
                    param.suggestion = '';
                }else if(param.type==2 && param.topic_id==''){
                    that.message='请先添加专题!';
                    that.$refs.toast.toShow();
                    return false;
                }
                if(param.title==='' || param.show_date==''){
                    that.message='参数不能为空!';
                    that.$refs.toast.toShow();
                    return false;
                }
                this.loadShow=true;
                $.post("/back/xinge/add-chance",param,function (data) {
                    if (data.code == 10000) {
                        that.loadShow=false;
                        that.message='修改成功';
                        that.$refs.toast.toShow();
                        that.$refs.modify.cancelHandle();
                        that.getList();
                    }else{
                        that.loadShow=false;
                        that.message='失败！'+data.msg;
                        that.$refs.toast.toShow();
                    }
                },"json")
                this.$refs.modal.isHide = false;
            },
            toRefer () {
                var that = this;
                if(this.referList.show_date == ''){
                    that.message='日期不能为空!';
                    that.$refs.toast.toShow();
                    return false;
                }
                var data = {
                    "type":this.referList.type,
                    "topic_id":this.referList.topic_id ? this.referList.topic_id :'',
                    "title":this.referList.title,
                    "show_date":this.formatTime(this.referList.show_date),
                    "url":this.referList.url,
                    "suggestion":this.referList.suggestion,
                };
                if(data.type==1){
                    data.topic_id = '';
                    data.suggestion = '';
                }else if(data.type==2 && data.topic_id==''){
                    that.message='请先添加专题!';
                    that.$refs.toast.toShow();
                    return false;
                }
                if(data.title==='' || data.show_date==''){
                    that.message='参数不能为空!';
                    that.$refs.toast.toShow();
                    return false;
                }

                this.loadShow=true;
                $.post("/back/xinge/add-chance",data,function (data) {
                    if (data.code == 10000) {
                        that.loadShow=false;
                        that.message=data.msg;
                        that.$refs.toast.toShow();
                        that.$refs.increase.cancelHandle();
                        that.getList();
                    }else{
                        that.loadShow=false;
                        that.message='失败！'+data.msg;
                        that.$refs.toast.toShow();
                    }
                },"json")
            },
            getList(){
                var that = this;
                this.loadShow=true;
                var data = {
                    "page":that.page,
                    "limit":that.limit,
                    "title":that.title,
                    "type":that.type,
                    "topic_id":that.topic_id,
                    "user_id":that.user_id,
                };
                $.post("/back/xinge/chance",data,function (data) {
                    if (data.code == 10000) {
                        that.list = data.data.data;
						for(var j in that.list){
						    that.list[j].ckeckVal='';
						}
                        that.topic_list = data.data.topic_list;
                        that.user_list = data.data.user_list;
                        that.totalnums = data.data.count;
                        that.loadShow=false;
						that.checkList=data.data.data;
                    }
                },"json")
            },
            //重置数据
            resetData(){
                this.referList = {
                    title:'',
                    type:'',
                    topic_id:'',
                    show_date:'',
                    url:'',
                    suggestion:''
                }
            },
            addCover(e){
                let that=this;
                let file=e.target.files[0];
                that.referList.img = file;
                var reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = function(){
                    that.referList.img_show=reader.result
                }
            },
            delCover(){
                this.$refs.cover.value=''
                this.referList.img_show=''
                this.referList.img=''
            },
            formatNumber (n) {
                n = n.toString()
                return n[1] ? n : '0' + n
            },
            formatTime (date){
                if(this.check(date)===true){
                    return date;
                }
                const year = date.getFullYear()
                const month = date.getMonth() + 1
                const day = date.getDate()
                const hour = date.getHours()
                const minute = date.getMinutes()
                const second = date.getSeconds()

                return [year, month, day].map(this.formatNumber).join('-') + ' ' + [hour, minute, second].map(this.formatNumber).join(':')
            },
            check(date){
                var a = /^(\d{4})-(\d{2})-(\d{2})$/;
                if (!a.test(date)) {
                    return false;
                } else {
                    return true;
                }
            },
            toDate(time){
                time = time.substring(0,19);
                time = time.replace(/-/g,'/');
                var timestamp = new Date(time).getTime();
                var date = new Date(timestamp);
                return date
            },
            deleteModal(id) {
                this.delete_id = id;
                this.$refs.keep.openModal();
            },
            delChance(){
                var that = this;
                var param = {
                    "chance_id":this.delete_id
                };
                $.post("/back/xinge/delete",param,function (data) {
                    if (data.code == 10000) {
                        that.loadShow=false;
                        that.message=data.msg;
                        that.$refs.toast.toShow();
                        that.$refs.keep.cancelHandle();
                        //判断是否为最后一页最后一条
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
            },
			all(){
				if(this.state==''){
					this.state='checked'
					for(var i in this.list){
						this.list[i].ckeckVal='checked'
					}
				}else{
					this.state=''
					for(var i in this.list){
						this.list[i].ckeckVal=''
					}
				}
			},
			toCheck(i){
				let that = this
				var count=0
				if(that.list[i].ckeckVal==''){
					that.list[i].ckeckVal='checked'
				}else{
					that.list[i].ckeckVal=''
				}
				that.$set(this.list, i, that.list[i]);
				for(var j in that.list){
					if(that.list[j].ckeckVal=='checked'){
						count++
						if(count==that.list.length){
							that.state='checked'
						}else{
							that.state=''
						}
					}
				}
			},
			// 打开人员弹框
			openAdd(){
                let chanceIds = [];
                for(var ji in this.list){
                    if(this.list[ji].ckeckVal){
                        chanceIds.push(this.list[ji].id)
                    }
                }
                if(chanceIds.length < 1){
                    this.message="没有选择日历/机会";
                    this.$refs.toast.toShow();
                    return false;
                }
				this.$refs.add.openModal();
			},
			//批量提交数据
			addValue(){
				let that=this;
				let userIds=[];
				let chanceIds=[];
				for(var j in that.userList){
					if(that.userList[j].checkStatus){
                        userIds.push(that.userList[j].id)
					}
				}
                for(var ji in that.list){
                    if(that.list[ji].ckeckVal){
                        chanceIds.push(that.list[ji].id)
                    }
                }
                if(userIds.length < 1){
                    that.message='没有选择用户';
                    that.$refs.toast.toShow();
                    return false;
                }
                if(chanceIds.length < 1){
                    that.message='没有选择日历/机会';
                    that.$refs.toast.toShow();
                    return false;
                }
                $.post("/back/xinge/add-auth",{"userIds":userIds,"chanceIds":chanceIds},function (data) {
                    if (data.code == 10000 ){
                        that.message='批量设置成功';
                        that.$refs.toast.toShow();
                        that.$refs.add.cancelHandle();
                    }else {
                        that.message=data.msg;
                        that.$refs.toast.toShow();
                    }
                },"json")


			},
            getContent(text){
                this.referList.suggestion = text;
            }
        }
    })
</script>
</html>