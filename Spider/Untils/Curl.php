<?php
	namespace Spider\Untils;
	class Curl{
		private static $options = array(
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_CONNECTTIMEOUT => 15,
			CURLOPT_TIMEOUT => 20,				//超时
			CURLOPT_RETURNTRANSFER => 1,		//获取内容不输出
			CURLOPT_HEADER => false,			//设定不包含头
			CURLOPT_USERAGENT => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.89 Safari/537.36",
			CURLOPT_ENCODING => "gzip",
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_MAXREDIRS => 3
		);
		
		//curl需要携带cookies	path
		public static function getCookieFile($CookieFileContent){
			self::$options[CURLOPT_COOKIE] = $CookieFileContent;
		}
		
		//网站来源
		public static function setReferer($url){
			self::$options[CURLOPT_REFERER]  = $url;
		}
		
		
		public static function run($url,$type='get',$fields=array()){
			$ch = curl_init();
			if(!empty($fields) && $type=='post')
				$url = $url.'?'.http_build_query($fields);
			
			self::$options[CURLOPT_URL]  = $url;
			curl_setopt_array($ch,self::$options);
			
			if($type=='post'){
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
				curl_setopt($ch, CURLOPT_POST, 1);
			}
			
			$return = curl_exec($ch);
			
			curl_close($ch);
			usleep(100000);				///休眠0.1
			return $return;
		}
	}