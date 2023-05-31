<?php
/**
 * +----------------------------------------------------------------------
 * | 路由部分
 * +----------------------------------------------------------------------
 */


//简单授权
Route::any("getWxBase",'api/weixin/getWxForBase');
Route::any("getWxInfo",'api/weixin/getWxBaseInfo');
//复杂授权
Route::any("getWxForDetail",'api/weixin/getWxForDetail');
Route::any("getWxDetail",'api/weixin/getWxDetail');
//绑定opendi
Route::any("bindOpenid",'api/users/bind_openid');
