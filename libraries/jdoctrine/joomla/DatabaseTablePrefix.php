<?php
namespace JDoctrine;
/**
 * @package    JDoctrine
 * @author     Adam Jakab {@link http://dev.alfazeta.com}
 * @author     Created on 19-Jun-2014
 * @license    GNU/GPL
 */
defined('_JEXEC') || die();
use \Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use \Doctrine\ORM\Mapping\ClassMetadataInfo;
/**
 * Purpose: This class is added to Doctrine's EventManager as an event listener(loadClassMetadata)
 *  so that the entire Doctrine application will transparently use Joomla's $dbprefix JConfig value
 *  when constructing queries.
 *  Adapted by recepie: http://docs.doctrine-project.org/en/2.1/cookbook/sql-table-prefixes.html
 *
 * Class DatabaseTablePrefix
 * @package JDoctrine
 */
class DatabaseTablePrefix {
	/** @var string */
	protected $prefix = "";

	/**
	 * Pick up Joomla's database table prefix
	 */
	public function __construct() {
		$this->prefix = \JFactory::getApplication()->get("dbprefix", "");
	}

	/**
	 * Use the prefix
	 * @param LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs) {
		/** @var ClassMetadataInfo $classMetadata */
		$classMetadata = $eventArgs->getClassMetadata();
		//add prefix to table name
		$classMetadata->setPrimaryTable(["name" => $this->prefix . $classMetadata->getTableName()]);
		//add prefix to joined tables
		foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
			if ($mapping['type'] == ClassMetadataInfo::MANY_TO_MANY) {
				$mappedTableName = $classMetadata->associationMappings[$fieldName]['joinTable']['name'];
				$classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->prefix . $mappedTableName;
			}
		}
	}
}
