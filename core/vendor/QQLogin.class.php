    <?php
				class QQLogin {
                    //应用的APPID
                    private $app_id = null;
                    //应用的APPKEY
                    private $app_secret = null;
                    //成功授权后的回调地址
                    private $my_url = null;

                    protected $error = null;

					public function __construct() {
                        $qqLoginConfig = C('QQ_LOGIN');
                        if(!$qqLoginConfig){
							//E('请配置QQ登录参数。');
                            $this->error='请配置QQ登录参数';
                            return false;
						}
						$this->app_id=$qqLoginConfig['APP_ID'];
                        $this->app_secret=$qqLoginConfig['APP_Key'];
                        //REDIRECT_URL
						$this->my_url=urlencode($qqLoginConfig['REDIRECT_URL']);
					}
					//获取code
					function getCode(){

                        //state参数用于防止CSRF攻击，成功授权后回调时会原样带回
                        $state = md5(uniqid(rand(), TRUE));
                        session('state',$state);
                        //拼接URL
                        $dialog_url = "https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id="
                            . $this->app_id . "&redirect_uri=" . $this->my_url. "&state="
                            .$state;
                        exit("<script> top.location.href='" . $dialog_url . "'</script>");
                    }
                    function getAccessToken($code){
//拼接URL

                        $token_url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&"
                            . "client_id=" . $this->app_id . "&redirect_uri=" . $this->my_url
                            . "&client_secret=" . $this->app_secret . "&code=" . $code;
                        $response = file_get_contents($token_url);
                        if (strpos($response, "callback") !== false)
                        {
                            $lpos = strpos($response, "(");
                            $rpos = strrpos($response, ")");
                            $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
                            $msg = json_decode($response);
                            if (isset($msg->error))
                            {
                                //E($msg->error_description,$msg->error);
                                $this->error=$msg->error_description;
                                return false;
                                /* echo "<h3>error:</h3>" . $msg->error;
                                 echo "<h3>msg  :</h3>" . $msg->error_description;
                                 exit;*/
                            }
                        }
                        //Step3：使用Access Token来获取用户的OpenID
                        $params = array();
                        parse_str($response, $params);
                            $accessToken = $params['access_token'];
                            session('qqlogin_accesstoken',$accessToken);

                        return $accessToken;
                    }
                    function getOpenId($code){
                        if(I('param.state') == session('state'))
                        {

                            $access_token = self::getAccessToken($code);
                            if($access_token===false){
                                return false;
                            }

                            $graph_url = "https://graph.qq.com/oauth2.0/me?access_token=".$access_token;
                             $str  = file_get_contents($graph_url);
                             if (strpos($str, "callback") !== false)
                             {
                                 $lpos = strpos($str, "(");
                                 $rpos = strrpos($str, ")");
                                 $str  = substr($str, $lpos + 1, $rpos - $lpos -1);
                             }
                             $user = json_decode($str);
                             if (isset($user->error))
                             {
                                 //E($user->error_description,$user->error);
                                 $this->error=$user->error_description;
                                 return false;
                                /* echo "<h3>error:</h3>" . $user->error;
                                 echo "<h3>msg  :</h3>" . $user->error_description;
                                 exit;*/
                             }
                             return $user->openid;
                          }
                        else
                        {
                            //echo("The state does not match. You may be a victim of CSRF.");
                            //E('The state does not match. You may be a victim of CSRF.');
                            $this->error='The state does not match. You may be a victim of CSRF.';
                            return false;
                        }
                    }
                    function getQQInfo($openid){
                        $accesstoken = session('qqlogin_accesstoken');
                        $graph_url = "https://graph.qq.com/user/get_user_info?access_token=".$accesstoken."&oauth_consumer_key=".$this->app_id."&openid=".$openid;
                        $str  = file_get_contents($graph_url);
                        $user = json_decode($str);
                        if($user->ret!=0){
                            $this->error=$user->msg;
                            return false;
                        }else{
                            return $user;
                        }


                    }

                    function getError(){
                        return $this->error;
                    }

				}
				?>  