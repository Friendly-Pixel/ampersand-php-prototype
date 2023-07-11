<?php

/*
 * This file is part of the Ampersand backend framework.
 *
 */

namespace Ampersand\Core;

use DateTime;
use DateTimeZone;
use JsonSerializable;
use Ampersand\Core\Link;
use Ampersand\Core\TType;
use Ampersand\Core\Concept;
use Ampersand\Exception\AmpersandException;
use Ampersand\Exception\AtomAlreadyExistsException;
use Ampersand\Exception\FatalException;

/**
 *
 * @author Michiel Stornebrink (https://github.com/Michiel-s)
 *
 */
class Atom implements JsonSerializable
{
    /**
     * Ampersand identifier of the atom
     */
    protected string $id;
    
    /**
     * Specifies the concept of which this atom is an instance
     */
    public Concept $concept;
    
    /**
     * Row data (from database query) from which this resource is created
     */
    protected ?array $queryData = null;
    
    public function __construct(string $atomId, Concept $concept)
    {
        $this->concept = $concept;
        $this->setId($atomId);
    }
    
    /**
     * Function is called when object is treated as a string
     */
    public function __toString(): string
    {
        // if atom id is longer than 40 chars, display first and last 20 chars
        $id = strlen($this->id) > 40 ? substr($this->id, 0, 20) . '...' . substr($this->id, -20) : $this->id;
        return "{$id}[{$this->concept}]";
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Return label of atom to be displayed in user interfaces
     *
     * When no default view is defined for its Concept, the Atom identifier is returned
     */
    public function getLabel(): string
    {
        $viewData = $this->concept->getViewData($this);

        if (empty($viewData)) {
            return $this->id;
        } else {
            return implode("", $viewData);
        }
    }

    protected function setId($atomId): self
    {
        // TODO: check can be removed when _NEW is replaced by other mechanism
        if ($atomId === '_NEW') {
            throw new AmpersandException("Replace _NEW with intended atom id before instantiating Atom object");
        }
        
        switch ($this->concept->type) {
            case TType::ALPHANUMERIC:
            case TType::BIGALPHANUMERIC:
            case TType::HUGEALPHANUMERIC:
            case TType::PASSWORD:
            case TType::TYPEOFONE:
            case TType::BOOLEAN:
                $this->id = $atomId;
                break;
            case TType::DATE:
                // In php backend, all Dates are kept in ISO-8601 format
                $datetime = new DateTime($atomId);
                $this->id = $datetime->format('Y-m-d'); // format in ISO-8601 standard
                break;
            case TType::DATETIME:
                // In php backend, all DateTimes are kept in DateTimeZone::UTC and DateTime::ATOM format
                // $atomId may contain a timezone, otherwise UTC is asumed.
                $datetime = new DateTime($atomId, new DateTimeZone('UTC')); // The $timezone parameter is ignored when the $time parameter either is a UNIX timestamp (e.g. @946684800) or specifies a timezone (e.g. 2010-01-28T15:00:00+02:00).
                $datetime->setTimezone(new DateTimeZone('UTC')); // if not yet UTC, convert to UTC
                $this->id = $datetime->format(DateTime::ATOM); // format in ISO-8601 standard, i.e. 2005-08-15T15:52:01+00:00 (DateTime::ATOM)
                break;
            case TType::FLOAT:
            case TType::INTEGER:
            case TType::OBJECT:
                $this->id = $atomId;
                break;
            default:
                throw new FatalException("Unknown/unsupported ttype '{$this->concept->type->value}' for concept '[{$this->concept}]'");
        }
        return $this;
    }
    
    /**
     * Returns json representation of Atom (identifier) according to Ampersand technical types (TTypes)
     * Function is called when object encoded to json with json_encode()
     * @throws \Ampersand\Exception\FatalException when technical type is not (yet) supported
     */
    public function jsonSerialize(): mixed
    {
        switch ($this->concept->type) {
            case TType::ALPHANUMERIC:
            case TType::BIGALPHANUMERIC:
            case TType::HUGEALPHANUMERIC:
            case TType::PASSWORD:
            case TType::TYPEOFONE:
                return (string) $this->id;
            case TType::BOOLEAN:
                return (bool) $this->id;
            case TType::DATE:
                $datetime = new DateTime($this->id);
                return $datetime->format('Y-m-d'); // format in ISO-8601 standard
            case TType::DATETIME:
                // DateTime(s) may contain a timezone, otherwise UTC is asumed.
                $datetime = new DateTime($this->id, new DateTimeZone('UTC')); // The $timezone parameter is ignored when the $time parameter either is a UNIX timestamp (e.g. @946684800) or specifies a timezone (e.g. 2010-01-28T15:00:00+02:00).
                $datetime->setTimezone(new DateTimeZone(date_default_timezone_get())); // convert back to systemtime
                return $datetime->format(DateTime::ATOM); // format in ISO-8601 standard, i.e. 2005-08-15T15:52:01+00:00 (DateTime::ATOM)
            case TType::FLOAT:
                return (float) $this->id;
            case TType::INTEGER:
                return (int) $this->id;
            case TType::OBJECT:
                return rawurlencode($this->id);
            default:
                throw new FatalException("Unknown/unsupported ttype '{$this->concept->type->value}' for concept '[{$this->concept}]'");
        }
    }
    
    /**
     * Checks if atom exists in storage
     */
    public function exists(): bool
    {
        return $this->concept->atomExists($this);
    }

    /**
     * Get the most specific version of this atom (i.e. with the smallest concept)
     */
    public function getSmallest(): Atom
    {
        foreach ($this->concept->getSpecializations($onlyDirectSpecializations = true) as $specConcept) {
            // NOTE! Only a single path down is considered.
            if ($specConcept->atomExists($this)) {
                // Walk further down the classification tree
                return (new Atom($this->id, $specConcept))->getSmallest();
            }
        }
        // No further specializations
        return $this;
    }
    
    /**
     * Add atom to concept
     */
    public function add(bool $populateDefaults = true): self
    {
        $this->concept->addAtom($this, $populateDefaults);
        return $this;
    }
    
    /**
     * Delete atom from concept
     */
    public function delete(): self
    {
        $this->concept->deleteAtom($this);
        return $this;
    }
    
    /**
     * Merge another atom into this atom
     */
    public function merge(Atom $anotherAtom): self
    {
        $this->concept->mergeAtoms($this, $anotherAtom);
        return $this;
    }

    /**
     * Rename an atom identifier
     */
    public function rename(string $newAtomId): Atom
    {
        $newAtom = new Atom($newAtomId, $this->concept);
        if ($newAtom->exists()) {
            throw new AtomAlreadyExistsException("Cannot change atom identifier, because id is already used by another atom of the same concept");
        } else {
            $newAtom->add(false);
            return $newAtom->merge($this);
        }
    }
    
    /**
     * Undocumented function
     *
     * @param string|Atom $tgtAtom
     * @param string|Relation $relation when provided as string, use relation signature
     * @param bool $isFlipped specifies if $this and $tgtAtom must be flipped to match the relation
     */
    public function link(string|Atom $tgtAtom, string|Relation $relation, bool $isFlipped = false): Link
    {
        if (!($relation instanceof Relation)) {
            $relation = $this->concept->getApp()->getRelation($relation);
        }
        if (!($tgtAtom instanceof Atom)) {
            $tgtAtom = $isFlipped ? new Atom($tgtAtom, $relation->srcConcept) : new Atom($tgtAtom, $relation->tgtConcept);
        }
        
        if ($isFlipped) {
            return new Link($relation, $tgtAtom, $this);
        } else {
            return new Link($relation, $this, $tgtAtom);
        }
    }
    
    /**
     * Undocumented function
     *
     * @param string|Relation $relation when provided as string, use relation signature
     * @param boolean $isFlipped specifies if relation must be flipped
     * @return \Ampersand\Core\Link[]
     */
    public function getLinks(string|Relation $relation, bool $isFlipped = false): array
    {
        if (!($relation instanceof Relation)) {
            $relation = $this->concept->getApp()->getRelation($relation);
        }
        
        if ($isFlipped) {
            return $relation->getAllLinks(null, $this);
        } else {
            return $relation->getAllLinks($this, null);
        }
    }

    /**
     * Undocumented function
     *
     * @param string|\Ampersand\Core\Relation $relation when provided as string, use relation signature
     * @param bool $flip specifies if relation must be flipped
     * @return \Ampersand\Core\Atom[]
     */
    public function getTargetAtoms(string|Relation $relation, bool $flip = false): array
    {
        return array_map(function (Link $link) use ($flip) {
            return $flip ? $link->src() : $link->tgt();
        }, $this->getLinks($relation, $flip));
    }
    
    /**
     * Save query row data (can be used for subinterfaces)
     */
    public function setQueryData(?array $data = null): Atom
    {
        $this->queryData = $data;
        return $this;
    }
    
    /**
     * Get (column of) query data
     *
     * @param bool|null $exists reference var that returns if column exists
     */
    public function getQueryData(?string $colName = null, ?bool &$exists = null): mixed
    {
        if (is_null($colName)) {
            return (array) $this->queryData;
        } else {
            // column name is prefixed with 'ifc_' to prevent duplicates with 'src' and 'tgt' cols, which are standard added to query data
            $exists = array_key_exists($colName, (array) $this->queryData);
            return $this->queryData[$colName] ?? null;
        }
    }
}
