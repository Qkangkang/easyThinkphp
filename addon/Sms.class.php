<?php
/**
 * 网易云信server API 接口 2.0
 * Class ServerAPI
 * @author  hzchensheng15@corp.netease.com
 * @created date    2015-10-27  16:30
 *
 * @modified date 2016-06-15 19:30
 * *** 添加直播相关示例接口 ***
 * 
***/

class Sms{

    const   HEX_DIGITS = "0123456789abcdef";

    private $api_send_url;//创蓝发送短信接口URL
    private $API_VARIABLE_URL;//创蓝变量短信接口URL
    private $api_account;
    private $api_password;
    private $err;

    public function getError(){
        return $this->err;
    }

    /**
     * 参数初始化
     * @param $AppKey
     * @param $AppSecret
     * @param $RequestType [选择php请求方式，fsockopen或curl,若为curl方式，请检查php配置是否开启]
     */
    public function __construct($config){
        $this->api_account    = $config['API_ACCOUNT'];
        $this->api_password = $config['API_PWD'];
        $this->API_VARIABLE_URL = $config['API_URL'];
    }

    public function sendSMS($tel,$param,$tpl){
        $params=$tel.','.$param;
        $res = $this->sendVariableSMS($tpl,$params);
        if(false !==$res){
            try{
                $output=json_decode($res,true);
                if(isset($output['code'])  && $output['code']=='0'){
                   return true;
                }else{
                    $this->err = $output['errorMsg'];
                    return false;
                }
            }catch(Exception $e){
                $this->err='服务器发送短信失败！';
                return false;
            }
        }else{

            return false;
        }



    }

    /**
     * 发送变量短信
     *
     * @param string $msg 			短信内容
     * @param string $params 	最多不能超过1000个参数组
     */
    public function sendVariableSMS( $msg, $params) {

        //创蓝接口参数
        $postArr = array (
            'account' => $this->api_account,
            'password' =>$this->api_password,
            'msg' => $msg,
            'params' => $params,
            'report' => 'true'
        );
        $result = $this->curlPost( $this->API_VARIABLE_URL, $postArr);
        return $result;
    }


    /**
     * 通过CURL发送HTTP请求
     * @param string $url  //请求URL
     * @param array $postFields //请求参数
     * @return mixed
     */
    private function curlPost($url,$postFields){
        $postFields = json_encode($postFields);
        $ch = curl_init ();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8'
            )
        );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt( $ch, CURLOPT_TIMEOUT,1);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);
        $ret = curl_exec ( $ch );
        if (false == $ret) {
            $result = curl_error($ch);
        } else {
            $rsp = curl_getinfo( $ch, CURLINFO_HTTP_CODE);
            if (200 != $rsp) {
                $this->err='服务器发送短信失败！';
                $result = false;
            } else {
                $result = $ret;
            }
        }
        curl_close ( $ch );
        return $result;
    }

}

?>