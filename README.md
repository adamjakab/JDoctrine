# JDoctrine - Doctrine library for Joomla 3

This library was created specifically for Joomla 3.3+ to give the possibility to use Repositories and Entities in J! projects.

The included Doctrine library has not been in any way touched - it is the original doctrine/orm library installed with composer.

## Usage

To get the EntityManager do something like this:

    require_once JPATH_ROOT."/libraries/jdoctrine/jdoctrine.php";
    $JDO = new \stdClass();
    $JDO->configuration = new \stdClass();
    $JDO->configuration->type = "annotation";
    $JDO->configuration->paths = [JPATH_ROOT."/path/to/my/com_component/Entities"];
    $JDO->configuration->isDevMode = true;
    $JDO->connection = null;
    $JDO->eventManager = null;
    $em = JDoctrine::getEntityManager($JDO);

This will give you an instance of the EntityManager configured with:

 * Joomla's default database configuration (`$JDO->connection = null;`)
 * Entities in folder(`JPATH_ROOT."/path/to/my/com_component/Entities"]`) - note that `$JDO->configuration->paths` is an array and you can pass multiple locations
 * The entities will use the "annotation" metadata configuration(`$JDO->configuration->type = "annotation";`). Obviously you can also use: "xml" or "yaml".
 * The EntityManager is in dev mode(`$JDO->configuration->isDevMode = true;`) so no caching will be done. Set this to true to do caching.

The JDoctrine class has been set up in such a way that you can obtain more than one instance of the EntityManager, each configured with its own details. This can be useful if you want to set up a second EntityManager that connects to a different database. To do that you'd do something like this:

    $JDO = new \stdClass();
    $JDO->configuration = new \stdClass();
    $JDO->configuration->type = "annotation";
    $JDO->configuration->paths = [JPATH_ROOT."/path/to/my/com_component/ExternalEntities"];
    $JDO->configuration->isDevMode = true;
    $JDO->connection = [
        "driver" => "pdo_mysql", /*or: (mysqli|pdo_pgsql|sqlsrv)*/
        "host" => "127.0.0.1", /*ip or fqdn*/
        "user" => "",
        "password" => "",
        "dbname" => "",
        "charset" => "utf8" /*sometimes it is good to specify*/
    ];
    $JDO->eventManager = null;
    $em2 = JDoctrine::getEntityManager($JDO);

## Changelog

### v1.2

 * Activated caching for non-dev mode: ApcCache if available, FilesystemCache otherwise
 * Activated Proxy caching in media/jDoctrine/proxy folder
 * Added protection for Joomla tables so that SchemaTool doesn't drop them

### v1.1

 * Zero-config connection info will use Joomla's default database configuration for EntityManager
 * Database tables for entities are now prefixed with Joomla's database prefix
