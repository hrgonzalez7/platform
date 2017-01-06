<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;
use Oro\Bundle\WorkflowBundle\Model\Filter\WorkflowDefinitionFilterInterface;

class WorkflowRegistry
{
    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var WorkflowAssembler */
    protected $workflowAssembler;

    /** @var Workflow[] */
    protected $workflowByName = [];

    /** @var array|WorkflowDefinitionFilterInterface[] */
    protected $definitionFilters = [];

    /**
     * @param ManagerRegistry $managerRegistry
     * @param WorkflowAssembler $workflowAssembler
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        WorkflowAssembler $workflowAssembler
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->workflowAssembler = $workflowAssembler;
    }

    /**
     * Get Workflow by name
     *
     * @param string $name
     * @param bool $exceptionOnNotFound
     * @return Workflow|null
     * @throws WorkflowNotFoundException
     */
    public function getWorkflow($name, $exceptionOnNotFound = true)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected value is workflow name string. But got %s',
                    is_object($name) ? get_class($name) : gettype($name)
                )
            );
        }

        if (!array_key_exists($name, $this->workflowByName)) {
            /** @var WorkflowDefinition $definition */
            $definition = $this->getEntityRepository()->find($name);
            if (!$definition) {
                if ($exceptionOnNotFound) {
                    throw new WorkflowNotFoundException($name);
                } else {
                    return null;
                }
            }

            return $this->getAssembledWorkflow($definition);
        }

        return $this->refreshWorkflow($this->workflowByName[$name]);
    }

    /**
     * Get Workflow by WorkflowDefinition
     *
     * @param WorkflowDefinition $definition
     * @return Workflow
     */
    protected function getAssembledWorkflow(WorkflowDefinition $definition)
    {
        $workflowName = $definition->getName();
        if (!array_key_exists($workflowName, $this->workflowByName)) {
            $workflow = $this->workflowAssembler->assemble($definition);
            $this->workflowByName[$workflowName] = $workflow;
        }

        return $this->refreshWorkflow($this->workflowByName[$workflowName]);
    }

    /**
     * @param string $entityClass
     * @return bool
     */
    public function hasActiveWorkflowsByEntityClass($entityClass)
    {
        $class = ClassUtils::getRealClass($entityClass);

        $activeWorkflowDefinitions = $this->getEntityRepository()->findActiveForRelatedEntity($class);

        $items = $this->processDefinitionFilters(
            $this->getNamedDefinitionsCollection($activeWorkflowDefinitions)
        );

        return !$items->isEmpty();
    }

    /**
     * Get Active Workflows that applicable to entity class
     *
     * @param string $entityClass
     * @return Workflow[]|Collection Named collection of active Workflow instances
     *                               with structure: ['workflowName' => Workflow $workflowInstance]
     */
    public function getActiveWorkflowsByEntityClass($entityClass)
    {
        $class = ClassUtils::getRealClass($entityClass);

        return $this->getAssembledWorkflows(
            $this->getEntityRepository()->findActiveForRelatedEntity($class)
        );
    }

    /**
     * Get Active Workflows by active groups
     *
     * @param array $groupNames
     * @return Workflow[]|Collection Named collection of active Workflow instances
     *                               with structure: ['workflowName' => Workflow $workflowInstance]
     */
    public function getActiveWorkflowsByActiveGroups(array $groupNames)
    {
        $groupNames = array_map('strtolower', $groupNames);

        $definitions = array_filter(
            $this->getEntityRepository()->findActive(),
            function (WorkflowDefinition $definition) use ($groupNames) {
                $exclusiveActiveGroups = $definition->getExclusiveActiveGroups();

                return (bool)array_intersect($groupNames, $exclusiveActiveGroups);
            }
        );

        return $this->getAssembledWorkflows($definitions);
    }

    /**
     * @param string $application
     * @return Workflow[]|Collection Named collection of active Workflow instances
     *                               with structure: ['workflowName' => Workflow $workflowInstance]
     */
    public function getActiveWorkflowsByApplication($application)
    {
        $definitions = array_filter(
            $this->getEntityRepository()->findActive(),
            function (WorkflowDefinition $definition) use ($application) {
                return in_array($application, $definition->getApplications(), true);
            }
        );

        return $this->getAssembledWorkflows($definitions);
    }

    /**
     * Returns named collection of active Workflow instances with structure:
     *      ['workflowName' => Workflow $workflowInstance]
     *
     * @return Workflow[]|Collection
     */
    public function getActiveWorkflows()
    {
        return $this->getAssembledWorkflows($this->getEntityRepository()->findActive());
    }

    /**
     * @param WorkflowDefinition[] $definitions
     *
     * @return Collection
     */
    private function getAssembledWorkflows(array $definitions)
    {
        $definitions = $this->getNamedDefinitionsCollection($definitions);

        return $this->processDefinitionFilters($definitions)
            ->map(
                function (WorkflowDefinition $workflowDefinition) {
                    return $this->getAssembledWorkflow($workflowDefinition);
                }
            );
    }

    /**
     * @param Collection|WorkflowDefinition[] $workflowDefinitions
     * @return Collection|WorkflowDefinition[]
     */
    private function processDefinitionFilters(Collection $workflowDefinitions)
    {
        if ($workflowDefinitions->isEmpty()) {
            return $workflowDefinitions;
        }

        foreach ($this->definitionFilters as $definitionFilter) {
            $workflowDefinitions = $definitionFilter->filter($workflowDefinitions);
        }

        return $workflowDefinitions;
    }

    /**
     * @param WorkflowDefinition[] $workflowDefinitions
     * @return Collection|Workflow[]
     */
    private function getNamedDefinitionsCollection(array $workflowDefinitions)
    {
        $workflows = new ArrayCollection();
        foreach ($workflowDefinitions as $definition) {
            $workflowName = $definition->getName();
            /** @var WorkflowDefinition $definition */
            $workflows->set($workflowName, $definition);
        }

        return $workflows;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->managerRegistry->getManagerForClass(WorkflowDefinition::class);
    }

    /**
     * @return WorkflowDefinitionRepository
     */
    protected function getEntityRepository()
    {
        return $this->getEntityManager()->getRepository(WorkflowDefinition::class);
    }

    /**
     * Ensure that all database entities in workflow are still in Doctrine Unit of Work
     *
     * @param Workflow $workflow
     * @return Workflow
     * @throws WorkflowNotFoundException
     */
    protected function refreshWorkflow(Workflow $workflow)
    {
        $refreshedDefinition = $this->refreshWorkflowDefinition($workflow->getDefinition());
        $workflow->setDefinition($refreshedDefinition);

        return $workflow;
    }

    /**
     * @param WorkflowDefinition $definition
     * @return WorkflowDefinition
     * @throws WorkflowNotFoundException
     */
    protected function refreshWorkflowDefinition(WorkflowDefinition $definition)
    {
        if (!$this->getEntityManager()->getUnitOfWork()->isInIdentityMap($definition)) {
            $definitionName = $definition->getName();

            $definition = $this->getEntityRepository()->find($definitionName);
            if (!$definition) {
                throw new WorkflowNotFoundException($definitionName);
            }
        }

        return $definition;
    }

    /**
     * @param WorkflowDefinitionFilterInterface $definitionFilter
     */
    public function addDefinitionFilter(WorkflowDefinitionFilterInterface $definitionFilter)
    {
        $this->definitionFilters[] = $definitionFilter;
    }
}
