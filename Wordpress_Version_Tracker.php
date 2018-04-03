<?php
	/**
	 * Created by PhpStorm.
	 * User: puggan
	 * Date: 2018-04-03
	 * Time: 10:05
	 */

	namespace SpiroAB;

	/**
	 * Class Wordpress_Version_Tracker
	 *
	 * @package SpiroAB
	 *
	 * @property Wpvt_Container[] plugins
	 *
	 */
	class Wordpress_Version_Tracker
	{
		public $plugins = [];

		/**
		 * Wordpress_Version_Tracker constructor.
		 *
		 * Add action-listner for init
		 */
		public function __construct()
		{
			add_action('init', [$this, 'init']);
		}

		/**
		 * Add action-listners, filters, and load plugin-info
		 *
		 * @return void
		 */
		public function init()
		{
			add_filter('pre_set_site_transient_update_plugins', [$this, 'update']);
			add_filter('plugins_api', [$this, 'info'], 10, 3);
			$this->load();
		}

		/**
		 * Load info from other plugins
		 *
		 * @return void
		 */
		public function load()
		{
			foreach(glob(WP_PLUGIN_DIR . "/*/version.json") as $filename)
			{
				if(!is_file($filename)) continue;
				if(!is_readable($filename)) continue;

				$data = file_get_contents($filename);
				if(empty($data)) continue;

				/** @var null|Phpdoc_Wpvt_Row $json */
				$json = json_decode($data);

				if(!is_object($json)) continue;

				if(empty($json->slug)) continue;

				$this->plugins[$json->slug] = new Wpvt_Container($json);
			}
		}

		/**
		 * Fetch info from remote location
		 *
		 * @param string $slug
		 *
		 * @return bool|null|Phpdoc_Wpvt_Row
		 */
		public function fetch($slug)
		{
			if(empty($this->plugins[$slug]))
			{
				return NULL;
			}

			if($this->plugins[$slug]->remote)
			{
				return $this->plugins[$slug]->remote;
			}

			if(empty($this->plugins[$slug]->local->latest_json))
			{
				return NULL;
			}

			$remote_json_raw = file_get_contents($this->plugins[$slug]->local->latest_json);

			if(empty($remote_json_raw))
			{
				return FALSE;
			}

			$this->plugins[$slug]->remote = json_decode($remote_json_raw);
			return $this->plugins[$slug]->remote;
		}

		/**
		 * Append wordpress plugin version update information
		 *
		 * @param Phpdoc_Wpvt_transient $transient
		 *
		 * @return Phpdoc_Wpvt_transient
		 */
		public function update($transient)
		{
			if (empty($transient->checked)) {
				return $transient;
			}

			foreach(array_keys($this->plugins) as $slug)
			{
				$wp_slug = "{$slug}/{$slug}.php";
				$info = $this->update_info($slug);
				if(empty($info->new_version))
				{
					$transient->no_update[$wp_slug] = $info;
					continue;
				}

				if($info->new_version === $info->version)
				{
					$transient->no_update[$wp_slug] = $info;
					continue;
				}

				$transient->response[$wp_slug] = $info;
			}

			return $transient;
		}

		/**
		 * @param bool|Phpdoc_Wpvt_Row $false
		 * @param string $action
		 * @param object $arg TODO
		 *
		 * @return bool|Phpdoc_Wpvt_Row
		 */
		public function info($false, $action, $arg)
		{
			if(empty($this->plugins[$arg->slug])) {
				return $false;
			}

			return $this->update_info($arg->slug);
		}

		/**
		 * @param $slug
		 *
		 * @param string $slug
		 *
		 * @return bool|Phpdoc_Wpvt_Row
		 */
		public function update_info($slug)
		{
			if(empty($this->plugins[$slug])) {
				return FALSE;
			}

			if(empty($this->plugins[$slug]->local->new_version))
			{
				if(!$this->plugins[$slug]->remote)
				{
					$this->fetch($slug);
				}
				if(isset($this->plugins[$slug]->remote->version))
				{
					$this->plugins[$slug]->local->new_version = $this->plugins[$slug]->remote->version;
				}
			}

			return $this->plugins[$slug]->local;
		}
	}

	/**
	 * Class Wpvt_Container
	 * @package SpiroAB
	 *
	 * @property Phpdoc_Wpvt_Row $local
	 * @property Phpdoc_Wpvt_Row $remote
	 */
	class Wpvt_Container {
		public $local;
		public $remote;

		/**
		 * Wpvt_Container constructor.
		 *
		 * @param Phpdoc_Wpvt_Row $local
		 * @param Phpdoc_Wpvt_Row $remote
		 */
		public function __construct($local = NULL, $remote = NULL)
		{
			$this->local = $local;
			$this->remote = $remote;
		}
	}

	/**
	 * Class Phpdoc_Wpvt_Row
	 * @package SpiroAB
	 *
	 * @property string $slug
	 * @property string $version
	 * @property string $new_version
	 * @property string $latest_json
	 */
	class Phpdoc_Wpvt_Row {
		public $slug;
		public $version;
		public $new_version;
		public $latest_json;
	}

	/**
	 * Class Phpdoc_Wpvt_transient
	 * @package SpiroAB
	 *
	 * @property int $last_checked
	 * @property string[] $checked
	 * @property Phpdoc_Wpvt_Row[] $response
	 * @property mixed[] $translations
	 * @property Phpdoc_Wpvt_Row[] $no_update
	 */
	class Phpdoc_Wpvt_transient
	{
		public $last_checked;
		public $checked;
		public $response;
		public $translations;
		public $no_update;
	}