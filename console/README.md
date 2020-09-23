*** 
  命令                            |  介绍                  |  执行频率 
  ------------------------------ | ---------------------- | ---------------------
yii rank/latest                  |  更新各平台榜单最新日期    |    1小时执行一次
yii rank/weixin-rank-add 1       |  新增微信日榜      |    * */2 * * *
yii rank/weixin-rank-add 2       |  新增微信周榜      |    * */2 * * 0
yii rank/weixin-rank-add 3       |  新增微信月榜      |    * */2 1 * *
yii rank/weibo-rank-add 1        |  新增微博日榜      |    2小时执行一次
yii rank/weibo-rank-add 2        |  新增微博周榜      |    周日每2小时执行一次
yii rank/weibo-rank-add 3        |  新增微博月榜      |    每月1号2小时执行一次
yii rank/toutiao-rank-add 1      |  新增头条日榜      |    2小时执行一次
yii rank/toutiao-rank-add 2      |  新增头条周榜      |    周日每2小时执行一次
yii rank/toutiao-rank-add 3      |  新增头条月榜      |    每月1号2小时执行一次
yii rank/douyin-rank-add 1       |  新增抖音日榜      |    2小时执行一次
yii rank/douyin-rank-add 2       |  新增抖音周榜      |    周日每2小时执行一次
yii rank/douyin-rank-add 3       |  新增抖音月榜      |    每月1号2小时执行一次
yii rank/kuaishou-rank-add 1     |  新增快手日榜      |    2小时执行一次
yii rank/kuaishou-rank-add 2     |  新增快手周榜      |    周日每2小时执行一次
yii rank/kuaishou-rank-add 3     |  新增快手月榜      |    每月1号2小时执行一次
yii rank/weixin-rank-update 1    |  更新微信日榜      |    每天[02  23  *  *  *]
yii rank/weixin-rank-update 2    |  更新微信周榜      |    周日[02  01  *  *  1]
yii rank/weixin-rank-update 3    |  更新微信月榜      |    每月[02  01  3  *  *]
yii rank/weibo-rank-update 1     |  更新微博日榜      |    每天[同微信日榜]
yii rank/weibo-rank-update 2     |  更新微博周榜      |    周日[同微信周榜]
yii rank/weibo-rank-update 3     |  更新微博月榜      |    每月[同微信月榜]
yii rank/toutiao-rank-update 1   |  更新头条日榜      |    每天
yii rank/toutiao-rank-update 2   |  更新头条周榜      |    周日
yii rank/toutiao-rank-update 3   |  更新头条月榜      |    每月
yii rank/douyin-rank-update 1    |  更新抖音日榜      |    每天
yii rank/douyin-rank-update 2    |  更新抖音周榜      |    周日
yii rank/douyin-rank-update 3    |  更新抖音月榜      |    每月
yii rank/kuaishou-rank-update 1  |  更新快手日榜      |    每天
yii rank/kuaishou-rank-update 2  |  更新快手周榜      |    周日
yii rank/kuaishou-rank-update 3  |  更新快手月榜      |    每月
yii warn/send 1                  |  预警推送任务      |    1分钟执行一次
yii warn/send 2                  |  预警推送任务      |    1分钟执行一次
yii account/add                  |  更新用户添加的账户 |    每2小时执行一次
*** 