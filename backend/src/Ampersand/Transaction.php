<?php

/*
 * This file is part of the Ampersand backend framework.
 *
 */

namespace Ampersand;

use Exception;
use Ampersand\Core\Concept;
use Ampersand\Core\Relation;
use Ampersand\Plugs\StorageInterface;
use Ampersand\Rule\RuleEngine;
use Ampersand\Rule\ExecEngine;
use Ampersand\Rule\Rule;
use Ampersand\AmpersandApp;
use Ampersand\Event\TransactionEvent;
use Ampersand\Exception\FatalException;
use Ampersand\Exception\InvalidOptionException;
use Psr\Log\LoggerInterface;
use Ampersand\Log\Logger;

/**
 *
 * @author Michiel Stornebrink (https://github.com/Michiel-s)
 *
 */
class Transaction
{
    /**
     * Points to the current open transaction
     */
    private static ?Transaction $currentTransaction = null;
    
    /**
     * Transaction number (random int)
     */
    private int $id;
    
    /**
     * Logger
     */
    private LoggerInterface $logger;

    /**
     * Reference to Ampersand app for which this transaction is instantiated
     */
    protected AmpersandApp $app;
    
    /**
     * Contains all affected Concepts during a transaction
     *
     * @var \Ampersand\Core\Concept[]
     */
    private array $affectedConcepts = [];
    
    /**
     * Contains all affected relations during a transaction
     *
     * @var \Ampersand\Core\Relation[]
     */
    private array $affectedRelations = [];
    
    /**
     * Specifies if invariant rules hold
     *
     * Null if no transaction has occurred (yet)
     */
    private ?bool $invariantRulesHold = null;
    
    /**
     * Specifies if the transaction is committed or rolled back
     *
     * Null if transaction is still open (i.e. not committed nor rolled back)
     */
    private ?bool $isCommitted = null;
    
    /**
     * List with storages that are affected in this transaction
     *
     * Used to commit/rollback all storages when this transaction is closed
     *
     * @var \Ampersand\Plugs\StorageInterface[] $storages
     */
    private array $storages = [];

    /**
     * List of exec engines
     *
     * @var \Ampersand\Rule\ExecEngine[]
     */
    protected array $execEngines = [];

    /**
     * List of services (i.e. role names) for which a run is requested
     *
     * @var string[]
     */
    protected array $requestedServiceIds = [];
    
    /**
     * Constructor
     *
     * Note! Don't use this constructor. Use AmpersandApp::newTransaction of AmpersandApp::getCurrentTransaction instead
     */
    public function __construct(AmpersandApp $app, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->app = $app;

        // Check to ensure only a single open transaction. AmpersandApp class is responsible for this.
        if (!is_null(self::$currentTransaction)) {
            throw new FatalException("Something is wrong in the code. Only a single open transaction is allowed.");
        } else {
            self::$currentTransaction = $this;
        }

        $this->id = rand();
        $this->logger->info("Opening transaction: {$this->id}");
        $this->initExecEngines();
    }

    /**
     * Function is called when object is treated as a string
     */
    public function __toString(): string
    {
        return 'Transaction ' . $this->id;
    }

    protected function initExecEngines(): void
    {
        $execEngineRoleNames = $this->app->getSettings()->get('execengine.execEngineRoleNames');
        foreach ((array) $execEngineRoleNames as $roleName) {
            try {
                $role = $this->app->getModel()->getRoleByName($roleName);
                $this->execEngines[] = new ExecEngine($role, $this, $this->app, Logger::getLogger('EXECENGINE'));
            } catch (Exception $e) {
                $this->logger->warning("ExecEngine role '{$roleName}' configured, but role is not used/defined in &-script");
            }
        }
    }

    /**
     * Run exec engines
     *
     * CheckAllRules specifies if all rules must be evaluated (true) or only the affected rules in this transaction (false)
     */
    public function runExecEngine(bool $checkAllRules = false): Transaction
    {
        $logger = Logger::getLogger('EXECENGINE');
        $logger->info("ExecEngine started");

        // Initial values
        $maxRunCount = $this->app->getSettings()->get('execengine.maxRunCount');
        $autoRerun = $this->app->getSettings()->get('execengine.autoRerun');
        $doRun = true;
        $runCounter = 0;

        // Rules to check
        $rulesToCheck = $checkAllRules ? $this->app->getModel()->getAllRules() : $this->getAffectedRules();

        // Do run exec engines while there is work to do
        do {
            $runCounter++;
            $logger->info("ExecEngine run #{$this->id}-{$runCounter} (auto rerun: " . var_export($autoRerun, true) . ")");
            
            // Run all exec engines
            $rulesFixed = [];
            foreach ($this->execEngines as $ee) {
                $logger->debug("Select exec engine '{{$ee->getId()}}'");
                $rulesFixed = array_merge($rulesFixed, $ee->checkFixRules($rulesToCheck));
            }

            // Run all requested services
            foreach ($this->requestedServiceIds as $serviceId) {
                $logger->debug("Select service '{{$serviceId}}'");
                $rulesFixed = array_merge($rulesFixed, $this->runService($serviceId));
            }
            $this->requestedServiceIds = []; // Empty list of requested services

            // If no rules fixed (i.e. no violations) in this loop: stop exec engine
            if (empty($rulesFixed)) {
                $doRun = false;
            }

            // Prevent infinite loop in exec engine reruns
            if ($runCounter >= $maxRunCount && $doRun) {
                $logger->error("Maximum reruns exceeded. Rules fixed in last run:" . implode(', ', $rulesFixed) . ")");
                $this->app->userLog()->error("Maximum reruns exceeded for ExecEngine", ['Rules fixed in last run' => $rulesFixed]);
                $doRun = false;
            }
            $logger->debug("Exec engine run finished");
            $rulesToCheck = $this->getAffectedRules(); // next run only affected rules need to be checked
        } while ($doRun && $autoRerun);

        $logger->info("ExecEngine finished");
        
        return $this;
    }

    /**
     * Run exec engine for affected rules in this transaction
     */
    public function singleRunForExecEngine(string $id): Transaction
    {
        // Find/run specific exec engine
        foreach ($this->execEngines as $ee) {
            if ($ee->getId() == $id) {
                $ee->checkFixRules($this->getAffectedRules());
            }
        }

        return $this;
    }

    /**********************************************************************************************
     * SERVICES
     *********************************************************************************************/

    public function requestServiceRun(string $serviceId): void
    {
        if (!in_array($serviceId, $this->requestedServiceIds)) {
            $this->requestedServiceIds[] = $serviceId;
        }
    }

    /**
     * Single run of a specific service
     * A service is a collection of RULES that are evaluated and fixed the same way as with ExecEngine roles
     *
     * @param string $serviceId identifier of the service (i.e. name of ROLE)
     * @return \Ampersand\Rule\Rule[] $rulesFixed by this service
     */
    protected function runService(string $serviceId): array
    {
        try {
            $role = $this->app->getModel()->getRoleByName($serviceId);
        } catch (Exception $e) {
            $this->logger->warning("Transaction::runService is called with role '{$serviceId}', but this role is not used/defined in &-script");
        }

        if (isset($role)) {
            $service = new ExecEngine($role, $this, $this->app, Logger::getLogger('EXECENGINE'));
            return $service->checkFixRules($role->maintains()); // check all rules of this service
        } else {
            return [];
        }
    }

    /**********************************************************************************************
     * CLOSING THE TRANSACTION (leading to a commit or rollback)
     *********************************************************************************************/

    /**
     * Cancel (i.e. rollback) the transaction
     */
    public function cancel(): Transaction
    {
        $this->logger->info("Request to cancel transaction: {$this->id}");

        if ($this->isClosed()) {
            throw new InvalidOptionException("Cannot cancel transaction, because transaction is already closed");
        }

        $this->rollback();

        self::$currentTransaction = null; // unset currentTransaction
        return $this;
    }

    /**
     * Alias for closing the transaction with the intention to rollback
     *
     * Affected conjuncts are evaluated and invariant rule violations are reported
     */
    public function dryRun(): Transaction
    {
        return $this->close(true);
    }
    
    /**
     * Close transaction
     */
    public function close(bool $dryRun = false, bool $ignoreInvariantViolations = false): self
    {
        $this->logger->info("Request to close transaction: {$this->id}");
        
        if ($this->isClosed()) {
            throw new InvalidOptionException("Cannot close transaction, because transaction is already closed");
        }

        // (Re)evaluate affected conjuncts
        foreach ($this->getAffectedConjuncts() as $conj) {
            $conj->evaluate(); // violations are persisted below, only when transaction is committed
        }

        // Check invariant rules
        $this->invariantRulesHold = $this->checkInvariantRules();
        
        // Decide action (commit or rollback)
        if ($dryRun) {
            $this->logger->info("Rollback transaction, because dry run was requested");
            $this->rollback();
        } else {
            if ($this->invariantRulesHold) {
                $this->logger->info("Commit transaction: {$this->id}");
                $this->commit();
            } elseif (!$this->invariantRulesHold && ($this->app->getSettings()->get('transactions.ignoreInvariantViolations') || $ignoreInvariantViolations)) {
                $this->logger->warning("Commit transaction {$this->id} with invariant violations");
                $this->commit();
            } else {
                $this->logger->info("Rollback transaction {$this->id}, because invariant rules do not hold");
                $this->rollback();
            }
        }
        
        self::$currentTransaction = null; // unset currentTransaction
        return $this;
    }

    /**
     * Commit transaction
     */
    protected function commit(): void
    {
        // Cache conjuncts
        foreach ($this->getAffectedConjuncts() as $conj) {
            $conj->persistCacheItem();
        }

        // Commit transaction for each registered storage
        foreach ($this->storages as $storage) {
            $storage->commitTransaction($this);
        }

        $this->isCommitted = true;

        $this->app->eventDispatcher()->dispatch(new TransactionEvent($this), TransactionEvent::COMMITTED);
    }

    /**
     * Rollback transaction
     */
    protected function rollback(): void
    {
        // Rollback transaction for each registered storage
        foreach ($this->storages as $storage) {
            $storage->rollbackTransaction($this);
        }

        // Clear atom cache for affected concepts
        foreach ($this->affectedConcepts as $cpt) {
            $cpt->clearAtomCache();
        }

        $this->isCommitted = false;

        $this->app->eventDispatcher()->dispatch(new TransactionEvent($this), TransactionEvent::ROLLEDBACK);
    }
    
    /**
     * Add storage implementation to this transaction
     */
    private function addAffectedStorage(StorageInterface $storage): void
    {
        if (!in_array($storage, $this->storages)) {
            $this->logger->debug("Add storage: " . $storage->getLabel());
            $this->storages[] = $storage;
        }
    }

    /**********************************************************************************************
     * KEEPING TRACK OF AFFECTED CONCEPTS, RELATIONS, CONJUNCTS and RULES
     *********************************************************************************************/
    
    /**
     * Undocumented function
     *
     * @return \Ampersand\Core\Concept[]
     */
    public function getAffectedConcepts(): array
    {
        return $this->affectedConcepts;
    }
    
    /**
     * Undocumented function
     *
     * @return \Ampersand\Core\Relation[]
     */
    public function getAffectedRelations(): array
    {
        return $this->affectedRelations;
    }
    
    /**
     * Mark a concept as affected within the open transaction
     */
    public function addAffectedConcept(Concept $concept): void
    {
        if (!in_array($concept, $this->affectedConcepts)) {
            $this->logger->debug("Mark concept '{$concept}' as affected concept");
            
            foreach ($concept->getPlugs() as $plug) {
                $this->addAffectedStorage($plug); // Register storage in this transaction
                $plug->startTransaction($this); // Start transaction for this storage
            }

            $this->affectedConcepts[] = $concept;
        }
    }
    
    /**
     * Mark a relation as affected within the open transaction
     */
    public function addAffectedRelations(Relation $relation): void
    {
        if (!in_array($relation, $this->affectedRelations)) {
            $this->logger->debug("Mark relation '{$relation}' as affected relation");

            foreach ($relation->getPlugs() as $plug) {
                $this->addAffectedStorage($plug); // Register storage in this transaction
                $plug->startTransaction($this); // Start transaction for this storage
            }

            $this->affectedRelations[] = $relation;
        }
    }

    /**
     * Return list of affected conjuncts in this transaction
     *
     * @return \Ampersand\Rule\Conjunct[]
     */
    public function getAffectedConjuncts(): array
    {
        $affectedConjuncts = [];
        
        // Get conjuncts for affected concepts and relations
        foreach ($this->affectedConcepts as $concept) {
            $affectedConjuncts = array_merge($affectedConjuncts, $concept->getRelatedConjuncts());
        }
        foreach ($this->affectedRelations as $relation) {
            $affectedConjuncts = array_merge($affectedConjuncts, $relation->getRelatedConjuncts());
        }
        
        // Remove duplicates and return conjuncts
        return array_unique($affectedConjuncts);
    }

    /**
     * Get list of rules that are affected in this transaction
     *
     * @return \Ampersand\Rule\Rule[]
     */
    public function getAffectedRules(): array
    {
        $affectedRuleNames = [];
        foreach ($this->getAffectedConjuncts() as $conjunct) {
            $affectedRuleNames = array_merge($affectedRuleNames, $conjunct->getRuleNames());
        }
        $affectedRuleNames = array_unique($affectedRuleNames);

        return array_map(function (string $ruleName): Rule {
            return $this->app->getModel()->getRule($ruleName);
        }, $affectedRuleNames);
    }

    /**
     * Returns if invariant rules hold and notifies user of violations (if any)
     *
     * Note! Only checks affected invariant rules
     */
    public function checkInvariantRules(): bool
    {
        $this->logger->info("Checking invariant rules");
        
        $affectedInvRules = array_filter($this->getAffectedRules(), function (Rule $rule) {
            return $rule->isInvariantRule();
        });

        $rulesHold = true;
        foreach (RuleEngine::getViolations($affectedInvRules) as $violation) {
            $rulesHold = false; // set to false if there is one or more violation
            $this->app->userLog()->invariant($violation); // notify user of broken invariant rules
        }

        return $rulesHold;
    }
    
    public function invariantRulesHold(): ?bool
    {
        return $this->invariantRulesHold;
    }
    
    public function isCommitted(): bool
    {
        return $this->isCommitted === true;
    }
    
    public function isRolledBack(): bool
    {
        return $this->isCommitted === false;
    }
    
    public function isOpen(): bool
    {
        return $this->isCommitted === null;
    }
    
    public function isClosed(): bool
    {
        return $this->isCommitted !== null;
    }
}
