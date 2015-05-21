<?php

/**
 * The "Organisational unit" class is the base class for
 * courses, subjects, parts and modules. It can hold child
 * organisational unit objects and series
 */
class OrgUnit {
    public $externalId;
    public $displayName;
    public $type;
    public $description;
    public $published;
    public $metadata;
    public $children = [];
    public $series = [];

    public function __construct($externalId, $displayName, $type, $description, $published, $metadata) {
        $this->externalId = $externalId;
        $this->displayName = $displayName;
        $this->type = $type;
        $this->description = $description;
        $this->published = $published;
        $this->metadata = $metadata;
    }

    public function add_child($child) {
        array_push($this->children, $child);
    }

    public function has_child($externalId) {
        return isset($this->childrenByExternalId[$externalId]);
    }

    public function add_series($series) {
        array_push($this->series, $series);
    }
}

/**
 * A course is the top node in the data tree. When importing data it's a bit
 * special as it's the only organisational unit where you will have to manually
 * provide an id for.
 */
class Course extends OrgUnit {
    public $id;

    public function __construct($id, $externalId, $displayName, $description, $published, $metadata) {
        parent::__construct($externalId, $displayName, 'course', $description, $published, $metadata);
        $this->id = $id;
    }
}

class Subject extends OrgUnit {
    public function __construct($externalId, $displayName, $description, $published, $metadata) {
        parent::__construct($externalId, $displayName, 'subject', $description, $published, $metadata);
    }
}

class Part extends OrgUnit {
    public function __construct($externalId, $displayName, $description, $published, $metadata) {
        parent::__construct($externalId, $displayName, 'part', $description, $published, $metadata);
    }
}
class Module extends OrgUnit {
    public function __construct($externalId, $displayName, $description, $published, $metadata) {
        parent::__construct($externalId, $displayName, 'module', $description, $published, $metadata);
    }
}

class Series {
    public $externalId;
    public $displayName;
    public $description;
    public $events = [];

    public function __construct($externalId, $displayName, $description) {
        $this->externalId = $externalId;
        $this->displayName = $displayName;
        $this->description = $description;
    }

    public function add_event($event) {
        array_push($this->events, $event);
    }
}

class Event {
    public $externalId;
    public $displayName;
    public $description;
    public $notes;
    public $location;
    public $start;
    public $end;
    public $type;
    public $organisers = [];

    public function __construct($externalId, $displayName, $description, $notes, $location, $start, $end, $type) {
        $this->externalId = $externalId;
        $this->displayName = $displayName;
        $this->description = $description;
        $this->notes = $notes;
        $this->location = $location;
        $this->start = $start;
        $this->end = $end;
        $this->type = $type;
    }

    public function add_organiser($organiser) {
        array_push($this->organisers, $organiser);
    }
}

class Organiser {
    public $displayName;
    public $shibbolethId;

    public function __construct($displayName, $shibbolethId) {
        $this->displayName = $displayName;
        $this->shibbolethId = $shibbolethId;
    }

}

?>
