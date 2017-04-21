<?php
/**
 * This file is part of Code Generator for Magento.
 * (c) 2017. Rostyslav Tymoshenko <krifollk@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Krifollk\CodeGenerator\Model\Command;

use Krifollk\CodeGenerator\Model\Generator\Crud\Config\Adminhtml\Routes;
use Krifollk\CodeGenerator\Model\Generator\Crud\Config\Di;
use Krifollk\CodeGenerator\Model\Generator\Crud\Controller\Adminhtml\Edit as EditAction;
use Krifollk\CodeGenerator\Model\Generator\Crud\Controller\Adminhtml\NewAction;
use Krifollk\CodeGenerator\Model\Generator\Crud\Controller\Adminhtml\Save;
use Krifollk\CodeGenerator\Model\Generator\Crud\Grid\Collection;
use Krifollk\CodeGenerator\Model\Generator\Crud\Layout\Edit;
use Krifollk\CodeGenerator\Model\Generator\Crud\Layout\Index;
use Krifollk\CodeGenerator\Model\Generator\Crud\Layout\IndexFactory;
use Krifollk\CodeGenerator\Model\Generator\Crud\Layout\NewLayout;
use Krifollk\CodeGenerator\Model\Generator\Crud\Model\DataProvider;
use Krifollk\CodeGenerator\Model\Generator\Crud\UiComponent\FormFactory;
use Krifollk\CodeGenerator\Model\Generator\Crud\UiComponent\Listing\Column\EntityActions;
use Krifollk\CodeGenerator\Model\Generator\Crud\UiComponent\ListingFactory;
use Krifollk\CodeGenerator\Model\Generator\Triad\CollectionPartFactory;
use Krifollk\CodeGenerator\Model\Generator\Triad\InterfacePartFactory;
use Krifollk\CodeGenerator\Model\Generator\Triad\ModelFactory;
use Krifollk\CodeGenerator\Model\Generator\Triad\Repository\RepositoryInterfacePartFactory;
use Krifollk\CodeGenerator\Model\Generator\Triad\Repository\RepositoryPartFactory;
use Krifollk\CodeGenerator\Model\Generator\Triad\ResourcePartFactory;
use Krifollk\CodeGenerator\Model\TableInfoFactory;

/**
 * Class Crud
 *
 * @package Krifollk\CodeGenerator\Model\Command
 */
class Crud extends Triad
{
    /** @var ListingFactory */
    private $listingFactory;

    /** @var IndexFactory */
    private $indexFactory;

    /** @var FormFactory */
    private $formFactory;
    /**
     * @var TableInfoFactory
     */
    private $tableInfoFactory;

    public function __construct(
        ModelFactory $modelFactory,
        InterfacePartFactory $interfacePartFactory,
        ResourcePartFactory $resourcePartFactory,
        CollectionPartFactory $collectionPartFactory,
        RepositoryInterfacePartFactory $repositoryInterfacePartFactory,
        RepositoryPartFactory $repositoryPart,
        ListingFactory $listingFactory,
        IndexFactory $indexFactory,
        FormFactory $formFactory,
        \Magento\Framework\Filesystem\Driver\File $file,
        TableInfoFactory $tableInfoFactory
    ) {
        parent::__construct(
            $modelFactory,
            $interfacePartFactory,
            $resourcePartFactory,
            $collectionPartFactory,
            $repositoryInterfacePartFactory,
            $repositoryPart,
            $file
        );
        $this->listingFactory = $listingFactory;
        $this->indexFactory = $indexFactory;
        $this->formFactory = $formFactory;
        $this->tableInfoFactory = $tableInfoFactory;
    }

    /**
     * @inheritdoc
     */
    protected function prepareEntities($moduleName, $tableName, $entityName)
    {
        $entities = parent::prepareEntities($moduleName, $tableName, $entityName);
        $idFieldName = $this->tableInfoFactory->create(['tableName' => $tableName])->getIdFieldName();
        $entities['entity_actions'] = (new EntityActions())->generate(
            [
                'moduleName' => $moduleName,
                'entityName' => $entityName,
                'idFieldName' => $idFieldName
            ]
        );
        $entities['ui_component_listing'] = $this->createUiComponentListingPart($moduleName, $tableName,$entityName)->generate(['actionsColumnClass' => $entities['entity_actions']->getEntityName()]);
        $entities['adminhtml_controller_index'] = $this->createIndexController($moduleName, $entityName)->generate();
        $entities['adminhtml_router'] = (new Routes($moduleName))->generate();//todo
        $entities['grid_collection'] = (new Collection($moduleName, $entityName))->generate(); //todo
        $entities['layout_index'] = (new Index($moduleName, $entityName))->generate(); //todo
        $entities['layout_edit'] = (new Edit($moduleName, $entityName))->generate(); //todo
        $entities['layout_new'] = (new NewLayout($moduleName, $entityName))->generate(); //todo
        $entities['di'] = (new Di($entityName, $entities['grid_collection']->getEntityName(), $moduleName, $tableName, $entities['resource']->getEntityName()))->generate();//todo
        $entities['form_data_provider'] = (new DataProvider($moduleName, $entityName, $entities['collection']->getEntityName()))->generate();
        $entities['ui_component_form'] = $this->createUiFormGenerator($moduleName, $tableName, $entityName)->generate(['dataProvider' => $entities['form_data_provider']->getEntityName()]);
        $entities['index_controller'] = (new \Krifollk\CodeGenerator\Model\Generator\Crud\Controller\Adminhtml\Index())->generate(['moduleName' => $moduleName, 'entityName' => $entityName]);
        $entities['new_controller'] = (new NewAction())->generate(['moduleName' => $moduleName, 'entityName' => $entityName]);
        $entities['edit_controller'] = (new EditAction())->generate(['moduleName' => $moduleName, 'entityName' => $entityName, 'entityRepository' => $entities['repositoryInterface']->getEntityName()]);

        $entities['save_controller'] = (new Save())->generate(
            [
                'moduleName'       => $moduleName,
                'entityName'       => $entityName,
                'idFieldName'      => 'id',
                'entityRepository' => $entities['repositoryInterface']->getEntityName(),
                'entityFactory'    => $entities['interface']->getEntityName() . 'Factory'
            ]
        );


        return $entities;
    }

    /**
     * @param $moduleName
     * @param $tableName
     * @param $entityName
     *
     * @return \Krifollk\CodeGenerator\Model\Generator\Crud\UiComponent\Listing
     */
    protected function createUiComponentListingPart($moduleName, $tableName, $entityName)
    {
        return $this->listingFactory->create([
            'tableName'  => $tableName,
            'moduleName' => $moduleName,
            'entityName' => $entityName,
        ]);
    }

    /**
     * @param $moduleName
     * @param $entityName
     *
     * @return \Krifollk\CodeGenerator\Model\Generator\Crud\Controller\Adminhtml\Index
     */
    protected function createIndexController($moduleName, $entityName)
    {
        return $this->indexFactory->create([
            'moduleName' => $moduleName,
            'entityName' => $entityName,
        ]);
    }

    protected function createUiFormGenerator($moduleName, $tableName, $entityName)
    {
        return $this->formFactory->create([
            'tableName'  => $tableName,
            'moduleName' => $moduleName,
            'entityName' => $entityName,
        ]);
    }
}
