<?php
	//队列获取方式	刷新list_uptime
	function get_user_queue($key = 'list',$key2 = 'new', $count = 2000,$id = 0)
	{
		if (!\Spider\Cache\Cache::get_instance()->qsize($key)) 
		{
			while(\Spider\Cache\Cache::get_instance()->qsize($key2)){
				del_queue($key2);
			}
			
			//if($id!=0){
			//	while(!\Spider\Cache\Cache::get_instance()->qsize($key)){
			//		sleep(10);
			//	}
			//	return \Spider\Cache\Cache::get_instance()->qpop($key);
			//}
			
			$id = $id*$count;
			$sql = "Select `username`, `list_uptime` From `user` Order By `list_uptime` Asc Limit {$id},{$count}";
			//error_log($sql.PHP_EOL,3,'./sql.log');
			$rows = \Spider\Cache\Db::get_all($sql);
			foreach ($rows as $row) 
			{
				\Spider\Cache\Cache::get_instance()->qpush($key, $row['username']);
			}
		}
		return \Spider\Cache\Cache::get_instance()->qpop($key);
	}
	
	function del_queue($key){
		//test
		$content = array();
		while(count($content)!=100 && \Spider\Cache\Cache::get_instance()->qsize($key)){
			 $data = json_decode(\Spider\Cache\Cache::get_instance()->qpop($key),true);
			 $data['addtime'] = time();
			 $data['info_uptime'] = time();
			 $content[] = $data;
		}
		
		if(!empty($content)){
			$res = \Spider\Cache\Db::insert_batch('user',$content);
			if($res === FALSE)
				loger::log('添加用户失败:'.$username);
		}
	}
