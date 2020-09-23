<?php

$baseUrl = $this->theme->baseUrl;
//echo $baseUrl;die;

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title>登录</title>
		<link rel="stylesheet" type="text/css" href="<?= $baseUrl; ?>/css/style.css"/>
		<script src="<?= $baseUrl; ?>/js/vue.js" type="text/javascript" charset="utf-8"></script>
		<script src="<?= $baseUrl; ?>/js/modal.js" type="text/javascript" charset="utf-8"></script>
		<script src="<?= $baseUrl; ?>/js/page.js" type="text/javascript" charset="utf-8"></script>
		<script src="<?= $baseUrl; ?>/js/jquery-1.7.2.min.js" type="text/javascript" charset="utf-8"></script>
	</head>
	<body>
		<div class="container bg" id="app">
			<!-- 左边导航 -->
			<div class="container-bleft bgl">
				<p class="wel wels">欢迎光临</p>
<!--				<p class="wel welb">舆情OEM</p>-->
				<p class="wel welb">后台管理系统</p>
			</div>
			<!-- 左边导航结束 -->
			
			<!-- 右边部分 -->
			<div class="container-right">
				<div class="inside">
					<!-- 内容 -->

                    <div class="owner-login-body">
                        <p class="ownerTitle">用户登录</p>
                        <div class="loginItem">
                            <img src="<?= $baseUrl; ?>/img/user.png" >
                            <input type="text" placeholder="请输入用户名" v-model="loginForm.username"/>
                        </div>
                        <div class="loginItem">
                            <img src="<?= $baseUrl; ?>/img/lock.png" >
                            <input type="password" placeholder="请输入密码" v-model="loginForm.password"/>
                        </div>
                        <div class="loginbtn" @click="login">登录</div>
                    </div>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			window.App = new Vue({
				el: '#app',
				created() {
					
				},
				mounted() {
					
				},
				data() {
					return {
						loginForm: {
						  username: '',
						  password: ''
						}
					}
				},
				methods: {
					login () {
						console.log(this.loginForm);
						var username = this.loginForm.username;
						var password =  this.loginForm.password;
                        if(username == ""){
                            alert("请输入用户名");
                            return false;
                        }else if(password == ""){
                            alert("请输入密码");
                            return false;
                        }
                        //ajax去服务器端校验
                        var data= {username:username,password:password};

                        $.ajax({
                            type:"POST",
                            url:"login",
                            data:data,
                            dataType:'json',
                            success:function(data){
                                //alert(msg);
                                if(data.code==10000){
                                    window.location.href = "/company/site/index";
                                }else{
                                    alert("登录失败，请重试!");
                                }
                            }
                        });
					}
				}
			});
		</script>
	</body>
</html>
