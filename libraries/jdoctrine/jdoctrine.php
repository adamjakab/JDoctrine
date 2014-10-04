<?php
/**
 * @package    JDoctrine
 * @author     Adam Jakab {@link http://dev.alfazeta.com}
 * @author     Created on 19-Jun-2014
 * @license    GNU/GPL
 */
defined('_JEXEC') || die();
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\EventManager;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\FilesystemCache;

/**
 * Class JDoctrine
 */
class JDoctrine {

	/**
	 * requires Doctrine's autoloader separately so
	 * you can do stuff before calling getEntityManager
	 * It is not necessary to be called explicitely as it will be always called by getEntityManager
	 * but for instance if you need to set up the eventManager(\Doctrine\Common\EventManager) you'll need this
	 */
	public static function initAutoloader() {
		require_once "vendor/autoload.php";
	}

	/**
	 * @param \stdClass $options
	 * @return EntityManager|bool
	 */
	public static function getEntityManager($options) {
		self::initAutoloader();
		if( ($opt = self::checkOptions($options)) ) {
			switch($opt->configuration->type) {
				case "xml":
					$configuration = Setup::createXMLMetadataConfiguration($opt->configuration->paths, $opt->configuration->isDevMode);
					break;
				case "yaml":
					$configuration = Setup::createYAMLMetadataConfiguration($opt->configuration->paths, $opt->configuration->isDevMode);
					break;
				case "annotation":
				default:
					$configuration = Setup::createAnnotationMetadataConfiguration($opt->configuration->paths, $opt->configuration->isDevMode);
					break;
			}
			//setup proxy & cache
			if($opt->configuration->isDevMode === true) {
				$cache = new ArrayCache;
			} else {
				if(extension_loaded('apc')) {
					$cache = new ApcCache();
				} else {
					$cache = new FilesystemCache(JPATH_ROOT.'/cache/jDoctrine');
				}
			}
			$configuration->setMetadataCacheImpl($cache);
			$configuration->setQueryCacheImpl($cache);
			$configuration->setProxyDir(JPATH_ROOT.'/media/jDoctrine/proxy');
			$configuration->setProxyNamespace('JDoctrine\Proxies');
			//
			$em = EntityManager::create($opt->connection, $configuration, $opt->eventManager);
			//register "enum" type as "string"
			$platform = $em->getConnection()->getDatabasePlatform();
			$platform->registerDoctrineTypeMapping("enum","string");
			//
			return($em);
		} else {
			return false;
		}
	}

	/**
	 * Checks if all required options are in place / correct
	 * @param \stdClass $opt
	 * @return \stdClass|bool
	 */
	private static function checkOptions($opt) {
		$error = false;
		$nullConnectionConfig = false;//indicates if we will be using Joomla's db config values

		//CONFIGURATION
		if(!$error) {
			if(isset($opt->configuration)) {
				if (isset($opt->configuration->type) && in_array($opt->configuration->type, ["annotation","xml","yaml"])) {
					if (isset($opt->configuration->paths) && is_array($opt->configuration->paths)) {
						//todo: check configuration paths
						//...//
						if(!isset($opt->configuration->isDevMode)||$opt->configuration->isDevMode!==true) {
							$opt->configuration->isDevMode = false;
						}
						//...//
					} else {
						$error = true;//configuration metadata path is not set
					}
				} else {
					$error = true;//configuration type must be: "annotation","xml","yaml"
				}
			} else {
				$error = true;//configuration not set
			}
		}

		//CONNECTION
		if(!$error) {
			if(!(isset($opt->connection) && is_array($opt->connection))) {
				//connection not set so we will be using Joomla's connection info
				$nullConnectionConfig = true;
				$app = \JFactory::getApplication();
				$opt->connection = [];
				$opt->connection["driver"] = "";
				switch($app->get("dbtype", "")) {
					case "mysql":
						$opt->connection["driver"] = "pdo_mysql";
						break;
					case "mysqli":
						$opt->connection["driver"] = "mysqli";
						break;
					case "postgres":
						$opt->connection["driver"] = "pdo_pgsql";
						break;
					case "mssql":/*Wow!*/
						$opt->connection["driver"] = "sqlsrv";
						break;
					default:
						$error = true;//unknown database type!
				}
				$opt->connection["host"] = $app->get("host", "");
				$opt->connection["user"] = $app->get("user", "");
				$opt->connection["password"] = $app->get("password", "");
				$opt->connection["dbname"] = $app->get("db", "");
				//extra(without these I have problems)
				$opt->connection["charset"] = "utf8";
			}
			//...check...//
		}

		//EVENT MANAGER
		if(!$error) {
			//we will always set up the event manager(Doctrine\Common\EventManager)
			/** @var Doctrine\Common\EventManager eventManager */
			$opt->eventManager = (isset($opt->eventManager) && get_class($opt->eventManager)=='Doctrine\Common\EventManager')?$opt->eventManager:new EventManager;

			//adding custom loadClassMetadata listener for working with Joomla's database table prefixes
			if($nullConnectionConfig) {
				require_once "joomla/DatabaseTablePrefix.php";
				$opt->eventManager->addEventListener(\Doctrine\ORM\Events::loadClassMetadata, new \JDoctrine\DatabaseTablePrefix());
			}
			//adding custom onSchemaDropTable listener to protect Joomla's database tables (disables drop tables in SchemaTool)
			if($nullConnectionConfig) {
				require_once "joomla/DatabaseProtector.php";
				$opt->eventManager->addEventListener(\Doctrine\DBAL\Events::onSchemaDropTable, new \JDoctrine\DatabaseProtector());
			}
		}

		//
		if($error) {
			echo __CLASS__.": Invalid configuration: " . print_r($opt, true);
			return(false);
		}
		return($opt);
	}
}


