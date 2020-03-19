<?php
/*
 *  Copyright (c) 2014 The CCP project authors. All Rights Reserved.
 *
 */
namespace Kewo;

class Center
{
    private $appid; //
    private $noncestr;
    private $app;
    private $role=3;
    private $key;//
    private $enabeLog = true; //日志开关。可填值：true、
    private $Filename = "./kewolog.txt"; //日志文件
    private $Handle;
    private $batch; //时间戳
    //private $baseUrl = "http://ke.test.hw2006.org/manageapi/v1/"; //路由请求基础路由
    private $baseUrl = "http://console.kewo.com/api/v1/"; //路由请求基础路由

    public function __construct($appid='',$app='',$key='',$baseUrl='',$role='')
    {
        $this->appid    = $appid;
        $this->noncestr = rand(1000,9999).time();
        $this->app      = $app;
        $this->key      = $key;
        $this->role     = $role??$this->role;
        $this->Handle   = fopen($this->Filename, 'a');
        $this->baseUrl  = $baseUrl??$this->baseUrl;
        $_SESSION['expire_in'] = 0;
        
    }
    /**
     * 主帐号鉴权
     */
    public function accAuth()
    {

        if ($this->key == "") {
            $data = new \stdClass();
            $data->errcode = '1003';
            $data->errmsg = '应用key为空';
            return $data;
        }
        if ($this->appid == "") {
            $data = new \stdClass();
            $data->errcode = '1002';
            $data->errmsg = 'appid为空';
            return $data;
        }
        if ($this->app == "") {
            $data = new \stdClass();
            $data->errcode = '1004';
            $data->errmsg = '应用ID为空';
            return $data;
        }
    }

    /**
     * 打印日志
     *
     * @param log 日志内容
     */
    public function showlog($log)
    {
        if ($this->enabeLog) {
            fwrite($this->Handle, $log . "\n");
        }
    }
    
    

    /**
     * 发起HTTPS请求
     *
     * @param url 请求路径
     * @param data 发送数据
     * @param header 请求头部信息
     * @param post 请求方式  默认为1 1为post请求   0为get 请求
     */
    public function curl($url, $data=[], $header, $post = 1)
    {
        if($post==1){
           $result= $this->curl_post($url, $data,$header);
        }else{
           $url.='?'.http_build_query($data);  
           $result= $this->curl_get($url,$header);
        }
        return $result;
    }
    /**
    * curl请求
     * @param url 请求路径
    */
    function curl_post($url, $postFields,$header) 
    {
    //初始化curl
        //初始化curl
        $ch = curl_init();
        //参数设置
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
       
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($ch);
        
        //连接失败
        if ($result == false) {
            $result = "{\"errcode\":\"1001\",\"errmsg\":\"网络错误\"}";
        }
        curl_close($ch);
        
        return $result;   
        
    }
    /**
     * curl请求
     * 
     * @return null
     */
    function curl_get($url,$header) 
    {
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

    /**
     * 发起HTTPS请求
     *
     * @param url 请求路径
     * @param path 文件相对路径
     */
    public function curl_post_file($url, $path)
    {
        //初始化curl
        $ch = curl_init();
        if (class_exists('\CURLFile')) {
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
            $data = array('media' => new \CURLFile(realpath($path))); //>=5.5
        } else {
            if (defined('CURLOPT_SAFE_UPLOAD')) {
                curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
            }
            $data = array('media' => '@' . realpath($path)); //<=5.5
        }
        //参数设置
        $res = curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        //连接失败
        if ($result == false) {
            $result = "{\"errcode\":\"1001\",\"errmsg\":\"网络错误\"}";
        }

        curl_close($ch);
        return $result;
    }

 
    /**
     * 微信登录获取页面
     * @param type int	1PC 2手机
     * 
     */
    public function loginByWechat($redirectTo='',$type=1,$role=3,$version=1)
    {
        //鉴权信息验证，对必选参数进行判空。
        $auth = $this->accAuth();
        if ($auth != "") {
            return $auth;
        }
        if($type==2){
            $type=4;
        }
        
        //检测用户角色
        $role =  isset($role)?$role:$this->role;
        
        $this->showlog("login by wechat,get login page, request datetime = " . date('y/m/d h:i') . "\n");
        
        // 生成请求URL
        $url = $this->baseUrl."loginIn";

        // 生成包头
        $header = array("Accept:application/json", "Content-Type:application/json;charset=utf-8");

        //数据
        $data['version']=$version;
        $data['appid']=$this->appid;
        $data['noncestr']=$this->noncestr;
        $data['timestamp']=time();
        
        $data['app']=$this->app;
        $data['role']=$role;
        $data['type']=3;
        $data['login']=$type;
        if($type==4){
            $data['redirectTo']=$redirectTo;
        }
        $key=$this->key;
        $data['sign']=makeSign($data,$key);
       
        // 发送请求
        $result = $this->curl($url, $data, $header, 0);
       
        $this->showlog("response body = " . $result . "\r\n");
        $datas = json_decode($result, true);

        return $datas;
    }
    /**
     * 手机号查重
     * 
     * @param mobile  int	手机号 
     */
    public function checkMobile($mobile,$role=3,$version=1)
    {
        //鉴权信息验证，对必选参数进行判空。
        $auth = $this->accAuth();
        if ($auth != "") {
            return $auth;
        }
        
        //检测用户角色
        $role || $role = $this->role;
        $this->showlog("check mobile,post checkMobile , request datetime = " . date('y/m/d h:i') . "\n");

        // 生成请求URL
        $url = $this->baseUrl."signUp";

        // 生成包头
        $header = array("Accept:application/json", "Content-Type:application/json;charset=utf-8");

        //数据
        $data['version']=$version;
        $data['appid']=$this->appid;
        $data['noncestr']=$this->noncestr;
        $data['timestamp']=time(); 
        
        $data['app']=$this->app;
        $data['mobile']=$mobile;
        $data['role']=$role;
        $data['type']=1;
        
        $key=$this->key;
        $data['sign']=makeSign($data,$key);

        // 发送请求
        $result = $this->curl($url, $data, $header, 1);
        $this->showlog("response body = " . $result . "\r\n");
        $datas = json_decode($result, true);

        return $datas;
    }
    /**
     * 手机号注册
     * @param mobile  int	手机号 
     *
     */
    public function registerByMobile($mobile,$role=3,$version=1)
    {
        //鉴权信息验证，对必选参数进行判空。
        $auth = $this->accAuth();
        if ($auth != "") {
            return $auth;
        }
        
        //检测用户角色
        $role || $role = $this->role;
        $this->showlog("register by mobile , request datetime = " . date('y/m/d h:i') . "\n");

        // 生成请求URL
        $url = $this->baseUrl."signUp";

        // 生成包头
        $header = array("Accept:application/json", "Content-Type:application/json;charset=utf-8");

        //数据
        $data['version']=$version;
        $data['appid']=$this->appid;
        $data['noncestr']=$this->noncestr;
        $data['timestamp']=time(); 
        
        $data['app']=$this->app;
        $data['mobile']=$mobile;
        $data['role']=$role;
        $data['type']=2;
        $data['login']=3;
        
        $key=$this->key;
        $data['sign']=makeSign($data,$key);

        // 发送请求
        $result = $this->curl($url, $data, $header, 1);
        
        $this->showlog("response body = " . $result . "\r\n");
        $datas = json_decode($result, true);

        return $datas;
    }
     /**
     * 微信登录验证
     * @param verifyCode  string	verifyCode 
     * @param type        int           type 1电脑 2手机端微信浏览器	 
     */
    public function checkLoginByWechat($type=1,$verifyCode,$role=3,$version=1)
    {
        //鉴权信息验证，对必选参数进行判空。
        $auth = $this->accAuth();
        if ($auth != "") {
            return $auth;
        }
        
        //检测用户角色
        $role || $role = $this->role;
        $this->showlog("register by checkLoginByWechat , request datetime = " . date('y/m/d h:i') . "\n");

        // 生成请求URL
        $url = $this->baseUrl."loginIn";

        // 生成包头
        $header = array("Accept:application/json", "Content-Type:application/json;charset=utf-8");

        //数据
        $data['version']=$version;
        $data['appid']=$this->appid;
        $data['noncestr']=$this->noncestr;
        $data['timestamp']=time(); 
        
        
        $data['app']=$this->app;
        $data['verifyCode']=$verifyCode;
        $data['role']=$role;
        $data['type']=3;
        $data['login']=2;
        
        $key=$this->key;
        $data['sign']=makeSign($data,$key);

        // 发送请求
        $result = $this->curl($url, $data, $header, 0);
       //dump($result);
        $this->showlog("response body = " . $result . "\r\n");
        $datas = json_decode($result, true);

        return $datas;
    }
     /**
     *  微信/支付宝 支付
     * @param  payType          String	       支付方式 默认1 1微信电脑 2微信手机   3支付宝电脑 4支付宝手机 5免费
     * @param  data             Array	       参数数组
    *  @param  type             String         交易类型 NATIVE或JSAPI 默认 NATIVE
     * @param  body             String         商品描述
     * @param  usernumber       String         usernumber
     * @param  out_trade_no     String         商户订单号
     * @param  total_fee        Int            标价金额 单位为元
     * @param  spbill_create_ip	String         终端ip地址
     * @param  notify_url	    String         支付成功异步通知地址
     * @param  product_id	    Int            商品id
     */
    public function createOrder($payType,$data,$role=3,$version=1)
    {
        //鉴权信息验证，对必选参数进行判空。
        $auth = $this->accAuth();
        if ($auth != "") {
            return $auth;
        }
        
        //检测用户角色
        $role || $role = $this->role;
        
        if($payType==1||$payType==2){
            $apiUrl='createWxOrder';
        }elseif($payType==3||$payType==4){
            $apiUrl='createAliOrder';
        }else{
            return false;
        }
        $this->showlog("register by ".$apiUrl." , request datetime = " . date('y/m/d h:i') . "\n");

        // 生成请求URL
        $url = $this->baseUrl.$apiUrl;

        // 生成包头
        $header = array("Accept:application/json", "Content-Type:application/json;charset=utf-8");
        //微信 支付宝
        switch ($payType) {
            case 1:
              $type='NATIVE';  
              break;
            case 2:
              $type='JSAPI';    
              break; 
            case 3:
              $type='PC';  
              break; 
            case 4:
              $type='MOBILE';
              break; 
            case 5:
              $type='';
              break; 
            default:
                return false;
                break;
        }
        
        //公共参数
        $data['version']=$version;
        $data['appid']=$this->appid;
        $data['noncestr']=$this->noncestr;
        $data['timestamp']=time(); ;
        
        //接口参数
        $data['app']=$this->app;
        $data['type']=$type;
        $data['data']=$data;
        
        $key=$this->key;
        $data['sign']=makeSign($data,$key);
        // 发送请求
        $result = $this->curl($url, $data, $header, 1);
       
        $this->showlog("response body = " . $result . "\r\n");
        
        $datas = json_decode($result, true);
       
        return $datas;
    }
     /**
     * 生成微信支付二维码
     * @param value   string	 需要生成二维码的内容
     * @param size    int       图片生成尺寸 默认12
     * @param margin  int	 图片边距 默认2
     */
    public function qr($value,$size,$margin)
    {
        $this->showlog("qr by account, request datetime = " . date('y/m/d h:i') . "\n");
        // 生成请求URL
        $url = $this->baseUrl."qr?value=".$value."&size=".$size.'&margin='.$margin;
        return $url;
    }
    /*
     * 查询订单状态
     * @param getOrder   string	 创建订单返回的 code
     * 
     */
    public function getOrder($number,$role=3,$version=1)
    {
        //鉴权信息验证，对必选参数进行判空。
        $auth = $this->accAuth();
        if ($auth != "") {
            return $auth;
        }
        
        //检测用户角色
        $role || $role = $this->role;
        $this->showlog("getOrder , request datetime = " . date('y/m/d h:i') . "\n");

        // 生成请求URL
        $url = $this->baseUrl."getOrder";

        // 生成包头
        $header = array("Accept:application/json", "Content-Type:application/json;charset=utf-8");

        //数据
        $data['version']=$version;
        $data['appid']=$this->appid;
        $data['noncestr']=$this->noncestr;
        $data['timestamp']=time();
        
        $data['app']=$this->app;
        $data['role']=$role;
        $data['number']=$number;
        
        $key=$this->key;
        $data['sign']=makeSign($data,$key);
        
        
        //发送请求
        $result = $this->curl($url, $data, $header, 0);
        
        $this->showlog("response body = " . $result . "\r\n");
        $datas = json_decode($result, true);

        return $datas;
    }
    /*
     * 同课窝中心同步数据
     * @param mobile    string	 手机号 
     * @param unionid   string	 unionid 
     */
    public function changeInfo($unionid,$mobile,$role=3,$version=1)
    {
        //鉴权信息验证，对必选参数进行判空。
        $auth = $this->accAuth();
        if ($auth != "") {
            return $auth;
        }
        
        //检测用户角色
        $role || $role = $this->role;
        $this->showlog("changeInfo , request datetime = " . date('y/m/d h:i') . "\n");

        // 生成请求URL
        $url = $this->baseUrl."changeInfo";

        // 生成包头
        $header = array("Accept:application/json", "Content-Type:application/json;charset=utf-8");

        //数据
        $data['version']=$version;
        $data['appid']=$this->appid;
        $data['noncestr']=$this->noncestr;
        $data['timestamp']=time(); 
        
        $data['app']=$this->app;
        $data['role']=$role;
        $data['mobile']=$mobile;
        $data['unionid']=$unionid;
        
        $key=$this->key;
        $data['sign']=makeSign($data,$key);
       
        //发送请求
        $result = $this->curl($url, $data, $header, 1);
        
        $this->showlog("response body = " . $result . "\r\n");
         
        $datas = json_decode($result, true);

        return $datas;
    }
    
    

}
