<?php namespace Gbrock\Table;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Input;

class Column {
    /** @var string Applicable database field used in sorting */
    protected $field;

    /** @var string The default sorting direction */
    protected $direction;

    /** @var string The visible portion of the column header */
    protected $label;

    /** @var bool Whether this column can be sorted by the user */
    protected $sortable = false;

    /**
     * @var mixed
     * The rendering method used when generating cell data
     * Can be either a string (the function or view file to be rendered) or a closure accepting the model $row:
     * $column->setRender(function($row){ return '<strong>' . $row->id . '</strong>'; })
     */
    protected $render;

    public static function create()
    {
        $args = func_get_args();

        $class = new static;

        // Detect instantiation scheme
        switch(count($args))
        {
            case 1: // one argument passed
                if(is_string($args[0]))
                {
                    // Only the field was passed
                    $class->setField($args[0]);
                    $class->setLabel(ucwords(str_replace('_', ' ', $args[0])));
                }
                break;
            case 2: // two arguments
                if(is_string($args[0]) && is_array($args[1]))
                {
                    // Normal complex initialization: field and quick parameters
                    $class->setField($args[0]);
                    $class->setParameters($args[1]);
                    if(!isset($args[1]['label']))
                    {
                        $class->setLabel(ucwords(str_replace('_', ' ', $args[0])));
                    }
                }
                break;
        }

        return $class;
    }

    /**
     * Checks if this column is currently being sorted.
     */
    public function isSorted()
    {
        if(Request::input('sort') == $this->getField())
        {
            return true;
        }

        return false;
    }

    /**
     * Generates a URL to toggle sorting by this column.
     */
    public function getSortURL($direction = false)
    {
        if(!$direction)
        {
            // No direction indicated, determine automatically from defaults.
            $direction = $this->getDirection();

            if($this->isSorted())
            {
                // If we are already sorting by this column, swap the direction
                $direction = $direction == 'asc' ? 'desc' : 'asc';
            }
        }

        // Generate and return a URL which may be used to sort this column
        return $this->generateUrl(array_filter([
            'sort' => $this->getField(),
            'direction' => $direction,
        ]));
    }

    /**
     * Returns the default sorting
     * @return string
     */
    public function getDirection()
    {
       if($this->isSorted())
       {
           // If the column is currently being sorted, grab the direction from the query string
           $this->direction = Request::input('direction');
       }

        if(!$this->direction)
        {
            $this->direction = 'asc';
        }

        return $this->direction;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param string $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * @return boolean
     */
    public function isSortable()
    {
        return $this->sortable;
    }

    /**
     * @param boolean $sortable
     */
    public function setSortable($sortable)
    {
        $this->sortable = (bool) $sortable;
    }

    public function generateUrl($parameters = [])
    {
        // Generate our needed parameters
        $parameters = array_merge($this->getCurrentInput(), $parameters);

        // Grab the current URL and keep any passed parameters
        $action = $this->getCurrentAction();
//        $parameters = array_merge($action['where'], $parameters);

        return action($action['simple'], $parameters);
    }

    protected function getCurrentInput()
    {
        return Input::only([
            'sort' => Request::input('sort'),
            'direction' => Request::input('direction'),
        ]);
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    protected function getCurrentAction()
    {
        $action = Request::route()->getAction();
        return array_merge($action, [
            'simple' => substr($action['controller'], strlen($action['namespace'])+1),
        ]);
    }

    private function setParameters($arguments)
    {
        foreach($arguments as $k => $v)
        {
            $this->{'set' . ucfirst($k)}($v);
        }
    }

    /**
     * @param string $direction
     */
    public function setDirection($direction)
    {
        $this->direction = $direction;
    }
}
