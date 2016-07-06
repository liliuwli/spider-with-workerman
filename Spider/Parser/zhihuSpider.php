<?php
	namespace Spider\Parser;
	class zhihuSpider{
		public $DriverName = 'zhihu';
		//运行  并且扩充队列
		public function run($get = TRUE,$id){
			$username = get_user_queue($this->DriverName.'list',$this->DriverName.'new',2000,$id);
			$cookie = '__utma=51854390.1401925293.1459682563.1459862292.1459939116.9;__utmb=51854390.9.9.1459940599678;__utmc=51854390;__utmt=1;__utmv=51854390.100-2|2=registration_date=20160403=1^3=entry_date=20160403=1;__utmz=51854390.1459852511.7.4.utmcsr=zhihu.com|utmccn=(referral)|utmcmd=referral|utmcct=/;_xsrf=92ce85d6ae08cce1ffc70ef5ee469b32;_za=19066790-77b5-40d9-83e9-bab27a084bda;cap_id="MDkxNjZmNDU1N2NmNGM3NWI5NjA5NmExZDBhYzdhMGI=|1459940690|8dab3d1dcb5f5cc079adefa25e31766883b62c0f";d_c0="ADDA6sEKtwmPTjef39p4D8r-oXNGAscOqH0=|1459682564";l_cap_id="MDJlNjRiMDkyOGJlNDA4NzkwZjk2MDk4NWRhOTFiZWQ=|1459940690|f32b47bdf36f602c221c0b5a2973a0220685f922";login="NTNhNTRhNjAxZjgyNDU1OGE0YjZlZDY3YmUwMmYzZjc=|1459940719|549581bc8a0df22081bf00f794d0514d9cbcd381";n_c=1;q_c1=0f70ef23c5a9477aaaeb4b3ae2a31c86|1459682563000|1459682563000;unlock_ticket="QUJBQTNSc2Z0d2tYQUFBQVlRSlZUWGowQkZmZUlFcWd1LU5mcFFVc2Vlbk1RWlFjSTlTemtnPT0=|1459940719|d7c398788074e1adadc100face78714cbcc7c0be";z_c0="QUJBQTNSc2Z0d2tYQUFBQVlRSlZUWEI2TEZlSnI3elF4c3FzUTVkRl9WUVQtRTA5ck5pUnhnPT0=|1459940719|2991e16b7beb33e6a13f7b644721b46be4f49741";';
			
			$userInfo = $this->getUserInfo($cookie,$username);
			
			$flag = \Spider\Cache\Db::update('user',$userInfo,"`username`='{$username}'",false);
			//error_log($flag.PHP_EOL,3,'./error.log');
//echo $flag;
			if($flag !== FALSE)
				\Spider\Cache\Loger::log('已更新用户信息:'.$username);
			//if($userInfo['followees'] > 100 || $userInfo['followers']>100)
			$this->addList($cookie,$username);
			
			//if($get)
			//	$this->addList($cookie,$username);
		}
		
		private function getUserInfo($cookie,$username){
			\Spider\Untils\Curl::getCookieFile($cookie);
			\Spider\Untils\Curl::setReferer('http://www.zhihu.com');
			$url = "http://www.zhihu.com/people/{$username}/";
			$time = time();
			\Spider\Cache\Loger::log('开始采集用户信息');
			$res = \Spider\Untils\Curl::run($url);
			\Spider\Cache\Loger::log('已采集用户信息'.(time()-$time).'秒');
			$userInfo = $this->get_user_about($res);
			$userInfo['username']	 	= $username;
			$userInfo['list_uptime'] 	= time();
			$userInfo['last_message_week'] = empty($data['last_message_time']) ? 7 : intval(date("w"));
			$userInfo['last_message_hour'] = empty($data['last_message_time']) ? 24 : intval(date("H"));
			return $userInfo;
		}
		
		private function getUserlist($cookie,$username,$user_type = 'followees'){
			$url = "http://www.zhihu.com/people/{$username}/{$user_type}";
			\Spider\Untils\Curl::getCookieFile($cookie);
			\Spider\Untils\Curl::setReferer('http://www.zhihu.com');
			\Spider\Cache\Loger::log('开始采集新用户信息');
			$content = \Spider\Untils\Curl::run($url);
			
			preg_match_all('#<h2 class="zm-list-content-title"><a data-tip=".*?" href="https://www.zhihu.com/people/(.*?)" class="zg-link" title=".*?">(.*?)</a></h2>#', $content, $out);
			$count = count($out[1]);
			\Spider\Cache\Loger::log('采集新用户信息数：'.$count);
			for ($i = 0; $i < $count; $i++) 
			{
				$d_username = empty($out[1][$i]) ? '' : $out[1][$i]; 
				$d_nickname = empty($out[2][$i]) ? '' : $out[2][$i]; 
				if (!empty($d_username) && !empty($d_nickname)) 
				{
					$users[$d_username] = array(
						'username'=>$d_username,
						'parent_username'=>$username,
					);
				}
			}
			
			$keyword = $user_type == 'followees' ? '关注了' : '关注者';	
			
			preg_match('#<span class="zg-gray-normal">'.$keyword.'</span><br />\s<strong>(.*?)</strong><label> 人</label>#i', $content, $out);
			$user_count = empty($out[1]) ? 0 : intval($out[1]);

			preg_match('#<input type="hidden" name="_xsrf" value="(.*?)"/>#i', $content, $out);
			$_xsrf = empty($out[1]) ? '' : trim($out[1]);

			preg_match('#<div class="zh-general-list clearfix" data-init="(.*?)">#i', $content, $out);
			$url_params = empty($out[1]) ? '' : json_decode(html_entity_decode($out[1]), true);
			
			if($user_count>20){
				$users = $this->getAjaxContent($user_count,$_xsrf,$url_params,$username,$user_type,$cookie,$keyword);
			}elseif($user_count===0){
				$users = array();
			}
			return $users;
		}
		
		
		
		private function get_user_about($content)
		{
			$data = array();

			if (empty($content)) 
			{
				return $data;
			}

			// 一句话介绍
			preg_match('#<span class="bio" title=["|\'](.*?)["|\']>#i', $content, $out);
			$data['headline'] = empty($out[1]) ? '' : $out[1];

			// 头像
			preg_match('#<img\s*class="avatar avatar--l"\s*src="(.*?)"\s*srcset=".*?"\s*alt=".*?"\s*/>#i', $content, $out);
			$data['headimg'] = empty($out[1]) ? '' : $out[1];

			// 居住地
			preg_match('#<span class="location item" title=["|\'](.*?)["|\']>#i', $content, $out);
			$data['location'] = empty($out[1]) ? '' : $out[1];

			// 所在行业
			preg_match('#<span class="business item" title=["|\'](.*?)["|\']>#i', $content, $out);
			$data['business'] = empty($out[1]) ? '' : $out[1];

			// 性别
			preg_match('#<span class="item gender" ><i class="icon icon-profile-(.*?)"></i></span>#i', $content, $out);
			$gender = empty($out[1]) ? 'other' : $out[1];
			if ($gender == 'female') 
				$data['gender'] = 0;
			elseif ($gender == 'male') 
				$data['gender'] = 1;
			else
				$data['gender'] = 2;

			// 公司或组织名称
			preg_match('#<span class="employment item" title=["|\'](.*?)["|\']>#i', $content, $out);
			$data['employment'] = empty($out[1]) ? '' : $out[1];

			// 职位
			preg_match('#<span class="position item" title=["|\'](.*?)["|\']>#i', $content, $out);
			$data['position'] = empty($out[1]) ? '' : $out[1];

			// 学校或教育机构名
			preg_match('#<span class="education item" title=["|\'](.*?)["|\']>#i', $content, $out);
			$data['education'] = empty($out[1]) ? '' : $out[1];

			// 专业方向
			preg_match('#<span class="education-extra item" title=["|\'](.*?)["|\']>#i', $content, $out);
			$data['education_extra'] = empty($out[1]) ? '' : $out[1];

			// 新浪微博
			preg_match('#<a class="zm-profile-header-user-weibo" target="_blank" href="(.*?)"#i', $content, $out);
			$data['weibo'] = empty($out[1]) ? '' : $out[1];

			// 个人简介
			preg_match('#<span class="content">\s(.*?)\s</span>#s', $content, $out);
			$data['description'] = empty($out[1]) ? '' : trim(strip_tags($out[1]));

			// 关注了、关注者
			preg_match('#<span class="zg-gray-normal">关注了</span><br />\s<strong>(.*?)</strong><label> 人</label>#i', $content, $out);
			$data['followees'] = empty($out[1]) ? 0 : intval($out[1]);
			preg_match('#<span class="zg-gray-normal">关注者</span><br />\s<strong>(.*?)</strong><label> 人</label>#i', $content, $out);
			$data['followers'] = empty($out[1]) ? 0 : intval($out[1]);

			// 关注专栏
			preg_match('#<strong>(.*?) 个专栏</strong>#i', $content, $out);
			$data['followed'] = empty($out[1]) ? 0 : intval($out[1]);

			// 关注话题
			preg_match('#<strong>(.*?) 个话题</strong>#i', $content, $out);
			$data['topics'] = empty($out[1]) ? 0 : intval($out[1]);

			// 关注专栏
			preg_match('#个人主页被 <strong>(.*?)</strong> 人浏览#i', $content, $out);
			$data['pv'] = empty($out[1]) ? 0 : intval($out[1]);

			// 提问、回答、专栏文章、收藏、公共编辑
			preg_match('#提问\s<span class="num">(.*?)</span>#i', $content, $out);
			$data['asks'] = empty($out[1]) ? 0 : intval($out[1]);
			preg_match('#回答\s<span class="num">(.*?)</span>#i', $content, $out);
			$data['answers'] = empty($out[1]) ? 0 : intval($out[1]);
			preg_match('#专栏文章\s<span class="num">(.*?)</span>#i', $content, $out);
			$data['posts'] = empty($out[1]) ? 0 : intval($out[1]);
			preg_match('#收藏\s<span class="num">(.*?)</span>#i', $content, $out);
			$data['collections'] = empty($out[1]) ? 0 : intval($out[1]);
			preg_match('#公共编辑\s<span class="num">(.*?)</span>#i', $content, $out);
			$data['logs'] = empty($out[1]) ? 0 : intval($out[1]);

			// 赞同、感谢、收藏、分享
			preg_match('#<strong>(.*?)</strong> 赞同#i', $content, $out);
			$data['votes'] = empty($out[1]) ? 0 : intval($out[1]);
			preg_match('#<strong>(.*?)</strong> 感谢#i', $content, $out);
			$data['thanks'] = empty($out[1]) ? 0 : intval($out[1]);
			preg_match('#<strong>(.*?)</strong> 收藏#i', $content, $out);
			$data['favs'] = empty($out[1]) ? 0 : intval($out[1]);
			preg_match('#<strong>(.*?)</strong> 分享#i', $content, $out);
			$data['shares'] = empty($out[1]) ? 0 : intval($out[1]);
			
			 // 从用户主页获取用户最后一条动态信息
			preg_match('#<div class="zm-profile-section-item zm-item clearfix" data-time="(.*?)"#i', $content, $out);
			$data['last_message_time'] = empty($out[1]) ? 0 : intval($out[1]);
			preg_match('#<div class="zm-profile-section-main zm-profile-section-activity-main zm-profile-activity-page-item-main">(.*?)</div>#s', $content, $out);
			$data['last_message'] = empty($out[1]) ? 0 : trim(str_replace("\n", " ", strip_tags($out[1])));
			
			return $data;
		}
		
		private function addList($cookie,$username){
			if(!\Spider\Cache\Cache::get_instance()->setnx($this->DriverName.$username,1))
				return ;

			$Userlist = $this->getUserlist($cookie,$username,'followees');
			foreach($Userlist as $v){
				$str = json_encode($v);
				if($res = \Spider\Cache\Cache::get_instance()->setnx($this->DriverName.$v['username'].'Unique',1))
					\Spider\Cache\Cache::get_instance()->qpush($this->DriverName.'new',$str);
			}
			
			//加入队列信息
			$Userlist = $this->getUserlist($cookie,$username,'followers');
			foreach($Userlist as $v){
				$str = json_encode($v);
				if($res = \Spider\Cache\Cache::get_instance()->setnx($this->DriverName.$v['username'].'Unique',1))
					\Spider\Cache\Cache::get_instance()->qpush($this->DriverName.'new',$str);
			}
		}
		
		
		private function getAjaxContent($user_count,$_xsrf,$url_params,$username,$user_type,$cookie,$keyword){
			if (!empty($_xsrf) && !empty($url_params) && is_array($url_params)){
				$url = "https://www.zhihu.com/node/" . $url_params['nodename'];
				$params = $url_params['params'];
				$j = 0;
				for ($i = 0; $i < $user_count; $i=$i+20) 
				{
					$params['offset'] = $i;
					$post_data = array(
						'method'=>'next',
						'params'=>json_encode($params),
						'_xsrf'=>$_xsrf,
					);
					\Spider\Untils\Curl::getCookieFile($cookie);
					\Spider\Untils\Curl::setReferer('http://www.zhihu.com');
					$content = \Spider\Untils\Curl::run($url,'post',$post_data);
					$j++;//显示变化的页面错误
					if (empty($content)) 
					{
						\Spider\Cache\Loger::log("采集用户 --- " . $username . " --- {$keyword} --- 第{$j}页 --- 失败\n");
						continue;
					}
					$rows = json_decode($content, true);
					if (empty($rows['msg']) || !is_array($rows['msg'])) 
					{
						\Spider\Cache\Loger::log("采集用户 --- " . $username . " --- {$keyword} --- 第{$j}页 --- 失败\n");
						continue;
					}
					\Spider\Cache\Loger::log("采集用户 --- " . $username . " --- {$keyword} --- 第{$j}页 --- 成功\n");

					foreach ($rows['msg'] as $row) 
					{
						preg_match_all('#<h2 class="zm-list-content-title"><a data-tip=".*?" href="https://www.zhihu.com/people/(.*?)" class="zg-link" title=".*?">(.*?)</a></h2>#', $row, $out);
						$d_username = empty($out[1][0]) ? '' : $out[1][0]; 
						$d_nickname = empty($out[2][0]) ? '' : $out[2][0]; 
						if (!empty($d_username) && !empty($d_nickname)) 
						{
							$users[$d_username] = array(
								'username'=>$d_username,
								'parent_username'=>$username,
							);
						}
					}
					
					if($j===500)						///知乎只允许  关联500页抓取
						break;
				}
			}
			return $users;
		}
	}
