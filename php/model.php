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

    public function get_or_add_series($series) {
        $existing_series = $this->get_series($series->externalId);
        if ($existing_series == false) {
            $this->add_series($series);
            return $series;
        } else {
            return $existing_series;
        }
    }

    public function get_series($seriesExternalId) {
        foreach ($this->series as $serie) {
            if ($serie->externalId == $seriesExternalId) {
                return $serie;
            }
        }
        return false;
    }

    public function add_series($series) {
        array_push($this->series, $series);
    }
}

class Course extends OrgUnit {
    public $id;

    public function __construct($id, $externalId, $displayName, $description, $published, $metadata) {
        parent::__construct($externalId, $displayName, 'course', $description, $published, $metadata);
        $this->id = $id;
    }
}

class Subject extends OrgUnit {
    public $id;

    public function __construct($id, $externalId, $displayName, $description, $published, $metadata) {
        parent::__construct($externalId, $displayName, 'subject', $description, $published, $metadata);
        $this->id = $id;
    }
}

class Part extends OrgUnit {
    public $id;

    public function __construct($id, $externalId, $displayName, $description, $published, $metadata) {
        parent::__construct($externalId, $displayName, 'part', $description, $published, $metadata);
        $this->id = $id;
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
