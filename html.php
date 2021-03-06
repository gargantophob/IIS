<?php

/**
 * @file html.php
 * HTML generator.
 * @author xsemri00
 */

require_once "library.php";
require_once "entity.php";

/** HTML page. */
/** {{{ */
class Page {
    /** A collection of primitives. */
    private $primitives;

    /** Initialize the page.
     *  @param active_idx index of highlighted item in menubar
     *  @authorized state of navigation bar
     */
    public function __construct() {
        $this->primitives = array();
    }

    /**
     * TODO
     */
    public function menu() {
        $topnav = new Block();
        $topnav->set('class', 'topnav');
        $source = null;
        $source = session_data("user");
        if ($source == null) {
            $topnav->add(new Link("index.php", "Main page"));
        } else {
            $source = Person::look_up($source);
            
            // Everybody can see his profile
            $topnav->add(new Link("profile.php", "My profile"));
            
            // Alcoholics can see patrons and experts and can report themselves
            if($source->role == "alcoholic") {
                $link = plink("members.php", array("type" => "patrons"));
                $topnav->add(new Link($link, "My patrons"));
                
                $link = plink("members.php", array("type" => "experts"));
                $topnav->add(new Link($link, "My experts"));
                
                $link = plink(
                    "new_report.php", array("target" => $source->email)
                );
                $topnav->add(new Link($link, "New report"));
            }
            
            // Patrons and experts see alcoholics
            if($source->role == "patron" || $source->role == "expert") {
                $link = plink("members.php", array("type" => "alcoholics"));
                $topnav->add(new Link($link, "My alcoholics"));
            }
            
            // Experts can report
            if($source->role == "expert") {
                $link = plink("members.php", array("type" => "alcoholics"));
                $topnav->add(new Link($link, "New report"));
            }

            // Everybody can see sessions and AA members, can edit profile or
            // logout and see a logout timer
            $topnav->add(new Link("sessions.php", "Sessions"));
            $topnav->add(new Link("members.php", "AA members"));
            $topnav->add(new Link("signup.php", "Edit profile"));
            $link = plink("index.php", array("logout" => "yes"));
            $topnav->add(new Link($link, "Logout"));
            
            $element = new Text("");
            $element->set("id", "timer");
            $topnav->add($element);
        }

        return $topnav->html();
    }

    /** Register a primitive.
     * @param primitive instance of a class implementing Primitive
     */
    public function add($primitive) {
        array_push($this->primitives, $primitive);
    }

    /** Add a newline. */
    public function newline() {
        $this->add(new CRLF());
    }

    /** html() implementation. */
    public function html() {
        // Start the page
        $page = "<!DOCTYPE html> <html>";
        $page = '<title>Alcoholics Assembly</title>';

        // Head + styles
        $page .= '<head><link rel="stylesheet" href="styles.css">';
//        $page .- '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js">';
        $page .= '</head>';
        // adjust to smaller devices
        // XXX doesn't work
        $page .= '<meta name="viewport" content="width=device-width,
        initial-scale=1">';
        // Body
        $page .= '<body>';
        // menu
        $page .= $this->menu();
        $page .= '<div class="row">';
        $page .= '<div class="column side"></div>';
        $page .= '<div class="column middle">';
        foreach ($this->primitives as $primitive) {
            $page .= $primitive->html();
        }
        $page .= "</div>";  // content column ending tag
        $page .= "</div>";  // row ending tag
        $page .= "</body>";

        // End the page
        $page .= "</html>";

        // Attach a script
        $page .= '
                <script>

                // Automatic logout
                var seconds = 600;
                var prompt = "Automatic logout in: ";
                var timestr = Math.floor(seconds / 60) + ":" + seconds%60;
                document.getElementById("timer").textContent = prompt + timestr;
                setInterval(
                    function() {
                        seconds--;
                        if(seconds == 0) {
                            window.location.replace("index.php?logout=yes");
                        }
                        var timestr = Math.floor(seconds / 60) + ":" + seconds%60;
                        document.getElementById("timer").textContent = prompt + timestr;
                    },
                    1000
                );
                
                </script>
        ';
        
        // Success
        return $page;
    }

    /** Render the page. */
    public function render() {
        echo($this->html());
    }
}
/** }}} */

/****************************** PRIMITIVES ******************************/

/** HTML primitive abstract class. */
abstract class Primitive {
    /** tag element attributes */
    protected $attributes;

    public function set($attribute, $value) {
        if (array_key_exists($value, $this->attributes)) {
            $this->attributes[$attribute] .= " $value";
        }
        else {
            $this->attributes[$attribute] = $value;
        }
    }

    public function get($attribute, $value) {
        $this->attributes[$attribute] = $value;
    }

    /** iterate over attributes and returns it in html notation */
    protected function attr_html() {
        $str = " ";
        foreach($this->attributes as $attribute => $value) {
            $str .= " $attribute='$value' ";
        }
        return $str;
    }

    public function __construct() {
        $this->attributes = array();
    }

    /** Get html representation of a primitive. */
    abstract public function html();
}


class Block extends Primitive {
    public $childs;

    public function __construct() {
        // XXX inline (span)?
        parent::__construct();
        $this->childs = array();
    }

    public function add($primitive) {
        array_push($this->childs, $primitive);
    }

    public function html() {
        $ret = '<div ' . $this->attr_html() . '>';
        foreach ($this->childs as $primitive) {
            $ret .= $primitive->html();
        }
        $ret .= '</div>';
        return $ret;
    }
}


/** Line break. */
class CRLF extends Primitive {
    /** html() implementation. */
    public function html() {
        return "</br>";
    }
}

/** Text block. */
class Text extends Primitive {
    /** Block data. */
    private $data;

    /** Construct a text block. */
    public function __construct($data) {
        parent::__construct();
        $this->data = $data;
    }

    /** html() implementation. */
    public function html() {
        return "<span " . $this->attr_html() . " >$this->data</span>";
    }
}

/** Hyperlink. */
class Link extends Primitive {
    /** Link description. */
    private $description;

    /** Create a hyperlink. */
    public function __construct($url, $description) {
        parent::__construct();
        $this->description = "$description";
        $this->set('href', $url);
    }

    /** html() implementation. */
    public function html() {
        return "<a " . $this->attr_html() . '>' . $this->description . '</a>';
    }
}

/** Image. */
class Image extends Primitive {
    /** Source image. */
    private $source;

    /** Create an image. */
    public function __construct($source) {
        parent::__construct();
        $this->source = $source;
    }

    /** html() implementation. */
    public function html() {
        return "<img src='$this->source' style='width:20%;height:auto;'>";
    }
}

/** Form input. */
class Input extends Primitive {
    /** Input type. */
    private $type;
    /** Input identifier. */
    protected $name;
    /** Input element label (optional). */
    protected $label;
    /** Required field flag. */
    protected $required;
    
    /** Construct a form input. */
    public function __construct($type, $name, $label = null) {
        parent::__construct();
        $this->type = $type;
        $this->name = $name;
        $this->label = $label;
        $this->required = FALSE;
    }

    /** Set required. */
    public function required() {
        $this->required = TRUE;
    }
    
    /** html() implementation. */
    public function html() {
        $str = '';
        // Add label (if exists)
        if(isset($this->label)) {
            $str .= "<label for='$this->name'>$this->label</label>";
        }

        // Open tag, append attributes, close tag
        $str .= "<input type='$this->type' name='$this->name' ";
        $str .= $this->attr_html();
        $str .=  "/>";

        // Required star
        if($this->required) {
            $str .= "<span style='color:red'>*</span>";
        }
        
        // Success
        return $str;
    }
}

/** Text area: a special input. */
class Textarea extends Input {
    // TODO
}

/** Select: a special input. */
class Select extends Input {
    /** List of options. */
    private $options;
    /** Selected option (optional). */
    private $selected;

    /** Construct a select input. */
    public function __construct($name, $label = null) {
        Input::__construct("select", $name, $label);
    }

    /** Add new option.
     * @param option new option
     * @param description option description
     * @note if @c option was already registered, its description will be overwritten
     */
    public function add_option($option, $description) {
        $this->options[$option] = $description;
    }

    /** Specify a preselected value.
     * @param option name of an option to select
     */
    public function select($option) {
        $this->selected = $option;
    }

    /** html() implementation. */
    public function html() {

        $str = "";
        // Add label (if exists)
        if(isset($this->label)) {
            $str .= "<label for='$this->name'>$this->label</label>";
        }

        // Open tag, append attributes, close tag
        $str .= "<select name='$this->name' ";
        $str .= $this->attr_html();
        $str .= ">";

        // Append options, close element
        foreach($this->options as $option => $description) {
            $str .= "<option value='$option' ";
            if($this->selected == $option) {
                $str .= " selected ";
            }
            $str .= ">$description</option>";
        }
        $str .= "</select>";

        // Success
        return $str;
    }
}

/** HTML Form. */
class Form extends Primitive {
    /** A collection of form inputs. */
    private $inputs;

    /** Error message (optional). */
    private $error;

    /** Create a form. */
    public function __construct() {
        parent::__construct();
        $this->inputs = array();
        $this->error = null;
    }

    /** Add a form input.
     * @param input instace of Input class
     */
    public function add($input) {
        array_push($this->inputs, $input);
    }

    /** Register error message. */
    public function add_error($error) {
        $this->error = $error;
    }

    /** html() implementation. */
    public function html() {
        // Open tag, append attributes, close the tag
        $str = "<form method='post'";
        $str .= $this->attr_html();
        $str .= ">";

        // Append form inputs, close element
        foreach($this->inputs as $input) {
            $str .= $input->html() . "</br>";
        }
        $str .= "</form>";

        // Add error message (if exists)
        if(isset($this->error)) {
            $str .= "<span style='color:red'>$this->error</span><br/>";
        }

        // Success
        return $str;
    }
}

/** A table: array of array of primitives. */
class Table extends Primitive {
    /** Rows (array of arrays). */
    private $rows;

    /** Initialize a table.
     * @param header table header
     */
    public function __construct($header = null) {
        parent::__construct();
        $this->rows = array();
        if ($header != null) {
            array_push($this->rows, $header);
        }
    }

    /** Append new row.
     * @param row an array of values
     */
    public function add($row) {
        array_push($this->rows, $row);
    }

    /** html() implementation. */
    public function html() {
        if (count($this->rows) == 1) {
            return '</br>';
        }
        $str = '<div></br></div><table>';
        $tag = 'th';
        foreach($this->rows as $row) {
            $str .= '<tr>';
            foreach($row as $item) {
                $str .= "<$tag>";
                $str .= $item->html();
                $str .= "</$tag>";
            }
            $tag = 'td';
            $str .= '</tr>';
        }
        $str .= '</table>';
        return $str;
    }
}

class Listing extends Primitive {
    /** list type - ul, ol, il, etc. */
    private $type;
    /** list items */
    private $items;

    static public $list_types = array('ul', 'ol', 'dd', 'dt', 'dl');

    public function __construct($type = 'ul') {
        assert(in_array($type, Listing::$list_types));
        $this->type = $type;
        parent::__construct();
        $this->items = array();
    }

    public function add($item) {
        array_push($this->items, $item);
    }

    public function html() {
        $ret = "< $this->type>" . $this->attr_html() . '>';
        foreach ($this->childs as $primitive) {
            $ret .= '<li>' . $primitive->html() . '</li>';
        }
        $ret .= "<$this->type>";
        return $ret;
    }
}

?>
