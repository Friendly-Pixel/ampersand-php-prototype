<?php

/*
 * This file is part of the Ampersand backend framework.
 *
 */

namespace Ampersand\Interfacing;

use Exception;
use Ampersand\Core\Relation;
use Ampersand\Core\Concept;
use Ampersand\Interfacing\View;
use Ampersand\Core\Atom;
use function Ampersand\Misc\isSequential;
use Ampersand\Plugs\IfcPlugInterface;
use Ampersand\Interfacing\Options;
use Ampersand\Interfacing\Ifc;
use Ampersand\Interfacing\InterfaceObjectInterface;
use Ampersand\Interfacing\Resource;

/**
 *
 * @author Michiel Stornebrink (https://github.com/Michiel-s)
 *
 */
class InterfaceExprObject implements InterfaceObjectInterface
{
    /**
     * Dependency injection of an IfcPlug implementation
     * @var \Ampersand\Plugs\IfcPlugInterface
     */
    protected $plug;
    
    /**
     * Interface id (i.e. safe name) to use in framework
     * @var string
     */
    protected $id;
    
    /**
     *
     * @var string
     */
    protected $path;
    
    /**
     * Interface name to show in UI
     * @var string
     */
    protected $label;
    
    /**
     * Roles that have access to this interface
     * Only applies to top level interface objects
     * @var string[]
     */
    public $ifcRoleNames = [];
    
    /**
     *
     * @var boolean
     */
    private $crudC;
    
    /**
     *
     * @var boolean
     */
    private $crudR;
    
    /**
     *
     * @var boolean
     */
    private $crudU;
    
    /**
     *
     * @var boolean
     */
    private $crudD;
    
    /**
     *
     * @var \Ampersand\Core\Relation|null
     */
    private $relation;
    
    /**
     *
     * @var boolean|null
     */
    public $relationIsFlipped;
    
    /**
     *
     * @var boolean
     */
    private $isUni;
    
    /**
     *
     * @var boolean
     */
    private $isTot;
    
    /**
     *
     * @var boolean
     */
    private $isIdent;
    
    /**
     *
     * @var string
     */
    private $query;
    
    /**
     *
     * @var \Ampersand\Core\Concept
     */
    public $srcConcept;
    
    /**
     *
     * @var \Ampersand\Core\Concept
     */
    public $tgtConcept;
    
    /**
     *
     * @var \Ampersand\Interfacing\View|null
     */
    private $view;

    /**
     * Specifies the class of the BOX (in case of BOX interface)
     * e.g. in ADL script: INTERFACE "test" : expr BOX <SCOLS> []
     * the boxClass is 'SCOLS'
     * @var string
     */
    protected $boxClass = null;
    
    /**
     *
     * @var string
     */
    private $refInterfaceId;
    
    /**
     *
     * @var boolean
     */
    private $isLinkTo = false;
    
    /**
     *
     * @var \Ampersand\Interfacing\InterfaceObjectInterface[]
     */
    private $subInterfaces = [];

    /**
     * Parent interface (when not root interface)
     *
     * @var \Ampersand\Interfacing\InterfaceObjectInterface
     */
    protected $parentIfc = null;

    /**
     * Constructor
     *
     * @param array $ifcDef Interface object definition as provided by Ampersand generator
     * @param \Ampersand\Plugs\IfcPlugInterface $plug
     * @param string|null $pathEntry
     */
    public function __construct(array $ifcDef, IfcPlugInterface $plug, string $pathEntry = null)
    {
        if ($ifcDef['type'] != 'ObjExpression') {
            throw new Exception("Provided interface definition is not of type ObjExpression", 500);
        }

        $this->plug = $plug;
        
        // Set attributes from $ifcDef
        $this->id = $ifcDef['id'];
        $this->label = $ifcDef['label'];
        $this->view = is_null($ifcDef['viewId']) ? null : View::getView($ifcDef['viewId']);
        
        $this->path = is_null($pathEntry) ? $this->label : "{$pathEntry}/{$this->label}"; // Use label, because path is only used for human readable purposes (e.g. Exception messages)
        
        // Information about the (editable) relation if applicable
        $this->relation = is_null($ifcDef['relation']) ? null : Relation::getRelation($ifcDef['relation']);
        $this->relationIsFlipped = $ifcDef['relationIsFlipped'];
        
        // Interface expression information
        $this->srcConcept = Concept::getConcept($ifcDef['expr']['srcConceptId']);
        $this->tgtConcept = Concept::getConcept($ifcDef['expr']['tgtConceptId']);
        $this->isUni = $ifcDef['expr']['isUni'];
        $this->isTot = $ifcDef['expr']['isTot'];
        $this->isIdent = $ifcDef['expr']['isIdent'];
        $this->query = $ifcDef['expr']['query'];
        
        // Subinterfacing
        if (!is_null($ifcDef['subinterfaces'])) {
            // Subinterfacing is not supported/possible for tgt concepts with a scalar representation type (i.e. non-objects)
            if (!$this->tgtConcept->isObject()) {
                throw new Exception("Subinterfacing is not supported for concepts with a scalar representation type (i.e. non-objects). (Sub)Interface '{$this->path}' with target {$this->tgtConcept} (type:{$this->tgtConcept->type}) has subinterfaces specified", 501);
            }
            
            /* Reference to top level interface
             * e.g.:
             * INTERFACE "A" : expr1 INTERFACE "B"
             * INTERFACE "B" : expr2 BOX ["label" : expr3]
             *
             * is interpreted as:
             * INTERFACE "A" : expr1;epxr2 BOX ["label" : expr3]
             */
            $this->refInterfaceId = $ifcDef['subinterfaces']['refSubInterfaceId'];
            $this->isLinkTo = $ifcDef['subinterfaces']['refIsLinkTo'];
            $this->boxClass = $ifcDef['subinterfaces']['boxClass'];
            
            // Inline subinterface definitions
            foreach ((array)$ifcDef['subinterfaces']['ifcObjects'] as $subIfcDef) {
                $ifc = $subIfcDef['type'] == 'ObjText' ? new InterfaceTxtObject($subIfcDef, $this->plug, $this->path) : new InterfaceExprObject($subIfcDef, $this->plug, $this->path);
                $ifc->parentIfc = $this;
                $this->subInterfaces[$ifc->id] = $ifc;
            }
        }
        
        // CRUD rights
        $this->crudC = $this->isRef() ? null : $ifcDef['crud']['create'];
        $this->crudR = $this->isRef() ? null : $ifcDef['crud']['read'];
        $this->crudU = $this->isRef() ? null : $ifcDef['crud']['update'];
        $this->crudD = $this->isRef() ? null : $ifcDef['crud']['delete'];
    }
    
    /**
     * Function is called when object is treated as a string
     * @return string
     */
    public function __toString(): string
    {
        return $this->id;
    }

    public function getIfcId(): string
    {
        return $this->id;
    }

    public function getIfcLabel(): string
    {
        return $this->label;
    }
    
    /**
     * Returns interface relation (when interface expression = relation), throws exception otherwise
     * @throws \Exception when interface expression is not an (editable) relation
     * @return \Ampersand\Core\Relation
     */
    public function relation(): Relation
    {
        if (is_null($this->relation)) {
            throw new Exception("Interface expression for '{$this->label}' is not an (editable) relation", 500);
        } else {
            return $this->relation;
        }
    }
    
    /**
     * Returns if interface expression is editable (i.e. expression = relation)
     * @return bool
     */
    public function isEditable(): bool
    {
        return !is_null($this->relation);
    }
    
    /**
     * Array with all editable concepts for this interface and all sub interfaces
     * @var Concept[]
     */
    public function getEditableConcepts()
    {
        $arr = [];
        
        // Determine editable concept for this interface
        if ($this->crudU() && $this->tgtConcept->isObject()) {
            $arr[] = $this->tgtConcept;
        }
        
        // Add editable concepts for subinterfaces
        foreach ($this->getSubinterfaces(Options::DEFAULT_OPTIONS | Options::INCLUDE_REF_IFCS) as $ifc) {
            $arr = array_merge($arr, $ifc->getEditableConcepts());
        }
        
        return $arr;
    }

    /**
     * Returns if interface expression relation is a property
     * @return bool
     */
    protected function isProp(): bool
    {
        return is_null($this->relation) ? false : ($this->relation->isProp && !$this->isIdent());
    }
    
    /**
     * Returns if interface is a reference to another interface
     * @return bool
     */
    protected function isRef(): bool
    {
        return !is_null($this->refInterfaceId);
    }
    
    /**
     * Returns referenced interface object
     *
     * @throws Exception when $this is not a reference interface
     * @return \Ampersand\Interfacing\InterfaceObjectInterface
     */
    protected function getRefToIfc(): InterfaceObjectInterface
    {
        if ($this->isRef()) {
            return Ifc::getInterface($this->refInterfaceId)->getIfcObject();
        } else {
            throw new Exception("Interface is not a reference interface: " . $this->getPath(), 500);
        }
    }
    
    /**
     * Returns if interface object is a leaf node
     * @return bool
     */
    public function isLeaf(int $options = Options::DEFAULT_OPTIONS): bool
    {
        return empty($this->getSubinterfaces($options));
    }
    
    /**
     * Returns if the interface expression isIdent
     * Note! Epsilons are not included
     *
     * @return boolean
     */
    public function isIdent(): bool
    {
        return $this->isIdent;
    }
    
    public function isUni(): bool
    {
        return $this->isUni;
    }
    
    public function isTot(): bool
    {
        return $this->isTot;
    }
    
    public function getPath(): string
    {
        return $this->path;
    }
    
    public function crudC(): bool
    {
        // If crudC not specified during object construction (e.g. in case of ref interface)
        if (is_null($this->crudC)) {
            if ($this->isRef()) {
                $this->crudC = $this->getRefToIfc()->crudC();
            } else {
                throw new Exception("Create rights not specified for interface " . $this->getPath(), 500);
            }
        }
        
        return $this->crudC;
    }
    
    public function crudR(): bool
    {
        // If crudR not specified during object construction (e.g. in case of ref interface)
        if (is_null($this->crudR)) {
            if ($this->isRef()) {
                $this->crudR = $this->getRefToIfc()->crudR();
            } else {
                throw new Exception("Read rights not specified for interface " . $this->getPath(), 500);
            }
        }
        
        return $this->crudR;
    }
    
    public function crudU(): bool
    {
        // If crudU not specified during object construction (e.g. in case of ref interface)
        if (is_null($this->crudU)) {
            if ($this->isRef()) {
                $this->crudU = $this->getRefToIfc()->crudU();
            } else {
                throw new Exception("Update rights not specified for interface " . $this->getPath(), 500);
            }
        }
        
        return $this->crudU;
    }
    
    public function crudD(): bool
    {
        // If crudD not specified during object construction (e.g. in case of ref interface)
        if (is_null($this->crudD)) {
            if ($this->isRef()) {
                $this->crudD = $this->getRefToIfc()->crudD();
            } else {
                throw new Exception("Delete rights not specified for interface " . $this->getPath(), 500);
            }
        }
        
        return $this->crudD;
    }

    /**
     * Returns generated query for this interface expression
     * @return string
     */
    public function getQuery(): string
    {
        return str_replace('_SESSION', session_id(), $this->query); // Replace _SESSION var with current session id.
    }
    
    /**
     * @param string $ifcId
     * @return \Ampersand\Interfacing\InterfaceObjectInterface
     */
    public function getSubinterface(string $ifcId): InterfaceObjectInterface
    {
        if (!array_key_exists($ifcId, $subifcs = $this->getSubinterfaces())) {
            throw new Exception("Subinterface '{$ifcId}' does not exist in interface '{$this->path}'", 500);
        }
    
        return $subifcs[$ifcId];
    }
    
    /**
     * @param string $ifcLabel
     * @return \Ampersand\Interfacing\InterfaceObjectInterface
     */
    public function getSubinterfaceByLabel(string $ifcLabel): InterfaceObjectInterface
    {
        foreach ($this->getSubinterfaces() as $ifc) {
            if ($ifc->label == $ifcLabel) {
                return $ifc;
            }
        }
        
        throw new Exception("Subinterface '{$ifcLabel}' does not exist in interface '{$this->path}'", 500);
    }
    
    /**
     * Return array with all sub interface recursively (incl. the interface itself)
     * @return \Ampersand\Interfacing\InterfaceObjectInterface[]
     */
    public function getInterfaceFlattened()
    {
        $arr = [$this];
        foreach ($this->getSubinterfaces(Options::DEFAULT_OPTIONS & ~Options::INCLUDE_REF_IFCS) as $ifc) {
            $arr = array_merge($arr, $ifc->getInterfaceFlattened());
        }
        return $arr;
    }
    
    /**
     * @param int $options
     * @return \Ampersand\Interfacing\InterfaceObjectInterface[]
     */
    protected function getSubinterfaces(int $options = Options::DEFAULT_OPTIONS)
    {
        if ($this->isRef() && ($options & Options::INCLUDE_REF_IFCS) // if ifc is reference to other root ifc, option to include refs must be set (= default)
            && (!$this->isLinkTo || ($options & Options::INCLUDE_LINKTO_IFCS))) { // this ref ifc must not be a LINKTO ór option is set to explicitly include linkto ifcs
        /* Return the subinterfaces of the reference interface. This skips the referenced toplevel interface.
             * e.g.:
             * INTERFACE "A" : expr1 INTERFACE "B"
             * INTERFACE "B" : expr2 BOX ["label" : expr3]
             *
             * is interpreted as:
             * INTERFACE "A" : expr1;epxr2 BOX ["label" : expr3]
             */
            return $this->getRefToIfc()->getSubinterfaces($options);
        } else {
            return $this->subInterfaces;
        }
    }
    
    /**
     * @return \Ampersand\Interfacing\Ifc[]
     */
    protected function getNavInterfacesForTgt()
    {
        /** @var \Ampersand\AmpersandApp $ampersandApp */
        global $ampersandApp; // TODO: remove dependency on global var
        $ifcs = [];
        if ($this->isLinkTo && $ampersandApp->isAccessibleIfc($refIfc = $this->getRefToIfc())) {
            $ifcs[] = $refIfc;
        } else {
            $ifcs = $ampersandApp->getInterfacesToReadConcept($this->tgtConcept);
        }
        
        return $ifcs;
    }

    /**
     * Undocumented function
     *
     * @param \Ampersand\Core\Atom $tgtAtom the atom for which to get view data
     * @return array
     */
    protected function getViewData(Atom $tgtAtom): array
    {
        if (is_null($this->view)) {
            return $this->tgtConcept->getViewData($tgtAtom);
        } else {
            return $this->view->getViewData($tgtAtom);
        }
    }

    public function all(Resource $src): array
    {
        if (!$this->crudR()) {
            throw new Exception("Read not allowed for " . $this->getPath(), 405);
        }
        
        return $this->getTgtResources($src, true);
    }

    public function one(Resource $src, string $tgtId = null): Resource
    {
        if (!$this->crudR()) {
            throw new Exception("Read not allowed for " . $this->getPath(), 405);
        }

        // If no tgtId is provided, the srcId is used. Usefull for ident interface expressions (I[Concept])
        if (is_null($tgtId)) {
            $tgtId = $src->id;
        }
        
        $tgts = $this->getTgtResources($src, true, $tgtId);

        if (!empty($tgts)) {
            // Resource found
            return current($tgts);
        } elseif ($this->tgtConcept->isObject() && $this->crudC()) {
            // Create the target if allowed
            return $this->makeResource($tgtId, $src);
        } else {
            // When not found
            throw new Exception("Resource not found", 404);
        }
    }

    public function read(Resource $src, int $options = Options::DEFAULT_OPTIONS, int $depth = null, array $recursionArr = [])
    {
        if (!$this->crudR()) {
            throw new Exception("Read not allowed for ". $this->getPath(), 405);
        }

        // Initialize result
        $result = [];

        // Object nodes
        if ($this->tgtConcept->isObject()) {
            foreach ($this->getTgtResources($src, true) as $resource) {
                $result[] = $this->getResourceContent($resource, $options, $depth, $recursionArr);
            }
            
            // Special case for leave PROP: return false when result is empty, otherwise true (i.e. I atom must be present)
            // Enables boolean functionality for editing ampersand property relations
            if ($this->isLeaf($options) && $this->isProp()) {
                if (empty($result)) {
                    return false;
                } else {
                    return true;
                }
            }
            
        // Non-object nodes (i.e. leaves, because subinterfaces are not allowed for non-objects)
        // Notice that ->getResourceContent() is not called on $resource. The interface stops here.
        } else {
            $result = $this->getTgtAtoms($src); // for json_encode $resource->jsonSerializable() is called
        }

        // Return result
        if ($this->isUni) { // single object
            return empty($result) ? null : current($result);
        } else { // array
            return $result;
        }
    }

    protected function getResourceContent(Resource $resource, $options, $depth, $recursionArr)
    {
        // Prevent infinite loops for reference interfaces when no depth is provided
        // We only need to check LINKTO ref interfaces, because cycles may not exist in regular references (enforced by Ampersand generator)
        // If $depth is provided, no check is required, because recursion is finite
        if ($this->isLinkTo && is_null($depth)) {
            if (in_array($resource->id, $recursionArr[$this->refInterfaceId] ?? [])) {
                throw new Exception("Infinite loop detected for {$resource} in " . $this->getPath(), 500);
            } else {
                $recursionArr[$this->refInterfaceId][] = $resource->id;
            }
        }

        // Init content array
        $content = [];

        // Basic UI data of a resource
        if ($options & Options::INCLUDE_UI_DATA) {
            $viewData = $this->getViewData($resource);

            // Add Ampersand atom attributes
            $content['_id_'] = $resource->id;
            $content['_label_'] = empty($viewData) ? $resource->getLabel() : implode('', $viewData);
            $content['_path_'] = $resource->getPath();
            
            // Add view data if array is assoc (i.e. not sequential, because then it is a label)
            if (!isSequential($viewData)) {
                $content['_view_'] = $viewData;
            }
        // Not INCLUDE_UI_DATA and ifc isLeaf (i.e. there are no subinterfaces) -> directly return $resource->id
        } elseif ($this->isLeaf($options)) {
            return $resource->id;
        }

        // Determine if sorting values must be added
        $addSortValues = in_array($this->boxClass, ['SCOLS', 'SHCOLS', 'SPCOLS']) && ($options & Options::INCLUDE_SORT_DATA);

        // Get data of subinterfaces if depth is not provided or max depth not yet reached
        if (is_null($depth) || $depth > 0) {
            if (!is_null($depth)) {
                $depth--; // decrease depth by 1
            }

            foreach ($this->getSubinterfaces($options) as $ifcObj) {
                /** @var \Ampersand\Interfacing\InterfaceObjectInterface $ifcObj */
                if (!$ifcObj->crudR()) {
                    continue; // skip subinterface if not given read rights (otherwise exception will be thrown when getting content)
                }
                $content[$ifcObj->getIfcId()] = $ifcObj->read($resource, $options, $depth, $recursionArr);

                // Add sort values
                if ($ifcObj->isUni() && $addSortValues) {
                    $content['_sortValues_'][$ifcObj->getIfcId()] = $content[$ifcObj->getIfcId()]['_label_'] ?? $content[$ifcObj->getIfcId()] ?? null;
                }
            }
        }

        // Interface(s) to navigate to for this resource
        if ($options & Options::INCLUDE_NAV_IFCS) {
            $content['_ifcs_'] = array_map(function (Ifc $o) {
                return ['id' => $o->getId(), 'label' => $o->getLabel()];
            }, $this->getNavInterfacesForTgt());
        }

        return $content;
    }

    public function create(Resource $src, $tgtId = null): Resource
    {
        if (!$this->crudC()) {
            throw new Exception("Create not allowed for ". $this->getPath(), 405);
        }
        
        // Make new resource
        if (isset($tgtId)) {
            $resource = $this->makeResource($tgtId, $src);
            if ($resource->exists()) {
                throw new Exception("Cannot create resource that already exists", 400);
            }
        } else {
            $resource = $this->makeNewResource($src);
        }
        
        // If interface is editable, also add tuple(src, tgt) in interface relation
        if ($this->isEditable()) {
            $this->add($src, $resource->id, true);
        }

        // Add to plug (database) and return
        return $resource->add();
    }

    /**
     * Set provided value (for univalent interfaces)
     *
     * @param \Ampersand\Core\Atom $src
     * @param mixed|null $value
     * @return bool
     */
    public function set(Atom $src, $value = null): bool
    {
        if (!$this->isUni()) {
            throw new Exception("Cannot use set() for non-univalent interface " . $this->getPath() . ". Use add or remove instead", 400);
        }

        if (is_array($value)) {
            throw new Exception("Non-array expected but array provided while updating " . $this->getPath(), 400);
        }
        
        // Handle Ampersand properties [PROP]
        if ($this->isProp()) {
            if ($value === true) {
                $this->add($src, $src->id);
            } elseif ($value === false) {
                $this->remove($src, $src->id);
            } else {
                throw new Exception("Boolean expected, non-boolean provided.", 400);
            }
        } elseif ($this->isIdent()) { // Ident object => no need for object id
            // go deeper into PUT when interface expression equals 'I'
            $this->makeResource($src->id, $src)->put($value);
        } else {
            if (is_null($value)) {
                $this->removeAll($src);
            } else {
                $this->add($src, $value);
            }
        }
        
        return true;
    }

    /**
     * Add value to resource list
     * @param \Ampersand\Core\Atom $src
     * @param mixed $value
     * @param bool $skipCrudUCheck
     * @return bool
     */
    public function add(Atom $src, $value, bool $skipCrudUCheck = false): bool
    {
        if (!isset($value)) {
            throw new Exception("Cannot add item. Value not provided", 400);
        }
        if (is_object($value) || is_array($value)) {
            throw new Exception("Literal expected but " . gettype($value) . " provided while updating " . $this->getPath(), 400);
        }
        
        if (!$this->isEditable()) {
            throw new Exception("Interface is not editable " . $this->getPath(), 405);
        }
        if (!$this->crudU() && !$skipCrudUCheck) {
            throw new Exception("Update not allowed for " . $this->getPath(), 405);
        }
        
        $tgt = new Atom($value, $this->tgtConcept);
        if ($tgt->concept->isObject() && !$this->crudC() && !$tgt->exists()) {
            throw new Exception("Create not allowed for " . $this->getPath(), 405);
        }
        
        $tgt->add();
        $src->link($tgt, $this->relation(), $this->relationIsFlipped)->add();
        
        return true;
    }

    /**
     * Remove value from resource list
     *
     * @param mixed $value
     * @return bool
     */
    public function remove(Atom $src, $value): bool
    {
        if (!isset($value)) {
            throw new Exception("Cannot remove item. Value not provided", 400);
        }
        if (is_object($value) || is_array($value)) {
            throw new Exception("Literal expected but " . gettype($value) . " provided while updating " . $this->getPath(), 400);
        }
        
        if (!$this->isEditable()) {
            throw new Exception("Interface is not editable " . $this->getPath(), 405);
        }
        if (!$this->crudU()) {
            throw new Exception("Update not allowed for " . $this->getPath(), 405);
        }
        
        $tgt = new Atom($value, $this->tgtConcept);
        $src->link($tgt, $this->relation(), $this->relationIsFlipped)->delete();
        
        return true;
    }

    /**
     * Undocumented function
     *
     * @param \Ampersand\Core\Atom $src
     * @return bool
     */
    public function removeAll(Atom $src): bool
    {
        if (!$this->isEditable()) {
            throw new Exception("Interface is not editable " . $this->getPath(), 405);
        }
        if (!$this->crudU()) {
            throw new Exception("Update not allowed for " . $this->getPath(), 405);
        }
        
        $this->relation->deleteAllLinks($src, ($this->relationIsFlipped ? 'tgt' : 'src'));

        return true;
    }

    public function delete(Resource $tgtAtom): bool
    {
        if (!$this->crudD()) {
            throw new Exception("Delete not allowed for ". $this->getPath(), 405);
        }
        
        // Perform delete
        $tgtAtom->concept->deleteAtom($tgtAtom);

        return true;
    }

    /**
     * Undocumented function
     *
     * @param \Ampersand\Interfacing\Resource $src
     * @return \Ampersand\Core\Atom[]
     */
    protected function getTgtAtoms(Resource $src): array
    {
        return $this->getTgtResources($src, false);
    }

    /**
     * Return list of target atoms
     * @param \Ampersand\Interfacing\Resource $src
     * @param bool $returnClass specifies if method returns list of Resources (true) or Atoms (false)
     * @param string|null $selectTgt
     * @return \Ampersand\Interfacing\Resource[]|\Ampersand\Core\Atom[]
     */
    protected function getTgtResources(Resource $src, bool $returnResource = true, string $selectTgt = null): array
    {
        $tgts = [];

        // If interface isIdent (i.e. expr = I[Concept]), and no epsilon is required (i.e. srcConcept equals tgtConcept of parent ifc) we can return the src
        if ($this->isIdent() && $this->srcConcept === $src->concept) {
            $tgts[] = $returnResource ? $this->makeResource($src->id, $src) : new Atom($src->id, $this->tgtConcept);
        } else {
            // Try to get tgt atom from src query data (in case of uni relation in same table)
            $tgtId = $src->getQueryData('ifc_' . $this->id, $exists); // column is prefixed with ifc_ in query data
            if ($exists) {
                if (!is_null($tgtId)) {
                    $tgts[] = $returnResource ? $this->makeResource($tgtId, $src) : new Atom($src->id, $this->tgtConcept);
                }
            // Evaluate interface expression
            } else {
                foreach ((array) $this->plug->executeIfcExpression($this, $src) as $row) {
                    $tgts[] = $returnResource ? $this->makeResource($row['tgt'], $src)->setQueryData($row) : new Atom($src->id, $this->tgtConcept);
                }
            }
        }

        // Integrity check
        if ($this->isUni() && count($tgts) > 1) {
            throw new Exception("Univalent (sub)interface returns more than 1 resource: " . $this->getPath(), 500);
        }

        // If specific target is specified, pick that one out
        if (!is_null($selectTgt)) {
            return array_filter($tgts, function (Atom $item) use ($selectTgt) {
                return $item->id == $selectTgt;
            });
        }
        
        return $tgts;
    }

    /**
     * Resource factory. Instantiates a new target resource
     *
     * @param string $resourceId
     * @param \Ampersand\Interfacing\Resource $parent
     * @return \Ampersand\Interfacing\Resource
     */
    protected function makeResource(string $resourceId, Resource $parent): Resource
    {
        return new Resource($resourceId, $this->tgtConcept, $this, $parent);
    }

    /**
     * Resource factory. Instantiates a new target resource with a new (random) id
     *
     * @return \Ampersand\Interfacing\Resource
     */
    protected function makeNewResource(Resource $parent): Resource
    {
        $resourceId = $this->tgtConcept->createNewAtomId();
        return $this->makeResource($resourceId, $parent);
    }

    public function getTechDetails(): array
    {
        return
            [ 'path' => $this->getPath()
            , 'label' => $this->getIfcLabel()
            , 'crudR' => $this->crudR()
            , 'crudU' => $this->crudU()
            , 'crudD' => $this->crudD()
            , 'crudC' => $this->crudC()
            , 'src' => $this->srcConcept->name
            , 'tgt' => $this->tgtConcept->name
            , 'view' => $this->view->label ?? ''
            , 'relation' => $this->relation()->signature ?? ''
            , 'flipped' => $this->relationIsFlipped
            , 'ref' => $this->refInterfaceId
            , 'roles' => implode(',', $this->ifcRoleNames)
            ];
    }

    public function diagnostics(): array
    {
        $diagnostics = [];

        if ($this->crudU() && !$this->isEditable()) {
            $diagnostics[] = [ 'interface' => $this->getPath()
                             , 'message' => "Update rights (crUd) specified while interface expression is not an editable relation!"
                             ];
        }

        if ($this->crudC() && !$this->tgtConcept->isObject()) {
            $diagnostics[] = [ 'interface' => $this->getPath()
                             , 'message' => "Create rights (Crud) specified while target concept is a scalar. This has no affect!"
                             ];
        }

        if ($this->crudD() && !$this->tgtConcept->isObject()) {
            $diagnostics[] = [ 'interface' => $this->getPath()
                             , 'message' => "Delete rights (cruD) specified while target concept is a scalar. This has no affect!"
                             ];
        }

        if (!$this->crudR()) {
            $diagnostics[] = [ 'interface' => $this->getPath()
                             , 'message' => "No read rights specified. Are you sure?"
                             ];
        }

        // Check for unsupported patchReplace functionality due to missing 'old value'. Related with issue #318. TODO: still needed??
        if ($this->isEditable() && $this->crudU() && !$this->tgtConcept->isObject() && $this->isUni()) {
            // Only applies to editable relations
            // Only applies to crudU, because issue is with patchReplace, not with add/remove
            // Only applies to scalar, because objects don't use patchReplace, but Remove and Add
            // Only if interface expression (not! the relation) is univalent, because else a add/remove option is used in the UI
            if ((!$this->relationIsFlipped && $this->relation()->getMysqlTable()->tableOf === 'tgt')
                    || ($this->relationIsFlipped && $this->relation()->getMysqlTable()->tableOf === 'src')) {
                $diagnostics[] = [ 'interface' => $this->getPath()
                                 , 'message' => "Unsupported edit functionality due to combination of factors. See issue #318"
                                 ];
            }
        }

        return $diagnostics;
    }
}
