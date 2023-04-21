<?php
/**
 * +----------------------------------------------------------------------
 * | 应用公共文件
 * +----------------------------------------------------------------------
 */

// 定义插件目录
define('ADDON_PATH', Env::get('root_path') . 'addons' . DIRECTORY_SEPARATOR);

// 闭包自动处理插件钩子业务
Hook::add('app_init', function () {
    // 获取开关
    $autoload = (bool)Config::get('addons.autoload', false);
    // 配置自动加载时直接返回
    if ($autoload) return;
    // 非正时表示后台接管插件业务
    // 当debug时不缓存配置
    $config = config('app_debug') ? [] : (array)cache('addons');
    if (empty($config)) {
        //读取插件通过文件夹的形式来读取
        $hooks = get_addon_list();
        foreach ($hooks as $hook) {
            //是否开启该插件,只有开启的插件才加载
            if($hook['status']==1)
                $config['hooks'][$hook['name']] = explode(',', $hook['addons']);
        }
        cache('addons', $config);
    }
    config('addons', $config);
});

/**
 * 过滤数组元素前后空格 (支持多维数组)
 * @param $array 要过滤的数组
 * @return array|string
 */
function trim_array_element($array){
    if(!is_array($array))
        return trim($array);
    return array_map('trim_array_element',$array);
}

/**
 * 将数据库中查出的列表以指定的 值作为数组的键名，并以另一个值作为键值
 * @param $arr
 * @param $key_name
 * @return array
 */
function convert_arr_kv($arr,$key_name,$value){
    $arr2 = array();
    foreach($arr as $key => $val){
        $arr2[$val[$key_name]] = $val[$value];
    }
    return $arr2;
}

/**
 * 验证输入的邮件地址是否合法
 */
function is_email($user_email)
{
    $chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
    if (strpos($user_email, '@') !== false && strpos($user_email, '.') !== false) {
        if (preg_match($chars, $user_email)) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/**
 * 验证输入的手机号码是否合法
 */
function is_mobile_phone($mobile_phone)
{
    $chars = "/^13[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$|17[0-9]{1}[0-9]{8}$/";
    if (preg_match($chars, $mobile_phone)) {
        return true;
    }
    return false;
}

/**
 * 邮件发送
 * @param $to    接收人
 * @param string $subject   邮件标题
 * @param string $content   邮件内容(html模板渲染后的内容)
 * @throws Exception
 * @throws phpmailerException
 */
function send_email($to,$subject='',$content=''){
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $arr = Db::name('config')->where('inc_type','smtp')->select();
    $config = convert_arr_kv($arr,'name','value');

    $mail->CharSet  = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->isSMTP();
    $mail->SMTPDebug = 0;
    //调试输出格式
    //$mail->Debugoutput = 'html';
    //smtp服务器
    $mail->Host = $config['smtp_server'];
    //端口 - likely to be 25, 465 or 587
    $mail->Port = $config['smtp_port'];

    if($mail->Port == '465') {
        $mail->SMTPSecure = 'ssl';
    }// 使用安全协议
    //Whether to use SMTP authentication
    $mail->SMTPAuth = true;
    //发送邮箱
    $mail->Username = $config['smtp_user'];
    //密码
    $mail->Password = $config['smtp_pwd'];
    //Set who the message is to be sent from
    $mail->setFrom($config['smtp_user'],$config['email_id']);
    //回复地址
    //$mail->addReplyTo('replyto@example.com', 'First Last');
    //接收邮件方
    if(is_array($to)){
        foreach ($to as $v){
            $mail->addAddress($v);
        }
    }else{
        $mail->addAddress($to);
    }

    $mail->isHTML(true);// send as HTML
    //标题
    $mail->Subject = $subject;
    //HTML内容转换
    $mail->msgHTML($content);
    return $mail->send();
}

function string2array($info) {
    if($info == '') return array();
    eval("\$r = $info;");
    return $r;
}
function array2string($info) {
    if($info == '') return '';
    if(!is_array($info)){
        $string = stripslashes($info);
    }
    foreach($info as $key => $val){
        $string[$key] = stripslashes($val);
    }
    $setup = var_export($string, TRUE);
    return $setup;
}
//文本域中换行标签输出
function textareaBr($info) {
    $info = str_replace("\r\n","<br />",$info);
    return $info;
}

// 无限分类-栏目
function tree_cate($cate , $lefthtml = '|— ' , $pid=0 , $lvl=0 ){
    $arr=array();
    foreach ($cate as $v){
        if($v['parentid']==$pid){
            $v['lvl']=$lvl + 1;
            $v['lefthtml']=str_repeat($lefthtml,$lvl);
            $v['lcatname']=$v['lefthtml'].$v['catname'];
            $arr[]=$v;
            $arr= array_merge($arr,tree_cate($cate,$lefthtml,$v['id'], $lvl+1 ));
        }
    }
    return $arr;
}

//组合多维数组
function unlimitedForLayer ($cate, $name = 'sub', $pid = 0) {
    $arr = array();
    foreach ($cate as $v) {
        if ($v['parentid'] == $pid) {
            $v[$name] = unlimitedForLayer($cate, $name, $v['id']);
            $v['url'] = getUrl($v);
            $arr[] = $v;
        }

    }
    return $arr;
}

//传递一个父级分类ID返回当前子分类
function getChildsOn ($cate, $pid) {
    $arr = array();
    foreach ($cate as $v) {
        if ($v['parentid'] == $pid) {
            $v['sub'] = getChilds($cate, $v['id']);
            $v['url'] = getUrl($v);
            $arr[] = $v;
        }
    }
    return $arr;
}

//传递一个父级分类ID返回所有子分类
function getChilds ($cate, $pid) {
    $arr = array();
    foreach ($cate as $v) {
        if ($v['parentid'] == $pid) {
            $v['url'] = getUrl($v);
            $arr[] = $v;
            $arr = array_merge($arr, getChilds($cate, $v['id']));
        }
    }
    return $arr;
}

//传递一个父级分类ID返回所有子分类ID
function getChildsId ($cate, $pid) {
    $arr = [];
    foreach ($cate as $v) {
        if ($v['parentid'] == $pid) {
            $arr[] = $v;
            $arr = array_merge($arr, getChildsId($cate, $v['id']));
        }
    }
    return $arr;
}
//格式化分类数组为字符串
function getChildsIdStr($ids,$pid=''){
    $result='';
    foreach ($ids as $k=>$v){
        $result.=$v['id'].',';
    }
    if($pid){
        $result = $pid.','.$result;
    }
    $result = rtrim($result,',');
    return $result;
}

//传递一个子分类ID返回所有的父级分类
function getParents ($cate, $id) {
    $arr = array();
    foreach ($cate as $v) {
        if ($v['id'] == $id) {
            $arr[] = $v;
            $arr = array_merge(getParents($cate, $v['parentid']), $arr);
        }
    }
    return $arr;
}

//获取所有模版
function getTemplate(){
    //查找设置的模版
    $system = Db::name('system')->where('id',1)->find();
    $path = './template/home/'.$system['template'].'/'.$system['html'].'/';
    $tpl['list'] = get_file_folder_List($path , 2, '*_list*');
    $tpl['show'] = get_file_folder_List($path , 2, '*_show*');
    return $tpl;
}

/**
 * 获取文件目录列表
 * @param string $pathname 路径
 * @param integer $fileFlag 文件列表 0所有文件列表,1只读文件夹,2是只读文件(不包含文件夹)
 * @param string $pathname 路径
 * @return array
 */
function get_file_folder_List($pathname,$fileFlag = 0, $pattern='*') {
    $fileArray = array();
    $pathname = rtrim($pathname,'/') . '/';
    $list   =   glob($pathname.$pattern);
    foreach ($list  as $i => $file) {
        switch ($fileFlag) {
            case 0:
                $fileArray[]=basename($file);
                break;
            case 1:
                if (is_dir($file)) {
                    $fileArray[]=basename($file);
                }
                break;

            case 2:
                if (is_file($file)) {
                    $fileArray[]=basename($file);
                }
                break;

            default:
                break;
        }
    }

    return $fileArray;
}


/**
 * 判断当前访问的用户是  PC端  还是 手机端  返回true 为手机端  false 为PC 端
 *  是否移动端访问访问
 * @return boolean
 */
function isMobile()
{
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
        return true;

    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset ($_SERVER['HTTP_VIA']))
    {
        // 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    // 脑残法，判断手机发送的客户端标志,兼容性有待提高
    if (isset ($_SERVER['HTTP_USER_AGENT']))
    {
        $clientkeywords = array ('nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile');
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
            return true;
    }
    // 协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT']))
    {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
        {
            return true;
        }
    }
    return false;
}

/**
 * 获得本地插件列表
 * @return array
 */
function get_addon_list()
{
    $results = scandir(ADDON_PATH);
    $list = [];
    foreach ($results as $name) {
        if ($name === '.' or $name === '..')
            continue;
        if (is_file(ADDON_PATH . $name))
            continue;
        $addonDir = ADDON_PATH . $name . DIRECTORY_SEPARATOR;
        if (!is_dir($addonDir))
            continue;

        if (!is_file($addonDir . ucfirst($name) . '.php'))
            continue;

        //这里不采用get_addon_info是因为会有缓存
        //$info = get_addon_info($name);
        $info_file = $addonDir . 'info.ini';
        if (!is_file($info_file))
            continue;

        $info = Config::parse($info_file, '', "addon-info-{$name}");
        //$info['url'] = addon_url($name);
        $list[$name] = $info;
    }
    return $list;
}

/**
 * 判断文件或文件夹是否可写
 * @param    string $file 文件或目录
 * @return    bool
 */
function is_really_writable($file)
{
    if (DIRECTORY_SEPARATOR === '/') {
        return is_writable($file);
    }
    if (is_dir($file)) {
        $file = rtrim($file, '/') . '/' . md5(mt_rand());
        if (($fp = @fopen($file, 'ab')) === FALSE) {
            return FALSE;
        }
        fclose($fp);
        @chmod($file, 0777);
        @unlink($file);
        return TRUE;
    } elseif (!is_file($file) OR ($fp = @fopen($file, 'ab')) === FALSE) {
        return FALSE;
    }
    fclose($fp);
    return TRUE;
}

/**
 * 插件更新配置文件
 *
 * @param string $name 插件名
 * @param array $array
 * @return boolean
 * @throws Exception
 */
function set_addon_fullconfig($name, $array)
{
    $file = ADDON_PATH . $name . DIRECTORY_SEPARATOR . 'config.php';
    if (!is_really_writable($file)) {
        throw new Exception("文件没有写入权限");
    }
    if ($handle = fopen($file, 'w')) {
        fwrite($handle, "<?php\n\n" . "return " . var_export($array, TRUE) . ";\n");
        fclose($handle);
    } else {
        throw new Exception("文件没有写入权限");
    }
    return true;
}
/**
 * 插件更新ini文件
 *
 * @param string $name 插件名
 * @param array $array
 * @return boolean
 * @throws Exception
 */
function set_addon_fullini($name, $array)
{
    $file = ADDON_PATH . $name . DIRECTORY_SEPARATOR . 'info.ini';
    if (!is_really_writable($file)) {
        throw new Exception("文件没有写入权限");
    }
    $str = '';
    foreach($array as $k=>$v){
        $str .= $k." = ".$v."\n";
    }

    if ($handle = fopen($file, 'w')) {
        fwrite($handle, $str);
        fclose($handle);
    } else {
        throw new Exception("文件没有写入权限");
    }
    return true;
}

function xfyun(){
    $daytime=strtotime('1970-1-1T00:00:00 UTC');
	// OCR手写文字识别服务webapi接口地址
    $api = "http://webapi.xfyun.cn/v1/service/v1/ocr/handwriting";
	// 应用APPID(必须为webapi类型应用,并开通手写文字识别服务,参考帖子如何创建一个webapi应用：http://bbs.xfyun.cn/forum.php?mod=viewthread&tid=36481)
    $XAppid = "89463718";
	// 接口密钥(webapi类型应用开通手写文字识别后，控制台--我的应用---手写文字识别---相应服务的apikey)
    $Apikey = "455082566c6c70c03a6427d0966624eb";
    $XCurTime =time();
    $XParam ="";
    $XCheckSum ="";
    // 语种设置和是否返回文本位置信息
    $Param= array(
		"language"=>"cn|en",
		"location"=>"false",
    );
	// 文件上传地址
    $image=file_get_contents('../public/uploads/20220409/4aa0c9d10f2d07f28d671a8b7eac6fb6.jpeg');
    $image=base64_encode($image);		    
    $Post = array(
	  'image' => $image,
	);
    $XParam = base64_encode(json_encode($Param));
    $XCheckSum = md5($Apikey.$XCurTime.$XParam);
    $headers = array();
    $headers[] = 'X-CurTime:'.$XCurTime;
    $headers[] = 'X-Param:'.$XParam;
    $headers[] = 'X-Appid:'.$XAppid;
    $headers[] = 'X-CheckSum:'.$XCheckSum;
    $headers[] = 'Content-Type:application/x-www-form-urlencoded; charset=utf-8';
    return http_request($api, $Post, $headers);
}
/**
 * 发送post请求
 * @param string $url 请求地址
 * @param array $post_data post键值对数据
 * @return string
 */
function http_request($url, $post_data, $headers) {		 
  $postdata = http_build_query($post_data);
  $options = array(
    'http' => array(
      'method' => 'POST',
      'header' => $headers,
      'content' => $postdata,
      'timeout' => 15 * 60 // 超时时间（单位:s）
    )
  );
  $context = stream_context_create($options);
  $result = file_get_contents($url, false, $context);
// 错误码链接：https://www.xfyun.cn/document/error-code (code返回错误码时必看)	
  return $result; 			
  //return "success";
}



function curlGet($url){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_URL, $url); 

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
}

function saiyouSms($phone,$code){
    
    /*****************
     * 加密请求 示例代码
     ******************/
    //appid参数 appkey参数在     短信-创建/管理AppID中获取
    //手机号支持单个
    //模板ID   短信-创建/管理短信模板中获得
    //短信模板对应变量
    //  若模板为：【SUBMAIL】您的验证码是@var(code)，请在@var(time)内输入。短信模板对应变量如下
    //  变量名和自定义内容相对应即可
    $appid = '77300';                                                               //appid参数
    $appkey = '12afc9d405aad2c5c3ebff37adab66e7';                                   //appkey参数
    $to = $phone;                                                            //收信人 手机号码
    $project_id = '5ZBQu3';                                                           //模板ID
    $vars = json_encode(array(                                                      //模板对应变量
        'code' => $code
    ));

    //通过接口获取时间戳
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL            => 'https://api.mysubmail.com/service/timestamp.json',
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_POST           => 0
    ));
    $output = curl_exec($ch);
    curl_close($ch);
    $output = json_decode($output, true);
    $timestamp = $output['timestamp'];

    $post_data = array(
        "appid"        => $appid,
        "to"           => $to,
        "project"      => $project_id,
        "timestamp"    => $timestamp,
        "sign_type"    => 'md5',
        "sign_version" => 2,
        "vars"         => $vars ,
    );
    //整理生成签名所需参数
    $temp = $post_data;
    unset($temp['vars']);
    ksort($temp);
    reset($temp);
    $tempStr = "";
    foreach ($temp as $key => $value) {
        $tempStr .= $key . "=" . $value . "&";
    }
    $tempStr = substr($tempStr, 0, -1);
    //生成签名
    $post_data['signature'] = md5($appid . $appkey . $tempStr . $appid . $appkey);


    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL            => 'https://api.mysubmail.com/message/xsend.json',
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_POST           => 1,
        CURLOPT_POSTFIELDS     => $post_data
    ));
    $output = curl_exec($ch);
    curl_close($ch);

    return $output;
    
}


function saiyounotice($phone,$content){
   
    /*****************
     * 加密请求 示例代码
     ******************/
    //appid参数 appkey参数在     短信-创建/管理AppID中获取
    //手机号支持单个
    //短信内容：签名+正文    详细规则查看短信-模板管理
    $appid = '77300';                                                               //appid参数
    $appkey = '12afc9d405aad2c5c3ebff37adab66e7';                                   //appkey参数
    $to = $phone;                                                            //收信人 手机号码
    $content = '【CorDx说】'.$content;
    
    //通过接口获取时间戳
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL            => 'https://api-v4.mysubmail.com/service/timestamp.json',
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_POST           => 0,
    ));
    $output = curl_exec($ch);
    curl_close($ch);
    $output = json_decode($output, true);
    $timestamp = $output['timestamp'];

    $post_data = array(
        "appid"         => $appid,
        "to"            => $to,
        "timestamp"     => $timestamp,
        "sign_type"     => 'md5',
        "sign_version"  => 2,
        "content"       => $content,
    );
    //整理生成签名所需参数
    $temp = $post_data;
    unset($temp['content']);
    ksort($temp);
    reset($temp);
    $tempStr = "";
    foreach ($temp as $key => $value) {
        $tempStr .= $key . "=" . $value . "&";
    }
    $tempStr = substr($tempStr, 0, -1);
    //生成签名
    $post_data['signature'] = md5($appid . $appkey . $tempStr . $appid . $appkey);

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL            => 'https://api-v4.mysubmail.com/sms/send.json',
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_POST           => 1,
        CURLOPT_POSTFIELDS     => $post_data,
    ));
    $output = curl_exec($ch);
    curl_close($ch);

    return $output;
    
}


/**
 * YzxSms 云之讯短信
 * @param $param 验证码
 * @param $mobile 手机号
 * @param $uid 用户透传id
 * @param $templateid 模板id
 * @return array  code:000000 msg:OK
 */
function YzxSms($param,$mobile,$templateid='',$uid=1){
    $appid = config('yzx.appid');
    if($templateid == ''){
        $templateid = config('yzx.templateid');
    }
    $options = ['accountsid'=>config('yzx.accountsid'),'token'=>config('yzx.token')];
    $yzxsms = new Ucpaas($options);
    $result = $yzxsms->SendSms($appid,$templateid,$param,$mobile,$uid);
    $result = json_decode($result,true);
    return $result;
}


function randString()
{
    $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $rand = $code[rand(0,25)]
        .strtoupper(dechex(date('m')))
        .date('d').substr(time(),-5)
        .substr(microtime(),2,5)
        .sprintf('%02d',rand(0,99));
    for(
        $a = md5( $rand, true ),
        $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV',
        $d = '',
        $f = 0;
        $f < 8;
        $g = ord( $a[ $f ] ),
        $d .= $s[ ( $g ^ ord( $a[ $f + 8 ] ) ) - $g & 0x1F ],
        $f++
    );
    return  $d;
}

//获取token
function gettoken(){
    $url = 'https://aip.baidubce.com/oauth/2.0/token';
    $post_data['grant_type']       = 'client_credentials';
    $post_data['client_id']      = 'NDhIj6lsZ6B9tdazEQBM5D61';
    $post_data['client_secret'] = 'YryBG82Sl2qjt4IPb6pHHIteD7buzYgC';
    $o = "";
    foreach ( $post_data as $k => $v ) 
    {
    	$o.= "$k=" . urlencode( $v ). "&" ;
    }
    $post_data = substr($o,0,-1);
    
    $res = request_post($url, $post_data);
    
    $ress = json_decode($res,true);
    return $ress['access_token'];
}

/**
* 发起http post请求(REST API), 并获取REST请求的结果
* @param string $url
* @param string $param
* @return - http response body if succeeds, else false.
*/
function request_post($url = '', $param = '')
{
    if (empty($url) || empty($param)) {
        return false;
    }

    $postUrl = $url;
    $curlPost = $param;
    // 初始化curl
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $postUrl);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    // 要求结果为字符串且输出到屏幕上
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    // post提交方式
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
    // 运行curl
    $data = curl_exec($curl);
    curl_close($curl);

    return $data;
}


function apireturn($code,$msg,$data=''){
    $data_rt['status'] = $code;
    $data_rt['msg'] = $msg;
    if(!empty($data)){
        $data_rt['data'] = $data;
    }else{
        $data_rt['data'] = array();
    }
    return json_encode($data_rt,TRUE);
}



//更新站点统计数据
//qid 大学堂id
//catid 站点id
//score 分数
function updzhandian($qid,$uid,$catid,$socre){

    $whrs['qid'] = $qid;
    $whrs['uid'] = $uid;
    $whrs['catid'] = $catid;
    $num = Db::name('zdrecord_list')->where($whrs)->count();

    if($num > 0){
        $parentid = Db::name('cate')->where('id',$catid)->value('parentid');
        if($parentid > 0){
            updzhandian($qid,$uid,$parentid,$socre);
        }
    }else{
        if($catid > 0){
            $datas['qid'] = $qid;
            $datas['uid'] = $uid;
            $datas['catid'] = $catid;
            Db::name('zdrecord_list')->insert($datas);

            $whr['qid'] = $qid;
            $whr['catid'] = $catid;
            $zinfo = Db::name('zdrecord')->where($whr)->find();
            if($zinfo){
                $data['number'] = $zinfo['number']+1;
                $data['score'] = $zinfo['score']+$socre;
                $data['update_time'] = time();
                $whrz['id'] = $zinfo['id'];
                Db::name('zdrecord')->where($whrz)->update($data);
                //更新上上级
                $parentid = Db::name('cate')->where('id',$catid)->value('parentid');
                if($parentid > 0){
                    updzhandian($qid,$uid,$parentid,$socre);
                }
            }else{
                $data['number'] = 1;
                $data['score'] = $socre;
                $data['qid'] = $qid;
                $data['catid'] = $catid;
                $data['create_time'] = time();
                $data['update_time'] = time();
                Db::name('zdrecord')->insert($data);
                //更新上上级
                $parentid = Db::name('cate')->where('id',$catid)->value('parentid');
                if($parentid > 0){
                    updzhandian($qid,$uid,$parentid,$socre);
                }
            }
        }
    }
}

//特殊字符转换
function zhuan($val1){
    $val1 = str_replace(',','，',$val1);
    $val1 = str_replace('(','（',$val1);
    $val1 = str_replace(')','）',$val1);
    $val1 = str_replace(':','：',$val1);
    $val1 = str_replace(';','；',$val1);
    $val1 = str_replace('!','！',$val1);
    $val1 = str_replace('|','｜',$val1);
    $val1 = str_replace(' ','',$val1);
    //$val1 = str_replace(PHP_EOL, '', $val1);
    $val1 = str_replace('座','',$val1);
    $val1 = str_replace('型','',$val1);
    $val1 = str_replace('&amp;','&',$val1);
    $val1 = strtoupper($val1);
    return $val1;
}


//分割中文字符串
function mb_str_split($str){
    return preg_split('/(?<!^)(?!$)/u', $str );
}


function fg($str){

    $arr_cont = preg_split("//u", $str, -1,PREG_SPLIT_NO_EMPTY);

    return $arr_cont;
}


function yingshe($num){
    $ZM = 'A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z';
    $array = explode(',',$ZM);
    return $array[$num];

}

/**

*根据二维数组某个字段的值查找数组

* @param $index 数组中的key

* @param $value 查找的值

*/
function seacharr_by_value($array, $index, $value){
    
    $newarray = array();
    
    if(is_array($array) && count($array)>0) {
    
        foreach(array_keys($array) as $key){
        
            $temp[$key] = $array[$key][$index];
            
            if ($temp[$key] == $value){
            
                $newarray[$key] = $array[$key];
            
            }
            
        }
        
    }

    return $newarray;

}




// 图片压缩
/**
 * common.php
 * $url:图片路径
 * $size_number:图片需要的大小，kb为单位
 * $size_thumb:图片需要的宽度
 */
function image_zip($url,$size_number,$size_thumb)
{
	$size = filesize($url);        
	if(($size/1000)>$size_number){
		$image = \think\Image::open($url);
		$image->thumb($size_thumb, $size_thumb)->save($url);
	}
}


//查分类
function sellist($ids,$val){
    if($val != ''){
        
        $cs = explode('_',$val);
        
        $csids = explode('||',$cs[1]);
        
        $items = '';
        foreach($csids as $vals){
            $items .= $vals.',';
        }
        $whraa = [];
        $whraa[] = ['spec_id','=',$cs[0]];
        $whraa[] = ['result','in',$items];
        if($ids){
            $whraa[] = ['product_id','in',$ids];
        }
        
        $ids = Db::name('product_relation')->field('product_id')->where($whraa)->select();
        return $ids;
    }else{
        return $ids;
    }

}

//资质操作记录
function aptitudelog($type_id,$zzid,$aptitude_type,$aptitude_content,$aptitude_uid){
    
    if($type_id == 1){
        $where['id'] = $zzid;
        $info = Db::name('register_credential')
            ->field('bianhao,name')
            ->where($where)
            ->find();
        $data['aptitude_bianhao'] = $info['bianhao'];
        $data['aptitude_name'] = $info['name'];
    }elseif($type_id == 2){
        $where['id'] = $zzid;
        $info = Db::name('brand')
            ->field('bianhao,name')
            ->where($where)
            ->find();
        $data['aptitude_bianhao'] = $info['bianhao'];
        $data['aptitude_name'] = $info['name'];
    }else{
        $where['id'] = $zzid;
        $info = Db::name('other')
            ->field('title,name')
            ->where($where)
            ->find();
        $data['aptitude_bianhao'] = $info['title'];
        $data['aptitude_name'] = $info['name'];
    }
    $data['type_id'] = $type_id;
    $data['zzid'] = $zzid;
    $data['aptitude_type'] = $aptitude_type;
    $data['aptitude_content'] = $aptitude_content;
    $data['aptitude_uid'] = $aptitude_uid;
    $data['aptitude_time'] = time();
    Db::name('aptitude_update')->insert($data);
    
}


//获取是否在考勤范围

function is_distance($lat,$lng,$uid){
    
    $data = Request::param();
    
    $today=strtotime(date('Y-m-d 00:00:00'));
    
    $map = [];
    $map[] = ['uid','=',$uid];
    $map[] = ['sub_time','>=',$today];
   
    $check_log = Db::name('check_log')->where($map)->order('sub_time asc')->select();
    
    $list = Db::name('location')->order('id asc')->select();
     
    $title = '';
    $address = '';
    $is_distance = 0;
    foreach($list as $key => $val){
         
        //获取实际距离
        $jl = getDistance($lat,$lng,$val['lat'],$val['lng'],$len_type = 1,$decimal = 2);
      
        if($jl <= $val['distance']){
            $title = $val['title'];
            $address = $val['address'];
            $is_distance++;
            $juli = $jl;
        }
    }
    
    $a['is_distance'] = $is_distance;
    $a['title'] = $title;
    $a['address'] = $address;
    
    return $a;
     
 }


//$startTime 当前时间 
//$min 变化分钟数
//$type + -
function strto_time($startTime,$min,$type){
    //echo date("H:i", strtotime("$startTime +30 min")); 
    
    return date("H:i", strtotime($startTime.$type.$min."min"));
}

function apireturns($code,$num,$msg,$data=''){
    $data_rt['status'] = $code;
    $data_rt['num'] = $num;
    $data_rt['msg'] = $msg;
    if(!empty($data)){
        $data_rt['data'] = $data;
    }
    return json_encode($data_rt,TRUE);
}

 /* 计算两组经纬度坐标之间的距离
 * @param $lat1 纬度1
 * @param $lng1 经度1
 * @param $lat2 纬度2
 * @param $lng2 经度2
 * @param int $len_type 返回值类型(1-m 2-km)
 * @param int $decimal 保留小数位数
 * @return float
 */
 function getDistance($lat1, $lng1, $lat2, $lng2, $len_type = 1, $decimal = 2)
 {
   $radLat1 = $lat1 * 3.1415926 / 180.0;
   $radLat2 = $lat2 * 3.1415926 / 180.0;
   $a = $radLat1 - $radLat2;
   $b = ($lng1 * 3.1415926 / 180.0) - ($lng2 * 3.1415926 / 180.0);
   $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
   $s = $s * 6378.137;
   $s = round($s * 1000);
   if ($len_type > 1) {
     $s /= 1000;
   }
   return round($s, $decimal);
 }
 
 /* 更新每日统计单人打卡记录
 * @param $uid 用户id
 * @param $attendance_group_id 考勤组id
 * @param $classesid 班次id
 * @param $day 日期
 * @param int $status 打卡状态 1正常2迟到3早退
 * @return float
 */ 
function update_check($uid,$attendance_group_id,$classesid,$day,$status){
    
    
    $map = [];
    $map[] = ['uid','=',$uid];
    $map[] = ['attendance_group_id','=',$attendance_group_id];
    $map[] = ['classesid','=',$classesid];
    $map[] = ['day','=',$day];
    $info = Db::name('check_count')->where($map)->find();
    if($info){
        //更新
        if($status == 1){
            $data['zcnum'] = $info['zcnum']+1;
        }else if($status == 2){
            $data['cdnum'] = $info['cdnum']+1;
        }else if($status == 3){
            $data['ztnum'] = $info['ztnum']+1;
        }
        $data['znum'] = $info['znum']+1;
        
        Db::name('check_count')->where('id',$info['id'])->update($data);
    }else{
        
        //添加
        $commuting_num = Db::name('classes')->where('id',$classesid)->value('commuting_num');
        
        $data['uid'] = $uid;
        $data['attendance_group_id'] = $attendance_group_id;
        $data['classesid'] = $classesid;
        $data['day'] = $day;
        $data['num'] = $commuting_num*2;
        if($status == 1){
            $data['zcnum'] = 1;
        }else{
            $data['zcnum'] = 0;
        }
        if($status == 2){
            $data['cdnum'] = 1;
        }else{
            $data['cdnum'] = 0;
        }
        if($status == 3){
            $data['ztnum'] = 1;
        }else{
            $data['ztnum'] = 0;
        }
        $data['znum'] = 1;
        Db::name('check_count')->insert($data);
        
    } 
}

//返回星期几
function days($num){
    if($num == 1){
        $text = '星期一';
    }else if($num == 2){
        $text = '星期二';
    }else if($num == 3){
        $text = '星期三';
    }else if($num == 4){
        $text = '星期四';
    }else if($num == 5){
        $text = '星期五';
    }else if($num == 6){
        $text = '星期六';
    }else if($num == 7){
        $text = '星期日';
    }else{
        $text = '无';
    }
    return $text;
}

//返回打卡状态
function status($num){
    if($num == 1){
        $text = '正常';
    }else if($num == 2){
        $text = '迟到';
    }else if($num == 3){
        $text = '早退';
    }else if($num == 4){
        $text = '半天缺卡';
    }else{
        $text = '无';
    }
    return $text;
}


/*
 * Effect    生成当月数组
 * author    FuJiHui
 * email     237813405@qq.com
 * time      2018-12-14 10:04:19
 * parameter date 当月日期
 * */
function getDateOfMonth($date)
{
    $timestamp = strtotime($date);
    $j = date('t',$timestamp); //获取当前月份天数
    $year = date('Y',$timestamp);
    $month = date('m',$timestamp);
    $start_time = strtotime(date($year.'-'.$month.'-01'));  //获取本月第一天时间戳
    $mDates = array();
    for($i=0;$i<$j;$i++){
        $mDates[] = date('Y-m-d',$start_time+$i*86400); //每隔一天赋值给数组
    }
    return $mDates;
}


function getMoreArry($arr,$arr_count) {

         $b = array();

         for($y=0;$y<$arr_count;$y++){
                for($x=0;$x<1;$x++){
                    $b[$y][$x] = $arr[$y];
                }
            }

         return $b;

}


//替换二维数组key
function subOrderSearch($chuqin_list,$keyword) {
    $chuqin_lists = array();
    foreach($chuqin_list as $key => $val){
        $chuqin_lists[$key][$keyword] = $val;
    }
    return $chuqin_lists;
}


//发送审批给审批人
function start_apply($unionid,$sort){
    
    $whr['unionid'] = $unionid;
    $whr['sort'] = $sort;
    $whr['is_send'] = 1;
    $data['is_send'] = 2;
    $data['sub_time'] = time();
    
    if(Db::name('flow_apply')->where($whr)->count() > 0){
        
        //如果不是结束
        if(Db::name('flow_apply')->where($whr)->value('flow_leixing') != 4){
            
            //如果是抄送继续下一步
            if(Db::name('flow_apply')->where($whr)->value('flow_leixing') == 3){
                $fl = Db::name('flow_list')->where('unionid',$unionid)->find();
                if($fl['status'] != 3){
                    $list = Db::name('flow_apply')->where($whr)->select();
                    $openid = '';
                    foreach($list as $key => $val){
                        $openid = Db::name('weixin')->where('uid',$val['apply_uid'])->value('openid');
                        if($openid){
                            //根据unionid获取信息
                            $flows = Db::name('flow_list')->field('flow_type,flow_id')->where('parentid',$val['unionid'])->find();
                            $info = Db::name($flows['flow_type'])->where('id',$flows['flow_id'])->find();
                            if($flows['flow_type'] == 'jiaban'){
                                $uname = Db::name('users')->where('id',$info['uid'])->value('username');
                                $title = $uname."「加班」审批抄送通知";
                                
                                if($info['leixing'] == 1){
                                    $lxname = '日常加班 ';
                                }else if($info['leixing'] == 2){
                                    $lxname = '周六日加班 ';
                                }
                                $neirong = $info['start'].' 至 '.$info['end'];
                                $shijian = date('m-d H:i',$val['create_time']);
                            }else if($flows['flow_type'] == 'buka'){
                            
                                $uname = Db::name('users')->where('id',$info['uid'])->value('username');
                                $title = $uname."「补卡」审批抄送通知";
                                
                                if($info['leixing'] == 2){
                                    $lxname = $info['shijian'].'，迟到';
                                }else if($info['leixing'] == 3){
                                    $lxname = $info['shijian'].'，早退';
                                }else if($info['leixing'] == 4){
                                    $lxname = $info['shijian'].'，缺卡';
                                }
                                $neirong = $info['riqi'].' ';
                                $neirong .=  '，'.$info['reason'];
                                $shijian = date('m-d H:i',$val['create_time']);
                            }else if($flows['flow_type'] == 'qingjia'){
                               
                                $uname = Db::name('users')->where('id',$info['uid'])->value('username');
                                $title = $uname."「请假」审批抄送通知";
                                
                                if($info['leixing'] == 1){
                                    $lxname = '事假 ';
                                }else if($info['leixing'] == 2){
                                    $lxname = '婚假 ';
                                }else if($info['leixing'] == 3){
                                    $lxname = '产假 ';
                                }else if($info['leixing'] == 4){
                                    $lxname = '丧假 ';
                                }else if($info['leixing'] == 5){
                                    $lxname = '调休 ';
                                }
                                $neirong = $info['start'].' 至 '.$info['end'];
                                $shijian = date('m-d H:i',$val['create_time']);
                            }else if($flows['flow_type'] == 'gnchuchai'){
                                
                                $uname = Db::name('users')->where('id',$info['uid'])->value('username');
                                $title = $uname."「国内出差」审批抄送通知";
                               
                                if($info['cctype'] == 1){
                                    $lxname = '单程';
                                }else{
                                    $lxname = '往返';
                                }
                                
                                $neirong = $info['start'].' 至 ';
                                $neirong .= $info['end'].' ，从 ';
                                $neirong .= $info['chufa'].' 至 ';
                                $neirong .= $info['mudi'].' ';
                                $neirong = str_replace('>','',$neirong);
                                
                                $shijian = date('m-d H:i',$val['create_time']);
                            }else if($flows['flow_type'] == 'gjchuchai'){
                                
                                $uname = Db::name('users')->where('id',$info['uid'])->value('username');
                                $title = "「国际出差」审批抄送通知";
                               
                                if($info['cctype'] == 1){
                                    $lxname = '单程';
                                }else{
                                    $lxname = '往返';
                                }
                                
                                $neirong = $info['start'].' 至 ';
                                $neirong .= $info['end'].' ，从 ';
                                $neirong .= $info['chufa'].' 至 ';
                                $neirong .= $info['mudi'].' ';
                                
                                $shijian = date('m-d H:i',$val['create_time']);
                            }else if($flows['flow_type'] == 'burujia'){
                                
                                $uname = Db::name('users')->where('id',$info['uid'])->value('username');
                                $title = "「哺乳假」审批抄送通知";
                                
                                if($info['leixing'] == 1){
                                    $lxname = '延后上班1小时';
                                }else if($info['leixing'] == 2){
                                    $lxname = '提前下班1小时';
                                }
                                $neirong = $info['start'].' 至 '.$info['end'];
                               
                                $shijian = date('m-d H:i',$val['create_time']);
                            }
                            
                            $dataq['title'] = $title;
                            $dataq['leixing'] = $lxname;
                            $dataq['neirong'] = $neirong;
                            $dataq['shijian'] = $shijian;
                            $dataq['openid'] = $openid;
                            $dataq['uname'] = $uname;
                            $dataq['type'] = 3;
                            
                            Db::name('wxnotice')->insert($dataq);
                            $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                            $url=$http_type.$_SERVER['HTTP_HOST']."/api/wxnofiy/guestbook";
                            http_curl($url,'post','json',$dataq);
                        }
                    }
                    
                    Db::name('flow_apply')->where($whr)->update($data);
                    $sort = $sort + 1;
                    start_apply($unionid,$sort);
                }
            }else{
                
                $list = Db::name('flow_apply')->where($whr)->select();
                
                $openid = '';
                foreach($list as $key => $val){
                    $openid = Db::name('weixin')->where('uid',$val['apply_uid'])->value('openid');
                    if($openid){
                       
                        //根据unionid获取信息
                        $flows = Db::name('flow_list')->field('flow_type,flow_id')->where('parentid',$val['unionid'])->find();
                        $info = Db::name($flows['flow_type'])->where('id',$flows['flow_id'])->find();
                        
                        if($flows['flow_type'] == 'jiaban'){
                            $uname = Db::name('users')->where('id',$info['uid'])->value('username');
                            $title = $uname."向您发起「加班」申请待审批";
                            if($info['leixing'] == 1){
                                $lxname = '日常加班 ';
                            }else if($info['leixing'] == 2){
                                $lxname = '周六日加班 ';
                            }
                            $neirong = $info['start'].' 至 '.$info['end'];
                            $shijian = date('m-d H:i',$val['create_time']);
                        }else if($flows['flow_type'] == 'buka'){
                        
                            $uname = Db::name('users')->where('id',$info['uid'])->value('username');
                            $title = $uname."向您发起「补卡」申请待审批";
                            
                            if($info['leixing'] == 2){
                                $lxname = $info['shijian'].'，迟到';
                            }else if($info['leixing'] == 3){
                                $lxname = $info['shijian'].'，早退';
                            }else if($info['leixing'] == 4){
                                $lxname = $info['shijian'].'，缺卡';
                            }
                            $neirong = $info['riqi'].' ';
                            $neirong .=  '，'.$info['reason'];
                            $shijian = date('m-d H:i',$val['create_time']);
                        }else if($flows['flow_type'] == 'qingjia'){
                           
                            $uname = Db::name('users')->where('id',$info['uid'])->value('username');
                            $title = $uname."向您发起「请假」申请待审批";
                            
                            if($info['leixing'] == 1){
                                $lxname = '事假 ';
                            }else if($info['leixing'] == 2){
                                $lxname = '婚假 ';
                            }else if($info['leixing'] == 3){
                                $lxname = '产假 ';
                            }else if($info['leixing'] == 4){
                                $lxname = '丧假 ';
                            }else if($info['leixing'] == 5){
                                $lxname = '调休 ';
                            }
                            $neirong = $info['start'].' 至 '.$info['end'];
                            $shijian = date('m-d H:i',$val['create_time']);
                        }else if($flows['flow_type'] == 'gnchuchai'){
                            
                            $uname = Db::name('users')->where('id',$info['uid'])->value('username');
                            $title = $uname."向您发起「国内出差」申请待审批";
                           
                            if($info['cctype'] == 1){
                                $lxname = '单程';
                            }else{
                                $lxname = '往返';
                            }
                            
                            $neirong = $info['start'].' 至 ';
                            $neirong .= $info['end'].' ，从 ';
                            $neirong .= $info['chufa'].' 至 ';
                            $neirong .= $info['mudi'].' ';
                            $neirong = str_replace('>','',$neirong);
                            
                            $shijian = date('m-d H:i',$val['create_time']);
                        }else if($flows['flow_type'] == 'gjchuchai'){
                            
                            $uname = Db::name('users')->where('id',$info['uid'])->value('username');
                            $title = $uname."向您发起「国际出差」申请待审批";
                           
                            if($info['cctype'] == 1){
                                $lxname = '单程';
                            }else{
                                $lxname = '往返';
                            }
                            
                            $neirong = $info['start'].' 至 ';
                            $neirong .= $info['end'].' ，从 ';
                            $neirong .= $info['chufa'].' 至 ';
                            $neirong .= $info['mudi'].' ';
                            
                            $shijian = date('m-d H:i',$val['create_time']);
                        }else if($flows['flow_type'] == 'burujia'){
                            
                            $uname = Db::name('users')->where('id',$info['uid'])->value('username');
                            $title = $uname."向您发起「哺乳假」申请待审批";
                            
                            if($info['leixing'] == 1){
                                $lxname = '延后上班1小时';
                            }else if($info['leixing'] == 2){
                                $lxname = '提前下班1小时';
                            }
                            $neirong = $info['start'].' 至 '.$info['end'];
                           
                            $shijian = date('m-d H:i',$val['create_time']);
                        }
                            
                        $dataq['title'] = $title;
                        $dataq['leixing'] = $lxname;
                        $dataq['neirong'] = $neirong;
                        $dataq['shijian'] = $shijian;
                        $dataq['openid'] = $openid;
                        $dataq['uname'] = '';
                        $dataq['type'] = 2;
                        
                        Db::name('wxnotice')->insert($dataq);
                        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                        $url=$http_type.$_SERVER['HTTP_HOST']."/api/wxnofiy/guestbook";
                        http_curl($url,'post','json',$dataq);
                        
                    }
                }
                Db::name('flow_apply')->where($whr)->update($data);
                
            }
            
        }else{
            
            $flinfo = Db::name('flow_apply')->where($whr)->find();
            
            Db::name('flow_apply')->where($whr)->update($data);
            $fl = Db::name('flow_list')->where('parentid',$unionid)->find();
            if($fl['status'] != 3){
                
                $dataz['status'] = 2;
                $dataz['update_time'] = time();
                Db::name('flow_list')->where('parentid',$unionid)->update($dataz);
                
                //发送通过通知
                //根据unionid获取信息
                $flows = Db::name('flow_list')->field('uid,flow_type,flow_id,create_time')->where('parentid',$flinfo['unionid'])->find();
                $info = Db::name($flows['flow_type'])->where('id',$flows['flow_id'])->find();
                if($flows['flow_type'] == 'jiaban'){
                    $uname = Db::name('users')->where('id',$flows['uid'])->value('username');
                    
                    $neirong = $info['start'].' 至 '.$info['end'];
                    if($info['leixing'] == 1){
                        $neirong .= '，「日常加班」申请';
                    }else if($info['leixing'] == 2){
                        $neirong .= '，「周六日加班」申请';
                    }
                    
                    $shijian = date('m-d H:i',$flows['create_time']);
                }else if($flows['flow_type'] == 'buka'){
                
                    $uname = Db::name('users')->where('id',$flows['uid'])->value('username');
                    
                    $neirong = $info['riqi'].' ';
                    if($info['leixing'] == 2){
                        $lxname = $info['shijian'].'，迟到';
                    }else if($info['leixing'] == 3){
                        $lxname = $info['shijian'].'，早退';
                    }else if($info['leixing'] == 4){
                        $lxname = $info['shijian'].'，缺卡';
                    }
                    $neirong .=  '补卡申请';
                    
                    $shijian = date('m-d H:i',$flows['create_time']);
                }else if($flows['flow_type'] == 'qingjia'){
                   
                    $uname = Db::name('users')->where('id',$flows['uid'])->value('username');
                    
                    $neirong = $info['start'].' 至 '.$info['end'];
                    if($info['leixing'] == 1){
                        $neirong .= '，事假 ';
                    }else if($info['leixing'] == 2){
                        $neirong .= '，婚假 ';
                    }else if($info['leixing'] == 3){
                        $neirong .= '，产假 ';
                    }else if($info['leixing'] == 4){
                        $neirong .= '，丧假 ';
                    }else if($info['leixing'] == 5){
                        $neirong .= '，调休 ';
                    }
                    
                    $shijian = date('m-d H:i',$flows['create_time']);
                }else if($flows['flow_type'] == 'gnchuchai'){
                    
                    $uname = Db::name('users')->where('id',$flows['uid'])->value('username');
                    
                    $neirong = $info['start'].' 至 ';
                    $neirong .= $info['end'].' ，从 ';
                    $neirong .= $info['chufa'].' 至 ';
                    $neirong .= $info['mudi'].' ';
                    
                    if($info['cctype'] == 1){
                        $neirong .= '单程';
                    }else{
                        $neirong .= '往返';
                    }
                    $neirong = str_replace('>','',$neirong);
                    
                    
                    $shijian = date('m-d H:i',$flows['create_time']);
                }else if($flows['flow_type'] == 'gjchuchai'){
                    
                    $uname = Db::name('users')->where('id',$flows['uid'])->value('username');
                    
                    $neirong = $info['start'].' 至 ';
                    $neirong .= $info['end'].' ，从 ';
                    $neirong .= $info['chufa'].' 至 ';
                    $neirong .= $info['mudi'].' ';
                    
                    if($info['cctype'] == 1){
                        $neirong .= '，单程';
                    }else{
                        $neirong .= '，往返';
                    }
                    $neirong = str_replace('>','',$neirong);
                    
                    $shijian = date('m-d H:i',$flows['create_time']);
                }else if($flows['flow_type'] == 'burujia'){
                    
                    $uname = Db::name('users')->where('id',$flows['uid'])->value('username');
                    
                    $neirong = $info['start'].' 至 '.$info['end'];
                    if($info['leixing'] == 1){
                        $neirong .= '，延后上班1小时';
                    }else if($info['leixing'] == 2){
                        $neirong .= '，提前下班1小时';
                    }
                    
                   
                    $shijian = date('m-d H:i',$flows['create_time']);
                }
                
                
                $openlist = Db::name('weixin')->field('uid,openid')->where('uid','in',$flinfo['shenqing_uid'])->select();
                foreach ($openlist as $k => $v){
                    if($v['openid']){
                         //所有字段都可为空
                        $dataq['uname'] = Db::name('users')->where('id',$v['uid'])->value('username');
                        $dataq['neirong'] = $neirong;
                        $dataq['shijian'] = $shijian;
                        $dataq['openid'] = $v['openid'];
                        $dataq['type'] = 2;
                        Db::name('wxnotice')->insert($dataq);
                        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                        $url=$http_type.$_SERVER['HTTP_HOST']."/api/wxnofiy/tongzhi";
                        http_curl($url,'post','json',$dataq);
                    }
                }
            
                
                //添加补卡记录
                if($fl['flow_type'] == 'buka'){
                    $buka = Db::name($fl['flow_type'])->where('id',$fl['flow_id'])->find();
                    
                    $riqi = $buka['riqi'];
                    $shijian = $buka['shijian'];
                    $check_num = $buka['check_num'];
                    //获取班次信息
                    //$classesinfo = Db::name('classes')->where('id',$buka['classid'])->find();
                  
                    if(Db::name('flow_apply')->where('unionid',$unionid)->where('sort',$sort)->value('flow_leixing') == 4){
                        
                        if($buka['leixing'] == 2){
                            
                            $upd['shijian'] = $shijian;
                            $upd['status'] = 1;
                            $whr_upd['riqi'] = $riqi;
                            $whr_upd['check_num'] = $check_num;
                            $whr_upd['classesid']= $buka['classid'];
                            $whr_upd['uid'] = $buka['uid'];
                            
                            Db::name('check_log')->where($whr_upd)->update($upd);
                            
                            Db::name('check_count')->where('uid',$buka['uid'])->where('attendance_group_id',Db::name('attendance_group_user')->where('uid',$buka['uid'])->value('attendance_group_id'))->where('classesid',$buka['classid'])->where('day',$riqi)->setInc('zcnum');
                            
                            Db::name('check_count')->where('uid',$buka['uid'])->where('attendance_group_id',Db::name('attendance_group_user')->where('uid',$buka['uid'])->value('attendance_group_id'))->where('classesid',$buka['classid'])->where('day',$riqi)->setDec('cdnum');
                        }
                        if($buka['leixing'] == 3){
                            
                            $upd['shijian'] = $shijian;
                            $upd['status'] = 1;
                            $whr_upd['riqi'] = $riqi;
                            $whr_upd['check_num'] = $check_num;
                            $whr_upd['classesid']= $buka['classid'];
                            $whr_upd['uid'] = $buka['uid'];
                            
                            Db::name('check_log')->where($whr_upd)->update($upd);
                            
                            Db::name('check_count')->where('uid',$buka['uid'])->where('attendance_group_id',Db::name('attendance_group_user')->where('uid',$buka['uid'])->value('attendance_group_id'))->where('classesid',$buka['classid'])->where('day',$riqi)->setInc('zcnum');
                            
                            Db::name('check_count')->where('uid',$buka['uid'])->where('attendance_group_id',Db::name('attendance_group_user')->where('uid',$buka['uid'])->value('attendance_group_id'))->where('classesid',$buka['classid'])->where('day',$riqi)->setDec('ztnum');
                        }
                        if($buka['leixing'] == 4){
                            //添加记录
                            $add['uid'] = $buka['uid'];
                            $add['lat'] = 0;
                            $add['lng'] = 0;
                            $add['attendance_group_id'] = Db::name('attendance_group_user')->where('uid',$buka['uid'])->where('status',1)->value('attendance_group_id');
                            $add['classesid'] = $buka['classid'];
                            $add['check_num'] = $check_num;
                            $add['riqi'] = $riqi;
                            $add['shijian'] = $shijian;
                            $add['zcshijian'] = $shijian;
                            $add['sub_time'] = time();
                            $add['title'] = '';
                            $add['address'] = '';
                            $add['status'] = 1;
                            $add['check_type'] = 1;
                            $add['create_time'] = time();
                            $add['update_time'] = time();
                            
                            Db::name('check_log')->insert($add);
                            
                            //每日统计增加
                            update_check($buka['uid'],Db::name('attendance_group_user')->where('uid',$buka['uid'])->where('status',1)->value('attendance_group_id'),$buka['classid'],$riqi,1);
                   
                        
                        }
                    }
                    
                }
                
            }
        }
    }else{
        $zsort = Db::name('flow_apply')->where('unionid',$unionid)->max('sort');
        if($sort < $zsort){
            $sort = $sort + 1;
            start_apply($unionid,$sort);
        }
    }
}

/**
 * 获取来源地址
 * @return string
 */
function get_url() {
    //获取来源地址
    $url = "http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
    return $url;
}



// php CURL请求
function curl($url, $post = false){
     $ch = curl_init();
     curl_setopt($ch, CURLOPT_URL, $url);
     curl_setopt($ch, CURLOPT_HEADER, 0);
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
     if ($post) {
         curl_setopt($ch, CURLOPT_POST, 1);
         curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
     }
     $result = curl_exec($ch);
     curl_close($ch);
     return $result;
}

function http_curl($url,$type='get',$res='json',$arr=''){
    //1.初始化curl
    $ch = curl_init();
    //2.设置curl的参数
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //不验证证书
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if ($type == 'post') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $arr);
    }
    //3.采集
    $output = curl_exec($ch);
    //4.关闭
    curl_close($ch);
    if ($res == 'json') {
        return json_decode($output,true);
    }
}

//查询请假名称
function qingjia($leixing){
    
    if($leixing == 1){
        $name = '事假';
    }else if($leixing == 2){
        $name = '婚嫁';
    }else if($leixing == 3){
        $name = '产假';
    }else if($leixing == 4){
        $name = '丧假';
    }else if($leixing == 5){
        $name = '调休';
    }
    
    return $name;
}


/**
 * 查询两个日期之间所有的日期
 * Returns every date between two dates as an array
 * @param string $startDate the start of the date range
 * @param string $endDate the end of the date range
 * @param string $format DateTime format, default is Y-m-d
 * @return array returns every date between $startDate and $endDate, formatted as "Y-m-d"
 */
function createDateRange($startDate, $endDate, $format = "Y-m-d")
{
    $begin = new DateTime($startDate);
    $end = new DateTime($endDate);
 
    $interval = new DateInterval('P1D'); // 1 Day
    $dateRange = new DatePeriod($begin, $interval, $end);
 
    $range = [];
    foreach ($dateRange as $date) {
        $range[] = $date->format($format);
    }
 
    return $range;
}
