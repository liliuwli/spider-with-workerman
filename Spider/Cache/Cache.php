<?php
	namespace Spider\Cache;
	class Cache{
		/**
		 * 实例数组		ssdb
		 * @var array
		 */
		protected static $_instance;
		protected static $config = array(

		);

		/**
		 * 获取实例
		 * @param string $config_name
		 * @throws Exception
		 */
		public static function get_instance()
		{
			if(!isset(self::$_instance))
			{
				if(extension_loaded('ssdb'))
				{
					self::$_instance = new \SSDB();
				}
				else
				{
					sleep(2);
					exit("extension redis is not installed\n");
				}
				self::$_instance->connect(self::$config['host'],self::$config['port']);
			}
			return self::$_instance;
		}
	}