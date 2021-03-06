<?php

declare(strict_types=1);

/**
 * This file is part of Code Generator for Magento.
 * (c) 2016. Rostyslav Tymoshenko <krifollk@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Krifollk\CodeGenerator\Console\Command;

use Krifollk\CodeGenerator\Model\Command\Triad;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TriadGenerateCommand
 *
 * @package Krifollk\CodeGenerator\Console\Command
 */
class TriadGenerateCommand extends AbstractCommand
{
    const COMMAND_NAME = 'generate:model:triad';

    /**#@+
     * Arguments
     */
    const TABLE_NAME_ARGUMENT  = 'table_name';
    const MODULE_NAME_ARGUMENT = 'module_name';
    const ENTITY_NAME_ARGUMENT = 'entity_name';
    /**#@-*/

    /**
     * Triad command generator helper
     *
     * @var Triad
     */
    private $triad;

    /**
     * Inject dependencies
     *
     * @param Triad $triad
     *
     * @throws \LogicException
     */
    public function __construct(Triad $triad)
    {
        parent::__construct(self::COMMAND_NAME);
        $this->triad = $triad;
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    protected function configure()
    {
        parent::configure();
        $this->setDescription('Generate Model, Resource, Collection and also Repository, by db table.')
            ->addArgument(self::ENTITY_NAME_ARGUMENT, InputArgument::REQUIRED, 'Entity name')
            ->addArgument(self::TABLE_NAME_ARGUMENT, InputArgument::REQUIRED, 'Table name');
    }

    /**
     * @todo
     * {@inheritdoc}
     * @throws \DomainException
     * @throws \InvalidArgumentException
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     * @throws \Zend\Code\Generator\Exception\RuntimeException
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $moduleName = $this->createModuleNameEntity($input->getArgument(self::MODULE_NAME_ARGUMENT));
        $tableName = $input->getArgument(self::TABLE_NAME_ARGUMENT);
        $entityName = ucfirst($input->getArgument(self::ENTITY_NAME_ARGUMENT));
        $dir = $this->getDirOption($input);

        try {
            $generatedFiles = $this->triad->generate($moduleName, $tableName, $entityName, $dir);

            foreach ($generatedFiles as $generatedFile) {
                $output->writeln(sprintf('<info>File %s was generated.</info>', $generatedFile));
            }
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }
}
