<?php
/**
 * VerySimpleDownload.
 *
 * @copyright Ralf Koester (Koester)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @package VerySimpleDownload
 * @author Ralf Koester <ralf@zikula.de>.
 * @link http://support.zikula.de
 * @link http://zikula.org
 * @version Generated by ModuleStudio 0.6.1 (http://modulestudio.de).
 */


/**
 * Category api base class.
 */
class VerySimpleDownload_Api_Base_Category extends Zikula_AbstractApi
{
    /**
     * Retrieves the main/default category of VerySimpleDownload.
     *
     * @param string $args['ot']       The object type to be treated (optional).
     * @param string $args['registry'] Name of category registry to be used (optional).
     * @deprecated Use the methods getAllProperties, getAllPropertiesWithMainCat, getMainCatForProperty and getPrimaryProperty instead.
     *
     * @return mixed Category array on success, false on failure.
     */
    public function getMainCat(array $args = array())
    {
        if (isset($args['registry'])) {
            $args['registry'] = $this->getPrimaryProperty($args);
        }
    
        $objectType = $this->determineObjectType($args, 'getMainCat');
    
        return CategoryRegistryUtil::getRegisteredModuleCategory($this->name, ucwords($objectType), $args['registry'], 32); // 32 == /__System/Modules/Global
    }
    
    /**
     * Defines whether multiple selection is enabled for a given object type
     * or not. Subclass can override this method to apply a custom behaviour
     * to certain category registries for example.
     *
     * @param string $args['ot']       The object type to be treated (optional).
     * @param string $args['registry'] Name of category registry to be used (optional).
     *
     * @return boolean true if multiple selection is allowed, else false.
     */
    public function hasMultipleSelection(array $args = array())
    {
        if (isset($args['registry'])) {
            // default to the primary registry
            $args['registry'] = $this->getPrimaryProperty($args);
        }
    
        $objectType = $this->determineObjectType($args, 'hasMultipleSelection');
    
        // we make no difference between different category registries here
        // if you need a custom behaviour you should override this method
    
        $result = false;
        switch ($objectType) {
            case 'download':
                $result = true;
                break;
        }
    
        return $result;
    }
    
    /**
     * Retrieves input data from POST for all registries.
     *
     * @param string $args['ot']     The object type to be treated (optional).
     * @param string $args['source'] Where to retrieve the data from (defaults to POST).
     *
     * @return array The fetched data indexed by the registry id.
     */
    public function retrieveCategoriesFromRequest(array $args = array())
    {
        $dataSource = $this->request->request;
        if (isset($args['source']) && $args['source'] == 'GET') {
            $dataSource = $this->request->query;
        }
    
        $catIdsPerRegistry = array();
    
        $objectType = $this->determineObjectType($args, 'retrieveCategoriesFromRequest');
        $properties = $this->getAllProperties($args);
        foreach ($properties as $propertyName => $propertyId) {
            $hasMultiSelection = $this->hasMultipleSelection(array('ot' => $objectType, 'registry' => $propertyName));
            if ($hasMultiSelection === true) {
                $argName = 'catids' . $propertyName;
                $inputValue = $dataSource->get($argName, array());
                if (!is_array($inputValue)) {
                    $inputValue = explode(',', $inputValue);
                }
            } else {
                $argName = 'catid' . $propertyName;
                $inputVal = (int) $dataSource->filter($argName, 0, FILTER_VALIDATE_INT);
                $inputValue = array();
                if ($inputVal > 0) {
                    $inputValue[] = $inputVal;
                }
            }
    
            // prevent "All" option hiding all entries
            foreach ($inputValue as $k => $v) {
                if ($v == 0) {
                    unset($inputValue[$k]);
                }
            }
    
            $catIdsPerRegistry[$propertyName] = $inputValue;
        }
    
        return $catIdsPerRegistry;
    }
    
    /**
     * Adds a list of where clauses for a certain list of categories to a given query builder.
     *
     * @param Doctrine\ORM\QueryBuilder $args['qb']     Query builder instance to be enhanced.
     * @param string                    $args['ot']     The object type to be treated (optional).
     * @param string                    $args['catids'] Category ids grouped by property name.
     *
     * @return Doctrine\ORM\QueryBuilder The enriched query builder instance.
     */
    public function buildFilterClauses(array $args = array())
    {
        $qb = $args['qb'];
    
        $properties = $this->getAllProperties($args);
        $catIds = $args['catids'];
    
        $filtersPerRegistry = array();
        $filterParameters = array('values' => array(), 'registries' => array());
    
        foreach ($properties as $propertyName => $propertyId) {
            if (!isset($catIds[$propertyName]) || !is_array($catIds[$propertyName]) || !count($catIds[$propertyName])) {
                continue;
            }
    
            $filterParameters['values'][$propertyName] = $catIds[$propertyName];
            $filterParameters['registries'][$propertyName] = $propertyId;
            $filtersPerRegistry[] = '(tblCategories.category IN (:propName' . $propertyName . ') AND tblCategories.categoryRegistryId = :propId' . $propertyName . ')';
        }
    
        if (count($filtersPerRegistry) > 0) {
            if (count($filtersPerRegistry) == 1) {
                $qb->andWhere($filtersPerRegistry[0]);
            } else {
    
                $qb->andWhere($qb->expr()->orX()->addMultiple($filtersPerRegistry));
            }
            foreach ($filterParameters['values'] as $propertyName => $filterValue) {
                $qb->setParameter('propName' . $propertyName, $filterValue)
                   ->setParameter('propId' . $propertyName, $filterParameters['registries'][$propertyName]);
            }
        }
    
        return $qb;
    }
    
    /**
     * Returns a list of all registries / properties for a given object type.
     *
     * @param string $args['ot'] The object type to retrieve (optional).
     *
     * @return array list of the registries (property name as key, id as value).
     */
    public function getAllProperties(array $args = array())
    {
        $objectType = $this->determineObjectType($args, 'getAllProperties');
    
        $propertyIdsPerName = CategoryRegistryUtil::getRegisteredModuleCategoriesIds($this->name, ucwords($objectType));
    
        return $propertyIdsPerName;
    }
    
    /**
     * Returns a list of all registries with main category for a given object type.
     *
     * @param string $args['ot']       The object type to retrieve (optional)
     * @param string $args['arraykey'] Key for the result array (optional)
     *
     * @return array list of the registries (registry id as key, main category id as value).
     */
    public function getAllPropertiesWithMainCat(array $args = array())
    {
        $objectType = $this->determineObjectType($args, 'getAllPropertiesWithMainCat');
    
        if (!isset($args['arraykey'])) {
            $args['arraykey'] = '';
        }
    
        $registryInfo = CategoryRegistryUtil::getRegisteredModuleCategories($this->name, ucwords($objectType), $args['arraykey']);
    
        return $registryInfo;
    }
    
    /**
     * Returns the main category id for a given object type and a certain property name.
     *
     * @param string $args['ot']       The object type to retrieve (optional)
     * @param string $args['property'] The property name (optional)
     *
     * @return integer The main category id of desired tree.
     */
    public function getMainCatForProperty(array $args = array())
    {
        $objectType = $this->determineObjectType($args, 'getMainCatForProperty');
    
        $catId = CategoryRegistryUtil::getRegisteredModuleCategory($this->name, ucwords($objectType), $args['property']);
    
        return $catId;
    }
    
    /**
     * Returns the name of the primary registry.
     *
     * @param string $args['ot'] The object type to retrieve (optional)
     *
     * @return string name of the main registry.
     */
    public function getPrimaryProperty(array $args = array())
    {
        $objectType = $this->determineObjectType($args, 'getPrimaryProperty');
    
        $registry = 'Main';
    
        return $registry;
    }
    
    /**
     * Determine object type using controller util methods.
     *
     * @param string $args['ot'] The object type to retrieve (optional)
     * @param string $methodName Name of calling method
     *
     * @return string name of the determined object type
     */
    protected function determineObjectType(array $args = array(), $methodName = '')
    {
        $objectType = isset($args['ot']) ? $args['ot'] : '';
        $controllerHelper = new VerySimpleDownload_Util_Controller($this->serviceManager);
        $utilArgs = array('api' => 'category', 'action' => $methodName);
        if (!in_array($objectType, $controllerHelper->getObjectTypes('api', $utilArgs))) {
            $objectType = $controllerHelper->getDefaultObjectType('api', $utilArgs);
        }
    
        return $objectType;
    }
}