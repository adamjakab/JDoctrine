<?php
namespace JDoctrine;
/**
 * @package    JDoctrine
 * @author     Adam Jakab {@link http://dev.alfazeta.com}
 * @author     Created on 19-Jun-2014
 * @license    GNU/GPL
 */
defined('_JEXEC') || die();
use \Doctrine\DBAL\Event\SchemaDropTableEventArgs;
/**
 * Purpose: This class is added to Doctrine's EventManager as an event listener(onSchemaDropTable)
 *  so to prevent any database table to be dropped.
 * When calling Doctrine\ORM\Tools\SchemaTool::updateSchema, it in turn will eventually call
 * Doctrine\DBAL\Platforms\AbstractPlatform::getDropTableSQL - This method, before returning a
 * "DROP TABLE ..." sql will call any listeners listening to Doctrine\DBAL\Events::onSchemaDropTable.
 * Here we are substituting the sql with some innoquous sql so to prevent Joomla (any) tables
 * to be dropped
 *
 * Class DatabaseProtector
 * @package JDoctrine
 */
class DatabaseProtector {
	/**
	 * @param SchemaDropTableEventArgs $eventArgs
	 */
	public function onSchemaDropTable(SchemaDropTableEventArgs $eventArgs) {
		$tbl = $eventArgs->getTable();
		$eventArgs->preventDefault();
		$eventArgs->setSql('DO "NOTHING FOR TABLE '.$tbl->getName().'";');
	}
}
